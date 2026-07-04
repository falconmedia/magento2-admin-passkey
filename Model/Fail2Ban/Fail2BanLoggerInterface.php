<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Fail2Ban;

/**
 * Writes Fail2Ban-compatible log lines for security events.
 *
 * Implementations must be side-effect safe (never throw) and must only write when
 * Fail2Ban logging is enabled in configuration, guarding the configured path
 * against traversal outside the Magento root.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface Fail2BanLoggerInterface
{
    /**
     * Append a single Fail2Ban-parseable line for a security event.
     *
     * @param string $eventType Event code (e.g. login_failed, lockout, brute_force_detected).
     * @param string|null $ip Remote IP address, when known.
     * @param string|null $username Attempted username, when known.
     * @param int|null $adminUserId Admin user id, when known.
     * @param string|null $method Login method (passkey|password), when known.
     * @return void
     */
    public function log(
        string $eventType,
        ?string $ip,
        ?string $username,
        ?int $adminUserId,
        ?string $method
    ): void;
}
