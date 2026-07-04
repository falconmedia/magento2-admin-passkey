<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Lockout;

/**
 * Operational lockout orchestration built on top of the failed-attempt seam.
 *
 * Accumulates failed attempts (by admin user id, username, or IP) within the
 * configured rolling window, creates/raises durable lockouts once the threshold
 * is reached, blocks both passkey and password login while locked, and releases
 * lockouts when they expire or an admin unlocks them. Recovery mode overrides
 * blocking so an admin can never be permanently locked out.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface LockoutManagerInterface
{
    /**
     * Register a failed login attempt and lock the identity when the threshold is met.
     *
     * Side-effect safe: never throws so it cannot break the login response.
     *
     * @param int|null $adminUserId Admin user id, when known.
     * @param string|null $username Attempted username, when known.
     * @param string|null $ip Remote IP address, when known.
     * @param string $method Login method (passkey|password).
     * @return void
     */
    public function registerFailedAttempt(
        ?int $adminUserId,
        ?string $username,
        ?string $ip,
        string $method
    ): void;

    /**
     * Register a successful login, clearing any active failure tracking for the identity.
     *
     * @param int|null $adminUserId
     * @param string|null $username
     * @param string|null $ip
     * @return void
     */
    public function registerSuccessfulAttempt(?int $adminUserId, ?string $username, ?string $ip): void;

    /**
     * Whether login is currently blocked for any of the supplied identifiers.
     *
     * @param int|null $adminUserId
     * @param string|null $username
     * @param string|null $ip
     * @return bool
     */
    public function isLocked(?int $adminUserId, ?string $username, ?string $ip): bool;

    /**
     * Release a lockout as an administrator action and audit it.
     *
     * @param int $entityId
     * @param int|null $actorAdminUserId Admin performing the unlock, when known.
     * @return void
     */
    public function unlock(int $entityId, ?int $actorAdminUserId = null): void;
}
