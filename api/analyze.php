<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$config = require dirname(__DIR__) . '/config/model.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function safeExifValue(mixed $value): string|int|float|null
{
    if (is_int($value) || is_float($value)) {
        return $value;
    }
    if (!is_string($value) || strlen($value) > 2048) {
        return null;
    }
    $clean = preg_replace('/[[:cntrl:]]/', '', trim($value));
    return $clean === null || $clean === '' ? null : substr($clean, 0, 240);
}

function extractMetadata(string $path, array $imageInfo, string $mime, int $bytes): array
{
    $metadata = [
        'format' => [
            'mime' => $mime,
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
            'bytes' => $bytes,
            'bits_per_channel' => isset($imageInfo['bits']) ? (int) $imageInfo['bits'] : null,
            'channels' => isset($imageInfo['channels']) ? (int) $imageInfo['channels'] : null,
        ],
        'exif' => [],
        'exif_present' => false,
    ];

    if (!function_exists('exif_read_data') || !in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
        return $metadata;
    }

    $raw = @exif_read_data($path, null, true, false);
    if (!is_array($raw)) {
        return $metadata;
    }

    // Intentionally exclude GPS and thumbnail data from the response.
    $allowed = [
        'IFD0' => ['Make', 'Model', 'Software', 'DateTime', 'Orientation', 'Artist', 'Copyright'],
        'EXIF' => ['DateTimeOriginal', 'ExposureTime', 'FNumber', 'ISOSpeedRatings', 'FocalLength', 'LensModel', 'ColorSpace'],
        'COMPUTED' => ['ApertureFNumber', 'IsColor', 'Width', 'Height'],
    ];

    foreach ($allowed as $section => $fields) {
        if (!isset($raw[$section]) || !is_array($raw[$section])) {
            continue;
        }
        foreach ($fields as $field) {
            if (!array_key_exists($field, $raw[$section])) {
                continue;
            }
            $value = safeExifValue($raw[$section][$field]);
            if ($value !== null) {
                $metadata['exif'][strtolower($section)][$field] = $value;
            }
        }
    }

    $metadata['exif_present'] = $metadata['exif'] !== [];
    return $metadata;
}

function validateModelResponse(mixed $decoded, array $config): array
{
    if (!is_array($decoded) || !array_is_list($decoded) || count($decoded) !== 2) {
        throw new RuntimeException('MODEL_SCHEMA_INVALID');
    }

    $scores = [];
    foreach ($decoded as $item) {
        if (!is_array($item) || !isset($item['label'], $item['score']) || !is_string($item['label']) || !is_numeric($item['score'])) {
            throw new RuntimeException('MODEL_SCHEMA_INVALID');
        }
        $label = strtolower(trim($item['label']));
        $score = (float) $item['score'];
        if (!array_key_exists($label, $config['labels']) || isset($scores[$label]) || !is_finite($score) || $score < 0 || $score > 1) {
            throw new RuntimeException('MODEL_SCHEMA_INVALID');
        }
        $scores[$label] = $score;
    }

    if (!isset($scores['artificial'], $scores['human']) || abs(array_sum($scores) - 1.0) > 0.05) {
        throw new RuntimeException('MODEL_SCHEMA_INVALID');
    }

    $artificialScore = $scores['artificial'];
    if ($artificialScore >= $config['thresholds']['artificial_min']) {
        $decision = 'ai_like';
    } elseif ($artificialScore <= $config['thresholds']['human_max']) {
        $decision = 'human_like';
    } else {
        $decision = 'inconclusive';
    }

    return [
        'status' => 'ok',
        'model_id' => $config['id'],
        'label_schema' => $config['labels'],
        'scores' => $scores,
        'artificial_score' => $artificialScore,
        'decision' => $decision,
        'thresholds' => $config['thresholds'],
        'calibrated' => false,
        'score_description' => 'Raw model class score; not a calibrated probability.',
    ];
}

