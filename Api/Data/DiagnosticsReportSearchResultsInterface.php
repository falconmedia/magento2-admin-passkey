<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for diagnostics reports.
 */
interface DiagnosticsReportSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get diagnostics reports.
     *
     * @return DiagnosticsReportInterface[]
     */
    public function getItems(): array;

    /**
     * Set diagnostics reports.
     *
     * @param DiagnosticsReportInterface[] $items
     * @return DiagnosticsReportSearchResultsInterface
     */
    public function setItems(array $items): DiagnosticsReportSearchResultsInterface;
}
