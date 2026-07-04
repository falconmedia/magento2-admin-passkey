<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;

/**
 * Verifies a WebAuthn registration (attestation) response and persists the
 * resulting passkey credential for an admin user.
 *
 * Internal, Admin-UI-only contract. Deliberately NOT annotated @api and NOT
 * exposed via webapi.xml — passkey registration is browser/Admin only.
 */
interface RegistrationVerificationServiceInterface
{
    /**
     * Verify an attestation response and persist the credential on success.
     *
     * The registration challenge is loaded by the value echoed in clientDataJSON,
     * validated (pending, unexpired, matching admin user) and consumed BEFORE the
     * credential is persisted. Origin, rpId, user presence/verification, attested
     * credential data and the COSE public key are all validated. A duplicate
     * credential id is rejected.
     *
     * @param int $adminUserId Admin user the passkey is being registered for.
     * @param array<string,mixed> $attestationResponse Browser PublicKeyCredential (attestation) payload.
     * @param string|null $remoteIp Optional remote IP for auditing.
     * @return CredentialInterface The persisted, active credential.
     * @throws WebAuthnVerificationException On any validation failure.
     */
    public function verify(int $adminUserId, array $attestationResponse, ?string $remoteIp = null): CredentialInterface;
}
