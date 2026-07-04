<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

/**
 * Immutable passkey-adoption summary across admin users.
 *
 * Plain value object; safe to instantiate with `new`.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class AdoptionStats
{
    /**
     * @param int $totalAdmins Number of admin accounts counted.
     * @param int $withPasskey Admins that own at least one active passkey.
     * @param int $withoutPasskey Admins with no active passkey.
     * @param float $adoptionPercent Passkey adoption percentage (0-100, one decimal).
     * @param int $twoFaActive Admins with an active native 2FA provider.
     * @param int $twoFaConfigured Admins with a configured but not active 2FA provider.
     * @param int $twoFaNone Admins with 2FA enabled globally but none configured.
     * @param int $twoFaDisabled Admins counted while native 2FA is disabled globally.
     * @param bool $twoFaEnabledGlobally Whether native 2FA is enabled globally.
     */
    public function __construct(
        private readonly int $totalAdmins,
        private readonly int $withPasskey,
        private readonly int $withoutPasskey,
        private readonly float $adoptionPercent,
        private readonly int $twoFaActive,
        private readonly int $twoFaConfigured,
        private readonly int $twoFaNone,
        private readonly int $twoFaDisabled,
        private readonly bool $twoFaEnabledGlobally
    ) {
    }

    /**
     * Number of admin accounts counted.
     *
     * @return int
     */
    public function getTotalAdmins(): int
    {
        return $this->totalAdmins;
    }

    /**
     * Admins that own at least one active passkey.
     *
     * @return int
     */
    public function getWithPasskey(): int
    {
        return $this->withPasskey;
    }

    /**
     * Admins with no active passkey.
     *
     * @return int
     */
    public function getWithoutPasskey(): int
    {
        return $this->withoutPasskey;
    }

    /**
     * Passkey adoption percentage (0-100, one decimal).
     *
     * @return float
     */
    public function getAdoptionPercent(): float
    {
        return $this->adoptionPercent;
    }

    /**
     * Admins with an active native 2FA provider.
     *
     * @return int
     */
    public function getTwoFaActive(): int
    {
        return $this->twoFaActive;
    }

    /**
     * Admins with a configured but not active 2FA provider.
     *
     * @return int
     */
    public function getTwoFaConfigured(): int
    {
        return $this->twoFaConfigured;
    }

    /**
     * Admins with 2FA enabled globally but none configured.
     *
     * @return int
     */
    public function getTwoFaNone(): int
    {
        return $this->twoFaNone;
    }

    /**
     * Admins counted while native 2FA is disabled globally.
     *
     * @return int
     */
    public function getTwoFaDisabled(): int
    {
        return $this->twoFaDisabled;
    }

    /**
     * Whether native 2FA is enabled globally.
     *
     * @return bool
     */
    public function isTwoFaEnabledGlobally(): bool
    {
        return $this->twoFaEnabledGlobally;
    }

    /**
     * Array representation for JSON output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_admins' => $this->totalAdmins,
            'with_passkey' => $this->withPasskey,
            'without_passkey' => $this->withoutPasskey,
            'adoption_percent' => $this->adoptionPercent,
            'two_fa_enabled_globally' => $this->twoFaEnabledGlobally,
            'two_fa' => [
                'active' => $this->twoFaActive,
                'configured' => $this->twoFaConfigured,
                'none' => $this->twoFaNone,
                'disabled' => $this->twoFaDisabled,
            ],
        ];
    }
}
