# Admin Reports & System Pages

Admin UI pages outside Stores → Configuration. ACL resources listed per page.

## Reports → Admin Passkey

Parent menu under **Reports**. Requires `FalconMedia_AdminPasskey::admin_passkey`.

### Audit Log

**Reports → Admin Passkey → Audit Log**  
ACL: `FalconMedia_AdminPasskey::audit_log`

Searchable grid of security events: passkey registration, revocation, login success/failure, lockouts, recovery mode changes, configuration changes, and related actions.

Export via **CLI**: `bin/magento adminpasskey:audit:export`. See [CLI commands](cli-commands.md).

Retention: [Cleanup](cleanup.md) → Audit Log Retention (default 365 days).

### Lockouts

**Reports → Admin Passkey → Lockouts**  
ACL: `FalconMedia_AdminPasskey::lockouts`

Currently locked administrator accounts. Manual unlock from the grid or via CLI. See [Lockout](lockout.md).

### Health Check

**Reports → Admin Passkey → Health Check**  
ACL: `FalconMedia_AdminPasskey::health`

Interactive health check results. See [Health check](health-check.md).

### Security Score

**Reports → Admin Passkey → Security Score**  
ACL: `FalconMedia_AdminPasskey::security_score`

Score breakdown and history. See [Security score](security-score.md).

### Diagnostics

**Reports → Admin Passkey → Diagnostics**  
ACL: `FalconMedia_AdminPasskey::diagnostics`

Generate and download support bundles. See [Diagnostics](diagnostics.md).

## System → Admin Passkey

Parent menu under **System**. Requires `FalconMedia_AdminPasskey::admin_passkey`.

### Trusted Devices

**System → Admin Passkey → Trusted Devices**  
ACL: `FalconMedia_AdminPasskey::trusted_devices`

Per-admin trusted browser list. See [Trusted devices](trusted-devices.md).

### Migration Dashboard

**System → Admin Passkey → Migration Dashboard**  
ACL: `FalconMedia_AdminPasskey::migration`

Adoption tracking and reminder actions. See [Migration dashboard](migration-dashboard.md).

### Data Cleanup

**System → Admin Passkey → Data Cleanup**  
ACL: `FalconMedia_AdminPasskey::diagnostics` (shared with diagnostics)

Retention overview and manual cleanup trigger. See [Cleanup](cleanup.md).

### Recovery

**System → Admin Passkey → Recovery**  
ACL: `FalconMedia_AdminPasskey::recovery`

Enable or monitor emergency recovery mode. See [Recovery](recovery.md).

## System → My Account

Passkey management is embedded in the standard account page — not a separate menu item. See [My Account passkeys](my-account-passkeys.md).

## Dashboard widget

The [Security dashboard widget](security-dashboard-widget.md) appears on the main Admin dashboard home, not under these menus.

## Configuration

All Stores → Configuration groups are documented individually under [Documentation index](README.md#documentation-index).
