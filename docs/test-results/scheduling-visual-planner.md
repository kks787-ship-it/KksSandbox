# Visual Day/Week planner — first visual Calendar Upgrade increment

**Scheduling 0.4.4 is installed on the protected sandbox.** Source: `5ce4301d4ba675f2541d1d72b8c8483f8ce1cecc`. Deployment completed **November 6, 2026, 11:37 AM PST**. A separate subsequent source/runtime verification also returned exit 0. Native protected-browser hands-on acceptance remains open.

This implements the first visual milestone from [the owner-approved master specification](../pilot/calendar-ux-owner-approved-master-spec.md), preserving its `d61d9be` update and the existing 0.4.3 booking flow.

## Delivered

- Primary Day/Week time-block grid, including meaningful empty days, 15-minute increments, facility timezone, Today/previous/next, Monday–Sunday week navigation, explicit Refresh, and persistent instructor/arena/horse filters.
- Day instructor lanes and separate overlapping event subcolumns. Normal desktop weeks fit all seven columns with a shared ruler; DST-transition views keep per-day offset-labeled rulers and bounded internal scrolling. Real spring/fall days have 92/100 quarter-hour instants, with no invented gap time or collapsed repeated occurrence.
- Distinct labeled requested, confirmed, active/expired held and canceled blocks. Only actual held/confirmed reservations block time. Recorded facility/resource availability, protective restrictions and existing travel envelopes are presented; requested/canceled/expired markers retain context without implying a reservation.
- Tap/click appointment details in an accessible dialog (phone bottom sheet), with existing authorized rider/horse/rotation, clearance/welfare, Edit/Confirm, Cancel and History controls. Escape closes it and restores focus. Full-roster editors may duplicate routine or group lessons into fresh requested drafts; group rosters/turns remain in Advanced rotation. Copies never reuse the source appointment identity or revision and require preview.
- Tap an open candidate to prefill date/time/resource in the existing booking flow. Complete service/rider/horse selections, then use the existing slot check to validate and select that intent. The grid never commits or reserves. Replacing an unsaved draft requires confirmation; declining preserves it.
- Mobile day/lane selectors, keyboard candidate navigation, scroll near working hours, clear status labels, and an accessible alternate appointment list. No drag-to-reschedule or resize write path was added.

## Targeted validation

| Stack/check | Result |
| --- | --- |
| WordPress 7.1.4 / PHP 8.3.36 / MariaDB 10.6.28, genuine provider MFA browser fixture | **56 passed** |
| WordPress 7.1.4 / PHP 8.1.34 / MySQL 8.0.46, genuine provider MFA browser fixture | **56 passed** |
| Pure presentation, DST, availability and draft-classification cases | **22 passed on both PHP versions** |
| Custom PHP syntax | **96 files passed** |
| Scheduling PHP coding standards | Passed |
| JS syntax and source whitespace | Passed |
| Deployment/backup guards | **22 synthetic scenarios passed** |
| Plesk identity guards | **6 mocked scenarios passed** |
| Local atomic directory exchange, corrupt-hash and symlink guards | **5 passed** |

Browser checks cover empty grids, click-to-create and authoritative preview/save, request/confirmation separation, multiple instructor lanes, active/expired holds, protective training blocks, dated arena gaps, duplication (including group turns), draft preservation, cancellation/history, role-scoped visibility, anonymous denial, month/week boundaries and both DST changes. Geometry and axe checks pass at **390/768/1280px**, including the phone details sheet. The adapter-disabled default deny passes on both stacks. Screenshots are synthetic local-fixture evidence, not photographs of the protected farm installation.

The previous 0.4.3 database/concurrency validation was not repeated as a new claim: the transaction, conflict, authorization and migration engines are unchanged. This increment targets changed presentation/read paths and existing guarded booking integration. The synthetic fixture's aged hold is a local expiration simulation; fixture seeding/probes are excluded from deployment.

## Protected installation and recovery

