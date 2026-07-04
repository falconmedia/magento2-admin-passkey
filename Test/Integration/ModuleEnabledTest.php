<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration;

use Magento\Framework\Module\ModuleListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the module is registered and enabled in the integration environment.
 *
 * @magentoAppIsolation enabled
 */
class ModuleEnabledTest extends TestCase
{
    private const MODULE_NAME = 'FalconMedia_AdminPasskey';

    public function testModuleIsEnabled(): void
    {
        /** @var ModuleListInterface $moduleList */
        $moduleList = Bootstrap::getObjectManager()->get(ModuleListInterface::class);

        $this->assertTrue(
            $moduleList->has(self::MODULE_NAME),
            self::MODULE_NAME . ' must be enabled.'
        );
    }

    public function testModuleHasExpectedSequence(): void
    {
        /** @var ModuleListInterface $moduleList */
        $moduleList = Bootstrap::getObjectManager()->get(ModuleListInterface::class);

        $module = $moduleList->getOne(self::MODULE_NAME);

        $this->assertNotNull($module);
        $this->assertContains('Magento_Backend', $module['sequence']);
        $this->assertContains('Magento_TwoFactorAuth', $module['sequence']);
    }
}
