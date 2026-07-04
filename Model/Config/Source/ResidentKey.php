<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * WebAuthn residentKey (discoverable credential) requirement options.
 */
class ResidentKey implements OptionSourceInterface
{
    public const DISCOURAGED = 'discouraged';
    public const PREFERRED = 'preferred';
    public const REQUIRED = 'required';

    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::DISCOURAGED, 'label' => __('Discouraged')],
            ['value' => self::PREFERRED, 'label' => __('Preferred')],
            ['value' => self::REQUIRED, 'label' => __('Required')],
        ];
    }
}
