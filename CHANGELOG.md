# Changelog

All notable changes to `falconmedia/magento2-admin-passkey` are documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.1.0] - 2026-11-02

### Security

- Passkey login now applies Magento's own account checks: role assigned, native `lock_expires`, Magento_Security user expiration and the module's account lockout. It records the login (logdate/lognum), flags expired passwords and registers the Magento_Security `admin_user_session` (including "terminate other sessions"). (H1, H2)
- Passkey registration requires re-entering the current Admin password (single-use step-up, max. 5 minutes). Wrong passwords count towards the account lockout. Every new passkey sends a security-alert email to the account owner. (H3)
- A passkey only replaces Magento 2FA when the authenticator performed user verification (PIN/biometrics). Without UV, Magento 2FA still follows. (H4)
- WebAuthn challenges are consumed with an atomic conditional `UPDATE`, and the sign counter is updated conditionally, so a concurrent replay can no longer succeed twice. (M1)
- "Password fallback: No" is enforced on the server (plugins on `Magento\Backend\Model\Auth::login()` and `Magento\User\Model\User::authenticate()`, which also covers the admin REST token endpoints), with an exception for recovery mode. (M2)
- `general/enabled = 0` now also disables the passkey login and registration endpoints. (M3)
- Uploaded SVG branding assets are rebuilt by the allowlist sanitizer `enshrined/svg-sanitize` and checked again on entity-decoded markup. Non-UTF-8 SVGs, `<style>`, `data:` and external references are rejected. (M4)
- Failed attempts are counted in separate rows per account, username and IP. A successful login only releases the rows of the signed-in account, never an IP row. The username-lockout trade-off is documented. (M5)

### Changed

- `composer.json` no longer contains a `version` field; the version comes from the git tag.
- `composer.json` now requires `2tvenom/cborencode`, `enshrined/svg-sanitize`, `magento/module-config`, `magento/module-store`, `magento/module-media-storage` and `magento/module-security`. (M6)
- `AdminSessionCreatorInterface::login()` takes a second argument `$userVerified`, and `CredentialRepositoryInterface` gains `updateSignCount()`.

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

[Unreleased]: https://github.com/falconmedia/magento2-admin-passkey/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/falconmedia/magento2-admin-passkey/releases/tag/v1.1.0
[1.0.1]: https://github.com/falconmedia/magento2-admin-passkey/releases/tag/v1.0.1
[1.0.0]: https://github.com/falconmedia/magento2-admin-passkey/releases/tag/v1.0.0
