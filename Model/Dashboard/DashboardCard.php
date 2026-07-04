<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Dashboard;

/**
 * Immutable representation of a single admin dashboard widget card.
 *
 * Plain value object; safe to instantiate with `new`. The card carries only
 * already-resolved, display-ready data plus optional action routes; the block
 * turns routes into URLs and the template formats the values.
 */
class DashboardCard
{
    public const STATUS_INFO = 'info';
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';
    public const STATUS_NEUTRAL = 'neutral';

    /**
     * @param string $id Card identifier (matches the config toggle key).
     * @param string $title Display title.
     * @param string $status One of the STATUS_* constants.
     * @param string|null $value Primary metric/value text, when the card has one.
     * @param string $description Supporting description text.
     * @param array<int, array{label: string, route: string}> $links Action links (route paths).
     */
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $status = self::STATUS_INFO,
        private readonly ?string $value = null,
        private readonly string $description = '',
        private readonly array $links = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<int, array{label: string, route: string}>
     */
    public function getLinks(): array
    {
        return $this->links;
    }
}
