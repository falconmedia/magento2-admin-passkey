<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Passkey;

use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use FalconMedia\AdminPasskey\Model\Login\LoginAttemptRecorderInterface;
use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\User\Model\User;

/**
 * Step-up re-authentication for passkey registration.
 *
 * An active Admin session alone is not enough to enrol a passkey: a stolen
 * session or an XSS payload could otherwise register an attacker-controlled
 * passkey as a permanent backdoor. Registration therefore requires the admin to
 * re-enter their current password. A successful check stores a short-lived,
 * user-bound grant in the session that the registration verify step consumes.
 *
 * Wrong passwords count towards both Magento's native admin lock (via
 * performIdentityCheck()) and the module's account lockout, so the step-up
 * cannot be used to brute-force the password of a hijacked session.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class RegistrationStepUp
{
    /**
     * Session key holding the step-up grant.
     */
    private const SESSION_KEY = 'adminpasskey_registration_step_up';

    /**
     * Lifetime of a step-up grant, in seconds (covers one browser ceremony).
     */
    private const GRANT_LIFETIME_SECONDS = 300;

    public function __construct(
        private readonly AdminAuthSession $authSession,
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Verify the admin's current password and, on success, store a step-up grant.
     *
     * @param User $user Session admin user.
     * @param string $password Password entered by the admin.
     * @param string|null $remoteIp
     * @return bool True when the password is correct and the grant was stored.
     */
    public function authenticate(User $user, string $password, ?string $remoteIp): bool
    {
        $userId = (int) $user->getId();
        if ($userId <= 0 || $password === '') {
            return false;
        }

        if ($this->lockoutManager->isLocked($userId, null, null)) {
            return false;
        }

        // performIdentityCheck() is what core uses for "Your Password" checks: it
        // dispatches admin_user_authenticate_after, so Magento's own failure
        // counter and lock_expires apply even when the module lockout is off.
        try {
            $user->performIdentityCheck($password);
            $verified = true;
        } catch (UserLockedException) {
            $this->revoke();

            return false;
        } catch (AuthenticationException) {
            $verified = false;
        }

        if (!$verified) {
            $this->lockoutManager->registerFailedAttempt(
                $userId,
                (string) $user->getUserName(),
                $remoteIp,
                LoginAttemptRecorderInterface::METHOD_PASSWORD
            );
            $this->revoke();

            return false;
        }

        $this->authSession->setData(self::SESSION_KEY, [
            'admin_user_id' => $userId,
            'expires_at' => $this->dateTime->gmtTimestamp() + self::GRANT_LIFETIME_SECONDS,
        ]);

        return true;
    }

    /**
     * Whether a valid, unexpired step-up grant exists for this admin user.
     *
     * @param int $adminUserId
     * @return bool
     */
    public function isGranted(int $adminUserId): bool
    {
        $grant = $this->authSession->getData(self::SESSION_KEY);
        if (!is_array($grant)) {
            return false;
        }

        return (int) ($grant['admin_user_id'] ?? 0) === $adminUserId
            && $adminUserId > 0
            && (int) ($grant['expires_at'] ?? 0) >= $this->dateTime->gmtTimestamp();
    }

    /**
     * Drop the step-up grant (after a registration, or on a failed check).
     *
     * @return void
     */
    public function revoke(): void
    {
        $this->authSession->setData(self::SESSION_KEY, null);
    }
}
