<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Locale\OptionInterface as DeployedLocales;

/**
 * Login-page language options: "Auto-detect (browser)" plus every locale the
 * Magento/backend theme is deployed for (the same set the admin Interface
 * Locale dropdown offers).
 */
class LoginLanguage implements OptionSourceInterface
{
    /**
     * Sentinel value: detect the language from the browser Accept-Language header.
     */
    public const AUTO = 'auto';

    public function __construct(
        private readonly DeployedLocales $deployedLocales
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => self::AUTO, 'label' => __('Auto-detect (browser)')],
        ];

        foreach ($this->deployedLocales->getOptionLocales() as $locale) {
            $options[] = $locale;
        }

        return $options;
    }
}
