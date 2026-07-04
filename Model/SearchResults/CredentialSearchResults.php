<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for credentials.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract (the generic framework
 * SearchResults only implements the base interface).
 */
class CredentialSearchResults extends SearchResults implements CredentialSearchResultsInterface
{
    /**
     * @return CredentialInterface[]
     */
    public function getItems(): array
    {
        /** @var CredentialInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param CredentialInterface[] $items
     * @return CredentialSearchResultsInterface
     */
    public function setItems(array $items): CredentialSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
