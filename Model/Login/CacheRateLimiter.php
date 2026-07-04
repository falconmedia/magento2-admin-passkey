<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Login;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Cache-backed fixed-window rate limiter.
 *
 * Deliberately fail-open: any cache error is logged and treated as "not limited"
 * so a cache outage can never lock every administrator out of the login page.
 * The default window/ceiling are conservative and cover the pre-auth options and
 * verify endpoints; Step 13 can replace the binding with a durable lockout store.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class CacheRateLimiter implements RateLimiterInterface
{
    /**
     * Cache key prefix for rate-limit counters.
     */
    private const CACHE_PREFIX = 'falconmedia_adminpasskey_ratelimit_';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly int $maxAttempts = 30,
        private readonly int $windowSeconds = 60
    ) {
    }

    /**
     * @inheritdoc
     */
    public function isLimited(string $key): bool
    {
        try {
            return $this->currentCount($key) >= $this->maxAttempts;
        } catch (\Throwable $e) {
            $this->logger->warning('AdminPasskey rate limiter read failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function registerAttempt(string $key): void
    {
        try {
            $count = $this->currentCount($key) + 1;
            $this->cache->save((string) $count, $this->cacheId($key), [], $this->windowSeconds);
        } catch (\Throwable $e) {
            $this->logger->warning('AdminPasskey rate limiter write failed: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function reset(string $key): void
    {
        try {
            $this->cache->remove($this->cacheId($key));
        } catch (\Throwable $e) {
            $this->logger->warning('AdminPasskey rate limiter reset failed: ' . $e->getMessage());
        }
    }

    /**
     * Current attempt count for the key within the active window.
     *
     * @param string $key
     * @return int
     */
    private function currentCount(string $key): int
    {
        // CacheInterface::load() returns the stored string or false on a miss;
        // an int cast normalises both the miss and an empty value to 0.
        return (int) $this->cache->load($this->cacheId($key));
    }

    /**
     * Build a safe cache identifier from an opaque key.
     *
     * @param string $key
     * @return string
     */
    private function cacheId(string $key): string
    {
        return self::CACHE_PREFIX . hash('sha256', $key);
    }
}
