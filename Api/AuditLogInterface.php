<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Audit log repository contract for security audit events. Admin UI only; not exposed via web API.
 * The higher-level event-recording service is layered on top of this contract in a later step.
 */
interface AuditLogInterface
{
    /**
     * Persist an audit event.
     *
     * @param AuditEventInterface $auditEvent
     * @return AuditEventInterface
     * @throws CouldNotSaveException
     */
    public function save(AuditEventInterface $auditEvent): AuditEventInterface;

    /**
     * Get audit event by row ID.
     *
     * @param int $entityId
     * @return AuditEventInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): AuditEventInterface;

    /**
     * Delete audit events created strictly before the given timestamp.
     *
     * @param string $olderThan Datetime string (UTC)
     * @return int Number of deleted rows
     * @throws CouldNotDeleteException
     */
    public function deleteOlderThan(string $olderThan): int;

    /**
     * Get audit events matching search criteria (list/filter for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return AuditEventSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): AuditEventSearchResultsInterface;
}
