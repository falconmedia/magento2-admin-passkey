<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\Challenge as ChallengeResource;
use Magento\Framework\Model\AbstractModel;

/**
 * WebAuthn challenge entity.
 */
class Challenge extends AbstractModel implements ChallengeInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ChallengeResource::class);
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
    public function setId($id): ChallengeInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getAdminUserId(): ?int
    {
        $value = $this->getData(self::ADMIN_USER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setAdminUserId(?int $adminUserId): ChallengeInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getChallenge(): ?string
    {
        $value = $this->getData(self::CHALLENGE);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setChallenge(?string $challenge): ChallengeInterface
    {
        return $this->setData(self::CHALLENGE, $challenge);
    }

    /**
     * @inheritdoc
     */
    public function getChallengeType(): ?string
    {
        $value = $this->getData(self::CHALLENGE_TYPE);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setChallengeType(?string $challengeType): ChallengeInterface
    {
        return $this->setData(self::CHALLENGE_TYPE, $challengeType);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        $value = $this->getData(self::STATUS);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status): ChallengeInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getRemoteIp(): ?string
    {
        $value = $this->getData(self::REMOTE_IP);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setRemoteIp(?string $remoteIp): ChallengeInterface
    {
        return $this->setData(self::REMOTE_IP, $remoteIp);
    }

    /**
     * @inheritdoc
     */
    public function getExpiresAt(): ?string
    {
        $value = $this->getData(self::EXPIRES_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setExpiresAt(?string $expiresAt): ChallengeInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    /**
     * @inheritdoc
     */
    public function getConsumedAt(): ?string
    {
        $value = $this->getData(self::CONSUMED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setConsumedAt(?string $consumedAt): ChallengeInterface
    {
        return $this->setData(self::CONSUMED_AT, $consumedAt);
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
    public function setCreatedAt(?string $createdAt): ChallengeInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
