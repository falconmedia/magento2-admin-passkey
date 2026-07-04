<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed, read-only accessor for every FalconMedia_AdminPasskey configuration path.
 *
 * Feature code must read configuration through this service and never touch
 * ScopeConfig paths directly.
 */
class ConfigProvider
{
    private const XML_PATH_PREFIX = 'adminpasskey/';

    // General.
    private const PATH_GENERAL_ENABLED = self::XML_PATH_PREFIX . 'general/enabled';
    private const PATH_GENERAL_LOGIN_LANGUAGE = self::XML_PATH_PREFIX . 'general/login_language';

    // Authentication Policy.
    private const PATH_AUTH_PASSKEY_FIRST = self::XML_PATH_PREFIX . 'authentication_policy/passkey_first_login';
    private const PATH_AUTH_PASSWORD_FALLBACK = self::XML_PATH_PREFIX . 'authentication_policy/password_fallback_enabled';
    private const PATH_AUTH_TWO_FA_FALLBACK = self::XML_PATH_PREFIX . 'authentication_policy/two_fa_fallback_enabled';

    // Login page design.
    private const PATH_LOGIN_DESIGN_LAYOUT = self::XML_PATH_PREFIX . 'login_design/layout';
    // Shared login copy (applies to every layout).
    private const PATH_LOGIN_ENVIRONMENT = self::XML_PATH_PREFIX . 'login_design/environment_badge';
    private const PATH_LOGIN_PASSKEY_HEADLINE = self::XML_PATH_PREFIX . 'login_design/passkey_headline';
    private const PATH_LOGIN_PASSKEY_DESCRIPTION = self::XML_PATH_PREFIX . 'login_design/passkey_description';
    private const PATH_LOGIN_PASSKEY_BUTTON = self::XML_PATH_PREFIX . 'login_design/passkey_button_label';
    private const PATH_LOGIN_SIGN_IN_TITLE = self::XML_PATH_PREFIX . 'login_design/sign_in_title';
    private const PATH_LOGIN_SIGN_IN_SUBTITLE = self::XML_PATH_PREFIX . 'login_design/sign_in_subtitle';
    private const PATH_LOGIN_PASSKEY_SUBTITLE = self::XML_PATH_PREFIX . 'login_design/passkey_subtitle';
    private const PATH_LOGIN_PASSWORD_TWO_FA = self::XML_PATH_PREFIX . 'login_design/password_two_fa_notice';
    // Split Console specific.
    private const PATH_LOGIN_SPLIT_BRAND_HEADLINE = self::XML_PATH_PREFIX . 'login_design_split_console/brand_headline';
    // Command Deck / Image Deck specific.
    private const PATH_LOGIN_COMMAND_AUTH_LABEL = self::XML_PATH_PREFIX . 'login_design_command_deck/auth_label';
    private const PATH_LOGIN_COMMAND_FOOTER = self::XML_PATH_PREFIX . 'login_design_command_deck/footer_text';
    private const PATH_LOGIN_COMMAND_IMAGE_LIGHT = self::XML_PATH_PREFIX . 'login_design_command_deck/stage_image_light';
    private const PATH_LOGIN_COMMAND_IMAGE_DARK = self::XML_PATH_PREFIX . 'login_design_command_deck/stage_image_dark';

    // WebAuthn.
    private const PATH_WEBAUTHN_RP_NAME = self::XML_PATH_PREFIX . 'webauthn/relying_party_name';
    private const PATH_WEBAUTHN_RP_ID = self::XML_PATH_PREFIX . 'webauthn/relying_party_id';
    private const PATH_WEBAUTHN_ORIGIN = self::XML_PATH_PREFIX . 'webauthn/expected_origin';
    private const PATH_WEBAUTHN_USER_VERIFICATION = self::XML_PATH_PREFIX . 'webauthn/user_verification';
    private const PATH_WEBAUTHN_RESIDENT_KEY = self::XML_PATH_PREFIX . 'webauthn/resident_key';
    private const PATH_WEBAUTHN_TIMEOUT_MS = self::XML_PATH_PREFIX . 'webauthn/ceremony_timeout_ms';
    private const PATH_WEBAUTHN_CHALLENGE_LIFETIME = self::XML_PATH_PREFIX . 'webauthn/challenge_lifetime_seconds';

