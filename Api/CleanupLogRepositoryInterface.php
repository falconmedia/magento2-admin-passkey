<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Cleanup log repository. Admin UI only; not exposed via web API.
 */
interface CleanupLogRepositoryInterface
{
    /**
     * Save a cleanup log.
     *
     * @param CleanupLogInterface $cleanupLog
     * @return CleanupLogInterface
     * @throws CouldNotSaveException
     */
    public function save(CleanupLogInterface $cleanupLog): CleanupLogInterface;

    /**
     * Get a cleanup log by row ID.
     *
     * @param int $entityId
     * @return CleanupLogInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): CleanupLogInterface;

    /**
     * Get the most recent cleanup log, or null when none exists.
     *
     * @return CleanupLogInterface|null
     */
    public function getLatest(): ?CleanupLogInterface;

    /**
     * Get cleanup logs matching search criteria (list/history for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CleanupLogSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CleanupLogSearchResultsInterface;
}
