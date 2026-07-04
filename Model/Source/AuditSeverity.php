<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the audit severity grid filter.
 */
class AuditSeverity implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => AuditEventInterface::SEVERITY_INFO, 'label' => __('Info')],
            ['value' => AuditEventInterface::SEVERITY_NOTICE, 'label' => __('Notice')],
            ['value' => AuditEventInterface::SEVERITY_WARNING, 'label' => __('Warning')],
            ['value' => AuditEventInterface::SEVERITY_CRITICAL, 'label' => __('Critical')],
        ];
    }
}
