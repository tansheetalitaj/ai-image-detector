<?php

declare(strict_types=1);

use AiImageDetector\Environment;

$autoload = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException('Dependencies are missing. Run "composer install" in the project root.');
}

require_once $autoload;

Environment::load(__DIR__);
