<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves the effective WebAuthn relying party identity (rpId, name, origin).
 *
 * Configured values win; when a value is left empty the rpId/origin are derived
 * from the store base URL so a correct default works out of the box. rpId and
 * origin are validated during verification (workflow Step 10).
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class RelyingPartyProvider
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Effective relying party display name.
     */
    public function getName(): string
    {
        $name = $this->configProvider->getRelyingPartyName();
        if ($name !== '') {
            return $name;
        }

        $company = $this->configProvider->getBrandingCompanyName();
        return $company !== '' ? $company : $this->getId();
    }

    /**
     * Effective relying party id (registrable domain / rpId).
     */
    public function getId(): string
    {
        $configured = $this->configProvider->getRelyingPartyId();
        if ($configured !== '') {
            return $configured;
        }

        $host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    /**
     * Effective expected origin (scheme://host[:port]).
     */
    public function getOrigin(): string
    {
        $configured = $this->configProvider->getExpectedOrigin();
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $baseUrl = $this->getBaseUrl();
        $parts = parse_url($baseUrl);
        if (!is_array($parts) || empty($parts['host'])) {
            return 'https://' . $this->getId();
        }

        $scheme = $parts['scheme'] ?? 'https';
        $origin = $scheme . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    /**
     * Base URL used to derive rpId/origin when they are not explicitly configured.
     */
    private function getBaseUrl(): string
    {
        try {
            $store = $this->storeManager->getStore();
            if ($store instanceof Store) {
                return (string) $store->getBaseUrl();
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }
}
