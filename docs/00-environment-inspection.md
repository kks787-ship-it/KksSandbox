# 00 — Development Environment Inspection

**Current status: Gateway completed sandbox infrastructure preparation on October 31, 2026.**
Hardening, full backup, structural/file restore checks, private source staging, exact-target
Plesk preflight and deployment dry run are complete. Do not repeat them. Functional login/
rendering on the isolated restoration is untested. No plugin installation/activation, site
setup, permalink change or production deployment is authorized.

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
| Preflight / dry run | Latest Gateway report: passed; only the two new plugin directories proposed |
| Permalinks | Earlier report: plain; no explicit change reported/authorized. Do not infer one from the passed preflight. |

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

## Completed infrastructure preparation

Gateway's latest completion record states:

- Full sandbox filesystem/config/database backup at
  /var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/; archives/checksums verified.
- DB export October 31, 2026, **10:08:58 AM PDT** (17:08:58 UTC); file archive **10:09:42 AM PDT**
  (17:09:42 UTC), including current hardened configuration and complete sandbox files.
- Scratch restoration in **csf_sandbox_restore_20261031**: all 12/12 InnoDB tables/count
  samples verified; 4,336 regular files, zero mismatches. Files are private/not publicly served.
  Functional WordPress login/page rendering **not run**.
- Existing private evidence: /var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt; reuse it.
- Existing private source pinned to d360dbfd5a0441fa142a3b963a4a42c5145d9738; plugin tree matches
  52401dcd9a90ca87ad074c1078ad6633df5be77e.
- Exact-target Plesk preflight and deployment dry run passed; no plugin files copied/activated
  or pages generated, and no production changes.

These supersede earlier incomplete-backup/restore/staging/dry-run status. Preserve the existing
source pin, backup and scratch evidence. Do not create new resources to repair stale docs.

## Remaining installation review

Check/prepare the existing backup's actual target-bound script format and capture age,
reuse the existing private operator evidence file, and review the files-only installation plan. A conditional limited backup refresh is justified only by an actual stale/
incompatible artifact, not by repeating full preparation. Capture times, scratch DB and evidence path are now supplied; only raw dump/archive filenames,
private scratch/source filesystem paths and hashes still need existing-record lookup.

Follow the [gate procedure](05-backup-rollback-deploy.md#backups-and-verified-backup-gate-before-any-apply)
and [staged plan](07-website-installation.md#staged-installation-approval-boundaries). No file
installation, activation, setup or permalink change is approved. Actual CSF/Kadence functional
testing and the functional restored-site test remain unrun.

## Inspection limits

Use the latest Gateway preflight pass as reported evidence; retain its actual method/output
from existing records. Do not claim an unspecified repository helper was run. The strict
sandbox helper requires pretty permalinks while earlier records had plain URLs; do not change
settings or weaken checks to reconcile that difference. Current documentation work executes
no server commands. Never print secrets, bootstrap unreviewed startup code under a read-only
scope, or convert local mocked tests into claimed server results.
