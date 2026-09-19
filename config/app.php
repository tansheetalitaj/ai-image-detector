<?php

declare(strict_types=1);

use AiImageDetector\Environment;

$appUrl = rtrim(Environment::string('APP_URL', 'http://ai-image-detector.local'), '/');
$apiPath = Environment::string('APP_API_PATH', '/api/analyze.php');

return [
    'environment' => Environment::string('APP_ENV', 'local'),
    'debug' => Environment::bool('APP_DEBUG', false),
    'url' => $appUrl,
    'api_path' => $apiPath,
    'evaluation_endpoint' => Environment::string('EVALUATION_API_ENDPOINT', $appUrl . $apiPath),
];
