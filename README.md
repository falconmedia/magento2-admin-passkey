# FalconMedia_AdminPasskey

Enterprise-grade Magento 2 **Admin security suite**. It adds passkey (WebAuthn)
login for the Magento Admin, with username/password and `Magento_TwoFactorAuth`
fallback preserved, plus operational tooling: trusted devices, lockouts,
emergency recovery, migration and health dashboards, diagnostics, a security
score engine, a configurable dashboard widget, white-label branding, full i18n,
and audit logging.

This directory currently contains the **module foundation only** (workflow
Step 4): registration, ACL, System Configuration, a typed `ConfigProvider`, and
the i18n base. Behavioural features are implemented in later workflow steps.

## Scope

**Admin UI only.** Passkeys are used exclusively for Admin UI login and Admin UI
confirmations.

### Out of scope (by design)

- REST API authentication
- GraphQL authentication
- SOAP authentication
- Integration tokens
- OAuth
- CLI authentication
- Frontend customer login
- Customer passkeys

API-related extra verification should use OTP / Magento 2FA, not passkeys.

## Requirements

- Magento 2.4.7+
- PHP 8.3 (compatibility targets: 8.4, 8.5)
- `Magento_Backend`, `Magento_TwoFactorAuth`

## Configuration

All settings live under **Stores → Configuration → Security → Admin Passkey**
(section `adminpasskey`), protected by the `FalconMedia_AdminPasskey::config`
ACL resource. Configuration groups:

General, Authentication Policy, Onboarding, Trusted Devices, Lockout, Recovery,
Migration Dashboard, Security Dashboard Widget, Security Score, Health Check,
Diagnostics, Cleanup, Fail2Ban, White Label & Branding, Email Templates,
Developer Options.

Read configuration in code through the typed service
`FalconMedia\AdminPasskey\Model\Config\ConfigProvider` — never read scope config
paths directly in feature code.

## Installation

```bash
bin/magento module:enable FalconMedia_AdminPasskey
bin/magento setup:upgrade
bin/magento cache:flush
```

> The enable / upgrade step is executed as a separate workflow step (Step 5); do
> not run it as part of creating the foundation.

## Quality gates & tests

All commands are run from the **Magento project root**.

### Unit tests

Module-scoped runner (reuses the Magento unit-test bootstrap):

```bash
php vendor/bin/phpunit -c app/code/FalconMedia/AdminPasskey/phpunit.xml.dist
```

Or via the global unit runner:

```bash
php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/FalconMedia/AdminPasskey/Test/Unit
```

### Integration tests

Run with the global Magento integration runner (requires a configured
`dev/tests/integration/etc/install-config-mysql.php`):

```bash
php vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/FalconMedia/AdminPasskey/Test/Integration
```

> Note: this project's integration test environment is known-broken, so the
> integration tests are written but not executed here.

### PHPStan (static analysis)

```bash
php vendor/bin/phpstan analyse -c app/code/FalconMedia/AdminPasskey/phpstan.neon.dist
```

### PHPCS (Magento coding standard)

```bash
php vendor/bin/phpcs --standard=app/code/FalconMedia/AdminPasskey/phpcs.xml.dist app/code/FalconMedia/AdminPasskey
```

Auto-fix fixable violations with `phpcbf`:

```bash
php vendor/bin/phpcbf --standard=app/code/FalconMedia/AdminPasskey/phpcs.xml.dist app/code/FalconMedia/AdminPasskey
```

## License

MIT. Copyright (c) FalconMedia. See [LICENSE](LICENSE).
