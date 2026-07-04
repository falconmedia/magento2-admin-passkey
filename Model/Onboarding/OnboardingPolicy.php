<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Onboarding;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;

/**
 * Decides whether a logged-in admin must be redirected to the passkey setup
 * wizard before they may use the rest of the Admin.
 *
 * The policy is intentionally split into two small, testable decisions:
 * {@see self::isExemptAction()} (a pure string check that guarantees an admin is
 * never permanently trapped) and {@see self::requiresOnboarding()} (config + the
 * admin's active-passkey count). The observer combines them; keeping the logic
 * here means the enforcement rules can be unit-tested without booting a request.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class OnboardingPolicy
{
    /**
     * Full-action-name prefix (lower-case) for every controller this module owns.
     * All of the module's own routes are always exempt so the wizard, the passkey
     * JSON endpoints and any recovery route this module ships can never be trapped.
     */
    private const MODULE_ACTION_PREFIX = 'adminpasskey_';

    /**
     * @param string[] $alwaysAllowedActions Extra full action names (lower-case) that must never be trapped
     *                                        (e.g. the native Admin login/logout), so an admin can always escape.
     */
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly array $alwaysAllowedActions = []
    ) {
    }

    /**
     * Whether the given full action name must never be redirected to the wizard.
     *
     * @param string $fullActionName Magento full action name (routeId_controller_action).
     * @return bool
     */
    public function isExemptAction(string $fullActionName): bool
    {
        $name = strtolower($fullActionName);

        if (str_starts_with($name, self::MODULE_ACTION_PREFIX)) {
            return true;
        }

        return in_array($name, $this->alwaysAllowedActions, true);
    }

    /**
     * Whether the given admin must complete passkey onboarding.
     *
     * Onboarding is required only when the suite is enabled, the "require passkey
     * onboarding" policy is on, and the admin currently has zero active passkeys.
     *
     * @param int $adminUserId
     * @return bool
     */
    public function requiresOnboarding(int $adminUserId): bool
    {
        if ($adminUserId <= 0) {
            return false;
        }

        if (!$this->configProvider->isEnabled() || !$this->configProvider->isPasskeyOnboardingRequired()) {
            return false;
        }

        return $this->credentialRepository->listActiveForAdmin($adminUserId)->getTotalCount() === 0;
    }

    /**
     * Combined decision: redirect this admin, on this action, to the wizard?
     *
     * @param int $adminUserId
     * @param string $fullActionName
     * @return bool
     */
    public function shouldRedirectToWizard(int $adminUserId, string $fullActionName): bool
    {
        if ($this->isExemptAction($fullActionName)) {
            return false;
        }

        return $this->requiresOnboarding($adminUserId);
    }
}
