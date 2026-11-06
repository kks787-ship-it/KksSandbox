# Digital Ranch — Consolidated Calendar Pilot UX Implementation Package

**Status:** Owner-approved requirements, consolidated November 6, 2026. **Priority:** ACTIVE staff-calendar pilot (Issue #11). **Starting point:** Scheduling 0.4.2 installed on the protected sandbox, pending protected booking acceptance. This document is the canonical consolidated implementation request; earlier Issue #11 comments remain rationale and test cases, not competing instructions.

**Goal:** Make scheduling practical for a boutique lesson/training barn. A staff member should be able to book 15-minute-grid lesson slots by choosing a service, efficiently book consecutive members of the same family, protect horse/staff recovery time without wasting instructor hours, correct setup mistakes, and immediately see new bookings and meaningful overrides on the Digital Ranch homepage.

## 1. Routine booking: choose a start slot; derive the rest

- Date and **start time selected on a 15-minute grid** in the farm's configured IANA timezone. Show available candidates based on actual policy and instructor/resource availability; unavailable slots must explain why.
- Choose a configured **lesson/service type**, which determines its duration, capacity and required resource kinds. Support at least 30-, 60- and 120-minute offerings, plus other configured 15-minute multiples. **Do not hard-code a 'double' lesson as a particular rider count.** Keep pricing/entitlement metadata associated with the selected service without inventing rates or payment behavior.
- Automatically compute lesson finish from start + configured duration. **No ordinary manual end-time field**, and no duplicated appointment/horse-turn start/finish entry for standard single-rider lessons. Display the computed appointment interval and total blocked interval before committing.
- Initial horse turn should default to the sole selected rider and the lesson time, **subject to horse work/rest rules**. Do not assume a two-hour shared lesson means one horse works for two hours. Add an **Advanced rotation** editor only when several horses, several riders or short turns actually require it; prepopulate sensible non-overlapping turns and respect actual resource/welfare checks.
- Booking state, hold expiry and confirmation must be clear: show expiry only for a real hold, not a confirmed booking. The preview explains *which field/policy is wrong*, not generic 'timezone' when a service-duration or resource requirement failed. Farm timezone validation success is not itself an error.
- Use server-authoritative timezone/DST interpretation, revision-safe commands, MFA/access checks and transaction/audit requirements already built. Preserve drafts after stale/uncertain results; don't create duplicate bookings on retry.

## 2. Family visits / consecutive appointments

- Add a first-class **Book another lesson for this family visit** action to the routine booking form; allow duplicate previous lesson then adjust service, rider, horse and other details. One family visit contains **individually itemized lesson appointments** with their own service type, rider(s), instructor(s), horse(s), location, attendance, cancellation and future billing entitlements.
- Automatically propose the next block starting at the previous lesson's end when the instructor/location and real required transitions allow. Allow an explicit gap for necessary turnover, horse rest or travel.
- Example A: **two siblings, 30 minutes each**, one parent, same instructor/arena. Lesson 1 10:00–10:30, lesson 2 10:30–11:00; parent/family arrives once, leaves once. Preserve both lesson records, with one unified visit grouping.
- Example B: a family with **120 + 120 + 60 minutes** of consecutive lesson blocks (5 teaching hours total), independently attributed to the relevant family riders; do not assume which rider takes which portion or whether a 'double' contains two riders.
- A family-group booking must show a single convenient review of each block and the overall reserved interval. Prefer atomic group transaction. If current storage/commands require per-lesson commits, make partial completion obvious, use stable group/command IDs, show exactly which blocks saved, provide safe reconciliation/correction and never claim an all-or-nothing save without support.
- **Editing or canceling one family lesson** must handle group linkage and recalculated outer buffers without quietly changing the other lessons, removing historical records, double booking or creating stale internal holds.

## 3. Automatic 15-minute buffers and exception policy

- Default to **15 minutes preparation BEFORE** and **15 minutes wrap-up AFTER** an independent lesson. Treat those as calendar reservations, distinct from the paid/teaching duration. Display as shaded intervals and prevent overlapping independent reservations on the affected instructor and relevant shared arena/location resources.
- **For linked, contiguous same-family lessons** with compatible instructor/location/turnover requirements, **automatically merge the internal post/pre buffers** into a single family visit. No manual override needed simply for siblings arriving together. Example A reserves **09:45–11:15** total: 60 minutes teaching + 30 minutes of outer buffers (NOT a gap between the children). Example B without any required internal breaks reserves **30 minutes outer buffers beyond its 5 teaching hours**.
- For **unrelated clients/visits**, keep normal buffers; don't merge simply because adjacent timestamps match. Allow a properly authorized manager to override just an identified pre/post buffer in an exceptional case.
- Use an explicit application permission such as `buffer_override`, intended for authorized Farm Operations and Head Trainer roles; **do not hard-code people, WordPress usernames or administrator access**. Capture affected appointment/resource/segment, reason, reviewer, before/after intervals, revision and auditable override. Show an unmistakable alert marker afterward. No permission grants are implied by this implementation request.
- A buffer override **never waives** actual occupied slot conflicts, horse health/welfare restrictions, configured horse recovery, suitability, rider clearance, instructor travel or mandatory opening-hours rules. Stricter real turnover requirements prevail. Model shared family reservations so their own sibling blocks don't appear as a false collision; retain concurrency-safe database validation and idempotence.
- Keep buffer defaults configurable later; initial pilot baseline is 15+15, not a hard-coded permanent farm-wide operating rule. Make outer buffers visible when selecting a start slot and on the weekly dashboard.

## 4. Fix horse-setup correction and lifecycle

**Current defect:** existing `horse_saved` edits cannot change `source` (client-owned vs school horse) or linked `resource_uuid`; user cannot delete a mistakenly created horse. These are everyday setup mistakes, not exceptional administrator operations.

- Allow **Edit horse classification** (client-owned ↔ school horse) and correct associated school horse resource, preserving horse UUID, history and safe resource associations. Validate service suitability, active resource type, uniqueness, training cap and future appointment implications; show affected bookings and block destructive/unsafe transition until reconciled.
- Support **Delete mistaken unused entry** only after checking **under transaction/lock** that it has no linked appointment, resource slot, welfare restriction, history that must remain, or other operative dependency. Show a named, explicit irreversible confirmation and keep a minimum audit/tombstone. No direct SQL or hidden force-delete.
- For horses with history, use **Archive / Restore**: archived horses are ineligible for new bookings and excluded from current active counts, but historical appointments, welfare notes and audit continue rendering. Restore only after rechecking suitability/availability/conflicts.
- Expose obvious edit/archive/restore/conditional delete controls in Farm Setup, with human-friendly dependency errors and normal reviewed authorization.
- **Clarify labels:** current `source` is a booking-resource classification, **not a named legal owner**. A future client-owner relationship system is separate (horse-scoped permissions Issue #18); do not imply it already exists.
- Keep existing instructor resources and user-supplied records intact. No automated deletion or recategorization of owner-created horses/instructors.

## 5. Immediate staff attention when a booking is committed

- As soon as a booking transaction is **actually committed**, create a durable notification event and show a conspicuous **heading-scale 'NEW LESSON SCHEDULED' in-app banner** to entitled staff. Failed previews, stale writes, uncertain unconfirmed states and replays must not create fake or duplicate alerts. Include simple **Acknowledge** and unread history without changing booking state.
- Email **now** using the already approved common WordPress `wp_mail()` / existing native PHP mail route and its established transport/queue semantics; **future push** consumes the same event/outbox. IMAP, separate SMTP vendor/integration or new email-management infrastructure are **not** prerequisites.
- Recipient selection by *effective application permission and assigned instructor(s)*, with authorized Farm Management oversight. No hard-coded identity, no anonymous/boarder recipients, and no real staff send until consent/delivery scope is specifically approved. Do not mistake the completed **security-mail test** as permission for lesson-notification delivery.
- Prefer **one consolidated family-visit notice per recipient** showing all confirmed sub-lessons, clear instructor assignments, actual outer buffers and the accurate committed subset. Distinguish **NEW**, **RESCHEDULED**, **CANCELED** and **URGENT CHANGE**; use meaningful subject lines and protected detail links.
- Privacy: send only minimal details needed for staff coordination. Avoid medical, minor contact, guardian/legal and sensitive client notes in email. Protected detail page enforces current access independently.
- Use transactional outbox with event/recipient idempotency, bounded claim/retry and distinct states: committed, queued, submitted, provider accepted, receiver confirmed/unknown. Do not claim exactly-once email delivery or blindly resend uncertain outcomes. Reuse Issue #20 as the shared notification architecture tracker.
- The **large visual banner** must be accessible, with proper semantics, readable on mobile and persistent until acknowledged. Avoid repeatedly obscuring content after the user acknowledges it.

## 6. Digital Ranch home: This Week's Schedule + override signals

- Place a compact, functional **THIS WEEK'S SCHEDULE** block near the top of the authenticated Digital Ranch home/dashboard. Mon–Sun in the facility timezone, emphasize today, show grouped family visits, individual appointments, teaching times, outer buffer times, assigned instructor/resource and authorized rider/horse information. Calendar and homepage must use the **same authoritative read model**, not duplicated booking logic.
- Clicking a day/lesson opens the authenticated staff calendar in the corresponding context; never leak restricted rider details via universal home tiles, public HTML, shared caches or frontend API responses.
- **Approved buffer overrides:** high-contrast **bright red** visual highlight, explicit **BUFFER OVERRIDE** text and alert icon on the affected lesson and weekly summary. Show precise overridden segment, approver, reason and reviewed window in the protected details. Use red+text+icon, not color alone.
- **Ordinary merged sibling buffers are not overrides** and must not be colored red. Distinguish genuine approved exceptions from unresolved safety or approval warnings. Approved buffer waiver does not imply horse welfare has been waived.
- Include outstanding **new booking / changed booking** notices with unread/acknowledged status on the dashboard. A changed or canceled session should not disappear without context. Responsive single-column mobile list and useful tablet/desktop week overview.
- A real named instructor may be a valid teaching-resource record without automatically having a WordPress login or management privilege. Role visibility and availability remain separate.

## 7. Implementation boundaries and sequencing

Codex should **deliver working, testable increments**, not another speculative architecture document:

1. **P0 / Unblock everyday booking:** 15-minute start grid, duration-derived ends, simplified single-rider workflow, field-specific validation, correct horse rider linkage, no confusing hold expiry. Make a single 30/60-minute fictional booking pass actual protected acceptance.
2. **P1 / Family and buffer engine:** link sequential 30+30 and 120+120+60 blocks, pre/post 15-minute reserve, merge same-visit internal buffers, preserve strict horse rest, resource conflict and manager override auditing.
3. **P1 / Fix setup mistakes:** horse classification edits, conditional delete and archive/restore, with historical integrity. This is a known active owner blocker and should not be indefinitely postponed behind notifications.
4. **P1 / Operational visibility:** dashboard weekly schedule, conspicuous red override markings and in-app committed-booking banner, then safely tested outbox/email adapter. Keep real outbound booking emails **off until explicitly scoped recipient authorization**.
5. After protected synthetic acceptance: hand the resulting UI to the owner for practical testing, then expand staff enrollment/grants in a separately authorized checkpoint. Full role portal Issue #22 and push/email extensions remain subsequent iterations.

Use current Scheduling 0.4.2 installed-sandbox functionality as the starting reference and inspect newest branch state before edits; **do not downgrade or overwrite Codex's newer work**. Implement in the existing PHP/JS framework with safe reviewed schema changes only when needed. Honor the Recovery-First Autonomous Development Policy for approved, contained, reversible sandbox improvements, with fresh backup/recovery appropriate to the actual change. Do not require repeated blanket approval for safe local work; do not presume authorization for real client/horse deletion, staff grants, production changes or real external notification.

## 8. Acceptance cases — minimum demonstrable evidence

- A 30-minute private lesson starting on a 15-minute slot yields correct computed finish; 60/120-minute types and other 15-minute increments work. Helpful errors for missing arena/availability, unassigned rider turn and true horse-work limit; DST gap/fold and America/Los_Angeles correctly resolved.
- One family visit with two 30-minute sibling lessons yields two records and **one 09:45–11:15 reserved envelope** for a 10:00–11:00 teaching visit. No manual buffer-override click required.
- A 120+120+60 family session shows **five teaching hours**, optional real breaks and exactly outer buffers when continuous. Clear safe handling of partial/uncertain saves, independent cancellations and changed sessions.
- Two unrelated families remain buffer-conflict protected. An authorized role can review/approve a specified buffer waiver with reason and red flagged audit; an unauthorized role cannot. Horse rest, hard resource occupancy and welfare holds remain unwaivable through buffer override.
- A horse mistakenly saved as client-owned can be corrected to school resource after valid mapping. Unused mistakes can be deleted with confirmation; previously used horses are archived/restored without breaking old lesson history.
- A newly committed lesson triggers **one in-app alert per entitled recipient** (or one grouped family alert), with no duplicate on replay. Sink-only email assertions prove recipient selection/idempotence; **actual booking email waits for scoped outbound consent**. Cancel/reschedule produce accurate labels.
- Week dashboard renders role-appropriate events, family visit grouping, buffers, notification state and high-contrast override flag at mobile/tablet/desktop sizes; normal grouped siblings are not red.
- Preserve all current WordPress/MFA roles, accounts, verified recovery/backup support, horse/rest rules, audit and transaction protections. No real payment, customer messaging, public booking, production or emergency module changes.

## Cross-references

- [Issue #11](https://github.com/kks787-ship-it/KksSandbox/issues/11) active calendar MVP and prior owner clarifications.
- [Issue #20](https://github.com/kks787-ship-it/KksSandbox/issues/20) central notification outbox and future push.
- [Issue #22](https://github.com/kks787-ship-it/KksSandbox/issues/22) later graphical role/permission administration.
- [Issue #18](https://github.com/kks787-ship-it/KksSandbox/issues/18) future horse-scoped boarder roles.

**Next Codex report:** one concise implementation status with actual committed changes, targeted test receipts, protected sandbox deployment state, any concrete blockers and the exact hands-on retest instructions. Do not mark these requirements implemented until actual code and verification exist.
