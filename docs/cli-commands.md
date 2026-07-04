# CLI Commands

All commands run from the Magento project root:

```bash
bin/magento <command>
```

## Reference

| Command | Description |
|---------|-------------|
| `adminpasskey:health` | Run health checks; non-zero exit on critical failure |
| `adminpasskey:score` | Print current security score and breakdown |
| `adminpasskey:migration:status` | Passkey adoption statistics |
| `adminpasskey:lockouts:list` | List locked administrator accounts |
| `adminpasskey:lockouts:unlock` | Unlock an account (`--username` or `--user-id`) |
| `adminpasskey:recovery:status` | Show whether emergency recovery is active |
| `adminpasskey:recovery:disable` | Disable recovery mode immediately |
| `adminpasskey:diagnostics:generate` | Generate a diagnostics ZIP report |
| `adminpasskey:audit:export` | Export audit log to CSV (filters via options) |
| `adminpasskey:cleanup` | Run data retention cleanup immediately |

Run `bin/magento <command> --help` for available options on each command.

## Examples

```bash
# Deploy smoke test
bin/magento adminpasskey:health

# Unlock admin after lockout
bin/magento adminpasskey:lockouts:unlock --username=jsmith

# Export last 30 days of audit events
bin/magento adminpasskey:audit:export --days=30 --output=/tmp/audit.csv

# Check adoption before disabling password fallback
bin/magento adminpasskey:migration:status
```

## Automation

- Use `adminpasskey:health` in deploy pipelines after `setup:upgrade`.
- Pipe `adminpasskey:score` to monitoring when you want alerting below a threshold.
- Schedule is **not** required for cleanup — the [Cleanup](cleanup.md) cron handles retention; `adminpasskey:cleanup` is for manual runs only.

## Related topics

- [Health check](health-check.md)
- [Lockout](lockout.md)
- [Recovery](recovery.md)
- [Diagnostics](diagnostics.md)
- [Cleanup](cleanup.md)
