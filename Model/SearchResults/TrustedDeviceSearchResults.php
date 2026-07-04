<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for trusted devices.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class TrustedDeviceSearchResults extends SearchResults implements TrustedDeviceSearchResultsInterface
{
    /**
     * @return TrustedDeviceInterface[]
     */
    public function getItems(): array
    {
        /** @var TrustedDeviceInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param TrustedDeviceInterface[] $items
     * @return TrustedDeviceSearchResultsInterface
     */
    public function setItems(array $items): TrustedDeviceSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
