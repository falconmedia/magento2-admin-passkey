<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Dashboard;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Dashboard\DashboardCard;
use FalconMedia\AdminPasskey\Model\Dashboard\DashboardCardAssembler;
use FalconMedia\AdminPasskey\Model\Dashboard\DashboardMetrics;
use FalconMedia\AdminPasskey\Model\Dashboard\DashboardMetricsProviderInterface;
use Magento\Framework\AuthorizationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the dashboard card assembler: config toggles, ACL gating
 * and status computation, with all data sources mocked.
 */
class DashboardCardAssemblerTest extends TestCase
{
    /**
     * @var DashboardMetricsProviderInterface&MockObject
     */
    private DashboardMetricsProviderInterface&MockObject $metricsProvider;

    /**
     * @var ConfigProvider&MockObject
     */
    private ConfigProvider&MockObject $configProvider;

    /**
     * @var AuthorizationInterface&MockObject
     */
    private AuthorizationInterface&MockObject $authorization;

    private DashboardCardAssembler $assembler;

    protected function setUp(): void
    {
        $this->metricsProvider = $this->createMock(DashboardMetricsProviderInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->metricsProvider->method('getMetrics')->willReturn($this->sampleMetrics());

        $this->assembler = new DashboardCardAssembler(
            $this->metricsProvider,
            $this->configProvider,
            $this->authorization
        );
    }

    public function testAllCardsAssembledWhenEnabledAndAllowed(): void
    {
        $this->allowEverything();

        $cards = $this->indexById($this->assembler->assemble());

        $this->assertCount(14, $cards);
        $this->assertArrayHasKey('passkey_adoption', $cards);
        $this->assertArrayHasKey('quick_actions', $cards);
    }

    public function testCardHiddenWhenDisabledInConfig(): void
    {
        $this->authorization->method('isAllowed')->willReturn(true);
        $this->configProvider->method('isDashboardCardEnabled')
            ->willReturnCallback(static fn (string $card): bool => $card !== 'security_score');

        $cards = $this->indexById($this->assembler->assemble());

        $this->assertArrayNotHasKey('security_score', $cards);
        $this->assertArrayHasKey('passkey_adoption', $cards);
    }

    public function testCardHiddenWhenAclDenied(): void
    {
        $this->configProvider->method('isDashboardCardEnabled')->willReturn(true);
        $this->authorization->method('isAllowed')
            ->willReturnCallback(
                static fn (string $resource): bool => $resource !== 'FalconMedia_AdminPasskey::lockouts'
            );

        $cards = $this->indexById($this->assembler->assemble());

        $this->assertArrayNotHasKey('active_lockouts', $cards);
        $this->assertArrayHasKey('audit_events', $cards);
    }

    public function testAdoptionCardComputesPercentageAndStatus(): void
    {
        $this->allowEverything();

        $cards = $this->indexById($this->assembler->assemble());
        $adoption = $cards['passkey_adoption'];

        $this->assertSame('80%', $adoption->getValue());
        $this->assertSame(DashboardCard::STATUS_OK, $adoption->getStatus());
    }

    public function testActiveLockoutsCardIsCriticalWhenPositive(): void
    {
        $this->allowEverything();

        $cards = $this->indexById($this->assembler->assemble());
        $lockouts = $cards['active_lockouts'];

        $this->assertSame('2', $lockouts->getValue());
        $this->assertSame(DashboardCard::STATUS_CRITICAL, $lockouts->getStatus());
    }

    public function testQuickActionsCardExposesLinks(): void
    {
        $this->allowEverything();

        $cards = $this->indexById($this->assembler->assemble());
        $quick = $cards['quick_actions'];

        $this->assertNull($quick->getValue());
        $this->assertNotEmpty($quick->getLinks());
    }

    private function allowEverything(): void
    {
        $this->configProvider->method('isDashboardCardEnabled')->willReturn(true);
        $this->authorization->method('isAllowed')->willReturn(true);
    }

    /**
     * @param DashboardCard[] $cards
     * @return array<string, DashboardCard>
     */
    private function indexById(array $cards): array
    {
        $indexed = [];
        foreach ($cards as $card) {
            $indexed[$card->getId()] = $card;
        }

        return $indexed;
    }

    private function sampleMetrics(): DashboardMetrics
    {
        return new DashboardMetrics(
            10,
            8,
            30,
            10,
            2,
            5,
            'ok',
            0,
            1,
            false,
            null,
            4,
            3,
            100,
            '2026-01-01 03:00:00',
            'success',
            ['type' => 'passkey_login', 'severity' => 'info', 'created_at' => '2026-01-02 10:00:00'],
            85,
            'Excellent'
        );
    }
}
