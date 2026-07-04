<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * WebAuthn userVerification requirement options.
 */
class UserVerification implements OptionSourceInterface
{
    public const REQUIRED = 'required';
    public const PREFERRED = 'preferred';
    public const DISCOURAGED = 'discouraged';

    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::REQUIRED, 'label' => __('Required')],
            ['value' => self::PREFERRED, 'label' => __('Preferred')],
            ['value' => self::DISCOURAGED, 'label' => __('Discouraged')],
        ];
    }
}
