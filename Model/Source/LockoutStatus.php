<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the lockout status grid filter.
 */
class LockoutStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => LockoutInterface::STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => LockoutInterface::STATUS_RELEASED, 'label' => __('Released')],
        ];
    }
}
