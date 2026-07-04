<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Admin login page layout variants.
 */
class LoginDesignLayout implements OptionSourceInterface
{
    public const SPOTLIGHT = 'spotlight';

    public const SPLIT_CONSOLE = 'split_console';

    public const COMMAND_DECK = 'command_deck';

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SPOTLIGHT, 'label' => __('Spotlight')],
            ['value' => self::SPLIT_CONSOLE, 'label' => __('Split Console')],
            ['value' => self::COMMAND_DECK, 'label' => __('Image Deck')],
        ];
    }
}
