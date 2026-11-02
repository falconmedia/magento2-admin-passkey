<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Auth;

use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\User\Model\UserFactory;

/**
 * Native-backed Admin session creator for the verified passkey login flow.
 *
 * The passkey path cannot call {@see \Magento\Backend\Model\Auth::login()} (there
 * is no password), so it reproduces what that call and its observers/plugins do:
 *
 *  - {@see NativeLoginGuard::assertCanLogin()} runs the account checks Magento
 *    applies to a password login (active, role assigned, native lock_expires,
 *    Magento_Security user expiration, module lockout for the account);
 *  - {@see AdminAuthSession::processLogin()} regenerates the session id,
 *    reloads the ACL and renews secret URLs;
 *  - {@see NativeLoginRecorder::afterLogin()} records the login (logdate/lognum),
 *    flags an expired password and registers the Magento_Security admin session
 *    (admin_user_session) including "terminate other sessions".
 *
 * A passkey only replaces Magento 2FA ({@see TfaSessionInterface::grantAccess()})
 * when the authenticator performed user verification (PIN/biometrics). Without
 * UV the passkey is a single possession factor and the native 2FA challenge
 * follows on the next request, exactly as for password login.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class AdminSessionCreator implements AdminSessionCreatorInterface
{
    public function __construct(
        private readonly AdminAuthSession $authSession,
        private readonly UserFactory $userFactory,
        private readonly EventManagerInterface $eventManager,
        private readonly BackendUrlInterface $backendUrl,
        private readonly TfaInterface $tfa,
        private readonly TfaSessionInterface $tfaSession,
        private readonly NativeLoginGuard $loginGuard,
        private readonly NativeLoginRecorder $loginRecorder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function login(int $adminUserId, bool $userVerified = false): void
    {
        $user = $this->userFactory->create();
        $user->load($adminUserId);

        if (!$user->getId() || (int) $user->getIsActive() !== 1) {
            throw new AuthenticationException(
                __('The account sign-in was incorrect or your account is disabled temporarily.')
            );
        }

        $this->loginGuard->assertCanLogin($user);

        $this->authSession->setUser($user);
        // Native processLogin() regenerates the session id and reloads the ACL.
        $this->authSession->processLogin();

        try {
            $this->loginRecorder->afterLogin($user);
        } catch (\Throwable $e) {
            // Never leave a half-registered session behind (e.g. no admin_user_session row).
            $this->authSession->processLogout();
            throw new AuthenticationException(
                __('The account sign-in was incorrect or your account is disabled temporarily.'),
                $e instanceof \Exception ? $e : null
            );
        }

        // A user-verified passkey is multi-factor and satisfies Magento 2FA for
        // this session only. Without UV the native 2FA challenge still applies.
        if ($userVerified && $this->tfa->isEnabled()) {
            $this->tfaSession->grantAccess();
        }

        $this->eventManager->dispatch('backend_auth_user_login_success', ['user' => $user]);
    }

    /**
     * @inheritdoc
     */
    public function resolveRedirectUrl(?string $requestedUrl = null): string
    {
        if ($requestedUrl !== null && $requestedUrl !== '' && $this->isSafeAdminUrl($requestedUrl)) {
            return $requestedUrl;
        }

        return $this->backendUrl->getUrl($this->backendUrl->getStartupPageUrl());
    }

    /**
     * Only allow redirecting to an absolute URL under the Admin base URL.
     *
     * @param string $url
     * @return bool
     */
    private function isSafeAdminUrl(string $url): bool
    {
        $baseUrl = $this->backendUrl->getBaseUrl();

        return $baseUrl !== '' && str_starts_with($url, $baseUrl);
    }
}
