# Scheduling 0.4.6 — Manage lesson types

Implements owner-approved master specification section 1A on top of Scheduling 0.4.5. Existing visual planner, automatic start selection and server-authoritative scheduling remain in place. No added third-party dependencies, physical schema migration, billing or public booking.

## Delivered behavior

- Facility Settings has native Add/Edit/Enable/Disable fields, editable friendly names, category/order and separate classifications. Opaque new IDs never derive from punctuation or digits in names; existing IDs stay unchanged.
- Seven proposed examples load only as disabled, unsaved drafts. Student Lesson 30/60 durations are known; other durations, capacities, horse sources and resource requirements are not guessed. Paid/unpaid are distinct classifications with no financial interpretation.
- Enablement requires reviewed resources, explicit horse source/capacity and duration in 15-minute steps. Enabled types populate the existing booking dropdown; its configured duration calculates finish time.
- The existing permission/MFA/nonce/signed-review/revision/transaction/audit/command-replay boundary controls all policy writes. Booking-impact counts appear before Apply and are rechecked under the same singleton lock used for booking.
- Scheduling-rule/classification changes or disabling are blocked while future noncanceled appointments use that type, including requested/held states. Reconcile those bookings or create a new type; this increment does not silently reschedule them.
- Appointment history stores the service definition/label/classification as booked. Legacy appointments resolve the definition before its first catalog change. Renames, cancellations and disabling retain history. Historical records cannot silently be rescheduled under changed same-ID rules; select a reviewed new type instead. Horse suitability keeps disabled IDs when editing horse records.

## Validation

Local fixtures contain invented records only. Passing fixture tests does not establish Beth/Kris operational acceptance.

| Check | Stack | Result |
| --- | --- | --- |
| Existing database/concurrency/migration/permission regression | PHP 8.3.36 / MariaDB 10.6.28 | 366 assertions passed |
| Existing database/concurrency/migration/permission regression | PHP 8.3.36 / MySQL 8.0.46 | 366 assertions passed |
| Final catalog transactions, snapshots, legacy order and native browser/axe | PHP 8.3.36 / MariaDB 10.6.28 | 60 domain + 28 browser checks passed |
| Final catalog transactions, snapshots, legacy order and native browser/axe | PHP 8.1.34 / MySQL 8.0.46 | 60 domain + 28 browser checks passed |
| Existing native settings/calendar workflow | PHP 8.3.36 / MariaDB 10.6.28 | 15 parser + 104 browser checks passed |
| Full logical dump/import with catalog/history/MFA data | Both final focused stacks above | All 27 deterministic table hashes matched |
| Custom plugin PHP syntax and coding standards | PHP 8.3.36 | 97 PHP files passed |
| Existing development/Plesk guards | Local mocks | 22 + 6 scenarios passed |
| File exchange/hash/link guards | Local temporary directories | 5 checks passed |

The regression's 366 assertions are 88 foundation, 131 calendar, 66 staff-usability and 81 permission checks. Existing two-worker booking/resource/permission races and populated migration/restore checks passed on both engines. The signed-settings suite covers invalid nonce, tampered review, stale tab, exact replay, lost commit acknowledgement, denied trainer access, rotations, cancellations and rider-clearance distinction. Older workflow selectors/text were updated for the already-installed planner's collapsed list and time summary. The final ordering correction is covered by five additional native-field checks on each focused stack; it does not change the scheduling engine.

Catalog-specific cases cover unknown disabled drafts, malformed metadata, editable friendly labels with stable IDs, opaque new IDs with punctuation/digits in names, automatic 30/60 finishes, disabled historical types, legacy pre-snapshot labels, future rule-change rejection under the policy lock, transactional history failure rollback and anonymous denial. Mobile/tablet/desktop checks use 390/768/1280px and axe; adapter-disabled denial passes. WordPress fixture: 7.1.4. An additional earlier PHP 8.3/MySQL focused run passed 55 domain/28 browser checks before the final ordering-only correction; it is not substituted for the final matrix above.

## Release and rollback

The exact inventory covers 28 scheduling files plus 25 unchanged MFA/provider files. Nine scheduling paths change, including two additions. Schema remains v3 with 15 scheduling tables / 27 total WordPress and scheduling tables. No real lesson type is created, renamed, enabled or disabled by installation; the owner reviews policy changes in the native form.

