<?php

declare(strict_types=1);

namespace AiImageDetector\Tests;

use AiImageDetector\Environment;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class EnvironmentTest extends TestCase
{
    private const KEY = 'TRACELENS_TEST_VALUE';

    protected function tearDown(): void
    {
        unset($_ENV[self::KEY], $_SERVER[self::KEY]);
    }

    public function testStringUsesDefaultForMissingOrEmptyValues(): void
    {
        self::assertSame('fallback', Environment::string(self::KEY, 'fallback'));

        $_ENV[self::KEY] = '  ';
        self::assertSame('fallback', Environment::string(self::KEY, 'fallback'));
    }

    public function testReadsTypedValues(): void
    {
        $_ENV[self::KEY] = '42';
        self::assertSame(42, Environment::int(self::KEY, 1, 1, 100));

        $_ENV[self::KEY] = '0.65';
        self::assertSame(0.65, Environment::float(self::KEY, 0.5, 0.0, 1.0));

        $_ENV[self::KEY] = 'true';
        self::assertTrue(Environment::bool(self::KEY));
    }

    public function testRejectsInvalidTypedValues(): void
    {
        $_ENV[self::KEY] = 'not-a-number';

        $this->expectException(UnexpectedValueException::class);
        Environment::int(self::KEY, 1);
    }
}
