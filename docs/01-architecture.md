# 01 — Platform Architecture

## Principles

1. **Kadence + Gutenberg for presentation; plugins for behaviour.** Business logic never lives
   in the theme. Switching or breaking the theme must not break bookings, emergency pages,
   or data.
2. **Separate plugins by risk and audience.** Public marketing, emergency operations, and
   private client data have different security, uptime, and privacy needs.
3. **Public, unlisted, and private are different things.**
   - *Public*: marketing pages, articles — indexed.
   - *Unlisted*: emergency horse pages — reachable by anyone with the URL, not indexed, not
     listed. **Never used for confidential data.**
   - *Private*: client, horse-owner, and staff records — authenticated *and* authorized per
     record.
4. **WordPress-native first.** Custom post types, capabilities, the Settings API, and core
   sitemaps before third-party frameworks. No page builders.
5. **Explicit capabilities.** Every sensitive feature has its own capability, granted
   deliberately — never implied by “Editor” or similar.

## Components

```
┌─────────────────────────────────────────────────────────────────────┐
│ Kadence theme (+ child theme only if needed)  — presentation only   │
├─────────────────────────────────────────────────────────────────────┤
│ csf-core         Knowledge Library, booking buttons, shared helpers │
│ csf-emergency    Horse IDs, farm status, banner, /emergency, log    │
│ (future)                                                            │
│ csf-notify       Alert subscriptions & delivery (email/push/SMS)    │
│ csf-assist       Evacuation help requests, responders, dispatch     │
│ csf-onboarding   Households, riders, agreements, e-signatures       │
│ csf-portal       Client / horse-owner / staff portals               │
└─────────────────────────────────────────────────────────────────────┘
```

Each future plugin depends only on documented hooks from the plugins beneath it:

| Hook | Provided by | Purpose |
|---|---|---|
| `csf_emergency_status_changed( $uuid, $state, $previous )` | csf-emergency | Fires once per confirmed farm status change. Notification modules use `$uuid` as the idempotency key. |
| `csf_emergency_notification_summary` (filter) | csf-emergency | Lets a notification module describe recipients on the review screen before confirmation. |
| `csf_emergency_purge_caches` | csf-emergency | Lets hosting-specific cache purges run after a status change. |

## Emergency plugin design decisions

| Decision | Reason |
|---|---|
| Standalone templates for `/h/…` and `/emergency` (no `wp_head()`) | They keep working if the theme, a builder, or an optimisation plugin breaks, and they load in one request on a weak rural mobile connection. |
| 8-character tokens from a 31-character alphabet without 0/O/1/l/i | Cryptographically random, non-sequential, ~8.5 × 10¹¹ combinations, and readable aloud from a worn tag. |
| Tokens are permanent and never regenerated | Printed tags must keep working for the horse’s lifetime. |
| Unknown, unapproved, and retired tags return the same page (with farm phone) | Does not reveal which codes exist, but a finder still reaches the farm. |
| Profiles are hidden until “Approved for public emergency display” is ticked | Information is published deliberately, never by saving a draft. |
| Microchip number masked by default | Shows that a chip exists without publishing the number. |
| `/emergency` shows aggregate horse counts by default | The spec asks for horse status summaries *and* no public directory. A per-horse list is an opt-in setting, shown only during an active emergency. |
| Report Found Horse uses `sms:` with a pre-filled message | Works on any phone with no server round-trip, which matters when the site is under load or coverage is poor. |
| Three-step status change with a single-use confirmation key | Prevents double clicks, browser resubmits, and back-button replays from applying (and later notifying) twice. Identical status + message within 5 minutes is also rejected. |
| Dedicated audit table rather than post meta | An append-only log that survives option changes and can be reviewed after an incident. |
| Data is kept on deactivate/uninstall | Emergency records must not vanish because a plugin was toggled. |

## Data separation for future portals

- Horse **emergency** profiles (public-approved fields only) are a different post type from
  future horse **records** (training journals, veterinary, billing). The portal will link a
  horse record to its emergency profile by ID; it will never expose portal fields through
  `/h/…`.
