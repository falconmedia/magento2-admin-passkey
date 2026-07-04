<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Api\Data\RecoveryStateSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for recovery states.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class RecoveryStateSearchResults extends SearchResults implements RecoveryStateSearchResultsInterface
{
    /**
     * @return RecoveryStateInterface[]
     */
    public function getItems(): array
    {
        /** @var RecoveryStateInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param RecoveryStateInterface[] $items
     * @return RecoveryStateSearchResultsInterface
     */
    public function setItems(array $items): RecoveryStateSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
