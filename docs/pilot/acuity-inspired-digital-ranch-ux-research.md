# Digital Ranch — Acuity Scheduling UX Benchmark and Build Direction

**Research date:** November 6, 2026  
**Status:** Public-facing product documentation and screenshots reviewed; implementation guidance, **not** copied source code.  
**Active baseline:** Scheduling 0.4.4 protected sandbox visual Day/Week planner. The owner-found click-empty-slot handoff is still an active fix.  
**Priority:** Calendar Issue #11 first. Payment/billing planning Issue #24 is a **future dependency**, not current implementation authorization.

## Executive design decision

Use Acuity Scheduling as a **workflow/usability reference**, not a visual or architectural clone. It separates a rich staff administration calendar from an uncomplicated client booking wizard. Digital Ranch must do the same while retaining horse welfare, arena/resource availability, same-family consecutive lessons and role-specific access.

**Original implementation only.** No copying proprietary source, CSS, brand assets or private screenshots. Acuity's server internals, tables and security topology are not publicly observable; the backend below describes **publicly documented interface/API behavior and the design we should build**, not a claim about Acuity's internal implementation.

## 1. Public interface comparison

| Area | Acuity documented workflow | Digital Ranch deliverable |
| --- | --- | --- |
| Admin calendar | Calendar screen offers **Day / Week / Month** and filters; appointment details/actions open by selecting an appointment. Month displays appointment and blocked-time counts. | Keep our v0.4.4 **15-minute grid** primary. Add a compact mini-month and consistent quick Edit/Reschedule/Cancel/Duplicate action drawer. No need to rebuild the visual grid. |
| Create staff booking | Calendar → Add New → Appointment, choose type/calendar/date/time, client, then schedule; custom time choice can warn but still be allowed by Acuity staff. | **Click a blank visual block**, and that precise date/start is **visibly selected immediately**, before choosing rider/horse; required fields trigger the existing validation automatically, followed by explicit final review. Do NOT copy a general 'custom time overrides hard restrictions' loophole. |
| Client self-booking | Simple appointment type → date/time choices → client info/intake → payment if configured → confirmation/reminders. Client scheduler can be hosted or embedded. | Future public portal: select lesson → eligible day/time → guardian/rider intake → review → **optional deposit/checkout** through separate billing module. Public booking stays off until approved. |
| Staff availability | Recurring weekly hours, date overrides, temporary repeating periods and blocked-time appointments. | Recurring **Main Arena and instructor hours** with 'Inherit facility hours', custom split times, day-specific closure tiles on visual planner and clear reopen controls; existing absolute intervals remain intact until migration is proven. |
| Rooms/resources | Calendars represent staff or locations, shared resources limit simultaneous bookings across calendars. | Instructor, arena, school/client horses and off-site location are distinct, properly capacity-limited resources with **additional horse safety** and rider clearance rules. |
| Padding | Per-appointment before/after padding increases blocked time and does not overlap; client sees service duration rather than padding. | Our default **15-minute outer buffer per independent visit**. Automatically merge **internal** human buffers for approved consecutive same-family lessons (30+30 or 120+120+60); horse recovery remains separately enforced. |
| Labels and alerts | Calendar labels/colors, configurable email/text notices and appointment change events; admin mobile app offers event notifications. | Clear visually labeled event states, conspicuous **NEW LESSON SCHEDULED** in-app notice and transactional outbox; future push. Real `wp_mail()` booking messages only after separate recipient and sending consent. |
| Permissions | Staff can have limited view or view/edit access to assigned calendars rather than universal account admin access. | Role/capability checks across the farm; no hard-coded people; instructor resources aren't automatically WordPress accounts. Existing Farm Owner and MFA protections preserved. |
| Payment | Acuity permits online service payments/deposits, receipts/invoices and linked payment processors. | Preserve booking→account/invoice/payment relationships NOW, implement merchant/gateway, boarder portal and full training billing LATER via Issue #24. |

## 2. What 'Acuity-like' should feel like

### A. Staff operator daily workflow — build NOW

**Desired:** Open Digital Ranch → see a fully populated daily/weekly planner → tap empty 10:00 AM cell → compact creation panel opens with **10:00 AM already selected**, not 'check the date and resources' → choose 30/60/120-minute service, rider, instructor, horse, Main Arena → see auto-calculated end and actual occupied/prep/cleanup interval → contextual availability/conflict check → review → save → appointment block appears → tap block for details, changes and history.

