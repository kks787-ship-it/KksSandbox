# 05 — Backup, Rollback & Deployment

**Current state: October 31, 2026.** The owner reports completed Gateway SSH hardening on
`serve1`. The three sandbox constants below have been applied and verified. **Do not reapply
or request approval for those completed changes.** The remaining preparation begins with a
full sandbox-only backup. No plugin installation, activation, site setup, permalink change
or production deployment is authorized.

The [current preflight report](test-results/sandbox-preflight.md) records evidence provenance.
Historical local test reports are not current server deployment results.

## Environments

| Environment | Exact target | Boundary |
|---|---|---|
| Sandbox | https://sandbox.cornerstonefarmomak.com; `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; DB `wp_mdf4x`, options `TXGEh_options` | Preparation only within separately authorized operations |
| Production | cornerstonefarmomak.com; `/var/www/vhosts/cornerstonefarmomak.com/httpdocs`; DB `wp_iacb7` | Excluded from changes |
| Unassigned database | `wp_mdoaq` | Excluded, including as a scratch database |
| Local/scratch | Disposable development resources only | Must have their own database and isolated configuration |

Both sites share a subscription. Do not assume a whole-subscription Plesk backup/restore
is sandbox-only. Never use production's httpdocs path as the sandbox destination.

## Milestone B — development deployment preparation

### Completed Gateway operations — October 31, 2026

Before changing the sandbox configuration, Gateway backed up the original wp-config to:

```text
/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak
```

Preserve this exact artifact as protected **configuration-only recovery evidence**. Do not
print/copy its contents into chat, Git or a public directory, overwrite it, move it or relax
its permissions to obtain access. It is not a database dump, full-site archive, completed
scratch restore or deployment-gate backup. Its checksum has not been supplied here.

Gateway then applied and verified the effective WordPress values:

| Setting/check | Verified result reported by owner |
|---|---|
| WP_ENVIRONMENT_TYPE | `development` |
| DISALLOW_FILE_EDIT | `true` |
| WP_DEBUG_DISPLAY | `false` |
| PHP syntax | Passed |
| Ownership | Verified/preserved; no specific owner name supplied in this report |
| Live wp-config permissions | Preserved at `0600` |
| Production | Untouched |

These are completed operations, not proposed edits. Preserve their values. No new change
to WP_DEBUG or WP_DEBUG_LOG is included in the remaining package; do not infer a private
logging destination from the debug-display check alone.

Prior Gateway inspection also confirmed the sandbox path, configured/connected DB, WP 7.1.4,
FPM PHP 8.3.36/extensions, active Kadence 1.5.2, MariaDB 10.6.23/InnoDB default, HTTPS/TLS 1.3,
HTTP→HTTPS 301, HTTP 401 password protection and `blog_public=0`. Pretty permalinks were plain
in the last supplied inspection and no subsequent permalink change has been reported.

## Sandbox preparation approval package

**Remaining work only; not yet executed or approved by a request to reconcile documentation.**
This replaces the earlier package that proposed reapplying the three constants. The owner
requires approval for the next operation because it creates backup files and new scratch
resources. Do not request fresh approval for completed hardening.

### Remaining execution sequence after explicit approval

1. **Full sandbox-only backup.** Recheck the exact path and connected database before export;
   Plesk previously defaulted its connection to `psa`, so explicitly select `wp_mdf4x` before
   querying its tables. Capture the current, already-hardened wp-config and its metadata,
   all sandbox WordPress files/core, wp-content/uploads and relevant local rewrite files,
   plus a consistent export of only `wp_mdf4x`. Store outside every web root in private,
   access-controlled recovery storage. Record target URL/path/DB, timestamp, checksums and
   export/archive exit statuses; verify compressed integrity and nonempty SQL. Preserve
   source ownership/modes and protect credentials. Record any Plesk/web-server configuration
   needed to reconstruct the sandbox without dumping secrets into the report.

   Use a sandbox-object-scoped Plesk facility or a reviewed single-transaction native DB
   export; do not include or write production/unassigned databases. The existing
   `backup-dev.sh` can provide a target-bound DB/wp-content backup after its guards pass,
   but **does not include core, wp-config or server configuration**, so it cannot alone
   satisfy this full-backup step. No guard needs to be weakened: development is now declared.
   Take the recovery copy before source staging or any future installation changes.

