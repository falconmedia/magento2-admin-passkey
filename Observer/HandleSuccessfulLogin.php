<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Observer;

use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use FalconMedia\AdminPasskey\Model\TrustedDevice\TrustedDeviceManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Header as HttpHeader;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * On a successful Admin login (password or passkey — both dispatch the native
 * backend_auth_user_login_success event), clears any failure tracking for the
 * account and lets the trusted-device manager remember the browser once the
 * configured threshold is reached.
 *
 * Never throws: a post-login side effect must never break the session that was
 * just established.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class HandleSuccessfulLogin implements ObserverInterface
{
    public function __construct(
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly TrustedDeviceManagerInterface $trustedDeviceManager,
        private readonly RemoteAddress $remoteAddress,
        private readonly HttpHeader $httpHeader,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $user = $observer->getEvent()->getData('user');
            if (!is_object($user) || !method_exists($user, 'getId')) {
                return;
            }
            $adminUserId = (int) $user->getId();
            if ($adminUserId <= 0) {
                return;
            }

            $username = method_exists($user, 'getUserName') ? (string) $user->getUserName() : null;
            $ip = $this->resolveIp();
            $userAgent = $this->resolveUserAgent();

            $this->lockoutManager->registerSuccessfulAttempt($adminUserId, $username, $ip);
            $this->trustedDeviceManager->handleSuccessfulLogin($adminUserId, $ip, $userAgent);
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey successful-login handling failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the remote IP address, or null.
     *
     * @return string|null
     */
    private function resolveIp(): ?string
    {
        $ip = (string) $this->remoteAddress->getRemoteAddress();

        return $ip === '' ? null : $ip;
    }

    /**
     * Resolve the request user agent, or null.
     *
     * @return string|null
     */
    private function resolveUserAgent(): ?string
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();

        return $userAgent === '' ? null : $userAgent;
    }
}
