# FalconMedia Admin Passkey — Documentation

Magento 2 Admin security suite with WebAuthn passkey login, password and Magento 2FA fallback, plus operational tooling for trusted devices, lockouts, recovery, migration, health checks, diagnostics, and audit logging.

**Scope:** Admin UI only. Passkeys are not used for storefront, REST, GraphQL, SOAP, OAuth, integration tokens, or CLI authentication.

## Requirements

- Magento 2.4.7+
- PHP 8.3+
- `Magento_Backend`, `Magento_TwoFactorAuth`

## Documentation index

### Getting started

| Topic | Description |
|-------|-------------|
| [Installation](installation.md) | Composer install and module enablement |
| [General](general.md) | Master enable switch and login page language |

### Login & passkeys

| Topic | Description |
|-------|-------------|
| [Authentication policy](authentication-policy.md) | Passkey-first, password fallback, 2FA interaction |
| [Login page design](login-page-design.md) | Layout selection, shared copy fields, layout subsections |
| [Spotlight layout](spotlight-layout.md) | Default centered card layout |
| [Split Console layout](split-console-layout.md) | Branded side panel with credentials form |
| [Image Deck layout](image-deck-layout.md) | Stage panel with custom background art |
| [Admin login](admin-login.md) | End-user login experience (all layouts) |
| [WebAuthn](webauthn.md) | Relying party and ceremony settings |
| [Onboarding](onboarding.md) | Mandatory setup wizard after login |
| [Passkey setup wizard](passkey-setup-wizard.md) | Registration flow for new passkeys |
| [My Account passkeys](my-account-passkeys.md) | Manage, rename, and revoke passkeys |

### Security & operations

| Topic | Description |
|-------|-------------|
| [Trusted devices](trusted-devices.md) | Remember browsers after repeated successful logins |
| [Lockout](lockout.md) | Brute-force throttling |
| [Recovery](recovery.md) | Emergency fallback when passkeys block access |
| [Migration dashboard](migration-dashboard.md) | Track passkey adoption across admins |
| [Security dashboard widget](security-dashboard-widget.md) | Dashboard cards and refresh interval |
| [Security score](security-score.md) | Weighted 0–100 score engine |
| [Health check](health-check.md) | Environment sanity checks |
| [Diagnostics](diagnostics.md) | Support report generation |
| [Cleanup](cleanup.md) | Data retention and scheduled cleanup |
| [Fail2Ban](fail2ban.md) | Parseable log output for Fail2Ban |
| [Admin reports](admin-reports.md) | Reports and System menu pages |

### Branding & templates

| Topic | Description |
|-------|-------------|
| [White label & branding](white-label-branding.md) | Company name, colours, logo, support links |
| [Email templates](email-templates.md) | Transactional email selection |

### Developer

| Topic | Description |
|-------|-------------|
| [Developer options](developer-options.md) | Debug logging (non-production) |
| [CLI commands](cli-commands.md) | `bin/magento adminpasskey:*` reference |

## Configuration location

All settings: **Stores → Configuration → Security → Admin Passkey**  
ACL resource: `FalconMedia_AdminPasskey::config`

Screenshots live in [`../assets/`](../assets/).
