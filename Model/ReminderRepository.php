<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\ReminderInterface;
use FalconMedia\AdminPasskey\Api\Data\ReminderSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\ReminderSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Api\ReminderRepositoryInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\Reminder as ReminderResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\Reminder\CollectionFactory as ReminderCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Reminder repository. Business logic must use this contract, never the resource model directly.
 */
class ReminderRepository implements ReminderRepositoryInterface
{
    public function __construct(
        private readonly ReminderResource $resource,
        private readonly ReminderFactory $reminderFactory,
        private readonly ReminderCollectionFactory $collectionFactory,
        private readonly ReminderSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ReminderInterface $reminder): ReminderInterface
    {
        if (!$reminder instanceof Reminder) {
            throw new CouldNotSaveException(__('Invalid reminder entity.'));
        }
        try {
            $this->resource->save($reminder);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save reminder: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $reminder;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): ReminderInterface
    {
        $reminder = $this->reminderFactory->create();
        $this->resource->load($reminder, $entityId);
        if ($reminder->getId() === null) {
            throw new NoSuchEntityException(__('Reminder with id "%1" does not exist.', $entityId));
        }
        return $reminder;
    }

    /**
     * @inheritdoc
     */
    public function getLatestForAdmin(int $adminUserId, string $reminderType): ?ReminderInterface
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(ReminderInterface::ENTITY_ID)
            ->setDirection('DESC')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ReminderInterface::ADMIN_USER_ID, $adminUserId)
            ->addFilter(ReminderInterface::REMINDER_TYPE, $reminderType)
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
                [ReminderInterface::CREATED_AT . ' < ?' => $olderThan]
            );
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(
                __('Could not delete reminders: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReminderSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var ReminderSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\ReminderInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
