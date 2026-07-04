<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Cleanup log data interface.
 *
 * Each row records one cleanup run. categories/counts/metadata hold JSON strings
 * and must never contain raw secrets.
 */
interface CleanupLogInterface
{
    public const ENTITY_ID = 'entity_id';
    public const CATEGORIES = 'categories';
    public const COUNTS = 'counts';
    public const STATUS = 'status';
    public const METADATA = 'metadata';
    public const CREATED_AT = 'created_at';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    /**
     * Get cleanup log row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set cleanup log row ID.
     *
     * @param int|null $id
     * @return CleanupLogInterface
     */
    public function setId(?int $id): CleanupLogInterface;

    /**
     * Get cleaned categories (JSON string).
     *
     * @return string|null
     */
    public function getCategories(): ?string;

    /**
     * Set cleaned categories (JSON string).
     *
     * @param string|null $categories
     * @return CleanupLogInterface
     */
    public function setCategories(?string $categories): CleanupLogInterface;

    /**
     * Get per-category counts (JSON string).
     *
     * @return string|null
     */
    public function getCounts(): ?string;

    /**
     * Set per-category counts (JSON string).
     *
     * @param string|null $counts
     * @return CleanupLogInterface
     */
    public function setCounts(?string $counts): CleanupLogInterface;

    /**
     * Get status (success|failed).
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status (success|failed).
     *
     * @param string|null $status
     * @return CleanupLogInterface
     */
    public function setStatus(?string $status): CleanupLogInterface;

    /**
     * Get non-sensitive metadata (JSON string).
     *
     * @return string|null
     */
    public function getMetadata(): ?string;

    /**
     * Set non-sensitive metadata (JSON string).
     *
     * @param string|null $metadata
     * @return CleanupLogInterface
     */
    public function setMetadata(?string $metadata): CleanupLogInterface;

    /**
     * Get created at timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at timestamp.
     *
     * @param string|null $createdAt
     * @return CleanupLogInterface
     */
    public function setCreatedAt(?string $createdAt): CleanupLogInterface;
}
