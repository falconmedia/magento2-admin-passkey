<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the cleanup log "Status" column.
 */
class CleanupStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => CleanupLogInterface::STATUS_SUCCESS, 'label' => __('Success')],
            ['value' => CleanupLogInterface::STATUS_FAILED, 'label' => __('Failed')],
        ];
    }
}