    // Onboarding.
    private const PATH_ONBOARDING_REQUIRE = self::XML_PATH_PREFIX . 'onboarding/require_passkey_onboarding';
    private const PATH_ONBOARDING_RECOMMEND_SECOND = self::XML_PATH_PREFIX . 'onboarding/recommend_second_passkey';

    // Trusted Devices.
    private const PATH_TRUSTED_ENABLED = self::XML_PATH_PREFIX . 'trusted_devices/enabled';
    private const PATH_TRUSTED_LIFETIME_DAYS = self::XML_PATH_PREFIX . 'trusted_devices/lifetime_days';
    private const PATH_TRUSTED_LOGINS_BEFORE_TRUST = self::XML_PATH_PREFIX . 'trusted_devices/successful_logins_before_trust';

    // Lockout.
    private const PATH_LOCKOUT_ENABLED = self::XML_PATH_PREFIX . 'lockout/enabled';
    private const PATH_LOCKOUT_MAX_ATTEMPTS = self::XML_PATH_PREFIX . 'lockout/max_failed_attempts';
    private const PATH_LOCKOUT_DURATION_MINUTES = self::XML_PATH_PREFIX . 'lockout/lockout_duration_minutes';
    private const PATH_LOCKOUT_ATTEMPT_WINDOW_MINUTES = self::XML_PATH_PREFIX . 'lockout/attempt_window_minutes';

    // Recovery.
    private const PATH_RECOVERY_ENABLED = self::XML_PATH_PREFIX . 'recovery/enabled';
    private const PATH_RECOVERY_WARNING_TEXT = self::XML_PATH_PREFIX . 'recovery/warning_text';
    private const PATH_RECOVERY_EXPIRY_MINUTES = self::XML_PATH_PREFIX . 'recovery/expiry_minutes';

    // Migration Dashboard.
    private const PATH_MIGRATION_ENABLED = self::XML_PATH_PREFIX . 'migration_dashboard/enabled';
    private const PATH_MIGRATION_REMINDER_EMAIL = self::XML_PATH_PREFIX . 'migration_dashboard/reminder_email_enabled';

    // Security Dashboard Widget.
    private const PATH_WIDGET_ENABLED = self::XML_PATH_PREFIX . 'security_dashboard_widget/enabled';
    private const PATH_WIDGET_REFRESH_INTERVAL = self::XML_PATH_PREFIX . 'security_dashboard_widget/refresh_interval';
    private const PATH_WIDGET_CARD_PREFIX = self::XML_PATH_PREFIX . 'security_dashboard_widget/card_';

    // Security Score.
    private const PATH_SCORE_ENABLED = self::XML_PATH_PREFIX . 'security_score/enabled';
    private const PATH_SCORE_WEIGHT_AUTH = self::XML_PATH_PREFIX . 'security_score/weight_authentication';
    private const PATH_SCORE_WEIGHT_SECURITY = self::XML_PATH_PREFIX . 'security_score/weight_security';
    private const PATH_SCORE_WEIGHT_OPERATIONAL = self::XML_PATH_PREFIX . 'security_score/weight_operational';
    private const PATH_SCORE_WEIGHT_THREATS = self::XML_PATH_PREFIX . 'security_score/weight_threats';

    // Health Check.
    private const PATH_HEALTH_ENABLED = self::XML_PATH_PREFIX . 'health_check/enabled';

    // Diagnostics.
    private const PATH_DIAGNOSTICS_ENABLED = self::XML_PATH_PREFIX . 'diagnostics/enabled';
    private const PATH_DIAGNOSTICS_SUPPORT_EMAIL = self::XML_PATH_PREFIX . 'diagnostics/support_email';
    private const PATH_DIAGNOSTICS_REFERENCE_PREFIX = self::XML_PATH_PREFIX . 'diagnostics/support_reference_prefix';

