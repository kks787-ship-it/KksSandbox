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


## Post-release protected-site verification — November 1, 2026

Authenticated real-Kadence sweep completed: scheduling checks **63/63 passed**, mobile and
desktop overall **42/42 passed**; all 21 tablet views flagged one common unnamed header home
link (`link-name`). Shared gutter geometry, H1, HTTP and overflow checks passed throughout.
Existing demo site title/default footer and missing CSF footer placement remain unfinished.
See the current scheduling report for job provenance. No extra deployment/theme edit was
performed by this review. Temporary credentials removed; inquiry off/emergency inactive.

## Scheduling 0.2.1 — pinned synthetic pilot proposal (not executed)

**Source pin:** `6f5a9bf51747399a309b39637a135841fe4cf2a1` on `codex/takeover-audit`.
**Scheduling plugin Git tree:** `893edbe31278946aa2aaa830a56d5d1812b87323`.
**Scope:** only its `wp-content/plugins/csf-scheduling/` directory, version 0.2.1,
15 source/asset files verified by
[`scheduling-staff-files.sha256`](test-results/scheduling-staff-files.sha256).
The later documentation commit adds this proposal without changing that plugin tree.
Never deploy a floating branch or substitute a newer SHA without another comparison/review.

This is prepared for **separate owner approval**, not an instruction already authorized or
executed. Approval must explicitly cover scheduling files/activation, guarded schema apply,
verified staff access/configuration and synthetic pilot records. Production, inquiries,
emergency activation, public booking and website setup remain excluded. The protected stack
has not been tested with this candidate. [Local validation](test-results/scheduling-staff.md)
and [Beth/Kris acceptance packet](13-scheduling-staff-acceptance.md) are review inputs.

### Exact target and stop conditions

- URL/home/siteurl: `https://sandbox.cornerstonefarmomak.com`.
- Absolute root: `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`.
- Expected configured DB: `wp_mdf4x`; prefix inferred from the recorded options table is
  `TXGEh_`. Verify both authoritatively before **any** write; stop if either differs.
- Excluded: sibling `httpdocs`, production DB `wp_iacb7`, unassigned DB `wp_mdoaq`, DNS,
  Plesk/server configuration, WordPress/Kadence files/settings, csf-core and csf-emergency.
- Owner's prior inspection reports no installed scheduling plugin and zero scheduling tables.
  Recheck read-only. Unexpected plugin files/tables, unknown prior data, target mismatch,
  unverified MFA/staff identities, failed backup or incompatible storage must stop the pilot.
  Do not silently adopt, overwrite or delete an unexpected state.

Use the existing authorized secure connection/secret bindings; never print credentials,
wp-config, client records or the process environment. Existing Basic Auth and hardening
constants remain in place. Do not redo environment onboarding, hardening or publication.
Use the subscription identity and preserve reviewed ownership; no broad recursive chown.

The following are **future operator steps after approval**, not commands run by Codex here.

### 1. Read-only target and backup gate

Verify real path, exact home/siteurl, development/staging environment, DB/prefix and active
plugins from the actual WordPress configuration. Capture versions of WordPress, PHP,
MariaDB/MySQL and Kadence, storage engines, cache/auth behavior and a metadata-only baseline:
public page/menu IDs and counts, active plugins and the disabled inquiry setting. Explicitly
confirm emergency is inactive. No site setup, permalink regeneration or content rebuild.

Verify the fresh scheduling prefix currently has **zero** tables. The expected future names
are `TXGEh_csf_sched_` plus settings, resources, appointments, allocations, commands, audit,
riders, horses, availability, restrictions, details, history, slots and participants.
Any existing table needs a reviewed existing-state/recovery plan instead of this first-install
proposal. Never query or migrate a fallback database.

Make a **fresh target-bound full DB/wp-content backup** outside the public root using the
existing backup process; preserve config privately through the existing backup facility.
Record capture time, exact target, archive/export checksums, ownership, restricted modes,
off-server recovery location and restore evidence. Verify export readability/representative
counts and rehearse the approved restore to an isolated target. Retain prior historical
backup evidence, but do not claim an older pre-photo/pre-calendar bundle captures this state
or that its structural restoration proved current functional recovery.

