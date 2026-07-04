<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Recovery;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;

/**
 * Pure decision logic for emergency recovery-mode state transitions.
 *
 * No I/O: the current state, its enabled timestamp, the auto-expiry window and the
 * current time are all passed in, so activeness, expiry and allowed transitions
 * are deterministic and fully unit-testable.
 */
class RecoveryStateTransitionEvaluator
{
    /**
     * Whether recovery mode is currently active (enabled and not auto-expired).
     *
     * @param string|null $state
     * @param string|null $enabledAt
     * @param int $expiryMinutes Auto-expiry window (0 disables auto-expiry).
     * @param string $now
     * @return bool
     */
    public function isActive(?string $state, ?string $enabledAt, int $expiryMinutes, string $now): bool
    {
        if ($state !== RecoveryStateInterface::STATE_ENABLED) {
            return false;
        }

        return !$this->isExpired($enabledAt, $expiryMinutes, $now);
    }

    /**
     * Whether an enabled recovery mode has passed its auto-expiry window.
     *
     * @param string|null $enabledAt
     * @param int $expiryMinutes
     * @param string $now
     * @return bool
     */
    public function isExpired(?string $enabledAt, int $expiryMinutes, string $now): bool
    {
        if ($expiryMinutes <= 0 || $enabledAt === null || $enabledAt === '') {
            return false;
        }

        $enabled = strtotime($enabledAt);
        $current = strtotime($now);
        if ($enabled === false || $current === false) {
            return false;
        }

        return ($enabled + ($expiryMinutes * 60)) <= $current;
    }

    /**
     * Whether recovery mode may be enabled given its current activeness.
     *
     * @param bool $currentlyActive
     * @return bool
     */
    public function canEnable(bool $currentlyActive): bool
    {
        return !$currentlyActive;
    }

    /**
     * Whether recovery mode may be disabled given its current activeness.
     *
     * @param bool $currentlyActive
     * @return bool
     */
    public function canDisable(bool $currentlyActive): bool
    {
        return $currentlyActive;
    }
}
