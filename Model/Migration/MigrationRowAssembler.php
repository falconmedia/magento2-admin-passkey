<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Api\TrustedDeviceRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Onboarding\OnboardingPolicy;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Builds a migration-dashboard row for an admin user from real data sources.
 *
 * All row-assembly logic lives here (not in the UI DataProvider) so it can be
 * unit tested with mocked repositories.
 */
class MigrationRowAssembler
{
    public const PASSKEY_REGISTERED = 'registered';
    public const PASSKEY_MISSING = 'missing';

    public const ONBOARDING_COMPLETE = 'complete';
    public const ONBOARDING_REQUIRED = 'required';
    public const ONBOARDING_OPTIONAL = 'optional';

    public const LOCKOUT_LOCKED = 'locked';
    public const LOCKOUT_NONE = 'none';

    public const RECOVERY_ACTIVE = 'active';
    public const RECOVERY_INACTIVE = 'inactive';

    /**
     * @var array<int, string>|null
     */
    private ?array $roleNameMap = null;

    /**
     * @var bool|null
     */
    private ?bool $recoveryActive = null;

    public function __construct(
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly TrustedDeviceRepositoryInterface $trustedDeviceRepository,
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly AuditLogInterface $auditLog,
        private readonly OnboardingPolicy $onboardingPolicy,
        private readonly TwoFactorAuthStatusProvider $twoFactorAuthStatusProvider,
        private readonly AdminUserProvider $adminUserProvider,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * Enrich a base admin-user row with the computed migration columns.
     *
     * @param array<string, mixed> $admin Base admin data (user_id, username, firstname, lastname, email, is_active, logdate).
     * @return array<string, mixed>
     */
    public function assembleRow(array $admin): array
    {
        $userId = (int) ($admin['user_id'] ?? 0);
        $passkeyCount = $this->countActivePasskeys($userId);
        $hasPasskey = $passkeyCount > 0;

        $admin['name'] = $this->resolveName($admin);
        $admin['role'] = $this->resolveRoleName($userId);
        $admin['twofa_status'] = $this->twoFactorAuthStatusProvider->getStatusForUser($userId);
        $admin['passkey_status'] = $hasPasskey ? self::PASSKEY_REGISTERED : self::PASSKEY_MISSING;
        $admin['passkeys'] = $passkeyCount;
        $admin['trusted_devices'] = $this->countActiveTrustedDevices($userId);
        $admin['last_login'] = $this->normaliseTimestamp($admin['logdate'] ?? null);
        $admin['last_passkey_login'] = $this->resolveLastPasskeyLogin($userId);
        $admin['onboarding_status'] = $this->resolveOnboardingStatus($userId, $hasPasskey);
        $admin['lockout_status'] = $this->resolveLockoutStatus($userId, (string) ($admin['username'] ?? ''));
        $admin['recovery_status'] = $this->isRecoveryActive() ? self::RECOVERY_ACTIVE : self::RECOVERY_INACTIVE;

        return $admin;
    }

    /**
     * Enrich many admin rows.
     *
     * @param array<int, array<string, mixed>> $admins
     * @return array<int, array<string, mixed>>
     */
    public function assembleRows(array $admins): array
    {
        $rows = [];
        foreach ($admins as $admin) {
            $rows[] = $this->assembleRow($admin);
        }

        return $rows;
    }

    /**
     * Count active passkeys for an admin user.
     *
     * @param int $userId
     * @return int
     */
    private function countActivePasskeys(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        try {
            return $this->credentialRepository->listActiveForAdmin($userId)->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Count active trusted devices for an admin user.
     *
     * @param int $userId
     * @return int
     */
    private function countActiveTrustedDevices(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        try {
            return $this->trustedDeviceRepository->listActiveForAdmin($userId)->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Resolve the display name from first/last name, falling back to the username.
     *
     * @param array<string, mixed> $admin
     * @return string
     */
    private function resolveName(array $admin): string
    {
        $name = trim(((string) ($admin['firstname'] ?? '')) . ' ' . ((string) ($admin['lastname'] ?? '')));

        return $name !== '' ? $name : (string) ($admin['username'] ?? '');
    }

    /**
     * Resolve the role (group) name for an admin user.
     *
     * @param int $userId
     * @return string
     */
    private function resolveRoleName(int $userId): string
    {
        if ($this->roleNameMap === null) {
            $this->roleNameMap = $this->adminUserProvider->getRoleNameMap();
        }

        return $this->roleNameMap[$userId] ?? '';
    }

    /**
     * Resolve the timestamp of the last successful passkey login.
     *
     * @param int $userId
     * @return string|null
     */
    private function resolveLastPasskeyLogin(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            $sortOrder = $this->sortOrderBuilder
                ->setField(AuditEventInterface::CREATED_AT)
                ->setDirection('DESC')
                ->create();

            // Passkey login runs pre-auth, so there is no session actor: the
            // verification service records the authenticated admin as the event
            // TARGET, not the actor. Match on that or the column stays empty.
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(AuditEventInterface::EVENT_TYPE, AuditLoggerInterface::EVENT_PASSKEY_LOGIN)
                ->addFilter(AuditEventInterface::TARGET_ADMIN_USER_ID, $userId)
                ->addSortOrder($sortOrder)
                ->setPageSize(1)
                ->create();

            $items = $this->auditLog->getList($searchCriteria)->getItems();
            if ($items === []) {
                return null;
            }

            $event = reset($items);

            return $event->getCreatedAt();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the onboarding status for an admin user.
     *
     * @param int $userId
     * @param bool $hasPasskey
     * @return string
     */
    private function resolveOnboardingStatus(int $userId, bool $hasPasskey): string
    {
        if ($hasPasskey) {
            return self::ONBOARDING_COMPLETE;
        }

        try {
            if ($this->onboardingPolicy->requiresOnboarding($userId)) {
                return self::ONBOARDING_REQUIRED;
            }
        } catch (\Throwable) {
            return self::ONBOARDING_OPTIONAL;
        }

        return self::ONBOARDING_OPTIONAL;
    }

    /**
     * Resolve the lockout status for an admin user.
     *
     * @param int $userId
     * @param string $username
     * @return string
     */
    private function resolveLockoutStatus(int $userId, string $username): string
    {
        try {
            if ($userId > 0 && $this->lockoutRepository->findActiveForAdmin($userId) !== null) {
                return self::LOCKOUT_LOCKED;
            }
            if ($username !== '' && $this->lockoutRepository->findActiveForUsername($username) !== null) {
                return self::LOCKOUT_LOCKED;
            }
        } catch (\Throwable) {
            return self::LOCKOUT_NONE;
        }

        return self::LOCKOUT_NONE;
    }

    /**
     * Whether recovery mode is active (computed once per assembler instance).
     *
     * @return bool
     */
    private function isRecoveryActive(): bool
    {
        if ($this->recoveryActive === null) {
            try {
                $this->recoveryActive = $this->recoveryModeService->isActive();
            } catch (\Throwable) {
                $this->recoveryActive = false;
            }
        }

        return $this->recoveryActive;
    }

    /**
     * Normalise an empty timestamp to null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function normaliseTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $string = (string) $value;

        return $string === '' ? null : $string;
    }
}