Before rollout, verify the complete backup receipt/artifacts are less than 24 hours old and the isolated functional recovery procedure is qualified. Compare exact prior/candidate file hashes; stage and lint the complete plugin directory, exchange it atomically, then verify FPM runtime, authorization defaults and deterministic hashes of all 15 scheduling tables. Preserve original files privately. No account/MFA/grant/inquiry/emergency/website/production changes.

**Rollback boundary:** File-only rollback to 0.4.5 is valid only before catalog policy/history changes, after exact source checks. Once catalog metadata has been saved, older code rejects it: use a compatible forward fix or a separately reviewed matching recovery. Never silently remove metadata or restore over newer owner records. The local restore comparisons cover metadata/history/MFA preservation; they are not a new protected-site recovery drill.

## Owner test path

[Staff Calendar](https://sandbox.cornerstonefarmomak.com/wp-admin/admin.php?page=csf-scheduling) → **Facility settings → Edit facility settings → Manage lesson types**. Add/configure a fictional type, Review changes, then Apply reviewed settings. Select an open planner slot and the enabled type; verify finish, Check schedule, then explicitly Save. See the [acceptance walkthrough](../13-scheduling-staff-acceptance.md).

Recurring arena/instructor hours and visual closures remain the next calendar increment. Upstream client-preview and DayPilot research documents are preserved; no public booking, competing engine, real notification or payment is introduced here.

## Protected installation — verified

Implementation: `d8f01eacff8e9b277a10a404895a3d9a6cfb58ef`; final ordering-corrected source pin: **`c2783b5e3f6eba046b97e5dc517a198517532377`**. The newer upstream frontend/research/DayPilot documentation commits were preserved, with no library or competing engine installed.

On November 6, 2026 (Pacific), the old 0.4.5 inventory and recovery gates passed. The exact nine-path update completed with exit 0, followed by an independent read-only FPM verification with exit 0. All 28 scheduling and 25 unchanged MFA/provider files matched the release inventory. The complete verified scheduling directory was atomically exchanged; originals remain privately retained.

Receipt/artifact integrity and less-than-24-hour freshness of the existing same-day 27-table backup were checked again immediately in the rollout. The established isolated functional recovery procedure remains qualified. **No new protected backup capture, isolated restore or off-server transfer was performed for this increment**; the reused bundle is not claimed as newly restore-tested. The new full metadata/history restore comparisons above are disposable local tests.

All 15 scheduling tables retained identical deterministic primary-key-ordered row hashes across installation. No schema migration, service-policy application, account/permission/MFA change or real record creation occurred. Existing Farm Owner privileges remain 20, with no employee grants added. Anonymous planner/settings rendering remains denied. No real mail/payment/public booking, inquiry enablement, emergency activation, website-content change or production operation occurred.

Protected native-browser acceptance remains **pending**. This executor has no authorized native owner browser session and did not export cookies, impersonate a CLI user or install synthetic MFA adapters on the farm. The owner should now follow the click path above, beginning with a fictional disabled draft and explicitly reviewed operating values.

Release hash: `d4c619da4eccc3034735be6ffd2f256af55b3d3bea6e68bd6a8c958a113d034e`.

## Sanitized evidence hashes

Raw local logs and protected receipts remain restricted separately. SHA-256 of completed logs:

- MariaDB full regression: `d95c6f5f0e020806e288fae5e9b971625b15990e6f8c4dd6a2008cd9a217e448`
- MySQL full regression: `b481d7e0184b9fe8c5c2ddfb7462443f4330035f26e2cc0f5c0e614b64075452`
- PHP 8.3/MariaDB final catalog and recovery: `7521e7f58b6a2a4d9b0c70712c983a716fe1b997689ba10c01864688e9e4a5bb`
- PHP 8.1/MySQL final catalog and recovery: `57a30d3a7849ddd6809ea1de7ffcb1812638da31dd6337db68099b7d8033a0f2`
- Native settings/workflow regression: `ece5cd835bab7e3777f816d083d4cc9eefb17f1275ea5ce0b7f08a663e8c66b8`
- PHP syntax/standards: `0c8f8ac2ad3953c35965fd3a890082b11d3f1d07f982824fa55bed920298c900`
