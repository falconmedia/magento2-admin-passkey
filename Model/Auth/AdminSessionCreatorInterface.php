<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Auth;

use Magento\Framework\Exception\AuthenticationException;

/**
 * Creates an authenticated Magento Admin session for an already-verified admin user.
 *
 * This is the single owner of Admin session creation for the passkey login flow
 * (see CHIEF_LOOP_PDR PDR-001). It reuses the native Magento backend auth session
 * so that standard behaviour — session id regeneration, ACL reload, secret-key
 * renewal and Magento_TwoFactorAuth enforcement — is preserved. It never verifies
 * a passkey itself; the caller must pass an id that has already been verified by
 * the WebAuthn assertion service.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface AdminSessionCreatorInterface
{
    /**
     * Log the given admin user in and regenerate the session id (fixation defence).
     *
     * The native {@see \Magento\Backend\Model\Auth\Session::processLogin()} is used,
     * which calls regenerateId(), reloads the ACL and renews secret URLs, then the
     * standard backend_auth_user_login_success event is dispatched so native
     * observers (admin session tracking, 2FA enforcement) run unchanged.
     *
     * @param int $adminUserId Verified Magento admin user id.
     * @return void
     * @throws AuthenticationException When the user does not exist or is inactive.
     */
    public function login(int $adminUserId): void;

    /**
     * Resolve the URL to redirect to after a successful login.
     *
     * Returns the originally requested Admin URL when it is a safe, in-Admin path,
     * otherwise the configured Admin startup page.
     *
     * @param string|null $requestedUrl Optional URL the browser asked for before login.
     * @return string
     */
    public function resolveRedirectUrl(?string $requestedUrl = null): string;
}
