# Functional / E2E Test Plan (skeletons)

Each scenario is a **skeleton** to be automated in MFTF or a browser matrix
(Chrome/Edge/Safari/Firefox × Windows/macOS/iOS/Android). WebAuthn steps assume a
virtual authenticator. Routes are under the admin area; ACL resources are the
`FalconMedia_AdminPasskey::*` set from `etc/acl.xml`.

Legend: **(browser)** needs a real authenticator; **(integration)** can be a
Magento integration test.

---

## 1. Onboarding (require passkey) (browser)
- **Given** `adminpasskey/onboarding/require_passkey_onboarding=1` and an admin
  with no active passkey.
- **When** the admin logs in with password (+2FA).
- **Then** `Observer\RequirePasskeyOnboarding` redirects to the wizard
  (`adminpasskey/onboarding/*`); the admin cannot reach other admin pages until a
  passkey is registered.
- **And** after registration an audit event `passkey_registration` exists.

## 2. 2FA fallback (browser/integration)
- **Given** `adminpasskey/authentication_policy/two_fa_fallback_enabled=1` and
  Magento 2FA configured for the admin.
- **When** the admin chooses password login.
- **Then** native `Magento_TwoFactorAuth` challenge is enforced after password;
  passkey layer does not bypass it.

## 3. Passkey login (browser)
- **Given** an admin with an active credential and `passkey_first_login=1`.
- **When** the admin completes the assertion ceremony on the login screen.
- **Then** a session is created via `Model\Auth\AdminSessionCreator`
  (`backend_auth_user_login_success` dispatched) and an audit event
  `passkey_login` is written.

## 4. Password fallback (browser/integration)
- **Given** `password_fallback_enabled=1`.
- **When** the admin uses username/password instead of a passkey.
- **Then** login succeeds (subject to 2FA) and audit event `password_login` is
  written.

## 5. Emergency recovery (integration)
- **Given** recovery enabled in config.
- **When** an admin enables recovery (UI `FalconMedia_AdminPasskey::recovery`) or
  CLI, then disables it (`adminpasskey:recovery:disable`).
- **Then** `recovery_enable` / `recovery_disable` audit events exist; while active
  passkey enforcement/lockouts are relaxed (`RecoveryModeService::isActive()`).

## 6. Lockout & unlock (integration)
- **Given** `adminpasskey/lockout/*` thresholds.
- **When** failed attempts exceed `max_failed_attempts` within
  `attempt_window_minutes`.
- **Then** a lockout row (status `active`) is created and login is blocked
  (`Plugin\Backend\Auth\BlockLockedAdminLogin`).
- **When** an admin runs `adminpasskey:lockouts:unlock <id|username>` (or the UI).
- **Then** the lockout is released (status `released`) with an `unlock` audit
  event.

## 7. Trusted devices (browser/integration)
- **Given** `adminpasskey/trusted_devices/enabled=1`.
- **When** an admin logs in successfully `successful_logins_before_trust` times.
- **Then** a trusted-device row is created; it expires after `lifetime_days`
  (`TrustedDeviceExpiryEvaluator`) and can be revoked in the UI.

## 8. Super-admin revoke others' passkeys (integration)
- **Given** an admin with `FalconMedia_AdminPasskey::passkeys_manage_others`.
- **When** they revoke another admin's credential.
- **Then** `CredentialAccessValidator` permits it, credential status becomes
  revoked, `passkey_revoke` audit event exists. A user **without** that ACL is
  denied.

## 9. Migration dashboard (integration)
- **Given** admins with mixed passkey/2FA state.
- **When** loading `FalconMedia_AdminPasskey::migration`.
- **Then** the grid (`MigrationRowAssembler`) shows per-admin passkey/2FA/lockout
  columns; totals match `adminpasskey:migration:status`.

## 10. Health check (integration)
- **When** loading `FalconMedia_AdminPasskey::health` or running
  `adminpasskey:health`.
- **Then** the same `HealthReport` is rendered; the CLI exits non-zero if any
  check is `error`.

## 11. System configuration (integration)
- **When** an admin edits `adminpasskey/*` under Stores > Configuration.
- **Then** values persist and are read via `Model\Config\ConfigProvider`;
  a `config_change` audit event may be recorded.

## 12. Dashboard widget (integration)
- **Given** `adminpasskey/security_dashboard_widget/enabled=1`.
- **When** loading the Admin dashboard.
- **Then** the security cards render via `DashboardCardAssembler`, each gated by
  its `card_*` config toggle and ACL; disabled cards are hidden.
