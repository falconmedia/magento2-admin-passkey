<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface;
use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Security score snapshot repository. Admin UI only; not exposed via web API.
 */
interface SecurityScoreSnapshotRepositoryInterface
{
    /**
     * Save a security score snapshot.
     *
     * @param SecurityScoreSnapshotInterface $snapshot
     * @return SecurityScoreSnapshotInterface
     * @throws CouldNotSaveException
     */
    public function save(SecurityScoreSnapshotInterface $snapshot): SecurityScoreSnapshotInterface;

    /**
     * Get a security score snapshot by row ID.
     *
     * @param int $entityId
     * @return SecurityScoreSnapshotInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): SecurityScoreSnapshotInterface;

    /**
     * Get the most recent snapshot (current score), or null when none exists.
     *
     * @return SecurityScoreSnapshotInterface|null
     */
    public function getCurrent(): ?SecurityScoreSnapshotInterface;

    /**
     * Delete snapshots created strictly before the given timestamp.
     *
     * @param string $olderThan Datetime string (UTC)
     * @return int Number of deleted rows
     * @throws CouldNotDeleteException
     */
    public function deleteOlderThan(string $olderThan): int;

    /**
     * Get snapshots matching search criteria (list/history for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SecurityScoreSnapshotSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SecurityScoreSnapshotSearchResultsInterface;
}
