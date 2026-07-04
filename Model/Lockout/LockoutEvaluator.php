<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Lockout;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;

/**
 * Pure decision logic for lockout evaluation.
 *
 * No I/O: thresholds, windows and timestamps are all passed in, so every rule
 * (should-lock, brute-force, window membership, currently-locked and lock expiry)
 * is deterministic and fully unit-testable.
 */
class LockoutEvaluator
{
    /**
     * Multiplier over the threshold at which repeated failures are flagged as brute force.
     */
    private const BRUTE_FORCE_MULTIPLIER = 2;

    /**
     * Whether the failed-attempt count reaches the lockout threshold.
     *
     * @param int $failedAttempts
     * @param int $maxAttempts
     * @return bool
     */
    public function shouldLock(int $failedAttempts, int $maxAttempts): bool
    {
        return $maxAttempts > 0 && $failedAttempts >= $maxAttempts;
    }

    /**
     * Whether the failed-attempt count is high enough to be treated as brute force.
     *
     * @param int $failedAttempts
     * @param int $maxAttempts
     * @return bool
     */
    public function isBruteForce(int $failedAttempts, int $maxAttempts): bool
    {
        return $maxAttempts > 0 && $failedAttempts >= $maxAttempts * self::BRUTE_FORCE_MULTIPLIER;
    }

    /**
     * Whether a prior attempt timestamp still falls inside the counting window.
     *
     * @param string $previousAttemptAt Timestamp of the tracked attempt row.
     * @param string $now Current time.
     * @param int $windowMinutes Rolling window length in minutes.
     * @return bool
     */
    public function isWithinWindow(string $previousAttemptAt, string $now, int $windowMinutes): bool
    {
        if ($windowMinutes <= 0) {
            return false;
        }

        $previous = strtotime($previousAttemptAt);
        $current = strtotime($now);
        if ($previous === false || $current === false) {
            return false;
        }

        return ($current - $previous) <= ($windowMinutes * 60);
    }

    /**
     * Whether a lockout row currently blocks login.
     *
     * A row blocks when it is active and its locked_until is in the future.
     *
     * @param string|null $status
     * @param string|null $lockedUntil
     * @param string $now
     * @return bool
     */
    public function isCurrentlyLocked(?string $status, ?string $lockedUntil, string $now): bool
    {
        if ($status !== LockoutInterface::STATUS_ACTIVE || $lockedUntil === null || $lockedUntil === '') {
            return false;
        }

        $until = strtotime($lockedUntil);
        $current = strtotime($now);
        if ($until === false || $current === false) {
            return false;
        }

        return $until > $current;
    }

    /**
     * Compute the locked_until timestamp for a new lockout.
     *
     * @param string $now
     * @param int $durationMinutes
     * @return string
     */
    public function computeLockedUntil(string $now, int $durationMinutes): string
    {
        $base = strtotime($now . ' UTC');
        if ($base === false) {
            $base = time();
        }
        $minutes = $durationMinutes > 0 ? $durationMinutes : 1;

        return gmdate('Y-m-d H:i:s', $base + ($minutes * 60));
    }
}
