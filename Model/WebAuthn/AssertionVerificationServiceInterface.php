<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;

/**
 * Verifies a WebAuthn assertion (authentication) response.
 *
 * Internal, Admin-UI-only contract. Deliberately NOT annotated @api and NOT
 * exposed via webapi.xml — passkey login is browser/Admin only. This service
 * never creates a Magento session; it returns a verified {@see AssertionResult}
 * for the Step 11 Admin login flow to consume.
 */
interface AssertionVerificationServiceInterface
{
    /**
     * Verify an assertion response and return the authenticated identity.
     *
     * The assertion challenge is loaded by the value echoed in clientDataJSON,
     * validated (pending, unexpired) and consumed BEFORE signature verification,
     * so a consumed challenge can never be replayed. Origin, rpId, user
     * presence/verification, credential status, signature and sign-counter
     * regression are all validated. On success the credential's last_used_at and
     * sign_count are updated.
     *
     * @param array<string,mixed> $assertionResponse Browser PublicKeyCredential (assertion) payload.
     * @param string|null $remoteIp Optional remote IP for auditing.
     * @return AssertionResult The verified result (verified, admin user id, credential id).
     * @throws WebAuthnVerificationException On any validation failure.
     */
    public function verify(array $assertionResponse, ?string $remoteIp = null): AssertionResult;
}
