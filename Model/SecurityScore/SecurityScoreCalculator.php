<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SecurityScore;

/**
 * Pure security score computation.
 *
 * Given a set of raw signals and category weights, it derives per-category
 * sub-scores (0-100), the weighted total (0-100), a label and a list of
 * recommendation codes. No I/O; fully unit testable.
 */
class SecurityScoreCalculator
{
    public const CATEGORY_AUTHENTICATION = 'authentication';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_OPERATIONAL = 'operational';
    public const CATEGORY_THREATS = 'threats';

    public const LABEL_POOR = 'Poor';
    public const LABEL_FAIR = 'Fair';
    public const LABEL_GOOD = 'Good';
    public const LABEL_EXCELLENT = 'Excellent';

    public const RECOMMENDATION_INCREASE_ADOPTION = 'increase_passkey_adoption';
    public const RECOMMENDATION_ENABLE_PASSKEY_FIRST = 'enable_passkey_first';
    public const RECOMMENDATION_DISABLE_PASSWORD_FALLBACK = 'disable_password_fallback';
    public const RECOMMENDATION_ENABLE_HTTPS = 'enable_https';
    public const RECOMMENDATION_ENABLE_LOCKOUT = 'enable_lockout';
    public const RECOMMENDATION_ENABLE_CLEANUP = 'enable_cleanup';
    public const RECOMMENDATION_RESOLVE_HEALTH = 'resolve_health_errors';
    public const RECOMMENDATION_DISABLE_RECOVERY = 'disable_recovery';
    public const RECOMMENDATION_REVIEW_LOCKOUTS = 'review_active_lockouts';

    /**
     * Compute the per-category sub-scores (each clamped to 0-100).
     *
     * @param SecurityScoreSignals $signals
     * @return array<string, int>
     */
    public function categoryScores(SecurityScoreSignals $signals): array
    {
        $adoption = (int) round($signals->getPasskeyAdoptionRatio() * 60);
        $authentication = $adoption
            + ($signals->passkeyFirstLogin ? 20 : 0)
            + ($signals->twoFaEnabled ? 20 : 0);

        $security = ($signals->httpsEnabled ? 50 : 0)
            + ($signals->trustedDevicesEnabled ? 15 : 0)
            + ($signals->passwordFallbackEnabled ? 0 : 15)
            + ($signals->recoveryActive ? 0 : 20);

        $healthComponent = max(0, 30 - ($signals->healthErrors * 15) - ($signals->healthWarnings * 5));
        $operational = ($signals->cleanupEnabled ? 40 : 0)
            + ($signals->diagnosticsEnabled ? 30 : 0)
            + $healthComponent;

        $threats = 100
            - ($signals->activeLockouts * 10)
            - (min($signals->failedLogins24h, 10) * 3)
            - ($signals->healthErrors * 10);

        return [
            self::CATEGORY_AUTHENTICATION => $this->clamp($authentication),
            self::CATEGORY_SECURITY => $this->clamp($security),
            self::CATEGORY_OPERATIONAL => $this->clamp($operational),
            self::CATEGORY_THREATS => $this->clamp($threats),
        ];
    }

    /**
     * Compute the weighted total score (0-100).
     *
     * @param array<string, int> $categoryScores Per-category sub-scores (0-100).
     * @param array<string, int> $weights Per-category weights.
     * @return int
     */
    public function calculate(array $categoryScores, array $weights): int
    {
        $weightedSum = 0;
        $totalWeight = 0;
        foreach ($categoryScores as $category => $score) {
            $weight = max(0, $weights[$category] ?? 0);
            $weightedSum += $this->clamp($score) * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0) {
            return 0;
        }

        return $this->clamp((int) round($weightedSum / $totalWeight));
    }

    /**
     * Map a score to its human-readable label.
     *
     * @param int $score
     * @return string
     */
    public function label(int $score): string
    {
        return match (true) {
            $score >= 80 => self::LABEL_EXCELLENT,
            $score >= 60 => self::LABEL_GOOD,
            $score >= 40 => self::LABEL_FAIR,
            default => self::LABEL_POOR,
        };
    }

    /**
     * Build the ordered list of recommendation codes for the given signals.
     *
     * @param SecurityScoreSignals $signals
     * @return string[]
     */
    public function recommendations(SecurityScoreSignals $signals): array
    {
        $recommendations = [];

        if ($signals->totalAdmins > 0 && $signals->getPasskeyAdoptionRatio() < 1.0) {
            $recommendations[] = self::RECOMMENDATION_INCREASE_ADOPTION;
        }
        if (!$signals->passkeyFirstLogin) {
            $recommendations[] = self::RECOMMENDATION_ENABLE_PASSKEY_FIRST;
        }
        if ($signals->passwordFallbackEnabled) {
            $recommendations[] = self::RECOMMENDATION_DISABLE_PASSWORD_FALLBACK;
        }
        if (!$signals->httpsEnabled) {
            $recommendations[] = self::RECOMMENDATION_ENABLE_HTTPS;
        }
        if (!$signals->lockoutEnabled) {
            $recommendations[] = self::RECOMMENDATION_ENABLE_LOCKOUT;
        }
        if (!$signals->cleanupEnabled) {
            $recommendations[] = self::RECOMMENDATION_ENABLE_CLEANUP;
        }
        if ($signals->healthErrors > 0) {
            $recommendations[] = self::RECOMMENDATION_RESOLVE_HEALTH;
        }
        if ($signals->recoveryActive) {
            $recommendations[] = self::RECOMMENDATION_DISABLE_RECOVERY;
        }
        if ($signals->activeLockouts > 0) {
            $recommendations[] = self::RECOMMENDATION_REVIEW_LOCKOUTS;
        }

        return $recommendations;
    }

    /**
     * Clamp a value to the 0-100 range.
     *
     * @param int $value
     * @return int
     */
    private function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}
