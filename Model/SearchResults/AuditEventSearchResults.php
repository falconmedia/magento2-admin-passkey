<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for audit events.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class AuditEventSearchResults extends SearchResults implements AuditEventSearchResultsInterface
{
    /**
     * @return AuditEventInterface[]
     */
    public function getItems(): array
    {
        /** @var AuditEventInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param AuditEventInterface[] $items
     * @return AuditEventSearchResultsInterface
     */
    public function setItems(array $items): AuditEventSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
