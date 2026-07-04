<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console;

/**
 * Immutable, normalised set of audit-export filters.
 *
 * Plain value object; safe to instantiate with `new`.
 *
 * @internal Console support; not part of a public web API contract.
 */
class AuditExportFilter
{
    /**
     * @param string|null $from Inclusive lower bound (UTC "Y-m-d H:i:s") or null.
     * @param string|null $to Inclusive upper bound (UTC "Y-m-d H:i:s") or null.
     * @param string|null $type Event-type code filter, or null for all types.
     */
    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?string $type
    ) {
    }

    /**
     * Inclusive lower bound (UTC "Y-m-d H:i:s"), or null.
     *
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Inclusive upper bound (UTC "Y-m-d H:i:s"), or null.
     *
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * Event-type code filter, or null for all types.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Whether any filter is set.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->from === null && $this->to === null && $this->type === null;
    }
}