- Household, rider, agreement, and signature data will live in dedicated tables owned by
  `csf-onboarding`, not in user meta or post meta, with per-record authorization checks
  (“can this user view *this* rider?”), not just role checks.
- Signed agreements: stored outside the web root or in a non-public uploads directory,
  served through an authorization-checked endpoint, with the agreement version and a hash
  of the signed content recorded.

## Planned roles (to be confirmed)

| Role | Purpose |
|---|---|
| Administrator | Site owner (Kris). |
| Emergency Coordinator (`csf_emergency_coordinator`, implemented) | Change farm status, maintain horse IDs, view log. |
| Trainer (future) | Training notes, lesson rosters, horse journals. |
| Staff / Working Student (future) | Assigned chores and schedules only. |
| Client (future) | Own household, riders, agreements, lesson notes. |
| Horse Owner (future) | Own horses’ records only. |

## Security baseline

- MFA for every account with emergency or administrative access. The dashboard warns when the
  WordPress.org **Two Factor** plugin is missing or not enabled for the current user.
- `DISALLOW_FILE_EDIT`, least-privilege roles, automatic minor core updates.
- All input sanitized, all output escaped, nonces and capability checks on every write
  (enforced via WordPress Coding Standards in `phpcs.xml.dist`).
- No credentials in the repository. API keys for future notification providers go in
  `wp-config.php` constants or environment variables on the server.

## Takeover review

The design is retained. See [the takeover audit](10-takeover-audit.md) and
[security release gates](SECURITY.md). Confirmation transients and status comparisons are
not atomic: the existing “once per event” hook is not an exactly-once guarantee under
concurrency or persistence failure. Notification consumers need durable idempotency and
recoverable event processing before operational use.

## Digital Ranch staff scheduling — Issue #4 architecture for review