Freeze unrelated sandbox edits for this change window. Baseline and rollback must describe
this actual current site. Current fresh backup/restore evidence and any unsupported PHP/DB
version/engine must be resolved before file copy, not after an unsuccessful migration.

### 2. Exact scheduling-only file comparison and activation

Stage an archive of the pinned SHA privately. Extract only the scheduling plugin and its
checksum manifest; exclude test fixtures, tests, screenshots, WordPress core, vendor,
node_modules, local configuration and all other plugin directories. Verify all 15 hashes
and absence of extra plugin files before comparing the exact destination.

The existing generic website deploy script is not assumed to support scheduling; do not
expand it or run an all-plugin deployment. For an approved first installation, the operator
may compare precisely these two directory endpoints (no parent-directory sync or deletion):

```sh
PILOT_SOURCE='6f5a9bf51747399a309b39637a135841fe4cf2a1'
PILOT_TARGET='/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'
PILOT_URL='https://sandbox.cornerstonefarmomak.com'
# PILOT_RELEASE is the private verified extraction of PILOT_SOURCE, not another checkout.
(cd "$PILOT_RELEASE" && sha256sum -c docs/test-results/scheduling-staff-files.sha256)
rsync -anic --no-owner --no-group "$PILOT_RELEASE/wp-content/plugins/csf-scheduling/" "$PILOT_TARGET/wp-content/plugins/csf-scheduling/"
```

Review/save the comparison privately. It must propose only the absent scheduling directory
and those 15 files. No `--delete`, csf-core/emergency, theme, page/menu, installation script or
production change is allowed. Once that concrete comparison and scope are approved, the
corresponding file apply is the same exact-endpoint rsync without `n`; rehash the installed
15 files and confirm their version. Activate **only** `csf-scheduling` on this exact root.
Activation changes WordPress active-plugin metadata but must create zero scheduling tables,
records or grants. Verify that property and that all other active-plugin states are unchanged.

### 3. Preview, review and explicitly apply the schema

Select an **owner-verified numeric operator ID** with manage_options. Do not infer Beth/Kris
IDs from display names/emails or automatically create users. With the plugin active, the
existing `wp --path="$PILOT_TARGET" --user="$PILOT_OPERATOR_ID" csf scheduling migrate`
is a **read-only preview**. Capture its actual-prefix 14-table DDL/physical inspection and
compare it with the tested [v2 schema](test-results/scheduling-calendar-preview.txt).
MySQL/MariaDB + InnoDB/expected collation, exact schema and future-version checks must pass;
SQLite/nontransactional storage, drift or unknown existing data blocks application.

The required migration constant must not be enabled globally in wp-config. Inspect its
current defined/value status read-only first. If an existing configuration explicitly defines
it false, stop for policy review; do not override that protection with a CLI bootstrap. After migration
approval, the operator can create a private, owner-only CLI bootstrap outside the public root
containing only `<?php define('CSF_SCHEDULING_MIGRATION_ALLOWED', true);`. Its path is
`PILOT_MIGRATION_GATE`; it contains no credentials. Use it for this one guarded invocation:

```sh
wp --path="$PILOT_TARGET" --user="$PILOT_OPERATOR_ID" --require="$PILOT_MIGRATION_GATE" \
  csf scheduling migrate --apply --expected-url="$PILOT_URL"
```

Remove that temporary CLI bootstrap after the command. Do not alter completed environment
constants or leave a migration flag in web configuration. Inspect all 14 physical tables,
schema version **2**, seed/audit identity and empty appointment/resource/roster counts.
Re-preview/reapply idempotency only through the same authorized gate if needed. Interrupted
DDL is unready; inspect its physical state and captured error before any reviewed retry.
Never manually stamp a version, convert an engine or delete unexplained tables to force success.

### 4. Staff identities, policies and synthetic records

