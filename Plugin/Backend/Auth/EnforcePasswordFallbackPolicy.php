<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Plugin\Backend\Auth;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Magento\Backend\Model\Auth;
use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;

/**
 * Server-side enforcement of "Password fallback: No".
 *
 * Hiding the password form in the login templates is not a control: a direct
 * POST to adminhtml/auth/login would still authenticate. This plugin rejects
 * every native password login while the module is enabled and the password
 * fallback is switched off: on {@see Auth::login()} (Admin login form, clear
 * message) and on {@see User::authenticate()}, which also covers the admin REST
 * token endpoints (integration/admin/token and the 2FA provider token endpoints).
 * Step-up checks via {@see User::performIdentityCheck()} are not affected. Emergency recovery mode lifts the block so admins
 * can always regain access; a recovery-state lookup error also lifts it (fail
 * open, so a storage error can never lock every admin out).
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class EnforcePasswordFallbackPolicy
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Reject native password login when the password fallback is disabled.
     *
     * @param Auth $subject
     * @param string $username
     * @param string $password
     * @return void
     * @throws AuthenticationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLogin(Auth $subject, $username, $password): void
    {
        $this->assertPasswordLoginAllowed();
    }

    /**
     * Reject username/password authentication of an admin user (all areas, incl. web API).
     *
     * @param User $subject
     * @param string $username
     * @param string $password
     * @return void
     * @throws AuthenticationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAuthenticate(User $subject, $username, $password): void
    {
        $this->assertPasswordLoginAllowed();
    }

    /**
     * Throw when password login is currently not allowed.
     *
     * @return void
     * @throws AuthenticationException
     */
    private function assertPasswordLoginAllowed(): void
    {
        if (!$this->configProvider->isEnabled() || $this->configProvider->isPasswordFallbackEnabled()) {
            return;
        }

        if ($this->isRecoveryActive()) {
            return;
        }

        throw new AuthenticationException(__('Password login is disabled. Please sign in with your passkey.'));
    }

    /**
     * Whether emergency recovery mode is active (fail open on error).
     *
     * @return bool
     */
    private function isRecoveryActive(): bool
    {
        try {
            return $this->recoveryModeService->isActive();
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey recovery check failed: ' . $e->getMessage());

            return true;
        }
    }
}
