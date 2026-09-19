<?php

declare(strict_types=1);

return [
    'id' => 'Organika/sdxl-detector',
    'endpoint' => 'https://router.huggingface.co/hf-inference/models/Organika/sdxl-detector',
    'labels' => [
        'artificial' => 0,
        'human' => 1,
    ],
    'thresholds' => [
        'human_max' => 0.35,
        'artificial_min' => 0.65,
    ],
    'calibrated' => false,
    'max_file_bytes' => 10 * 1024 * 1024,
    'max_width' => 8192,
    'max_height' => 8192,
    'max_pixels' => 40_000_000,
    'connect_timeout_seconds' => 5,
    'request_timeout_seconds' => 30,
];
