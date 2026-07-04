# Installation

## Composer

```bash
composer require falconmedia/magento2-admin-passkey
bin/magento module:enable FalconMedia_AdminPasskey
bin/magento setup:upgrade
bin/magento cache:flush
```

## Post-install checklist

1. Open **Stores → Configuration → Security → Admin Passkey**.
2. Confirm **Enable Admin Passkey** is set to **Yes** (default after install).
3. Set [Login Page Language](general.md#login-page-language) — default **Auto-detect (browser)** is fine for most installs.
4. Review [WebAuthn](webauthn.md) settings — leave RP ID and origin empty to derive them from the store base URL unless you run Admin on a dedicated host.
5. Register a passkey via [Passkey setup wizard](passkey-setup-wizard.md) or **System → My Account**.
6. Optionally enable [Onboarding](onboarding.md) to require passkey registration for admins without one.

## ACL

Grant administrators access to the relevant resources under **System → Permissions → User Roles**:

- `FalconMedia_AdminPasskey::config` — configuration
- `FalconMedia_AdminPasskey::admin_passkey` — parent for reports/system pages
- Individual resources for audit log, lockouts, health, security score, diagnostics, trusted devices, migration, recovery

See [Admin reports](admin-reports.md) for the full menu structure.