[Exact release inventory](scheduling-visual-planner-inventory.json): **26 scheduling files**, **8 changed/added paths**, **25 unchanged MFA/provider files**. Only the scheduling plugin changed. All hashes and PHP syntax were checked before atomic directory exchange; the exact original 0.4.3 files are retained privately.

The same-day complete 27-table backup was reused and its receipt/artifact integrity and under-24-hour freshness verified again. The previously successful isolated functional drill qualifies the recovery procedure. **No new backup capture or isolated restore was performed for this file-only increment**, and the current bundle is not represented as newly restore-tested. Off-server transfer remains untouched.

All **15 scheduling tables** retained identical deterministic primary-key-ordered JSON row hashes across the update. Schema remains **v3**; no migration, policy replacement, account/factor change, permission grant, master bootstrap, or synthetic protected appointment occurred. The existing Farm Owner mirror remains valid; anonymous planner/settings output is denied. Inquiry remains disabled, emergency inactive, mandatory MFA enforcement unchanged/off, and production excluded. No real booking messages were sent.

Rollback: verify installed files against this inventory and retained originals against the 0.4.3 inventory, then exchange only those plugin directories. Stop on unexpected source differences. No database rollback is needed for this schema-neutral release; never overwrite newer owner records automatically. The current canonical appointment representation remains readable by 0.4.3.

## Owner review — exact click path

1. Open [Digital Ranch Staff Calendar](https://sandbox.cornerstonefarmomak.com/wp-admin/admin.php?page=csf-scheduling) in the existing native MFA-authenticated Farm Owner session.
2. Select **Day** or **Week**, a date, and optionally an instructor/arena/horse filter. Use **Show calendar**, **Today**, **Previous**, **Next** or **Refresh**. On a phone, the week day selector changes the visible daily planner.
3. Tap a lesson rectangle to expand its details. Try Edit, History, Duplicate or cancellation on a fictional lesson. Group copies should preserve every rider/horse turn in Advanced rotation.
4. Tap an open **+** time block. Its date/time/resource becomes a draft intent. Choose the fictional rider, suitable horse, lesson type and required resources; click **Show 15-minute start slots**, **Check schedule before saving**, then Create. Review the calculated finish and independent rider-clearance warning before any confirmation.
5. Verify a conflicting or welfare-blocked attempt is rejected; inspect the day/week result and cancellation history. Preserve actual owner-created records and use explicitly fictional data for acceptance.

**Actual protected browser booking/mobile acceptance is pending.** This executor has no authorized native browser session. Source/runtime verification and local browser fixtures do not constitute Beth/Kris acceptance. No cookie export, impersonated CLI session or fake MFA verifier was used on the farm.

## Boundaries and next increments

A green/open time is a **candidate**, not proof that every service-specific horse, rider, workload, travel or resource condition is satisfied. Preview/save remain authoritative. Day views show up to six named instructor lanes plus an Other lane; filters reach individual resources. DST weeks can require internal sideways scrolling to keep every day's own offset ruler accurate.

Recurring arena hours and visual closure editing are **next**, preserving the existing absolute intervals; this release does not reinterpret or modify them. Family grouping, 15+15 arrival/departure reservations, buffer override privileges/indicators, horse correction/archive/delete, notification outbox/banners and dashboard integration remain in the approved specification. No unimplemented buffer is shown as reserved, and real booking email remains off.

## Evidence hashes

Restricted raw local logs remain separate from public server evidence. SHA-256:

- MariaDB native planner: `5a021778b43e5dd1de057e5ad7b707dc895f0c0d28c15abc69434239f0f8f0dd`
- MySQL native planner: `279418aee2c7714d6581354e1cb20a8979cf9abcef8c5685d55e091b248fea0e`
- Pure PHP 8.1 cases: `a9638165977f8f4e6c9c5fcd2773ac2ba1b1a4cfb7253cccec15324a99f30ef5`
- PHP syntax/coding standards: `dd7e07fdf50c5c204f3f3fcc68e057a610604a3ee16577797b96c9337e46bf9e`
