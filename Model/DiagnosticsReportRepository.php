<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Api\DiagnosticsReportRepositoryInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\DiagnosticsReport as DiagnosticsReportResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\DiagnosticsReport\CollectionFactory as DiagnosticsReportCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Diagnostics report repository. Business logic must use this contract, never the resource model directly.
 */
class DiagnosticsReportRepository implements DiagnosticsReportRepositoryInterface
{
    public function __construct(
        private readonly DiagnosticsReportResource $resource,
        private readonly DiagnosticsReportFactory $reportFactory,
        private readonly DiagnosticsReportCollectionFactory $collectionFactory,
        private readonly DiagnosticsReportSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(DiagnosticsReportInterface $report): DiagnosticsReportInterface
    {
        if (!$report instanceof DiagnosticsReport) {
            throw new CouldNotSaveException(__('Invalid diagnostics report entity.'));
        }
        try {
            $this->resource->save($report);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save diagnostics report: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $report;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): DiagnosticsReportInterface
    {
        $report = $this->reportFactory->create();
        $this->resource->load($report, $entityId);
        if ($report->getId() === null) {
            throw new NoSuchEntityException(__('Diagnostics report with id "%1" does not exist.', $entityId));
        }
        return $report;
    }

    /**
     * @inheritdoc
     */
    public function getBySupportReferenceId(string $supportReferenceId): DiagnosticsReportInterface
    {
        $report = $this->reportFactory->create();
        $this->resource->load($report, $supportReferenceId, DiagnosticsReportInterface::SUPPORT_REFERENCE_ID);
        if ($report->getId() === null) {
            throw new NoSuchEntityException(
                __('Diagnostics report with reference "%1" does not exist.', $supportReferenceId)
            );
        }
        return $report;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $entityId): void
    {
        $report = $this->getById($entityId);
        if (!$report instanceof DiagnosticsReport) {
            throw new CouldNotDeleteException(__('Invalid diagnostics report entity.'));
        }
        try {
            $this->resource->delete($report);
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(
                __('Could not delete diagnostics report: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): DiagnosticsReportSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var DiagnosticsReportSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
