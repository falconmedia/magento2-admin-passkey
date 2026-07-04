<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Health;

/**
 * Environment and configuration health check service.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface HealthCheckServiceInterface
{
    /**
     * Run all health checks and return the aggregated report.
     *
     * @return HealthReport
     */
    public function run(): HealthReport;
}
