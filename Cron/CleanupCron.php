<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Cron;

use FalconMedia\AdminPasskey\Model\Cleanup\CleanupServiceInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Psr\Log\LoggerInterface;

/**
 * Scheduled retention cleanup for expired module data.
 */
class CleanupCron
{
    public function __construct(
        private readonly CleanupServiceInterface $cleanupService,
        private readonly ConfigProvider $configProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute the cleanup run when the feature is enabled.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->configProvider->isCleanupEnabled()) {
            return;
        }

        try {
            $this->cleanupService->run();
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey scheduled cleanup failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
