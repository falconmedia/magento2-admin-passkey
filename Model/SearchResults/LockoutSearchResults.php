<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for lockouts.
 *
 * Extends the framework container and re-declares the typed item accessors so an
 * instance genuinely satisfies the module's strongly typed repository contract
 * (the generic framework SearchResults only implements the base interface).
 */
class LockoutSearchResults extends SearchResults implements LockoutSearchResultsInterface
{
    /**
     * @return LockoutInterface[]
     */
    public function getItems(): array
    {
        /** @var LockoutInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param LockoutInterface[] $items
     * @return LockoutSearchResultsInterface
     */
    public function setItems(array $items): LockoutSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
