<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model;

use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for LockoutRepository.
 * Uses a real admin user and the repository contract only; no raw SQL and no mocks.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class LockoutRepositoryTest extends TestCase
{
    private LockoutRepositoryInterface $repository;
    private int $adminUserId;
    private string $username;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(LockoutRepositoryInterface::class);

        $this->username = 'passkey_lockout_admin';

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Lockout')
            ->setEmail('passkey.lockout@example.com')
            ->setUsername($this->username)
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testSaveGetByIdAndList(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var LockoutInterface $lockout */
        $lockout = $objectManager->create(LockoutInterface::class);
        $lockout->setAdminUserId($this->adminUserId)
            ->setUsername($this->username)
            ->setReason('too_many_failed_attempts')
            ->setFailedAttempts(5)
            ->setStatus(LockoutInterface::STATUS_ACTIVE)
            ->setLockedUntil(gmdate('Y-m-d H:i:s', time() + 900))
            ->setMetadata('{"ip":"203.0.113.30"}');

        $saved = $this->repository->save($lockout);
        $entityId = $saved->getId();
        $this->assertNotNull($entityId);
        $this->assertGreaterThan(0, $entityId);

        $loaded = $this->repository->getById($entityId);
        $this->assertSame($this->adminUserId, $loaded->getAdminUserId());
        $this->assertSame($this->username, $loaded->getUsername());
        $this->assertSame(5, $loaded->getFailedAttempts());
        $this->assertSame(LockoutInterface::STATUS_ACTIVE, $loaded->getStatus());
        $this->assertNotEmpty($loaded->getCreatedAt());

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(LockoutInterface::STATUS, LockoutInterface::STATUS_ACTIVE)
            ->addFilter(LockoutInterface::ADMIN_USER_ID, $this->adminUserId)
            ->create();
        $list = $this->repository->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $list->getTotalCount());

        $found = false;
        foreach ($list->getItems() as $item) {
            if ($item->getId() === $entityId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Saved lockout should be returned by getList filter');
    }
}
