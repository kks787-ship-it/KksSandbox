# Project decisions

Current execution state is in [the sandbox report](test-results/sandbox-preflight.md). The pre-installation decisions below retain historical context; the latest owner correction and local candidate decision supersede their pending-stage wording.

## Continue the existing implementation

Takeover baseline: c44240e9994c5c693360bef21d9aa2757de79c8e, independently matched to the
remote Claude branch. Continue on codex/takeover-audit. No rebuild: the plugin boundaries,
WordPress-native content, repeatable setup and small dependency footprint are useful.
Correct specific defects and add targeted tests. Evidence: [takeover audit](10-takeover-audit.md).

## Keep canonical documentation

Keep numbered architecture, roadmap, deployment and installation documents as canonical.
New business context, security guidance, decision log and root AGENTS.md link to those;
no parallel architecture/roadmap/deployment document set or redundant CLAUDE.md is needed.

## Fail closed on development target detection

Deployment and backup accept only successfully detected local, development or staging targets.
Unknown output, WP-CLI errors and production are rejected before rsync/export. This prevents
a broken WordPress bootstrap from silently removing the production guard. A verified target
is still not deployment approval.

## Report failed emergency persistence

If saving emergency state fails, return an error before logging success, firing extension
hooks or purging caches. This narrow correction does not provide a transaction or exactly-once
notification semantics. Atomic confirmation consumption and state/log/outbox consistency
remain release gates for operational emergency use.

## Use current evidence for readiness

Historical 472 browser checks remain a prior report, not current validation. Local PHP/SQLite
success does not prove MySQL/MariaDB, actual Kadence, Plesk, real mobile networks, MFA or physical
tag readiness. Record blocked and unrun checks explicitly.

## Proposed emergency concurrency fix — design before operational approval

Status: **proposal, not implemented or validated on MySQL/MariaDB**. This supersedes any
in-code implication that deleting a transient guarantees concurrent single use.

### Failure interleavings identified

Two requests can both read the same confirmation before either deletes it. Both can read the
same current state before either duplicate check/write completes, generating different UUIDs
for one reviewed action. Separately, two coordinators can each review revision R and later
publish conflicting changes. State can commit before the audit insert fails or the process
dies; a synchronous post-write hook can fail after the state already changed. A corrected
public_info/next_update with unchanged status/message is currently rejected for five minutes.
These follow from source ordering; an actual multi-worker MySQL race has not been run here.

### Recommended persistence model

Use plugin-owned **InnoDB** tables, verified on the actual Plesk MySQL/MariaDB versions:

- One farm-state row keyed by farm/site ID, with monotonic revision, event UUID, complete
  public payload, updater and UTC timestamp. This becomes the authoritative read source.
- Review/command rows with a UNIQUE hash of a cryptographically random confirmation token,
  bound to site/user, full normalized payload/hash, reviewed base revision and expiry; store
  the result event UUID/revision. Do not store raw token secrets in the log.
- Audit events with UNIQUE event_uuid and command ID. Keep the event and new state consistent.
- Durable outbox rows with UNIQUE(event_uuid, event_kind). Future delivery rows are uniquely
  keyed by event/channel/recipient; track attempt/lease, provider ID, next retry and outcome.

On confirm: verify capability, nonce, token/user binding, expiry and explicit confirmation.
Begin a transaction on the same database connection. Lock the singleton farm-state row
(`SELECT ... FOR UPDATE`); lock the command row in a consistent order. If the command already
succeeded, return its saved result without publishing again. Compare the saved base revision
against the current revision: reject stale reviews and require a fresh review (including
any changed recipients). Validate the full payload, including public_info/next_update.
Consume the command and write revision R+1, audit event and outbox entry **in one transaction**.
Check every query and affected-row count; rollback all on failure. Commit before acknowledging
success. Unknown commit outcome must be resolved by looking up the unique command result,
not by generating a fresh event on retry. Bound retries for deadlock/lock timeout use the same
command token. Expired/pending commands can be purged by a bounded maintenance job.

