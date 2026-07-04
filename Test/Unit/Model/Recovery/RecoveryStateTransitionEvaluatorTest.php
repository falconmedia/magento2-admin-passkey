<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Recovery;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryStateTransitionEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for emergency recovery-mode state transitions.
 *
 * All inputs (state, enabled timestamp, expiry window, now) are passed in, so the
 * active/expired/can-enable/can-disable rules are asserted deterministically,
 * including the auto-expiry safeguard that prevents recovery staying on forever.
 */
class RecoveryStateTransitionEvaluatorTest extends TestCase
{
    private RecoveryStateTransitionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new RecoveryStateTransitionEvaluator();
    }

    /**
     * @dataProvider activeProvider
     */
    public function testIsActive(?string $state, ?string $enabledAt, int $expiry, string $now, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isActive($state, $enabledAt, $expiry, $now));
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null, 2: int, 3: string, 4: bool}>
     */
    public static function activeProvider(): array
    {
        return [
            'enabled within window is active' => [
                RecoveryStateInterface::STATE_ENABLED,
                '2026-01-01 10:00:00',
                60,
                '2026-01-01 10:30:00',
                true,
            ],
            'enabled but expired is inactive' => [
                RecoveryStateInterface::STATE_ENABLED,
                '2026-01-01 10:00:00',
                60,
                '2026-01-01 11:30:00',
                false,
            ],
            'enabled with no expiry stays active' => [
                RecoveryStateInterface::STATE_ENABLED,
                '2026-01-01 10:00:00',
                0,
                '2027-01-01 10:00:00',
                true,
            ],
            'disabled is never active' => [
                RecoveryStateInterface::STATE_DISABLED,
                '2026-01-01 10:00:00',
                60,
                '2026-01-01 10:10:00',
                false,
            ],
            'null state is never active' => [
                null,
                '2026-01-01 10:00:00',
                60,
                '2026-01-01 10:10:00',
                false,
            ],
        ];
    }

    /**
     * @dataProvider expiredProvider
     */
    public function testIsExpired(?string $enabledAt, int $expiry, string $now, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isExpired($enabledAt, $expiry, $now));
    }

    /**
     * @return array<string, array{0: string|null, 1: int, 2: string, 3: bool}>
     */
    public static function expiredProvider(): array
    {
        return [
            'before expiry' => ['2026-01-01 10:00:00', 60, '2026-01-01 10:59:00', false],
            'exactly at expiry' => ['2026-01-01 10:00:00', 60, '2026-01-01 11:00:00', true],
            'after expiry' => ['2026-01-01 10:00:00', 60, '2026-01-01 12:00:00', true],
            'no expiry window never expires' => ['2026-01-01 10:00:00', 0, '2030-01-01 10:00:00', false],
            'null enabled_at never expires' => [null, 60, '2026-01-01 12:00:00', false],
        ];
    }

    public function testCanEnableOnlyWhenNotActive(): void
    {
        $this->assertTrue($this->evaluator->canEnable(false));
        $this->assertFalse($this->evaluator->canEnable(true));
    }

    public function testCanDisableOnlyWhenActive(): void
    {
        $this->assertTrue($this->evaluator->canDisable(true));
        $this->assertFalse($this->evaluator->canDisable(false));
    }
}
