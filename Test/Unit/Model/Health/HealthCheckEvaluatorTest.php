<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Health;

use FalconMedia\AdminPasskey\Model\Health\HealthCheckEvaluator;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the health check evaluation rules.
 */
class HealthCheckEvaluatorTest extends TestCase
{
    private HealthCheckEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new HealthCheckEvaluator();
    }

    public function testPhpVersion(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluatePhpVersion('8.3.0', '8.3.0')->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_ERROR,
            $this->evaluator->evaluatePhpVersion('8.1.0', '8.3.0')->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_ERROR,
            $this->evaluator->evaluatePhpVersion('', '8.3.0')->getStatus()
        );
    }

    public function testHttps(): void
    {
        $ok = $this->evaluator->evaluateHttps(true);
        $this->assertSame(HealthCheckResult::STATUS_OK, $ok->getStatus());
        $this->assertSame(HealthCheckEvaluator::CHECK_HTTPS, $ok->getId());
        $this->assertSame(HealthCheckResult::STATUS_ERROR, $this->evaluator->evaluateHttps(false)->getStatus());
    }

    public function testWebAuthn(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateWebAuthn('example.com', true)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_ERROR,
            $this->evaluator->evaluateWebAuthn('', true)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_ERROR,
            $this->evaluator->evaluateWebAuthn('example.com', false)->getStatus()
        );
    }

    public function testHsts(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateHsts('max-age=31536000')->getStatus()
        );
        $this->assertSame(HealthCheckResult::STATUS_WARNING, $this->evaluator->evaluateHsts(null)->getStatus());
        $this->assertSame(HealthCheckResult::STATUS_WARNING, $this->evaluator->evaluateHsts('  ')->getStatus());
    }

    public function testCron(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateCron(true, 5, 60)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_WARNING,
            $this->evaluator->evaluateCron(true, 120, 60)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_WARNING,
            $this->evaluator->evaluateCron(false, null, 60)->getStatus()
        );
    }

    public function testCleanupConfig(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateCleanupConfig(true, 30)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_WARNING,
            $this->evaluator->evaluateCleanupConfig(false, 30)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_ERROR,
            $this->evaluator->evaluateCleanupConfig(true, -1)->getStatus()
        );
    }

    public function testConfigSanity(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateConfigSanity(100)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_ERROR,
            $this->evaluator->evaluateConfigSanity(0)->getStatus()
        );
    }

    public function testLockoutHealth(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateLockoutHealth(1, 5)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_WARNING,
            $this->evaluator->evaluateLockoutHealth(5, 5)->getStatus()
        );
    }

    public function testRecoveryState(): void
    {
        $this->assertSame(
            HealthCheckResult::STATUS_WARNING,
            $this->evaluator->evaluateRecoveryState(true)->getStatus()
        );
        $this->assertSame(
            HealthCheckResult::STATUS_OK,
            $this->evaluator->evaluateRecoveryState(false)->getStatus()
        );
    }
}
