<?php

declare(strict_types=1);

namespace AiImageDetector\Tests;

use AiImageDetector\ModelResponseParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelResponseParserTest extends TestCase
{
    private ModelResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ModelResponseParser(require dirname(__DIR__, 3) . '/config/model.php');
    }

    #[DataProvider('validResponses')]
    public function testParsesValidResponses(float $artificial, float $human, string $decision): void
    {
        $result = $this->parser->parse([
            ['label' => 'artificial', 'score' => $artificial],
            ['label' => 'human', 'score' => $human],
        ]);

        self::assertSame($decision, $result['decision']);
        self::assertFalse($result['calibrated']);
    }

    public static function validResponses(): array
    {
        return [
            'AI-like' => [0.82, 0.18, 'ai_like'],
            'human-like' => [0.22, 0.78, 'human_like'],
            'inconclusive' => [0.50, 0.50, 'inconclusive'],
        ];
    }

    #[DataProvider('invalidResponses')]
    public function testRejectsInvalidResponses(mixed $payload): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MODEL_SCHEMA_INVALID');
        $this->parser->parse($payload);
    }

    public static function invalidResponses(): array
    {
        return [
            'nested result' => [[[['label' => 'artificial', 'score' => 0.9]]]],
            'unknown label' => [[['label' => 'fake', 'score' => 0.9], ['label' => 'human', 'score' => 0.1]]],
            'out of range' => [[['label' => 'artificial', 'score' => 1.2], ['label' => 'human', 'score' => -0.2]]],
            'not normalized' => [[['label' => 'artificial', 'score' => 0.4], ['label' => 'human', 'score' => 0.4]]],
        ];
    }
}
