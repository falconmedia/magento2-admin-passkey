<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for diagnostics reports.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class DiagnosticsReportSearchResults extends SearchResults implements DiagnosticsReportSearchResultsInterface
{
    /**
     * @return DiagnosticsReportInterface[]
     */
    public function getItems(): array
    {
        /** @var DiagnosticsReportInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param DiagnosticsReportInterface[] $items
     * @return DiagnosticsReportSearchResultsInterface
     */
    public function setItems(array $items): DiagnosticsReportSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
