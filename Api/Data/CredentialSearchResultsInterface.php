<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results interface for passkey credentials.
 */
interface CredentialSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get credentials list.
     *
     * @return CredentialInterface[]
     */
    public function getItems(): array;

    /**
     * Set credentials list.
     *
     * @param CredentialInterface[] $items
     * @return CredentialSearchResultsInterface
     */
    public function setItems(array $items): CredentialSearchResultsInterface;
}
