# 05 — Backup, Rollback & Deployment

**Current state: October 31, 2026.** Gateway completed sandbox hardening, full backup,
structural/database and filesystem restore verification, private source staging, exact-target
Plesk preflight and deployment dry run. The fresh installation backup gate passed, then the
owner approved **files-only installation of `csf-core` and `csf-emergency` in the sandbox**.
Both plugin directories are present and verified. A later **owner-approved sandbox-only
activation of `csf-core` 0.2.0 succeeded**; `csf-emergency` 0.1.0 remains inactive.
Do not repeat completed preparation or file copying. Functional login/page rendering on the
isolated restoration remains untested. Emergency plugin activation, site setup, permalink
change and production deployment remain unauthorized.

The [current report](test-results/sandbox-preflight.md) records evidence provenance; the
[installation plan](07-website-installation.md#staged-installation-approval-boundaries)
separates copying inactive files from later activation, setup and real Kadence testing.

## Environments

| Environment | Exact target | Boundary |
|---|---|---|
| Sandbox | https://sandbox.cornerstonefarmomak.com; `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; DB `wp_mdf4x`, options `TXGEh_options` | `csf-core` active with approval; `csf-emergency` inactive; setup unapproved |
| Production | cornerstonefarmomak.com; `/var/www/vhosts/cornerstonefarmomak.com/httpdocs`; DB `wp_iacb7` | Excluded from changes |
| Unassigned database | `wp_mdoaq` | Excluded, including as a scratch database |
| Existing scratch | DB `csf_sandbox_restore_20261031` and private/not-publicly-served restored filesystem | Preserve; filesystem path is in existing Gateway records |

Both sites share a subscription. Never substitute production's httpdocs path, use an unscoped
whole-subscription restore, or recreate a scratch resource merely to fix stale documentation.

## Milestone B — development deployment preparation

### Completed Gateway operations — October 31, 2026

Before hardening, the original sandbox wp-config was preserved at:

```text
/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak
```

Preserve this protected **configuration-only** artifact unchanged. Do not print/commit its
contents, overwrite/move it or relax its permissions. It is distinct from the completed
full site backup below, and must not be passed off as a database dump or full restoration.

Gateway applied and verified WP_ENVIRONMENT_TYPE=development, DISALLOW_FILE_EDIT=true and
WP_DEBUG_DISPLAY=false; PHP syntax passed, ownership was preserved/verified and live config
mode remained 0600. **Do not reapply the constants or request hardening approval again.**
No new WP_DEBUG/WP_DEBUG_LOG setting or private log destination is implied.

Prior inspection confirmed WP 7.1.4, FPM PHP 8.3.36/extensions, active Kadence 1.5.2, MariaDB
10.6.23, HTTPS/TLS 1.3, HTTP 401 password protection and indexing disabled. No production
changes, plugin copying, activation or site setup have been reported.

## Sandbox preparation approval package

**The preparation package is complete, not awaiting execution.** The latest owner-supplied
Gateway report supersedes earlier pending-backup/restore/staging/dry-run entries.

| Completed item | Reported evidence |
|---|---|
| Full sandbox filesystem/config/database backup | `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/` |
| Archive and checksum verification | Passed |
| Isolated scratch DB restore | Complete in `csf_sandbox_restore_20261031`; all **12/12 InnoDB tables** and representative record counts verified |
| Restored filesystem | **4,336 regular files**, **zero mismatches** |
| Private source staging | Pinned to `d360dbfd5a0441fa142a3b963a4a42c5145d9738` |
| Plugin source verification | Matches reviewed `52401dcd9a90ca87ad074c1078ad6633df5be77e` |
| Exact-target Plesk preflight | Passed per Gateway |
| Deployment dry run | Passed; only the two new CSF plugin directories proposed |

**Restore qualification:** structural database and file-integrity verification passed;
functional restored-site login/page rendering has **not** been tested. Record both facts.
Do not describe all restoration as unperformed, or describe it as fully functionally tested.

Gateway now supplied the existing scratch DB name, evidence path and precise backup capture
times below. Recover only remaining raw details (private scratch/source paths, artifact names/
hashes and preflight/dry-run output) from existing Gateway records; do not guess or repeat work. The server source stays pinned to **d360dbf**;
later documentation commits do not call for a git pull or new staging task. This Codex checkout
also verified that d360dbf's plugin trees and three backup/deploy scripts match 52401dc exactly.

### Remaining review sequence

1. Check the existing backup's actual target-bound layout and capture age using the gate below.
   Prepare private operator evidence from the completed restore records, with the unrun
   functional tests visible. Request a limited current application-backup refresh **only**
   if actual artifacts are stale, incompatible or invalid; preserve the full backup.
2. Review the [staged installation plan](07-website-installation.md#staged-installation-approval-boundaries).
   First approval is for copying only the two plugin directories, left inactive, with the
   functional restore limitation disclosed. A nonempty evidence file is not owner approval.
3. Separate later approvals cover activation, 21-page setup/permalinks and real CSF/Kadence
   testing. No such operations are authorized now.

## Sandbox read-only exact-target inspection

The root, configured/connected DB and development constants are already verified through
Gateway. Do not ask the owner to rediscover them or redo hardening. Recheck target identity
as a final operation guard without treating completed preparation as unperformed.

Gateway reports a passed Plesk preflight; its exact command/tool version was not forwarded.
Do not claim the repository's particular sandbox-preflight.py helper passed: it requires
/%postname%/, while earlier reports had plain URLs. No later permalink change is reported
or authorized. Retain the Gateway pass and obtain its method from existing records; do not
change permalinks or weaken a helper to manufacture agreement.

The non-bootstrap helper uses WP-CLI config/read-only DB operations before wp-settings.php;
wp-config itself is still evaluated. General WP-CLI helpers may initialize MU-plugins/drop-ins
even with skip flags. Choose the authorized verification method; never dump configuration,
credentials, salts or the process environment. Use the domain PHP 8.3 handler command-locally.

### Prepared file-only dry run

The Gateway dry run **already passed**. Preserve its listing and exit status. A justified
future comparison following material source/target drift must retain --dry-run, the pinned
source and the exact sandbox path; no full preparation repeat or source pull is needed.
Unknown or symlinked content/plugin destinations are a stop, not permission to alter them.
The actual installation command is provided in doc 07 for later approval only.

## Backups and verified backup gate before any apply

The completed full backup is:

```text
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/
```

Gateway recorded DB export at **October 31, 2026, 10:08:58 AM PDT (17:08:58 UTC)** and
file archive at **10:09:42 AM PDT (17:09:42 UTC)**, including current hardened wp-config and
complete sandbox files. Existing private restoration evidence is:

```text
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt
```

Reuse that existing record and `csf_sandbox_restore_20261031`; do not recreate either.
Keep original verified artifacts/checksums and the functional-test-not-run qualification.
The old config-only backup remains separate. Neither backup should be overwritten/relabeled.

### Required target-bound gate bundle

The staged d360dbf script requires this exact layout:

| Required artifact | Contents / provenance |
|---|---|
| database.sql.gz | Nonempty valid gzip SQL export of wp_mdf4x; actual capture at most 24 hours old when files are copied |
| wp-content.tar.gz | Valid archive of this sandbox's wp-content |
| target-path.txt | Exact line `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com` |
| target-url.txt | Exact line `https://sandbox.cornerstonefarmomak.com` |
| wp-version.txt | Actual captured WordPress version; expected 7.1.4 |
| plugins.csv | Actual captured plugin inventory |
| SHA256SUMS | Exactly six entries for the six files above, derived from real bytes |
| Separate restore-evidence file | Private operator-reviewed completed checks/identities/hashes, with functional login/rendering explicitly NOT RUN |

Precise capture times are now known. The reported DB capture reaches 24 elapsed hours at
**November 1, 2026, 9:08:58 AM PST / 17:08:58 UTC** (DST ends overnight). Raw artifact
filenames/hashes and their current metadata still need the existing-record check; do not
claim the script gate passed from reported timestamps alone.
Copy/recompression date or a touched mtime is not a new database capture. The script uses
database.sql.gz's mtime; verify capture provenance too. Do not touch/recompress old data to
make it look fresh, invent a checksum, or claim a passed gate without running it.

First reuse an existing compatible, current bundle and run its verifier read-only:

```bash
CSF_DEV_ROOT='/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'
CSF_DEV_URL='https://sandbox.cornerstonefarmomak.com'
CSF_RESTORE_EVIDENCE='/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt'
# CSF_GATE_BACKUP must be the actual compatible bundle; the full-backup directory's layout is not assumed.
bash scripts/verify-dev-backup.sh "$CSF_GATE_BACKUP" "$CSF_DEV_ROOT" "$CSF_DEV_URL" "$CSF_RESTORE_EVIDENCE"
```

The verifier checks the six-entry manifest, compressed integrity/nonempty SQL, exact path/URL,
age at most 24 hours, backup outside the site and a nonempty evidence file. It does **not**
interpret that file, prove functional restoration, or grant installation approval.

Use the [reconciled restoration record](test-results/sandbox-restoration-evidence.md) to
review the existing private evidence file, not create a replacement. Scratch DB and capture
times are known; obtain only remaining source hashes/private filesystem paths from existing
records. Nonempty content is not a fresh verification of underlying artifacts.

Only if existing artifacts are stale/incompatible/invalid, obtain approval for a **limited
current target-bound application backup** with the existing helper:

```bash
# Reference only: do not execute unless this specific gate refresh is needed and authorized.
bash scripts/backup-dev.sh "$CSF_DEV_ROOT" "$CSF_APPROVED_PRIVATE_GATE_PARENT"
```

Record the actual new bundle printed by the command; do not overwrite the complete backup.
This captures DB/wp-content/metadata, not a replacement full-site backup or a repeated
scratch/source-staging package. It does not prove the new dump was itself restored; reference
the original drill honestly. If material state/schema/recovery-method changes invalidate
old evidence, report that specific issue rather than silently transferring a pass or
repeating the whole package without justification. Preserve access controls and export
only the sandbox database.

**Installation-specific backup gate completed October 31, 2026.** Gateway created a *new* script-format application backup at:

```text
/var/www/vhosts/cornerstonefarmomak.com/private/csf-dev-20261031-184634-36879/
```

The backup captures the sandbox database, `wp-content`, target URL/path, WordPress version and plugin inventory, with exactly six SHA-256 manifest entries. `scripts/verify-dev-backup.sh` completed successfully as Gateway job `job_01M69MJJ04000DFWPDVJ254SKY` and checked compressed integrity, exact target binding, the existing restore-evidence file and the age gate. Backup-creation job: `job_01M69MHBVD00VDBTS3X5MD49D3`.

Captured at approximately **18:46 UTC / 11:46 AM PDT October 31, 2026**; the export's actual file mtime is the authoritative freshness value. **Re-run verification immediately before installation**: a new backup is needed if older than 24 hours, the sandbox has materially changed or integrity fails. The existing full backup and isolated structural restore were not repeated or replaced; a *functional* scratch-site login/rendering test remains unrun. Existing private restore evidence has not been modified. This documentation records the performed gate, **not** permission to install plugins. Inactive file copying remains a separate owner approval, followed by distinct activation and setup approvals.

## Deployment — future, separately approved

The next stage copies csf-core/csf-emergency into the sandbox **left inactive**. Source stays
pinned to d360dbf and matches reviewed 52401dc. Use the [final staged plan](07-website-installation.md#staged-installation-approval-boundaries).
No installation is yet authorized. File-copy approval must acknowledge the functional restore
limitation; evidence presence alone is not that approval.

Activation and 21-page setup require later approvals covering their DB/rewrite/content
changes. Do not change permalinks for file copying. No production change, main merge,
DNS/vhost change or launch is part of this plan.

## Rollback

Keep the completed backup, existing scratch resources and staged checkout. No cleanup or
restore over an existing site is authorized by this update. For the future files-only stage,
record that both new plugin directories remain absent just before copying. If their state
has changed, stop for review. Within that stage's explicit rollback scope, remove/rename only
those two newly introduced **inactive** directories; do not import a DB or change other files.

If activation has occurred, removing files alone does not undo tables, roles or options.
Record a current pre-activation rollback point and use a separately reviewed sandbox-only
DB/files restore when needed, preserving later user changes. Never restore a shared
subscription or production DB. Verify ownership, current hardened config/mode 0600, access
protection, active theme/plugins, routes and cache behavior before calling recovery complete.

Preserve the original pre-hardening config backup as historical recovery evidence. Restoring
it removes completed hardening; do not automate that as a routine deployment rollback.
Use the current verified hardened backup for subsequent release recovery. The original
config artifact requires a deliberate scoped recovery decision before restoration.

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

## Files-only sandbox deployment — October 31, 2026 (verified)

After owner authorization, Gateway rechecked the fresh backup gate, exact WordPress URL/database,
private source commit `d360dbfd5a0441fa142a3b963a4a42c5145d9738`, reviewed plugin trees,
empty destination paths, and absence of symlinks. Gateway then copied only `csf-core` and
`csf-emergency` to the sandbox plugin directory via the reviewed script, using
`--apply --expected-url --backup --restore-evidence`. Deployment job:
`job_01M69NHGP200KW7JZK6HD2V9YZ` (succeeded).

Post-copy validation job: `job_01M69NPPXH00VQVHRVJ8K2KKQ8` (succeeded). WordPress reports
`csf-core` 0.2.0 and `csf-emergency` 0.1.0 as **inactive**. All plugin file contents match
the pinned reviewed source; **68 PHP files** passed PHP 8.3 syntax checks. Because the private
checkout created files with restrictive umask (`0700` directories/`0600` files), Gateway
normalized permissions **only inside these two newly introduced plugin trees** to `0755`
directories and `0644` files, matching existing WordPress conventions. File ownership is
the Plesk subscription user. Future deployments using raw `rsync -a` may reset these modes;
review a deployment-script permission normalization change before further code updates.

Sandbox database remains `wp_mdf4x` with the original five posts (site setup not run).
Neither plugin was activated, and production's plugin directories remained unmodified.
A later separate owner authorization activated **`csf-core` only**. `csf-emergency`
remains inactive. The **next stage requires separate approval** for creating the 21 pages/menus
and any permalink change.
Real Kadence rendering and usability have not been validated. The completed backup gate's
24-hour freshness must be rechecked before any subsequent mutation; if expired, create a
new limited application backup without repeating the entire structural restore.

## Verified `csf-core` activation (October 31, 2026)

Gateway checked the same sandbox-specific backup gate (fresh, six hashes valid, restored-evidence
file present), reconfirmed the exact development URL, `wp_mdf4x` and reviewed source, then
activated **only `csf-core` 0.2.0** as the Plesk subscription user.
Activation job `job_01M69P01SA00P66KZ43HJE6FC0` succeeded without command errors.
`wp plugin list` reports `csf-core=active` and `csf-emergency=inactive`.
`wp csf status` reports all 21 planned website pages missing; `wp csf setup --dry-run`
showed the 21 prospective page creations and footer pattern without mutating site content.
Five total posts remain, while taxonomy term records changed from 1 to 27 and option rows
from 147 to 150 as activation seed effects. Homepage remains `show_on_front=posts`;
permalink structure remains empty/plain; Kadence remains active; sandbox indexing is still off
and its public HTTP request is challenged with HTTP 401 password protection.

No page/menu setup, explicit permalink change, emergency activation, notifications,
production code deployment, or production database action was authorized or performed.
Separate approval is still required for website setup and any permalinks configuration.
