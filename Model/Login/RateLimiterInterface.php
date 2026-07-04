<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Login;

/**
 * Minimal fixed-window rate-limit seam guarding the pre-auth login endpoints.
 *
 * Step 11 ships a cache-backed, fail-open implementation to throttle abuse of the
 * unauthenticated assertion-options/verify endpoints without enabling user
 * enumeration. Full lockout evaluation (per admin user / per IP with audit and
 * unlock) is intentionally deferred to Step 13, which can build on the same seam.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface RateLimiterInterface
{
    /**
     * Whether the given key has reached its attempt ceiling for the current window.
     *
     * @param string $key Opaque bucket key (e.g. an action name plus remote IP).
     * @return bool
     */
    public function isLimited(string $key): bool;

    /**
     * Register a single attempt against the given key.
     *
     * @param string $key Opaque bucket key.
     * @return void
     */
    public function registerAttempt(string $key): void;

    /**
     * Clear the counter for the given key (e.g. after a successful login).
     *
     * @param string $key Opaque bucket key.
     * @return void
     */
    public function reset(string $key): void;
}
