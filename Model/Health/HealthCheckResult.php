<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Health;

/**
 * Immutable result of a single health check.
 *
 * This is a plain value object (no services), so it is safe to instantiate with
 * `new` from the evaluator and services.
 */
class HealthCheckResult
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_ERROR = 'error';

    public function __construct(
        private readonly string $id,
        private readonly string $label,
        private readonly string $status,
        private readonly string $message
    ) {
    }

    /**
     * Stable machine identifier for the check.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Human-readable check label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Result status (ok|warning|error).
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Human-readable result message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Whether the check passed cleanly.
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /**
     * Array representation for JSON manifests and grids.
     *
     * @return array{id: string, label: string, status: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
