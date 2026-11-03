# 05 — Backup, Rollback & Deployment

## Current next decision — isolated functional restore

The [single R-Restore authorization card](pilot/r-restore-approval.md#current-owner-authorization-card--one-private-functional-restore)
is the current execution proposal. Public code is reproducibly SHA-pinned; actual source and
exclusive destination stay privately bound. Missing private bindings make the public template
fail closed. This preparation does not authorize the restore, a live rollback or GateB.
[Validation and diagnostic evidence](test-results/recovery-readiness.md#isolated-restore-executable-preparation--november-3-2026-utc)
distinguishes local synthetic tests, actual read-only preflight and pending farm recovery.


## Latest result — approved local capture complete, restore held

One owner-approved capture of8f8fc51 completed LOCAL_CAPTURE_VERIFIED/exit0. [Exact set/manifest/receipt evidence](test-results/recovery-readiness.md#approved-local-capture-completed--sanitized-status)
records sanitized status; exact recovery bindings are retained privately. The approved code's built-in integrity checks passed;
functional restoration, off-server custody and GateB acceptance are not proved. Do not rerun
that fixed-token command or alter its set. Next prepare an exact isolated R-Restore package for
separate approval. No transfer, restore, account/security change, install/migration or deployment
is authorized by capture success. The older preparation/approval language below is historical.

## Current R-Backup capture candidate — local-only, approval required

[One-page owner approval card](pilot/r-backup-capture-approval.md) and [nine-file source/payload manifest](../scripts/recovery/LOCAL-CAPTURE.sha256)
now define one exact server-local capture. This candidate replaces the legacy manual credential/
protected-path inputs. Standing diagnostic authority and inspection PASS do not authorize its
new sensitive artifacts. No farm-server operation occurred during preparation.

Entrypoint: [capture_local.py](../scripts/recovery/capture_local.py), embedded by
[build_local_capture.py](../scripts/recovery/build_local_capture.py) into one hash-verified command.
It extracts existing local credentials without PHP execution; derives canonical config sources,
private aliases and generated symlink topology with the successful inspection's bounded discovery;
validates source phase; then runs the existing B1–B3 engine. Target/private mappings and original
symlink attributes are saved only in target.json/file-attributes.json inside the recovery set.
Canonical content/reference/alias changes are rechecked before a complete receipt can be issued.

The candidate token/destination is fixed in source; stdin cannot override target/path/token/custody
or supply credentials/SQL. Its exact five fields are actor_ref, approval_ref, start, end,
authorization; marker must equal one-local-backup-capture. Window<=1800seconds; source/DB/
protected/disk/count/time caps are fixed. No source staging, packages, external transfer or restore.

Data artifacts are exactly database.sql.gz, wp-content.tar.gz, wordpress-root.tar.gz,
wp-config.php.recovery, protected-configs.tar.gz, target.json, file-attributes.json,
before.catalog.json, before.rows.ndjson, after.catalog.json, after.rows.ndjson,
before-files.sha256, after-files.sha256, capture-start-utc.txt, capture-end-utc.txt,
drift-review.json. SHA256SUMS and receipt.json bring the accepted set to18 named files.
No named credential client, raw SQL spool, operator log or extra export is required.

Each diagnostic snapshot uses one read-only MVCC connection; native dump uses its own consistent
transaction with utf8mb4, quick/skip-lock/no-tablespaces/hex flags on this DB only. InnoDB phase
and zero routines/events/triggers are guarded before export. Before/after row/schema evidence
is retained without wildcard exclusions or cause attribution. No dump/live-equivalence or
frozen-site claim; any drift is review-required, not automatic PASS. No cron/cache/site freeze.

Caller must require terminal succeeded/exit0, LOCAL_CAPTURE_VERIFIED and matching confidential
receipt/manifest hashes. complete/review-required/incomplete are distinct. A partial/forced-kill
set stays restricted and unaccepted; existing backups are never deleted. O_TMPFILE/memfd material
closes on process death. Recovery cannot be claimed from hashes alone. [Local validation](test-results/recovery-readiness.md#server-local-capture-candidate--november-2-2026)
includes a real synthetic MariaDB concurrent-update snapshot/import test; actual dump privileges,
source drift, volume and operational restore remain unverified until separately authorized work.

## Latest recovery prerequisite status — bounded inspection passed

Owner-adopted [supervised autonomy](DECISIONS.md#november-2-2026--owner-adopted-supervised-autonomy-policy)
is recorded in e2790d4. Subsequent reasoned bounded read-only diagnostics/local corrections are
[documented with sanitized terminal evidence](test-results/recovery-readiness.md#supervised-diagnostic-session--november-2-2026).
Final inspection CHECKS_PASSED/exit0 establishes those observation-time checks only. No fresh
backup/restore, software install, schema, account/MFA/security or deployment operation occurred.
Prepare an exact server-local R-Backup capture proposal for separate approval; do not use the
legacy manual-input capture or infer permission from diagnostic PASS. All recovery/deployment
holds remain. Older per-attempt diagnostic authorization text below is historical after adoption.

## Current diagnostic revision — local only, one new approval needed

The first approved server-local command stopped BLOCKED/exit2; the specific failed guard and
underlying cause remain unknown. New fixed allowlisted code/stage diagnostics replace the generic
response, with no hashes/paths/errors/values in either PASS or BLOCKED output. Read scope/guards
are unchanged. Use the [current approval card](pilot/r-backup-approval.md#single-revised-inspection-authorization-card)
and current local-inspection manifest; previous command approval cannot transfer to new bytes.
No server/Gateway call or retry occurred in this revision. Two-renewal behavior is not implemented
or authorized for this failure. Implementation2fd02a2 and all deployment holds remain intact.

## Current inspection revision — server-local credentials, not executed

Owner directs a practical server-local read of the exact sandbox wp-config and bounded discovery
of its Plesk config references. Use only the [fresh authorization card](pilot/r-backup-approval.md#single-revised-inspection-authorization-card)
and [new source](../scripts/recovery/inspect_local.py)/[hash manifest](../scripts/recovery/LOCAL-INSPECTION.sha256).
It replaces manual credential/path JSON and stored-code selectors for inspection only. Existing
root SSH access is owner-reported, not verified here; reviewed scope is sandbox-only, with no sudo
request or write. A new command hash requires fresh execution authorization; previous approvals
are not transferred. No server/Gateway call occurred. Capture/transfer/restore/Gate B remain held.

## Recovery and identity preparation — separate decisions, no execution

November 2, 2026: [current assignment](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6585062869)
is **local documentation/tests/proposed commands only**, with no farm-server connection.
Implementation remains2fd02a2; account correction17fd654 is preserved. No new backup/restore,
account, factor, installation, schema or production operation is authorized or claimed.

Owner decision entry points: **[R-Backup only](pilot/r-backup-approval.md)** and then, after its
exact receipt exists, **[R-Restore only](pilot/r-restore-approval.md)**. Both are HELD. Accepting
the document is not execution permission. Neither authorizes later account/MFA/Toolkit/calendar
changes. [Single owner worksheet](pilot/gate-b-owner-review.md) records the remaining choices.

These two recovery operations use the existing owner-approved infrastructure/Gateway executor;
they do not require a provisioned Farm Master, new staff accounts or WordPress MFA first.
Later private WP identity/security decisions may proceed in parallel without creating a circular
backup prerequisite. No temporary cloned login is evidence of the real person's authentication.

### R-Backup proposed command catalogue

Local engineering is complete: [standalone executable](../scripts/recovery/payload.py),
[reviewable source](../scripts/recovery/backup.py), deterministic build, strict archive/receipt
validators and [release hashes](../scripts/recovery/PAYLOAD.sha256). The earlier b5ea68e missing
helpers are implemented and tested with synthetic private bindings. Historical snippets are not
an execution substitute. Application implementation remains2fd02a2.

**Only next decision:** [read-only prerequisite approval card](pilot/r-backup-approval.md#owner-authorization-card--read-only-prerequisites-only).
Its exact hash-verified command runs code/bindings in memory; no source staging or temporary file
is needed. The [final operator checklist](pilot/r-backup-approval.md#final-operator-checklist--redacted-no-execution)
distinguishes verified tool contracts/local tests from private selectors/operator/live checks
that remain unverified. The inspection command/runtime release is unchanged from48ba6b2. No Gateway/server call occurred. Current private bindings/installed compatibility
remain unverified; capture, encrypted transfer, restore and all Gate B deployment gates stay held.

B1–B3 inventory/MVCC diagnostics/consistent dump/config archives/byte and header verification/
manifest/receipt/drift/cleanup run as one bounded lifecycle. Capture1770s TERM+30s kill grace;
inspection115s+5s; no source mutation, WP bootstrap, global locks, maintenance/cron changes,
package installation or user actions. Raw SQL spools and credential descriptors are anonymous;
only the new private capture artifacts are retained. See [technical contract](pilot/recovery-command-catalogue.md#current-b1b3-executable--supersedes-the-historical-fragments-below)
and [local validation evidence](test-results/recovery-readiness.md).

### R-Restore proposed command catalogue

Use [R1–R3](pilot/recovery-command-catalogue.md#r-restore-command-payload) only after independent
approval of the **specific new bundle/manifest digest**, private recovery/run paths, existing
tool paths/hashes and clone credential-test choice. A historical GateA bundle, `latest` or a
different source phase is not an implicit substitute. The input binding remains unavailable
until R-Backup actually succeeds and is reviewed.

The dedicated mount/network/PID namespace masks live vhosts/control sockets; a private MariaDB
daemon/socket/datadir and DB-scoped import identity cannot use the host connection. Restore
only into the new private tree. Verify archive/schema/data/files before clone rewrites; then
use approved clone-only config/loopback/mail/cron isolation and any explicitly authorized cloned
account test. Stop recorded child processes, retain private evidence and never import into the
live DB or overwrite later owner changes. App recovery via PHP built-in HTTP is not restoration
of Plesk/BasicAuth/Toolkit or proof of real MFA/off-server disaster recovery.

### Deterministic baseline and drift review

The normative [baseline contract](pilot/baseline-contract.json), proposed pure
[SELECT-plan generator](pilot/baseline-select-plan.py.txt) and synthetic specification tests
define the new process. They are not installed on the farm or part of the45-file plugin release.

1. Bind source URL/root/database/prefix, phase, version, physical schema/index descriptor and
   origin. A live diagnostic and an artifact-derived restore are **different origins**. Obtain
   the owner/custodian's independent expected snapshot/manifest digests; a file cannot authenticate
   itself merely by recomputing its own checksum. Do not mix old observations/new artifacts.
2. Coordinate a quiet edit window with the owner; do not freeze the website technically, enable
   maintenance, disable live cron, set global read-only or take table locks to force equality.
   HTTP protection stays unchanged. If ordinary traffic persists, record it and its limits.
3. Read only the bound DB's catalog. Require known table set/engines, ordered unique primary keys,
   column types/defaults/collations and all indexes. Separate AUTO_INCREMENT counter observations
   from physical column attributes; keep them rather than resetting/ignoring them. Recheck catalog
   after capture; schema changes invalidate an ordinary same-schema comparison.
4. In **one connection** set session UTC/REPEATABLE READ/READ ONLY and begin a consistent snapshot;
   read every table's rows before COMMIT. Derive each non-NULL cell from exact bytes using
   `HEX(CAST(column AS BINARY))`, retain NULL separately and length-frame ordered cells. Hash row
   content and ordered primary-key identity; never coerce booleans/numeric strings, unserialize
   options or strip audit timestamps. Reject duplicate/missing row keys. Native data values and
   account fields are not printed; row hashes/option labels remain in restricted private files.
5. Sort table/key records for comparison, not for dropping evidence. Capture time/row order are
   provenance; row contents, schema, file modes/source changes and audit time remain meaningful.
   The locally tested generator emits only the twelve native pre-install table SELECTs inside
   one read-only transaction. A future27-table/post-security phase needs a reviewed new binding,
   not automatic adoption, ignored tables or a changed application source pin.
6. Report added/removed/modified rows by private locator. Security-critical options include
   home/siteurl, admin/recovery contact, registration/default role, active/auto-update plugins,
   native role definitions, theme, factor/provider/epoch settings, inquiry and cron schedule.
   **Every users/usermeta/scheduling/audit change is critical**, including sessions, factors,
   recovery/application passwords and capability mirrors. No wildcard transient/cache exclusion.
7. Only exact known doing-cron lock/timeout option names are **volatile candidates** for a review
   projection. Preserve them in raw drift and require acknowledgment; do not assert cron wrote
   them. Update caches and unknown transient/security keys remain unclassified/review-required.
   The stable projection may help scan changes but can never declare the whole DB unchanged or
   authorize a restore/apply. Unexpected sensitive differences get focused investigation; routine
   drift alone is not evidence of compromise or authority for broad forensics.
8. The dump's independent `--single-transaction` instant differs from before/after diagnostic
   instants. Preserve all timestamps/rows/file observations; no atomic DB/filesystem or shared
   snapshot claim. R-Restore derives the authoritative artifact baseline **before** clone URL/
   account/config changes. Acknowledge/reconcile post-capture edits before any separately approved
   live recovery; never copy old options or discard new pages/photos/accounts to make hashes agree.

Public receipt: source/run/interval, artifact digests, counts/categories/acknowledgment state,
acceptance/limits and outstanding decisions only. No per-user row locators, private option keys,
UIDs, emails, raw values, credentials, cookies or private identity evidence in Git/chat. The local
tests use entirely synthetic bytes/data and cannot establish an actual server baseline or cause
of the earlier163-row options digest difference.

### Plesk and WordPress Toolkit compatibility rehearsal — not R-Restore

**Separate proposed gate T-AuthClone/T-Toolkit; HELD.** R-Restore is existing-site functional
recovery, not permission to install the security stack, create test accounts/factors, provision
a Plesk clone or change routing. Choose the exact private rehearsal target and permitted changes
before a later explicit approval. No compatibility assertion is made from static version checks.

1. Private mechanism inventory: verified operator identifies exact Toolkit version/site binding,
   browser login/SSO versus direct file/CLI maintenance versus authenticated API use; inventory
   client/mobile/publishing/monitoring jobs. Review selected target-only configuration and bounded
   redacted status evidence, not raw token/Authorization/launch URLs or staff fields. Zero current
   application-password records and a log sample cannot prove dormant dependencies absent.
2. Rehearse pinned Two-Factor0.17.0 + csf-mfa0.2.0 in an independently approved, mail-isolated
   private clone with synthetic operator/business identities, actual fresh TOTP/backup challenge,
   verified recovery custody, source hashes and private exact-ID mapping. No real factor/secret
   is reused. Clone WP sessions alone simulate an alternate-login boundary, **not actual Toolkit**.
3. Actual Toolkit SSO requires an explicitly approved private **Plesk-managed** clone/site binding
   or later protected window with fresh backup/recovery. A route-free PHP namespace cannot prove
   the real control-plane integration. If that target/mechanism is unavailable, record BLOCKED,
   not compatible or incompatible. No DNS/subscription/certificate/Plesk setup is implied now.
4. Establish baseline with enforcement off but provider/adapter independently approved; then
   enable M-Enforce **only on the approved rehearsal target**, complete matrix below and exercise
   recovery. Do not change the real administrator, disable MFA on the real site or bypass HTTP
   protection for the test. Keep independent trusted recovery available before any later live flag.

| Private acceptance path | Pass after selected enforcement | Fail / blocked condition |
| --- | --- | --- |
| Toolkit native login/SSO | No protected capability until this WP session completes approved MFA; supported revalidation unlocks only native role | Password-only privilege bypass; inability to complete factor/recover; actual path untested is BLOCKED |
| Toolkit direct file/CLI management | Privately verified maintenance function remains available through separately trusted operator surface, without becoming farm ownership/MFA proof | An essential approved workflow breaks with no approved replacement; do not invent how Toolkit works |
| Application-password API | Privileged API denied as intended; dependency owner approves retirement or a separately reviewed supported alternative | Undocumented essential use; silently reenable app passwords to make test green |
| Authenticated XML-RPC | Denied as intended; required clients have approved replacement/retirement | Unknown dependency or unauthorized XML-RPC exception |
| Native cookie/nonce REST | Actual own-session factor plus nonce/native role works; nonce alone cannot authorize | Privilege without completed factor, cross-user proof or leaked private directory |
| Public read REST/static HTTP | Legitimate native public-read behavior and existing BasicAuth boundary remain correct | Accidental whole-site denial, BasicAuth removal or credentials treated as MFA |
| Subscriber business/staff | Farm rights only after separately approved membership/ownership; no native WP/Plesk administration | Automatic admin/master promotion or identity inferred from a login string |
| Logout/revocation/credential rotation | Old session immediately loses protected access; fresh own challenge needed | Stale or restored/replayed proof authorizes |
| Recovery code | One use succeeds, replay denied; custody/rotation private | Codes printed, reusable replay or unverified live delivery |
| Lost factor / sole operator | Approved same-UID physical recovery demonstrated in isolation, enforcement retained | Bypass flag, replacement-by-name, disabled MFA or no safe recovery |
| Notifications/email | Only separately approved intercepted/test delivery path; no real send implied | Unexpected mail, recipient leakage or inquiry enablement |

Sanitized results must bind target/source/provider hashes, mechanism, settings before/after,
success/denial statuses, recovery and rollback evidence. Overall PASS requires every essential
actual mechanism, not just a simulated login. Essential FAIL/BLOCKED keeps M-Enforce held until
owner accepts a tested safe path. Native WordPress MFA does not secure the Plesk control panel;
its independent authentication/recovery remains an owner/hosting responsibility.

### Recovery approval decision summary

R-Backup may authorize only one bounded capture and explicitly selected encrypted custody.
R-Restore may authorize only one exact-artifact isolated functional rehearsal with its explicit
clone-only test choice. Neither authorizes T-AuthClone/Toolkit, account creation, factors,
mandatory enforcement, scheduling files/schema, ownership, employee grants, synthetic records
or real operation. Shared services/production/public site remain outside all proposed effects.
Original GateA evidence is preserved, not rerun or treated as a fresh authorization.

## Gate B current-server staged execution plan — November 2, 2026

**Proposal only; stop before all server modifications.** [Read-only current-server
assessment](test-results/gate-b-readiness.md), [single owner worksheet](pilot/gate-b-owner-review.md)
and [exact proposed file/config/database diff](pilot/gate-b-installation-proposed.json).
Implementation remains `2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`, Scheduling0.4.0/schema3,
adapter0.2.0 and official Two-Factor0.17.0. Historical commands/version plans below are not
authority for the current release. The readiness assignment did not execute these steps.

**Account-model correction:** HTTP Basic Auth is not the Plesk-created WordPress technical
administrator or a farm identity. Its human controller/UID remain privately unverified.
Recommend preserving that native admin for maintenance and separately approving Kris's
Subscriber business identity plus Beth's staff identity. This fits the existing distinct-UID
bootstrap; no new admin or same-UID exception is proposed. Different accounts do not prove
independent humans: owner must decide/verify controller independence before O-Master.
[Canonical account/bootstrap/UI design](01-architecture.md#accounts--access--corrected-identity-model-november-2-2026)
and the current owner worksheet supersede recent same-account reuse assumptions. No server
reinspection or change was performed for this local correction.

### Ordered checkpoints for separately authorized windows

1. **Private verification:** owner confirms the technical administrator, distinct Kris/Beth
   identities and intended authority through an independent channel. Keep numeric IDs/private
   logins/evidence in the approved private record. Confirm HTTP Basic Auth access, trusted
   recovery route, security notifications, external API/Toolkit dependencies and update custodian.
   Zero current application passwords does not approve disabling an unrecorded integration.
   Review critical settings and a fresh consistent baseline after the prior options digest
   difference (11 other tables/all4,479 files matched). Reported backend activity is plausible,
   not a proven cause or incident. Investigate unexpected sensitive differences; no broad
   forensic investigation, automatic repair or forced hash match is required by aggregate drift.
2. **R-Backup, then R-Restore:** authorize each separately for the exact sandbox root/URL,
   `wp_mdf4x`/`TXGEh_`. Freeze unrelated owner edits, capture current file/table/content/options/
   role/authentication baselines and a fresh DB/wp-content/root/config/salts recovery bundle.
   Use private0700 directories,0600 artifacts, SHA256 manifests, target binding, integrity and
   timestamps. Rehearse functional restore into network/private-DB isolation with outbound mail
   blocked; compare12 native tables/files before clone-only login changes and verify representative
   rendering. Recover config/secrets privately. Do not reuse live DB, expose a public clone or
   copy secret values into jobs/reports. Include exact backup/config restore evidence and operator
   recovery availability before approving security changes. Off-server custody needs its own scope.
3. **A-person:** approve only named account reuse/creation and baseline Subscriber roles, private
   handoff and explicit notification behavior. Verify existing administrator authority separately;
   do not rename/share/promote it, infer farm membership or grant future finance/emergency powers.
   Record each identity's baseline privately. Account creation alone grants no scheduling access.
4. **M-Install / M-Adapter:** recheck all three candidate directories are absent. Verify official
   ZIP91,837bytes/SHA25659d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473;
   verify exact21 provider and4 adapter files against the manifest before/after copying. Preserve
   subscription ownership, use0644 regular files/0755 new directories, no symlinks/extras/parent
   sync/delete. File copy and plugin activation are distinct approved operations. Only approve
   `CSF_MFA_ENABLED=true` under M-Adapter; mandatory enforcement is not an activation side effect.
   Security mail/credential custody must be reviewed before enrolling anyone. Vendor email/dummy
   source files remain in the exact official distribution but are not approved authentication factors.
5. **M-Enroll-operator / M-Recovery-operator:** verify actual TOTP plus a single-use backup-code
   challenge in fresh independent browser sessions; record success/denials without screenshots of
   secrets. Verify logout, revoked password-only sessions, trusted same-UID physical recovery and
   private recovery-code custody. Stop if the sole operator cannot recover. Use a supported provider
   procedure; no cookie export, fake factor/filter, password-only exception or farm-master CLI grant.
6. **M-Enforce:** approve `CSF_MFA_ENFORCE_ADMIN=true` only after operator/recovery/dependency
   evidence. Verify password-only admin/controller/role/API writes denied, safe own-profile enrollment
   grace, actual factor unlock and public read REST/static BasicAuth boundaries. Application passwords
   and authenticated XML-RPC become unavailable; Toolkit/SSO must also satisfy actual-factor proof
   before privileged web access. No Plesk/shared-service configuration change is implicit.
7. **M-Enroll-Kris / M-Enroll-Beth:** separately verify each approved identity's enrollment, fresh
   challenge, logout/recovery custody and no native WordPress administration. No farm grant yet.
   Enrollment alone is insufficient. Security plugin/activation/configuration changes can invalidate
   older proofs: reauthenticate; never weaken the verifier to keep an old session.
8. **Refresh recovery baseline:** before the calendar window, obtain the separately approved fresh
   backup/isolated recovery that includes the now-approved accounts, MFA secrets/settings and
   enforcement. Record/reconcile every change since the security baseline. A pre-MFA backup alone
   is not current security recovery evidence. Do not silently authorize another backup by this plan.
9. **I-Files:** separately approve exact20 scheduling files and activation. Reverify target and
   +45/replace0/delete0 proposal; if any destination has appeared, stop for a new comparison. Preserve
   core/emergency/theme/WordPress/uploads/public pages/menus/inquiry/global timezone. No new
   scheduling data/grants arise from plugin activation itself; native active_plugins/credential epoch
   may change, requiring fresh MFA. Do not flush shared OPcache or restart shared FPM/HTTP services.
10. **I-Schema:** using the privately verified native administrator, first capture the plugin's
    migration preview and compare **all15** actual-prefix CREATE statements, engines/indexes and
    source DDL hash. This future preview bootstraps WordPress and is **not** part of this read-only
    assessment. After separate apply authorization, allow `CSF_SCHEDULING_MIGRATION_ALLOWED=true`
    only for that reviewed window, explicitly apply with exact home/site URL guard, then remove/set
    false. No raw DDL or general deployment script. Inspect readiness, schema3, InnoDB/indexes,
    empty domain tables, unconfigured facility settings and no inferred master/staff. Expected native
    tables remain12; total becomes27. Physical DDL can survive failed transactions: stop on partial
    creation; never drop/reseed/stamp or retry blind. Migration does not configure fictional policy.
11. **O-Master:** after new independent challenges, privately approve distinct operator/owner IDs,
    source digest, target, facility/policy/registry/revision and verification record. Authorize a
    non-symlink private0600 invitation outside ABSPATH in a0700 directory owned/readable by sandbox
    PHP. Pool prefix/listen resolve to the sandbox socket; private-parent mode and declared
    open_basedir currently support this proposal, but verify actual FPM readability/nonpublic custody
    after approval. Do not use root-only ownership or broad group/world permissions. Invitation<=600s;
    approvals need MFA<=300s. Use native independent operator/owner forms, brief facility change freeze,
    atomic one-use consumption and original-command status for uncertain responses. No CLI master
    command or static bootstrap constant. Transfer later also needs outgoing-owner consent.
12. **G-person/group:** owner reviews effective privileges and saves only approved groups/overrides/
    scopes. Beth's Head Trainer plus resource/availability grants are a proposal, not implemented
    account settings. Verify last-master protection and independent owner/trainer denials/revocation.
13. **P / C-Policy / D-Fixture:** approve each fictional value/test week, then configuration and
    explicit fictional records separately. No real horses/clients, autopilot recurrence, payments,
    electronic signatures, public booking or emergency integration. WordPress global UTC stays intact.
14. **T-Pilot:** verify exact45 hashes/no extra provider files, schema, authentication/nonces/private
    cache/role boundaries, manual appointments/groups/rotations/client horses, conflicts, welfare,
    missing-clearance warnings, cancellations and audit at390/768/1280 plus actual phones. Use
    [the staff walkthrough](13-scheduling-staff-acceptance.md). Test failure injection/recovery races
    in isolation, not against live farm data. Record each failure. Beth and Kris perform and sign off
    their own tasks; no automated acceptance on their behalf. Real use remains a later explicit gate.

### Expected database/configuration footprint

No existing table is altered/dropped. Approved migration creates the15 listed InnoDB tables,
unconfigured singleton settings/facility UUID, authorization with no master/active staff and schema
audit. Default timezone/hours/welfare/services remain unknown and unbookable until C-Policy.
Activation updates native active_plugins. Provider enrollment/authentication uses native options/
user metadata for selected factors, secrets, hashed backup codes, challenge/attempt state, epochs
and session proofs. O-Master later updates authorization/history/settings/commands/audit plus
approved farm-capability mirrors and revocations. Account creation/role changes are their own A/G
operations. Exact keys and file hashes are in the [security release report](test-results/scheduling-security.md).
No old bootstrap constant is enabled. No live preview, flag, source or data change occurred here.

### Failure containment and separately approved recovery

- **Before activation:** stop on any target/hash/unexpected file/table/account difference. Removal
  of even unactivated additions/config flags is X-Cleanup, not automatic authority to delete.
- **After security activation:** remain fail closed; preserve BasicAuth and mandatory-MFA policy.
  Diagnose from the separately trusted operator surface. Do not deactivate/bypass MFA to regain
  privileged web access. Approved same-UID physical recovery re-verifies the person and resets
  factors through supported provider APIs; it never elects a replacement owner or infers identity.
- **After scheduling activation/migration:** X-Contain can authorize scheduling-only deactivation,
  retaining all data/audit/native mirrors for diagnosis. It grants no provider removal, schema drop,
  stale-source downgrade, role edit or whole-site restore. Last-master guards and protected data must
  be considered before any later account deletion/cleanup. Never run destructive drills on the sandbox.
- **X-Restore:** first preserve/reconcile all post-backup website, menu, photo, account, MFA, grant,
  policy, appointment and audit changes. Select the exact matched target/artifact/config pair and
  rehearse it privately. A prior config must not reopen password-only administration after enforcement
  was approved. Restored sessions, consumed backup codes or older authenticator seeds must not be
  revived: approved recovery revokes relevant sessions and rotates/re-enrolls stale factors/recovery
  codes before protected access reopens, maintaining current approved enforcement. Keep custody and
  recovery events private. An unknown command result is checked by original UUID, not overwritten
  with an older database to retry ownership. No silent merge, blind full-DB import or data erasure.
- **After recovery:** reverify exact source/target, physical schema/data/audit, current approved MFA,
  account boundaries, private HTTP behavior and public content/inquiry/emergency baselines. Owner
  approves resumption separately; synthetic pilot success never authorizes real records/production.

## Issue #14 release preparation — no execution approval

Immutable reviewed implementation source: **`2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`**. Scheduling tree
`2caf847c1453821fbe633657c250917198bc79ae`; adapter tree
`e2357a00c59c986daef5533183f963c12a8e9ac8`. Exact inventories/tests are linked below.

Current local scheduling0.4.0/csf-mfa0.2.0 candidate uses the actual verified official
Two-Factor0.17.0 ZIP, unchanged scheduling schema3/15tables, and an exact45-file combined
distribution (20 scheduling,4 adapter,21 vendor). Read the [security report and release
inventory](test-results/scheduling-security.md) and **current override** in the [single GateB
decision package](pilot/gate-b-owner-review.md). Historical source/installation proposals below
are not execution authority for this candidate. No general deploy script is changed or reused
to install these plugins without a separately approved exact comparison.

GateB is held: A-person/operator, M-choice/files/adapter/enrollment/enforcement/recovery/update,
I-files/schema, O-master, G-person, P-policy, C/D-fictional records and T-protected acceptance
remain independent. Mandatory admin MFA is a separate default-off setting, not activation's
side effect. The old CLI ownership bootstrap/transfer is retired; never set its flag as recovery.

Before an authorized change, inspect the exact sandbox URL/root/DB/prefix and active plugins,
PHP/SQL/WordPress/Kadence/proxy/caching stack. Compare all45 proposed file hashes against current
target without touching unrelated directories. Confirm fifteen exact owned schema3 tables and
native users/usermeta/options are InnoDB. Back up DB/wp-content and securely recoverable config,
record timestamps/target/checksums and perform the separately approved isolated functional
restore/login/page/integrity rehearsal. GateA evidence alone cannot establish fresh rollback.
No production or shared/unassigned DB enters the plan.

On later authorized execution, install only approved inventory; provider enrollment and
enforcement need separate identity/recovery verification and fresh real browser MFA. Preview
and explicitly apply the existing guarded schema3 migration if absent, with a temporary
authorized migration window closed afterward. No Issue14 physical migration is introduced.
The finite private ownership invitation is separately approved, exact release/target/policy/
revision/actor/action-bound, <=600seconds and outside webroot; each independent browser approval
needs MFA<=300seconds. No shell impersonation, root SQL owner assignment or reusable bypass.
Resolve uncertain command results by original UUID before repeating a ceremony.

Keep baseline/postchange hashes, schema/options/user-meta diffs, source and actor evidence
private where sensitive. Repeat mandatory-authentication, owner/employee boundaries and existing
synthetic calendar/mobile acceptance. No SQL fault injection into the farm DB; use the isolated
restore. If a check fails, stop further writes. Scheduling-only containment does not authorize
dropping data, restoring the whole database, modifying public content or disabling MFA. Same-UID
physical recovery and any full restore each need their own owner-approved incident plan,
intervening-write reconciliation and post-restore session revocation. No real operational
records until independent final authorization and Beth/Kris acceptance.

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
