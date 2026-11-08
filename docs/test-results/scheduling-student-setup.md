# Scheduling 0.4.9 — Student lesson setup

## Scope

Implements Issue #11 acceptance blocker and master specification §1B (0469261).
Six scheduling source files change; no schema, engine, MFA, permissions or account changes.
The 59-file source inventory is in `scheduling-student-setup-inventory.json`.
Scheduling 0.4.9 source update completed on the development sandbox, with independent
integrity readback. Real facility lesson settings remain owner-controlled; no types were
automatically enabled. Protected operational receipts remain private.

## Behavior

Lesson Types offers **Set up Student 30 & 60 Minute Lessons**. It prepares unsaved,
enabled proposals, reuses matching stable IDs and rejects ambiguous duplicates or conflicting
durations. Capacity, horse source and resource review are required. Review changes saves
nothing; Apply reviewed settings commits through the existing audited revision guard.
The success message links to Calendar and horse suitability. Original Pilot objects and
historical booking bodies/labels remain unchanged. New service types do not silently change
horse suitability or increase work limits: a 60-minute service can still require shorter turns.

Booking state is visible beside the primary lesson selector. New authorized bookings retain
Confirmed as their existing default. Existing Held appointments stay Held. Held explicitly
requires an expiry; choosing Confirmed removes that requirement. No screenshot state is
attributed to a default defect without evidence.

## Local validation

- PHP 8.3/MariaDB 10.6 and PHP 8.3/MySQL 8.0: 366 database assertions per engine,
  including two-worker conflicts, migration and logical recovery regressions.
- Focused Student setup browser suite: 39 checks per tested stack, PHP 8.3/MariaDB
  and PHP 8.1/MySQL. Includes native review/apply, initial versus enabled choices,
  unchanged Pilot definitions/history, same-slot 30→60 finish calculation, stale preview
  rejection, suitability and work-limit rejection, existing Held preservation, re-enable
  with stable ID, duplicate rejection, accessibility and overflow at 390/768/1280 pixels.
- Broader PHP 8.3/MariaDB calendar regression: 71 browser and 49 resource checks;
  DST, authorization, welfare, closure/booking concurrency and 27-table restoration pass.
- Focused runs verify 27-table restored metadata, history and MFA, plus default-deny access.
- 100 custom-plugin PHP syntax checks and 95 configured coding-standard files pass; JavaScript and shell syntax pass.

All appointments and configuration mutations in these tests use disposable synthetic fixtures.
Local passes do not establish owner acceptance or prove any real Student type is enabled.

Local log SHA-256 evidence is recorded in `scheduling-student-setup-evidence.json`.

## Owner acceptance

1. Open Lesson Types and choose **Set up Student 30 & 60 Minute Lessons**.
2. Review capacity, horse source, travel and required resources for each proposal.
3. Review changes, then Apply reviewed settings. Review horse suitability and work limits.
4. Return to Calendar; select each Student type from the same clicked time slot and verify finish.
5. Confirm an ordinary fictional lesson; check conflicts and Held behavior where intended.

No business configuration is activated automatically. Family lessons/buffers remain separate work.

## Continuation reconciliation — November 8, 2026

The continuation checkout was created from `codex/takeover-audit` at
`22657b84d192b91bc364553794e4aba9e7ccd1f2`, on local branch
`codex/digital-ranch-continuation`. No scheduling implementation, schema, policy,
account, MFA or operational data was changed during this reconciliation.

### Evidence and remaining acceptance

- GitHub Issue #11 comment 6710262880 records the six-file Scheduling 0.4.9
  sandbox source update and independent integrity readback. This agrees with the
  release report above. It is historical deployment evidence, not a new runtime
  inspection or evidence that the owner's actual Student services are enabled.
- Later Issue #11 comment 6712522878 records navigation triage: direct Calendar
  access was denied as well; source integrity, schema, callback registration and
  authorization mirror were verified, but the authentication prerequisite was
  unmet. Native owner authentication correction remains pending. Do not implement
  a speculative menu fix, grant access, or bypass MFA.
- The owner-approved master specification remains
  `docs/pilot/calendar-ux-owner-approved-master-spec.md`. Its older milestone
  descriptions are superseded by the later 0.4.9 release evidence, not permission
  to downgrade the installed scheduler.
- Personal Context returned unavailable. The original Baseline Drift Investigation
  conversation and prior Codex task were not accessible here. The current owner's
  continuation request was verified through room history; no unseen approval or
  private recovery receipt was inferred.

### Source findings for the next increments

1. Family visits and 15-minute outer buffers remain unimplemented. The appointment
   payload has no visit identity; allocations reserve lesson time plus configured
   travel only. Add grouping and shared reservations through the existing facility
   lock, command ledger, audit, preview rollback and write-time validation. Test
   30+30 and 120+120+60, partial/uncertain results, cancellation/recalculation, DST,
   unrelated-family collisions and concurrent booking before release.
2. Horse classification correction remains explicitly blocked by `horse_saved`.
   School horse slot identity is a positive resource ID; client horse identity is
   the negative horse-row ID. Removing the rejection or merely exposing fields
   would be unsafe without checking existing slot, appointment and welfare
   dependencies. Preserve UUID/history and validate remapping, archive/restore and
   conditional unused-entry deletion under the same transaction. Existing field
   permissions already distinguish resource management from suitability changes.
3. Homepage weekly visibility and committed-booking notifications remain queued.
   Add durable events atomically with actual booking writes; preview, rollback,
   replay and uncertain outcomes must not generate duplicate alerts. Test recipient
   scope and mail through a sink only. Real email, staff grants, public booking,
   payments, emergency activation and production remain held.

### Checks run in this continuation

- 280 existing Python recovery tests passed using disposable local temporary
  files. The first read-only run could not create temporary directories and
  errored; the subsequent approved temporary-file run passed. Neither run contacted
  a farm or established current operational recovery.
- 22 mock deployment/backup guard scenarios and 6 mock Plesk identity preflight
  scenarios passed. These are local guard tests, not a live preflight.
- All 38 release-inventory files present in the repository match their recorded
  SHA-256 digests. The other 21 entries are the separately supplied Two-Factor
  vendor distribution, absent from this checkout; they were not reverified here.
- PHP, Composer and Docker are unavailable in this fresh workspace. Automatic
  approval review rejected system-wide local tooling installation as outside the
  authorized installation scope. The command did not execute. Approval for a
  disposable-workspace tooling installation has been requested; no workaround or
  remote fixture installation was attempted.
- The recorded PHP/database/browser results above are prior-release evidence,
  not reruns. No new feature is represented as implemented or accepted.

### Deployment hold

No farm-server call or deployment was made. No current less-than-24-hour verified
backup, exact reviewed deployment payload, run-specific isolated recovery evidence,
transparent preflight or separate post-write readback was established in this
continuation. Keep sandbox writes held until the actual required conditions are
verified. Never restore over newer owner data. Full PHP/database/browser validation
and access to the prior private operational context remain prerequisites for the
requested implementation and qualified rollout.

### Subsequent local implementation

After the reconciliation above, an unreleased 0.4.10-dev unused-horse correction
increment was implemented with isolated PHP WebAssembly validation. See
[specific changes, executed tests and remaining rollout blockers](scheduling-horse-correction.md).
The system-wide installation remains rejected; the temporary WebAssembly alternative
was separately approved and does not establish database/browser acceptance.
