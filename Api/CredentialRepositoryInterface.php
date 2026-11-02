<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Passkey credential repository. Admin UI only; not exposed via web API.
 */
interface CredentialRepositoryInterface
{
    /**
     * Save a credential.
     *
     * @param CredentialInterface $credential
     * @return CredentialInterface
     * @throws CouldNotSaveException
     */
    public function save(CredentialInterface $credential): CredentialInterface;

    /**
     * Get credential by row ID.
     *
     * @param int $entityId
     * @return CredentialInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): CredentialInterface;

    /**
     * Get credential by WebAuthn credential ID.
     *
     * @param string $credentialId
     * @return CredentialInterface
     * @throws NoSuchEntityException
     */
    public function getByCredentialId(string $credentialId): CredentialInterface;

    /**
     * List active credentials for an admin user.
     *
     * @param int $adminUserId
     * @return CredentialSearchResultsInterface
     */
    public function listActiveForAdmin(int $adminUserId): CredentialSearchResultsInterface;

    /**
     * Atomically store a new sign count and last-used timestamp.
     *
     * The row is only updated when the stored counter is lower than the new one,
     * or when both are zero (authenticators that do not implement a counter), so a
     * replayed or concurrently submitted assertion cannot advance the counter twice.
     *
     * @param int $entityId Credential row ID.
     * @param int $newSignCount Counter reported by the authenticator.
     * @param string $lastUsedAt UTC datetime (Y-m-d H:i:s).
     * @return bool True when exactly one row was updated.
     * @throws CouldNotSaveException
     */
    public function updateSignCount(int $entityId, int $newSignCount, string $lastUsedAt): bool;

    /**
     * Revoke a credential by row ID.
     *
     * @param int $entityId
     * @return CredentialInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function revoke(int $entityId): CredentialInterface;

    /**
     * Get credentials matching search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CredentialSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CredentialSearchResultsInterface;
}