Before provisioning, obtain the actual owner-approved staff IDs and account/MFA evidence.
Confirm their WordPress login and Basic Auth independently on the protected site. The
foundation's provision command grants **view/manage/confirm/audit** to the verified allowlist;
review that scope, operator inclusion and any reduced-access requirement explicitly.
Generic administrators never become schedulers merely by having that role. Do not run
provisioning against guessed IDs, and do not mistake an instructor resource for a user account.

Read the current singleton revision, choose a new UUID and use the existing explicit
`csf scheduling provision --user-ids=... --owner-verified --revision=... --command-id=...`
under the verified operator. No direct SQL/user-role edits. Configure the owner-reviewed full
policy via the existing `configure --file=... --revision=... --command-id=...` service using
an authorized staff identity. Record command IDs/revisions privately; never repeat an uncertain
command with a different ID until the original outcome has been resolved.

**Inputs still needed:** timezone; all seven service-hour days; enabled service names,
capacities/durations, required instructor/arena/location/horse kinds, school/client/either
sources and travel needs; daily/consecutive workload and recovery limits; training enrollment
cap; Academy disabled/enabled/season/capacity decision; preferred off-site day and travel
buffer; verified staff permission/MFA roster; actual suitability/availability decisions.
No null/unknown policy or horse availability should be silently made permissive.

For the pilot, use only agreed fictional **Pilot** records, finite test availability and
explicit sample suitability. Beth/Kris use the native forms, not JSON. Do not execute local
test runners on the VPS: they deliberately require disposable `/test/site/`/fixture DB guards.
Never install the stand-in theme or copy a local wp-config. Actual-client onboarding and
signature evidence remain absent; evidence flags are manual and must not be represented as
signed waivers or approval to start a real service.

### 5. Protected synthetic acceptance and no-change verification

Run the [walkthrough](13-scheduling-staff-acceptance.md) on actual Beth/Kris devices and the
real protected WordPress/Kadence/DB stack. Record each task, reviewer, device/browser and
outcome privately. Test day/week/filter, four riders/two horses/recovery, partial-slot title
edit, client horse, missing availability/suitability, welfare block/release, moving/canceling,
clearance warnings, two staff saves, stale drafts and exact uncertain-command replay.
Do not deliberately damage a live connection/database to simulate hardware failure; injected
failure tests belong to disposable fixtures.

Verify private page/tool access for owner-approved roles, logged-out and missing-nonce
requests, Basic Auth, MFA and no private response caching. Logged-out Basic Auth HTTP401 is
expected. Check actual headers/proxy behavior, not just plugin nocache_headers. Review for
private data in URLs/logs/public REST/search; static JS/CSS must contain no records.

Compare public page/menu metadata and active-plugin/inquiry baselines. Smoke-check Home,
Lessons and Contact on phone/tablet/desktop: Contact Beth to Schedule still links to Contact,
no public self-booking or inquiry form appears, emergency stays inactive and no production
operation occurred. Preserve existing header/footer/photos/owner edits; do not repair unrelated
website issues as part of this pilot. Any compatibility, privacy, policy or staff usability
failure blocks real bookings and needs a recorded follow-up.

### 6. Containment, rollback and operational decision

If acceptance fails, first deactivate **only scheduling**, retain its data/audit and verify
that public website, inquiry and emergency states match the baseline. This is containment,
not a claim of full rollback: custom per-user grants/tables can remain inert and must be
tracked privately. There is no automatic destructive down migration.

A full rollback requires review of any changes since the fresh backup. Preserve/export
post-install scheduling records privately before restoring a DB. Do not restore the whole
website DB over later owner page/photo/menu edits without reconciliation/approval. Use the
matched backup and reviewed plugin version, verify restored table/plugin/grant/page/menu
state, and rerun private/public checks. Removing recorded pilot grants or synthetic data
also requires an explicit reviewed cleanup; do not delete audit history via ad hoc SQL.
The local restore results prove fixture mechanics, not completed protected recovery.

