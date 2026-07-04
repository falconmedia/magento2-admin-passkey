<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\ReminderInterface;
use FalconMedia\AdminPasskey\Api\Data\ReminderSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Reminder repository. Admin UI only; not exposed via web API.
 */
interface ReminderRepositoryInterface
{
    /**
     * Save a reminder.
     *
     * @param ReminderInterface $reminder
     * @return ReminderInterface
     * @throws CouldNotSaveException
     */
    public function save(ReminderInterface $reminder): ReminderInterface;

    /**
     * Get a reminder by row ID.
     *
     * @param int $entityId
     * @return ReminderInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): ReminderInterface;

    /**
     * Get the most recent reminder of a type for an admin user, or null when none exists.
     *
     * @param int $adminUserId
     * @param string $reminderType
     * @return ReminderInterface|null
     */
    public function getLatestForAdmin(int $adminUserId, string $reminderType): ?ReminderInterface;

    /**
     * Delete reminders created strictly before the given timestamp.
     *
     * @param string $olderThan Datetime string (UTC)
     * @return int Number of deleted rows
     * @throws CouldNotDeleteException
     */
    public function deleteOlderThan(string $olderThan): int;

    /**
     * Get reminders matching search criteria (list/history for admin grids).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return ReminderSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReminderSearchResultsInterface;
}