2. **Isolated scratch restoration.** Create a new private disposable scratch directory and
   a new uniquely named database dedicated to the drill (proposed prefix
   `csf_sandbox_restore_`). Do not reuse `wp_mdf4x`, `wp_iacb7` or `wp_mdoaq`. Restore the
   full backup there and record exact new resource names. Replace the restored copy's DB
   configuration with the scratch database **before any WordPress initialization**; never
   execute the restored wp-config while it still names `wp_mdf4x`. Keep public routing,
   cron, mail, payments and webhooks isolated from live services. Verify schema/options,
   files/checksums, uploads and an appropriately isolated functional recovery check. A
   successful SQL import alone is insufficient restore evidence. Record operator/date,
   backup identity, scratch target, checks and result. If isolation/functional verification
   cannot be established, mark the restore incomplete. Scratch cleanup requires its own
   approved exact-target scope; never infer permission to remove unrelated resources.

3. **Private source staging.** Stage reviewed Codex source outside all web roots, proposed
   path `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-source`, only after
   confirming canonical containment, no symlinks and no pre-existing files to overwrite.
   Use the subscription user and existing authorized GitHub read access or an approved
   release transfer; no credentials in chat or Git. Pin a reviewed full commit from
   `codex/takeover-audit`, not a moving branch tip or main merge. Verify that the two plugin
   trees match prepared release `52401dcd9a90ca87ad074c1078ad6633df5be77e` (later documentation
   commits do not change that release). Reject unexpected untracked/ignored files in plugin
   source directories. Missing source/authentication is a blocker, not authority to extract
   platform or Gateway credentials.

4. **Exact-target preflight and deployment dry run.** Reconfirm URL/path/database identity,
   preserved development/debug constants, source SHA and non-symlinked target plugin paths.
   Read WP_HOME/WP_SITEURL override definitions if still unresolved; do not print other
   configuration values. Do not reapply the completed constants. Use the approved
   non-bootstrap inspection method; unknown or failed checks remain failures. Strict
   preflight is expected to stop while pretty permalinks are plain; do not weaken that
   guard or change permalinks. A file-only rsync comparison may still be performed as a
   separately identified preparation result; it does not clear installation readiness.
   Return raw proposed added/changed/deleted files and underlying exit statuses.

5. **Stop for review and separate installation approval.** Preserve backup/restore evidence,
   source commit and dry-run output. No --apply, plugin upload/copying/activation, csf setup,
   permalink save, update, DNS/firewall change, or fixture tests that mutate the sandbox.
   Real Kadence functional acceptance remains pending. Reverify/take a fresh backup before
   any subsequently authorized installation; the apply gate requires a current backup and
   actual recorded restore evidence.

### Approval scope to request

Only the remaining full sandbox backup, isolated scratch restoration/new scratch resources,
private source staging, preflight and deployment **dry run** need execution approval. The three
constants are complete and excluded from this request. Production, both existing assigned
DBs and `wp_mdoaq` remain excluded from modification; only read/export `wp_mdf4x` during backup.
No plugin installation/activation, permalink change or production deployment is included.
Documentation updates do not supply this execution approval or establish callable Gateway
access. Execution claims must be backed by actual output from the authorized connection.

## Sandbox read-only exact-target inspection

Identity already verified by Gateway: the canonical root and configured/connected database
match the sandbox in the environment table. Do not ask the owner to rediscover them or
present completed environment hardening as an outstanding task. Recheck identity as a guard
before a future operation without mutating configuration.

`scripts/sandbox-preflight.py` avoids wp-settings/plugin/theme initialization by using
WP-CLI configuration commands and read-only DB transactions. Its strict permalink check
will currently block full readiness; that is expected. WP-CLI db query still evaluates
wp-config itself, so review custom configuration code before claiming absolute side-effect
isolation. General `inspect-environment.sh` / `plesk-preflight.sh` commands may bootstrap
must-use plugins/drop-ins even with --skip-plugins/--skip-themes; use the agreed safe method
when database writes/normal WordPress initialization are outside scope.

If a reviewed tooling checkout already exists, this inspection prints to stdout:

```bash
python3 scripts/sandbox-preflight.py   '/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'
```

Do not print wp-config, passwords, salts or the full process environment. Use the domain PHP
8.3 handler command-locally; do not change domain settings merely to match the CLI. A read-only
inspection does not permit uploading/cloning a checkout unless source staging was authorized.

### Prepared file-only dry run

After an authorized source staging step (or from an already-reviewed existing checkout),
verify the source path/SHA and all target content/plugin path components. Refuse symlinks to
another installation. From the source checkout run **only the comparison**:

