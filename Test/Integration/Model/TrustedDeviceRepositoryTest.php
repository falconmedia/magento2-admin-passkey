<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model;

use FalconMedia\AdminPasskey\Api\TrustedDeviceRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for TrustedDeviceRepository.
 * Uses a real admin user and the repository contract only; no raw SQL and no mocks.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class TrustedDeviceRepositoryTest extends TestCase
{
    private TrustedDeviceRepositoryInterface $repository;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(TrustedDeviceRepositoryInterface::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Device')
            ->setEmail('passkey.device@example.com')
            ->setUsername('passkey_device_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testSaveGetRevokeAndList(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $tokenHash = hash('sha256', 'device-token-' . $this->adminUserId);

        /** @var TrustedDeviceInterface $device */
        $device = $objectManager->create(TrustedDeviceInterface::class);
        $device->setAdminUserId($this->adminUserId)
            ->setDeviceTokenHash($tokenHash)
            ->setLabel('Office Laptop')
            ->setMetadata('{"os":"macOS"}')
            ->setStatus(TrustedDeviceInterface::STATUS_ACTIVE)
            ->setExpiresAt(gmdate('Y-m-d H:i:s', time() + 86400));

        $saved = $this->repository->save($device);
        $entityId = $saved->getId();
        $this->assertNotNull($entityId);
        $this->assertGreaterThan(0, $entityId);

        $loaded = $this->repository->getById($entityId);
        $this->assertSame($tokenHash, $loaded->getDeviceTokenHash());
        $this->assertSame(TrustedDeviceInterface::STATUS_ACTIVE, $loaded->getStatus());
        $this->assertNotEmpty($loaded->getFirstSeenAt());

        $byHash = $this->repository->getByTokenHash($tokenHash);
        $this->assertSame($entityId, $byHash->getId());

        $activeList = $this->repository->listActiveForAdmin($this->adminUserId);
        $this->assertGreaterThanOrEqual(1, $activeList->getTotalCount());

        $revoked = $this->repository->revoke($entityId);
        $this->assertSame(TrustedDeviceInterface::STATUS_REVOKED, $revoked->getStatus());
        $this->assertNotEmpty($revoked->getRevokedAt());

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(TrustedDeviceInterface::ADMIN_USER_ID, $this->adminUserId)
            ->create();
        $list = $this->repository->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $list->getTotalCount());
    }
}
