<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Observer;

use FalconMedia\AdminPasskey\Model\Onboarding\OnboardingPolicy;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Backend\Model\UrlInterface as BackendUrl;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Enforces passkey onboarding by redirecting a logged-in admin with no active
 * passkey to the setup wizard.
 *
 * Wired to the admin-area {@see controller_action_predispatch} event (see
 * etc/adminhtml/events.xml), so it runs after the native authentication plugin
 * has already established a session — never before login. The decision itself
 * lives in {@see OnboardingPolicy}, which always exempts this module's own routes
 * (wizard, passkey endpoints, recovery) plus the native login/logout actions, so
 * an admin is guided into onboarding without ever being permanently locked out.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class RequirePasskeyOnboarding implements ObserverInterface
{
    /**
     * Route to the passkey setup wizard.
     */
    private const WIZARD_ROUTE = 'adminpasskey/passkey/wizard';

    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly OnboardingPolicy $onboardingPolicy,
        private readonly BackendUrl $backendUrl,
        private readonly ActionFlag $actionFlag
    ) {
    }

    /**
     * Redirect to the wizard when onboarding is required for the current request.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->adminSession->isLoggedIn()) {
            return;
        }

        $action = $observer->getControllerAction();
        $request = $observer->getRequest();
        if ($action === null || $request === null) {
            return;
        }

        $adminUserId = (int) $this->adminSession->getUser()?->getId();
        if (!$this->onboardingPolicy->shouldRedirectToWizard($adminUserId, (string) $request->getFullActionName())) {
            return;
        }

        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
        $action->getResponse()->setRedirect($this->backendUrl->getUrl(self::WIZARD_ROUTE));
    }
}
