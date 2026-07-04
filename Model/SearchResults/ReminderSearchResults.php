<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\ReminderInterface;
use FalconMedia\AdminPasskey\Api\Data\ReminderSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for reminders.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class ReminderSearchResults extends SearchResults implements ReminderSearchResultsInterface
{
    /**
     * @return ReminderInterface[]
     */
    public function getItems(): array
    {
        /** @var ReminderInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param ReminderInterface[] $items
     * @return ReminderSearchResultsInterface
     */
    public function setItems(array $items): ReminderSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
