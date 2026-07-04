# Admin Login

End-user experience for signing in to the Magento Admin with FalconMedia Admin Passkey enabled.

## Overview

The module replaces the default Admin login page with a custom layout supporting:

- **Passkey (WebAuthn)** — passwordless, usernameless sign-in via biometrics or a hardware security key
- **Password fallback** — traditional username and password, optionally followed by Magento 2FA

Behaviour is controlled by [Authentication policy](authentication-policy.md).

## Login page language

Before sign-in, all login labels (tab names, field labels, buttons) follow [General → Login Page Language](general.md#login-page-language):

- **Auto-detect (browser)** — matches the visitor's browser language against deployed backend locales, falling back to `en_US`.
- **Forced locale** — always shows the selected language regardless of browser settings.

After sign-in, each admin uses their own **Interface Locale** from My Account. Login page language and interface locale are independent.

## Layouts

Three visual layouts are available. See the dedicated pages for configuration and screenshots:

| Layout | Documentation |
|--------|---------------|
| Spotlight | [Spotlight layout](spotlight-layout.md) |
| Split Console | [Split Console layout](split-console-layout.md) |
| Image Deck | [Image Deck layout](image-deck-layout.md) |

## Passkey sign-in

1. Open the Admin URL (`/admin` or your custom admin path).
2. On the **Passkey** tab (Spotlight) or passkey button (Split Console / Image Deck), click **Continue with passkey** (or the configured label).
3. Complete the browser or OS WebAuthn prompt (Touch ID, Face ID, Windows Hello, security key, etc.).
4. You are signed in — Magento 2FA is **not** required after a successful passkey login.

No username is required when discoverable passkeys are enabled ([WebAuthn](webauthn.md) → Resident Key: Preferred or Required).

## Password sign-in

1. Switch to the **Password** tab or scroll to the credentials section.
2. Enter username and password.
3. If [Enable Magento 2FA for password login](authentication-policy.md) is **Yes**, complete the Magento 2FA step on the next screen.

The 2FA notice on the login form is configurable under [Login page design](login-page-design.md) → Password 2FA Notice.

## Theme toggle

The login page includes a light / system / dark theme switcher in the top-right corner. Theme preference is stored in the browser.

## Environment badge

When Magento runs in developer mode, a **Developer** badge appears. Override the label via [Login page design](login-page-design.md) → Environment badge.

## After login

If [Onboarding](onboarding.md) is enabled and the admin has no passkey, they are redirected to the [Passkey setup wizard](passkey-setup-wizard.md).

Registered passkeys can be managed under **System → My Account**. See [My Account passkeys](my-account-passkeys.md).

## Lockouts

Repeated failed login attempts trigger [Lockout](lockout.md). Locked accounts can be reviewed under **Reports → Admin Passkey → Lockouts**.
