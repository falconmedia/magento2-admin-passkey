<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\SecurityScoreSnapshot as SecurityScoreSnapshotResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Security score snapshot entity.
 */
class SecurityScoreSnapshot extends AbstractModel implements SecurityScoreSnapshotInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(SecurityScoreSnapshotResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setId($id): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getScore(): ?int
    {
        $value = $this->getData(self::SCORE);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setScore(?int $score): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::SCORE, $score);
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): ?string
    {
        $value = $this->getData(self::LABEL);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setLabel(?string $label): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getCategoryBreakdown(): ?string
    {
        $value = $this->getData(self::CATEGORY_BREAKDOWN);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCategoryBreakdown(?string $categoryBreakdown): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::CATEGORY_BREAKDOWN, $categoryBreakdown);
    }

    /**
     * @inheritdoc
     */
    public function getRecommendations(): ?string
    {
        $value = $this->getData(self::RECOMMENDATIONS);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setRecommendations(?string $recommendations): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::RECOMMENDATIONS, $recommendations);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): ?string
    {
        $value = $this->getData(self::METADATA);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setMetadata(?string $metadata): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::METADATA, $metadata);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): SecurityScoreSnapshotInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
