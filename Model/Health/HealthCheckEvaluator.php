<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Health;

/**
 * Pure evaluation logic for the health checks.
 *
 * Every method takes primitive facts gathered by {@see HealthCheckService} and
 * returns a {@see HealthCheckResult}. No I/O happens here, so each rule is unit
 * testable in isolation.
 */
class HealthCheckEvaluator
{
    public const CHECK_PHP_VERSION = 'php_version';
    public const CHECK_MAGENTO_VERSION = 'magento_version';
    public const CHECK_HTTPS = 'https';
    public const CHECK_WEBAUTHN = 'webauthn';
    public const CHECK_HSTS = 'hsts';
    public const CHECK_CRON = 'cron';
    public const CHECK_AUDIT_LOGGING = 'audit_logging';
    public const CHECK_CLEANUP = 'cleanup';
    public const CHECK_DIAGNOSTICS = 'diagnostics';
    public const CHECK_RECOVERY = 'recovery_state';
    public const CHECK_LOCKOUT = 'lockout_health';
    public const CHECK_CONFIG_SANITY = 'config_sanity';

    /**
     * Evaluate the running PHP version against the minimum supported version.
     *
     * @param string $current
     * @param string $minimum
     * @return HealthCheckResult
     */
    public function evaluatePhpVersion(string $current, string $minimum): HealthCheckResult
    {
        $label = (string) __('PHP Version');
        if ($current === '' || version_compare($current, $minimum, '<')) {
            return new HealthCheckResult(
                self::CHECK_PHP_VERSION,
                $label,
                HealthCheckResult::STATUS_ERROR,
                (string) __('PHP %1 or newer is required; the server is running %2.', $minimum, $current === '' ? '?' : $current)
            );
        }

        return new HealthCheckResult(
            self::CHECK_PHP_VERSION,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Running PHP %1.', $current)
        );
    }

    /**
     * Evaluate the Magento version against the minimum supported version.
     *
     * @param string $current
     * @param string $minimum
     * @return HealthCheckResult
     */
    public function evaluateMagentoVersion(string $current, string $minimum): HealthCheckResult
    {
        $label = (string) __('Magento Version');
        if ($current === '') {
            return new HealthCheckResult(
                self::CHECK_MAGENTO_VERSION,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('The Magento version could not be determined.')
            );
        }
        if (version_compare($current, $minimum, '<')) {
            return new HealthCheckResult(
                self::CHECK_MAGENTO_VERSION,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('Magento %1 or newer is recommended; the store is running %2.', $minimum, $current)
            );
        }

        return new HealthCheckResult(
            self::CHECK_MAGENTO_VERSION,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Running Magento %1.', $current)
        );
    }

