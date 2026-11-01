# 05 — Backup, Rollback & Deployment

## Latest approved release — October 31, 2026

The owner approved core-only deployment and the separate scheduling content/settings change.
Source `6a4652100988719cac919780b4ec9103b8c35ada` is now installed on the protected sandbox,
with 21 saved pages, two applicable menu entries and approved public call/email updated.
Inquiry remains disabled, emergency inactive and unchanged, production untouched. The plugin
version remains 0.3.0. Older 0.2.0/pending-deployment statements below are historical.

Fresh rollback bundle: private `csf-dev-20261101-065451-968013`, captured November 1
06:54:51 UTC / October 31 23:54:51 PDT. Target-bound integrity gate passed; original structural
restore evidence was reused without claiming a new or functional restore. See the exact
job IDs, checks, selective rollback artifacts and remaining protected-browser gate in
[the scheduling release report](test-results/scheduling-contact.md#approved-sandbox-release--october-31-2026-pdt).
Do not repeat this deployment or reuse its approval for future changes. No full-site setup,
permalink change, infrastructure onboarding or production operation was performed.


**Current state — October 31, 2026:** Gateway preparation and the initial public-site
installation/setup are complete. csf-core 0.2.0 is active, 21 pages/menus and static Home are
configured, and /%postname%/ routes are verified. Emergency remains inactive. A later repository report identifies Kadence gutter Issue #1; authenticated sitewide
visual/editor acceptance and its fix remain pending. **Do not
repeat completed preparation or site setup.** The local 0.3.0 candidate is not deployed.

A future update requires separate approval, a reviewed exact SHA, current target-bound backup
and core-only dry run. The historical backup predates the completed site setup; retain its
structural restore evidence without claiming current or functional recovery verification.
[Current status](test-results/sandbox-preflight.md), [update plan](07-website-installation.md#staged-installation-approval-boundaries).

## Environments

| Environment | Exact target | Boundary |
|---|---|---|
| Sandbox | https://sandbox.cornerstonefarmomak.com; `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; DB `wp_mdf4x`, options `TXGEh_options` | Core 0.2.0 active; future code updates require approval |
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
10.6.23, HTTPS/TLS 1.3, HTTP 401 password protection and indexing disabled. No production changes were made. The later owner report confirms core installation/activation and site setup; emergency stays inactive.

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

### Remaining update review sequence

1. Preserve the old full backup and structural restore; any future active-site update needs
   a current application backup of the now-published build in the gate format below. Request
   only the needed authorized backup refresh, not another infrastructure/restore package.
2. Stage the exact reviewed candidate only under the approved update scope. The historical
   d360dbf dry run does not validate new code. Use --only-core to exclude emergency.
3. Review the new dry run, ownership/modes, backup/recovery evidence and explicit update
   approval. No activation, setup, permalink or emergency change is an update prerequisite.

## Sandbox read-only exact-target inspection

The root, configured/connected DB and development constants are already verified through
Gateway. Do not ask the owner to rediscover them or redo hardening. Recheck target identity
as a final operation guard without treating completed preparation as unperformed.

Gateway reports a passed Plesk preflight and subsequently confirms /%postname%/, 162
rewrite rules, installed standard sandbox .htaccess and passed Apache syntax. The original
preflight's exact command/version was not forwarded: do not invent a specific helper result.
Do not change permalinks or weaken a check to manufacture a pass.

The non-bootstrap helper uses WP-CLI config/read-only DB operations before wp-settings.php;
wp-config itself is still evaluated. General WP-CLI helpers may initialize MU-plugins/drop-ins
even with skip flags. Choose the authorized verification method; never dump configuration,
credentials, salts or the process environment. Use the domain PHP 8.3 handler command-locally.

### Prepared file-only dry run

The Gateway dry run **already passed**. Preserve its listing and exit status. A future core update uses the newly reviewed exact SHA, --only-core and the exact sandbox path; omit --apply to retain dry-run mode. That is a comparison of changed code, not a repeat of the historical preparation package.
Unknown or symlinked content/plugin destinations are a stop, not permission to alter them.
The future update command is provided in doc 07 for later approval only.

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

The verifier requires this exact layout (unchanged in the new candidate):

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

A future update needs a script-compatible current target-bound gate bundle, preserving the
completed full backup and reusing the existing restore-evidence.txt. This documentation task
records the supplied execution evidence only; it has not created that bundle, modified the
private evidence file, or executed the remote gate. A formatting refresh must not be reported
as a new structural restore or falsely renew old data's age.

## Completed Gateway installation history — retained from remote branch

These records from the owner's newer branch commits supersede older pending-gate wording.
They are historical execution evidence, not operations run by this Codex task or approval for
the new candidate. All changes were sandbox-only, against wp_mdf4x as the subscription user.

| Operation | Evidence / observed result |
|---|---|
| Fresh installation-format backup | /var/www/vhosts/cornerstonefarmomak.com/private/csf-dev-20261031-184634-36879/; approximately October 31 18:46 UTC / 11:46 AM PDT; actual export mtime governs age |
| Backup creation | Gateway job_01M69MHBVD00VDBTS3X5MD49D3 |
| Gate verification | job_01M69MJJ04000DFWPDVJ254SKY passed six-entry integrity/target/evidence/age checks; reverified before activation by job_01M69NYRX300GG0KF838890WE3 |
| Owner-approved inactive file copy | job_01M69NHGP200KW7JZK6HD2V9YZ; post-copy verification job_01M69NPPXH00VQVHRVJ8K2KKQ8 |
| Installed file validation | Both trees matched d360dbf/reviewed 52401dc; 68 PHP files passed PHP 8.3 lint; modes normalized only within those trees to 0755/0644; subscription ownership |
| Owner-approved core-only activation | job_01M69P01SA00P66KZ43HJE6FC0, exit 0; core 0.2.0 active, emergency 0.1.0 inactive |
| Historical activation effects | At activation only: 5 posts/2 pages, terms 1→27, options 147→150; Home still posts/plain routes. These counts are superseded by the next setup operation |
| Owner-approved setup/routes | job_01M69QTN9Y00N8R62TCV7R23XT; 21 pages published, Home ID 6, menus 28/29, footer block 27; /%postname%/, 162 rules, stock sandbox .htaccess mode 0644 and Apache syntax passed |
| Setup verification | 21/21 do_blocks renders, one H1 each; 76 links, 23 owner notes; repeat dry run all ok; unauthenticated HTTP 401 expected |

The older full backup/structural restore was retained, not repeated; functional restored-site
login/rendering remains untested. The new gate bundle also predates activation/site setup
and must not be claimed as a backup of the current 21-page build. Preserve it, but require a
current rollback point before an approved update. Both CSF plugin directories are installed;
only core is active. Emergency activation and production changes remain excluded.

## Deployment — future core update, separately approved

Core 0.2.0 is active. The local 0.3.0 candidate is not deployed. Use the [current update plan](07-website-installation.md#next-sandbox-update-plan--separate-approval-required): exact reviewed
source, current target-bound backup/evidence, core-only dry run and separate apply approval.
--only-core excludes emergency; directories/files are normalized to 0755/0644 without
changing wp-config.php, uploads, production or intentionally changing active-plugin state.
Run under the correct subscription account and inspect ownership. No activation, setup,
permalink, DNS/vhost, main merge or production change is an update prerequisite.

## Rollback

Preserve current core files/checksums/version and current backup before an approved update.
For a files-only regression, restore only the prior sandbox core tree within the approved
recovery scope; do not delete the already active plugin or import a DB merely to undo copying.
If follow-on settings/content edits occurred, preserve later owner changes and review a
sandbox-only state-aware restore to wp_mdf4x. Never restore the subscription or production DB.

Keep completed backup/scratch/source records. The historical structural restore is not a
functional rehearsal of the now-installed build. Preserve hardened constants, owner/mode 0600,
HTTPS/access protection, theme/plugin state and routes. The original pre-hardening config
artifact is historical recovery evidence, not an automatic rollback (it removes hardening).

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
| Outbound/cron | Emergency notification delivery remains unimplemented. Optional inquiries stay off until the existing mail transport and actual inbox receipt are verified; verify WP-Cron for expiry cleanup. Scheduling links remain internal to Contact. For updates/checksums allow the relevant official WordPress/theme destinations through normal TLS. Record WordPress/server cron; a durable worker is a future notification requirement. |

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
- Deferred until separately approved emergency testing on an isolated synthetic copy: logged-out monitoring banner appears above the header, works with sticky header choices,
  propagates through every cache layer, and disappears on return to Normal.
- Standalone emergency and print routes remain independent and noindex. Use synthetic data
  only on the isolated copy; physical tags and owner emergency acceptance remain separate.

Existing navigation tests contain stand-in-specific selectors. Adapt assertions to real
Kadence accessibility roles/DOM and record real evidence; do not assume those tests apply unchanged.

## Production launch — future, separate approval

Preserve the live Tucker/Elementor site. A future launch plan needs a final production backup,
redirect map (including old /index.php URLs), reviewed DNS/vhost/certificate changes and a
rollback window. No part of the current sandbox preparation authorizes those operations.
