# Master specification audit — 0.4.18-dev

November 8, 2026. Compared the owner-approved calendar master specification with
candidate code through this audit increment. All implementation statuses below
refer to source, not protected acceptance or deployment. Baseline recurring hours,
closures, catalog and visual planner were already delivered in 0.4.9; they were not
rebuilt or represented as new work. No farm changes occurred in this audit.

| Master requirement | Current candidate / evidence | Remaining acceptance or implementation |
| --- | --- | --- |
| §1 routine grid, derived ends, DST, explicit state | Existing Booking, Time, staff.js and native planner; isolated regression fixtures retained | Actual WordPress/DB/browser/MFA acceptance; count clicks and scrolls |
| §1A catalog/stable IDs/history | Existing Catalog/Settings and dedicated Types screen; owner labels are proposals | No price/entitlement/payment assumptions; validate rename/disable/future-booking impact |
| §1B actual enabled Student 30/60 | Existing reviewed setup action from 0.4.9; fresh source read identified 0.4.9 earlier | Actual current farm policy and owner-approved settings/application remain unverified; synthetic choices are not acceptance |
| §2 family itemization/next action | Atomic full-group writer, common-state holds/expiry, multiple riders/horse turns, cancellation, attachment, canceled-visit reuse | Full SQL/races/HTTP; no production scheduling claim |
| §2 routine-to-family handoff/span | Added direct event-detail action loading original lesson and next proposed block, with scope checks and no writes; total teaching minutes and overall span in combined review | Drawer integration and device usability; span explicitly includes any gaps and is not a claim of continuous occupancy |
| §3 15+15/shared buffers/overrides | Saved resource allocations, compatible family sharing, unwaivable constraints; separate default-off override right and red text/icon markers | Real races, policy boundary/DST fixtures and scoped browser checks; configurable policy UI is expressly later scope |
| §4 classification/resource correction | Unused horse mapping can be corrected under dependency guard; used horse history preserved | Used-horse remapping and safe affected-booking reconciliation still unimplemented, not considered complete |
| §4 unused deletion | Added horse_deleted command, named irreversible confirmation, locked dependency checks, retained full setup/history and UUID tombstone; deleted UUID cannot be recreated | Actual DB rollback/replay/deletion-versus-booking race and native confirmation UI remain unrun; no real horse deleted |
| §4 archive/restore/classification labels | Guarded archive/restore retained, future bookings block archival, active counts exclude archives; labels distinguish booking source from legal owner | Actual workflow acceptance; no owner relationship system implied |
| §5 durable in-app events/ack/history | Transactional command-keyed notices with per-actor acknowledgement; failed previews/replays do not create events | Real SQL/access/MFA cases remain required |
| §5 reassignment/history correctness | Independent review fixes: former assigned instructor gets restricted departure using old time only; immutable before/after event-time snapshots prevent historical date rewriting | Legacy events without snapshots say date unavailable; no unsafe historical backfill; full HTTP access regression pending |
| §5 email/outbox/push | Durable events remain transport-held; fixture-only sink tests cover recipient subsets, keys, bounded retry and uncertainty | Spec's durable claim repository/worker and wp_mail adapter are NOT implemented. Sink module is unloaded, no real sender/queue/cron; outbound consent and provider qualification remain separate |
| §6 dashboard/override prominence | Scoped weekly/today summaries, family grouping, resource windows, notices and real override text/icon | 390/768/1280px, keyboard/screen-reader and cache/access acceptance |
| §6A visual day/week | Existing visual grid, cell handoff, expansion and filters preserved; actual saved buffers/visit links integrated | Usability and concurrent lanes require browser acceptance; month and drag-resize optional later |
| §6B resource weekly hours/closures | Existing weekly/split hours, dated/weekly closures/reopen, impact review; continuation corrected impact to allocations and added future-only Edit | Prospective splitting/editing of a started series remains unimplemented; do not rewrite past occurrences |
| §6D simplified everyday UI | Existing compact booking drawer and separate Types/Hours/Farm screens retained; family editing still a separate native screen | Same-drawer family experience and owner action/scroll-count quality gate remain incomplete; no claim of Acuity-equivalent usability |
| §6C/§7/§8 recovery and rollout | Schema-4 recovery source/public payloads prepared; matching source/data and all gates retained | Functional restore, fresh <24h backup, exact reviewed payload, transparent preflight, independent readback and owner acceptance all required before deployment |

