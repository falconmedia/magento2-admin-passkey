<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Emergency recovery-mode state data interface.
 *
 * Each row is an immutable state-change record; the most recent row is the
 * current state. metadata must never store raw secrets.
 */
interface RecoveryStateInterface
{
    public const ENTITY_ID = 'entity_id';
    public const STATE = 'state';
    public const ENABLED_AT = 'enabled_at';
    public const DISABLED_AT = 'disabled_at';
    public const ACTOR_ADMIN_USER_ID = 'actor_admin_user_id';
    public const REASON = 'reason';
    public const METADATA = 'metadata';
    public const UPDATED_AT = 'updated_at';

    public const STATE_ENABLED = 'enabled';
    public const STATE_DISABLED = 'disabled';

    /**
     * Get recovery state row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set recovery state row ID.
     *
     * @param int|null $id
     * @return RecoveryStateInterface
     */
    public function setId(?int $id): RecoveryStateInterface;

    /**
     * Get state (enabled|disabled).
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Set state (enabled|disabled).
     *
     * @param string|null $state
     * @return RecoveryStateInterface
     */
    public function setState(?string $state): RecoveryStateInterface;

    /**
     * Get enabled at timestamp.
     *
     * @return string|null
     */
    public function getEnabledAt(): ?string;

    /**
     * Set enabled at timestamp.
     *
     * @param string|null $enabledAt
     * @return RecoveryStateInterface
     */
    public function setEnabledAt(?string $enabledAt): RecoveryStateInterface;

    /**
     * Get disabled at timestamp.
     *
     * @return string|null
     */
    public function getDisabledAt(): ?string;

    /**
     * Set disabled at timestamp.
     *
     * @param string|null $disabledAt
     * @return RecoveryStateInterface
     */
    public function setDisabledAt(?string $disabledAt): RecoveryStateInterface;

    /**
     * Get actor admin user ID.
     *
     * @return int|null
     */
    public function getActorAdminUserId(): ?int;

    /**
     * Set actor admin user ID.
     *
     * @param int|null $actorAdminUserId
     * @return RecoveryStateInterface
     */
    public function setActorAdminUserId(?int $actorAdminUserId): RecoveryStateInterface;

    /**
     * Get state-change reason.
     *
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * Set state-change reason.
     *
     * @param string|null $reason
     * @return RecoveryStateInterface
     */
    public function setReason(?string $reason): RecoveryStateInterface;

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
     * @return RecoveryStateInterface
     */
    public function setMetadata(?string $metadata): RecoveryStateInterface;

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
     * @return RecoveryStateInterface
     */
    public function setUpdatedAt(?string $updatedAt): RecoveryStateInterface;
}
