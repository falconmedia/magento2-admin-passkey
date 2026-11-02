<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * WebAuthn challenge repository. Admin UI only; not exposed via web API.
 */
interface ChallengeRepositoryInterface
{
    /**
     * Save a challenge.
     *
     * @param ChallengeInterface $challenge
     * @return ChallengeInterface
     * @throws CouldNotSaveException
     */
    public function save(ChallengeInterface $challenge): ChallengeInterface;

    /**
     * Get challenge by row ID.
     *
     * @param int $entityId
     * @return ChallengeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): ChallengeInterface;

    /**
     * Atomically consume a pending challenge (mark consumed) by row ID.
     *
     * Implemented as a conditional UPDATE (status pending -> consumed), so when
     * two requests race on the same challenge only one of them can win; the loser
     * gets a CouldNotSaveException.
     *
     * @param int $entityId
     * @return ChallengeInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException When the challenge is not (or no longer) pending.
     */
    public function consume(int $entityId): ChallengeInterface;

    /**
     * Delete challenges that expired before the given timestamp.
     *
     * @param string|null $olderThan Datetime string (defaults to now)
     * @return int Number of deleted rows
     * @throws CouldNotDeleteException
     */
    public function deleteExpired(?string $olderThan = null): int;

    /**
     * Get challenges matching search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return ChallengeSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ChallengeSearchResultsInterface;
}