Status: **design only**, following the October 31, 2026 scheduling decision and
[Issue #4](https://github.com/kks787-ship-it/KksSandbox/issues/4). Implementation and
deployment are separate increments. Public contact never creates an appointment.
Beth or Kris enters and explicitly confirms dates agreed directly with the household.
No provider integration, external availability, imported history, public self-booking,
payments, messaging, signatures, subscriptions or client portal enters this MVP.

### Boundary and runtime

Add a separate `csf-scheduling` operations plugin. It owns its tables, staff wp-admin
calendar and authenticated REST namespace `csf-scheduling/v1`; it does not rebuild pages,
write `csf-core` settings, activate emergency, or depend on emergency horse profiles.
Public horse listings are not authoritative private horse records. Optional future
onboarding references use a documented adapter and stable IDs, never copied medical,
agreement or billing data. The public website continues linking only to Contact.

Use WordPress authentication/capabilities and prepared SQL on the same `$wpdb` connection.
Require verified InnoDB transactions on the target MariaDB/MySQL version; SQLite tests
alone cannot approve concurrency. Fail closed for unsupported storage, missing schema,
unknown farm timezone or an incomplete migration. Authoritative calendar reads bypass
cross-request object caches initially. No email or other network calls inside transactions.

### Tables, keys and invariants

All names below carry the installation's `$wpdb->prefix` plus `csf_sched_`. IDs are
unsigned BIGINT primary keys, timestamps are UTC DATETIME(6), and UUIDs use a unique
ASCII binary-collated CHAR(36). Index every reference. Use explicit application reference
validation and restrict hard deletion; migration verifies engine, keys and indexes rather
than assuming `dbDelta()` succeeded. No cascading deletion of appointments or audit history.

| Table | Fields and keys |
|---|---|
| `settings` | Singleton `id=1`, schema/policy revision, farm IANA timezone, configured Academy interval, protected-time and resource policies. Lock this row first for every calendar mutation. |
| `resources` | `id`, unique UUID, kind (`instructor`, `horse`, `arena`, `location`), private display name, active flag, optional WordPress user ID, school/client-owned classification, suitability policy reference, revision. Instructors and horses are exclusive; arenas initially exclusive per appointment. Shared group riding is one appointment with a roster. |
| `households` | `id`, UUID, minimal staff label, optional unique `(source_namespace, external_id)` onboarding reference; no public user account required. |
| `participants` | `id`, UUID, household ID, minimal staff label, optional onboarding reference. No birth dates, medical or guardian documents here; a staff eligibility/suitability decision is referenced separately. |
| `appointments` | `id`, UUID, title, type (`group`, `private`, `haul_in`, `off_site`, `academy`, `training`, `break`), state, nullable hold expiry UTC, UTC start/end, original local start/end, IANA zone and resolved UTC offsets, instructor ID, location ID, route-group ID, capacity, staff-only notes/visibility, resource-review-required flag, series ID/occurrence key nullable, created/updated/confirmed/canceled actor IDs and UTC times, cancellation reason/initiator/notice-received UTC/effective UTC, revision. CHECK-equivalent validation: end > start, capacity > 0; UNIQUE `(series_id, occurrence_key)`, INDEX `(state,start_utc,end_utc)`. |
| `roster` | Composite PK `(appointment_id,participant_id)`, household ID, participation-clearance status/reference, suitability decision/reference and reviewer/time. Capacity counts distinct participants. Horse assignment is not one horse per rider for the entire appointment; use appointment allocations and ride slots below. |
| `horse_references` | `id`, UUID, stable private horse identity, ownership (`school`, `client`), optional unique managed horse resource ID. A client horse need not belong to farm inventory. Use its stable reference for collision checks across visits; never create a fictitious school-horse allocation. |
| `appointment_horses` | Composite PK `(appointment_id,horse_reference_id)`, use (`school_pool`, `client`), declared activity/workload category. Names the reserved pool or client horses even without detailed ride slots; absent slot detail uses the full appointment interval conservatively for overlap/workload checks. |
| `ride_slots` | `id`, appointment ID, participant ID, horse reference ID, occupied start/end and explicit recovery buffers, UNIQUE `(appointment_id,participant_id,start_utc)`, indexes `(horse_reference_id,start_utc,end_utc)` and `(participant_id,start_utc,end_utc)`. No overlapping rider or horse use within a class; sequential shared-horse rotations allowed. Slot must fit the appointment and its declared horse pool; managed school horses must also have allocations. |
| `resource_blocks` | `id`, resource/horse reference ID, unavailable/rest interval, generic staff-visible reason, restricted note reference, creator/update/release actors/times and revision. Welfare blocks are hard exclusions, not warning overrides; retain history. |
| `participation_clearances` | `id`, participant ID, relevant service/appointment scope, release/guardian/emergency-contact verification statuses, reviewer/time, expiry/recheck date and external evidence reference. Record manual verification until onboarding exists; no signatures, health documents or fabricated approvals here. |
| `allocations` | PK `id`, appointment ID, resource ID, occupied start/end including explicit travel/preparation buffers, UNIQUE `(appointment_id,resource_id)`, INDEX `(resource_id,start_utc,end_utc)`. Only confirmed appointments and explicit holds have allocations. Group school-horse pools are reserved here at appointment level. Participant and client-horse overlap checks also use roster/ride slots under the same mutation lock. |
| `series` | `id`, UUID, owner user ID, farm-local weekday/start/duration, interval weeks, bounded count/until, zone, template, generation horizon/cursor, revision. MVP supports weekly recurrence, not arbitrary unbounded RRULE evaluation. |
| `exceptions` | PK `(series_id,occurrence_key)`, action (`skip`, `cancel`, `replace`), replacement appointment ID nullable, actor/reason/time. The key is the original local date/time, retained even when rescheduled. |
| `route_groups` | `id`, date, staff geographic label, ordered stops and explicitly entered travel buffers; no geocoding or automatic distance claims. |
| `training_enrollments` | `id`, horse ID, full-training start/end dates, status and audit actor; distinct from riding-session allocations. Maximum five concurrent active full-training horses, checked over enrollment intervals under the settings lock. |
| `commands` | UUID command ID UNIQUE, actor ID, normalized payload digest, expected revision, result appointment/revision, status/time. Reuse a command after uncertain commit; reject same key with a different actor or payload. No raw credentials or nonce storage. |
| `audit` | Append-only `id`, command UUID, entity/type/ID, action, old/new revision, actor, time, reason and bounded changed-field summary. UNIQUE `(command_id,entity_type,entity_id,new_revision)`. Minimize notes/participant details in audit; never log REST bodies. |

Notes are staff-only in MVP; no public visibility value is accepted. Referential checks,
state validation, clearance, welfare and audit writes apply to REST, admin, CLI and future adapters through
one mutation service. No alternate direct `update_post_meta` scheduling write path.

### States and permissions

| State | Meaning | Occupies resources |
|---|---|---|
| `requested` | Staff-recorded inquiry; neither a reservation nor accepted appointment | No |
| `tentative_hold` | Beth/Kris explicitly chooses resources and a bounded hold expiry | Yes, until expiry |
| `confirmed` | Beth/Kris explicitly confirms a directly agreed date and complete roster | Yes |
| `canceled` | Retained history with cancellation actor, time and reason | No |

Allowed transitions: requested → hold/confirmed/canceled; hold → requested/confirmed/canceled;
confirmed → canceled or an explicitly reviewed confirmed reschedule. Canceled records remain
terminal; create a linked replacement instead of silently restoring one. Expired holds are
excluded from overlap reads by expiry time even if cleanup is late; cleanup records the
hold → requested transition with a system actor. Reconfirmation always revalidates conflicts. Calendar confirmation is not permission to ride;
participation clearance is an independent, visibly reported gate (see below).
No separate completed state in MVP: elapsed confirmed appointments remain auditable history.

Create `csf_schedule_manager` with view/edit/hold/confirm/cancel/audit capabilities; assign
only to the owner-verified Beth and Kris user IDs through an explicit provisioning step.
Do not infer identity from display name, email or Administrator role. Confirmation requires
both the confirm capability and membership in that two-user allowlist. No automatic grants
on activation. A future read-only staff role is deferred. Anonymous users, clients, editors,
emergency coordinators and other administrators without these grants receive no access.
Require authenticated session, REST nonce and capability/record checks on every route;
verify MFA through the site's established policy before operational acceptance. Escape
calendar titles/notes, validate scalar shapes and bounded lengths, and use no-store/private
headers. Calendar payloads never enter public REST schemas, search, sitemaps or feeds.
Audit access requires its own capability; show minimal participant labels on weekly/monthly
views and load household detail only in an authorized appointment drawer. Retention and
minor-data handling policy require owner review before real participant entry.

### Conflict control and atomic save

Use one singleton scheduling write lock initially: a small two-person calendar favors a
simple demonstrable correctness boundary over high write throughput. Every writer, including
recurrence generation, hold expiry, welfare blocks, clearance changes, enrollment and
resource/policy edits, follows this protocol:

1. Authenticate and authorize; validate request shapes and normalize local times. Start an
   InnoDB transaction; `SELECT ... FOR UPDATE` the existing settings singleton. Never use a
   transient, object cache or browser conflict preview as a reservation lock.
2. Look up/lock command UUID. Return an existing committed result for the same actor/digest;
   otherwise insert it and check the appointment/series expected revision. Stale writes are
   409 responses requiring a fresh review, including cancellation and roster changes.
3. Read authoritative current allocations, declared appointment horse identities, welfare blocks
   and nonexpired holds. For half-open intervals
   `[start,end)`, conflict exists when `existing.start < proposed.end AND existing.end > proposed.start`.
   Exclude the appointment being edited; validate its whole replacement roster/resources.
   Adjacent appointments are allowed only after their configured buffers. Distinct instructors,
   horses and arenas can run simultaneously; sharing any exclusive resource across distinct
   appointments is rejected. Within one group appointment, validate sequential ride slots
   separately so riders can rotate through the same reserved school-horse pool.
4. Check participant overlaps, capacity, active resources, horse/rider suitability, Sunday
   closure, protected/welfare blocks, workload/break limits, Academy policy and full-training
   enrollment cap. Apply service-specific requirements; off-site/haul-in client horses do not
   require a school horse. Incomplete required scheduling data permits only requested status.
   Independently compute ride clearance; pending paperwork does not turn a confirmed slot
   into permission to participate. Return
   conflicts with staff-safe labels and no sensitive notes. Explicitly acknowledged advisory
   warnings are bound to the current normalized payload and policy revision.
5. Persist appointment/series/roster/declared horses/ride slots and clearance references, replace allocations,
   increment revision and append audit
   plus command result in this same transaction. Check every query and affected-row count.
   Roll back on any error; no success, partial roster or audit-only outcome. Commit before
   returning success. A lost response is resolved by command UUID, not a fresh operation.

All mutations serialize on the same pre-existing row, so even a previously empty calendar
is protected against concurrent phantom inserts. UNIQUE occurrence and command keys provide
additional deduplication. Bound deadlock/timeout retries with the same command UUID; otherwise
return retryable 503 without claiming a reservation. A failed COMMIT has an unknown outcome
until a fresh connection resolves the command. Audit and appointment are committed together.
Restrict direct DB access to operators; the protocol cannot protect writes that bypass it.

### Group rotations, service resources, welfare and participation

Reserve a group or Academy's school-horse pool at appointment level so no other appointment
can use those horses simultaneously. Multiple students may ride the same horse sequentially;
never enforce one horse per student for the entire multi-hour class. Where riders rotate,
enter discrete concurrent ride slots and their recovery buffers. Reject simultaneous use of
one horse by two riders, overlapping rider slots, slots outside the class and horses outside
the reserved pool. Class capacity is a staff-configured participant limit, separate from the
number of horses. Without a detailed slot plan, calculate workload conservatively over each
horse's full reserved interval; do not assume unrecorded rest. Rotations/suitability and the
minimum slot detail needed for a service require Beth's review before operations.

| Service | Required scheduling resources; owner configures exact values |
|---|---|
| Group / Horse Academy | Instructor, facility/arena/location, participant capacity, explicitly reserved school-horse pool or declared client horses, and a rotation plan where used. No automatic one-to-one rider/horse rule. |
| Private | Instructor and location; select school-horse allocation or declared client horse. Arena required when using the farm arena. |
| Haul-in | Instructor, farm location/arena and client's stable horse reference; no farm-owned horse required. |
| Off-site | Instructor, client location, route group and travel buffers, client's stable horse reference; no farm arena or school horse required unless explicitly used. Non-Wednesday is advisory. |
| Internal training/break | Explicitly affected instructor/horse/facility resources; no client roster required. |

A client horse can be “not a farm resource” while still having a private stable horse
reference. Check that identity through `appointment_horses` across appointments/ride slots under the same lock, along
with instructors, participants and actual managed resources. Do not deduce that two unnamed
horses differ or claim collision protection for an unidentified horse; resolve identity
before riding/confirmation where the service requires it. Ownership never implies suitability.

Horse welfare is a hard scheduling constraint. Staff can enter unavailable/rest blocks for
comfort, veterinary/farrier care, injury or recovery; the calendar shows only “unavailable,”
not private health notes. Configurable working-time limits per farm-local day, consecutive
work limits, minimum rest and workload categories must be approved per school horse/service;
no invented numeric defaults. Count ride-slot work where a complete plan exists, otherwise
reserved time conservatively, including adjacent days/series occurrences as required. Unknown
required limits prevent operational confirmation. A manager cannot acknowledge away a welfare
block to fill a lesson. Record who creates, edits or releases a block and the reason.

Permit a protective block even when existing appointments conflict: persist the block and
flag every affected appointment as requiring staff resource review in the same transaction.
Do not silently cancel appointments or leave them looking ride-ready. New confirmations,
reschedules and ride clearance must respect the block; resolving affected entries requires
an audited reassignment/reschedule/cancellation or a separately reviewed block release.
No automated diagnosis or weather/fire/smoke threshold is implemented.

Calendar state and rider clearance are independent. A confirmed date may remain **not cleared
to ride** while paperwork is pending. Before the first ride (and after expiry or changed
requirements), staff verifies the required release, guardian authorization for minors and
emergency-contact completion, recording reviewer/time and an evidence reference. Unknown,
missing, expired or revoked clearance prohibits participation and shows a clear roster warning;
never infer signatures from a confirmed reservation. The MVP staff workflow has an explicit
pre-ride checklist/check-in gate; recurring bookings do not bypass it. The scheduling plugin
is not an e-signature system and cannot physically prevent riding, so staff must follow that
manual gate until onboarding supplies authoritative eligibility checks. Clearance and horse
suitability are separate; neither grants the other. Keep details in authorized views only.

Cancellations retain initiator (household/staff), notice-received time, effective cancellation
time, actor, reason, previous schedule and series/exception scope in audit. Correcting a notice
adds an audit event rather than erasing history. Display notice duration if useful, without
assuming a financial consequence. A discussed 24-hour practice is not an approved billing rule:
no automatic fees, forfeits, refunds or makeup credits. Those policies and any future overrides
require explicit owner review; payments and subscription billing remain deferred.

### Farm policy and recurrence

Farm zone proposal: `America/Los_Angeles` for Omak, requiring owner confirmation at setup;
never use the executor timezone or an unverified WordPress UTC setting. Store local intent,
zone and resolved UTC instants. Reject nonexistent spring-forward times; require an explicit
offset choice for ambiguous fall-back times. End must follow start in UTC and local intent
must be shown before confirmation. A later farm-zone change does not reinterpret old records.

- Sundays are closed to client appointments, including intervals crossing into Sunday in
  farm time. Internal training/break blocks may exist then. MVP has no silent override.
- Wednesdays are the preferred off-site day. Warn for other days and geographically mixed
  route groups; Beth/Kris can acknowledge those warnings, with a reason, before save. Never
  impose an unsupported absolute Wednesday-only restriction. Enter travel buffers explicitly.
- Monday Horse Academy occupies 14:30–17:00 farm-local time as a staff-created academy group
  appointment with its actual instructor/horse/arena allocations. Configure its season and
  resource roster before confirming conflicting Monday client work; an absent policy/season
  decision blocks that approval rather than inventing attendees. Cancel an occurrence through
  an explicit exception. Subscription billing is deferred.
- Beth's personal-horse training and breaks are explicit confirmed internal blocked-time
  entries with allocated resources and no client roster. They cannot be ignored when saving
  a lesson. Rescheduling them requires the same revision/audit/conflict protocol.
- Five full-training horses is an enrollment interval limit, not a limit of five lesson
  horses or appointments. Suitability and lesson workload are owner-configured per resource;
  unspecified suitability blocks confirmation, not a fabricated assessment.
- Group lessons are the default entry choice, with explicit roster/capacity; private,
  haul-in and off-site remain selectable. Do not infer actual capacity from marketing copy.

A series is staff-owned and expands in farm-local wall time, then resolves each occurrence
to UTC, preserving 14:30 across DST. Preview a bounded initial horizon (proposed 12 weeks,
maximum 100 occurrences per command). Staff confirms that finite set; no unreviewed future
appointments automatically become confirmed. Beyond the horizon, offer another preview.
If any occurrence conflicts, reject the batch atomically with occurrence-level errors;
staff can explicitly exclude dates through exceptions and resubmit. Never silently skip.
Generation retries upsert by stable occurrence key without duplicating canceled/skipped dates.
“One occurrence” creates an exception; “this and future” splits the series at the boundary,
retains prior IDs/history and previews future replacements. No all-history rewrite. Edits to
an occurrence keep its original key. Concurrent series edits use the same lock/revisions.

### REST contracts and staff UI

All routes below require authenticated manager access; mutations require a UUID idempotency
key and expected revision (create uses zero). No unauthenticated request-ingestion route.
List bounds are required (maximum 93 days) with pagination, no unrestricted full-history dump.

| Interface | Contract |
|---|---|
| `GET /appointments?from=&to=` and `GET /appointments/{uuid}` | Staff calendar summaries and authorized detail with revision/ETag; local zone and UTC times explicit. |
| `POST /appointments/preview` | Validate entry/series and return proposed occurrences, blocking conflicts, advisory warnings and policy revision; performs no allocation. Save revalidates everything. |
| `POST /appointments` | Create requested/hold/confirmed only through the mutation service; confirmation requires explicit action and eligibility. 201 plus UUID/revision. |
| `PATCH /appointments/{uuid}` | Reviewed reschedule/roster/notes with expected revision; cannot implicitly promote state. |
| `POST /appointments/{uuid}/confirm` or `/cancel` | Explicit transition, reason/confirmation, expected revision and command UUID. No hard-delete endpoint. |
| `POST /series/preview`, `POST /series`, `PATCH /series/{uuid}` | Finite occurrence preview/save, scope one/future, exception decisions and series revision. |
| `GET /resources`, `GET /route-groups`, protected policy/enrollment/welfare-block mutations | Bounded reference lists; manager-only mutations use the same transaction lock and audit. |
| `GET /appointments/{uuid}/participation`, `PATCH /participants/{uuid}/clearance` | Server-computed pre-ride requirements and explicit staff verification/revocation; manager capability, record checks, expected revision and audit. Never derives clearance from calendar confirmation. |
| `GET /audit?appointment=` and `GET /commands/{uuid}` | Separate audit capability; actor-bound command result for uncertain-response recovery. |

Return 400 validation errors, 403 forbidden, 404 unavailable records, 409 revision/resource
conflicts, 422 business constraints and 503 unresolved storage/retry failures. Never expose
SQL or private participant details in errors. Server sets actors/timestamps; clients cannot
supply approval identity or bypass warnings by posting an arbitrary boolean.

Staff opens weekly or monthly view, chooses a date and group/private/etc., enters local time,
participants/service-specific resources/location and any rotation plan, then previews. Show
calendar confirmation and participation clearance as separate badges. A pre-ride review lists
paperwork, suitability, welfare and resource conflicts; never label a pending rider ride-ready. Requested items are visually distinct and say
“does not reserve resources.” A separate Hold action requires expiry; Confirm shows conflicts,
warning acknowledgments and direct-agreement confirmation. Disable duplicate submit visually,
but rely on server idempotency. 409 reloads current revision before a new review. Cancellation
collects notice time/initiator and retains visible history without calculating charges. Use labeled keyboard-operable controls, textual statuses,
focus management, readable mobile day lists and no drag-only rescheduling. No public dashboard.

### Migration, rollback and delivery increments

1. Owner reviews this design and unresolved policy values (timezone, user IDs, capacity,
   suitability, horse workloads/rest, rotation policy, release/guardian checks, cancellation
   notice handling, Academy season/resources, travel buffers and retention). No MVP code or
   server changes are implied by architecture approval.
2. Implement schema/version checks and a local CLI migration plan/apply on disposable fixtures.
   Migration is explicit, not an activation side effect. Verify current backup and target;
   create tables/indexes, seed only settings, verify schema, then mark migration complete.
   Seed no historical appointments. Re-running a completed or interrupted migration must be
   safe; incomplete schema never serves writes. DDL is not assumed transactionally reversible.
3. Implement authorization, mutation service and manual single appointment with audit/conflicts.
   Test rollback and multi-worker MariaDB races before staff UI acceptance. Then deliver
   weekly/monthly UI; then bounded recurrence, exceptions and policy handling.
4. Test on disposable MySQL/MariaDB fixtures of target versions, with cache on/off. Rehearse
   migration from empty/previous schema, interrupted DDL and failed indexes; export/restore
   tables and verify counts, keys, revisions, audit consistency and representative calendar
   reads. SQLite remains a fast supplementary fixture only.
5. Before a separately approved sandbox release, prepare exact SHA, target-bound current
   DB/files backup, schema plan, roles plan and rollback evidence using the canonical runbook.
   No public page/menu rebuild or emergency activation. Conduct Beth/Kris staff acceptance
   before operational use; production requires another authorization.

Rollback before first write: deactivate scheduling and retain the new tables for inspection;
restore prior plugin only if schema-compatible. After writes: freeze staff mutations, export
all new appointments/audit/commands and take a fresh backup before reverting code. Use a
compatible reader or explicitly reviewed reverse migration; never restore an old database
and silently discard later appointments. A coordinated restore requires reconciliation of
post-backup staff entries. Deactivation/uninstall never drops private scheduling tables.

### Acceptance test matrix (to implement with the MVP)

| Test | Required outcome |
|---|---|
| Beth/Kris manual confirmed entry, week/month/detail | Same UUID, correct local/UTC time, resources, actor and audit; survives reload. |
| Visitor contacts farm / requested entry | No automatic creation or confirmation from Contact; requested has no allocations. |
| Anonymous/client/editor/emergency coordinator and ungranted administrator | REST/list/detail/audit/write denied; no cached disclosure. Spoofed actor rejected. |
| Two PHP workers confirm overlapping horse/instructor/arena | Exactly one wins, loser 409; no partial rows/audit; repeat with an initially empty calendar. |
| Simultaneous separate resources, adjacent buffered intervals, roster duplicate | Valid independent work succeeds; buffered overlap and concurrent rider/horse allocation fail; sequential group rotations remain valid. |
| Lost response, duplicate command, altered payload, stale edit/cancel | Original result replayed only for original actor/payload; conflicting replay/revision rejected. |
| Fail roster/allocation/audit/command write, kill before/after commit, deadlock | Transaction rolls back or committed command resolves deterministically; never false success. |
| Sunday crossing-midnight, Wednesday advisory, route geography/travel | Sunday client work blocked; non-Wednesday off-site allowed only after reviewed advisory; buffers enforced. |
| Monday Academy, protected Beth training/break, six full-training horses | Resource conflicts blocked; explicit Academy exception retained; sixth overlapping enrollment denied; lesson horses unaffected. |
| Group/Academy rotations with fewer horses than riders | Sequential shared-horse slots succeed; overlapping rider/horse slots and another appointment using a reserved pool horse fail. Participant capacity remains independent of horse count. |
| Off-site/haul-in using client horse | Confirm with correct instructor/location/route and client horse reference, without allocating a school horse; overlapping instructor/client horse/participant still rejected. |
| Unavailable/rest block, workload and recovery buffers | New confirmations cannot bypass welfare; adding a protective block flags existing conflicts. Exceeded or unknown configured limits fail, including adjacent/recurring workloads; no private health notes in overview. |
| Confirmed appointment with missing/expired release or guardian clearance | Slot remains confirmed but rider is visibly not cleared; pre-ride gate denies participation. Manual verified evidence unlocks only the relevant clearance, never horse suitability. |
| Cancellation notice, household/staff initiator and recurrence scope | Notice/history and corrections retained; no fee, forfeit or makeup-credit writes generated, including notice under 24 hours. |
| Group capacity, inactive or unsuitable horse | Capacity/suitability checked separately; unknown required scheduling policy blocks confirmation; reservation never implies participation clearance. |
| Weekly series across DST, gap/fold times, one/future edits and cancellations | Local time preserved; gap rejected/fold resolved; stable keys/exceptions and no duplicates after retries. |
| Hold expiry with stopped cleanup, concurrent confirm and expiry | Expired hold stops blocking by clock; state/audit transitions serialized and conflict rechecked. |
| Schema migrations/rollback on disposable MariaDB, cache on/off | Engine/index checks pass; interrupted migration resumes safely; restore/reconciliation loses no approved fixture entries. |
| Mobile/keyboard and stored-XSS/privacy tests | Accessible labeled calendar; escaped notes; no minor detail in overview, logs, URLs, public REST/search/cache. |

These are architecture acceptance criteria, **not executed scheduling-MVP tests**. The
current repository has no scheduling plugin or schema migration to test yet.
