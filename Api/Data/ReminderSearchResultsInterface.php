<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for reminders.
 */
interface ReminderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get reminders.
     *
     * @return ReminderInterface[]
     */
    public function getItems(): array;

    /**
     * Set reminders.
     *
     * @param ReminderInterface[] $items
     * @return ReminderSearchResultsInterface
     */
    public function setItems(array $items): ReminderSearchResultsInterface;
}
