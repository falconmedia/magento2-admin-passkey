<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SecurityScore;

/**
 * Immutable snapshot of the raw signals the security score is derived from.
 *
 * Plain value object; safe to instantiate with `new`.
 */
class SecurityScoreSignals
{
    public function __construct(
        public readonly int $totalAdmins,
        public readonly int $adminsWithPasskey,
        public readonly bool $passkeyFirstLogin,
        public readonly bool $passwordFallbackEnabled,
        public readonly bool $lockoutEnabled,
        public readonly bool $trustedDevicesEnabled,
        public readonly bool $recoveryActive,
        public readonly bool $httpsEnabled,
        public readonly bool $twoFaEnabled,
        public readonly int $activeLockouts,
        public readonly int $failedLogins24h,
        public readonly int $healthErrors,
        public readonly int $healthWarnings,
        public readonly bool $cleanupEnabled,
        public readonly bool $diagnosticsEnabled
    ) {
    }

    /**
     * Ratio (0..1) of admins that have at least one active passkey.
     *
     * @return float
     */
    public function getPasskeyAdoptionRatio(): float
    {
        if ($this->totalAdmins <= 0) {
            return 0.0;
        }

        return min(1.0, max(0.0, $this->adminsWithPasskey / $this->totalAdmins));
    }
}