Do not wrap update_option() and cached transients in a transaction and assume consistency:
object caches are not transactional. Read authoritative state from the custom row, initially
without cross-request caching. If a legacy option mirror is retained for migration, it must
not be authoritative and must be repairable. After commit, schedule/poll the outbox and purge
caches; record/retry purge failures. A worker must recover if PHP dies before scheduling.
Provide a manual fallback and visible pending/failed delivery/purge status.

Delivery is **at least once with deduplication**, not blanket exactly-once. Use provider
idempotency keys when supported. If a process dies after sending but before recording receipt,
retry can duplicate delivery when the provider cannot deduplicate; document that limitation
and distinguish queued, accepted, delivered, failed and unknown. Never send email/SMS inside
the state transaction. Retain the public page and phone fallback if providers fail.

A simple transient/set_transient lock, non-atomic get/delete, process-local mutex or five-minute
message comparison is insufficient. A database advisory lock alone also does not make
state/audit/outbox writes atomic or reject stale reviews. On hosts without verified InnoDB
transaction support, block operational activation rather than silently falling back.

### Migration, acceptance and rollback

Perform migration on a backed-up development database first. Preserve the existing state,
audits, issued horse tokens and capability model. Create/verify tables before switching reads;
import the current option once with a defined initial revision. Do not assume dbDelta succeeded
because a schema version option was updated. Take a schema-aware backup before migration.
Reverting plugin files after new state writes would read a stale legacy option: rollback must
include an explicit data reconciliation/export plan or a coordinated database restore.

Acceptance tests on **multiple PHP workers and actual MySQL/MariaDB**, with persistent object
cache both on and off:

1. Many simultaneous submits of one token: one state revision/event/outbox row, same result
   returned to replays; wrong-user/nonce/expired token denied with no writes.
2. Two valid reviews of revision R: one wins, the other receives a stale-review response.
3. Information-only corrections apply; an identical complete payload is handled deliberately.
4. Inject failed command/state/audit/outbox writes, deadlocks and timeouts: no partial commit.
5. Kill before commit, after commit before response, before worker wakeup and after provider
   acceptance: deterministic retry/recovery with one command result and documented delivery semantics.
6. Restart workers; test lease expiry, retries, opt-out, provider errors and cache-purge failure.
7. Confirm the banner/log/public state agree after commit and on logged-out mobile networks;
   perform an owner-observed drill and verify manual fallback.

Implement and review this as the next emergency-hardening change. A development preview may
be installed with explicit approval while labeled non-operational; real-world emergency
approval must wait for these persistence tests, MFA, physical/mobile tests and owner acceptance.

## Reconcile completed Gateway hardening — October 31, 2026

The owner's correction supersedes earlier pending-hardening reports. Gateway preserved the
original config at `/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak`, then
applied and verified development environment, DISALLOW_FILE_EDIT=true and
WP_DEBUG_DISPLAY=false, syntax, ownership and live mode 0600. Record this as completed;
do not reapply constants or request approval for the same hardening again.

Preserve that artifact as configuration-only recovery evidence; it is not the full site backup.
The subsequent Gateway preparation completion below supersedes the earlier pending task list.

## Historical preparation and scoped installation plan — October 31, 2026

The owner's latest report confirms the full backup/checksum verification at
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/, isolated structural
restore (12 InnoDB tables/count samples, 4,336 regular files, zero mismatches), private source
pin d360dbf with plugin trees matched to 52401dc, and passed exact-target Plesk preflight/dry
run proposing only the two new plugin directories. Preserve those results; do not repeat
preparation, recreate scratch resources or move the source pin to a documentation-only tip.

