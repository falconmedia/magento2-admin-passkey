<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Diagnostics;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Support diagnostics bundle generation and delivery.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface DiagnosticsServiceInterface
{
    /**
     * Generate a diagnostics ZIP bundle and record the report.
     *
     * @param int|null $actorAdminUserId
     * @return DiagnosticsReportInterface
     * @throws LocalizedException
     */
    public function generate(?int $actorAdminUserId = null): DiagnosticsReportInterface;

    /**
     * Email a previously generated report's summary to the configured developer.
     *
     * @param int $reportId
     * @param int|null $actorAdminUserId
     * @return DiagnosticsReportInterface
     * @throws LocalizedException
     */
    public function send(int $reportId, ?int $actorAdminUserId = null): DiagnosticsReportInterface;

    /**
     * Resolve the absolute filesystem path of a report's ZIP bundle for download.
     *
     * @param int $reportId
     * @return string
     * @throws LocalizedException
     */
    public function getReportArchivePath(int $reportId): string;

    /**
     * Resolve the report's ZIP bundle path relative to the var/ directory.
     *
     * @param int $reportId
     * @return string
     * @throws LocalizedException
     */
    public function getReportArchiveRelativePath(int $reportId): string;
}
