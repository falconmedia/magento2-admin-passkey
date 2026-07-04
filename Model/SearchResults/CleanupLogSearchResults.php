<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for cleanup-log entries.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class CleanupLogSearchResults extends SearchResults implements CleanupLogSearchResultsInterface
{
    /**
     * @return CleanupLogInterface[]
     */
    public function getItems(): array
    {
        /** @var CleanupLogInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param CleanupLogInterface[] $items
     * @return CleanupLogSearchResultsInterface
     */
    public function setItems(array $items): CleanupLogSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