**Concrete UX rules**
1. A click on a grid slot **selects and displays** that start immediately. Missing rider/resource fields can be filled afterward without losing the selected slot. Revalidate automatically as they are filled. No duplicate 'Show 15-minute starts' action is required for the *same* chosen slot.
2. Visual booking detail drawer/bottom sheet should show lesson, participant(s), instructor, horses/rotations, arena, exact teaching interval, buffer interval, booking state, eligibility/clearance and specific blocked reason. Keep normal operations in one compact panel; Advanced rotation only when needed.
3. Top calendar controls: Today, previous/next, Day/Week, optional Month, resource/instructor lane filter, a small date navigator, visible timezone, refresh. A genuine empty day still shows an understandable time grid. On phone use one-day readable lanes rather than squeezing seven columns.
4. Required resource unavailability should have a direct 'Manage [arena/instructor] availability' link for entitled staff. Status colors must pair with labels/icons. Bright red **BUFFER OVERRIDE** is reserved for a genuinely authorized exception, not a normal sibling block.
5. A green candidate start is **not a confirmed available booking**. Recompute server-side before commit for resource conflicts, other appointments, horse rest/welfare, rider clearance, DST and policy revision. No drag-to-save or implicit approval.
6. Preserve unsaved drafts; handle unknown commit results without duplicate bookings. Bookings, client records and other modules share canonical IDs and permission-filtered read models.

### B. Resource availability — NEXT calendar increment

- Set recurring day-of-week availability on Main Arena and individual instructor calendars, **inheriting facility hours** by default, with optional custom split sessions.
- Click or select a 15-minute grid interval → Block time / Close entire day / Reopen / Repeat weekly / optional end date, with accessible tap/keyboard equivalents.
- Show affected existing appointments before closing an occupied resource. Never silently cancel or bypass an active conflict.
- Distinguish recurring base hours, dated exceptions, booked appointments and booking padding. Respect saved long absolute intervals on the existing sandbox: no silent conversion to a new recurring rule.
- Link the visual availability editor directly from the filtered resource lane.

### C. Client self-service — FUTURE

An eventual client-visible scheduler can follow Acuity's service-first wizard:
**Service (and eligible instructor if choice offered) → available dates/time → rider/guardian profile and any required intake → review → pay approved fee/deposit or apply package credit → booking confirmation**.

Client views should be simpler and expose *only bookable, permitted choices*, never private horse workload/welfare, staff recovery, other families' schedules or internal exception reasons. Do not publish the client scheduler in current protected pilot.

## 3. Why Corner Stone Farm will differ from Acuity

- **Family visits:** Two siblings each receive 30 minutes back-to-back, one 15-minute arrival and one 15-minute departure buffer; the group retains two independent lesson records. A 120+120+60 family visit can present 5 teaching hours and only external buffers when genuinely contiguous. Family blocks must not falsely conflict with their own internal transitions.
- **Horse safety:** A two-hour *teaching* session is not two hours of continuous work by one horse. Ride turns/horse rotations and required recovery remain server-authoritative even if a manager waives a human prep buffer.
- **Protected time and burnout:** Sunday client closure and staff-owned time off remain deliberate policy choices; future Recovery Guard is a separate opt-in development path, never inferred by the current scheduler.
- **Specialized program types:** Off-site coaching and travel, Academy, youth clinics, training horses and haul-in riders have different resources and requirements. Keep all configured rather than hard-coding fictitious schedules/prices.
- **Data correction:** Horse classification correction, safe delete of unused erroneous records and archive/restore of used profiles. These are basic current pilot needs absent in Acuity's generic appointment service.
- **Sensitive permissions:** Named instructor resources don't automatically imply account logins. Real Farm Owner/Head Trainer rights depend on separately granted permissions.
- **Billing readiness:** Boarders and training clients need recurring invoicing and outstanding balances, not merely a one-time service charge.

## 4. Backend: public contract vs our own system design

### Publicly documented Acuity contract (observable; not proprietary internals)

The public API supports querying **/availability/dates** for a service/month and **/availability/times** for service/date, then creating an appointment. Webhooks announce scheduling/rescheduling/cancellation and related changes. Acuity documentation also describes resources, weekly availability, blocked time and payments. This establishes a useful *product-level* workflow but **does not disclose Acuity's database schema, backend framework, job orchestration or authorization design**.

