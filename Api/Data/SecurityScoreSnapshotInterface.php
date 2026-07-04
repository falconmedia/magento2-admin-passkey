<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Security score snapshot data interface.
 *
 * Each row is an immutable point-in-time score. category_breakdown/recommendations/
 * metadata hold JSON strings and must never contain raw secrets.
 */
interface SecurityScoreSnapshotInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SCORE = 'score';
    public const LABEL = 'label';
    public const CATEGORY_BREAKDOWN = 'category_breakdown';
    public const RECOMMENDATIONS = 'recommendations';
    public const METADATA = 'metadata';
    public const CREATED_AT = 'created_at';

    /**
     * Get score snapshot row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set score snapshot row ID.
     *
     * @param int|null $id
     * @return SecurityScoreSnapshotInterface
     */
    public function setId(?int $id): SecurityScoreSnapshotInterface;

    /**
     * Get score (0-100).
     *
     * @return int|null
     */
    public function getScore(): ?int;

    /**
     * Set score (0-100).
     *
     * @param int|null $score
     * @return SecurityScoreSnapshotInterface
     */
    public function setScore(?int $score): SecurityScoreSnapshotInterface;

    /**
     * Get score label.
     *
     * @return string|null
     */
    public function getLabel(): ?string;

    /**
     * Set score label.
     *
     * @param string|null $label
     * @return SecurityScoreSnapshotInterface
     */
    public function setLabel(?string $label): SecurityScoreSnapshotInterface;

    /**
     * Get per-category breakdown (JSON string).
     *
     * @return string|null
     */
    public function getCategoryBreakdown(): ?string;

    /**
     * Set per-category breakdown (JSON string).
     *
     * @param string|null $categoryBreakdown
     * @return SecurityScoreSnapshotInterface
     */
    public function setCategoryBreakdown(?string $categoryBreakdown): SecurityScoreSnapshotInterface;

    /**
     * Get recommendations (JSON string).
     *
     * @return string|null
     */
    public function getRecommendations(): ?string;

    /**
     * Set recommendations (JSON string).
     *
     * @param string|null $recommendations
     * @return SecurityScoreSnapshotInterface
     */
    public function setRecommendations(?string $recommendations): SecurityScoreSnapshotInterface;

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
     * @return SecurityScoreSnapshotInterface
     */
    public function setMetadata(?string $metadata): SecurityScoreSnapshotInterface;

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
     * @return SecurityScoreSnapshotInterface
     */
    public function setCreatedAt(?string $createdAt): SecurityScoreSnapshotInterface;
}
