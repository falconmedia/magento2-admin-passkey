<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeSearchResultsInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeGuard;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for challenge lookup, validation and single-use consumption.
 */
class ChallengeGuardTest extends TestCase
{
    private const NOW = 1_700_000_000;

    /** @var ChallengeRepositoryInterface&MockObject */
    private ChallengeRepositoryInterface $challengeRepository;

    /** @var SearchCriteriaBuilder&MockObject */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /** @var DateTime&MockObject */
    private DateTime $dateTime;

    private ChallengeGuard $guard;

    protected function setUp(): void
    {
        $this->challengeRepository = $this->createMock(ChallengeRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->dateTime = $this->createMock(DateTime::class);

        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));
        $this->dateTime->method('gmtTimestamp')->willReturn(self::NOW);

        $this->guard = new ChallengeGuard(
            $this->challengeRepository,
            $this->searchCriteriaBuilder,
            $this->dateTime
        );
    }

    public function testLoadPendingReturnsValidChallenge(): void
    {
        $challenge = $this->challenge(ChallengeInterface::STATUS_PENDING, self::NOW + 100, 42);
        $this->stubList([$challenge]);

        $this->assertSame($challenge, $this->guard->loadPending(ChallengeInterface::TYPE_ASSERTION, 'value'));
    }

    public function testMissingChallengeRejected(): void
    {
        $this->stubList([]);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey challenge is invalid.');
        $this->guard->loadPending(ChallengeInterface::TYPE_ASSERTION, 'value');
    }

    public function testConsumedChallengeRejectedAsReplay(): void
    {
        $this->stubList([$this->challenge(ChallengeInterface::STATUS_CONSUMED, self::NOW + 100, 42)]);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey challenge has already been used.');
        $this->guard->loadPending(ChallengeInterface::TYPE_ASSERTION, 'value');
    }

    public function testExpiredChallengeRejected(): void
    {
        $this->stubList([$this->challenge(ChallengeInterface::STATUS_PENDING, self::NOW - 1, 42)]);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey challenge has expired.');
        $this->guard->loadPending(ChallengeInterface::TYPE_ASSERTION, 'value');
    }

    public function testAdminMismatchRejected(): void
    {
        $this->stubList([$this->challenge(ChallengeInterface::STATUS_PENDING, self::NOW + 100, 7)]);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey challenge does not match the requested user.');
        $this->guard->loadPending(ChallengeInterface::TYPE_REGISTRATION, 'value', 42);
    }

    public function testConsumeDelegatesToRepository(): void
    {
        $challenge = $this->challenge(ChallengeInterface::STATUS_PENDING, self::NOW + 100, 42);
        $challenge->method('getId')->willReturn(9);
        $this->challengeRepository->expects($this->once())->method('consume')->with(9);

        $this->guard->consume($challenge);
    }

    /**
     * Stub the repository list result.
     *
     * @param array<int, ChallengeInterface> $items
     * @return void
     */
    private function stubList(array $items): void
    {
        $searchResults = $this->createMock(ChallengeSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn($items);
        $this->challengeRepository->method('getList')->willReturn($searchResults);
    }

    /**
     * Build a challenge mock.
     *
     * @param string $status
     * @param int $expiresAtTimestamp
     * @param int|null $adminUserId
     * @return ChallengeInterface&MockObject
     */
    private function challenge(string $status, int $expiresAtTimestamp, ?int $adminUserId): ChallengeInterface
    {
        $challenge = $this->createMock(ChallengeInterface::class);
        $challenge->method('getStatus')->willReturn($status);
        $challenge->method('getExpiresAt')->willReturn(gmdate('Y-m-d H:i:s', $expiresAtTimestamp));
        $challenge->method('getAdminUserId')->willReturn($adminUserId);
        $challenge->method('getChallenge')->willReturn('value');

        return $challenge;
    }
}