The owner must separately accept the synthetic pilot, policy/permission mapping and device
results before considering real appointments. Household onboarding, verified signatures,
service-start enforcement, payments, recurrence, notifications, emergency safety release and
production launch remain separate decisions. No step in this proposal enables inquiries,
emergency operations or automatic public booking.

## Sprint #11 — Phase 1 evidence and approval checklist (November 2, 2026)

Read-only preparation is complete; **the approval gate remains closed**. The [preflight report](test-results/scheduling-pilot-preflight.md) records direct target observations, [15-file proposed diff](pilot/scheduling-install-diff.json), exact-prefix [14-table DDL review](pilot/scheduling-v2-TXGEh-proposed.ddl.txt), retained backup evidence and limitations. The source pin/tree and six-step proposal above remain authoritative. No server installation/migration/provisioning, fresh backup or restore has occurred in this phase.

Execute this checklist only after explicit owner authorization, in order; stop on any failed prerequisite:

1. Record approved scope, private operator/staff ID mapping, permission scope, effective MFA evidence and [fictional policy](pilot/README.md) approval. One unidentified administrator is not verified Beth/Kris access. Account creation/MFA infrastructure changes need separate scope; do not expand the 15-file scheduling release.
2. Recheck exact root/URL/DB/prefix, absent plugin/zero owned tables, active plugins, disabled inquiry, inactive emergency and public-content/settings baseline. Freeze unrelated edits for the change window. Any drift requires a new reviewed comparison.
3. Create a fresh target-bound DB/wp-content backup and paired private configuration recovery. Verify checksums/export, record capture time and recovery custody, and perform an **isolated functional restore** including login/rendering. Preserve historical structural evidence; do not recreate its old scratch resources. A failed or incomplete fresh recovery blocks installation.
4. Privately stage only pin `6f5a9bf51747399a309b39637a135841fe4cf2a1`. Verify subtree and all15 hashes, no extra files; perform/save the exact-endpoint rsync dry run above. Require only the approved15 additions. Copy only scheduling with no deletion; normalize ownership/modes solely inside its new directory; rehash installed files. Activate only scheduling and verify zero tables/grants/data from activation.
5. As the verified numeric operator, run the existing read-only migration preview and compare actual14-table DDL/physical checks with the review artifact. Record a clean first-install result. Only then apply through the approved one-shot private CLI gate and exact expected URL as above; remove gate, inspect14 tables/version2/seed/audit and empty domain records. Stop on drift/partial DDL; no manual schema stamping.
6. Provision only approved existing IDs with the existing owner-verified command/revision/UUID flow; configure the approved full fictional policy through the existing service. No direct SQL/grant edits, guessed IDs or globally enabled migration gate. Record changes privately for later reconciliation.
7. Create the agreed Pilot-only fixture using native forms (four riders, two school horses, client horse, instructors/arenas/location, finite availability/suitability). Follow [the complete acceptance walkthrough](13-scheduling-staff-acceptance.md): private lesson; four-rider/two-horse rotation with recovery; haul-in; availability/suitability failures; welfare restriction/release; resource/rider clashes; editing; cancellation/history; confirmed-but-uncleared warnings; two distinct staff simultaneous saves and stale drafts; uncertain-command replay. Record actual results/failures, not expected outcomes. No real records.
8. Test protected-site privacy/Basic Auth/MFA/nonce/capability/cache behavior and private calendar at390/768/1280px with keyboard/accessibility checks. Smoke-check public Home/Lessons/Contact; compare public content/theme/menu/inquiry/emergency baselines, allowing only approved scheduling active-plugin/role changes. No unrelated repairs. Beth/Kris independently record their hands-on outcomes; operator/browser results do not substitute for their acceptance.
9. On failure, contain by deactivating only scheduling; preserve owned data/audit and record remaining inert grants. A full rollback needs matched-backup review, preservation/reconciliation of later edits/records and explicit restore/cleanup authorization. Verify restored files/tables/grants and public/private behavior. Never silently drop tables, delete audit history or restore the whole DB over later owner content.
10. Deliver observed compatibility, failures, backup/recovery evidence, no-change checks and Beth/Kris outstanding tasks. Leave real operational scheduling closed pending separate final owner authorization. Production, inquiries, public self-booking and emergency management remain excluded throughout.

