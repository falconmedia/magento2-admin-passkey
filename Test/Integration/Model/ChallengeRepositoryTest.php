<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for ChallengeRepository.
 * Uses a real admin user and the repository contract only; no raw SQL and no mocks.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ChallengeRepositoryTest extends TestCase
{
    private ChallengeRepositoryInterface $repository;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(ChallengeRepositoryInterface::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Challenge')
            ->setEmail('passkey.challenge@example.com')
            ->setUsername('passkey_challenge_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testSaveConsumeListAndDeleteExpired(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var ChallengeInterface $challenge */
        $challenge = $objectManager->create(ChallengeInterface::class);
        $challenge->setAdminUserId($this->adminUserId)
            ->setChallenge('challenge-value-abc')
            ->setChallengeType(ChallengeInterface::TYPE_REGISTRATION)
            ->setStatus(ChallengeInterface::STATUS_PENDING)
            ->setRemoteIp('203.0.113.10')
            ->setExpiresAt(gmdate('Y-m-d H:i:s', time() + 300));

        $saved = $this->repository->save($challenge);
        $entityId = $saved->getId();
        $this->assertNotNull($entityId);
        $this->assertGreaterThan(0, $entityId);

        $loaded = $this->repository->getById($entityId);
        $this->assertSame(ChallengeInterface::TYPE_REGISTRATION, $loaded->getChallengeType());
        $this->assertSame(ChallengeInterface::STATUS_PENDING, $loaded->getStatus());

        $consumed = $this->repository->consume($entityId);
        $this->assertSame(ChallengeInterface::STATUS_CONSUMED, $consumed->getStatus());
        $this->assertNotEmpty($consumed->getConsumedAt());

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(ChallengeInterface::ADMIN_USER_ID, $this->adminUserId)
            ->create();
        $list = $this->repository->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $list->getTotalCount());

        /** @var ChallengeInterface $expired */
        $expired = $objectManager->create(ChallengeInterface::class);
        $expired->setAdminUserId($this->adminUserId)
            ->setChallenge('challenge-value-expired')
            ->setChallengeType(ChallengeInterface::TYPE_ASSERTION)
            ->setStatus(ChallengeInterface::STATUS_PENDING)
            ->setExpiresAt(gmdate('Y-m-d H:i:s', time() - 300));
        $this->repository->save($expired);

        $deleted = $this->repository->deleteExpired();
        $this->assertGreaterThanOrEqual(1, $deleted);
    }
}
