<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results interface for audit events.
 */
interface AuditEventSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get audit events list.
     *
     * @return AuditEventInterface[]
     */
    public function getItems(): array;

    /**
     * Set audit events list.
     *
     * @param AuditEventInterface[] $items
     * @return AuditEventSearchResultsInterface
     */
    public function setItems(array $items): AuditEventSearchResultsInterface;
}
