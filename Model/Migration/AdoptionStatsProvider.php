<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;

/**
 * Aggregates passkey-adoption facts across admin users from the same real data
 * sources the migration dashboard uses.
 *
 * Extracted as a dedicated service so both the Admin migration dashboard and the
 * `adminpasskey:migration:status` CLI command reuse one implementation and it can
 * be unit tested with mocked repositories.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class AdoptionStatsProvider
{
    public function __construct(
        private readonly AdminUserProvider $adminUserProvider,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly TwoFactorAuthStatusProvider $twoFactorAuthStatusProvider
    ) {
    }

    /**
     * Compute the current adoption summary.
     *
     * @param bool $activeOnly When true, only active admin accounts are counted.
     * @return AdoptionStats
     */
    public function getStats(bool $activeOnly = true): AdoptionStats
    {
        $adminIds = $this->adminUserProvider->getAdminIds($activeOnly);
        $total = count($adminIds);

        $withPasskey = 0;
        $twoFa = [
            TwoFactorAuthStatusProvider::STATUS_ACTIVE => 0,
            TwoFactorAuthStatusProvider::STATUS_CONFIGURED => 0,
            TwoFactorAuthStatusProvider::STATUS_NONE => 0,
            TwoFactorAuthStatusProvider::STATUS_DISABLED => 0,
        ];

        foreach ($adminIds as $adminId) {
            if ($this->hasActivePasskey($adminId)) {
                $withPasskey++;
            }

            $status = $this->twoFactorAuthStatusProvider->getStatusForUser($adminId);
            if (!isset($twoFa[$status])) {
                $twoFa[$status] = 0;
            }
            $twoFa[$status]++;
        }

        $withoutPasskey = $total - $withPasskey;
        $adoptionPercent = $total > 0 ? round(($withPasskey / $total) * 100, 1) : 0.0;

        return new AdoptionStats(
            $total,
            $withPasskey,
            $withoutPasskey,
            $adoptionPercent,
            $twoFa[TwoFactorAuthStatusProvider::STATUS_ACTIVE],
            $twoFa[TwoFactorAuthStatusProvider::STATUS_CONFIGURED],
            $twoFa[TwoFactorAuthStatusProvider::STATUS_NONE],
            $twoFa[TwoFactorAuthStatusProvider::STATUS_DISABLED],
            $this->twoFactorAuthStatusProvider->isEnabled()
        );
    }

    /**
     * Whether an admin owns at least one active passkey.
     *
     * @param int $adminUserId
     * @return bool
     */
    private function hasActivePasskey(int $adminUserId): bool
    {
        if ($adminUserId <= 0) {
            return false;
        }

        try {
            return $this->credentialRepository->listActiveForAdmin($adminUserId)->getTotalCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
