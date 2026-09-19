<?php

declare(strict_types=1);

namespace AiImageDetector\Tests;

use AiImageDetector\ImageValidator;
use DomainException;
use PHPUnit\Framework\TestCase;

final class ImageValidatorTest extends TestCase
{
    private array $temporaryFiles = [];
    private ImageValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ImageValidator(require dirname(__DIR__, 3) . '/config/model.php');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testAcceptsAValidImageAndReturnsRealMetadata(): void
    {
        $path = $this->createPng(16, 16);
        $result = $this->validator->validateFile($path);

        self::assertSame('image/png', $result['metadata']['format']['mime']);
        self::assertSame(16, $result['metadata']['format']['width']);
        self::assertSame(16, $result['metadata']['format']['height']);
        self::assertNotSame('', $result['bytes']);
    }

    public function testRejectsImagesBelowMinimumDimensions(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('INVALID_IMAGE_DIMENSIONS');
        $this->validator->validateFile($this->createPng(8, 8));
    }

    public function testRejectsNonImageFiles(): void
    {
        $path = $this->temporaryPath();
        file_put_contents($path, 'not an image');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('UNSUPPORTED_IMAGE_TYPE');
        $this->validator->validateFile($path);
    }

    private function createPng(int $width, int $height): string
    {
        $path = $this->temporaryPath();
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);
        return $path;
    }

    private function temporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'aid-test-');
        self::assertNotFalse($path);
        $this->temporaryFiles[] = $path;
        return $path;
    }
}
