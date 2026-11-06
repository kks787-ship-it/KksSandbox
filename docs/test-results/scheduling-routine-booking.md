# Routine booking — first Calendar Upgrade increment

Scheduling **0.4.3**, schema **3**, **15 scheduling tables**. Implements the routine-booking priority from the owner-approved [master specification](../pilot/calendar-ux-owner-approved-master-spec.md), building on 0.4.2. This is the first functional increment, not completion of the entire upgrade package.

## Behavior

- Choose the lesson date, configured service, one rider, horse and required resources. Start choices follow the facility's 15-minute grid and actual hours/availability. Later starts load in bounded pages; disabled choices explain the selected resource or policy constraint.
- The server calculates finish and the sole rider's horse turn from the configured elapsed duration. Routine entry has no manual finish or rotation rows. Configured 30/45/60/120-minute arithmetic is covered; duration never determines rider count.
- DST gaps are rejected; repeated times require an explicit occurrence. Long lessons still fail when a full-length horse turn exceeds welfare limits. Use the collapsed Advanced rotation editor for shorter turns or multiple riders/horses.
- Only held bookings expose expiry. Preview reports the teaching and currently reserved intervals. Existing travel reservations remain; the later independent-visit 15+15 buffer feature is **not implemented in this increment**.
- Slot checks and preview reserve nothing. Requested drafts check occupied resources for availability without acquiring confirmation permission or becoming reservations. Save still uses the existing locked, revision-safe, audited command path. Requested saves remain unreserved until held/confirmed.
- All forms on a rendered page share one policy/revision snapshot. A concurrent policy change cannot attach a new revision to older displayed settings.

## Validation

Source candidate: `68fa9e04cdb78425bfdd1cbe2a52a2295def3c59`.

- PHP 8.3.36 / MariaDB 10.6.28: **366 database assertions**.
- PHP 8.1.34 / MySQL 8.0.46: **366 database assertions**.
- Both engines passed empty/populated simultaneous booking, shared horse/instructor/rider/incomplete group races and concurrent migration. Populated upgrade/downgrade guards and logical recovery checks passed.
- MySQL native browser: **36 checks**, including 390/768/1280px geometry and axe accessibility. The genuine-session domain probe separately exercises **18 arithmetic/DST assertions** per browser stack.
- **95 custom PHP files** pass syntax validation; scheduling coding standards pass. JavaScript and shell syntax pass. Deployment guards: **22** synthetic scenarios; Plesk guards: **6 mocked identity scenarios**. Local atomic file-exchange/hash/link guard checks: **5**.

Combined MariaDB native browser: **104 checks**. Together with MySQL, **140 browser checks passed**; the two genuine-session probes also passed their 18 domain assertions each. Both browser fixtures confirmed the adapter-disabled default deny. Local fixtures use synthetic records and the actual pinned MFA provider. Test-only setup/authentication probes are excluded from the release inventory and must never be deployed.

## Protected rollout and recovery

Only the scheduling plugin is eligible for this file update. The [exact inventory](scheduling-routine-booking-inventory.json) identifies 23 scheduling files, nine changed/added paths and 25 unchanged MFA/provider files. No database migration, activation, policy replacement, account change, permission grant, MFA change or mail dispatch is part of the update.

Before writes, verify a fresh complete 27-table backup, the previously qualified isolated functional recovery procedure, exact target identity, existing source checksums, free space and disabled inquiry/inactive emergency state. Download only the pinned public source, check every hash and PHP syntax, then atomically exchange the complete verified scheduling directory. Retain the exact original privately for file-only rollback. Compare all scheduling table contents before/after; stop on unexplained drift without attributing it or restoring database data automatically.

Rollback: verify the installed candidate against this inventory, verify the retained original against the 0.4.2 inventory, then atomically exchange only those plugin directories. Stop if either side differs from its expected hashes. Database recovery is unnecessary for this schema-neutral update and must never overwrite newer owner changes automatically. A new backup's integrity verification does not mean that particular bundle has undergone another isolated restore.

