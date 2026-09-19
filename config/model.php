<?php

declare(strict_types=1);

use AiImageDetector\Environment;

$modelId = Environment::string('HUGGINGFACE_MODEL_ID', 'Organika/sdxl-detector');
$humanMax = Environment::float('MODEL_HUMAN_MAX_SCORE', 0.35, 0.0, 1.0);
$artificialMin = Environment::float('MODEL_ARTIFICIAL_MIN_SCORE', 0.65, 0.0, 1.0);

if ($humanMax >= $artificialMin) {
    throw new UnexpectedValueException(
        'MODEL_HUMAN_MAX_SCORE must be lower than MODEL_ARTIFICIAL_MIN_SCORE.'
    );
}

return [
    'id' => $modelId,
    'endpoint' => Environment::string(
        'HUGGINGFACE_API_ENDPOINT',
        'https://router.huggingface.co/hf-inference/models/' . $modelId
    ),
    'labels' => [
        'artificial' => 0,
        'human' => 1,
    ],
    'thresholds' => [
        'human_max' => $humanMax,
        'artificial_min' => $artificialMin,
    ],
    'calibrated' => false,
    'max_file_bytes' => Environment::int('UPLOAD_MAX_FILE_BYTES', 10 * 1024 * 1024, 1),
    'max_width' => Environment::int('UPLOAD_MAX_WIDTH', 8192, 16),
    'max_height' => Environment::int('UPLOAD_MAX_HEIGHT', 8192, 16),
    'max_pixels' => Environment::int('UPLOAD_MAX_PIXELS', 40_000_000, 256),
    'connect_timeout_seconds' => Environment::int('MODEL_CONNECT_TIMEOUT_SECONDS', 5, 1, 120),
    'request_timeout_seconds' => Environment::int('MODEL_REQUEST_TIMEOUT_SECONDS', 30, 1, 300),
];
