# Private dashboard and notification candidate — 0.4.13-dev

November 8, 2026. Local only; not published or deployed.

## Implemented

An authenticated THIS WEEK'S SCHEDULE summary appears near the top of the native
Digital Ranch calendar landing view and as a WordPress dashboard widget. Both use
`Calendar::view(monday, 'week')`, followed by independently scoped detail reads.
The facility timezone determines Mon–Sun and today. Empty days remain visible,
family children stay individually itemized, canceled/requested/expired states are
explicit, and actual saved family resource windows are shown. Authorized context
controls names; no frontend route, public tile, API or shared cache was added.
The grid has a narrow-screen single-column fallback. Browser acceptance is unrun.

Successful booking writes now create a durable `booking_notice` document inside
the same Store transaction before COMMIT. Preview paths skip notice creation;
failed transactions roll it back, and Store exact replay returns before the hook.
The command UUID is the event key. A family produces one consolidated event with
its actual changed children, not separate transport events per child. Unchanged
confirmed children do not create fake NEW alerts. Initial confirmation is NEW;
meaningful confirmed edits are RESCHEDULED; confirmed cancellation is CANCELED.
Changes involving an old or new start in the next 24 hours are marked urgent.

Recipient snapshots use effective application rights and assigned instructor
resource IDs. Management coordination rights receive the complete event; assigned
instructors receive only their entitled children. Read-only access or arena-only
assignment does not itself imply a notification recipient. Every later read also
rechecks the current session's MFA/access and appointment scope. No hard-coded
people, WordPress role checks or new grants were introduced.

Unread in-app notices have heading-scale text and accessible status semantics.
Acknowledgement is nonce protected, actor-scoped and processed through the same
locked/replay/audit service; it appends history without changing booking state.
Default queries exclude acknowledged notices, and bounded cursor navigation offers
unread and full history. Details use protected calendar links. Stored notice bodies
contain appointment/resource/user IDs and change classifications, not contact,
medical, guardian or agreement text.

Each event records `email_state=held_no_delivery_authorization` and
`push_state=not_configured`. **No wp_mail call, delivery worker, cron hook, network
sender, recipient email lookup or push integration exists.** These are durable held
transport envelopes, not a working email queue. Bounded claims/retries, provider
acceptance and uncertain-delivery reconciliation still need implementation and
separate delivery authorization before any real send. No delivery success is claimed.

The existing versioned details/history tables hold notices and acknowledgements;
there are still 15 scheduling tables. The complete undeployed schema-4 recovery
candidate must include these record types. Source-only rollback to an earlier
incompatible development commit is not a supported recovery plan.

## Validation actually run

- Nine weekly date/grouping checks pass on PHP 8.1.34 and 8.3.36, covering facility
  Monday, UTC/year/DST boundaries, empty days, itemized canceled family children,
  and use of the scoped reader. The reader/access are doubles in this test.
- Sixteen notification checks pass on both versions, using the real effective
  permission evaluator and classification/recipient logic. They cover no fake
  draft/hold/unchanged alerts, state transitions, buffer changes, urgent moves away,
  oversight, per-instructor subsets, inactive/unverified members and deny precedence.
- Forty-eight existing family command/parser checks still pass on both versions.
- All 144 plugin/test PHP files parse on both versions; diff checks pass.

## Authored but unrun / remaining validation

The disposable database suite now checks the consolidated held event, protected
read, per-actor acknowledgement, unchanged booking/allocations after acknowledgement,
exact acknowledgement replay and exclusion from unread results. Existing family
late-ledger rollback and exact booking replay cases also cover notice durability
and duplication. These require the unavailable WordPress/MySQL/MariaDB fixture;
no claim is made that they ran.

HTTP nonce/session, database fault/commit-unknown, real concurrent acknowledgement,
recipient revocation, large-history cursor and native dashboard/browser accessibility
checks remain required. No protected sandbox browser or owner acceptance was run.
No new plugin installation, email, staff provisioning, payment, public/emergency
booking, MFA weakening or production operation occurred.

## Independent review corrections — 0.4.18-dev

Recipients now include removed assigned instructors with a minimal old-time-only
departure notice. Original assignment provenance allows restricted historical
notices without current private detail access, subject to current MFA/effective
rights. New events freeze before/after times; later reschedules cannot rewrite
prior notice dates. Legacy missing dates are labeled unavailable, not reconstructed.
30 isolated notification checks and 32 sink checks pass on each PHP version.
Full HTTP/DB access and recipient regression still require the integration fixture.

## November 8 durable dispatch qualification increment

The explicitly loaded `Email_Dispatch` fixture worker persists stable event/recipient
jobs in existing schema-4 details rows through the facility lock, command ledger and
audit transaction. Queue, claim, pre-submit, completion, suppression and expiry are
versioned. The worker always generates a fresh pre-submit command: a replayed ledger
result is never send authority. Failed/unknown pre-submit commits do not submit;
failed completion leaves `submitting`, and expiry becomes terminal `uncertain`.
Only explicit synthetic transient rejection permits bounded backoff; uncertain
results never automatically retry. This is not an exactly-once delivery claim.

Current verified active membership, effective rights, native capability mirror,
MFA enrollment, original audience and current assignment are rechecked under the
lock immediately before pre-submit. Removed-instructor departure envelopes retain
only the original minimal departure, while revoked rights/resource bindings suppress
the job. A changed sibling scope suppresses the whole original job rather than
silently changing an idempotent payload. A permission change after the committed
pre-submit boundary is not retroactive; real transport requires a separately
reviewed dispatch authorization/cutoff policy before activation.

The class is not bootstrapped, has no cron/HTTP hooks, and accepts only the final
in-memory sink. Its Store command fails closed unless explicitly loaded on
`/test/site/`, database `csf_fixture`, environment `local`, with authenticated
team-assignment/calendar rights. Source events remain `held_no_delivery_authorization`.
No real email, address lookup, delivery switch, staff grants or schema change.

Local recipient regression: 15 checks passed under PHP 8.3 WASM. New real-DB
fixtures cover durable transitions, exact replay, ledger rollback before submission,
completion failure, uncertain/expired claims, backoff, enrollment revocation and two
workers attempting the same claimed job. CI execution results will be recorded
separately; fixture source is not evidence of a passed database test.
