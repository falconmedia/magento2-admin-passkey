<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the trusted-device status grid filter.
 */
class TrustedDeviceStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => TrustedDeviceInterface::STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => TrustedDeviceInterface::STATUS_REVOKED, 'label' => __('Revoked')],
            ['value' => TrustedDeviceInterface::STATUS_EXPIRED, 'label' => __('Expired')],
        ];
    }
}
