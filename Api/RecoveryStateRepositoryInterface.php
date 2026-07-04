<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Api\Data\RecoveryStateSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Recovery-state repository. Admin UI only; not exposed via web API.
 */
interface RecoveryStateRepositoryInterface
{
    /**
     * Save a recovery-state record.
     *
     * @param RecoveryStateInterface $recoveryState
     * @return RecoveryStateInterface
     * @throws CouldNotSaveException
     */
    public function save(RecoveryStateInterface $recoveryState): RecoveryStateInterface;

    /**
     * Get a recovery-state record by row ID.
     *
     * @param int $entityId
     * @return RecoveryStateInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): RecoveryStateInterface;

    /**
     * Get the most recent recovery-state record (current state), or null when none exists.
     *
     * @return RecoveryStateInterface|null
     */
    public function getCurrent(): ?RecoveryStateInterface;

    /**
     * Get recovery-state records matching search criteria (list/filter for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return RecoveryStateSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RecoveryStateSearchResultsInterface;
}
