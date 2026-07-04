<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Login;

/**
 * Seam for recording Admin login outcomes (passkey and password fallback).
 *
 * Step 11 provides a thin, no-op-safe implementation that audits the fallback
 * path (the passkey path is already audited by the WebAuthn assertion service)
 * and feeds a rate-limit/lockout counter. Step 13 (Trusted Devices / Lockout /
 * Recovery) is expected to enrich the implementation with full lockout
 * evaluation, trusted-device updates and Fail2Ban logging without changing this
 * contract.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface LoginAttemptRecorderInterface
{
    /**
     * Login method identifiers.
     */
    public const METHOD_PASSKEY = 'passkey';
    public const METHOD_PASSWORD = 'password';

    /**
     * Backend auth session data key used to carry the login method across the
     * native backend_auth_user_login_success event dispatch.
     */
    public const SESSION_METHOD_KEY = 'adminpasskey_login_method';

    /**
     * Record a successful login for the given admin user.
     *
     * Implementations must be side-effect safe: never throw, so a recording
     * failure cannot block a legitimate login.
     *
     * @param int $adminUserId Authenticated admin user id.
     * @param string $method One of the METHOD_* constants.
     * @param string|null $remoteIp Optional remote IP address.
     * @return void
     */
    public function recordSuccess(int $adminUserId, string $method, ?string $remoteIp = null): void;

    /**
     * Record a failed login attempt.
     *
     * Implementations must be side-effect safe: never throw, so a recording
     * failure cannot break the login response.
     *
     * @param string|null $username Attempted username, when known (may be null pre-auth).
     * @param string $method One of the METHOD_* constants.
     * @param string|null $reason Optional non-sensitive failure reason.
     * @param string|null $remoteIp Optional remote IP address.
     * @return void
     */
    public function recordFailure(
        ?string $username,
        string $method,
        ?string $reason = null,
        ?string $remoteIp = null
    ): void;
}
