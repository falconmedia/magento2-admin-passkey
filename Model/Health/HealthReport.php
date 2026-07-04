<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Health;

/**
 * Immutable aggregate of health check results.
 *
 * Plain value object; safe to instantiate with `new`.
 */
class HealthReport
{
    /**
     * @param HealthCheckResult[] $results
     */
    public function __construct(
        private readonly array $results
    ) {
    }

    /**
     * All check results.
     *
     * @return HealthCheckResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Count results with the given status.
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        $count = 0;
        foreach ($this->results as $result) {
            if ($result->getStatus() === $status) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Number of failing checks.
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->countByStatus(HealthCheckResult::STATUS_ERROR);
    }

    /**
     * Number of warning checks.
     *
     * @return int
     */
    public function getWarningCount(): int
    {
        return $this->countByStatus(HealthCheckResult::STATUS_WARNING);
    }

    /**
     * Whether any check failed.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->getErrorCount() > 0;
    }

    /**
     * Whether any check produced a warning.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return $this->getWarningCount() > 0;
    }

    /**
     * Overall status: error if any error, warning if any warning, otherwise ok.
     *
     * @return string
     */
    public function getOverallStatus(): string
    {
        if ($this->hasErrors()) {
            return HealthCheckResult::STATUS_ERROR;
        }
        if ($this->hasWarnings()) {
            return HealthCheckResult::STATUS_WARNING;
        }

        return HealthCheckResult::STATUS_OK;
    }

    /**
     * Array representation for JSON manifests.
     *
     * @return array<int, array{id: string, label: string, status: string, message: string}>
     */
    public function toArray(): array
    {
        $items = [];
        foreach ($this->results as $result) {
            $items[] = $result->toArray();
        }

        return $items;
    }
}
