# Native recurring resource hours and closures — 0.4.7

This increment retains the native planner. It adds reviewed weekly hours and visual closures for arenas, instructors and locations. Family-session buffers, horse archive controls, notifications and the weekly dashboard remain subsequent increments. No notification dispatch is part of this release.

## Behavior

- Each resource explicitly keeps its dated availability, inherits facility hours, or uses custom split weekday hours. Existing dated entries remain stored; an effective local date controls the new base. Installing source selects no operating policies for the owner.
- Managers select one quarter-hour, two range endpoints, or an entire local day. Selection only fills a native form. A separate signed review and acknowledgment precede the transactional save.
- Closures may repeat weekly through an explicit inclusive end date, bounded to one year. Reopening applies to one occurrence or the entire recorded closure. Other closures, facility hours, horse welfare and booking conflicts continue to apply.
- Review counts future noncanceled appointments requiring attention, including actual reserved slot/travel envelopes. Existing appointments are never automatically canceled or moved. The count does not attribute preexisting conflicts to the proposal. More than 2,000 future assigned appointments blocks the operation.
- Existing `availability_manage` plus `resource_view`, verified MFA, nonce, actor/session-bound signature, global revision, entity revision, singleton transaction, command replay and audit/history protections govern changes. This increment grants no permissions and adds no endpoint or schema migration.
- Weekly hours pin the facility timezone. A later timezone change closes that base until reviewed. Fold openings narrow to the later start/earlier finish; missing boundaries stay closed. Recurring protective closures cover both fold occurrences and protect the entire local date if a boundary disappears. One dated closure can use explicit first/second occurrences.

## Owner walkthrough

1. Open Digital Ranch Staff Calendar and filter to the fictional arena or instructor.
2. Expand **Hours and closures**. Choose **Custom weekly hours**, set the effective date, enter split weekday ranges, then **Review weekly hours**. Check the affected-booking count before applying.
3. Enable **Manage resource availability**. Tap a time cell to prefill a closure; enable **Two-tap range selection** for longer spans. Alternatively use **Close this day** or the labeled date/time fields.
4. Enter a reason. For weekly repetition choose an explicit end date. Review, acknowledge affected appointments, and apply.
5. Open the recorded closure and review either one-date reopening or release of the entire series. Confirm other protections still appear. Existing appointments remain visible throughout.
6. Check ordinary lesson creation and editing, day/week navigation, keyboard controls and phone layout. Owner acceptance is separate from local synthetic tests.

## Release and rollback limits

Database schema remains v3: 15 scheduling tables, 27 including WordPress. New weekly/recurrence/exception metadata resides in existing versioned documents and history.

A source-only downgrade to 0.4.6 after these documents are saved is unsafe: that code does not interpret weekly closures. Prefer a forward correction. Recovery must use an explicitly reviewed matching source/database point and must never overwrite newer owner work automatically. Before any sandbox update, verify a complete backup younger than 24 hours, qualified 27-table isolated recovery, exact source hashes, rollback reference, and unchanged MFA/owner grants/inquiry/emergency state.

## Validation

Disposable local WordPress 7.1.4 fixtures:

| Check | PHP 8.3 / MariaDB 10.6 | PHP 8.3 / MySQL 8.0 | PHP 8.1 / MySQL 8.0 |
| --- | --- | --- | --- |
| Existing foundation/calendar/staff/permissions regression | 366 passed | 366 passed | Not repeated |
| New weekly-hours/closure domain checks | 49 passed | Not separately repeated | 49 passed |
| Native browser, keyboard, security and axe checks | 32 passed | Not separately repeated | 32 passed |
| Two simultaneous closure/booking workers | Passed | Not separately repeated | Passed |
| Full 27-table dump/import, deterministic row comparison | Passed | Covered by existing regression | Passed |

The new browser suite covers split weekly hours, Review-before-Apply, signature/nonce tampering, anonymous access, stale review comparison, exact replay, existing-booking impact, one-date reopening, keyboard range selection, 23-hour spring closure, and 390/768/1280px accessibility/overflow checks. Domain tests also exercise fold/gap boundaries, preserved dated entries, legacy editing, independent protective blocks, rollback on history failure, repeat limits and fail-closed timezone changes.

22 scheduling PHP files pass coding standards; scheduling PHP and the two new PHP fixtures pass syntax checks. JavaScript syntax, shell syntax, 14 sandbox-preflight unit tests, six mocked Plesk scenarios and five atomic-rollout/hash/link guard checks pass. The optional existing rsync permission test could not run because host rsync is unavailable; this release uses the independently tested atomic directory-exchange workflow, not that rsync script.

The initial new race fixture was denied because its CLI worker lacked the disposable fixture's MFA adapter; adding the existing local-only adapter corrected the fixture. No farm authentication bypass was added. Earlier browser failures were test assertions using an unauthenticated read, hidden details text, and a followed login redirect; the final suite verifies those outcomes explicitly.

Final review caught a cross-resource closure guard error before deployment. The target identity check was corrected and the new suite was rerun on both engines, including a real booking preview for a different arena. The 366-check legacy regressions preceded that final scoped correction; the 49 domain and 32 browser checks on each engine exercise the final source.

Mock/local fixtures are not protected-site acceptance. Raw log digests and exact source hashes are retained in adjacent evidence/inventory JSON files.

## Protected sandbox readiness

The existing 0.4.6 source inventory, native FPM runtime, fresh-backup integrity/freshness gate and qualified recovery procedure passed verification. Anonymous planner/settings access stayed denied; 20 Farm Owner privileges and zero employee grants were unchanged. The exact verification-mode payload was initially rejected as a possible deployment; local AST/control-flow tests proved its fixed verification mode before the same command was accepted. No rejected operation was routed around the review.

0.4.7 deployment and protected runtime verification are recorded separately after completion. No real weekly hours, closures or lesson records are automatically seeded. Owner browser acceptance remains outstanding.