**Owner approval requested:** authorize the fresh backup/isolated recovery and the conditional synthetic pilot sequence above; privately identify the operator and each staff account with MFA evidence, and approve/revise the proposed fictional policy. Installation/migration/provisioning remain blocked until those prerequisites are satisfied. This gate comes from Issue#11 and the owner's Phase2 instruction, not an inferred new approval requirement.

## Sprint #11 — Gate A completed, Gate B held (November 1, 2026 PST)

After explicit owner approval limited to fresh backup and isolated functional recovery,
[Gate A passed](test-results/scheduling-pilot-gate-a.md). Fresh private bundle:
`csf-gate-a-20261102-004845-8d96d856`, captured November 1 16:48:45–16:48:54 PST
(November 2 00:48:45–00:48:54 UTC). Six standard and 12 supplementary recovery checksums
passed; exact config recovery is included privately. All 4,479 restored files and 12 tables
matched source hashes/row fingerprints; isolated MariaDB integrity, cloned-account login,
authenticated dashboard, Home/Lessons/Contact and Kadence stylesheet checks passed.

The isolated drill used a separate MariaDB process/datadir/socket and loopback-only network
namespace, with PHP 8.3.36's built-in HTTP server. It did not reproduce Plesk/Nginx/Apache/FPM,
MFA or Basic Auth. Temporary cloned credentials were cleared and services stopped; the new
private recovery resource is retained 0700. Historical recovery resources are unchanged.
Final live comparison matched all 12 DB fingerprints and 4,479 files; the existing backup
verification script passed against this exact bundle and new functional evidence. Off-server
encrypted custody remains unverified. See the report for job IDs, failed fixture attempts,
checksums, scope and limitations. Do not infer full disaster-recovery or operational acceptance.

**Stop remains in force:** no scheduling files/activation/migration, staff creation/grants,
policy application or pilot records were authorized or performed. Gate B needs separate owner
approval plus verified staff identities/MFA and fictional policy. Gate C real operations remain
unauthorized. Refresh the backup when stale or after intervening changes; never restore over
later owner edits without reconciliation. Existing inquiry/emergency/production boundaries
and the pinned scheduling release/checklist above remain unchanged.

## Gate B — owner review only

The [single owner decision package](pilot/gate-b-owner-review.md) separates account creation,
MFA choice/install/enrollment/enforcement, fictional policy, scheduling files/activation,
schema apply, per-person grants, policy application, synthetic records, testing and recovery
into independent decisions. Every Gate B execution row remains on Hold. Review approval is
not permission to execute a later row. The MFA candidate requires a separately pinned release
and file/database comparison; its files are outside the scheduling-only 15-file release.

This package preserves tracker commit `1b9a4182ff0e3229dfac5f945ae5788e732f049a` and the
existing scheduling source pin/tree. Preparing it made no server calls or changes. Existing
Gate A recovery remains evidence, not a new execution approval or a backup of future changes.

## Issue #12 — new granular-permissions candidate (local only; all Gate B execution held)

The tested local candidate is **scheduling 0.3.0/schema 3**, with the native Team & Permissions
owner dashboard. The old 0.2.1 source pin/15-file manifest remain historical evidence, **not an
approved installation or acceptable blanket provisioning path**. Review the new exact candidate
pin, 19-file manifest, DDL and validation in [the release report](test-results/scheduling-permissions.md).
[The current owner package](pilot/gate-b-owner-review.md) separates every approval below.

1. Reconfirm scope and exact protected sandbox root/URL/wp_mdf4x/TXGEh_ from the canonical target.
   Record verified numeric operator, master and staff identities without name/email inference.
   Resolve the actual approved MFA mechanism **and effective-session adapter**; absence defaults
   to denied access. Do not install a security provider, adapter, account or WordPress role as
   part of the scheduling-only file copy. Explicit approval for each prerequisite is required.
