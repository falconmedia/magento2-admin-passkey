<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\SecurityScore;

use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreCalculator;
use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreSignals;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the security score maths.
 */
class SecurityScoreCalculatorTest extends TestCase
{
    private SecurityScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SecurityScoreCalculator();
    }

    public function testPerfectSignalsProduceExcellentScore(): void
    {
        $signals = $this->signals(
            totalAdmins: 4,
            adminsWithPasskey: 4,
            passkeyFirstLogin: true,
            passwordFallbackEnabled: false,
            lockoutEnabled: true,
            trustedDevicesEnabled: true,
            recoveryActive: false,
            httpsEnabled: true,
            twoFaEnabled: true,
            activeLockouts: 0,
            failedLogins24h: 0,
            healthErrors: 0,
            healthWarnings: 0,
            cleanupEnabled: true,
            diagnosticsEnabled: true
        );

        $breakdown = $this->calculator->categoryScores($signals);
        $score = $this->calculator->calculate($breakdown, $this->equalWeights());

        $this->assertSame(100, $breakdown[SecurityScoreCalculator::CATEGORY_AUTHENTICATION]);
        $this->assertSame(100, $breakdown[SecurityScoreCalculator::CATEGORY_SECURITY]);
        $this->assertSame(100, $breakdown[SecurityScoreCalculator::CATEGORY_OPERATIONAL]);
        $this->assertSame(100, $breakdown[SecurityScoreCalculator::CATEGORY_THREATS]);
        $this->assertSame(100, $score);
        $this->assertSame(SecurityScoreCalculator::LABEL_EXCELLENT, $this->calculator->label($score));
        $this->assertSame([], $this->calculator->recommendations($signals));
    }

    public function testWorstSignalsProducePoorScore(): void
    {
        $signals = $this->signals(
            totalAdmins: 4,
            adminsWithPasskey: 0,
            passkeyFirstLogin: false,
            passwordFallbackEnabled: true,
            lockoutEnabled: false,
            trustedDevicesEnabled: false,
            recoveryActive: true,
            httpsEnabled: false,
            twoFaEnabled: false,
            activeLockouts: 5,
            failedLogins24h: 50,
            healthErrors: 3,
            healthWarnings: 4,
            cleanupEnabled: false,
            diagnosticsEnabled: false
        );

        $breakdown = $this->calculator->categoryScores($signals);
        $score = $this->calculator->calculate($breakdown, $this->equalWeights());

        $this->assertSame(0, $breakdown[SecurityScoreCalculator::CATEGORY_AUTHENTICATION]);
        $this->assertSame(0, $breakdown[SecurityScoreCalculator::CATEGORY_THREATS]);
        $this->assertSame(SecurityScoreCalculator::LABEL_POOR, $this->calculator->label($score));

        $recommendations = $this->calculator->recommendations($signals);
        $this->assertContains(SecurityScoreCalculator::RECOMMENDATION_INCREASE_ADOPTION, $recommendations);
        $this->assertContains(SecurityScoreCalculator::RECOMMENDATION_ENABLE_HTTPS, $recommendations);
        $this->assertContains(SecurityScoreCalculator::RECOMMENDATION_ENABLE_LOCKOUT, $recommendations);
        $this->assertContains(SecurityScoreCalculator::RECOMMENDATION_DISABLE_RECOVERY, $recommendations);
        $this->assertContains(SecurityScoreCalculator::RECOMMENDATION_REVIEW_LOCKOUTS, $recommendations);
    }

    public function testCategoryScoresAreClampedToRange(): void
    {
        $signals = $this->signals(
            totalAdmins: 1,
            adminsWithPasskey: 1,
            passkeyFirstLogin: true,
            passwordFallbackEnabled: false,
            lockoutEnabled: true,
            trustedDevicesEnabled: true,
            recoveryActive: false,
            httpsEnabled: true,
            twoFaEnabled: true,
            activeLockouts: 100,
            failedLogins24h: 100,
            healthErrors: 100,
            healthWarnings: 100,
            cleanupEnabled: true,
            diagnosticsEnabled: true
        );

        foreach ($this->calculator->categoryScores($signals) as $categoryScore) {
            $this->assertGreaterThanOrEqual(0, $categoryScore);
            $this->assertLessThanOrEqual(100, $categoryScore);
        }
    }

    public function testZeroWeightSumReturnsZero(): void
    {
        $breakdown = [
            SecurityScoreCalculator::CATEGORY_AUTHENTICATION => 100,
            SecurityScoreCalculator::CATEGORY_SECURITY => 100,
            SecurityScoreCalculator::CATEGORY_OPERATIONAL => 100,
            SecurityScoreCalculator::CATEGORY_THREATS => 100,
        ];
        $weights = [
            SecurityScoreCalculator::CATEGORY_AUTHENTICATION => 0,
            SecurityScoreCalculator::CATEGORY_SECURITY => 0,
            SecurityScoreCalculator::CATEGORY_OPERATIONAL => 0,
            SecurityScoreCalculator::CATEGORY_THREATS => 0,
        ];

        $this->assertSame(0, $this->calculator->calculate($breakdown, $weights));
    }

    /**
     * @dataProvider labelProvider
     */
    public function testLabelBoundaries(int $score, string $expected): void
    {
        $this->assertSame($expected, $this->calculator->label($score));
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function labelProvider(): array
    {
        return [
            'excellent-lower-bound' => [80, SecurityScoreCalculator::LABEL_EXCELLENT],
            'good-lower-bound' => [60, SecurityScoreCalculator::LABEL_GOOD],
            'fair-lower-bound' => [40, SecurityScoreCalculator::LABEL_FAIR],
            'poor' => [39, SecurityScoreCalculator::LABEL_POOR],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function equalWeights(): array
    {
        return [
            SecurityScoreCalculator::CATEGORY_AUTHENTICATION => 25,
            SecurityScoreCalculator::CATEGORY_SECURITY => 25,
            SecurityScoreCalculator::CATEGORY_OPERATIONAL => 25,
            SecurityScoreCalculator::CATEGORY_THREATS => 25,
        ];
    }

    private function signals(
        int $totalAdmins,
        int $adminsWithPasskey,
        bool $passkeyFirstLogin,
        bool $passwordFallbackEnabled,
        bool $lockoutEnabled,
        bool $trustedDevicesEnabled,
        bool $recoveryActive,
        bool $httpsEnabled,
        bool $twoFaEnabled,
        int $activeLockouts,
        int $failedLogins24h,
        int $healthErrors,
        int $healthWarnings,
        bool $cleanupEnabled,
        bool $diagnosticsEnabled
    ): SecurityScoreSignals {
        return new SecurityScoreSignals(
            $totalAdmins,
            $adminsWithPasskey,
            $passkeyFirstLogin,
            $passwordFallbackEnabled,
            $lockoutEnabled,
            $trustedDevicesEnabled,
            $recoveryActive,
            $httpsEnabled,
            $twoFaEnabled,
            $activeLockouts,
            $failedLogins24h,
            $healthErrors,
            $healthWarnings,
            $cleanupEnabled,
            $diagnosticsEnabled
        );
    }
}
