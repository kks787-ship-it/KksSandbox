# Project decisions

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

## Record completed Gateway preparation and scoped installation plan — October 31, 2026

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


## Retain exact Gateway capture and evidence identifiers — October 31, 2026

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
