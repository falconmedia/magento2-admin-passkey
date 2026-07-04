<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Trusted device repository. Admin UI only; not exposed via web API.
 */
interface TrustedDeviceRepositoryInterface
{
    /**
     * Save a trusted device.
     *
     * @param TrustedDeviceInterface $trustedDevice
     * @return TrustedDeviceInterface
     * @throws CouldNotSaveException
     */
    public function save(TrustedDeviceInterface $trustedDevice): TrustedDeviceInterface;

    /**
     * Get trusted device by row ID.
     *
     * @param int $entityId
     * @return TrustedDeviceInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): TrustedDeviceInterface;

    /**
     * Get trusted device by device token hash.
     *
     * @param string $deviceTokenHash
     * @return TrustedDeviceInterface
     * @throws NoSuchEntityException
     */
    public function getByTokenHash(string $deviceTokenHash): TrustedDeviceInterface;

    /**
     * List active trusted devices for an admin user.
     *
     * @param int $adminUserId
     * @return TrustedDeviceSearchResultsInterface
     */
    public function listActiveForAdmin(int $adminUserId): TrustedDeviceSearchResultsInterface;

    /**
     * Revoke a trusted device by row ID.
     *
     * @param int $entityId
     * @return TrustedDeviceInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function revoke(int $entityId): TrustedDeviceInterface;

    /**
     * Get trusted devices matching search criteria (list/filter for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return TrustedDeviceSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TrustedDeviceSearchResultsInterface;
}
