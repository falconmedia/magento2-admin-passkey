<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\Recovery;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

/**
 * Renders the emergency recovery-mode status panel and the enable/disable forms.
 *
 * The interactive behaviour is deliberately minimal: two form-key protected POST
 * forms guarded by a native confirm. All state logic is delegated to the recovery
 * service so no business logic lives in the presentation layer.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Index extends Template
{
    public function __construct(
        Context $context,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly ConfigProvider $configProvider,
        private readonly FormKey $formKeyProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Whether recovery mode is currently active.
     *
     * @return bool
     */
    public function isRecoveryActive(): bool
    {
        return $this->recoveryModeService->isActive();
    }

    /**
     * The current recovery-state record, if any.
     *
     * @return RecoveryStateInterface|null
     */
    public function getCurrentState(): ?RecoveryStateInterface
    {
        return $this->recoveryModeService->getCurrent();
    }

    /**
     * Configured warning text shown before enabling recovery.
     *
     * @return string
     */
    public function getWarningText(): string
    {
        return $this->configProvider->getRecoveryWarningText();
    }

    /**
     * Auto-expiry window (minutes) after which recovery disables itself.
     *
     * @return int
     */
    public function getExpiryMinutes(): int
    {
        return $this->configProvider->getRecoveryExpiryMinutes();
    }

    /**
     * URL of the enable action.
     *
     * @return string
     */
    public function getEnableUrl(): string
    {
        return $this->getUrl('adminpasskey/recovery/enable');
    }

    /**
     * URL of the disable action.
     *
     * @return string
     */
    public function getDisableUrl(): string
    {
        return $this->getUrl('adminpasskey/recovery/disable');
    }

    /**
     * Current admin form key.
     *
     * @return string
     */
    public function getFormKeyValue(): string
    {
        return $this->formKeyProvider->getFormKey();
    }
}
