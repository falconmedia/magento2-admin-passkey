<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Auth;

use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Security\Model\UserExpirationManager;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;

/**
 * Account checks that Magento applies to a password login, re-applied to passkey login.
 *
 * A password login runs these through {@see User::authenticate()} and its events;
 * the passkey flow does not call that method (there is no password), so without
 * this guard a locked, expired or role-less admin could still sign in:
 *
 *  - role assigned ({@see User::verifyIdentity()} / hasAssigned2Role());
 *  - native admin lock ({@see \Magento\User\Observer\Backend\AuthObserver}, lock_expires);
 *  - Magento_Security user expiration
 *    ({@see \Magento\Security\Observer\AdminUserAuthenticateBefore});
 *  - the module's own durable lockout for the account.
 *
 * Every rejection uses Magento's generic sign-in message (no enumeration).
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class NativeLoginGuard
{
    private const SECURITY_MODULE = 'Magento_Security';

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly UserExpirationManager $userExpirationManager,
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Throw when the (already loaded, active) admin user may not sign in.
     *
     * @param User $user
     * @return void
     * @throws AuthenticationException
     */
    public function assertCanLogin(User $user): void
    {
        $userId = (int) $user->getId();

        if (!$user->hasAssigned2Role($userId)) {
            throw new AuthenticationException(__('More permissions are needed to access this.'));
        }

        if ($this->isNativelyLocked($user)) {
            throw $this->genericFailure();
        }

        if ($this->isExpired($userId)) {
            $this->userExpirationManager->deactivateExpiredUsersById([$userId]);
            throw $this->genericFailure();
        }

        // Account-bound lockout only: a username/IP lockout must not block the
        // passkey path, otherwise anyone could lock an admin out completely.
        if ($this->lockoutManager->isLocked($userId, null, null)) {
            throw $this->genericFailure();
        }
    }

    /**
     * Whether Magento's own admin lock (admin_user.lock_expires) is still active.
     *
     * @param User $user
     * @return bool
     */
    private function isNativelyLocked(User $user): bool
    {
        $lockExpires = $user->getLockExpires();
        if ($lockExpires === null || $lockExpires === '') {
            return false;
        }

        $expiresAt = strtotime((string) $lockExpires . ' UTC');

        // An unparsable value is treated as locked (fail closed, like the native observer's DateTime throw).
        return $expiresAt === false || $expiresAt > $this->dateTime->gmtTimestamp();
    }

    /**
     * Whether Magento_Security marks the user as expired.
     *
     * @param int $userId
     * @return bool
     */
    private function isExpired(int $userId): bool
    {
        if (!$this->moduleManager->isEnabled(self::SECURITY_MODULE)) {
            return false;
        }

        return $this->userExpirationManager->isUserExpired((string) $userId);
    }

    /**
     * Magento's generic, non-enumerating sign-in failure.
     *
     * @return AuthenticationException
     */
    private function genericFailure(): AuthenticationException
    {
        return new AuthenticationException(
            __(
                'The account sign-in was incorrect or your account is disabled temporarily. '
                . 'Please wait and try again later.'
            )
        );
    }
}