Functional login/page rendering of the restoration is untested. Record the completed
structural/file checks without converting them to a full functional recovery claim. The
prepared evidence record and final gate procedure require actual artifact paths, capture
times/hashes and private operator review from existing Gateway logs; do not fabricate them.

The next approval is files-only installation with both plugins left inactive. Activation,
21-page setup/permalinks and actual Kadence testing are separate stages. The actual fresh
target-bound script gate is still to be checked against existing artifacts; conditionally
refresh a limited gate backup only for real staleness/incompatibility, not a blanket redo.
No plugin/permalink/production operation was authorized or executed by this documentation task.


## Historical backup capture and evidence identifiers — October 31, 2026

Gateway supplied DB export 10:08:58 AM PDT (17:08:58 UTC), file archive 10:09:42 AM PDT
(17:09:42 UTC), existing scratch DB csf_sandbox_restore_20261031 and evidence at
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt.
The complete files include hardened wp-config. Structural recovery passed; functional
login/rendering did not run. Record and reuse these facts instead of repeating work or
recreating evidence. The 24-hour DB threshold falls November 1 at 9:08:58 AM PST
(17:08:58 UTC), accounting for the DST transition.

A fresh target-bound bundle in the existing verifier's required format is the next milestone,
not a new full sandbox restoration. Format/age/integrity still require actual artifact checks.
Source remains pinned d360dbf; plugin installation/testing needs separate explicit approval.
No new remote operation is authorized by this documentation update.

## Finish the existing sandbox and keep inquiries disabled — latest October 31 correction

The owner confirms core 0.2.0 active, 21 published pages, assigned primary/mobile/footer menus,
static Home and /%postname%/ with 162 rules, standard .htaccess and passed Apache syntax.
Emergency stays inactive. Do not reinstall, regenerate pages or change permalinks. Footer
block 27 placement, theme configuration and authenticated real-Kadence acceptance are pending;
the later remote roadmap reports shared gutter Issue #1. Local substitute-theme checks are not theme acceptance.

Prepare core 0.3.0 for review without deployment. Fix rsync mode preservation with explicit
0755/0644 and ownership flags; allow --only-core to avoid touching emergency on a public-site
update. Preserve all existing backup/target/symlink guards. Failed WordPress writes must
produce CLI failure/admin error and no new completion stamp, not a false success. Setup can
partially save changes, so report that and avoid automatic force/rebuild retries.

Implement the optional inquiry in the existing core plugin. The owner confirms recipient
beth.siemon@cornerstonefarmomak.com and explicitly requests it stay off until delivery is
verified; the provider remains unknown. No recipient or enablement is written automatically.
Use fixed server-side mail routing, validated Reply-To, nonce/timing/honeypot checks,
database-backed rate and one-use claims, and no message storage/automatic replies. Test with
intercepted mail only. Actual MariaDB claim behavior, inbox delivery, privacy/caching settings
and enabled sandbox form behavior remain pre-enablement gates. Transport acceptance is not
delivered mail, and this form is not an emergency notification workflow.

A future code update requires a reviewed new SHA, current backup of the installed build,
core-only dry run and separate authorization. Old d360dbf dry-run evidence remains historical;
its older structural restore must not be relabeled as current or functional recovery.

### Preserve newer shared-branch findings

During push reconciliation, remote commits through 457fffb supplied the completed fresh
backup gate/job provenance and reported sitewide Kadence Issue #1 (intro text at viewport
edge, headings inset; observed on Meet Beth/Camps & Clinics). Preserve all those commits.
The defect report supersedes the earlier no-confirmed-defects clarification. No CSS fix or
independent authenticated reproduction is claimed here. Expand the prepared acceptance
runner to all 21 pages at 390/768/1280px and include gutters/other sections/Gutenberg in manual
review. Preserve full-bleed backgrounds and existing content; do not force a page rebuild.

## Shared gutter correction and safe Contact integration — local candidate only