```bash
CSF_DEV_ROOT='/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'
for plugin in csf-core csf-emergency; do
  rsync -a --delete --exclude=.git --itemize-changes --dry-run     "wp-content/plugins/$plugin/" "$CSF_DEV_ROOT/wp-content/plugins/$plugin/" || exit "$?"
done
```

No comparison has yet been reported as run on the sandbox. `--dry-run` is mandatory; do not
remove it or substitute production's httpdocs path. Missing rsync, missing parent directories,
unknown source or symlinked destinations are blockers, not permission to install/create/copy.
An existing secure local-to-remote rsync route with --dry-run is an alternative when sources
are local; do not disable host-key verification. Capture the listing and actual exit codes.

## Backups and verified backup gate before any apply

A complete current website/DB backup and isolated restore are outstanding. The original
`/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak` protects only the old
configuration. It has no full DB/site content and must never be passed off as a full backup,
restore drill or evidence satisfying deploy-plugins.sh/verify-dev-backup.sh.

`backup-dev.sh` produces database.sql.gz, wp-content.tar.gz, target path/URL metadata,
WordPress version, plugin inventory and SHA256SUMS. It uses restrictive permissions and
accepts only successfully detected local/development/staging environments. Supplement it
with the rest of the full sandbox capture above. Never expand scope to production objects.
Keep recovery material access-controlled/encrypted off-server and record real restore results.

For a future approved installation, the current gate requires six fixed checksum entries,
compressed archive integrity, nonempty SQL, exact target path/URL, backup outside the site,
database archive age at most 24 hours, and a nonempty owner-recorded restore-evidence file.
Checksum success proves integrity, not successful restoration or provenance. Do not fabricate
evidence. A future operator can verify a real current application backup with:

```bash
bash scripts/verify-dev-backup.sh "$CSF_BACKUP"   '/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'   'https://sandbox.cornerstonefarmomak.com' "$CSF_RESTORE_EVIDENCE"
```

## Deployment — future, separately approved

No installation is authorized. Before --apply, review this preparation's actual output,
resolve any remaining URL/permalink guards through separately approved changes, and record
the exact release, sandbox identity, full backup, current application backup, restore evidence
and rollback point. --apply requires --expected-url, --backup and --restore-evidence.
ZIP uploads require the same checks/approval and must not bypass the guard.
Only csf-core/csf-emergency plugin directories may be copied. Activation and site setup are
separate mutating steps. Follow [the installation guide](07-website-installation.md) only
within their approved scope. No production changes, main merge, DNS/vhost changes or launch.

## Rollback

For the remaining preparation, leave the live sandbox unchanged except for explicitly approved
new recovery/source resources. If a preparation step fails, record partial results and stop;
do not import a DB over the sandbox or production to undo creation of a scratch/source path.

For a future authorized deployment, record previous plugin files/version, active theme/plugins,
release SHA, same-point full backup and restore evidence. Recovery options are:

1. For a PHP fatal, deactivate/rename only the failing sandbox CSF plugin using the approved
   recovery method. Data is retained, but dynamic blocks/styles/emergency behavior may disappear.
2. Restore exact prior plugin files; review/remove newly introduced files only within those
   two approved plugin directories. Code rollback alone does not undo tables/roles/pages/options.
3. If data/settings must be reverted, restore the **sandbox-only** DB/files from the same
   verified backup point after preserving/reviewing later changes. Never restore a whole
   shared subscription or select production's database. Recheck identities before import.
4. Verify ownership, retained 0600 wp-config, effective configuration, login, HTTPS/access
   protection, prior theme/plugins, routes and cache behavior. Do not claim success until checked.

Keep the original pre-hardening config backup unchanged as historical recovery evidence.
Restoring it would remove the completed hardening; do not automate that as a routine rollback.
Use the forthcoming current/hardened full backup as the later deployment rollback point.
Any restore of the original configuration requires a deliberate scoped recovery decision.

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

### Real Kadence acceptance gate

Kadence **1.5.2 is active**, per Gateway's server inspection. This establishes theme identity,
not compatibility of the CSF build's layouts/header/footer/editor. Those functional checks
remain pending; historical stand-in screenshots/tests are not real Kadence acceptance.

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

## Production launch — future, separate approval

Preserve the live Tucker/Elementor site. A future launch plan needs a final production backup,
redirect map (including old /index.php URLs), reviewed DNS/vhost/certificate changes and a
rollback window. No part of the current sandbox preparation authorizes those operations.
