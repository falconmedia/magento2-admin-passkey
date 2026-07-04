<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Lockout;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Model\Lockout\LockoutEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the lockout decision logic.
 *
 * No I/O is involved: thresholds, windows and timestamps are all passed in, so the
 * test asserts the should-lock, brute-force, window-membership, currently-locked
 * and locked-until rules deterministically across thresholds and windows.
 */
class LockoutEvaluatorTest extends TestCase
{
    private LockoutEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new LockoutEvaluator();
    }

    /**
     * @dataProvider shouldLockProvider
     */
    public function testShouldLock(int $failed, int $max, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->shouldLock($failed, $max));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: bool}>
     */
    public static function shouldLockProvider(): array
    {
        return [
            'below threshold' => [4, 5, false],
            'at threshold' => [5, 5, true],
            'above threshold' => [6, 5, true],
            'zero threshold never locks' => [10, 0, false],
            'negative threshold never locks' => [10, -1, false],
        ];
    }

    /**
     * @dataProvider bruteForceProvider
     */
    public function testIsBruteForce(int $failed, int $max, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isBruteForce($failed, $max));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: bool}>
     */
    public static function bruteForceProvider(): array
    {
        return [
            'at threshold not yet brute force' => [5, 5, false],
            'just below double' => [9, 5, false],
            'at double is brute force' => [10, 5, true],
            'above double is brute force' => [15, 5, true],
            'zero threshold never brute force' => [100, 0, false],
        ];
    }

    /**
     * @dataProvider withinWindowProvider
     */
    public function testIsWithinWindow(string $previous, string $now, int $windowMinutes, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isWithinWindow($previous, $now, $windowMinutes));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int, 3: bool}>
     */
    public static function withinWindowProvider(): array
    {
        return [
            'inside window' => ['2026-01-01 10:00:00', '2026-01-01 10:10:00', 15, true],
            'exactly on window edge' => ['2026-01-01 10:00:00', '2026-01-01 10:15:00', 15, true],
            'outside window' => ['2026-01-01 10:00:00', '2026-01-01 10:16:00', 15, false],
            'zero window never counts' => ['2026-01-01 10:00:00', '2026-01-01 10:00:01', 0, false],
            'invalid previous timestamp' => ['not-a-date', '2026-01-01 10:00:00', 15, false],
        ];
    }

    /**
     * @dataProvider currentlyLockedProvider
     */
    public function testIsCurrentlyLocked(?string $status, ?string $lockedUntil, string $now, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isCurrentlyLocked($status, $lockedUntil, $now));
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null, 2: string, 3: bool}>
     */
    public static function currentlyLockedProvider(): array
    {
        return [
            'active and future' => [
                LockoutInterface::STATUS_ACTIVE,
                '2026-01-01 11:00:00',
                '2026-01-01 10:00:00',
                true,
            ],
            'active but expired' => [
                LockoutInterface::STATUS_ACTIVE,
                '2026-01-01 09:00:00',
                '2026-01-01 10:00:00',
                false,
            ],
            'released never locks' => [
                LockoutInterface::STATUS_RELEASED,
                '2026-01-01 11:00:00',
                '2026-01-01 10:00:00',
                false,
            ],
            'null locked_until never locks' => [
                LockoutInterface::STATUS_ACTIVE,
                null,
                '2026-01-01 10:00:00',
                false,
            ],
            'empty locked_until never locks' => [
                LockoutInterface::STATUS_ACTIVE,
                '',
                '2026-01-01 10:00:00',
                false,
            ],
        ];
    }

    public function testComputeLockedUntilAddsDuration(): void
    {
        $result = $this->evaluator->computeLockedUntil('2026-01-01 10:00:00', 15);

        $this->assertSame('2026-01-01 10:15:00', $result);
    }

    public function testComputeLockedUntilClampsNonPositiveDurationToOneMinute(): void
    {
        $result = $this->evaluator->computeLockedUntil('2026-01-01 10:00:00', 0);

        $this->assertSame('2026-01-01 10:01:00', $result);
    }
}