Reproduce Issue #1 on a disposable installation using public Kadence 1.5.2, with the old
0778176 stylesheet as a browser baseline. Actual computed styles show two interactions:
Kadence's later .entry-content .alignfull resets section padding to zero; the plugin's forced
zero left margin then puts short leads at the viewport edge while 1160px headings center.
Use a more specific shared full-width section gutter rule and derive lead inline-start
margin from the same 1160px content width. Preserve full-bleed backgrounds, hero behavior,
existing HTML/copy and editor block serialization. No per-page patch or body margin.

A saved 0.2.0 Contact page requires content integration separately from copying plugin files.
Use the reviewed old template in an isolated fixture to prove the existing non-forced hash
logic updates only unedited content, adds one block idempotently and keeps other pages/menus.
Edited or untracked Contact content is never overwritten; warn for manual Gutenberg block
placement. Do not enable the form or send mail as part of upgrading content.

The user explicitly chose local fixes and sandbox review preparation. No verified sandbox
network access or private Basic Auth file is available; Gateway connectivity is separate.
Local downloaded-theme results cannot approve the actual site's theme settings, owner edits,
PHP/MariaDB or inbox delivery. Keep Issue #1 open and any deployment/content edit gated.
The local unconfigured Kadence tablet title fallback produced an unnamed brand link; test
native Logo & Title configuration on the fixture and flag the actual header for manual
review rather than adding theme workarounds to core or silently changing the sandbox.

## Correct public inquiry acknowledgments before enablement — Issue #2

Replace the untrusted submitted URL flag with a five-minute site-bound HMAC receipt minted
only after validated POST and transport acceptance. Separate the signing purpose from the
public form challenge. Claim each valid receipt once using a digest/expiry no-autoload option
with identical competing values; reuse bounded cleanup. Receipt responses are no-store/private
and no-referrer. Keep message/sender/recipient data out of URLs and markers. No delivery claim,
automatic retry or form enablement follows from acknowledgment. Regression coverage includes
forged GET, successful POST/303, replay, invalid receipts and transport failure. This is a
local code correction in the consolidated 0.3.0 candidate, not sandbox deployment approval.


## Owner scheduling-provider decision — October 31, 2026

**Supersedes all prior Acuity booking plans and instructions.** The owners confirmed Acuity Scheduling has been **canceled**. Do not reactivate, verify, integrate, import from, or link to Acuity. Any old references or options in source, active content, or historical plans are deprecated and need safe cleanup through [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3).

