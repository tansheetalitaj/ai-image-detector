<?php

declare(strict_types=1);

namespace AiImageDetector;

use DomainException;
use finfo;

final class ImageValidator
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/tiff'];

    public function __construct(private readonly array $config)
    {
    }

    public function validateUpload(array $upload): array
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new DomainException('UPLOAD_FAILED');
        }

        $path = (string) ($upload['tmp_name'] ?? '');
        if ($path === '' || !is_uploaded_file($path)) {
            throw new DomainException('INVALID_IMAGE_SIZE');
        }

        return $this->validateFile($path, (int) ($upload['size'] ?? 0));
    }

    public function validateFile(string $path, ?int $reportedBytes = null): array
    {
        $bytes = $reportedBytes ?? (is_file($path) ? (int) filesize($path) : 0);
        if (!is_file($path) || $bytes < 1 || $bytes > $this->config['max_file_bytes']) {
            throw new DomainException('INVALID_IMAGE_SIZE');
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new DomainException('UNSUPPORTED_IMAGE_TYPE');
        }

        $imageInfo = @getimagesize($path);
        if (!is_array($imageInfo)) {
            throw new DomainException('IMAGE_DECODE_FAILED');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        if ($width < 16 || $height < 16 || $width > $this->config['max_width'] || $height > $this->config['max_height'] || $width * $height > $this->config['max_pixels']) {
            throw new DomainException('INVALID_IMAGE_DIMENSIONS');
        }

        $fileBytes = file_get_contents($path);
        if ($fileBytes === false) {
            throw new DomainException('IMAGE_READ_FAILED');
        }

        return [
            'bytes' => $fileBytes,
            'metadata' => $this->extractMetadata($path, $imageInfo, $mime, $bytes),
        ];
    }

    private function extractMetadata(string $path, array $imageInfo, string $mime, int $bytes): array
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

        $allowed = [
            'IFD0' => ['Make', 'Model', 'Software', 'DateTime', 'Orientation', 'Artist', 'Copyright'],
            'EXIF' => ['DateTimeOriginal', 'ExposureTime', 'FNumber', 'ISOSpeedRatings', 'FocalLength', 'LensModel', 'ColorSpace'],
            'COMPUTED' => ['ApertureFNumber', 'IsColor', 'Width', 'Height'],
        ];

        foreach ($allowed as $section => $fields) {
            foreach ($fields as $field) {
                if (!isset($raw[$section]) || !is_array($raw[$section]) || !array_key_exists($field, $raw[$section])) {
                    continue;
                }
                $value = $this->safeExifValue($raw[$section][$field]);
                if ($value !== null) {
                    $metadata['exif'][strtolower($section)][$field] = $value;
                }
            }
        }

        $metadata['exif_present'] = $metadata['exif'] !== [];
        return $metadata;
    }

    private function safeExifValue(mixed $value): string|int|float|null
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
}
