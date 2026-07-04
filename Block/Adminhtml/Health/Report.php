<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\Health;

use FalconMedia\AdminPasskey\Model\Health\HealthReport;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckServiceInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Renders the passkey health check report on the admin health page.
 */
class Report extends Template
{
    /**
     * @var HealthReport|null
     */
    private ?HealthReport $report = null;

    public function __construct(
        Context $context,
        private readonly HealthCheckServiceInterface $healthCheckService,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Run (once) and return the health report.
     *
     * @return HealthReport
     */
    public function getReport(): HealthReport
    {
        if ($this->report === null) {
            $this->report = $this->healthCheckService->run();
        }

        return $this->report;
    }

    /**
     * Map a check status to a Tailwind/Blank badge CSS class.
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass(string $status): string
    {
        return match ($status) {
            'ok' => 'grid-severity-notice',
            'warning' => 'grid-severity-minor',
            'error' => 'grid-severity-critical',
            default => 'grid-severity-notice',
        };
    }
}
