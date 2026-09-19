<?php

declare(strict_types=1);

use AiImageDetector\ApiClient;
use AiImageDetector\ImageValidator;
use AiImageDetector\ModelResponseParser;

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$config = require dirname(__DIR__) . '/config/model.php';
$services = require dirname(__DIR__) . '/config/services.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(['ok' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Use POST with a multipart image field.']], 405);
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    respond(['ok' => false, 'error' => ['code' => 'IMAGE_REQUIRED', 'message' => 'Choose an image and try again.']], 422);
}

$messages = [
    'UPLOAD_FAILED' => ['The image upload could not be read.', 422],
    'INVALID_IMAGE_SIZE' => [
        sprintf('Image must be between 1 byte and %s MB.', number_format($config['max_file_bytes'] / 1048576, 1)),
        422,
    ],
    'UNSUPPORTED_IMAGE_TYPE' => ['Supported formats: JPG, PNG, WebP, GIF, BMP, and TIFF.', 415],
    'IMAGE_DECODE_FAILED' => ['The uploaded file is not a decodable image.', 422],
    'INVALID_IMAGE_DIMENSIONS' => [
        sprintf(
            'Image dimensions must be at least 16px, no more than %d × %dpx, and no more than %s megapixels.',
            $config['max_width'],
            $config['max_height'],
            number_format($config['max_pixels'] / 1000000, 1)
        ),
        422,
    ],
    'IMAGE_READ_FAILED' => ['The uploaded image could not be read.', 500],
];

try {
    $validated = (new ImageValidator($config))->validateUpload($_FILES['image']);
} catch (DomainException $error) {
    [$message, $status] = $messages[$error->getMessage()] ?? ['The uploaded image is invalid.', 422];
    respond(['ok' => false, 'error' => ['code' => $error->getMessage(), 'message' => $message]], $status);
}

$token = $services['huggingface']['token'];
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
        $parser = new ModelResponseParser($config);
        $model = (new ApiClient($config, $parser))->classify($validated['bytes'], $token);
    } catch (RuntimeException $error) {
        $model['status'] = 'error';
        $model['error_code'] = explode(':', $error->getMessage(), 2)[0];
    }
}

respond([
    'ok' => true,
    'request_id' => bin2hex(random_bytes(8)),
    'metadata' => $validated['metadata'],
    'model' => $model,
]);
