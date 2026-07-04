<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Builds and persists a WebAuthn assertion (authentication) challenge.
 *
 * Internal, Admin-UI-only contract. Deliberately NOT annotated @api and NOT
 * exposed via webapi.xml — passkey login is browser/Admin only.
 */
interface AssertionChallengeServiceInterface
{
    /**
     * Build publicKeyCredentialRequestOptions and persist a single-use, expiring
     * assertion challenge row.
     *
     * When $adminUserId is null the options support discoverable credentials
     * (empty allowCredentials). When an admin user id is supplied, allowCredentials
     * is populated from that user's active credentials (with transports).
     *
     * @param int|null $adminUserId Target admin user id, or null for discoverable credentials.
     * @param string|null $remoteIp Optional remote IP to record on the challenge row.
     * @return array<string, mixed> The publicKeyCredentialRequestOptions payload.
     * @throws CouldNotSaveException When the challenge row cannot be persisted.
     */
    public function createOptions(?int $adminUserId = null, ?string $remoteIp = null): array;
}
