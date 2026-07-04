<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\Passkey;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Supplies configuration for the CSP-safe passkey setup wizard component.
 *
 * The block itself renders no interactive markup; it only serialises the endpoint
 * URLs, the form key and the translated JS labels into a data-mage-init binding
 * consumed by the wizard JS component. The wizard reuses the existing authenticated
 * register/options + register/verify endpoints and the rename endpoint.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Wizard extends Template
{
    /**
     * JS component (RequireJS module) that drives the wizard steps.
     */
    private const JS_COMPONENT = 'FalconMedia_AdminPasskey/js/wizard';

    public function __construct(
        Context $context,
        private readonly ConfigProvider $configProvider,
        private readonly FormKey $formKeyProvider,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Whether the wizard should recommend registering a second passkey at the end.
     *
     * @return bool
     */
    public function isSecondPasskeyRecommended(): bool
    {
        return $this->configProvider->isSecondPasskeyRecommended();
    }

    /**
     * When onboarding is mandatory, the wizard cannot be dismissed until a
     * passkey is registered — the close control becomes a Sign out link instead.
     *
     * @return bool
     */
    public function isOnboardingMandatory(): bool
    {
        return $this->configProvider->isPasskeyOnboardingRequired();
    }

    /**
     * Where the close (x) control returns to — the My Passkeys page.
     */
    public function getCloseUrl(): string
    {
        return $this->getUrl('adminpasskey/passkey/index');
    }

    /**
     * Admin sign-out URL, used when onboarding is mandatory and unmet.
     */
    public function getSignOutUrl(): string
    {
        return $this->getUrl('adminhtml/auth/logout');
    }

    /**
     * data-mage-init JSON binding the wizard component to its configuration.
     *
     * @return string
     */
    public function getMageInitJson(): string
    {
        return $this->json->serialize([
            self::JS_COMPONENT => [
                'registerOptionsUrl' => $this->getUrl('adminpasskey/register/options'),
                'registerVerifyUrl' => $this->getUrl('adminpasskey/register/verify'),
                'renameUrl' => $this->getUrl('adminpasskey/passkey/rename'),
                'finishUrl' => $this->getUrl('adminpasskey/passkey/index'),
                'closeUrl' => $this->getUrl('adminpasskey/passkey/index'),
                'signOutUrl' => $this->getUrl('adminhtml/auth/logout'),
                'formKey' => $this->formKeyProvider->getFormKey(),
                'recommendSecondPasskey' => $this->isSecondPasskeyRecommended(),
                'mandatory' => $this->isOnboardingMandatory(),
                'labels' => [
                    'inProgress' => (string) __('Waiting for your passkey…'),
                    'unsupported' => (string) __('This browser does not support passkeys.'),
                    'registerFailed' => (string) __('The passkey could not be registered. Please try again.'),
                    'renameFailed' => (string) __('The passkey name could not be saved. Please try again.'),
                    'nameRequired' => (string) __('Please enter a name for this passkey.'),
                ],
            ],
        ]);
    }
}