function callModel(string $bytes, string $token, array $config): array
{
    $curl = curl_init($config['endpoint']);
    if ($curl === false) {
        throw new RuntimeException('MODEL_CLIENT_ERROR');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $bytes,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/octet-stream',
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => $config['connect_timeout_seconds'],
        CURLOPT_TIMEOUT => $config['request_timeout_seconds'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        throw new RuntimeException('MODEL_NETWORK_ERROR' . ($curlError !== '' ? ': ' . $curlError : ''));
    }
    if ($status === 401 || $status === 403) {
        throw new RuntimeException('MODEL_AUTH_ERROR');
    }
    if ($status === 429) {
        throw new RuntimeException('MODEL_RATE_LIMITED');
    }
    if ($status === 503) {
        throw new RuntimeException('MODEL_LOADING');
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('MODEL_HTTP_ERROR:' . $status);
    }

    try {
        $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new RuntimeException('MODEL_JSON_INVALID');
    }

    return validateModelResponse($decoded, $config);
}

if (defined('AI_DETECTOR_LIBRARY_ONLY') && AI_DETECTOR_LIBRARY_ONLY === true) {
    return;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(['ok' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Use POST with a multipart image field.']], 405);
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    respond(['ok' => false, 'error' => ['code' => 'IMAGE_REQUIRED', 'message' => 'Choose an image and try again.']], 422);
}

$upload = $_FILES['image'];
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    respond(['ok' => false, 'error' => ['code' => 'UPLOAD_FAILED', 'message' => 'The image upload could not be read.']], 422);
}

$path = (string) ($upload['tmp_name'] ?? '');
$bytes = (int) ($upload['size'] ?? 0);
if ($path === '' || !is_uploaded_file($path) || $bytes < 1 || $bytes > $config['max_file_bytes']) {
    respond(['ok' => false, 'error' => ['code' => 'INVALID_IMAGE_SIZE', 'message' => 'Image must be between 1 byte and 10 MB.']], 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($path);
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/tiff'];
if (!in_array($mime, $allowedMimes, true)) {
    respond(['ok' => false, 'error' => ['code' => 'UNSUPPORTED_IMAGE_TYPE', 'message' => 'Supported formats: JPG, PNG, WebP, GIF, BMP, and TIFF.']], 415);
}

$imageInfo = @getimagesize($path);
if (!is_array($imageInfo)) {
    respond(['ok' => false, 'error' => ['code' => 'IMAGE_DECODE_FAILED', 'message' => 'The uploaded file is not a decodable image.']], 422);
}

$width = (int) $imageInfo[0];
$height = (int) $imageInfo[1];
if ($width < 16 || $height < 16 || $width > $config['max_width'] || $height > $config['max_height'] || $width * $height > $config['max_pixels']) {
    respond(['ok' => false, 'error' => ['code' => 'INVALID_IMAGE_DIMENSIONS', 'message' => 'Image dimensions must be at least 16px and no more than 8192px per side or 40 megapixels.']], 422);
}

$fileBytes = file_get_contents($path);
if ($fileBytes === false) {
    respond(['ok' => false, 'error' => ['code' => 'IMAGE_READ_FAILED', 'message' => 'The uploaded image could not be read.']], 500);
}

$metadata = extractMetadata($path, $imageInfo, $mime, $bytes);
$token = trim((string) getenv('HUGGINGFACE_API_TOKEN'));
$model = [
    'status' => 'not_configured',
    'model_id' => $config['id'],
    'label_schema' => $config['labels'],
    'thresholds' => $config['thresholds'],
    'calibrated' => false,
    'score_description' => 'Raw model class score; not a calibrated probability.',
];

if ($token !== '') {
    try {
        $model = callModel($fileBytes, $token, $config);
    } catch (RuntimeException $error) {
        $model['status'] = 'error';
        $model['error_code'] = explode(':', $error->getMessage(), 2)[0];
    }
}

respond([
    'ok' => true,
    'request_id' => bin2hex(random_bytes(8)),
    'metadata' => $metadata,
    'model' => $model,
]);
