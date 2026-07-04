# Security Regression Matrix

Each row maps a security guarantee to its **existing real unit test** (runs in the
unit gate) or to a **manual/integration** procedure where browser/HTTP context is
required. WebAuthn vectors/helpers live in `Test/Unit/Model/WebAuthn/`
(`WebAuthnTestVectors`, `InMemoryCredential`).

| # | Threat / guarantee | Coverage | Where |
|---|--------------------|----------|-------|
| 1 | **Replay protection** (challenge single-use / consumed) | Unit | `Test/Unit/Model/WebAuthn/ChallengeGuardTest.php` (consumed challenge rejected) |
| 2 | **Invalid signatures** rejected | Unit | `Test/Unit/Model/WebAuthn/SignatureVerifierTest.php`, `AssertionVerificationServiceTest.php` |
| 3 | **Expired challenges** rejected | Unit | `ChallengeGuardTest.php` (expiry), `ChallengeIssuerTest.php` (TTL from `challenge_lifetime_seconds`) |
| 4 | **Origin / rpIdHash binding** | Unit | `ClientDataParserTest.php`, `AuthenticatorDataParserTest.php`, `RegistrationVerificationServiceTest.php` |
| 5 | **Concurrent registration** (no duplicate/racey credential) | Unit + integration | `CredentialDescriptorsTest.php` (`excludeCredentials` populated); *(integration: unique index on credential id)* |
| 6 | **Concurrent login** (sign-counter regression) | Unit + integration | counter handling in `AssertionVerificationServiceTest.php`; *(integration: two parallel assertions)* |
| 7 | **Trusted-device validation** (token integrity + expiry) | Unit | `TrustedDeviceTokenizerTest.php`, `TrustedDeviceExpiryEvaluatorTest.php`, `SuccessfulLoginTrustPolicyTest.php` |
| 8 | **Privilege escalation / ownership** | Unit | `Test/Unit/Model/Passkey/CredentialAccessValidatorTest.php` (own vs others' credentials) |
| 9 | **ACL enforcement** on admin controllers | Manual/integration | every controller declares `ADMIN_RESOURCE = FalconMedia_AdminPasskey::*` (see `Controller/Adminhtml/*`); verify 403 without the ACL |
| 10 | **CSRF (form key)** on state-changing admin POSTs | Manual/integration | Magento admin form-key middleware; verify POST without a valid form key is rejected |
| 11 | **XSS escaping** in templates/grids | Manual/integration | `.phtml` use `$escaper`; branding SVG sanitised by `SvgSanitizer` (`Test/Unit/Model/Branding/SvgSanitizerTest.php`) |
| 12 | **Diagnostics sanitisation** (no secrets in bundle/logs) | Unit | `Test/Unit/Model/Diagnostics/LogSanitizerTest.php`, `ManifestBuilderTest.php` |
| 13 | **Audit metadata redaction** | Unit | `Test/Unit/Model/Audit/AuditLoggerTest.php` (sensitive keys redacted) |
| 14 | **Brute force / lockout** thresholds | Unit + integration | `Test/Unit/Model/Lockout/LockoutEvaluatorTest.php`; *(integration: end-to-end failed-attempt accrual)* |
| 15 | **Recovery cannot bypass audit** | Unit | `Test/Unit/Model/Recovery/RecoveryStateTransitionEvaluatorTest.php` |

## Notes

- Items marked **Manual/integration** are framework-level guarantees (form key,
  ACL, escaper) that are asserted at the HTTP/controller layer and are not
  meaningfully testable as isolated unit tests; automate them via MFTF or the
  integration runner (do **not** run here).
- No new pure-logic gaps were found for items 1-8: each already has a real unit
  test reusing the shared WebAuthn vectors/helpers. New unit tests added in this
  step cover the CLI formatter, audit filter parsing, and adoption stats.
