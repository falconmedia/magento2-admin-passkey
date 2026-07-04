<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\TrustedDevice;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterfaceFactory;
use FalconMedia\AdminPasskey\Api\TrustedDeviceRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Default trusted-device orchestration.
 *
 * Only a SHA-256 hash of the device token is ever persisted; the raw token lives
 * exclusively in the browser cookie. Every mutating action is audited. All login
 * paths funnel through {@see self::handleSuccessfulLogin()} which is side-effect
 * safe so trusted-device handling can never block a legitimate login.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    /**
     * Persistent cookie carrying the raw device token (hash-only stored server-side).
     */
    public const COOKIE_NAME = 'fm_adminpasskey_td';

    /**
     * Cache key prefix for the per-browser successful-login counter.
     */
    private const COUNTER_PREFIX = 'falconmedia_adminpasskey_td_success_';

    /**
     * Maximum stored device label length (matches the label column).
     */
    private const LABEL_MAX_LENGTH = 255;

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly TrustedDeviceRepositoryInterface $trustedDeviceRepository,
        private readonly TrustedDeviceInterfaceFactory $trustedDeviceFactory,
        private readonly TrustedDeviceTokenizer $tokenizer,
        private readonly TrustedDeviceExpiryEvaluator $expiryEvaluator,
        private readonly SuccessfulLoginTrustPolicy $trustPolicy,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly CacheInterface $cache,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Json $json,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function handleSuccessfulLogin(int $adminUserId, ?string $remoteIp, ?string $userAgent): void
    {
        try {
            if ($adminUserId <= 0 || !$this->configProvider->isTrustedDevicesEnabled()) {
                return;
            }

            if ($this->isCurrentRequestTrusted($adminUserId)) {
                $this->touchCurrentDevice();
                $this->resetCounter($adminUserId, $remoteIp);

                return;
            }

            $count = $this->incrementCounter($adminUserId, $remoteIp);
            $threshold = $this->configProvider->getSuccessfulLoginsBeforeTrust();

            if (!$this->trustPolicy->shouldCreateTrustedDevice(true, false, $count, $threshold)) {
                return;
            }

            $this->createTrustedDevice($adminUserId, $remoteIp, $userAgent);
            $this->resetCounter($adminUserId, $remoteIp);
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey trusted-device handling failed: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function isCurrentRequestTrusted(int $adminUserId): bool
    {
        $device = $this->loadCurrentDevice();
        if ($device === null || $device->getAdminUserId() !== $adminUserId) {
            return false;
        }

        return $this->expiryEvaluator->isValid(
            $device->getStatus(),
            $device->getExpiresAt(),
            $this->now()
        );
    }

    /**
     * @inheritdoc
     */
    public function revoke(int $entityId, ?int $actorAdminUserId = null): void
    {
        $device = $this->trustedDeviceRepository->revoke($entityId);
        $this->audit(
            AuditLoggerInterface::EVENT_TRUSTED_DEVICE_REVOKE,
            $device,
            $actorAdminUserId
        );
    }

    /**
     * @inheritdoc
     */
    public function expireStaleDevices(): int
    {
        $now = $this->now();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(TrustedDeviceInterface::STATUS, TrustedDeviceInterface::STATUS_ACTIVE)
            ->addFilter(TrustedDeviceInterface::EXPIRES_AT, $now, 'lteq')
            ->create();

        $expired = 0;
        foreach ($this->trustedDeviceRepository->getList($searchCriteria)->getItems() as $device) {
            if (!$this->expiryEvaluator->isExpired($device->getExpiresAt(), $now)) {
                continue;
            }
            $device->setStatus(TrustedDeviceInterface::STATUS_EXPIRED);
            $this->trustedDeviceRepository->save($device);
            $this->audit(AuditLoggerInterface::EVENT_TRUSTED_DEVICE_EXPIRED, $device, null);
            $expired++;
        }

        return $expired;
    }

    /**
     * Create and persist a new trusted device for the current browser.
     *
     * @param int $adminUserId
     * @param string|null $remoteIp
     * @param string|null $userAgent
     * @return void
     */
    private function createTrustedDevice(int $adminUserId, ?string $remoteIp, ?string $userAgent): void
    {
        $token = $this->tokenizer->generateToken();
        $now = $this->now();
        $lifetimeDays = $this->configProvider->getTrustedDeviceLifetimeDays();

        /** @var TrustedDeviceInterface $device */
        $device = $this->trustedDeviceFactory->create();
        $device->setAdminUserId($adminUserId);
        $device->setDeviceTokenHash($this->tokenizer->hash($token));
        $device->setLabel($this->buildLabel($userAgent));
        $device->setMetadata($this->buildMetadata($remoteIp, $userAgent));
        $device->setStatus(TrustedDeviceInterface::STATUS_ACTIVE);
        $device->setFirstSeenAt($now);
        $device->setLastSeenAt($now);
        $device->setExpiresAt($this->expiryEvaluator->resolveExpiresAt($now, $lifetimeDays));

        $device = $this->trustedDeviceRepository->save($device);
        $this->issueCookie($token, $lifetimeDays);
        $this->audit(AuditLoggerInterface::EVENT_TRUSTED_DEVICE_CREATED, $device, $adminUserId);
    }

    /**
     * Update the last-seen timestamp of the current trusted device.
     *
     * @return void
     */
    private function touchCurrentDevice(): void
    {
        $device = $this->loadCurrentDevice();
        if ($device === null) {
            return;
        }
        $device->setLastSeenAt($this->now());
        $this->trustedDeviceRepository->save($device);
    }

    /**
     * Load the trusted device referenced by the current request cookie, if any.
     *
     * @return TrustedDeviceInterface|null
     */
    private function loadCurrentDevice(): ?TrustedDeviceInterface
    {
        $token = $this->cookieManager->getCookie(self::COOKIE_NAME);
        if (!is_string($token) || $token === '') {
            return null;
        }

        try {
            return $this->trustedDeviceRepository->getByTokenHash($this->tokenizer->hash($token));
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    /**
     * Issue the persistent device-token cookie.
     *
     * @param string $token
     * @param int $lifetimeDays
     * @return void
     */
    private function issueCookie(string $token, int $lifetimeDays): void
    {
        $duration = $lifetimeDays > 0 ? $lifetimeDays * 86400 : 86400;
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath('/')
            ->setHttpOnly(true)
            ->setSecure(true)
            ->setSameSite('Lax');

        $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $token, $metadata);
    }

    /**
     * Increment and return the per-browser successful-login counter.
     *
     * @param int $adminUserId
     * @param string|null $remoteIp
     * @return int
     */
    private function incrementCounter(int $adminUserId, ?string $remoteIp): int
    {
        $id = $this->counterId($adminUserId, $remoteIp);
        $count = ((int) $this->cache->load($id)) + 1;
        // Retain the counter for a generous window so intermittent logins still add up.
        $this->cache->save((string) $count, $id, [], 30 * 86400);

        return $count;
    }

    /**
     * Clear the per-browser successful-login counter.
     *
     * @param int $adminUserId
     * @param string|null $remoteIp
     * @return void
     */
    private function resetCounter(int $adminUserId, ?string $remoteIp): void
    {
        $this->cache->remove($this->counterId($adminUserId, $remoteIp));
    }

    /**
     * Build the counter cache id for an admin/browser pair.
     *
     * @param int $adminUserId
     * @param string|null $remoteIp
     * @return string
     */
    private function counterId(int $adminUserId, ?string $remoteIp): string
    {
        return self::COUNTER_PREFIX . hash('sha256', $adminUserId . '|' . ($remoteIp ?? 'unknown'));
    }

    /**
     * Build a short, non-sensitive device label from the user agent.
     *
     * @param string|null $userAgent
     * @return string|null
     */
    private function buildLabel(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, self::LABEL_MAX_LENGTH);
    }

    /**
     * Build non-sensitive device metadata JSON.
     *
     * @param string|null $remoteIp
     * @param string|null $userAgent
     * @return string|null
     */
    private function buildMetadata(?string $remoteIp, ?string $userAgent): ?string
    {
        $metadata = [];
        if ($remoteIp !== null && $remoteIp !== '') {
            $metadata['ip'] = $remoteIp;
        }
        if ($userAgent !== null && $userAgent !== '') {
            $metadata['user_agent'] = mb_substr($userAgent, 0, self::LABEL_MAX_LENGTH);
        }

        return $metadata === [] ? null : $this->json->serialize($metadata);
    }

    /**
     * Record a trusted-device audit event, swallowing audit errors.
     *
     * @param string $eventType
     * @param TrustedDeviceInterface $device
     * @param int|null $actorAdminUserId
     * @return void
     */
    private function audit(string $eventType, TrustedDeviceInterface $device, ?int $actorAdminUserId): void
    {
        try {
            $context = [
                AuditLoggerInterface::CONTEXT_TARGET => $device->getAdminUserId(),
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'trusted_device_row_id' => $device->getId(),
                    'label' => $device->getLabel(),
                    'status' => $device->getStatus(),
                ],
            ];
            if ($actorAdminUserId !== null) {
                $context[AuditLoggerInterface::CONTEXT_ACTOR] = $actorAdminUserId;
            }
            $this->auditLogger->record($eventType, $context);
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey trusted-device audit failed: ' . $e->getMessage());
        }
    }

    /**
     * Current UTC timestamp (Y-m-d H:i:s).
     *
     * @return string
     */
    private function now(): string
    {
        return $this->dateTime->gmtDate();
    }
}
