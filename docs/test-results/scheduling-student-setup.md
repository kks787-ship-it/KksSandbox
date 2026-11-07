# Scheduling 0.4.9 — Student lesson setup

## Scope

Implements Issue #11 acceptance blocker and master specification §1B (0469261).
Six scheduling source files change; no schema, engine, MFA, permissions or account changes.
The 59-file source inventory is in `scheduling-student-setup-inventory.json`.
Source deployment is pending; real facility lesson settings remain owner-controlled.

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

## Owner acceptance

1. Open Lesson Types and choose **Set up Student 30 & 60 Minute Lessons**.
2. Review capacity, horse source, travel and required resources for each proposal.
3. Review changes, then Apply reviewed settings. Review horse suitability and work limits.
4. Return to Calendar; select each Student type from the same clicked time slot and verify finish.
5. Confirm an ordinary fictional lesson; check conflicts and Held behavior where intended.

No business configuration is activated automatically. Family lessons/buffers remain separate work.
