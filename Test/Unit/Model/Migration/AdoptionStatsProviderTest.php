<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Migration;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterface;
use FalconMedia\AdminPasskey\Model\Migration\AdminUserProvider;
use FalconMedia\AdminPasskey\Model\Migration\AdoptionStatsProvider;
use FalconMedia\AdminPasskey\Model\Migration\TwoFactorAuthStatusProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the passkey adoption-stats aggregator.
 */
class AdoptionStatsProviderTest extends TestCase
{
    /**
     * @var AdminUserProvider&MockObject
     */
    private AdminUserProvider&MockObject $adminUserProvider;

    /**
     * @var CredentialRepositoryInterface&MockObject
     */
    private CredentialRepositoryInterface&MockObject $credentialRepository;

    /**
     * @var TwoFactorAuthStatusProvider&MockObject
     */
    private TwoFactorAuthStatusProvider&MockObject $twoFactorAuthStatusProvider;

    private AdoptionStatsProvider $provider;

    protected function setUp(): void
    {
        $this->adminUserProvider = $this->createMock(AdminUserProvider::class);
        $this->credentialRepository = $this->createMock(CredentialRepositoryInterface::class);
        $this->twoFactorAuthStatusProvider = $this->createMock(TwoFactorAuthStatusProvider::class);

        $this->provider = new AdoptionStatsProvider(
            $this->adminUserProvider,
            $this->credentialRepository,
            $this->twoFactorAuthStatusProvider
        );
    }

    public function testAggregatesAdoptionAndTwoFaCounts(): void
    {
        $this->adminUserProvider->method('getAdminIds')->with(true)->willReturn([1, 2, 3]);
        $this->credentialRepository->method('listActiveForAdmin')->willReturnCallback(
            fn (int $id): CredentialSearchResultsInterface => $this->results([1 => 1, 2 => 0, 3 => 2][$id] ?? 0)
        );
        $this->twoFactorAuthStatusProvider->method('isEnabled')->willReturn(true);
        $this->twoFactorAuthStatusProvider->method('getStatusForUser')->willReturnMap(
            [
                [1, TwoFactorAuthStatusProvider::STATUS_ACTIVE],
                [2, TwoFactorAuthStatusProvider::STATUS_CONFIGURED],
                [3, TwoFactorAuthStatusProvider::STATUS_NONE],
            ]
        );

        $stats = $this->provider->getStats(true);

        $this->assertSame(3, $stats->getTotalAdmins());
        $this->assertSame(2, $stats->getWithPasskey());
        $this->assertSame(1, $stats->getWithoutPasskey());
        $this->assertSame(66.7, $stats->getAdoptionPercent());
        $this->assertSame(1, $stats->getTwoFaActive());
        $this->assertSame(1, $stats->getTwoFaConfigured());
        $this->assertSame(1, $stats->getTwoFaNone());
        $this->assertSame(0, $stats->getTwoFaDisabled());
        $this->assertTrue($stats->isTwoFaEnabledGlobally());
    }

    public function testNoAdminsProducesZeroPercent(): void
    {
        $this->adminUserProvider->method('getAdminIds')->willReturn([]);
        $this->twoFactorAuthStatusProvider->method('isEnabled')->willReturn(false);

        $stats = $this->provider->getStats(false);

        $this->assertSame(0, $stats->getTotalAdmins());
        $this->assertSame(0.0, $stats->getAdoptionPercent());
        $this->assertFalse($stats->isTwoFaEnabledGlobally());
    }

    public function testCredentialLookupFailureCountsAsNoPasskey(): void
    {
        $this->adminUserProvider->method('getAdminIds')->willReturn([5]);
        $this->credentialRepository->method('listActiveForAdmin')
            ->willThrowException(new \RuntimeException('db down'));
        $this->twoFactorAuthStatusProvider->method('isEnabled')->willReturn(true);
        $this->twoFactorAuthStatusProvider->method('getStatusForUser')
            ->willReturn(TwoFactorAuthStatusProvider::STATUS_NONE);

        $stats = $this->provider->getStats();

        $this->assertSame(1, $stats->getTotalAdmins());
        $this->assertSame(0, $stats->getWithPasskey());
        $this->assertSame(1, $stats->getWithoutPasskey());
    }

    private function results(int $total): CredentialSearchResultsInterface
    {
        $results = $this->createMock(CredentialSearchResultsInterface::class);
        $results->method('getTotalCount')->willReturn($total);

        return $results;
    }
}
