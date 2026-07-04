# FalconMedia_AdminPasskey - Functional & Security Test Notes

This directory holds **release-readiness test documentation**: the end-to-end
(browser/MFTF) scenario skeletons and the security-regression matrix.

## Why notes instead of runnable MFTF/browser tests

WebAuthn ceremonies require a **real browser with an authenticator** (platform
passkey, roaming key, or a virtual authenticator via CDP). They cannot be
asserted headlessly in this project's current environment, and the integration/
MFTF runners are not available here. Per the module's testing rules these flows
are therefore captured as **manual/automatable skeletons** (Given/When/Then) with
the exact routes, ACL resources, config paths and services to touch, rather than
as green-but-fake automated tests.

Everything that is **pure logic** (challenge lifecycle, signature verification,
COSE/DER handling, ownership checks, tokenisation, formatters, filter parsing,
adoption stats) is covered by real unit tests under `Test/Unit/` and runs in the
unit gate.

## Files

| File | Contents |
|------|----------|
| `FUNCTIONAL-TEST-PLAN.md` | E2E scenario skeletons for the 12 required flows. |
| `SECURITY-REGRESSION.md` | Security-regression matrix mapping each threat to its unit test or a manual/integration procedure. |

## How to automate later

- **MFTF:** add `Test/Mftf/Test/*.xml` per scenario; use a Chrome virtual
  authenticator (`Emulation.setAutomationOverride` / WebDriver
  `POST /session/:id/webauthn/authenticator`) to register/assert credentials.
- **Integration:** the flows marked *(integration)* below can be written against
  the global `dev/tests/integration` runner (do **not** run here).
