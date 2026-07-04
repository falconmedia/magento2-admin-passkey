<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Api\Data\RecoveryStateSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\RecoveryStateSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Api\RecoveryStateRepositoryInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\RecoveryState as RecoveryStateResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\RecoveryState\CollectionFactory as RecoveryStateCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Recovery-state repository. Business logic must use this contract, never the resource model directly.
 */
class RecoveryStateRepository implements RecoveryStateRepositoryInterface
{
    public function __construct(
        private readonly RecoveryStateResource $resource,
        private readonly RecoveryStateFactory $recoveryStateFactory,
        private readonly RecoveryStateCollectionFactory $collectionFactory,
        private readonly RecoveryStateSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(RecoveryStateInterface $recoveryState): RecoveryStateInterface
    {
        if (!$recoveryState instanceof RecoveryState) {
            throw new CouldNotSaveException(__('Invalid recovery state entity.'));
        }
        try {
            $this->resource->save($recoveryState);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save recovery state: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $recoveryState;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): RecoveryStateInterface
    {
        $recoveryState = $this->recoveryStateFactory->create();
        $this->resource->load($recoveryState, $entityId);
        if ($recoveryState->getId() === null) {
            throw new NoSuchEntityException(__('Recovery state with id "%1" does not exist.', $entityId));
        }
        return $recoveryState;
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?RecoveryStateInterface
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(RecoveryStateInterface::ENTITY_ID)
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
    public function getList(SearchCriteriaInterface $searchCriteria): RecoveryStateSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var RecoveryStateSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
