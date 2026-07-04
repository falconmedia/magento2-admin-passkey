<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Lockout repository. Admin UI only; not exposed via web API.
 */
interface LockoutRepositoryInterface
{
    /**
     * Save a lockout.
     *
     * @param LockoutInterface $lockout
     * @return LockoutInterface
     * @throws CouldNotSaveException
     */
    public function save(LockoutInterface $lockout): LockoutInterface;

    /**
     * Get lockout by row ID.
     *
     * @param int $entityId
     * @return LockoutInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): LockoutInterface;

    /**
     * Find the most recent active lockout row for an admin user, if any.
     *
     * @param int $adminUserId
     * @return LockoutInterface|null
     */
    public function findActiveForAdmin(int $adminUserId): ?LockoutInterface;

    /**
     * Find the most recent active lockout row for a username, if any.
     *
     * @param string $username
     * @return LockoutInterface|null
     */
    public function findActiveForUsername(string $username): ?LockoutInterface;

    /**
     * Find the most recent active lockout row for a remote IP, if any.
     *
     * @param string $ip
     * @return LockoutInterface|null
     */
    public function findActiveForIp(string $ip): ?LockoutInterface;

    /**
     * Get lockouts matching search criteria (list/filter for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return LockoutSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LockoutSearchResultsInterface;
}
