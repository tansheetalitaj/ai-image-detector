<?php

declare(strict_types=1);

namespace AiImageDetector;

use JsonException;
use RuntimeException;

final class ApiClient
{
    public function __construct(
        private readonly array $config,
        private readonly ModelResponseParser $parser,
    ) {
    }

    public function classify(string $imageBytes, string $token): array
    {
        $curl = curl_init($this->config['endpoint']);
        if ($curl === false) {
            throw new RuntimeException('MODEL_CLIENT_ERROR');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $imageBytes,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/octet-stream',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout_seconds'],
            CURLOPT_TIMEOUT => $this->config['request_timeout_seconds'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('MODEL_NETWORK_ERROR' . ($curlError !== '' ? ': ' . $curlError : ''));
        }
        if ($status === 401 || $status === 403) {
            throw new RuntimeException('MODEL_AUTH_ERROR');
        }
        if ($status === 429) {
            throw new RuntimeException('MODEL_RATE_LIMITED');
        }
        if ($status === 503) {
            throw new RuntimeException('MODEL_LOADING');
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('MODEL_HTTP_ERROR:' . $status);
        }

        try {
            $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('MODEL_JSON_INVALID');
        }

        return $this->parser->parse($decoded);
    }
}
