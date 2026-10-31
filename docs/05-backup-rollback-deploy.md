# 05 — Backup, Rollback & Deployment

## Environments

| Environment | Purpose | Touched by this repo? |
|---|---|---|
| Production (current site) | Live Tucker/Elementor site | **No.** Not without explicit written approval. |
| Development (new install, Kadence) | Build and review | Yes, via the scripts below. |
| Local | Developer testing | Yes. |

Set `define( 'WP_ENVIRONMENT_TYPE', 'development' );` in the dev site's `wp-config.php`. The
scripts refuse to run against a site reporting `production`.

## Backups

**Before every deployment** to dev:

```bash
bash scripts/backup-dev.sh /var/www/vhosts/<dev-domain>/httpdocs ~/csf-backups
```

Produces `database.sql.gz`, `wp-content.tar.gz`, the WordPress version, the plugin list, and
SHA-256 checksums in a timestamped directory with `700` permissions.

Additionally, configure **Plesk Backup Manager** for the dev subscription (daily, 14-day
retention) and confirm the production subscription has its own scheduled backups stored
**off the server** (e.g. S3-compatible or SFTP remote storage). Verify by actually restoring
one backup to a scratch subscription — an untested backup is not a backup.

Backups contain personal data. Store copies encrypted and delete them per the retention policy.

## Deployment (dev)

```bash
git pull
bash scripts/deploy-plugins.sh /var/www/vhosts/<dev-domain>/httpdocs          # dry run
# For --apply, use the verified-backup procedure under Milestone B below.
```

Only `wp-content/plugins/csf-*` is touched. Then, if routes changed: **Settings →
Permalinks → Save**.

## Rollback

| Problem | Rollback |
|---|---|
| Bad plugin code | `git checkout <previous-tag>` then `deploy-plugins.sh --apply`. |
| Bad plugin code, site unreachable | Rename `wp-content/plugins/csf-<name>` via Plesk File Manager to deactivate it. Emergency data is retained. |
| Bad content/settings | Restore `database.sql.gz`: `gunzip -c database.sql.gz \| wp db import -` (dev only). |
| Lost uploads/files | Extract `wp-content.tar.gz` over the site. |

Tag each approved release: `git tag -a v0.1.0 -m "…"`.

## Production launch (future, separate approval)

The production switch will be its own documented plan covering: final backup of the old site,
URL redirect map (including old `/index.php/` URLs), DNS/vhost changes in Plesk, certificate,
search-console resubmission, and a rollback window during which the old site remains restorable.

## Takeover deployment safeguards

Deployment and backup now require successful environment detection and accept only `local`,
`development` or `staging`; WP-CLI errors, unknown output and production are rejected before
work. Backups use a restrictive umask and support WordPress paths containing spaces.
The backup script does not include wp-config.php or prove restorability: use a secure server
backup for configuration and perform a scratch restore. Keep backups outside the web root.