2. Freeze unrelated edits, refresh the exact-target backup and private config/source recovery
   after any intervening website/account/MFA changes. Verify six standard artifacts plus recovery
   supplement, age, hashes and isolated functional recovery. Gate A is retained evidence, not
   authority to create a new backup or proof of full Plesk/MFA/off-server recovery for later state.
3. After I-Files approval, stage only the new pinned scheduling directory and its 19-file manifest
   privately. Recheck destination; the last protected observation was absent, not a current dry
   run. Require an exact endpoint-only comparison with no core/emergency/theme/parent deletion,
   no tests/vendor/node_modules/config/MFA fixture. Copy only approved files, modes 0755/0644 and
   subscription ownership, then rehash. Activate only scheduling; verify zero schema/grants/data.
4. After I-Schema approval, capture the real v3 guarded preview. Fresh install owns 15 tables;
   additive v2 upgrade adds only authorization(id,registry_json) and retains the physical 14-table
   contracts. Review [source-derived DDL](pilot/scheduling-v3-TXGEh-proposed.ddl.txt), engine,
   collation, future/drift checks and any existing state before the one-shot CLI migration gate.
   Apply only with exact home/siteurl, then remove the temporary gate. Verify schema 3, singleton,
   seed/audit and domain counts. No automatic staff/master promotion; legacy IDs are quarantined
   inactive/unverified with provenance retained. Stop on partial DDL, unknown data or failed audit.
5. Master identity is **another approval**, not migration/provisioning. The existing technical
   operator needs manage_options; chosen farm owner need not have it. Verify exact numeric ID,
   independent MFA and strong login, backup and owner acknowledgment. Inspect that the separate
   identity gate is undefined; an explicitly false guard stops review. Use an owner-only private
   CLI bootstrap defining only CSF_SCHEDULING_MASTER_BOOTSTRAP_ALLOWED=true, never a global web
   flag. Invoke the existing `csf scheduling master` with --user=<verified operator>,
   --user-id=<verified owner>, --owner-verified, --expected-url=<exact sandbox URL>, current
   --revision and unique --command-id, under --require=<private gate>. Remove the gate and verify
   master/audit/mirror/MFA and no WordPress role promotion. --transfer is a separately approved
   recovery ceremony; it disables the former master's farm access/sessions. No UI self-assignment.
6. Initial G decisions name exact group/default/override/resource-scope mappings. The verified
   master uses **Team & Permissions** to preview/save. Ordinary later employee/group changes need
   no developer CLI. Preserve explicit denies and audit every action. Recheck actual MFA after
   raw capability mirrors change; never revive the retired four-capability provision command.
   Configure only approved fictional policy via the existing controlled service, then create
   approved synthetic fixtures. No invitation/outbound messages or actual client records.
7. Run [owner/staff acceptance](13-scheduling-staff-acceptance.md), protected-stack privacy/MFA/
   proxy/cache/device checks, concurrent bookings and welfare/clearance/cancellation scenarios.
   Beth/Kris record their own results. Compare content/file/menu/theme/inquiry/emergency baselines,
   accounting only for separately approved account/MFA/scheduling changes. No real use follows
   automatically from a successful synthetic pilot.
8. On failure, only specifically approved containment deactivates scheduling, retaining audit/data.
   A full matched-backup rollback must preserve/reconcile subsequent site/account/MFA/booking
   changes and needs explicit restore/cleanup scope. No table dropping, audit deletion, grant
   SQL edits, destructive down migration or MFA bypass. Pinned v2 code correctly refuses v3.

No protected installation, MFA/account change, bootstrap, grant, migration or pilot occurred in
Issue #12. Production, inquiries, emergency management and public website configuration remain
excluded. New code completion is not Gate B or real operational authorization.