    // Cleanup.
    private const PATH_CLEANUP_ENABLED = self::XML_PATH_PREFIX . 'cleanup/enabled';
    private const PATH_CLEANUP_SCHEDULE = self::XML_PATH_PREFIX . 'cleanup/schedule';
    private const PATH_CLEANUP_CHALLENGE_RETENTION = self::XML_PATH_PREFIX . 'cleanup/challenge_retention_days';
    private const PATH_CLEANUP_DIAGNOSTICS_RETENTION = self::XML_PATH_PREFIX . 'cleanup/diagnostics_retention_days';
    private const PATH_CLEANUP_AUDIT_RETENTION = self::XML_PATH_PREFIX . 'cleanup/audit_retention_days';
    private const PATH_CLEANUP_SCORE_RETENTION = self::XML_PATH_PREFIX . 'cleanup/score_snapshot_retention_days';
    private const PATH_CLEANUP_REMINDER_RETENTION = self::XML_PATH_PREFIX . 'cleanup/reminder_retention_days';

    // Fail2Ban.
    private const PATH_FAIL2BAN_ENABLED = self::XML_PATH_PREFIX . 'fail2ban/enabled';
    private const PATH_FAIL2BAN_LOG_PATH = self::XML_PATH_PREFIX . 'fail2ban/log_path';

    // White Label & Branding.
    private const PATH_BRANDING_ENABLED = self::XML_PATH_PREFIX . 'branding/enabled';
    private const PATH_BRANDING_COMPANY_NAME = self::XML_PATH_PREFIX . 'branding/company_name';
    private const PATH_BRANDING_SUPPORT_EMAIL = self::XML_PATH_PREFIX . 'branding/support_email';
    private const PATH_BRANDING_SUPPORT_URL = self::XML_PATH_PREFIX . 'branding/support_url';
    private const PATH_BRANDING_DOCUMENTATION_URL = self::XML_PATH_PREFIX . 'branding/documentation_url';
    private const PATH_BRANDING_PRIVACY_URL = self::XML_PATH_PREFIX . 'branding/privacy_url';
    private const PATH_BRANDING_FOOTER_TEXT = self::XML_PATH_PREFIX . 'branding/footer_text';
    private const PATH_BRANDING_PRIMARY_COLOR = self::XML_PATH_PREFIX . 'branding/primary_accent_color';
    private const PATH_BRANDING_SECONDARY_COLOR = self::XML_PATH_PREFIX . 'branding/secondary_accent_color';
    private const PATH_BRANDING_LOGO = self::XML_PATH_PREFIX . 'branding/logo';
    private const PATH_BRANDING_ICON = self::XML_PATH_PREFIX . 'branding/icon';
    private const PATH_BRANDING_BACKGROUND = self::XML_PATH_PREFIX . 'branding/background_image';
    private const PATH_BRANDING_WIZARD_ILLUSTRATION = self::XML_PATH_PREFIX . 'branding/wizard_illustration';
    private const PATH_BRANDING_DASHBOARD_ICON = self::XML_PATH_PREFIX . 'branding/dashboard_icon';

    // White Label & Branding copy.
    private const PATH_BRANDING_LOGIN_INTRO = self::XML_PATH_PREFIX . 'branding/login_intro_text';
    private const PATH_BRANDING_WIZARD_INTRO = self::XML_PATH_PREFIX . 'branding/wizard_intro_text';
    private const PATH_BRANDING_DIAGNOSTICS_INTRO = self::XML_PATH_PREFIX . 'branding/diagnostics_intro_text';
    private const PATH_BRANDING_SCORE_LABEL_POOR = self::XML_PATH_PREFIX . 'branding/score_label_poor';
    private const PATH_BRANDING_SCORE_LABEL_FAIR = self::XML_PATH_PREFIX . 'branding/score_label_fair';
    private const PATH_BRANDING_SCORE_LABEL_GOOD = self::XML_PATH_PREFIX . 'branding/score_label_good';
    private const PATH_BRANDING_SCORE_LABEL_EXCELLENT = self::XML_PATH_PREFIX . 'branding/score_label_excellent';

    // Email Templates.
    private const PATH_EMAIL_REMINDER = self::XML_PATH_PREFIX . 'email_templates/reminder_template';
    private const PATH_EMAIL_LOCKOUT = self::XML_PATH_PREFIX . 'email_templates/lockout_template';
    private const PATH_EMAIL_RECOVERY = self::XML_PATH_PREFIX . 'email_templates/recovery_template';
    private const PATH_EMAIL_DIAGNOSTICS = self::XML_PATH_PREFIX . 'email_templates/diagnostics_template';
    private const PATH_EMAIL_SUPPORT = self::XML_PATH_PREFIX . 'email_templates/support_template';
    private const PATH_EMAIL_SECURITY_ALERT = self::XML_PATH_PREFIX . 'email_templates/security_alert_template';

