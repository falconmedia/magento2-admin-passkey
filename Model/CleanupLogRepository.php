<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\CleanupLogRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Model\ResourceModel\CleanupLog as CleanupLogResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\CleanupLog\CollectionFactory as CleanupLogCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Cleanup log repository. Business logic must use this contract, never the resource model directly.
 */
class CleanupLogRepository implements CleanupLogRepositoryInterface
{
    public function __construct(
        private readonly CleanupLogResource $resource,
        private readonly CleanupLogFactory $cleanupLogFactory,
        private readonly CleanupLogCollectionFactory $collectionFactory,
        private readonly CleanupLogSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(CleanupLogInterface $cleanupLog): CleanupLogInterface
    {
        if (!$cleanupLog instanceof CleanupLog) {
            throw new CouldNotSaveException(__('Invalid cleanup log entity.'));
        }
        try {
            $this->resource->save($cleanupLog);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save cleanup log: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $cleanupLog;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): CleanupLogInterface
    {
        $cleanupLog = $this->cleanupLogFactory->create();
        $this->resource->load($cleanupLog, $entityId);
        if ($cleanupLog->getId() === null) {
            throw new NoSuchEntityException(__('Cleanup log with id "%1" does not exist.', $entityId));
        }
        return $cleanupLog;
    }

    /**
     * @inheritdoc
     */
    public function getLatest(): ?CleanupLogInterface
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(CleanupLogInterface::ENTITY_ID)
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
    public function getList(SearchCriteriaInterface $searchCriteria): CleanupLogSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var CleanupLogSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
