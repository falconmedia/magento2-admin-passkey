<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\TrustedDevice;

/**
 * Pure decision logic for when a browser should be promoted to a trusted device.
 *
 * A browser is only remembered after the configured number of successful logins,
 * and never while it is already trusted or while the feature is disabled.
 */
class SuccessfulLoginTrustPolicy
{
    /**
     * Whether a new trusted device should be created for the current browser.
     *
     * @param bool $enabled Whether the trusted-devices feature is enabled.
     * @param bool $alreadyTrusted Whether the browser already presents a valid trusted device.
     * @param int $successCount Consecutive successful logins recorded for this browser.
     * @param int $threshold Configured successful logins required before trust.
     * @return bool
     */
    public function shouldCreateTrustedDevice(
        bool $enabled,
        bool $alreadyTrusted,
        int $successCount,
        int $threshold
    ): bool {
        if (!$enabled || $alreadyTrusted) {
            return false;
        }

        return $threshold > 0 && $successCount >= $threshold;
    }
}
