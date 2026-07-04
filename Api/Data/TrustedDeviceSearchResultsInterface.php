<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results interface for trusted devices.
 */
interface TrustedDeviceSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get trusted devices list.
     *
     * @return TrustedDeviceInterface[]
     */
    public function getItems(): array;

    /**
     * Set trusted devices list.
     *
     * @param TrustedDeviceInterface[] $items
     * @return TrustedDeviceSearchResultsInterface
     */
    public function setItems(array $items): TrustedDeviceSearchResultsInterface;
}