**Interim public scheduling workflow:** All lesson booking/request CTAs read **“Contact Beth to Schedule”** and link to the internal **/contact/** page. Present only verified, owner-approved call/message channels. Do not suggest the public can see live availability, book online, or receive an automatic confirmed appointment. The website inquiry form stays **disabled** until separately verified/approved, and is **not** the fallback channel.

**Future Digital Ranch calendar:** Use appointments explicitly confirmed by Beth or Kris as the source of truth. Staff enter/confirm appointments directly. No Acuity APIs, imports, synchronization, or assumed historic bookings. Requests, tentative holds, and confirmed appointments must have distinct states.

**Execution boundary:** This is a binding product/design decision and Codex implementation task, **not authorization to mutate the VPS**. As of this entry, the new Acuity-removal website changes are *not yet applied*. The previously approved csf-core 0.3.0 release is already installed in the protected sandbox; page content has not been regenerated. Review code, local tests, and a safe saved-page migration separately before any future sandbox update. Production and inactive emergency plugin remain untouched.


## Local scheduling removal and internal calendar design — October 31, 2026

Issue #3 source now fixes dynamic scheduling buttons to Contact Beth to Schedule → /contact/,
ignoring old provider options/labels and event registration URLs while preserving saved
block/shortcode interfaces. Generated copy uses direct Beth/Kris confirmation. Existing
classic booking navigation renders the Contact route without rewriting saved menu records.
Contact uses only public Farm Details settings; no inquiry recipient becomes a public email.
The inquiry form remains disabled and emergency inactive. Saved pages, custom header HTML,
synced footer and owner edits require the separate non-forced migration review in doc 07.

Issue #4's architecture is now in the [canonical architecture](01-architecture.md#digital-ranch-staff-scheduling--issue-4-architecture-for-review).
It proposes a separate staff-only scheduling plugin with explicit states, finite local-time
recurrence and transactional appointment/resource/audit writes. This is an owner-review
deliverable, not implemented scheduling code. See the [test report](test-results/scheduling-contact.md)
for source/guard passes and blocked PHP/WordPress/browser checks. No deployment, environment
republication, installation-script edit, live content operation or production change occurred.


## Scheduling architecture business-rule refinements — validation follow-up

The Issue #4 review is incorporated in doc 01 before calendar implementation: appointment-level
school-horse pools and sequential ride slots permit group/Academy rotations; service-specific
resources permit client-horse off-site/haul-in appointments without a school horse; hard welfare
blocks and configured workload/rest limits are independent of lesson demand; a confirmed slot
is distinct from staff-verified permission to ride; and cancellation notice/history does not
trigger unapproved fees, forfeits or makeup credits. Actual capacities, workloads, suitability,
travel buffers, Academy policy, clearance evidence and user grants still require owner review.
No scheduling schema, migration or appointments have been created.

The owner has now approved public scheduling call/email details in [the confirmation register](03-owner-confirmation-register.md#public-scheduling-contact-approval).
Those details are recorded for review, not yet written to the sandbox. No text-message,
24-hour emergency service or email-delivery claim follows. Keep inquiries disabled and emergency
inactive. The calibrated candidate preview proposes 21 page updates and no owner-edited overwrite;
plugin files, page/menu migration and settings publication still require scoped authorization.


## Execute separately approved scheduling sandbox release

The user explicitly approved release steps 2 and 3: exact core files at 6a46521 and the public
call/email plus 21 saved-page/applicable-menu changes. After fresh backup and reviewed core-only
comparison, both operations completed. Hash-guarded content migration preserved IDs and owner
provenance; repeat setup preview is all ok. Emergency remains inactive/unchanged, inquiries
disabled, production untouched. No calendar implementation is authorized by this release.
Protected Kadence browser acceptance remains pending the secure Basic Auth credential handoff;
HTTP 401 without it is expected. Record the release as deployed with acceptance outstanding,
not as fully verified on real devices. See docs/test-results/scheduling-contact.md.


## Protected scheduling review completed — November 1, 2026

Secure Gateway credentials enabled actual Kadence review. All 63 scheduling views passed;
42 mobile/desktop views passed overall while 21 tablet views share a serious unnamed home-link
accessibility issue. Existing demo title/default footer and absent placed CSF footer remain.
Do not bypass those findings or treat the private-fixture successes as full live acceptance.
The review was read-only; the temporary credential file was removed. No additional theme,
plugin/content deployment or calendar implementation was authorized or performed.

## Implement scheduling foundation locally — Issue #8

A separate `csf-scheduling` 0.1.0 candidate now implements explicit six-table InnoDB migrations,
owner-verified staff provisioning, configurable facility policies/resources, idempotent audited
foundation mutations and a private read-only diagnostic. Activation neither migrates nor grants
roles/capabilities. Appointment entry and confirmations remain disabled/unimplemented.

Portability is single-facility per WordPress prefix, not speculative multi-tenant SaaS. CSF
hours, staff identities, Academy and horse caps are reviewed pilot configuration, never universal
code defaults. No calendar scheduling rules are claimed enforced by the foundation alone.
Schema v2 and the transactionally safe staff calendar are the prepared next Issue #9 work.
No protected sandbox install/migration, farm data, Plesk, production, emergency activation or
inquiry enablement is part of this implementation. See canonical doc 01 and its test report.