### Proposed Digital Ranch authoritative flow

```text
Facility policy + weekly/exception resource hours
    + instructor/arena/horse suitability, travel, welfare, booking buffers
       ↓
15-minute candidate slot/read model (with explanatory reasons)
       ↓
Staff grid / future client-safe available dates and times
       ↓
Explicit reviewed booking command (MFA, role, revision, idempotency)
       ↓
Transactional conflict/welfare recheck + committed appointment/ride allocations
       ↓
Committed event + audit
    ↙                      ↘
Staff dashboard       Notification outbox (sink first → authorized wp_mail)
    ↓
Future invoice/charge linkage → provider-neutral payment gateway (Issue #24)
```

**Financial dependency NOW, no payment code now:** Stable IDs for booking, group visit, rider/guardian account, horse, service type and price policy. Separate invoice/charge/credit/payment status from booking status. Service definitions should be extensible with optional price/deposit/package eligibility and a policy for confirmed vs held pending payment. Never create a confirmed reservation based solely on an unverified client-side 'payment succeeded' response. Future hosted checkout/tokenization, webhook signature validation, reconciliation and refund ledger belong under Issue #24.

## 5. Phase plan for Codex

**P0 — current pilot:** Finish the visual-grid click→actual selected date/time integration fix, then conduct one real protected fictional appointment with the owner. Preserve v0.4.4 calendar and v0.4.3 server booking behavior.

**P1 — next:** Recurring arena/resource hours and visual closure calendar as an incremental change, with controlled preservation of existing records and qualified recovery.

**P1 — in parallel as capacity allows:** Family-group booking and smart 15-minute outer buffers; explicit role-controlled buffer exceptions; horse classification/record correction; dashboard weekly preview and post-commit notification banner. Avoid building unrelated new modules.

**Future, not active:** Full role portal, client/boarder self-service, online checkout and full billing/invoicing, external mobile push, independent SMS/IMAP/SMTP systems.

**Verification:** No UI-only bypass of server validation; exact 15-minute aligned clicks, DST fold/gap, phone/tablet/desktop, staff permissions, room/arena conflicts, horse rest, recurring exception closures, family group partial-save idempotency, notification dedupe, click-through to details. Run changed-input tests, not unrelated passed suites. No production or real client changes.

## 6. Official public sources reviewed (November 6, 2026)

- Original user references: https://acuityscheduling.com/features/online-booking and https://acuityscheduling.com/features/how-acuity-works
- Calendar day/week/month and appointment details: https://help.acuityscheduling.com/hc/en-us/articles/16676934756109-Managing-your-schedule
- Staff booking and admin appointment actions: https://help.acuityscheduling.com/hc/en-us/articles/16676870828685-Booking-appointments-for-clients
- Client booking journey: https://help.acuityscheduling.com/hc/en-us/articles/16676869573645-How-clients-book-appointments
- Repeating availability and individual-date exceptions: https://help.acuityscheduling.com/hc/en-us/articles/16676880363277-Setting-repeating-hours-in-Acuity-Scheduling
- Add/edit/remove time blocks: https://help.acuityscheduling.com/hc/en-us/articles/16676868579853-Blocking-off-unavailable-time
- Resource limits: https://help.acuityscheduling.com/hc/en-us/articles/16676949567757-Use-resources-to-limit-bookings
- Appointment padding: https://help.acuityscheduling.com/hc/en-us/articles/16676926857101-Adding-padding-between-appointments
- Webhook event behavior: https://developers.acuityscheduling.com/docs/webhooks
- API date/time availability: https://developers.acuityscheduling.com/reference/get-availability-dates and https://developers.acuityscheduling.com/reference/get-availability-times
- Admin mobile operation: https://help.acuityscheduling.com/hc/en-us/articles/16676868721165-Using-the-Acuity-Scheduling-Admin-mobile-app
- Settings permissions: https://help.acuityscheduling.com/hc/en-us/articles/47601371795085-Understand-Acuity-access-levels

**Next Codex response:** a functioning code increment, a concise changelog/test record, protected sandbox deployment status and a direct owner click path. No another general architecture plan. Public research is a benchmark; the application remains a Corner Stone Farm original.
