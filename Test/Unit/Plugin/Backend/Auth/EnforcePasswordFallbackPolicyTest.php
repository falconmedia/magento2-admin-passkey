<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Plugin\Backend\Auth;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use FalconMedia\AdminPasskey\Plugin\Backend\Auth\EnforcePasswordFallbackPolicy;
use Magento\Backend\Model\Auth;
use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit coverage for the server-side "password fallback disabled" enforcement.
 */
class EnforcePasswordFallbackPolicyTest extends TestCase
{
    public function testPasswordLoginRejectedWhenFallbackDisabled(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->plugin(true, false, false)->beforeLogin($this->createMock(Auth::class), 'admin', 'secret');
    }

    public function testAdminTokenAuthenticationRejectedWhenFallbackDisabled(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->plugin(true, false, false)->beforeAuthenticate($this->createMock(User::class), 'admin', 'secret');
    }

    public function testRecoveryLookupErrorFailsOpen(): void
    {
        $config = $this->createMock(ConfigProvider::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('isPasswordFallbackEnabled')->willReturn(false);
        $recoveryService = $this->createMock(RecoveryModeServiceInterface::class);
        $recoveryService->method('isActive')->willThrowException(new \RuntimeException('db down'));

        $plugin = new EnforcePasswordFallbackPolicy($config, $recoveryService, $this->createMock(LoggerInterface::class));
        $plugin->beforeAuthenticate($this->createMock(User::class), 'admin', 'secret');
        $this->addToAssertionCount(1);
    }

    public function testPasswordLoginAllowedWhenFallbackEnabled(): void
    {
        $this->plugin(true, true, false)->beforeLogin($this->createMock(Auth::class), 'admin', 'secret');
        $this->addToAssertionCount(1);
    }

    public function testPasswordLoginAllowedWhenModuleDisabled(): void
    {
        $this->plugin(false, false, false)->beforeLogin($this->createMock(Auth::class), 'admin', 'secret');
        $this->addToAssertionCount(1);
    }

    public function testPasswordLoginAllowedDuringRecovery(): void
    {
        $this->plugin(true, false, true)->beforeLogin($this->createMock(Auth::class), 'admin', 'secret');
        $this->addToAssertionCount(1);
    }

    private function plugin(bool $enabled, bool $fallback, bool $recovery): EnforcePasswordFallbackPolicy
    {
        $config = $this->createMock(ConfigProvider::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('isPasswordFallbackEnabled')->willReturn($fallback);

        $recoveryService = $this->createMock(RecoveryModeServiceInterface::class);
        $recoveryService->method('isActive')->willReturn($recovery);

        return new EnforcePasswordFallbackPolicy($config, $recoveryService, $this->createMock(LoggerInterface::class));
    }
}
