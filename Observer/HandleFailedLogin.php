<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Observer;

use FalconMedia\AdminPasskey\Model\Login\LoginAttemptRecorderInterface;
use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * On a failed native Admin (password) login, feeds the failed-attempt counter so
 * repeated password failures drive the same durable lockout as passkey failures.
 *
 * The native backend_auth_user_login_failed event carries the attempted username.
 * Never throws: a failed-login side effect must never break the login response.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class HandleFailedLogin implements ObserverInterface
{
    public function __construct(
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly RemoteAddress $remoteAddress,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $username = $observer->getEvent()->getData('user_name');
            $username = is_string($username) && $username !== '' ? $username : null;
            $ip = $this->resolveIp();

            $this->lockoutManager->registerFailedAttempt(
                null,
                $username,
                $ip,
                LoginAttemptRecorderInterface::METHOD_PASSWORD
            );
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey failed-login handling failed: ' . $e->getMessage());
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
}
