<?php

declare(strict_types=1);

use AiImageDetector\Environment;

return [
    'huggingface' => [
        'token' => Environment::string('HUGGINGFACE_API_TOKEN'),
    ],
];
