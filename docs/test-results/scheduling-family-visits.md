# Family command and native editor candidate — 0.4.12-dev

November 8, 2026. Local source only; no publication, installation, migration or farm writes.

## Implemented

`family_visit_saved` is dispatched by the existing Store transaction, singleton
settings lock, global revision, actor-bound exact replay ledger and audit. Its
bounded 20-item body includes a stable visit UUID, expected visit revision and an
explicit same-family attestation. Each child's save/cancellation is separately
authorized before and inside the transaction. Every existing active member must
appear; omission cannot silently detach a child. Existing unrelated appointments
cannot be attached or overwritten through this first version.

The writer calculates resource reservations server-side, clears only the complete
authorized group's old allocations within the transaction, and rebuilds its
itemized appointments, reservations and history. Any failure rolls back the complete
change. Cancellations retain their records and teaching times of surviving lessons;
the survivor's newly exposed buffer must pass resource/hours/closure checks again.
Standalone child edits/cancellations are rejected and direct staff to the visit.
Horse turn identities, occupancy, suitability, health and workload checks are still
performed by the original appointment writer. Sibling arrival sharing does not
waive mandatory travel or horse recovery. Family envelopes must fit actual facility
opening instants as well as resource availability, including DST.

**Confirmed-only boundary:** held and requested group items are rejected. Allowing
independently expiring shared holds without transactional expiry handling could
leave a survivor without its required outer buffer. Ordinary existing held/requested
appointments retain their previous behavior. This restriction is visible in the UI.

The native Family visit screen supports individually selected service/rider/horse
and resources, copying a lesson into the next elapsed-time slot, explicit repeated-hour
choice, one combined signed review, back-to-draft, and a final all-item save. The
review is bound to the actor, session, exact payload, command and global revision;
POSTs also require the existing native nonce and MFA-backed permissions. No grants
were added. Failed/uncertain save responses retain the exact command for replay.
A group read returns members only when every active child is visible to the session.
Calendar detail links open the visit, and saved resource buffer windows drive the
calendar's reservation shading and detail text. Canceled windows are historical.

Visit metadata uses versioned documents in the existing details table; no physical
tables were added. The undeployed schema-4 candidate remains required. Source-only
rollback to an earlier schema-4 development commit that ignores family linkage is
NOT safe; release/recovery must pin the complete reviewed candidate and compatible
snapshot. Nothing here updates or bypasses the deployment recovery contract.

## Tests actually run

Using isolated temporary WordPress Playground PHP WebAssembly:

- **103 planner checks on each of PHP 8.1.34 and 8.3.36.**
- **48 command/parser checks on each version**, executing real Calendar, Family,
  Time, Booking and Resource Hours code with an in-memory database double.
  Includes written shared envelopes, per-child authorization rejection, partial
  child failure, context cleanup, standalone-edit rejection, omitted/stale member
  rejection, survivor-buffer recalculation/conflict, actual horse consecutive-work
  rejection, strict facility opening, native duration derivation and DST gap/fold.
- Existing **26 horse correction, 33 lifecycle, 17 recurring-hours checks** pass
  on both PHP versions after integration.
- All **140 plugin/test PHP files** parse on PHP 8.3.36. Shell syntax and
  `git diff --check` pass.

The unit harness simulates transaction rollback; it does **not** establish real
InnoDB isolation, SQL behavior, the real permission registry or browser behavior.

## Authored but NOT run

`family-visit.php` adds guarded disposable WordPress/database cases for all-table
preview rollback, second-child failure, late ledger failure, exact replay,
changed-payload rejection, group membership, scope and survivor allocations.
`family-races.sh`/`family-race.php` launch actual concurrent workers with the same
reviewed revision, requiring exactly one complete family and one conflict, with
no partial sibling records. Both are wired into the existing disposable runner.

No native PHP/MySQL/MariaDB/Docker WordPress fixture is available in this environment.
These database tests, scoped-role tests, HTTP/nonce tests, PHPCS and protected browser
acceptance at 390/768/1280px remain unrun. This candidate is not deployment-ready.

## Remaining scope

- Shared requested/held visits and safe expiry; adding existing independent lessons
  to a visit; detailed multi-horse rotation editing in the compact family screen.
- Automatic 15+15 for independent lessons (the group candidate does not silently
  backfill legacy reservations), reviewed buffer configuration and authorized
  per-segment override/audit/indicators.
- Compact drawer refinements, accessibility/mobile review, inline combined review,
  five-hour and edit/cancel/closure races on both actual database engines.
- Dashboard, notification infrastructure without real delivery, closure Edit,
  used-horse remapping and other unfinished approved roadmap items.

No real email, staff grants, payment, public/emergency booking, production operation
or MFA change occurred. Publication remains held; deployment also requires fresh
complete backup, verified isolated functional recovery, transparent preflight,
reviewed exact payload and independent readback, including the new schema contract.

## November 8 continuation — 0.4.17-dev

This supersedes the confirmed-only and compact-editor limitations above. Active
children must share one state and, for holds, one exact UTC expiry. The existing
SQL occupancy predicates release every held child's allocations at that common
expiry; rows/history are retained. No timer changes appointments or reconstructs
buffers after partial expiry. Renewal/confirmation requires a new whole-group
review and revalidation under the same lock. Mixed states/expiries are rejected;
confirmed lessons cannot be downgraded to requested/held.

The native editor now supports requested/held/confirmed visits, explicit multiple
riders and horse turns, draft turn/lesson removal, scoped attachment of existing
independent lessons and new lessons in all-canceled visits. Canceled records are
not revived. Empty-visit read/reuse checks visibility over its historical members.
A lesson already attached to another visit cannot be taken over. Detailed copies
shift horse-turn instants by the lesson's elapsed duration, preserving DST offsets.
Fresh bookings default confirmed for actors with confirmation rights; hold fields
are hidden/disabled by the local form script unless Held is selected. The server
never trusts the visibility of those fields as authorization.

Independent integration review found and fixed a preexisting cancellation detail
snapshot retaining `confirmed` while its appointment row said `canceled`. New
cancellation history/details now match the row; existing owner records are untouched.

Actually run: **87 command/parser checks on each PHP 8.1.34 and 8.3.36**, including
common expiry, mixed-state rejection, whole-group confirmation, attachment, empty
visit scope/history and explicit detailed riders/turns. Full isolated candidate
suite is **346 checks per PHP version**, including the separately documented sink
adapter. **164 PHP files** parse for both versions; staff JS syntax passes. Synthetic
native component rendering emits the new controls without PHP errors. These are
boundary-double/component tests, not WordPress browser/database acceptance.

Real-database expiry/partial-confirmation/renewal cases were added to the guarded
fixture but NOT RUN. No MariaDB/MySQL, Docker/Podman or native PHP/Composer exists
in the available runtime. The existing Chromium socket restriction and cloud-browser
local-file prohibition remain; no bypass, live hosting or publication was used.
The repository has no checked-in Actions workflow for running this unpublished
candidate. SQLite/WASM is not equivalent evidence for these MySQL lock/JSON/race
semantics. Full SQL, concurrency, HTTP nonce/MFA/scope and three-size accessibility
acceptance remain release blockers. Existing source/publishing and farm gates hold.
