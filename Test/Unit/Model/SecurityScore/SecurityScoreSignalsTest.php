<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\SecurityScore;

use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreSignals;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the passkey adoption ratio helper.
 */
class SecurityScoreSignalsTest extends TestCase
{
    /**
     * @dataProvider ratioProvider
     */
    public function testAdoptionRatio(int $total, int $withPasskey, float $expected): void
    {
        $signals = new SecurityScoreSignals(
            $total,
            $withPasskey,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            0,
            0,
            0,
            0,
            false,
            false
        );

        $this->assertSame($expected, $signals->getPasskeyAdoptionRatio());
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: float}>
     */
    public static function ratioProvider(): array
    {
        return [
            'no-admins' => [0, 0, 0.0],
            'full-adoption' => [4, 4, 1.0],
            'half-adoption' => [4, 2, 0.5],
            'over-count-clamped' => [2, 5, 1.0],
        ];
    }
}
