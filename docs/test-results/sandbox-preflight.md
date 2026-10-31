# Sandbox deployment status — Gateway preparation completed October 31, 2026

Source: the owner's latest Gateway SSH completion report. It supersedes earlier reports that
called full backup, scratch restore, staging or dry run incomplete. These operations were
performed through Gateway, not independently executed by this Codex documentation task.
**Do not repeat completed preparation.** No plugin installation/activation, site setup,
permalink changes or production deployment is authorized by this update.

## Exact target

| Property | Reported verified value |
|---|---|
| Sandbox | https://sandbox.cornerstonefarmomak.com |
| Canonical document root | `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com` |
| Configured / connected DB | `wp_mdf4x` / `wp_mdf4x`; options `TXGEh_options` |
| Stack | WordPress 7.1.4, domain PHP 8.3.36 (plesk-php83-fpm), MariaDB 10.6.23, real Kadence 1.5.2 active |
| Security / config | Valid Let's Encrypt/TLS 1.3, HTTP→HTTPS 301, HTTP 401 password protection, blog_public=0; development environment, DISALLOW_FILE_EDIT=true, WP_DEBUG_DISPLAY=false; syntax passed, live config ownership preserved and mode 0600 |
| Full backup directory | `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/` |
| Staged private source | Pinned to `d360dbfd5a0441fa142a3b963a4a42c5145d9738`; exact existing checkout path not included in the forwarded report |
| Reviewed plugin release | Trees verified against `52401dcd9a90ca87ad074c1078ad6633df5be77e` |
| Production | `/var/www/vhosts/cornerstonefarmomak.com/httpdocs`, DB `wp_iacb7`; untouched/excluded |
| Unassigned DB | `wp_mdoaq`; excluded, including as scratch restore target |

## Completed preparation — do not reschedule

| Task | Gateway-reported result |
|---|---|
| Full sandbox filesystem/config/database backup | Complete at the directory above |
| Backup archive / checksum verification | Passed |
| Scratch DB creation and restoration | Complete in a new isolated database |
| Restored database checks | All **12 InnoDB tables** verified, with representative record-count checks |
| Restored filesystem checks | **4,336 regular files**, **zero mismatches** |
| Private GitHub source staging | Complete, pinned to d360dbf; plugin source matches reviewed 52401dc |
| Exact-target Plesk environment preflight | Passed per Gateway report |
| Deployment dry run | Passed; only the two new CSF plugin directories proposed |
| Copied plugin files | None |
| Activated plugins / generated pages | None |
| Production modifications | None |

This Codex checkout also independently compared the two commits' plugin trees and deployment,
backup and verification scripts: no differences. That is a local Git comparison, not a new
server preflight or dry run. New documentation commits must not cause the staged server
checkout to move from d360dbf or trigger a new source staging task.

## Restore qualification — preserve exactly

The isolated restoration passed **structural database and filesystem-integrity verification**.
A functional WordPress login/page-rendering test on that restoration **has not been performed**.
Do not describe it as a fully functionally tested recovery, and do not describe all restoration
work as unperformed. Reuse the existing [restoration evidence record](sandbox-restoration-evidence.md),
with that limitation visible. Exact scratch resource names, per-table counts and checksum
values were not included in the supplied report; recover them from existing Gateway records
when preparing operator evidence rather than inventing them or repeating the drill.

## Immediate remaining gate and installation plan

1. Check the **existing backup** against scripts/verify-dev-backup.sh's target-bound artifact
   layout and actual capture age at installation time. Its real artifact filenames, hashes,
   precise capture time and gate result have not been supplied in this conversation. A dated
   directory name is not a freshness check. Do not claim the final script gate has passed yet.
2. Prepare the private operator-reviewed restoration-evidence file from completed records,
   retaining the functional-test-not-run qualification. Nonempty content alone does not prove
   the underlying restore; the script checks presence, not that every claim is true.
3. If existing artifacts are fresh and compatible, reuse them. Only if stale, incompatible or
   invalid should the operator request/perform an authorized **limited current application
   backup refresh**. Preserve the completed full backup; do not rerun source staging, full
   preparation or structural restore merely because its status was previously documented late.
4. Review [the staged sandbox installation plan](../07-website-installation.md#staged-installation-approval-boundaries).
   First approval is for copying only the two new plugin directories, left inactive. Activation,
   21-page setup/permalinks and actual Kadence testing are later, separate stages.

The immediate objective is testing csf-core/csf-emergency against real Kadence 1.5.2. Plugin
files, activation/site setup and live compatibility tests are **not started**. The original
configuration-only recovery artifact remains
`/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak`; it is distinct from the
completed full sandbox backup and must be preserved unchanged.

## Preflight details and unrun checks

Gateway reports a passed exact-target Plesk preflight. The exact command/tool version and
raw output were not forwarded. Do not claim that the repository's specific strict
sandbox-preflight.py helper passed: it checks /%postname%/ and earlier reports had plain
permalinks. No permalink change is now authorized or reported. Preserve the latest passed
Gateway preflight result, record its actual method from existing logs, and do not modify
permalinks or weaken a helper to manufacture agreement. File copying does not require
rewriting URLs; route configuration belongs to the separately approved setup stage.

The earlier stored home/siteurl checks pass. Separate WP_HOME/WP_SITEURL override findings
have not been forwarded; do not invent them or repeat the whole environment inspection.
Functional restored-site verification, live CSF/Kadence behavior, emergency concurrency and
operational acceptance remain distinct unrun work. Private debug-log destination is not
established solely by WP_DEBUG_DISPLAY=false.

## Scope and evidence limits

This update authorizes documentation/plan preparation, not VPS mutations. Full backup,
restore, source staging and dry run are reported complete; no fresh gate bundle or remote
backup was created by this task. The [backup gate package](../05-backup-rollback-deploy.md#backups-and-verified-backup-gate-before-any-apply)
provides the exact required fields and conditional refresh procedure. Application code and
deployment guards remain unchanged. No new VPS commands or credentials were used here.
