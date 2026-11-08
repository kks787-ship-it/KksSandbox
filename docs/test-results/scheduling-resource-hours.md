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

## Release status

Scheduling 0.4.7 sandbox update completed. Detailed operational verification is retained privately. Owner hands-on acceptance remains outstanding. No third-party calendar library was adopted.

## Continuation scope verification — November 8, 2026

A fresh read of remote `codex/takeover-audit` still returned
`22657b84d192b91bc364553794e4aba9e7ccd1f2`. The continuation's resource-hours engine,
resource review UI and `assets/resources.js` are unchanged from that baseline.
The older inventory's `deployment pending` label is historical: the release status
above records completed 0.4.7 sandbox installation; the 0.4.8 report and Issue #11
comment 6700850974 record the later compact interface installation/readback, followed
by 0.4.9. No new farm runtime inspection was performed in this verification.

**Already delivered; do not rebuild:** recurring arena/instructor/location hours,
facility inheritance, custom split weekdays/Closed days, effective-date preservation
of dated availability, dated/full-day/weekly closures with bounded end dates,
one-occurrence/whole-series reopening, signed review/apply, affected-booking checks,
DST handling and concurrency-safe commands. The later dedicated Manage Hours and
compact Block Time interfaces remain present. Installing source never chooses the
owner's actual operating schedule.

**Remaining approved work/acceptance:**

- Owner hands-on acceptance in the correctly authenticated Farm Owner session remains
  open. Verify both arena and instructor Friday/split hours, a full-day closure,
  a repeating closure, one-date reopening and affected-booking review. Do not infer
  actual saved farm hours from synthetic fixtures or automatically replace them.
- The master specification asks for Edit as well as Reopen on an existing closure.
  No direct in-place closure-edit command/control was found: current commands are
  add (new UUID/expected=0) and release/reopen. Preserve these delivered paths and
  implement a narrowly reviewed edit/replacement workflow with history and impact
  review when addressing that remaining UX requirement; do not silently release a
  protection or claim this control already exists.
- Family visit/outer-buffer work must extend hours/closure impact and booking checks
  to actual shared buffer reservations. Current logic covers recorded lesson/travel
  envelopes; it does not implement the queued family reservation model.
- The local horse-lifecycle/schema-4 candidate needs the existing 49 domain checks,
  browser matrix, booking/closure races and 27-table recovery rerun before release.
  The 0.4.7/0.4.8 passes above remain historical, not new acceptance of that candidate.

Added and executed 17 isolated checks against the unchanged real hours/time domain
code on PHP WebAssembly 8.1.34 and 8.3.36: split shifts, unconfigured days, inheritance,
legacy effective dates, timezone mismatch, invalid ranges, bounded recurrence,
one-date versus series reopening, DST gaps and folds. These require no WordPress,
database or network and do not substitute for the existing integration/browser suite.
`tests/scheduling/resource-hours-unit.php` is included in the disposable test runner.

Household onboarding, legally reviewed agreements and payment infrastructure are
separate planned modules in the wider roadmap (`docs/14-client-readiness-onboarding-payments.md`).
They are not enabled or folded into this active Issue #11 calendar increment. Do not
invent agreement terms, take signatures, grant staff access, activate public signup or
booking, or initiate billing/payments. Current scheduling evidence flags remain manual
staff evidence, not signed agreements or proof of payment. Existing owner holds stand.

## Local follow-up: future closure Edit and allocation impact (0.4.14-dev)

November 8, 2026. `resource_closure_edited` now uses the same signed native review,
per-resource authorization, expected document/global revisions, singleton lock and
history as existing closure commands. It keeps the same closure/resource identity,
retains reopened dates and appends exact before/after documents. It does not cancel
or move bookings. An explicit Edit control appears for future closures/series.
Closures that have started cannot be rewritten: reviewed reopening plus a new future
closure remains available. No automatic split/rewrite of historical recurrence is claimed.

Inspection found that impact SQL queried `slots` (horse identities) for arena and
instructor resources. It now queries `allocations`, where their actual resource
reservation envelopes are stored. This corrects buffer-only overlap counts, including
shared-family outer windows. The visible first-occurrence review list also includes
stored family resource windows; the bounded aggregate remains authoritative.

**Executed:** 20 isolated edit/impact checks on each of PHP 8.1.34 and 8.3.36,
using the actual resource-hours writer with synthetic document/database boundary
doubles. These verify future/stale/permission guards, immutable target, preserved
reopened dates/history, buffer-only impact and the allocation-table query. Existing
17 recurring-hours checks and 48 family command checks still pass on both versions.
All 145 plugin/test PHP files parse on both versions. Diff checks pass.

**Authored, unrun:** the disposable database family suite now covers a closure
intersecting only a survivor's buffer, preview rollback, replacement impact, and
unchanged appointments/allocations after closure edit. Database/browser/concurrency
acceptance and PHPCS still require an available fixture. This is an undeployed local
candidate, not an additional live closure change or owner acceptance.