    /**
     * Evaluate whether the Admin is served over HTTPS.
     *
     * @param bool $isSecure
     * @return HealthCheckResult
     */
    public function evaluateHttps(bool $isSecure): HealthCheckResult
    {
        $label = (string) __('HTTPS');
        if (!$isSecure) {
            return new HealthCheckResult(
                self::CHECK_HTTPS,
                $label,
                HealthCheckResult::STATUS_ERROR,
                (string) __('WebAuthn requires HTTPS. The Admin is not being served over a secure connection.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_HTTPS,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('The Admin is served over HTTPS.')
        );
    }

    /**
     * Evaluate WebAuthn availability (relying party id resolvable and secure context).
     *
     * @param string $rpId
     * @param bool $isSecure
     * @return HealthCheckResult
     */
    public function evaluateWebAuthn(string $rpId, bool $isSecure): HealthCheckResult
    {
        $label = (string) __('WebAuthn Availability');
        if ($rpId === '') {
            return new HealthCheckResult(
                self::CHECK_WEBAUTHN,
                $label,
                HealthCheckResult::STATUS_ERROR,
                (string) __('The WebAuthn relying party ID could not be resolved.')
            );
        }
        if (!$isSecure) {
            return new HealthCheckResult(
                self::CHECK_WEBAUTHN,
                $label,
                HealthCheckResult::STATUS_ERROR,
                (string) __('WebAuthn cannot operate without a secure (HTTPS) context.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_WEBAUTHN,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('WebAuthn is available for relying party "%1".', $rpId)
        );
    }

    /**
     * Evaluate the HTTP Strict Transport Security header, where detectable.
     *
     * @param string|null $hstsHeader
     * @return HealthCheckResult
     */
    public function evaluateHsts(?string $hstsHeader): HealthCheckResult
    {
        $label = (string) __('HTTP Strict Transport Security');
        if ($hstsHeader === null || trim($hstsHeader) === '') {
            return new HealthCheckResult(
                self::CHECK_HSTS,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('No HSTS header was detected. Enabling HSTS is recommended for Admin security.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_HSTS,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('An HSTS header is present.')
        );
    }

    /**
     * Evaluate cron configuration and freshness.
     *
     * @param bool $cronConfigured
     * @param int|null $minutesSinceLastRun Null when cron has never run.
     * @param int $maxAgeMinutes
     * @return HealthCheckResult
     */
    public function evaluateCron(bool $cronConfigured, ?int $minutesSinceLastRun, int $maxAgeMinutes): HealthCheckResult
    {
        $label = (string) __('Cron');
        if (!$cronConfigured || $minutesSinceLastRun === null) {
            return new HealthCheckResult(
                self::CHECK_CRON,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('No recent cron run was detected. Scheduled cleanup depends on cron.')
            );
        }
        if ($minutesSinceLastRun > $maxAgeMinutes) {
            return new HealthCheckResult(
                self::CHECK_CRON,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('The last cron run was %1 minutes ago, exceeding the %2 minute threshold.', $minutesSinceLastRun, $maxAgeMinutes)
            );
        }

        return new HealthCheckResult(
            self::CHECK_CRON,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Cron ran %1 minutes ago.', $minutesSinceLastRun)
        );
    }

    /**
     * Evaluate whether audit logging is active (suite enabled).
     *
     * @param bool $enabled
     * @return HealthCheckResult
     */
    public function evaluateAuditLogging(bool $enabled): HealthCheckResult
    {
        $label = (string) __('Audit Logging');
        if (!$enabled) {
            return new HealthCheckResult(
                self::CHECK_AUDIT_LOGGING,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('The suite is disabled, so security events are not being audited.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_AUDIT_LOGGING,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Audit logging is active.')
        );
    }

    /**
     * Evaluate cleanup configuration.
     *
     * @param bool $enabled
     * @param int $challengeRetentionDays
     * @return HealthCheckResult
     */
    public function evaluateCleanupConfig(bool $enabled, int $challengeRetentionDays): HealthCheckResult
    {
        $label = (string) __('Cleanup Configuration');
        if (!$enabled) {
            return new HealthCheckResult(
                self::CHECK_CLEANUP,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('Scheduled cleanup is disabled; expired data will accumulate.')
            );
        }
        if ($challengeRetentionDays < 0) {
            return new HealthCheckResult(
                self::CHECK_CLEANUP,
                $label,
                HealthCheckResult::STATUS_ERROR,
                (string) __('Challenge retention is misconfigured (negative value).')
            );
        }

        return new HealthCheckResult(
            self::CHECK_CLEANUP,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Scheduled cleanup is enabled.')
        );
    }

    /**
     * Evaluate diagnostics configuration.
     *
     * @param bool $enabled
     * @param string $supportEmail
     * @return HealthCheckResult
     */
    public function evaluateDiagnosticsConfig(bool $enabled, string $supportEmail): HealthCheckResult
    {
        $label = (string) __('Diagnostics Configuration');
        if (!$enabled) {
            return new HealthCheckResult(
                self::CHECK_DIAGNOSTICS,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('Diagnostics are disabled.')
            );
        }
        if (trim($supportEmail) === '') {
            return new HealthCheckResult(
                self::CHECK_DIAGNOSTICS,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('No support email is configured; diagnostics reports cannot be sent to the developer.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_DIAGNOSTICS,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Diagnostics are enabled and a support email is configured.')
        );
    }

    /**
     * Evaluate the emergency recovery-mode state.
     *
     * @param bool $recoveryActive
     * @return HealthCheckResult
     */
    public function evaluateRecoveryState(bool $recoveryActive): HealthCheckResult
    {
        $label = (string) __('Recovery Mode');
        if ($recoveryActive) {
            return new HealthCheckResult(
                self::CHECK_RECOVERY,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('Emergency recovery mode is currently active. Disable it once access is restored.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_RECOVERY,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Emergency recovery mode is not active.')
        );
    }

    /**
     * Evaluate lockout health based on the number of active lockouts.
     *
     * @param int $activeLockouts
     * @param int $warnThreshold
     * @return HealthCheckResult
     */
    public function evaluateLockoutHealth(int $activeLockouts, int $warnThreshold): HealthCheckResult
    {
        $label = (string) __('Lockout Health');
        if ($activeLockouts >= $warnThreshold && $warnThreshold > 0) {
            return new HealthCheckResult(
                self::CHECK_LOCKOUT,
                $label,
                HealthCheckResult::STATUS_WARNING,
                (string) __('There are %1 active lockouts, which may indicate an attack.', $activeLockouts)
            );
        }

        return new HealthCheckResult(
            self::CHECK_LOCKOUT,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('There are %1 active lockouts.', $activeLockouts)
        );
    }

    /**
     * Evaluate configuration sanity (security score weights should sum to a positive total).
     *
     * @param int $weightSum
     * @return HealthCheckResult
     */
    public function evaluateConfigSanity(int $weightSum): HealthCheckResult
    {
        $label = (string) __('Configuration Sanity');
        if ($weightSum <= 0) {
            return new HealthCheckResult(
                self::CHECK_CONFIG_SANITY,
                $label,
                HealthCheckResult::STATUS_ERROR,
                (string) __('The security score weights sum to zero; the score cannot be computed.')
            );
        }

        return new HealthCheckResult(
            self::CHECK_CONFIG_SANITY,
            $label,
            HealthCheckResult::STATUS_OK,
            (string) __('Configuration values are within expected ranges.')
        );
    }
}
