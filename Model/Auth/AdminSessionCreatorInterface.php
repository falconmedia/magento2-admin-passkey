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
     * Applies the same account checks as a native password login (active, role,
     * native lock, user expiration, module lockout), then uses the native
     * {@see \Magento\Backend\Model\Auth\Session::processLogin()} (regenerateId(),
     * ACL reload, secret URL renewal), records the login, registers the
     * Magento_Security admin session and dispatches backend_auth_user_login_success.
     *
     * Magento 2FA is only satisfied by the passkey when $userVerified is true
     * (authenticator UV flag); otherwise the native 2FA challenge still follows.
     *
     * @param int $adminUserId Verified Magento admin user id.
     * @param bool $userVerified Whether the assertion carried the UV flag.
     * @return void
     * @throws AuthenticationException When the user may not sign in.
     */
    public function login(int $adminUserId, bool $userVerified = false): void;

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
