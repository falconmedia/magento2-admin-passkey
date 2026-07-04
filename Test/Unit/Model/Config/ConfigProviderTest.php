<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Config;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the typed configuration accessor.
 *
 * ScopeConfigInterface is a framework boundary, so mocking it here is correct:
 * the test asserts that every accessor reads the expected config path, in the
 * expected scope, and returns a correctly typed value.
 */
class ConfigProviderTest extends TestCase
{
    private const PREFIX = 'adminpasskey/';

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface&MockObject $scopeConfig;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->configProvider = new ConfigProvider($this->scopeConfig);
    }

    /**
     * @dataProvider booleanAccessorProvider
     */
    public function testBooleanAccessorReadsFlagInDefaultScope(string $method, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::PREFIX . $path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn(true);

        $result = $this->configProvider->{$method}();

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function booleanAccessorProvider(): array
    {
        return [
            'general enabled' => ['isEnabled', 'general/enabled'],
            'passkey first login' => ['isPasskeyFirstLogin', 'authentication_policy/passkey_first_login'],
            'password fallback' => ['isPasswordFallbackEnabled', 'authentication_policy/password_fallback_enabled'],
            'two fa fallback' => ['isTwoFaFallbackEnabled', 'authentication_policy/two_fa_fallback_enabled'],
            'onboarding required' => ['isPasskeyOnboardingRequired', 'onboarding/require_passkey_onboarding'],
            'recommend second' => ['isSecondPasskeyRecommended', 'onboarding/recommend_second_passkey'],
            'trusted devices' => ['isTrustedDevicesEnabled', 'trusted_devices/enabled'],
            'lockout' => ['isLockoutEnabled', 'lockout/enabled'],
            'recovery' => ['isRecoveryEnabled', 'recovery/enabled'],
            'migration dashboard' => ['isMigrationDashboardEnabled', 'migration_dashboard/enabled'],
            'migration reminder' => ['isMigrationReminderEmailEnabled', 'migration_dashboard/reminder_email_enabled'],
            'dashboard widget' => ['isDashboardWidgetEnabled', 'security_dashboard_widget/enabled'],
            'security score' => ['isSecurityScoreEnabled', 'security_score/enabled'],
            'health check' => ['isHealthCheckEnabled', 'health_check/enabled'],
            'diagnostics' => ['isDiagnosticsEnabled', 'diagnostics/enabled'],
            'cleanup' => ['isCleanupEnabled', 'cleanup/enabled'],
            'fail2ban' => ['isFail2BanEnabled', 'fail2ban/enabled'],
            'branding' => ['isBrandingEnabled', 'branding/enabled'],
            'debug logging' => ['isDebugLoggingEnabled', 'developer_options/debug_logging'],
        ];
    }

    /**
     * @dataProvider integerAccessorProvider
     */
    public function testIntegerAccessorCastsValue(string $method, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::PREFIX . $path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn('42');

        $result = $this->configProvider->{$method}();

        $this->assertIsInt($result);
        $this->assertSame(42, $result);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function integerAccessorProvider(): array
    {
        return [
            'trusted lifetime' => ['getTrustedDeviceLifetimeDays', 'trusted_devices/lifetime_days'],
            'max failed attempts' => ['getMaxFailedAttempts', 'lockout/max_failed_attempts'],
            'lockout duration' => ['getLockoutDurationMinutes', 'lockout/lockout_duration_minutes'],
            'refresh interval' => ['getDashboardRefreshInterval', 'security_dashboard_widget/refresh_interval'],
            'weight authentication' => ['getSecurityScoreWeightAuthentication', 'security_score/weight_authentication'],
            'weight security' => ['getSecurityScoreWeightSecurity', 'security_score/weight_security'],
            'weight operational' => ['getSecurityScoreWeightOperational', 'security_score/weight_operational'],
            'weight threats' => ['getSecurityScoreWeightThreats', 'security_score/weight_threats'],
            'challenge retention' => ['getChallengeRetentionDays', 'cleanup/challenge_retention_days'],
            'diagnostics retention' => ['getDiagnosticsRetentionDays', 'cleanup/diagnostics_retention_days'],
            'audit retention' => ['getAuditRetentionDays', 'cleanup/audit_retention_days'],
        ];
    }

    public function testIntegerAccessorReturnsZeroWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame(0, $this->configProvider->getMaxFailedAttempts());
    }

    /**
     * @dataProvider defaultedIntegerAccessorProvider
     */
    public function testDefaultedIntegerAccessorReadsConfiguredValue(string $method, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::PREFIX . $path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn('12');

        $result = $this->configProvider->{$method}();

        $this->assertIsInt($result);
        $this->assertSame(12, $result);
    }

    /**
     * @dataProvider defaultedIntegerAccessorProvider
     */
    public function testDefaultedIntegerAccessorFallsBackWhenUnset(
        string $method,
        string $path,
        int $expectedDefault
    ): void {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame($expectedDefault, $this->configProvider->{$method}());
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function defaultedIntegerAccessorProvider(): array
    {
        return [
            'successful logins before trust' => [
                'getSuccessfulLoginsBeforeTrust',
                'trusted_devices/successful_logins_before_trust',
                3,
            ],
            'attempt window minutes' => [
                'getAttemptWindowMinutes',
                'lockout/attempt_window_minutes',
                15,
            ],
            'recovery expiry minutes' => [
                'getRecoveryExpiryMinutes',
                'recovery/expiry_minutes',
                60,
            ],
        ];
    }

    /**
     * @dataProvider stringAccessorProvider
     */
    public function testStringAccessorCastsValue(string $method, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::PREFIX . $path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn('a-string-value');

        $result = $this->configProvider->{$method}();

        $this->assertIsString($result);
        $this->assertSame('a-string-value', $result);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function stringAccessorProvider(): array
    {
        return [
            'recovery warning' => ['getRecoveryWarningText', 'recovery/warning_text'],
            'diagnostics email' => ['getDiagnosticsSupportEmail', 'diagnostics/support_email'],
            'reference prefix' => ['getSupportReferencePrefix', 'diagnostics/support_reference_prefix'],
            'fail2ban log path' => ['getFail2BanLogPath', 'fail2ban/log_path'],
            'branding company' => ['getBrandingCompanyName', 'branding/company_name'],
            'branding support email' => ['getBrandingSupportEmail', 'branding/support_email'],
            'branding support url' => ['getBrandingSupportUrl', 'branding/support_url'],
            'branding documentation url' => ['getBrandingDocumentationUrl', 'branding/documentation_url'],
            'branding privacy url' => ['getBrandingPrivacyUrl', 'branding/privacy_url'],
            'branding footer' => ['getBrandingFooterText', 'branding/footer_text'],
            'branding primary color' => ['getBrandingPrimaryAccentColor', 'branding/primary_accent_color'],
            'branding secondary color' => ['getBrandingSecondaryAccentColor', 'branding/secondary_accent_color'],
            'reminder template' => ['getReminderEmailTemplate', 'email_templates/reminder_template'],
            'lockout template' => ['getLockoutEmailTemplate', 'email_templates/lockout_template'],
            'recovery template' => ['getRecoveryEmailTemplate', 'email_templates/recovery_template'],
            'diagnostics template' => ['getDiagnosticsEmailTemplate', 'email_templates/diagnostics_template'],
        ];
    }

    public function testStringAccessorReturnsEmptyStringWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame('', $this->configProvider->getBrandingCompanyName());
    }

    public function testStoreScopeIsForwardedForFlags(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::PREFIX . 'general/enabled', ScopeInterface::SCOPE_STORE, 7)
            ->willReturn(true);

        $this->assertTrue($this->configProvider->isEnabled(7));
    }

    public function testStoreScopeIsForwardedForValues(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::PREFIX . 'lockout/max_failed_attempts', ScopeInterface::SCOPE_STORE, 3)
            ->willReturn('10');

        $this->assertSame(10, $this->configProvider->getMaxFailedAttempts(3));
    }

    public function testDashboardCardEnabledUsesPrefixedPath(): void
    {
        $path = self::PREFIX . 'security_dashboard_widget/card_login_ratio';
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn(true);

        $this->assertTrue($this->configProvider->isDashboardCardEnabled('login_ratio'));
    }

    public function testGetDashboardCardsReturnsEveryCardKeyedByIdentifier(): void
    {
        $enabledPath = self::PREFIX . 'security_dashboard_widget/card_login_ratio';
        $this->scopeConfig->method('isSetFlag')
            ->willReturnCallback(
                static fn (string $path): bool => $path === $enabledPath
            );

        $cards = $this->configProvider->getDashboardCards();

        $this->assertCount(14, $cards);
        $this->assertArrayHasKey('passkey_adoption', $cards);
        $this->assertArrayHasKey('security_score', $cards);
        $this->assertArrayHasKey('quick_actions', $cards);
        $this->assertTrue($cards['login_ratio']);
        $this->assertFalse($cards['passkey_adoption']);
        foreach ($cards as $enabled) {
            $this->assertIsBool($enabled);
        }
    }
}
