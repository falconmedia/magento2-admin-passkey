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
 * Reuses {@see \Magento\Backend\Model\Auth\Session}: setUser() binds the admin
 * user and processLogin() regenerates the session id (fixation defence), reloads
 * the ACL and renews secret URLs. Passkey login replaces Magento 2FA for that
 * session ({@see TfaSessionInterface::grantAccess()}), while password login keeps
 * the native 2FA challenge on the next request.
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
        private readonly TfaSessionInterface $tfaSession
    ) {
    }

    /**
     * @inheritdoc
     */
    public function login(int $adminUserId): void
    {
        $user = $this->userFactory->create();
        $user->load($adminUserId);

        if (!$user->getId() || (int) $user->getIsActive() !== 1) {
            throw new AuthenticationException(
                __('The account sign-in was incorrect or your account is disabled temporarily.')
            );
        }

        $this->authSession->setUser($user);
        // Native processLogin() regenerates the session id and reloads the ACL.
        $this->authSession->processLogin();

        // Passkey satisfies the second factor — skip Magento 2FA for this session only.
        if ($this->tfa->isEnabled()) {
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