If a sole master's post-commit mirror fails, farm access remains denied. An independently verified
original operator may, under separate approved private identity-gate scope, replay the exact original
bootstrap actor/body/revision/UUID to reconcile current mirrors without altering authority or adding
an audit event. Preserve that command identity privately. If original verification/command custody
cannot be established, stop for separately approved identity transfer/recovery; never use ad hoc SQL
capability grants or a global bypass. Normal employee changes remain native owner-dashboard work.

Issue #12 immutable local implementation pin: **c04a24387e3d26f7cb0a7a13683a3981675a086b**,
plugin tree **852837cb22fcf2b9d219cfe918c43d567cc5cc4e**. See the 19-file release report
and inventory. This identifies reviewable code; it is not Gate B installation, identity/MFA,
migration, grant, policy or pilot authorization. The historical 0.2.1 pin remains preserved.

## Issue #13 — MFA release review and additional Gate B holds

Read [the security/release report](test-results/scheduling-mfa.md) and the revised
[single owner decision package](pilot/gate-b-owner-review.md). Recommended selection is pinned
Two-Factor 0.17.0 + separate csf-mfa0.1.0 with scheduling0.3.1; no owner selection/execution is
assumed. Original c04a243/d09ad92 and historical6f5a9bf remain immutable provenance, never
silently updated installation pins. Physical scheduling schema remains v3/15 tables.

Before any future installation approval:

1. Resolve M-Choice and exact source-build versus official-ZIP artifact selection. The vendor
   publishes ZIP SHA25659d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473;
   local tests did not retrieve those ZIP bytes. Independently hash/unpack/compare the selected
   provider artifact. Review all21source-distribution files, separate3adapter files and19scheduling
   files. New plugin directories only; no parent sync/delete or existing core/emergency/site edits.
2. Complete safe operator/admin mandatory-MFA coverage and the separately reviewed master
   identity ceremony. CLI --user cannot establish browser MFA; O-Master is blocked, not exempt.
   Never copy browser tokens into CLI flags/files or use the synthetic filter outside tests.
3. Verify exact protected identity/base roles/auth paths and compatibility on an isolated
   representative PHP8.3.36/MariaDB10.6.23/WordPress7.1.4/Kadence/Plesk/HTTPS/BasicAuth stack.
   No new real identities, provider installation, switch, migration or records are authorized here.
4. Obtain independent R-Backup scope; capture current exact-target DB/wp-content/config/salts,
   private provider account/meta/option/session state and scheduling data. Hash artifacts, verify
   encrypted recovery custody, and restore/functionally test privately. GateA only covers its
   historical capture. Do not expose real secrets, accounts or restored private data publicly.
5. Review M-Install, M-Adapter, M-Enforce and M-Notify separately, including the private
   CSF_MFA_ENABLED switch, site-wide TOTP/backup-only restrictions, eight-hour proof TTL,
   provider email effects, update pinning and session/credential revocation. No provider uninstall.
6. Each A/M-Enroll decision names verified numeric identity privately, base role, device/recovery
   custody and supervised native login/recovery/negative results. Initial account creation and
   enrollment grant no farm rights. WordPress Administrator never implies master identity.
7. Only after missing security designs/acceptance and exact I-Files/I-Schema/O-Master/G/P/C/D/T
   approvals may later installation/pilot work execute. Activation does not migrate or grant.
   Use the existing guarded exact-target schema preview/apply and transaction/audit boundaries.

Containment keeps default-deny: stop/deactivate scheduling/adapter under separately authorized
scope, retain provider data and scheduling audit; do not disable global MFA or guess a master.
Restoring old files alone does not undo account/MFA/session state. Any full restore/cleanup
requires a matched artifact, later-change reconciliation, separate authorization and post-restore
session revocation/credential review. Production/inquiry/emergency/public content remain excluded.

Issue #13 immutable implementation pin: **2c08321d98566bc50ccb6d62712abf798cc16d0d**; scheduling tree
**03884538be197ea1c97c5d65aef65122cd783f69**, adapter tree
**d50ee60a71bccd55217374af1f40c3d8c173d43d**. Read the43-file proposed inventory and
separate artifact/identity/security holds. No protected installation approval follows.
