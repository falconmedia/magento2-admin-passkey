<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source\Migration;

use FalconMedia\AdminPasskey\Model\Migration\MigrationRowAssembler;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the migration dashboard "Lockout" column.
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
            ['value' => MigrationRowAssembler::LOCKOUT_LOCKED, 'label' => __('Locked')],
            ['value' => MigrationRowAssembler::LOCKOUT_NONE, 'label' => __('None')],
        ];
    }
}
