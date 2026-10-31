# Sandbox exact-target preflight — reconciled October 31, 2026

Source: the owner's forwarded Gateway SSH inspection reports and explicit correction of the
completed October 31 configuration work. These are Gateway-reported server operations, not
commands independently executed by this Codex session. This update supersedes earlier entries
that called hardening pending. It is a documentation reconciliation, not VPS execution approval.

## Completed and verified through Gateway

| Check | Latest supplied result |
|---|---|
| Sandbox URL | https://sandbox.cornerstonefarmomak.com |
| Document root and canonical path | `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; equal |
| Configured DB_NAME and SELECT DATABASE() | Both `wp_mdf4x` |
| Database session / options table | `tx_read_only=1`; `wp_mdf4x.TXGEh_options` |
| MariaDB | 10.6.23; InnoDB default (verify actual future emergency-table engines separately) |
| Stored home/siteurl | Both match sandbox HTTPS URL |
| WordPress | 7.1.4; DB schema version matches core file |
| Domain PHP | 8.3.36; plesk-php83-fpm; FPM binary confirmed |
| PHP extensions | Required checks passed; mysqli, curl, mbstring, XML/DOM, ZIP, GD, Imagick, OpenSSL, sodium and intl present |
| Kadence | 1.5.2; template and stylesheet kadence (active) |
| HTTPS | Valid Let's Encrypt certificate; TLS 1.3; expires January 29, 2027; HTTP 301 to HTTPS |
| Sandbox access / indexing | HTTP 401 password protection; blog_public=0 |
| WP_ENVIRONMENT_TYPE | **development — applied and effective WordPress value verified October 31** |
| DISALLOW_FILE_EDIT | **true — applied and effective WordPress value verified October 31** |
| WP_DEBUG_DISPLAY | **false — applied and effective WordPress value verified October 31** |
| PHP syntax after hardening | Passed |
| Live wp-config ownership / permissions | Verified/preserved; mode **0600** (specific owner name not supplied) |
| Production | Untouched by reported hardening |

## Original configuration recovery evidence

Gateway created this backup **before applying the three constants**:

```text
/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak
```

Preserve it without overwriting/moving it or publishing its contents. It is **configuration
only**, not a full website backup, database export or completed restoration test. No checksum
for this artifact has been supplied here. The live config permission result above must not
be misrepresented as a newly verified permission/checksum of the backup itself.

Do not reapply the constants, repeat a hardening approval request, or restore the original
config as a normal deployment step. Its restoration would revert the completed hardening.

## Still outstanding — no completion claim

| Gate | Current state |
|---|---|
| Full sandbox files and database backup | **Not completed.** Capture current hardened config, full sandbox files and only wp_mdf4x, with integrity/target evidence. |
| Isolated scratch restoration | **Not completed.** Requires a new scratch DB/path; never reuse wp_mdf4x, wp_iacb7 or wp_mdoaq. |
| Private reviewed source staging | **Not completed.** Git/WP-CLI/rsync exist; the application checkout has not been staged on the VPS. |
| Exact-target deployment dry run | **Not completed.** Return real added/changed/deleted listings, source SHA and exit status. Prior local rsync fixture output is not sandbox evidence. |
| Pretty permalinks | Plain/empty in last inspection; **no change authorized or reported**. Strict preflight should retain this blocker. |
| URL overrides | Stored values match; no later WP_HOME/WP_SITEURL override result supplied. Preserve this specific uncertainty rather than reopening the verified three constants. |
| Real Kadence build behavior | Theme version/activation verified; CSF rendering, editor validity, navigation, accessibility and cache behavior remain untested. |
| Plugin installation, activation and site setup | **Not authorized or performed** by the supplied reports. |

Private logging remains a separate inspection item; WP_DEBUG_DISPLAY=false alone does not
prove a private WP_DEBUG_LOG destination. Do not infer changes to WP_DEBUG/WP_DEBUG_LOG.

## Correct remaining sequence

1. After explicit execution approval, create a **full sandbox-only backup of current state**.
2. Restore it into new isolated private scratch resources and record actual recovery evidence.
3. Stage pinned reviewed Codex source outside all web roots, preserving existing user files.
4. Run the exact-target preflight and a clearly labeled file-only deployment dry run.
5. Review results and request separate plugin installation approval. Do not apply deployment.

The [preparation package](../05-backup-rollback-deploy.md#sandbox-preparation-approval-package)
now begins with full backup, not configuration edits. Approval of new backup/scratch/source
resources is still needed; the request to update GitHub documentation does not grant it.
No plugin installation/activation, permalink/site-setup change or production deployment is
included. Existing assigned databases are excluded from writes during preparation; only
read/export the sandbox DB, and write restored data exclusively to the new scratch DB.

## Exact target and retained safeguards

Use the canonical sandbox root above; production remains at
`/var/www/vhosts/cornerstonefarmomak.com/httpdocs` with DB `wp_iacb7`. Existing unassigned DB
`wp_mdoaq` remains excluded, including as a scratch DB. Both sites share a subscription, so
an unscoped subscription restore is not an acceptable sandbox recovery method.

Plesk initially selected `psa` during inspection. Gateway's DB identity guard stopped before
any WordPress options query; explicit `wp_mdf4x` selection resolved it. Keep that guard for
every connection. Neither excluded database was queried in that reported inspection.

Keep all deployment/backup guards; none is weakened by this reconciliation. The strict
sandbox helper may still stop on plain permalinks or an unresolved check. A separate rsync
--dry-run file comparison does not clear installation gates. Record actual command outputs,
not merely a zero exit code from a partial check.

## Evidence limits and local checks

The historical 14 sandbox guard unittest cases, 22 deployment/backup scenarios and six generic
Plesk-preflight scenarios passed using local fixtures/mocks. They do not validate the VPS.
This reconciliation changes documentation only; application code and scripts are unchanged.
Earlier reports accurately describe their earlier inspection scope but no longer establish
that the three constants remain pending. Use this report for current preparation status.

Receiving Gateway output does not establish a callable connection in this Codex session.
No new VPS commands, settings, backup, restore, source staging or deployment were performed
as part of this documentation correction.
