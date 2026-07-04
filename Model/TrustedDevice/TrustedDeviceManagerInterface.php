<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\TrustedDevice;

/**
 * Operational trusted-device orchestration.
 *
 * Wraps the trusted-device repository with the login-time behaviour: counting
 * successful logins per browser, promoting a browser to a trusted device once the
 * configured threshold is reached (storing only a token hash), revoking devices
 * with an audit trail, and expiring devices past their lifetime.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface TrustedDeviceManagerInterface
{
    /**
     * Handle a successful admin login for the current browser/request.
     *
     * Side-effect safe: never throws so it cannot break a login. When the browser
     * reaches the configured successful-login threshold, a trusted device is
     * created (only its token hash is stored) and a token cookie is issued.
     *
     * @param int $adminUserId Authenticated admin user id.
     * @param string|null $remoteIp Remote IP address, when known.
     * @param string|null $userAgent Request user-agent, when known.
     * @return void
     */
    public function handleSuccessfulLogin(int $adminUserId, ?string $remoteIp, ?string $userAgent): void;

    /**
     * Whether the current request presents a valid trusted device for the admin.
     *
     * @param int $adminUserId
     * @return bool
     */
    public function isCurrentRequestTrusted(int $adminUserId): bool;

    /**
     * Revoke a trusted device and record an audit event.
     *
     * @param int $entityId
     * @param int|null $actorAdminUserId Admin performing the revoke, when known.
     * @return void
     */
    public function revoke(int $entityId, ?int $actorAdminUserId = null): void;

    /**
     * Expire every active device whose expiry timestamp has passed.
     *
     * @return int Number of devices expired.
     */
    public function expireStaleDevices(): int;
}
