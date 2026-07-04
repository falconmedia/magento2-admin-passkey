# Changelog

All notable changes to `falconmedia/magento2-admin-passkey` are documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.1] - 2026-07-04

### Added

- **Login Page Language** — configure the Admin login page locale before sign-in:
  - *Auto-detect (browser)* matches `Accept-Language` against deployed backend locales, falling back to `en_US`
  - Force any deployed backend locale (same list as Interface Locale)
- `Model/Config/Source/LoginLanguage` and `Plugin/Backend/Locale/LoginLocale` for login-page locale resolution
- Bundled default **Image Deck** stage artwork (`passkey-stage-light.png`, `passkey-stage-dark.png`)
- Full documentation in `docs/` with screenshots in `assets/`

### Changed

- **Login Page Design** configuration consolidated:
  - Shared copy fields (headline, description, passkey button label, sign-in title/subtitle, passkey button subtitle, password 2FA notice) live on **Login Page Design**
  - **Spotlight Layout Content** subsection removed — Spotlight uses shared fields only
  - **Split Console Layout Content** reduced to brand headline only
  - **Image Deck Layout Content** — stage images, authentication label, footer text
- Login templates and CSS updated for Image Deck and Split Console layouts
- i18n strings updated across `en_US`, `nl_NL`, `de_DE`, `es_ES`, `fr_FR`, `it_IT`
- README and composer description updated for enterprise positioning

### Upgrade notes

```bash
composer update falconmedia/magento2-admin-passkey
bin/magento setup:upgrade
bin/magento cache:flush
```

Review **Stores → Configuration → Security → Admin Passkey → General → Login Page Language** after upgrade.

## [1.0.0] - 2026-07-04

### Added

Initial release — enterprise Magento 2 Admin security suite:

- Passkey (WebAuthn) login for Admin UI with username/password and Magento 2FA fallback
- Three login layouts: Spotlight, Split Console, Image Deck
- Passkey setup wizard and My Account passkey management
- Trusted devices, lockouts, emergency recovery
- Migration dashboard, security dashboard widget, security score engine
- Health check, diagnostics, scheduled cleanup, Fail2Ban logging
- White-label branding, email templates, audit logging
- CLI commands (`adminpasskey:*`)
- Admin reports and system menu pages

[1.0.1]: https://github.com/falconmedia/magento2-admin-passkey/releases/tag/v1.0.1
[1.0.0]: https://github.com/falconmedia/magento2-admin-passkey/releases/tag/v1.0.0
