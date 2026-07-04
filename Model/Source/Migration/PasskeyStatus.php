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
 * Option source for the migration dashboard "Passkey" column.
 */
class PasskeyStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => MigrationRowAssembler::PASSKEY_REGISTERED, 'label' => __('Registered')],
            ['value' => MigrationRowAssembler::PASSKEY_MISSING, 'label' => __('Missing')],
        ];
    }
}
