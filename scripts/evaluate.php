<?php

declare(strict_types=1);

function fail(string $message): never
{
    fwrite(STDERR, "Evaluation failed: {$message}\n");
    exit(1);
}

function metric(int $numerator, int $denominator): ?float
{
    return $denominator > 0 ? $numerator / $denominator : null;
}

function summarize(array $rows): array
{
    $counts = ['total' => count($rows), 'tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0, 'inconclusive' => 0, 'artificial' => 0, 'human' => 0];
    foreach ($rows as $row) {
        $actual = $row['label'];
        $decision = $row['decision'];
        $counts[$actual]++;
        if ($decision === 'inconclusive') {
            $counts['inconclusive']++;
        } elseif ($actual === 'artificial' && $decision === 'ai_like') {
            $counts['tp']++;
        } elseif ($actual === 'artificial' && $decision === 'human_like') {
            $counts['fn']++;
        } elseif ($actual === 'human' && $decision === 'ai_like') {
            $counts['fp']++;
        } elseif ($actual === 'human' && $decision === 'human_like') {
            $counts['tn']++;
        }
    }

    return $counts + [
        'coverage' => metric($counts['total'] - $counts['inconclusive'], $counts['total']),
        'precision' => metric($counts['tp'], $counts['tp'] + $counts['fp']),
        // Inconclusive artificial samples remain missed positives in recall.
        'recall' => metric($counts['tp'], $counts['artificial']),
        'false_positive_rate' => metric($counts['fp'], $counts['human']),
        'inconclusive_rate' => metric($counts['inconclusive'], $counts['total']),
    ];
}

function pct(?float $value): string
{
    return $value === null ? 'n/a' : number_format($value * 100, 1) . '%';
}

$options = getopt('', ['endpoint::', 'manifest::']);
$root = dirname(__DIR__);
$manifest = $options['manifest'] ?? $root . '/evaluation/manifest.csv';
$endpoint = $options['endpoint'] ?? 'http://ai-image-detector.local/api/analyze.php';

if (!is_file($manifest) || ($handle = fopen($manifest, 'rb')) === false) {
    fail("manifest not found: {$manifest}");
}

$header = fgetcsv($handle);
$required = ['id', 'file', 'label', 'image_type', 'generator', 'edit_type', 'source', 'license', 'split', 'notes'];
if ($header !== $required) {
    fail('manifest header does not match the documented schema');
}

$manifestRows = [];
while (($values = fgetcsv($handle)) !== false) {
    if ($values === [null] || $values === []) {
        continue;
    }
    if (count($values) !== count($header)) {
        fail('manifest contains a malformed row');
    }
    $row = array_combine($header, $values);
    if (!in_array($row['label'], ['artificial', 'human'], true)) {
        fail("invalid label for {$row['id']}");
    }
    if (!in_array($row['image_type'], ['photograph', 'digital_art', 'edited_image', 'screenshot', 'generated'], true)) {
        fail("invalid image_type for {$row['id']}");
    }
    $manifestRows[] = $row;
}
fclose($handle);

if ($manifestRows === []) {
    fail('manifest has no labeled samples; metrics would be meaningless');
}

$results = [];
foreach ($manifestRows as $row) {
    $path = realpath($root . '/' . ltrim(str_replace('\\', '/', $row['file']), '/'));
    if ($path === false || !is_file($path)) {
        fail("missing file for {$row['id']}: {$row['file']}");
    }
    $curl = curl_init($endpoint);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['image' => new CURLFile($path)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        fail("endpoint failed for {$row['id']} ({$status} {$error})");
    }
    $payload = json_decode($body, true);
    if (!is_array($payload) || ($payload['model']['status'] ?? null) !== 'ok') {
        fail("model result unavailable for {$row['id']}; configure the server token first");
    }
    $results[] = $row + [
        'decision' => $payload['model']['decision'],
        'artificial_score' => $payload['model']['artificial_score'],
    ];
    fwrite(STDERR, ".");
}
fwrite(STDERR, "\n");

$overall = summarize($results);
$groups = [];
foreach ($results as $result) {
    $groups[$result['image_type']][] = $result;
}
ksort($groups);

echo "# Evaluation report\n\n";
echo "Model: Organika/sdxl-detector\n\n";
echo "Inconclusive band: artificial score > 0.35 and < 0.65\n\n";
echo "| Segment | N | Coverage | Precision | Recall | False-positive rate | Inconclusive |\n";
echo "| --- | ---: | ---: | ---: | ---: | ---: | ---: |\n";
printf("| Overall | %d | %s | %s | %s | %s | %s |\n", $overall['total'], pct($overall['coverage']), pct($overall['precision']), pct($overall['recall']), pct($overall['false_positive_rate']), pct($overall['inconclusive_rate']));
foreach ($groups as $name => $groupRows) {
    $summary = summarize($groupRows);
    printf("| %s | %d | %s | %s | %s | %s | %s |\n", $name, $summary['total'], pct($summary['coverage']), pct($summary['precision']), pct($summary['recall']), pct($summary['false_positive_rate']), pct($summary['inconclusive_rate']));
}

echo "\nPrecision treats `artificial` as the positive class. Recall includes inconclusive artificial samples in its denominator; false-positive rate is AI-like decisions divided by all human samples.\n";
