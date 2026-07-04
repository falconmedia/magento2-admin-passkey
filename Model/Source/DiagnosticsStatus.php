<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the diagnostics report "Status" column.
 */
class DiagnosticsStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => DiagnosticsReportInterface::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => DiagnosticsReportInterface::STATUS_GENERATED, 'label' => __('Generated')],
            ['value' => DiagnosticsReportInterface::STATUS_SENT, 'label' => __('Sent')],
            ['value' => DiagnosticsReportInterface::STATUS_FAILED, 'label' => __('Failed')],
        ];
    }
}
