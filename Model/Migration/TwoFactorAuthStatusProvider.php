<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\UserConfigManagerInterface;

/**
 * Adapter that reports the native Magento Two-Factor Authentication status of an
 * admin user, isolating this module from the TwoFactorAuth internals.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class TwoFactorAuthStatusProvider
{
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CONFIGURED = 'configured';
    public const STATUS_NONE = 'none';

    public function __construct(
        private readonly TfaInterface $tfa,
        private readonly UserConfigManagerInterface $userConfigManager
    ) {
    }

    /**
     * Whether native 2FA is enabled globally.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        try {
            return $this->tfa->isEnabled();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Resolve the 2FA status for a single admin user.
     *
     * @param int $adminUserId
     * @return string One of the STATUS_* constants.
     */
    public function getStatusForUser(int $adminUserId): string
    {
        if (!$this->isEnabled()) {
            return self::STATUS_DISABLED;
        }

        try {
            $providers = $this->tfa->getUserProviders($adminUserId);
            if ($providers === []) {
                return self::STATUS_NONE;
            }

            foreach ($providers as $provider) {
                $code = $provider->getCode();
                if ($code !== '' && $this->userConfigManager->isProviderConfigurationActive($adminUserId, $code)) {
                    return self::STATUS_ACTIVE;
                }
            }

            return self::STATUS_CONFIGURED;
        } catch (\Throwable) {
            return self::STATUS_NONE;
        }
    }
}
