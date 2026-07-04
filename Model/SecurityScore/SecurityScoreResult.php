<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SecurityScore;

/**
 * Immutable computed security score.
 *
 * Plain value object; safe to instantiate with `new`.
 */
class SecurityScoreResult
{
    /**
     * @param int $score Total score, 0-100.
     * @param string $label Human-readable label (Poor|Fair|Good|Excellent).
     * @param array<string, int> $breakdown Per-category score, 0-100.
     * @param array<int, array{code: string, message: string}> $recommendations
     */
    public function __construct(
        private readonly int $score,
        private readonly string $label,
        private readonly array $breakdown,
        private readonly array $recommendations
    ) {
    }

    /**
     * Total score, 0-100.
     *
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * Human-readable score label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Per-category score breakdown.
     *
     * @return array<string, int>
     */
    public function getBreakdown(): array
    {
        return $this->breakdown;
    }

    /**
     * Recommendations to improve the score.
     *
     * @return array<int, array{code: string, message: string}>
     */
    public function getRecommendations(): array
    {
        return $this->recommendations;
    }
}
