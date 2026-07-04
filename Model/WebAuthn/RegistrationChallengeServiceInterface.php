<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Builds and persists a WebAuthn registration (attestation) challenge for an admin user.
 *
 * Internal, Admin-UI-only contract. Deliberately NOT annotated @api and NOT
 * exposed via webapi.xml — passkey registration is browser/Admin only.
 */
interface RegistrationChallengeServiceInterface
{
    /**
     * Build publicKeyCredentialCreationOptions for the given admin user and persist
     * a single-use, expiring registration challenge row.
     *
     * @param int $adminUserId Target Magento admin user id.
     * @param string|null $remoteIp Optional remote IP to record on the challenge row.
     * @return array<string, mixed> The publicKeyCredentialCreationOptions payload.
     * @throws LocalizedException When the admin user cannot be resolved.
     * @throws CouldNotSaveException When the challenge row cannot be persisted.
     */
    public function createOptions(int $adminUserId, ?string $remoteIp = null): array;
}