## Protected staff acceptance

Open [Staff Calendar](https://sandbox.cornerstonefarmomak.com/wp-admin/admin.php?page=csf-scheduling) using the existing MFA-authenticated Farm Owner account.

1. Use clearly fictional rider/horse/resource records with explicit suitability and availability. Preserve the owner's existing horse/instructor records; do not recategorize them as part of a test.
2. Choose an enabled lesson type, rider, horse, instructor/arena and date. Show start slots; use Show later starts if needed. Select a start and verify the calculated finish and reserved interval.
3. Preview, create a requested lesson, then edit and confirm it. Verify confirmation retains any separate pre-ride clearance warning.
4. Start a conflicting fictional lesson and verify the occupied choices are unavailable. Review the reason; do not override occupied time or welfare protections.
5. Edit/cancel the fictional lesson; check day/week display and retained history on a phone.

Protected acceptance requires a real authorized native browser session. Server-side source/runtime inspection and local fixture browser results cannot substitute for that action. Never export live cookies, use a fake MFA verifier or impersonate a controller through CLI to manufacture acceptance.

## Remaining upgrade work

Family visit grouping/atomic or reconciled saves; merged outer buffers; audited permission-controlled buffer exceptions; horse classification/lifecycle corrections; committed-booking banners/acknowledgments and notification outbox; dashboard homepage. Real booking email remains off. These requirements are retained in the master specification and are not claimed as delivered here.

## Protected result

Completed **November 6, 2026, 10:38 AM PST** from source `68fa9e04cdb78425bfdd1cbe2a52a2295def3c59`. File update and a separate subsequent runtime/source verification both returned exit 0.

- All **23 scheduling files** and **25 unchanged MFA/provider files** matched the release inventory. Nine scheduling paths changed, including the two added assets/classes.
- The existing **15 scheduling tables** retained identical deterministic, primary-key-ordered JSON-row hashes across the update. Schema remains v3; no migration ran. Existing rider, horse, resource, appointment, welfare, permission and audit rows were preserved.
- A new complete **27-table backup** passed artifact/receipt integrity verification before writes. The prior successful isolated functional drill continues to qualify the recovery procedure; this new backup was **not separately restored** during this file-only update. No off-server transfer occurred.
- Runtime checks confirmed v0.4.3, complete existing facility policy, the existing 20-privilege Farm Owner mirror, no additional employee grants, hidden anonymous settings and default-off mandatory MFA enforcement.
- Inquiry remained disabled; emergency remained inactive; production was excluded. No policy replacement, real message, account/factor change, owner-record correction or new pilot appointment occurred.
- Original plugin files were retained privately for hash-guarded atomic file rollback. No database rollback or restoration was performed.

**Protected native booking/mobile acceptance is pending.** No authorized browser session is available to this executor. Follow the staff steps above in the real MFA-authenticated browser; do not substitute the local 140-check browser result for protected-site acceptance or Beth/Kris approval.

### Evidence hashes

The release manifest contains every deployed file hash. Restricted raw local logs are retained separately; their SHA-256 values are listed here without private server evidence:

- MariaDB database: `7b64e864cca3a83e3f06f14770a9c57c183a4bd08cb70f4119c8d1a334c3e0df`
- MySQL database: `842496553dc25342eb5b7a3fd3c8eef943603e1ac43305be82a8c5af47b7abd0`
- MariaDB combined browser: `ff5cdef1a09284569f13b94f0489bac5c6c49c46ebc4285f3a06ef0564cc8af5`
- MySQL routine browser: `94d94e3de0aaec6267661695759ade6f42958189d452e62a2efeb690fbf2cb81`
- Custom PHP lint: `ee9480a95ca98c6be0201f39756eb372ca2862e5297640c5a77e0f334b749659`
