<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source\Migration;

use FalconMedia\AdminPasskey\Model\Migration\TwoFactorAuthStatusProvider;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the migration dashboard "2FA" column.
 */
class TwoFaStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => TwoFactorAuthStatusProvider::STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => TwoFactorAuthStatusProvider::STATUS_CONFIGURED, 'label' => __('Configured')],
            ['value' => TwoFactorAuthStatusProvider::STATUS_NONE, 'label' => __('Not configured')],
            ['value' => TwoFactorAuthStatusProvider::STATUS_DISABLED, 'label' => __('Disabled')],
        ];
    }
}
