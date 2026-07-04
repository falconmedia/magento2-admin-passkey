<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Fail2Ban;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Filesystem-backed Fail2Ban logger.
 *
 * Only writes when Fail2Ban logging is enabled and the configured path is a safe,
 * non-traversing path under the Magento root. All errors are swallowed: a logging
 * failure must never interfere with the login flow.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Fail2BanLogger implements Fail2BanLoggerInterface
{
    /**
     * Directory permissions used when creating the log directory.
     */
    private const DIR_PERMISSIONS = 0750;

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly Fail2BanLineFormatter $formatter,
        private readonly DirectoryList $directoryList,
        private readonly File $file,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function log(
        string $eventType,
        ?string $ip,
        ?string $username,
        ?int $adminUserId,
        ?string $method
    ): void {
        try {
            if (!$this->configProvider->isFail2BanEnabled()) {
                return;
            }

            $absolutePath = $this->resolveSafePath($this->configProvider->getFail2BanLogPath());
            if ($absolutePath === null) {
                return;
            }

            $line = $this->formatter->format(
                $this->dateTime->gmtDate(),
                $eventType,
                $ip,
                $username,
                $adminUserId,
                $method
            ) . "\n";

            $directory = dirname($absolutePath);
            if (!$this->file->isExists($directory)) {
                $this->file->createDirectory($directory, self::DIR_PERMISSIONS);
            }

            $stream = $this->file->fileOpen($absolutePath, 'a');
            $this->file->fileLock($stream);
            try {
                $this->file->fileWrite($stream, $line);
            } finally {
                $this->file->fileUnlock($stream);
                $this->file->fileClose($stream);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('AdminPasskey Fail2Ban logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve a configured relative path to a safe absolute path under the Magento root.
     *
     * Rejects empty values, null bytes and any parent-directory traversal so a
     * misconfiguration can never write outside the Magento installation.
     *
     * @param string $configuredPath
     * @return string|null Absolute path, or null when the path is unsafe/empty.
     */
    private function resolveSafePath(string $configuredPath): ?string
    {
        $relative = ltrim(trim($configuredPath), '/');
        if ($relative === '') {
            return null;
        }

        if (str_contains($relative, "\0") || str_contains($relative, '..')) {
            $this->logger->warning('AdminPasskey Fail2Ban log path rejected as unsafe.');

            return null;
        }

        $root = rtrim($this->directoryList->getRoot(), '/');

        return $root . '/' . $relative;
    }
}
