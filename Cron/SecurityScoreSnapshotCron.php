<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Cron;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Scheduled security score snapshot so the dashboard can show a trend over time.
 */
class SecurityScoreSnapshotCron
{
    public function __construct(
        private readonly SecurityScoreServiceInterface $securityScoreService,
        private readonly ConfigProvider $configProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Capture a daily snapshot when the security score feature is enabled.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->configProvider->isSecurityScoreEnabled()) {
            return;
        }

        try {
            $this->securityScoreService->snapshot();
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdminPasskey scheduled security score snapshot failed: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
