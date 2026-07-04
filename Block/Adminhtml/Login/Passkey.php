<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\Login;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Renders the passkey affordance on the Admin login page.
 *
 * The block only supplies configuration (endpoint URLs, redirect target and
 * translated labels) to a CSP-safe JS component via data-mage-init; the button
 * itself is progressively enhanced by the browser only when WebAuthn is
 * available, so the native username/password + Magento 2FA login always remains
 * usable and unchanged.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Passkey extends Template
{
    /**
     * JS component (RequireJS module) that enhances the login form.
     */
    private const JS_COMPONENT = 'FalconMedia_AdminPasskey/js/login';

    public function __construct(
        Context $context,
        private readonly ConfigProvider $configProvider,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Whether the passkey login affordance should be rendered at all.
     *
     * @return bool
     */
    public function isPasskeyLoginEnabled(): bool
    {
        return $this->configProvider->isEnabled() && $this->configProvider->isPasskeyFirstLogin();
    }

    /**
     * data-mage-init JSON binding the JS component to its configuration.
     *
     * @return string
     */
    public function getMageInitJson(): string
    {
        return $this->json->serialize([
            self::JS_COMPONENT => [
                'optionsUrl' => $this->getUrl('adminpasskey/login/options'),
                'verifyUrl' => $this->getUrl('adminpasskey/login/verify'),
                'redirectUrl' => $this->getRequestedRedirect(),
                'labels' => [
                    'button' => (string) __('Sign in with a passkey'),
                    'inProgress' => (string) __('Waiting for your passkey…'),
                    'unsupported' => (string) __('This browser does not support passkeys.'),
                    'failed' => (string) __('The passkey login could not be completed.'),
                ],
            ],
        ]);
    }

    /**
     * Optional post-login redirect URL passed through the Admin login request.
     *
     * @return string
     */
    private function getRequestedRedirect(): string
    {
        $url = $this->getRequest()->getParam('url');

        return is_string($url) ? $url : '';
    }
}