## Audit increment and actual verification

- Conditional deletion removes only the proven-unused horse roster row. Associated
  resources, suitability references in other records, original setup history and
  audit are not deleted. Any booking/detail, external history, slot/allocation,
  protective-block or non-setup lifecycle history rejects deletion. Database errors
  fail closed; command uses existing Store lock/revision/ledger/rollback.
- Ordinary lesson handoff retains the original revision and proposes a distinct
  unsaved child at its finish. No attestation is auto-approved. Grouped lessons open
  their existing visit. Unauthorized/canceled sources are rejected.
- Reassignment notices record recipient assignment provenance. A former instructor
  retains only their event-time date/classification, with no current detail link,
  new resource identity, rider details or new schedule date. Current MFA/effective
  calendar rights and original recipient membership still apply; denied/revoked
  access does not become a new entitlement. Prior notices use frozen dates, not the
  mutable appointment row. Legacy missing snapshots are not invented.
- **393 isolated checks per PHP 8.1.34 and 8.3.36 passed**: 103 planner, 89 family,
  26 horse correction, 33 lifecycle, 34 deletion, 17 recurring hours, 20 closure edit,
  9 dashboard, 30 notification and 32 sink checks. These use boundary doubles.
- **165 plugin/test PHP files** parse on both targets. JS syntax and diff checks pass.
  Real database deletion rollback/replay and stored notice snapshot cases were added
  but not run. Existing 283 recovery/22 deployment/6 mocked preflight results are
  from the immediately preceding preparation, not live restore qualification.

Publication-only approval was received for the continuation branch and a draft PR.
No merging, deployment, real mail/push, staff grants, payments, public/emergency
booking or MFA changes are authorized by that approval. The branch will remain
stable after publication so independent findings can be reconciled against one SHA.
See [the exact integration plan](scheduling-integration-validation-plan.md).

## Continuation after CI review

The latest local increment supersedes three implementation gaps above: historical
horse remapping is allowed only after ongoing/future bookings, remaining reservation
windows and unreleased protective blocks are reconciled; started closures split
prospectively at a future local midnight into retained and replacement documents;
family forms/review/save now use the calendar task drawer with focus return.
All use existing transaction, authorization and native signed forms. New DB/browser
coverage is authored; CI acceptance for this increment is not yet established.

The existing native wp_mail runtime harness is now a separate disposable CI suite
with an in-memory PHPMailer override and synthetic invalid-domain recipients.
This qualifies existing transport checks only; durable scheduling outbox claim/worker
integration and scoped real-delivery authorization remain outstanding.

## November 8 reconciliation after draft run 18

The earlier unimplemented/unrun cells above are historical. Draft `4c29c66`, run
42468961364, supersedes them: all four PHP8.1/8.3 × MariaDB10.6/MySQL8.0 jobs
passed, including family/horse/closure transactions and races, logical restore,
33 durable sink-dispatch assertions, 16 current-recipient checks and one-submission
worker races. Scheduling PHPCS passed without waivers. Browser results: booking64,
permissions39, calendar71, hours32/domain60, family23, MFA50, security79, native-mail9
and booking-mail bridge12. These are synthetic acceptance, not owner/farm acceptance.

Implemented since the original table: safe used-horse corrections after dependency
reconciliation; unused deletion/tombstones; atomic prospective started-closure
splits; calendar family drawer; durable versioned sink claims/retries/uncertainty and
current recipient checks. Catalog 30/60 setup remains review-only until the owner
applies actual farm policy. Recurring hours and closures were existing baseline work.

Current follow-up adds guarded native wp_mail integration to the durable worker
(with persisted transport binding), dedicated real-MFA dashboard/notices browser
coverage, exact 30+30 and 120+120+60 native family cases and recorded interaction
metrics. Family no-horse services hide irrelevant horse input without changing
server validation. These new runtime cases are authored; record their CI outcome
separately before claiming they passed. The former absence of family grouping in
0.4.7 prevents an honest like-for-like legacy family benchmark; current action and
page/panel-scroll measurements are reported without inventing a baseline.

Remaining: protected owner configuration/usability acceptance, real delivery
activation/recipient consent, and recovery/deployment qualification. Configurable
buffers, month/drag-resize, household agreements/payments and broader role portals
are later scope. No existing grants or deployment gates change.
