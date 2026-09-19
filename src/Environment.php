<?php

declare(strict_types=1);

namespace AiImageDetector;

use Dotenv\Dotenv;
use UnexpectedValueException;

final class Environment
{
    private static bool $loaded = false;

    public static function load(string $projectRoot): void
    {
        if (self::$loaded) {
            return;
        }

        Dotenv::createImmutable($projectRoot)->safeLoad();
        self::$loaded = true;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::value($key);
        if ($value === null || trim($value) === '') {
            return $default;
        }

        return trim($value);
    }

    public static function int(
        string $key,
        int $default,
        ?int $minimum = null,
        ?int $maximum = null
    ): int {
        $raw = self::value($key);
        if ($raw === null || trim($raw) === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ($value === false || ($minimum !== null && $value < $minimum) || ($maximum !== null && $value > $maximum)) {
            throw new UnexpectedValueException("Environment variable {$key} must be a valid integer in range.");
        }

        return $value;
    }

    public static function float(
        string $key,
        float $default,
        ?float $minimum = null,
        ?float $maximum = null
    ): float {
        $raw = self::value($key);
        if ($raw === null || trim($raw) === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_FLOAT);
        if ($value === false || ($minimum !== null && $value < $minimum) || ($maximum !== null && $value > $maximum)) {
            throw new UnexpectedValueException("Environment variable {$key} must be a valid number in range.");
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $raw = self::value($key);
        if ($raw === null || trim($raw) === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new UnexpectedValueException("Environment variable {$key} must be true or false.");
        }

        return $value;
    }

    private static function value(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) ? $value : null;
    }
}
