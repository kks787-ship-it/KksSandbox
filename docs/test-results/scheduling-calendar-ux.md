# Calendar-first native interface — Scheduling 0.4.8

This is a presentation increment over 0.4.7. The scheduling engine, schema v3/15 tables, transaction lock, command replay, authorization registry, welfare rules and conflict checks are retained. No third-party calendar library is added.

## Working behavior

- Calendar-first landing: day/week planner with Schedule Lesson, Block Time and Manage Hours. Farm settings, lesson types, rider/horse/resource setup and weekly-hour forms are not rendered in the everyday calendar.
- An open time cell opens a compact native dialog/bottom sheet with exact time and lane context. Lesson type determines finish. Staff select the rider and an appropriate horse; a horse is never silently assigned. A unique active instructor/arena can be suggested, but the authoritative server still checks it.
- Optional title and state controls are under More options. Routine titles derive from the readable lesson type and rider. Authorized new lessons default to Confirmed; the inline summary and final action distinguish reserved confirmations from unreserved requests. Copies explicitly start as requests.
- Exact routine drafts automatically pass through the existing authenticated read-only preview. Stale responses cannot enable saving for changed drafts. One explicit final Schedule/Save submits the existing nonce/revision/UUID-bound native POST, which rechecks all constraints transactionally. An automatic preview never reserves anything.
- Incomplete rider clearance is visibly separate from reserving time and requires acknowledgment. It never updates evidence or clears the rider. Complex rotations and cancellation retain their additional checks.
- Block Time opens a compact panel with resource, time range/day, reason presets and bounded recurrence. Preview and explicit Save remain separate. A zero-impact review needs no redundant checkbox; a positive impact retains mandatory acknowledgment. Already-visible affected first-occurrence bookings are identified without exposing other staff's hidden records. The full count also covers other dates and reserved travel envelopes.
- Resource selection, signed review and displayed target remain aligned. Switching resources hides the earlier target's review. Rapid panel switching and Escape restore the original forms without duplication or draft loss.
- Manage Hours shows only the selected resource, Closed controls and Add Break. Inherited facility hours are presented as editable starting values for a custom proposal, never saved automatically. Existing dated hours and closure history remain intact.
- Manage Lesson Types submits only catalog fields. The server combines validated rows with its current policy snapshot; unrelated policies are never trusted to hidden client copies. Existing review, booked-label history and in-use-service guards remain in force.

## Measured native workflow comparison

These are actual Playwright interactions with source-pinned local WordPress interfaces, not a mockup or human acceptance study. The 0.4.7 baseline's 56 file checksums were verified. Both versions use synthetic records, the same qualified horse/verified rider, an arena context and one active instructor suggestion. Initial authentication/filter selection is excluded. A click, fill or select counts as one action; final navigation is included. Scroll jumps count changes in window position greater than 150px, sampled after each action; panel/internal-grid scrolling is separate.

| Scenario | 0.4.7 actions | 0.4.8 actions | Window scroll jumps, before → after |
| --- | ---: | ---: | ---: |
| Confirmed 30-minute lesson | 9 | 5 | 3 → 2 |
| 90-minute arena block, zero affected bookings | 7 | 6 | 5 → 0 |
| Friday finish change, existing custom hours, zero affected bookings | 5 | 4 | 5 → 2 |

The reduction is conditional on the stated context. More resource choices or genuine clearance/impact warnings require additional actions. We do not claim one-click bookings or zero scrolling for every task.

## Validation

| Check | PHP 8.3 / MariaDB 10.6 | PHP 8.1 / MySQL 8.0 |
| --- | --- | --- |
| Native UX/security/accessibility/browser assertions | 71 passed | 71 passed |
| Existing weekly-hours/closure domain assertions | 49 passed | 49 passed |
| Simultaneous booking-versus-closure workers | Passed | Passed |
| Full 27-table dump/import and deterministic row comparison | Passed | Passed |

Full existing foundation/calendar/usability/permission regressions also passed **366 assertions on each engine**, using PHP 8.3. The regression's heading assertion was updated for the renamed calendar; its clearance, nonce and mutation assertions remain. The engine/authorization source modules were not rewritten.

The release browser scenarios cover normal 30/60-minute lessons, one explicit final save, reschedule without duplication, requested-copy semantics, 90-minute closure save, positive-impact acknowledgment, visible affected-booking identity, split Friday hours save, catalog-only policy preservation, DST gaps/folds, hard resource conflicts, unsuitable horses, separate clearance acknowledgment, rapid modal restoration and target-bound closure review. Booking/block panels and the hours editor pass axe checks at 390/768/1280px, with keyboard Escape/focus restoration and overflow checks.

All 100 custom-plugin PHP files pass syntax checks; the 95 files selected by configured coding standards pass; the changed scheduling plugin comprises 23 PHP files. JavaScript/shell syntax, 22 development guards, six mocked Plesk scenarios, 14 sandbox-preflight unit tests and five local atomic-rollout integrity/permission/link guards pass. Log digests and measured interaction records are in the adjacent evidence JSON. Fixtures are local and synthetic; these results are not protected-site owner acceptance.

## Boundaries and remaining work

Family grouping/outer buffers, horse archive controls, notifications and the weekly dashboard remain queued. This increment supplies their compact shell, not those business features. Closing a dialog preserves its draft within the page; cross-page/reload draft persistence is not added. Complex groups still use the explicit advanced rotation editor. Impact details are scoped to visible first-occurrence bookings; the full future count must still be reviewed when other dates or travel are involved.

Candidate source is ready; protected rollout remains pending. No server command ran during this increment. Operational review details and bindings remain private. No production changes, account grants, MFA changes, public booking or real notifications are part of this release.

For rollout, use a complete integrity-verified backup younger than 24 hours and the qualified 27-table recovery procedure. Compare all 59 package files (34 scheduling plus 25 unchanged MFA/provider files), publish only the nine reviewed scheduling paths, preserve permissions and compare all 15 scheduling-table row baselines before/after. Retain the exact prior source and a recovery reference. Do not automatically restore a database over newer owner changes. Do not downgrade below the 0.4.7 metadata-aware engine while weekly-hours/closure records exist.
