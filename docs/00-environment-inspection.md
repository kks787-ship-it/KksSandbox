# 00 — Development Environment Inspection

**Current status: Gateway inspection and sandbox configuration hardening completed, as
reported by the owner on October 31, 2026.** The three constants below are verified. Do not
reapply them. Full sandbox backup, isolated scratch restoration, source staging and deployment
dry run remain incomplete; no installation is authorized. This replaces the prior
not-yet-inspected/pending-hardening snapshot without changing the historical test reports.

## Observed sandbox identity

Source: owner-forwarded Gateway SSH reports from serve1.rainierserve.com; not independent
VPS commands executed by this documentation task. Full evidence and remaining limits are in
[the current preflight report](test-results/sandbox-preflight.md).

| Item | Gateway-reported observation |
|---|---|
| URL | https://sandbox.cornerstonefarmomak.com |
| Plesk-relative root | sandbox.cornerstonefarmomak.com |
| Absolute/canonical root | `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com` |
| Configured and connected DB | wp_mdf4x; read-only inspection tx_read_only=1 |
| Options table | TXGEh_options |
| Stored home/siteurl | Both sandbox HTTPS URL |
| WordPress | 7.1.4; core/database schema versions match |
| Domain PHP | 8.3.36; plesk-php83-fpm; required extensions present |
| MariaDB | 10.6.23, default InnoDB |
| Kadence | 1.5.2, active parent and stylesheet |
| HTTPS/access | Let's Encrypt, TLS 1.3, certificate expiry January 29, 2027; HTTPS redirect 301; password challenge 401 |
| Search indexing | Disabled, blog_public=0 |
| WP_ENVIRONMENT_TYPE | development — applied and effective value verified |
| DISALLOW_FILE_EDIT | true — applied and effective value verified |
| WP_DEBUG_DISPLAY | false — applied and effective value verified |
| Config validation | PHP syntax passed; ownership preserved/verified, live file mode 0600 |
| Permalinks | Plain in latest supplied inspection; no later change reported |

## Completed configuration backup and hardening

Gateway backed up the original sandbox wp-config **before** editing it on October 31, 2026:

```text
/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak
```

Retain this protected configuration-only artifact as recovery evidence. Do not expose its
contents, overwrite it or call it a full site/database backup. Gateway then applied the three
constants above and verified syntax/effective WordPress values/ownership/0600 permissions.
The owner reports production was untouched. No repeat configuration change is needed.

## Excluded resources

Production: cornerstonefarmomak.com, root
`/var/www/vhosts/cornerstonefarmomak.com/httpdocs`, database wp_iacb7. Unassigned DB wp_mdoaq
is also excluded and must not be reused for the scratch restore.

## Remaining work

After separate approval, begin with a **full sandbox-only current files/config/DB backup**,
then isolated restoration into a new scratch database, private reviewed source staging and
exact-target deployment dry run. Follow [the revised runbook](05-backup-rollback-deploy.md#sandbox-preparation-approval-package).
No plugin installation/activation, permalink changes or production deployment are authorized.
Remaining functional acceptance includes actual Kadence rendering, cache/rewrites and recovery
verification. Stored URLs are known; WP_HOME/WP_SITEURL override findings are still unsupplied.

## Future inspection procedure

Use existing approved tooling and the exact target; recheck identities without reapplying
verified values. scripts/sandbox-preflight.py uses pre-WordPress-load config/DB operations;
its current strict permalink check will stop while URLs remain plain. Do not weaken guards.
The general inspector/preflight helpers may bootstrap must-use plugins/drop-ins; select the
safe non-bootstrap method when runtime side effects are outside scope. Review custom
wp-config code before evaluating it. Never print credentials, salts or the process environment.

Git, WP-CLI and rsync are already available per Gateway. Their availability is not evidence
that code has been staged or a deployment dry run completed. Preserve actual outputs and
label unknown/unrun checks. No full backup, restore, staging or VPS change was executed by
this documentation reconciliation.
