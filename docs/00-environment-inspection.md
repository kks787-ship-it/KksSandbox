# 00 — Development Environment Inspection

**Current status — October 31, 2026:** Gateway completed preparation and the public-site
installation/setup. csf-core 0.2.0 is active; 21 pages, assigned menus, static Home and pretty
routes are reported verified. Emergency remains inactive. Do not repeat completed work.
Authenticated real-Kadence browser/editor acceptance and functional restored-site testing
remain pending. No new deployment, setup, permalink or production operation is authorized.

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
| Permalinks | /%postname%/; 162 rewrite rules, standard sandbox .htaccess, Apache syntax passed (latest owner report) |

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
- Initial exact-target preflight/dry run passed. Subsequently core 0.2.0 was installed/activated, 21 pages published, menus assigned and Home configured. No production changes; emergency remains inactive.

These supersede earlier incomplete-backup/restore/staging/dry-run status. Preserve the existing
source pin, backup and scratch evidence. Do not create new resources to repair stale docs.

## Remaining build acceptance

Issue #1 now has a shared CSS candidate and local downloaded Kadence 1.5.2 reproduction.
Protected sandbox review is still NOT RUN: network access/private credentials are unavailable.
Authenticated review must cover all 21 pages at 390/768/1280px;
footer synced block 27 is not placed in Footer Builder. Header/palette/typography/navigation,
authentic assets, owner notes and booking verification remain configuration/content work.
See the [current report](test-results/sandbox-preflight.md) and [acceptance/update plan](07-website-installation.md).

Local core 0.3.0 fixes and the disabled inquiry form are not deployed. A future approved update
needs a reviewed exact SHA, current target-bound application backup and --only-core dry run.
The old full backup predates site installation/setup: preserve its structural evidence and
capture provenance without claiming current or functional recovery. Do not repeat the entire
infrastructure package or request known identities again.

## Inspection limits

The original preflight's exact command was not forwarded; do not invent a particular helper
result. The later explicit permalink/rewrite report supersedes the earlier plain-URL record.
HTTP 401 without Basic Authentication is expected, not proof of a broken route or successful
page rendering. No VPS commands were executed here. Never print configuration secrets or
bootstrap unreviewed startup code under a strict read-only scope. Local mocked/SQLite and
substitute-theme tests do not establish actual Plesk, MariaDB or Kadence compatibility.

Newer remote records also confirm the fresh gate bundle csf-dev-20261031-184634-36879 passed
before the owner-approved install/activation/setup. See doc 05 for Gateway job provenance.
It predates the current published build and is not a substitute for a current update backup.
