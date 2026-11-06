# Immediate calendar time selection — Scheduling 0.4.5

**Installed on the protected sandbox: November 6, 2026, 12:32 PM PST.** Source commit: `67c7406dfa1fd322b8943c1f38fc6f1147541da0`. Update and separate subsequent source/runtime verification both returned exit 0. Protected native-browser owner acceptance remains open.

## Fixed owner-observed blocker

In 0.4.4 a calendar click retained an intent but left the start selector empty until a separate Show start slots action. The clicked date/start is now immediately selected, with an elapsed-duration finish. Choosing a 30/60/120-minute or other configured 15-minute-multiple service recalculates that display without losing the time.

Only missing required details are requested. Once service, rider, applicable horse, instructor/arena/location and any real hold expiry are present, the editor automatically checks that exact instant through the **existing authenticated, locked transaction-preview endpoint**. It never silently substitutes another start. Rejected times remain selected with the server's specific reason and checked alternative choices where available. An alternative is adopted only when the user chooses it.

The ordinary grid-click workflow no longer requires **Show 15-minute start slots** or manual reselection. The optional control now browses alternative starts; after an unverifiable transport result it offers an explicit read-only retry. A final **Check schedule before saving** and explicit Create/Save remain mandatory.

This changes only `assets/routine.js` and scheduling version metadata. No new booking engine, API, database contract, billing fields or write path was introduced. Existing stable appointment/service/rider/horse identifiers and actual booking command identity are preserved. Availability probes use transient read-only command identifiers and persist no ledger entries; they never replace the final save command.

## Safety and async behavior

- Missing fields, failed suitability, horse workload limits, conflicts or stale review do not clear/move the selected time or enable Create.
- Input checks are debounced and serialized per editor; old-context responses cannot restore an earlier choice. Same-cell repeated clicks preserve the draft and do not duplicate checks.
- Unsaved-draft replacement still requires confirmation. Titles affect final review without needlessly repeating availability checks.
- An uncertain/unreadable read retains the draft and stops automatic retries. No automatic booking submission or commit retry was added.
- Grid DST choices keep their explicit offsets. A changed date is resolved by the existing server time service; gaps are rejected, and repeated local times require an explicit occurrence. Clock choices survive other missing-field edits.
- Selected/estimated finish is not a confirmed reservation. The existing server still derives the canonical finish and enforces permissions, revisions, conflicts and horse welfare. Preparation/family buffers not yet implemented remain unreserved.

## Changed-input validation

| Check | Result |
| --- | --- |
| WordPress 7.1.4 / PHP 8.3.36 / MariaDB 10.6.28 focused auto-selection workflow | **38 passed** |
| WordPress 7.1.4 / PHP 8.1.34 / MySQL 8.0.46 focused auto-selection workflow | **38 passed** |
| Existing native visual planner regression on MariaDB/PHP 8.3 | **56 passed** |
| Changed PHP header syntax/coding standards; changed JS syntax; source whitespace | Passed |
| Existing deployment/backup guards | **22 synthetic scenarios passed** |
| Plesk guard cases | **6 mocked scenarios passed** |
| Atomic exchange, corrupt-hash and symlink checks | **5 local checks passed** |

**132 focused/regression workflow checks** in total. The focused fixtures use actual native MFA sessions and the pinned provider. They cover Wednesday 10:00 selection before missing inputs, 30/60/120-minute elapsed finishes, automatic validation without Show slots, missing arena, unsuitable horse, long-horse-turn rejection, a conflicting selected time and explicit alternatives, stale responses, repeated clicks, unknown read outcomes, both DST fold offsets, an unresolved fold, a spring gap, mobile taps, keyboard activation, 390/768/1280px layout/axe, and explicit-only creation.

All scheduling rows remain identical during grid selection and automatic/final read-only previews. A fictional requested appointment appears only after the test user's explicit Save. The existing planner regression also passes details/actions, group-rotation duplication, held/expired/canceled states, protective time, dated arena gaps and scoped-role/anonymous visibility. Adapter-disabled default denial passes on both stacks. Broad previously passed database/migration suites were not rerun or claimed as new results because their source/contract is unchanged.

## Protected rollout and recovery

The [release inventory](scheduling-auto-selected-time-inventory.json) verifies all **26 scheduling files** and **25 unchanged MFA/provider files**, with exactly **two changed paths**. The complete verified scheduling directory was atomically exchanged; original 0.4.4 files remain privately retained for hash-guarded file-only rollback.

The same-day complete 27-table backup was reused, with receipt/artifact integrity and under-24-hour freshness checked again. The existing successful isolated functional recovery procedure remains qualified. **No new backup capture or restore was performed for this increment**, and the reused bundle is not claimed as newly restore-tested. No off-server transfer occurred.

All **15 scheduling tables** retained identical deterministic primary-key-ordered JSON row hashes across installation. Schema remains v3; no migration or actual farm record was created/changed. The existing Farm Owner permission mirror, MFA/provider files and settings were preserved. No accounts, grants, MFA enrollment/enforcement or facility policy changed. No real message or payment was sent/processed. Inquiry disabled; emergency inactive; production excluded. Anonymous planner/settings rendering remains denied.

Rollback: verify the installed candidate and retained originals against the respective 0.4.5/0.4.4 inventories, then exchange only the plugin directories. Stop on unexpected file changes. Do not restore a database or overwrite newer owner records for this file-only update.

## Owner retest — no redundant time selection

1. Refresh [Digital Ranch Staff Calendar](https://sandbox.cornerstonefarmomak.com/wp-admin/admin.php?page=csf-scheduling) in the existing MFA-authenticated Farm Owner session.
2. In Day or Week, click an open time block (for example Wednesday 10:00 when permitted). The editor must immediately show that date/start and the service-derived finish.
3. Choose the fictional rider, suitable horse, service and required instructor/arena. **Do not click Show start slots.** The time should validate automatically. Missing/blocked details should explain the problem while retaining your original time.
4. Select **Check schedule before saving**, review the result, then explicitly Create/Save a fictional lesson. Check the displayed requested/confirmed state separately from rider clearance.

Actual protected native-browser acceptance is still pending; this executor has no authorized native browser session. Source/runtime installation checks and local fixture results do not constitute owner acceptance. No live cookies, impersonated CLI user or synthetic MFA verifier were used on the farm.

## Next increment and deferred modules

Recurring arena/instructor hours and visual closure management remain next, preserving existing availability records. Family grouping, smart buffers, horse corrections and staff notifications remain in the active master specification. Public booking, real booking mail and billing/payment processing remain off. The Acuity research is a public usability benchmark; its private internals were neither assumed nor copied. Issue #24 remains future billing work, with no processor or financial implementation introduced here.

## Evidence hashes

Restricted raw local logs are retained separately. SHA-256:

- MariaDB focused auto-selection: `6eaaf648d1b5e4921c7676b2f0b42d98885e035754d5643c727fb1cf183a085e`
- MySQL focused auto-selection: `2b9884ead1248a9eb22a250428141478cc21505ba70e94e3f70cf1f75aae377d`
- MariaDB visual planner regression: `5a021778b43e5dd1de057e5ad7b707dc895f0c0d28c15abc69434239f0f8f0dd`
- Changed PHP syntax/standards: `2903f853c300d6a81a7d41c8d733c20480209c07ebe2f6935ef73b22ecf34313`
