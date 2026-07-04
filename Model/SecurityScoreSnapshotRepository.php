<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface;
use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Api\SecurityScoreSnapshotRepositoryInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\SecurityScoreSnapshot as SecurityScoreSnapshotResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\SecurityScoreSnapshot\CollectionFactory as SnapshotCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Security score snapshot repository. Business logic must use this contract, never the resource model directly.
 */
class SecurityScoreSnapshotRepository implements SecurityScoreSnapshotRepositoryInterface
{
    public function __construct(
        private readonly SecurityScoreSnapshotResource $resource,
        private readonly SecurityScoreSnapshotFactory $snapshotFactory,
        private readonly SnapshotCollectionFactory $collectionFactory,
        private readonly SecurityScoreSnapshotSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(SecurityScoreSnapshotInterface $snapshot): SecurityScoreSnapshotInterface
    {
        if (!$snapshot instanceof SecurityScoreSnapshot) {
            throw new CouldNotSaveException(__('Invalid security score snapshot entity.'));
        }
        try {
            $this->resource->save($snapshot);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save security score snapshot: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $snapshot;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): SecurityScoreSnapshotInterface
    {
        $snapshot = $this->snapshotFactory->create();
        $this->resource->load($snapshot, $entityId);
        if ($snapshot->getId() === null) {
            throw new NoSuchEntityException(__('Security score snapshot with id "%1" does not exist.', $entityId));
        }
        return $snapshot;
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?SecurityScoreSnapshotInterface
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(SecurityScoreSnapshotInterface::ENTITY_ID)
            ->setDirection('DESC')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->setPageSize(1)
            ->create();

        $items = $this->getList($searchCriteria)->getItems();

        return $items === [] ? null : reset($items);
    }

    /**
     * @inheritdoc
     */
    public function deleteOlderThan(string $olderThan): int
    {
        try {
            $connection = $this->resource->getConnection();
            return (int) $connection->delete(
                $this->resource->getMainTable(),
                [SecurityScoreSnapshotInterface::CREATED_AT . ' < ?' => $olderThan]
            );
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(
                __('Could not delete security score snapshots: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SecurityScoreSnapshotSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SecurityScoreSnapshotSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
