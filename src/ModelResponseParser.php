<?php

declare(strict_types=1);

namespace AiImageDetector;

use RuntimeException;

final class ModelResponseParser
{
    public function __construct(private readonly array $config)
    {
    }

    public function parse(mixed $decoded): array
    {
        if (!is_array($decoded) || !array_is_list($decoded) || count($decoded) !== 2) {
            throw new RuntimeException('MODEL_SCHEMA_INVALID');
        }

        $scores = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['label'], $item['score']) || !is_string($item['label']) || !is_numeric($item['score'])) {
                throw new RuntimeException('MODEL_SCHEMA_INVALID');
            }

            $label = strtolower(trim($item['label']));
            $score = (float) $item['score'];
            if (!array_key_exists($label, $this->config['labels']) || isset($scores[$label]) || !is_finite($score) || $score < 0 || $score > 1) {
                throw new RuntimeException('MODEL_SCHEMA_INVALID');
            }
            $scores[$label] = $score;
        }

        if (!isset($scores['artificial'], $scores['human']) || abs(array_sum($scores) - 1.0) > 0.05) {
            throw new RuntimeException('MODEL_SCHEMA_INVALID');
        }

        $artificialScore = $scores['artificial'];
        return [
            'status' => 'ok',
            'model_id' => $this->config['id'],
            'label_schema' => $this->config['labels'],
            'scores' => $scores,
            'artificial_score' => $artificialScore,
            'decision' => $this->decisionForScore($artificialScore),
            'thresholds' => $this->config['thresholds'],
            'calibrated' => false,
            'score_description' => 'Raw model class score; not a calibrated probability.',
        ];
    }

    public function decisionForScore(float $artificialScore): string
    {
        if ($artificialScore >= $this->config['thresholds']['artificial_min']) {
            return 'ai_like';
        }
        if ($artificialScore <= $this->config['thresholds']['human_max']) {
            return 'human_like';
        }
        return 'inconclusive';
    }
}
