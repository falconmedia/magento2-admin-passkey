<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\TrustedDevice;

use FalconMedia\AdminPasskey\Model\TrustedDevice\SuccessfulLoginTrustPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the successful-login trust promotion policy.
 *
 * The policy must never promote a browser while the feature is disabled or while
 * it is already trusted, and only once the successful-login threshold is reached.
 */
class SuccessfulLoginTrustPolicyTest extends TestCase
{
    private SuccessfulLoginTrustPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new SuccessfulLoginTrustPolicy();
    }

    /**
     * @dataProvider decisionProvider
     */
    public function testShouldCreateTrustedDevice(
        bool $enabled,
        bool $alreadyTrusted,
        int $successCount,
        int $threshold,
        bool $expected
    ): void {
        $this->assertSame(
            $expected,
            $this->policy->shouldCreateTrustedDevice($enabled, $alreadyTrusted, $successCount, $threshold)
        );
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: int, 3: int, 4: bool}>
     */
    public static function decisionProvider(): array
    {
        return [
            'disabled never promotes' => [false, false, 10, 3, false],
            'already trusted never promotes' => [true, true, 10, 3, false],
            'below threshold does not promote' => [true, false, 2, 3, false],
            'at threshold promotes' => [true, false, 3, 3, true],
            'above threshold promotes' => [true, false, 5, 3, true],
            'zero threshold never promotes' => [true, false, 5, 0, false],
        ];
    }
}
