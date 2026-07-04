<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Model\ResourceModel\Lockout as LockoutResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\Lockout\CollectionFactory as LockoutCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Lockout repository. Business logic must use this contract, never the resource model directly.
 */
class LockoutRepository implements LockoutRepositoryInterface
{
    public function __construct(
        private readonly LockoutResource $resource,
        private readonly LockoutFactory $lockoutFactory,
        private readonly LockoutCollectionFactory $collectionFactory,
        private readonly LockoutSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(LockoutInterface $lockout): LockoutInterface
    {
        if (!$lockout instanceof Lockout) {
            throw new CouldNotSaveException(__('Invalid lockout entity.'));
        }
        try {
            $this->resource->save($lockout);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save lockout: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $lockout;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): LockoutInterface
    {
        $lockout = $this->lockoutFactory->create();
        $this->resource->load($lockout, $entityId);
        if ($lockout->getId() === null) {
            throw new NoSuchEntityException(__('Lockout with id "%1" does not exist.', $entityId));
        }
        return $lockout;
    }

    /**
     * @inheritdoc
     */
    public function findActiveForAdmin(int $adminUserId): ?LockoutInterface
    {
        $searchCriteria = $this->buildActiveCriteria(LockoutInterface::ADMIN_USER_ID, (string) $adminUserId);

        return $this->firstResult($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function findActiveForUsername(string $username): ?LockoutInterface
    {
        $searchCriteria = $this->buildActiveCriteria(LockoutInterface::USERNAME, $username);

        return $this->firstResult($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function findActiveForIp(string $ip): ?LockoutInterface
    {
        $searchCriteria = $this->buildActiveCriteria(LockoutInterface::IP, $ip);

        return $this->firstResult($searchCriteria);
    }

    /**
     * Build search criteria for the most recent active row matching a field/value.
     *
     * @param string $field
     * @param string $value
     * @return SearchCriteriaInterface
     */
    private function buildActiveCriteria(string $field, string $value): SearchCriteriaInterface
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(LockoutInterface::ENTITY_ID)
            ->setDirection('DESC')
            ->create();

        return $this->searchCriteriaBuilder
            ->addFilter($field, $value)
            ->addFilter(LockoutInterface::STATUS, LockoutInterface::STATUS_ACTIVE)
            ->addSortOrder($sortOrder)
            ->setPageSize(1)
            ->create();
    }

    /**
     * Return the first row for a search criteria, or null.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return LockoutInterface|null
     */
    private function firstResult(SearchCriteriaInterface $searchCriteria): ?LockoutInterface
    {
        $items = $this->getList($searchCriteria)->getItems();
        $first = reset($items);

        return $first instanceof LockoutInterface ? $first : null;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LockoutSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var LockoutSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var LockoutInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
