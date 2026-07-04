<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Model\ResourceModel\AuditEvent as AuditEventResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\AuditEvent\CollectionFactory as AuditEventCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Audit log repository. Business logic must use this contract, never the resource model directly.
 */
class AuditLog implements AuditLogInterface
{
    public function __construct(
        private readonly AuditEventResource $resource,
        private readonly AuditEventFactory $auditEventFactory,
        private readonly AuditEventCollectionFactory $collectionFactory,
        private readonly AuditEventSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(AuditEventInterface $auditEvent): AuditEventInterface
    {
        if (!$auditEvent instanceof AuditEvent) {
            throw new CouldNotSaveException(__('Invalid audit event entity.'));
        }
        try {
            $this->resource->save($auditEvent);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save audit event: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $auditEvent;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): AuditEventInterface
    {
        $auditEvent = $this->auditEventFactory->create();
        $this->resource->load($auditEvent, $entityId);
        if ($auditEvent->getId() === null) {
            throw new NoSuchEntityException(__('Audit event with id "%1" does not exist.', $entityId));
        }
        return $auditEvent;
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
                [AuditEventInterface::CREATED_AT . ' < ?' => $olderThan]
            );
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(
                __('Could not delete audit events: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): AuditEventSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var AuditEventSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\AuditEventInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
