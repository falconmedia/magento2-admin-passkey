<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\TrustedDeviceRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Model\ResourceModel\TrustedDevice as TrustedDeviceResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\TrustedDevice\CollectionFactory as TrustedDeviceCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Trusted device repository. Business logic must use this contract, never the resource model directly.
 */
class TrustedDeviceRepository implements TrustedDeviceRepositoryInterface
{
    public function __construct(
        private readonly TrustedDeviceResource $resource,
        private readonly TrustedDeviceFactory $trustedDeviceFactory,
        private readonly TrustedDeviceCollectionFactory $collectionFactory,
        private readonly TrustedDeviceSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(TrustedDeviceInterface $trustedDevice): TrustedDeviceInterface
    {
        if (!$trustedDevice instanceof TrustedDevice) {
            throw new CouldNotSaveException(__('Invalid trusted device entity.'));
        }
        try {
            $this->resource->save($trustedDevice);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save trusted device: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $trustedDevice;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): TrustedDeviceInterface
    {
        $trustedDevice = $this->trustedDeviceFactory->create();
        $this->resource->load($trustedDevice, $entityId);
        if ($trustedDevice->getId() === null) {
            throw new NoSuchEntityException(__('Trusted device with id "%1" does not exist.', $entityId));
        }
        return $trustedDevice;
    }

    /**
     * @inheritdoc
     */
    public function getByTokenHash(string $deviceTokenHash): TrustedDeviceInterface
    {
        $trustedDevice = $this->trustedDeviceFactory->create();
        $this->resource->load($trustedDevice, $deviceTokenHash, TrustedDeviceInterface::DEVICE_TOKEN_HASH);
        if ($trustedDevice->getId() === null) {
            throw new NoSuchEntityException(
                __('Trusted device with token hash "%1" does not exist.', $deviceTokenHash)
            );
        }
        return $trustedDevice;
    }

    /**
     * @inheritdoc
     */
    public function listActiveForAdmin(int $adminUserId): TrustedDeviceSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(TrustedDeviceInterface::ADMIN_USER_ID, $adminUserId)
            ->addFilter(TrustedDeviceInterface::STATUS, TrustedDeviceInterface::STATUS_ACTIVE)
            ->create();

        return $this->getList($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function revoke(int $entityId): TrustedDeviceInterface
    {
        $trustedDevice = $this->getById($entityId);
        $trustedDevice->setStatus(TrustedDeviceInterface::STATUS_REVOKED);
        $trustedDevice->setRevokedAt($this->dateTime->gmtDate());

        return $this->save($trustedDevice);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TrustedDeviceSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var TrustedDeviceSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
