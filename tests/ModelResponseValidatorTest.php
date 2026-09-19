<?php

declare(strict_types=1);

const AI_DETECTOR_LIBRARY_ONLY = true;
require dirname(__DIR__) . '/api/analyze.php';
$config = require dirname(__DIR__) . '/config/model.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assertRejected(mixed $payload, array $config): void
{
    try {
        validateModelResponse($payload, $config);
    } catch (RuntimeException $error) {
        assertSameValue('MODEL_SCHEMA_INVALID', $error->getMessage(), 'Unexpected rejection code');
        return;
    }
    throw new RuntimeException('Invalid payload was accepted');
}

$ai = validateModelResponse([
    ['label' => 'artificial', 'score' => 0.82],
    ['label' => 'human', 'score' => 0.18],
], $config);
assertSameValue('ai_like', $ai['decision'], 'AI-like threshold');
assertSameValue(false, $ai['calibrated'], 'Calibration flag');

$human = validateModelResponse([
    ['label' => 'human', 'score' => 0.78],
    ['label' => 'artificial', 'score' => 0.22],
], $config);
assertSameValue('human_like', $human['decision'], 'Human-like threshold');

$uncertain = validateModelResponse([
    ['label' => 'artificial', 'score' => 0.50],
    ['label' => 'human', 'score' => 0.50],
], $config);
assertSameValue('inconclusive', $uncertain['decision'], 'Inconclusive band');

assertRejected([[['label' => 'artificial', 'score' => 0.9]]], $config);
assertRejected([['label' => 'fake', 'score' => 0.9], ['label' => 'human', 'score' => 0.1]], $config);
assertRejected([['label' => 'artificial', 'score' => 1.2], ['label' => 'human', 'score' => -0.2]], $config);
assertRejected([['label' => 'artificial', 'score' => 0.4], ['label' => 'human', 'score' => 0.4]], $config);

echo "Model response validator tests passed.\n";
