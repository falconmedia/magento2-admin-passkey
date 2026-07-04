<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\TrustedDevice;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;

/**
 * Pure decision logic for trusted-device expiry and validity.
 *
 * No I/O: every input (current time, expiry timestamp, status) is passed in, so
 * the rules are fully unit-testable and free of clock/database coupling.
 */
class TrustedDeviceExpiryEvaluator
{
    /**
     * Compute the expiry timestamp for a device given a lifetime in days.
     *
     * A non-positive lifetime means "never expires" and returns null.
     *
     * @param string $now Reference time (Y-m-d H:i:s, UTC).
     * @param int $lifetimeDays
     * @return string|null Expiry timestamp (Y-m-d H:i:s) or null when it never expires.
     */
    public function resolveExpiresAt(string $now, int $lifetimeDays): ?string
    {
        if ($lifetimeDays <= 0) {
            return null;
        }

        $base = strtotime($now . ' UTC');
        if ($base === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $base + ($lifetimeDays * 86400));
    }

    /**
     * Whether a device is past its expiry timestamp.
     *
     * A null expiry never expires.
     *
     * @param string|null $expiresAt
     * @param string $now
     * @return bool
     */
    public function isExpired(?string $expiresAt, string $now): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return false;
        }

        $expiry = strtotime($expiresAt);
        $current = strtotime($now);
        if ($expiry === false || $current === false) {
            return false;
        }

        return $expiry <= $current;
    }

    /**
     * Whether a device is currently usable: active status and not expired.
     *
     * @param string|null $status
     * @param string|null $expiresAt
     * @param string $now
     * @return bool
     */
    public function isValid(?string $status, ?string $expiresAt, string $now): bool
    {
        return $status === TrustedDeviceInterface::STATUS_ACTIVE
            && !$this->isExpired($expiresAt, $now);
    }
}
