<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Dashboard;

/**
 * Collects the raw metrics rendered by the admin dashboard widget.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface DashboardMetricsProviderInterface
{
    /**
     * Gather the current dashboard metrics from the module services.
     *
     * Individual data sources are read defensively so a single failing source
     * degrades gracefully instead of breaking the whole dashboard.
     *
     * @return DashboardMetrics
     */
    public function getMetrics(): DashboardMetrics;
}
