<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

/**
 * Immutable outcome of a successful passkey assertion verification.
 *
 * The assertion service throws {@see Exception\WebAuthnVerificationException} on
 * any failure, so a returned result always represents a verified assertion. The
 * result is a pure hand-off DTO: it identifies the authenticated admin user and
 * the credential used, and deliberately does NOT create a Magento session — that
 * is the responsibility of the Step 11 Admin login flow.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class AssertionResult
{
    public function __construct(
        private readonly bool $verified,
        private readonly int $adminUserId,
        private readonly string $credentialId,
        private readonly bool $userVerified = false
    ) {
    }

    /**
     * Whether the assertion was cryptographically verified.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Authenticated admin user id.
     *
     * @return int
     */
    public function getAdminUserId(): int
    {
        return $this->adminUserId;
    }

    /**
     * Base64url credential id that produced the assertion.
     *
     * @return string
     */
    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    /**
     * Whether the authenticator reported user verification (UV flag: PIN or biometrics).
     *
     * Only a user-verified assertion is multi-factor (possession + knowledge/inherence)
     * and may therefore stand in for Magento 2FA.
     *
     * @return bool
     */
    public function isUserVerified(): bool
    {
        return $this->userVerified;
    }
}
