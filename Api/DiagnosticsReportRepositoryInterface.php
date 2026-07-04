<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Diagnostics report repository. Admin UI only; not exposed via web API.
 */
interface DiagnosticsReportRepositoryInterface
{
    /**
     * Save a diagnostics report.
     *
     * @param DiagnosticsReportInterface $report
     * @return DiagnosticsReportInterface
     * @throws CouldNotSaveException
     */
    public function save(DiagnosticsReportInterface $report): DiagnosticsReportInterface;

    /**
     * Get a diagnostics report by row ID.
     *
     * @param int $entityId
     * @return DiagnosticsReportInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): DiagnosticsReportInterface;

    /**
     * Get a diagnostics report by support reference ID.
     *
     * @param string $supportReferenceId
     * @return DiagnosticsReportInterface
     * @throws NoSuchEntityException
     */
    public function getBySupportReferenceId(string $supportReferenceId): DiagnosticsReportInterface;

    /**
     * Delete a diagnostics report by row ID.
     *
     * @param int $entityId
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $entityId): void;

    /**
     * Get diagnostics reports matching search criteria (list/filter for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return DiagnosticsReportSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): DiagnosticsReportSearchResultsInterface;
}
