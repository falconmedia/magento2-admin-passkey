<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Plugin\Backend\Auth;

use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Magento\Backend\Model\Auth;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Blocks native password login while the account or IP is locked out.
 *
 * Runs before {@see \Magento\Backend\Model\Auth::login()} and throws the standard
 * authentication exception when the lockout manager reports the identity as
 * locked, so repeated failures actually stop further password attempts. The
 * passkey login endpoints apply the same check independently. Lockout evaluation
 * fails open (a lockout-store error can never lock every admin out): any error is
 * logged and login proceeds.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class BlockLockedAdminLogin
{
    public function __construct(
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly RemoteAddress $remoteAddress,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Reject the login attempt when the username/IP is currently locked.
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
        if (!$this->isLocked((string) $username)) {
            return;
        }

        throw new AuthenticationException(
            __('Too many failed attempts. This account is temporarily locked. Please try again later.')
        );
    }

    /**
     * Whether the given username or the current IP is locked (fail-open on error).
     *
     * @param string $username
     * @return bool
     */
    private function isLocked(string $username): bool
    {
        try {
            $ip = (string) $this->remoteAddress->getRemoteAddress();

            return $this->lockoutManager->isLocked(
                null,
                $username !== '' ? $username : null,
                $ip !== '' ? $ip : null
            );
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey lockout check failed: ' . $e->getMessage());

            return false;
        }
    }
}