    // Developer Options.
    private const PATH_DEVELOPER_DEBUG_LOGGING = self::XML_PATH_PREFIX . 'developer_options/debug_logging';

    /**
     * @var string[] Identifiers of the configurable dashboard widget cards.
     */
    private const DASHBOARD_CARDS = [
        'passkey_adoption',
        'admins_without_passkeys',
        'login_ratio',
        'active_lockouts',
        'failed_logins_24h',
        'health_status',
        'recovery_status',
        'trusted_devices',
        'diagnostics_reports',
        'audit_events',
        'last_cleanup',
        'last_security_event',
        'security_score',
        'quick_actions',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    // region General

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_GENERAL_ENABLED, $storeId);
    }

    /**
     * Login-page language strategy: 'auto' (browser detection) or a specific
     * deployed backend locale to force.
     */
    public function getLoginLanguage(?int $storeId = null): string
    {
        return $this->string(self::PATH_GENERAL_LOGIN_LANGUAGE, $storeId);
    }

    // endregion

    // region Authentication Policy

    public function isPasskeyFirstLogin(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_AUTH_PASSKEY_FIRST, $storeId);
    }

    public function isPasswordFallbackEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_AUTH_PASSWORD_FALLBACK, $storeId);
    }

    public function isTwoFaFallbackEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_AUTH_TWO_FA_FALLBACK, $storeId);
    }

    // endregion

    // region WebAuthn

    /**
     * Configured relying party display name (may be empty; resolver applies a fallback).
     */
    public function getRelyingPartyName(?int $storeId = null): string
    {
        return $this->string(self::PATH_WEBAUTHN_RP_NAME, $storeId);
    }

    /**
     * Configured relying party ID / rpId (may be empty; resolver derives it from the base URL host).
     */
    public function getRelyingPartyId(?int $storeId = null): string
    {
        return $this->string(self::PATH_WEBAUTHN_RP_ID, $storeId);
    }

    /**
     * Configured expected origin (may be empty; resolver derives it from the base URL).
     */
    public function getExpectedOrigin(?int $storeId = null): string
    {
        return $this->string(self::PATH_WEBAUTHN_ORIGIN, $storeId);
    }

    /**
     * WebAuthn userVerification requirement (required|preferred|discouraged).
     */
    public function getUserVerification(?int $storeId = null): string
    {
        $value = $this->string(self::PATH_WEBAUTHN_USER_VERIFICATION, $storeId);
        return $value !== '' ? $value : 'preferred';
    }

    /**
     * WebAuthn residentKey requirement (discouraged|preferred|required).
     */
    public function getResidentKey(?int $storeId = null): string
    {
        $value = $this->string(self::PATH_WEBAUTHN_RESIDENT_KEY, $storeId);
        return $value !== '' ? $value : 'preferred';
    }

    /**
     * WebAuthn ceremony timeout in milliseconds.
     */
    public function getCeremonyTimeoutMs(?int $storeId = null): int
    {
        $value = $this->int(self::PATH_WEBAUTHN_TIMEOUT_MS, $storeId);
        return $value > 0 ? $value : 60000;
    }

    /**
     * Challenge row lifetime in seconds (used to compute expires_at).
     */
    public function getChallengeLifetimeSeconds(?int $storeId = null): int
    {
        $value = $this->int(self::PATH_WEBAUTHN_CHALLENGE_LIFETIME, $storeId);
        return $value > 0 ? $value : 300;
    }

    // endregion

    // region Onboarding

    public function isPasskeyOnboardingRequired(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_ONBOARDING_REQUIRE, $storeId);
    }

    public function isSecondPasskeyRecommended(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_ONBOARDING_RECOMMEND_SECOND, $storeId);
    }

    // endregion

    // region Trusted Devices

    public function isTrustedDevicesEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_TRUSTED_ENABLED, $storeId);
    }

    public function getTrustedDeviceLifetimeDays(?int $storeId = null): int
    {
        return $this->int(self::PATH_TRUSTED_LIFETIME_DAYS, $storeId);
    }

    /**
     * Number of successful logins from a browser before it becomes a trusted device.
     */
    public function getSuccessfulLoginsBeforeTrust(?int $storeId = null): int
    {
        $value = $this->int(self::PATH_TRUSTED_LOGINS_BEFORE_TRUST, $storeId);
        return $value > 0 ? $value : 3;
    }

    // endregion

    // region Lockout

    public function isLockoutEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_LOCKOUT_ENABLED, $storeId);
    }

    public function getMaxFailedAttempts(?int $storeId = null): int
    {
        return $this->int(self::PATH_LOCKOUT_MAX_ATTEMPTS, $storeId);
    }

    public function getLockoutDurationMinutes(?int $storeId = null): int
    {
        return $this->int(self::PATH_LOCKOUT_DURATION_MINUTES, $storeId);
    }

    /**
     * Rolling window (minutes) within which failed attempts are counted toward lockout.
     */
    public function getAttemptWindowMinutes(?int $storeId = null): int
    {
        $value = $this->int(self::PATH_LOCKOUT_ATTEMPT_WINDOW_MINUTES, $storeId);
        return $value > 0 ? $value : 15;
    }

    // endregion

    // region Recovery

    public function isRecoveryEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_RECOVERY_ENABLED, $storeId);
    }

    public function getRecoveryWarningText(?int $storeId = null): string
    {
        return $this->string(self::PATH_RECOVERY_WARNING_TEXT, $storeId);
    }

    /**
     * Minutes after which emergency recovery mode auto-expires (0 disables auto-expiry).
     */
    public function getRecoveryExpiryMinutes(?int $storeId = null): int
    {
        $value = $this->int(self::PATH_RECOVERY_EXPIRY_MINUTES, $storeId);
        return $value > 0 ? $value : 60;
    }

    // endregion

    // region Migration Dashboard

    public function isMigrationDashboardEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_MIGRATION_ENABLED, $storeId);
    }

    public function isMigrationReminderEmailEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_MIGRATION_REMINDER_EMAIL, $storeId);
    }

    // endregion

    // region Security Dashboard Widget

    public function isDashboardWidgetEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_WIDGET_ENABLED, $storeId);
    }

    public function getDashboardRefreshInterval(?int $storeId = null): int
    {
        return $this->int(self::PATH_WIDGET_REFRESH_INTERVAL, $storeId);
    }

    /**
     * Whether a single dashboard card is enabled.
     */
    public function isDashboardCardEnabled(string $card, ?int $storeId = null): bool
    {
        return $this->flag(self::PATH_WIDGET_CARD_PREFIX . $card, $storeId);
    }

    /**
     * Enabled state of every dashboard card, keyed by card identifier.
     *
     * @return array<string, bool>
     */
    public function getDashboardCards(?int $storeId = null): array
    {
        $cards = [];
        foreach (self::DASHBOARD_CARDS as $card) {
            $cards[$card] = $this->isDashboardCardEnabled($card, $storeId);
        }

        return $cards;
    }

    // endregion

    // region Security Score

    public function isSecurityScoreEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_SCORE_ENABLED, $storeId);
    }

    public function getSecurityScoreWeightAuthentication(?int $storeId = null): int
    {
        return $this->int(self::PATH_SCORE_WEIGHT_AUTH, $storeId);
    }

    public function getSecurityScoreWeightSecurity(?int $storeId = null): int
    {
        return $this->int(self::PATH_SCORE_WEIGHT_SECURITY, $storeId);
    }

    public function getSecurityScoreWeightOperational(?int $storeId = null): int
    {
        return $this->int(self::PATH_SCORE_WEIGHT_OPERATIONAL, $storeId);
    }

    public function getSecurityScoreWeightThreats(?int $storeId = null): int
    {
        return $this->int(self::PATH_SCORE_WEIGHT_THREATS, $storeId);
    }

    // endregion

    // region Health Check

    public function isHealthCheckEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_HEALTH_ENABLED, $storeId);
    }

    // endregion

    // region Diagnostics

    public function isDiagnosticsEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_DIAGNOSTICS_ENABLED, $storeId);
    }

    public function getDiagnosticsSupportEmail(?int $storeId = null): string
    {
        return $this->string(self::PATH_DIAGNOSTICS_SUPPORT_EMAIL, $storeId);
    }

    public function getSupportReferencePrefix(?int $storeId = null): string
    {
        return $this->string(self::PATH_DIAGNOSTICS_REFERENCE_PREFIX, $storeId);
    }

    // endregion

    // region Cleanup

    public function isCleanupEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_CLEANUP_ENABLED, $storeId);
    }

    /**
     * Cron expression for the scheduled cleanup job (may be empty; crontab default applies).
     */
    public function getCleanupSchedule(?int $storeId = null): string
    {
        return $this->string(self::PATH_CLEANUP_SCHEDULE, $storeId);
    }

    public function getChallengeRetentionDays(?int $storeId = null): int
    {
        return $this->int(self::PATH_CLEANUP_CHALLENGE_RETENTION, $storeId);
    }

    public function getDiagnosticsRetentionDays(?int $storeId = null): int
    {
        return $this->int(self::PATH_CLEANUP_DIAGNOSTICS_RETENTION, $storeId);
    }

    public function getAuditRetentionDays(?int $storeId = null): int
    {
        return $this->int(self::PATH_CLEANUP_AUDIT_RETENTION, $storeId);
    }

    public function getScoreSnapshotRetentionDays(?int $storeId = null): int
    {
        return $this->int(self::PATH_CLEANUP_SCORE_RETENTION, $storeId);
    }

    public function getReminderRetentionDays(?int $storeId = null): int
    {
        return $this->int(self::PATH_CLEANUP_REMINDER_RETENTION, $storeId);
    }

    // endregion

    // region Fail2Ban

    public function isFail2BanEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_FAIL2BAN_ENABLED, $storeId);
    }

    public function getFail2BanLogPath(?int $storeId = null): string
    {
        return $this->string(self::PATH_FAIL2BAN_LOG_PATH, $storeId);
    }

    // endregion

    // region White Label & Branding

    public function isBrandingEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_BRANDING_ENABLED, $storeId);
    }

    public function getBrandingCompanyName(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_COMPANY_NAME, $storeId);
    }

    public function getBrandingSupportEmail(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SUPPORT_EMAIL, $storeId);
    }

    public function getBrandingSupportUrl(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SUPPORT_URL, $storeId);
    }

    public function getBrandingDocumentationUrl(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_DOCUMENTATION_URL, $storeId);
    }

    public function getBrandingPrivacyUrl(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_PRIVACY_URL, $storeId);
    }

    public function getBrandingFooterText(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_FOOTER_TEXT, $storeId);
    }

    public function getBrandingPrimaryAccentColor(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_PRIMARY_COLOR, $storeId);
    }

    public function getBrandingSecondaryAccentColor(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SECONDARY_COLOR, $storeId);
    }

    /**
     * Stored logo file path (relative to media/adminpasskey/logo), or empty when none.
     */
    public function getBrandingLogo(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_LOGO, $storeId);
    }

    /**
     * Stored icon file path (relative to media/adminpasskey/icon), or empty when none.
     */
    public function getBrandingIcon(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_ICON, $storeId);
    }

    /**
     * Stored background image path (relative to media/adminpasskey/background_image), or empty when none.
     */
    public function getBrandingBackgroundImage(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_BACKGROUND, $storeId);
    }

    /**
     * Stored wizard illustration path (relative to media/adminpasskey/wizard_illustration), or empty when none.
     */
    public function getBrandingWizardIllustration(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_WIZARD_ILLUSTRATION, $storeId);
    }

    /**
     * Stored dashboard icon path (relative to media/adminpasskey/dashboard_icon), or empty when none.
     */
    public function getBrandingDashboardIcon(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_DASHBOARD_ICON, $storeId);
    }

    /**
     * Configured login intro copy (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingLoginIntroText(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_LOGIN_INTRO, $storeId);
    }

    /**
     * Configured wizard intro copy (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingWizardIntroText(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_WIZARD_INTRO, $storeId);
    }

    /**
     * Configured diagnostics intro copy (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingDiagnosticsIntroText(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_DIAGNOSTICS_INTRO, $storeId);
    }

    /**
     * Configured "poor" security-score label (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingScoreLabelPoor(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SCORE_LABEL_POOR, $storeId);
    }

    /**
     * Configured "fair" security-score label (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingScoreLabelFair(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SCORE_LABEL_FAIR, $storeId);
    }

    /**
     * Configured "good" security-score label (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingScoreLabelGood(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SCORE_LABEL_GOOD, $storeId);
    }

    /**
     * Configured "excellent" security-score label (may be empty; the branding provider applies a fallback).
     */
    public function getBrandingScoreLabelExcellent(?int $storeId = null): string
    {
        return $this->string(self::PATH_BRANDING_SCORE_LABEL_EXCELLENT, $storeId);
    }

    // endregion

    // region Login Design

    public function getLoginDesignLayout(?int $storeId = null): string
    {
        $value = $this->string(self::PATH_LOGIN_DESIGN_LAYOUT, $storeId);

        return $value !== '' ? $value : \FalconMedia\AdminPasskey\Model\Config\Source\LoginDesignLayout::SPOTLIGHT;
    }

    public function getLoginEnvironmentBadge(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_ENVIRONMENT, $storeId);
    }

    public function getLoginPasskeyHeadline(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_PASSKEY_HEADLINE, $storeId);
    }

    public function getLoginPasskeyDescription(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_PASSKEY_DESCRIPTION, $storeId);
    }

    public function getLoginPasskeyButtonLabel(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_PASSKEY_BUTTON, $storeId);
    }

    public function getLoginSignInTitle(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_SIGN_IN_TITLE, $storeId);
    }

    public function getLoginSignInSubtitle(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_SIGN_IN_SUBTITLE, $storeId);
    }

    public function getLoginPasskeySubtitle(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_PASSKEY_SUBTITLE, $storeId);
    }

    public function getLoginPasswordTwoFaNotice(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_PASSWORD_TWO_FA, $storeId);
    }

    public function getLoginSplitBrandHeadline(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_SPLIT_BRAND_HEADLINE, $storeId);
    }

    public function getLoginCommandAuthLabel(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_COMMAND_AUTH_LABEL, $storeId);
    }

    public function getLoginCommandFooterText(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_COMMAND_FOOTER, $storeId);
    }

    /**
     * Stored filename of the uploaded light-mode stage image, or '' when none.
     */
    public function getLoginCommandStageImageLight(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_COMMAND_IMAGE_LIGHT, $storeId);
    }

    /**
     * Stored filename of the uploaded dark-mode stage image, or '' when none.
     */
    public function getLoginCommandStageImageDark(?int $storeId = null): string
    {
        return $this->string(self::PATH_LOGIN_COMMAND_IMAGE_DARK, $storeId);
    }

    // endregion

    // region Email Templates

    public function getReminderEmailTemplate(?int $storeId = null): string
    {
        return $this->string(self::PATH_EMAIL_REMINDER, $storeId);
    }

    public function getLockoutEmailTemplate(?int $storeId = null): string
    {
        return $this->string(self::PATH_EMAIL_LOCKOUT, $storeId);
    }

    public function getRecoveryEmailTemplate(?int $storeId = null): string
    {
        return $this->string(self::PATH_EMAIL_RECOVERY, $storeId);
    }

    public function getDiagnosticsEmailTemplate(?int $storeId = null): string
    {
        return $this->string(self::PATH_EMAIL_DIAGNOSTICS, $storeId);
    }

    public function getSupportEmailTemplate(?int $storeId = null): string
    {
        return $this->string(self::PATH_EMAIL_SUPPORT, $storeId);
    }

    public function getSecurityAlertEmailTemplate(?int $storeId = null): string
    {
        return $this->string(self::PATH_EMAIL_SECURITY_ALERT, $storeId);
    }

    // endregion

    // region Developer Options

    public function isDebugLoggingEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::PATH_DEVELOPER_DEBUG_LOGGING, $storeId);
    }

    // endregion

    // region Internal helpers

    private function flag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag($path, $this->scope($storeId), $storeId);
    }

    private function int(string $path, ?int $storeId): int
    {
        return (int) $this->scopeConfig->getValue($path, $this->scope($storeId), $storeId);
    }

    private function string(string $path, ?int $storeId): string
    {
        return (string) $this->scopeConfig->getValue($path, $this->scope($storeId), $storeId);
    }

    private function scope(?int $storeId): string
    {
        return $storeId === null ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORE;
    }

    // endregion
}
