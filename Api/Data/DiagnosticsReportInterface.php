<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Diagnostics report data interface.
 *
 * Each row records one generated support diagnostics bundle. files/counts/metadata
 * hold JSON strings and must never contain raw secrets.
 */
interface DiagnosticsReportInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SUPPORT_REFERENCE_ID = 'support_reference_id';
    public const STATUS = 'status';
    public const FILES = 'files';
    public const COUNTS = 'counts';
    public const METADATA = 'metadata';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    /**
     * Get diagnostics report row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set diagnostics report row ID.
     *
     * @param int|null $id
     * @return DiagnosticsReportInterface
     */
    public function setId(?int $id): DiagnosticsReportInterface;

    /**
     * Get support reference ID.
     *
     * @return string|null
     */
    public function getSupportReferenceId(): ?string;

    /**
     * Set support reference ID.
     *
     * @param string|null $supportReferenceId
     * @return DiagnosticsReportInterface
     */
    public function setSupportReferenceId(?string $supportReferenceId): DiagnosticsReportInterface;

    /**
     * Get status (pending|generated|sent|failed).
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status (pending|generated|sent|failed).
     *
     * @param string|null $status
     * @return DiagnosticsReportInterface
     */
    public function setStatus(?string $status): DiagnosticsReportInterface;

    /**
     * Get generated files manifest (JSON string).
     *
     * @return string|null
     */
    public function getFiles(): ?string;

    /**
     * Set generated files manifest (JSON string).
     *
     * @param string|null $files
     * @return DiagnosticsReportInterface
     */
    public function setFiles(?string $files): DiagnosticsReportInterface;

    /**
     * Get report counts (JSON string).
     *
     * @return string|null
     */
    public function getCounts(): ?string;

    /**
     * Set report counts (JSON string).
     *
     * @param string|null $counts
     * @return DiagnosticsReportInterface
     */
    public function setCounts(?string $counts): DiagnosticsReportInterface;

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
     * @return DiagnosticsReportInterface
     */
    public function setMetadata(?string $metadata): DiagnosticsReportInterface;

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
     * @return DiagnosticsReportInterface
     */
    public function setCreatedAt(?string $createdAt): DiagnosticsReportInterface;

    /**
     * Get updated at timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at timestamp.
     *
     * @param string|null $updatedAt
     * @return DiagnosticsReportInterface
     */
    public function setUpdatedAt(?string $updatedAt): DiagnosticsReportInterface;
}
