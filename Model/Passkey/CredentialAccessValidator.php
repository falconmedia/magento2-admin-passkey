<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Passkey;

/**
 * Pure ownership check used by the self-service passkey endpoints.
 *
 * A credential may only be renamed or revoked (via the self endpoints) by the
 * admin who owns it. Extracting this into a tiny, dependency-free class keeps the
 * rule unit-testable and impossible to accidentally bypass in a controller.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class CredentialAccessValidator
{
    /**
     * Whether the credential owner matches the current admin.
     *
     * @param int|null $ownerAdminUserId The credential's admin_user_id.
     * @param int $currentAdminUserId The authenticated admin's user id.
     * @return bool
     */
    public function isOwnedByAdmin(?int $ownerAdminUserId, int $currentAdminUserId): bool
    {
        if ($currentAdminUserId <= 0 || $ownerAdminUserId === null) {
            return false;
        }

        return $ownerAdminUserId === $currentAdminUserId;
    }
}