See the [takeover development deployment plan](10-takeover-audit.md#development-deployment-plan)
for inspection, release review, backup/restore evidence and approval before applying changes.

## Milestone B — development deployment preparation

**Status:** preparation only. Baseline `676e149` was pushed to `origin/codex/takeover-audit`;
main was not merged. No VPS connection or modification was performed. The exact development
hostname, document root, separate database and installed real Kadence version still need
confirmation from the Plesk subscription. Do not infer them from the production hostname.

### What the owner does in Plesk

1. Select the **separate development subscription/domain**. Record its full HTTPS URL,
   WordPress document root, system user, database name (no password), active Kadence version
   and any child-theme version. Confirm production uses a different document root and database;
   rule out aliases, shared content directories and symlinked plugin paths. Do not clone a live
   database with client data into a publicly accessible dev site.
2. Review development PHP Settings and Site Health against the table below; make changes only
   to the development domain after inspection. Confirm the domain handler matches the PHP
   used for WP-CLI. Run commands as the subscription user, never root. For a selected Plesk PHP
   handler, place that handler's bin directory on PATH before invoking WP-CLI; verify `php -v`.
3. Confirm the dev site's existing `WP_ENVIRONMENT_TYPE` is `development` or `staging`,
   `DISALLOW_FILE_EDIT` is true, `WP_DEBUG_DISPLAY` is false, and indexing is discouraged.
   If these are absent, record the finding first; changing them is a separately authorized
   development configuration action. Protect dev with Plesk directory authentication, HTTPS,
   unique admin accounts and MFA. Do not copy the local test password or SQLite install.
4. In **development Backup Manager**, create a full subscription backup including configuration,
   databases, WordPress files and uploads. Keep an encrypted off-server copy. Confirm restore
   to a separate scratch subscription/database, including login, uploads and representative URLs.
   Keep the scratch subscription private. Record operator, date, backup ID/checksum, scratch
   target, checks and outcome in a private restore-evidence file. A successful backup job or
   checksum alone is not a successful restore. Do not restore over production or current dev.
5. Supply the non-secret inspection report and Kadence version; provide an authentic theme ZIP
   if a local compatibility run is desired. Provide authorized access only through secure
   environment settings when requesting Codex to perform server operations. Do not send keys
   or passwords in chat. After reviewing the exact preflight, backup and dry-run results,
   explicitly authorize installation on that exact dev URL/path.

### Required runtime and settings

| Item | Requirement / verification |
|---|---|
| WordPress | Use an up-to-date supported release, **at least 6.6 for this deployment** because brand presets use theme JSON schema v3. The plugin header's 6.5 minimum remains a separately tracked compatibility issue. Locally exercised WP 7.1.4. Do not upgrade the VPS as part of inspection. |
| PHP | Plugin syntax requires 8.1+; select a currently security-supported release supported by installed WP/Kadence/plugins (prefer 8.3/8.4 after validation). Locally exercised 8.4.24; lower-version matrix unverified. |
| Database | MySQL 8.0+ or MariaDB 10.6+ recommended; utf8mb4. Verify exact version. **InnoDB is required for the proposed future transactional emergency fix**, which is not implemented yet. |
| PHP extensions | Require mysqli/mysqlnd for MySQL, JSON, mbstring, DOM/XML, cURL, OpenSSL, fileinfo, filter and hash; GD or Imagick for photos. ZIP recommended for theme/plugin uploads; intl, EXIF and OPcache recommended. PDO SQLite/sqlite3 are **local fixture only**, unnecessary on Plesk MySQL. Composer/WPCS dev tooling also needs tokenizer, ctype, simplexml/xmlwriter. |
| PHP limits | Starting targets: memory_limit 256M+, WP_MEMORY_LIMIT 128M/256M within that limit; upload_max_filesize 32M+, post_max_size at least upload limit plus request overhead (e.g. 64M); max_execution_time 120s for admin imports; max_input_vars 3000. These are proposed starting values, not measured capacity requirements. Check subscription RAM and adjust. |
| Web server | Valid HTTPS certificate, matching https home/siteurl, HTTP→HTTPS redirect, no mixed content. Apache AllowOverride/rewrite or equivalent Plesk nginx routing must send pretty permalinks to WordPress. A generic server 404 is insufficient rewrite evidence. |
| Permissions | Subscription-owned files; typically files 0644/directories 0755 with tighter wp-config permissions supported by the handler; no world-writable files, no root-owned deploy output. Inspect actual ACLs/ownership first. |
| Cache | Exclude /h/*, /emergency*, wp-admin and authenticated pages from nginx/CDN/page cache. Public banner cache purge needs logged-out verification across all cache layers. Do not claim it works solely because WP hooks fired. |
| Tooling | Bash, Git, WP-CLI, rsync, realpath, GNU stat/date, awk, tar, gzip, sha256sum and MySQL/MariaDB client for exports. Node, Composer and browser tooling may stay on the build machine; production PHP plugins do not require node_modules or vendor. |
| Outbound/cron | No notification provider or credentials needed for this release. Acuity is a browser link. For updates/checksums allow the relevant official WordPress/theme destinations through normal TLS. Record WordPress/server cron; a durable worker is a future notification requirement. |

### Read-only inspection and prepared dry run

The following commands are for the owner to run, or for Codex after server-access authorization.
They have **not** been run against Plesk. Select the reviewed preparation commit on the takeover
branch in a tooling checkout outside the public web root. Do not `git pull` an unreviewed tip.
Replace these placeholders with the exact observed values; the example URL is deliberately invalid.

```bash
# Use the development subscription account and domain PHP handler.
CSF_DEV_ROOT='/exact/path/from/Plesk/development-document-root'
CSF_DEV_URL='https://dev.example.invalid'
cd /path/outside-web-root/KksSandbox
bash scripts/inspect-environment.sh "$CSF_DEV_ROOT" > /private/path/dev-inspection.md
bash scripts/plesk-preflight.sh "$CSF_DEV_ROOT" "$CSF_DEV_URL"
bash scripts/deploy-plugins.sh "$CSF_DEV_ROOT" --expected-url "$CSF_DEV_URL" \
  > /private/path/plugin-dry-run.txt
```

The inspector issues read commands only, does not dump wp-config/secrets, does not execute
user-derived shell strings, and reports failed checks with nonzero status. It skips ordinary
plugins/themes; **WordPress may still bootstrap existing must-use plugins/drop-ins**, whose
side effects cannot be ruled out by the script. Review those first if strict no-write
assurance is required; collect Plesk/Site Health observations manually instead if necessary.
The preflight also skips plugins/themes, verifies development/staging, exact home/siteurl,
indexing off, the real Kadence parent template and CLI extensions. It does not prove theme
rendering. Inspect file changes/deletions in the dry-run output before approval.

### Verified backup gate before any apply

After the full subscription backup/restore drill, take a **fresh** application backup before
installation, outside every public document root:

```bash
bash scripts/backup-dev.sh "$CSF_DEV_ROOT" /private/path/csf-backups
# Set this to the exact new directory printed above, not an older backup.
CSF_BACKUP='/private/path/csf-backups/csf-dev-<timestamp>-<pid>'
CSF_RESTORE_EVIDENCE='/private/path/restore-drill-evidence.txt'
bash scripts/verify-dev-backup.sh "$CSF_BACKUP" "$CSF_DEV_ROOT" "$CSF_DEV_URL" "$CSF_RESTORE_EVIDENCE"
```

The gate checks the six fixed checksum entries, compressed archive integrity, nonempty SQL,
exact target path/URL, backup outside the WordPress root, database archive age ≤24h and a
nonempty restore-evidence file. It checks integrity, **not provenance or successful restoration**;
the operator must truthfully record and review a completed drill. Do not manufacture evidence
just to pass. Existing older backups lack the new target metadata: take a new backup.
The script still excludes wp-config, core and server configuration; the full Plesk backup
must cover those. Quiesce development editing/cron activity for a coherent snapshot.

Only **after explicit authorization** for the reviewed URL/path and release:

```bash
# DO NOT RUN during preparation.
bash scripts/deploy-plugins.sh "$CSF_DEV_ROOT" --expected-url "$CSF_DEV_URL" \
  --backup "$CSF_BACKUP" --restore-evidence "$CSF_RESTORE_EVIDENCE" --apply
```

Only csf-core and csf-emergency plugin directories are copied. Unknown environments, failed
WP-CLI, URL mismatch, missing backup/evidence and symlinked content/plugin paths are refused.
The root path is canonicalized; the owner must still compare it against Plesk and production.
No script can identify a mislabeled production site from WP_ENVIRONMENT_TYPE alone.
WordPress ZIP upload bypasses these script gates, so do not use it to avoid the same backup,
identity and approval requirements. Do not activate plugins or run setup during preparation.

### Real Kadence acceptance gate

**Unverified in this task.** The authoritative downloads.wordpress.org theme endpoint returned
403 under current cloud policy, and no exact installed version/theme ZIP is available.
A Kadence-looking stand-in is not compatibility evidence. A system Chromium browser is now
available locally, but it does not solve the missing authentic theme.

Use the authentic installed Kadence version (plus actual child theme/plugins) on an isolated
copy or the explicitly authorized development installation. Record WP/PHP/Kadence versions
and theme source/checksum. Follow doc 07 for manual palette/header/footer; verify:

- Theme parent is kadence; no test stand-in installed on the VPS.
- Home/Lessons/Contact at phone/tablet/desktop widths: one H1, full-width/unboxed layouts,
  correct logo or plain text fallback, visible keyboard focus and no horizontal overflow.
- `_kad_*` layout metadata actually takes effect; validate in Kadence's editor panel.
- Primary/mobile menus and footer synced pattern are placed correctly; keyboard menu operation,
  booking link, editor block validity and accessibility checks pass against actual Kadence DOM.
- Logged-out monitoring banner appears above the header, works with sticky header choices,
  propagates through every cache layer, and disappears on return to Normal.
- Standalone emergency and print routes remain independent and noindex. Use synthetic data
  only on the isolated copy; physical tags and owner emergency acceptance remain separate.

Existing navigation tests contain stand-in-specific selectors. Adapt assertions to real
Kadence accessibility roles/DOM and record real evidence; do not assume those tests apply unchanged.

### Rollback on development only

Before authorized installation record the reviewed Git SHA, prior plugin version/archive,
current active plugins/theme, full Plesk backup ID and app backup checksum. Stop development
editing if a rollback is needed and preserve any post-install changes for review.

1. For a PHP fatal, use the **development** File Manager to rename only the failing CSF plugin
   directory or use WP-CLI with --skip-plugins to deactivate it. This is recovery, not a full
   rollback; data remains but dynamic blocks/styling or emergency behavior may disappear.
2. Restore the exact prior CSF plugin directories from the verified pre-install archive
   (remove unexpected new files within those two directories after reviewing the diff).
   Replacing code alone does not undo activation-created tables/roles, pages, menus or options.
3. For content/settings/schema changes, restore the development database and wp-content from
   the same backup point using Plesk's target-specific restore controls. For a full restore,
   include configuration from the full subscription backup. Recheck destination paths/DB
   before confirming; never select the production subscription. Preserve newer data first.
4. Confirm ownership, HTTPS, login, former active theme/plugins, representative pages, booking,
   permalinks, emergency state/log and logged-out cache freshness. Return test state to Normal
   only if that matches the recorded pre-test state. Log the outcome. Do not silently overwrite
   later changes with an old database snapshot.

### What Codex can automate

With separately authorized development access: collect the safe inspection report, check
identity/settings/extensions, produce checksumed plugin releases, make and verify a fresh
backup, compare dry-run output, copy approved plugin files, inspect logs, and run local/dev
smoke checks. Codex can prepare a restore plan and assist with an authorized scratch restore;
it cannot certify an unperformed restore, real-device/tag behavior or owner sign-off.
Plesk subscription selection, accurate production/dev isolation, secure access, backup storage,
restore-drill sign-off, theme asset provision and deployment approval remain owner actions.

## Sandbox read-only exact-target inspection

Owner-confirmed target: **https://sandbox.cornerstonefarmomak.com**, relative root
`sandbox.cornerstonefarmomak.com`, Plesk database **wp_mdf4x**. Production's **wp_iacb7** and
unassigned **wp_mdoaq** are excluded. WP/PHP/Kadence are reported as 7.1.4 / 8.3.36 / 1.5.2.
These supplied values replace earlier unknown URL/version placeholders; the absolute root
and configured/connected database still need server evidence. No absolute path is assumed.

Current authorization is **inspection and dry run only**, not deployment, installation of
tooling, backups/restore, activation, setup, database updates or configuration edits. Do not
run --apply, wp eval, wp option update, wp csf setup, wp core update-db or plugin activation.
General WP-CLI commands that bootstrap WordPress may trigger plugin/MU-plugin writes; use the
non-bootstrap inspection below instead. Review any custom wp-config.php code first because
WP-CLI db query evaluates configuration before stopping short of wp-settings.php.

### First obtain the absolute path and non-secret configured database

In the **development subscription's existing SSH terminal**, from the subscription home
(the folder containing the owner-confirmed relative root), run only:

```bash
cd -- sandbox.cornerstonefarmomak.com &&
pwd -P &&
wp --path="$PWD" config get DB_NAME --type=constant
```

This prints the resolved path and DB name only; it does not connect to the database.
If the directory or WP-CLI is missing, report the error rather than searching production,
installing tools or guessing a path. If the terminal is chrooted, report that too: its absolute
path is in that shell's namespace and Plesk must confirm the host-path mapping before remote
rsync. Never print wp-config.php, DB_PASSWORD, salts or the process environment.

Expected DB_NAME: **wp_mdf4x**. Any other value is a stop. This verifies configured identity
only; do not report the connected database as verified until the read-only SELECT also passes.

### Full preflight without staging new files on the server

If the reviewed checkout/tooling already exists **outside** the public web root on the server:

```bash
# Replace only after the canonical path was actually observed.
python3 scripts/sandbox-preflight.py '/observed/absolute/path/sandbox.cornerstonefarmomak.com'
```

Use the existing domain PHP 8.3 handler on PATH for WP-CLI; a command-local PATH selection
is sufficient and does not change the domain's Plesk settings. Report a missing handler or
WP-CLI rather than installing/changing anything. Preflight output goes to stdout; no report
file is written on the VPS unless separately authorized.

If no tooling checkout exists, an operator with **existing secure SSH access** can pipe the
reviewed script from the local checkout into Python's stdin without uploading a file:

```bash
# csf-sandbox is a placeholder for an already configured/verified SSH connection.
# Run this on the local machine holding the reviewed repository, not in Plesk's terminal.
ssh csf-sandbox 'python3 - sandbox.cornerstonefarmomak.com' < scripts/sandbox-preflight.py
```

Run as the development subscription user, not root. The relative argument assumes that
account's initial directory contains the documented sandbox folder; use the observed absolute
path if not. Do not disable SSH host-key verification. This command inspects only, not rsync.

### Exact-target dry run after preflight passes

With a reviewed, already-existing server checkout, the same script supports `--dry-run`.
It cannot apply changes and rejects --apply. With no server checkout, use local-to-remote
rsync from the reviewed checkout after the target has passed the full preflight:

```bash
# Replace with the real path from the passing preflight; do not run a placeholder.
CSF_DEV_ROOT='/observed/absolute/path/sandbox.cornerstonefarmomak.com'
for plugin in csf-core csf-emergency; do
  rsync -a --delete --exclude=.git --itemize-changes --dry-run --secluded-args \
    "wp-content/plugins/$plugin/" "csf-sandbox:$CSF_DEV_ROOT/wp-content/plugins/$plugin/"
done
```

The destination must be the verified sandbox root, never production httpdocs. Inspect every
proposed deletion/copy. Missing rsync or a missing target parent directory is a blocker, not
authorization to install packages or create directories. This is a prepared command plan;
it is not evidence a server comparison ran. Future apply still requires fresh verified
backup/restore evidence and explicit authorization for the exact target/release.

### Gateway result received — exact path and remaining gates

The subsequent owner-forwarded Gateway report confirms the canonical sandbox root is:

```text
/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com
```

Both configured DB_NAME and SELECT DATABASE() match **wp_mdf4x**; the read-only session
reports `tx_read_only=1`; safe option reads used **wp_mdf4x.TXGEh_options**. Stored home/siteurl
match the sandbox HTTPS URL. WP 7.1.4/core schema, domain FPM PHP 8.3.36/extensions and active
Kadence 1.5.2 match. These replace earlier unknown-path/identity entries in this runbook.
See [the current report](test-results/sandbox-preflight.md) for evidence provenance.

Two installation gates remain unresolved: effective environment type is unknown and stored
permalink_structure is empty. Do not change either during this task. The original read-only
scope permits inspecting just the non-secret WP_ENVIRONMENT_TYPE, WP_HOME and WP_SITEURL
constants and the domain FPM's WP_ENVIRONMENT_TYPE source; do not bootstrap WordPress or
print other configuration/environment values. Absent/invalid environment sources default to
production in WordPress, and a constant takes precedence over the environment variable.

A **file-only dry run** can be prepared/executed against the verified sandbox directories
while installation remains blocked. This operation does not load WordPress or use its DB.
It does not bypass or weaken deploy-plugins.sh's apply gates or sandbox-preflight.py's strict
readiness checks. No file comparison counts as authorization to copy, activate or configure.

In the working Gateway conversation, as the development subscription user:

1. Reconfirm the exact canonical root; ensure wp-content/plugins and both target plugin paths
   are not symlinks. Do not inspect production plugin directories.
2. Use an already-existing reviewed source checkout outside all public web roots. Confirm its
   two CSF plugin trees match `52401dcd9a90ca87ad074c1078ad6633df5be77e` with a quiet Git diff;
   reject untracked/ignored additions inside those trees too. Record the source path and SHA.
   If source is absent, stop: do not clone/upload/extract anything during this task.
3. From that existing checkout, run only:

```bash
CSF_DEV_ROOT='/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'
for plugin in csf-core csf-emergency; do
  rsync -a --delete --exclude=.git --itemize-changes --dry-run \
    "wp-content/plugins/$plugin/" "$CSF_DEV_ROOT/wp-content/plugins/$plugin/" || exit "$?"
done
```

Return the raw dry-run listing and exit status without creating a report file on the VPS.
If rsync or the target parent directory is absent, report that blocker; do not install tools
or create directories. Existing secure local-to-remote rsync with --dry-run is an alternative
when the source checkout is local. Do not substitute a production path.

After file comparison, installation still needs: confirmed development/staging designation,
reviewed URL overrides, separately authorized pretty-permalink/setup changes, a fresh verified
backup and restore evidence, actual Kadence acceptance and explicit apply approval. Do not
reinterpret this inspection update as authorization for those changes.
