# Digital Ranch — Client Booking Front End and Administrator Publication Controls

**Date:** 2026-11-06  
**Status:** Owner-requested build specification. **Preview-first implementation** on the protected sandbox; NOT authorization to enable public self-booking, create real client records, dispatch real booking notices or accept payments.  
**Related:** Active calendar Issue #11, service catalog Section 1A of `calendar-ux-owner-approved-master-spec.md`, Acuity UX research, future billing Issue #24, intake/portal issues.

## Goal

Connect the Corner Stone Farm public website's "Book a Lesson" / program pages to the authoritative Digital Ranch scheduling system, **without exposing the staff calendar**. Farm Management must control **which exact service types are allowed for client self-booking** and whether clients can only request or can confirm a reservation. Use Acuity's service-first client scheduling experience as a benchmark, not its code, styles or undocumented internals.

**Existing baseline:** Scheduling 0.4.5 on protected sandbox with staff-only admin calendar, 15-minute slot choices, explicit confirmation, exact resource/horse/rider protections, MFA, transactional booking, and currently NO public route. Existing website CTA still directs clients to contact the farm. Do not silently flip that CTA or launch a public booking page.

## 1. Administrator control panel: Booking Publication

Add **Digital Ranch → Settings → Online Booking** with a prominent **GLOBAL status**:

- **Disabled** (default): No externally accessible booking route or action. Existing "Contact Beth to Schedule" remains active. No indexing or accidental public widget.
- **Private preview**: Authorized farm staff can see the full client-facing flow on the protected sandbox using synthetic records; public routes still deny scheduling and do not expose private data. Preview should be accessible by a clearly labeled staff-only link; no real messages.
- **Live**: A distinct future owner-approved release gate after staff testing, anti-spam, email, consent/intake, resource safety and data-protection acceptance. No implicit promotion from preview.

For **each stable service ID** in the existing lesson-type catalog, provide separate controls that are not inferred from its name:

| Setting | Expected choices / behavior |
| --- | --- |
| Enabled internally | Staff can use the existing authorized staff calendar regardless of online visibility when enabled |
| Client exposure / eligibility | **Staff only** (default); **existing authorized client/household only**; **invitation-only**; or **publicly listed** |
| Client transaction policy | **Request for approval** (default); **instant confirmation** only after explicit owner approval and complete safety/availability prerequisites |
| Client text | Public display name, client-safe description, category, optional approved image, instructions and order |
| Lesson rules | Duration, rider capacity, accepted horse category, required instructor/arena/location, and which assignments staff make privately |
| Availability policy | Allowed calendars/instructors, weekdays, published time range, min booking notice, max future horizon, max per-day/week per service/instructor, approved cancellation/reschedule policy |
| Eligibility/intake | Existing client login required or permitted guest request; rider/guardian identity, relevant signed consents and rider clearance checked before riding |
| Future billing hook | Stable service/appointment/group identifiers and optional payment/deposit/package metadata placeholder; **no charges until Issue #24 delivery and separate launch approval** |

**Owner-preferred preliminary exposure policy to review, not activate automatically:**

- Student lesson 30/60: candidates for public **request** scheduling once approved.
- Training-client lessons: existing **authorized training client only**; normal public visitors cannot claim eligibility.
- Working Student — Paid and Working Student — Unpaid: **staff only** by default (or linked authorized student portal once separately approved).
- School-break programs, camps, clinics, new client introductory programs: possible publicly listed requests, subject to separate capacity, youth consent and program approval.
- No default automatic confirmation just because a service is visible.

A restricted service being **unlisted is not the same as access-controlled**. Never rely only on a guessed/unlisted URL for Training Client or Working Student permissions. For invitation-only booking, verify a short-lived signed token with scope, expiration and revocation (and if required user authentication) against the server's current policy; do not leak unlisted service eligibility by enumeration.

## 2. Acuity-like client flow — Corner Stone Farm branding

Build one reusable front-end scheduler rendered with a Gutenberg/Kadence-compatible server-side block/shortcode, or approved equivalent, from a separately testable booking controller. Future placement:

- Dedicated **Book a Lesson** WordPress page, currently staff-only preview on the sandbox.
- Approved call-to-action buttons on Lessons, Programs, Holiday Gifts and other relevant pages eventually open this page with the eligible service **preselected by stable ID**.
- Same booking core is suitable for a future authenticated boarder/household portal; **do not duplicate booking data logic**.

**Client experience (mobile first):**
1. **Choose a program/service** (grouped by category, showing only exposed and eligible offerings). Show duration, short description, modality, and pricing only if explicitly approved; unknown price is not zero or "free".
2. **Choose date/time**, monthly calendar with available days and day slot buttons or compact next-available week; 15-minute start-grid where permitted, timezone visible. The client never sees all instructor calendars, other client names or private resource details. If instructor choice is offered, show only farm-approved public display names/availability, or "any available".
3. **Rider/guardian intake**: use an authenticated household record where available, or a bounded guest request form with minimum necessary fields; capture guardian authorization where applicable, not sensitive medical details in public HTML. Missing releases/clearance cannot be inferred or self-attested as staff-verified.
4. **Review** lesson type, intended start/end, cancellation terms, preparation instructions, pending/confirmed booking status; confirm user consent and submit an idempotent transaction request. If payment is required in future, route through separately authorized billing/hosted checkout instead of collecting card details in WordPress.
5. **Show an accurate result**: "Request received — awaiting farm confirmation" vs "Confirmed" according to the service's approved policy. Do not claim email sent or payment taken unless verified. Offer appointment reference and secure client follow-up route when implemented.

Use the farm's original branding, friendly mentorship-centered copy and clean accessible service cards. Avoid overwhelming beginner parents with internal horse/resource controls. No Acuity branding, screen-scraping, code or proprietary assets.

## 3. Security, scheduling and data isolation

- **ONE scheduling authority:** Public slots use a restricted **service-safe projection** of the same facility/resource/instructor/horse/time rules used by staff. Client responses expose only eligible dates/start times and generic unavailable states. Do not surface student names, minor contacts, horse medical/welfare flags, internal rest blocks, trainer calendars, staff audit, payment accounts or SQL identifiers.
- **No public staff-page access**: WordPress admin routes, internal AJAX and per-horse details remain MFA/role guarded. Build minimal independent public endpoints with explicit allowlist of fields and rate limiting.
- Server MUST re-evaluate before every commit: service online status, current client eligibility, guardian/intake state, stable service ID/price-policy revision, permitted instructor/resource, 15-minute slot, facility hours, closures, travel, buffers, horse workload/rest, concurrent holds and horse/rider suitability. A green public day/time is a candidate, not a guarantee.
- By default a guest request **does not reserve a slot** unless there is a separately approved bounded hold policy; a requested appointment must not appear falsely confirmed. If instant confirmation is ever enabled, confirm only when all required legal/operational prerequisites and effective resource assignments can be satisfied. No anonymous override of prep buffers or true welfare/availability rules.
- Rate limit by IP/account where appropriate, use short-lived CSRF/anti-automation protections, validate all requests on server, prevent enumeration and duplicate requests (idempotency), avoid excessive public query/database work. Follow applicable privacy/guardian handling for minors.
- Keep recipient notifications in a durable **sink-only outbox** during build. Existing `wp_mail()` security test approval is NOT booking email authorization; later real staff/client messages require explicit recipient and send scope. Do not expose personal data in external notices.
- Global Disable and per-service visibility **must actually be enforced in handlers**, not just UI/hide/show switches. Revoking service exposure immediately invalidates new booking attempts, invitations and direct links while preserving historical appointments.
- Full backups and current recoverability must cover any new tables, WordPress options and booking/request states. Do not modify 27-table restore contracts by assumption; add a reviewed schema-aware migration and isolated restore qualification only if necessary.

## 4. Example service policy decisions to demonstrate in the UI

| Type | Staff internal | General visitor | Existing eligible client | Initial outcome |
| --- | --- | --- | --- | --- |
| Student Lesson — 30 min | Enabled after owner config | Owner-controlled public listing | Same rule or household | Request for approval |
| Student Lesson — 60 min | Enabled after owner config | Owner-controlled public listing | Same rule or household | Request for approval |
| Working Student — Unpaid | Enabled after owner config | Hidden and denied | Only eligible authenticated student, if enabled later | Staff-only default |
| Working Student — Paid | Enabled after owner config | Hidden and denied | Only eligible authenticated student, if enabled later | Staff-only default |
| Training Client Lesson | Enabled after owner config | Hidden and denied | Authenticated, linked training client only if enabled | Request or staff only |
| Other services | Owner-configurable | Denied until published | As approved | Per service |

These are **proposed starting defaults**, not a decision that any specific service is public now. Paid/unpaid working student classification is NOT a payment policy.

## 5. Deployment and priorities

**Stage 1 — Build with no public booking activation**:
- Back-end Online Booking settings and per-service publication/eligibility/request vs confirmed controls.
- Staff-only preview of branded frontend service → date/time → minimal intake → review journey with fictional data.
- Booking status/visibility handler tests and public denial checks. Replace no existing website CTA automatically. No real submission, outbound mail or payments.

**Stage 2 — Connect site pages, still gated**:
- Integrate reusable Gutenberg/Kadence shortcode/block into a dedicated protected sandbox page, and preview potential Lessons/Programs CTA placement.
- Test mobile responsiveness, missing eligible services, disabled global flag, direct URL abuse, access denial and no leakage.
- Validate an owner-selected menu/CTA plan. Keep old `Contact Beth to Schedule` available until live launch is approved.

**Stage 3 — Separately approved public launch**:
- Explicit selections for which services may be publicly listed, request-only/instant behavior, booking notice/horizon/cancellation rules, household/guardian intake, abuse protection, mail/receipt notification consent and optional later billing.
- Real site privacy/legal/security checks, booking acceptance, cancellation/expiry/idempotence, client verification and fresh backup/rollback.
- Gradually enable live booking for **a small approved subset**. Public booking page and endpoints remain disabled until this separate approval.

**Do not block the active staff calendar**: recurring arena/instructor hours, grouped family buffers, horse correction, notification and dashboard work remain the priority in Issue #11. Build frontend preview in an isolated, safe increment or parallel workstream where it does not delay core calendar. Future paid checkout/boarder invoices are covered under Issue #24, not this implementation.

## 6. Acceptance matrix

1. Online booking **Disabled**: no client availability data or write endpoint exposed even through shortcode or deep link; staff calendar unaffected.
2. Private preview renders service categories/description and viable sample dates, but anonymous submission is denied.
3. Public-listed test service appears; staff-only and training-client services are absent to unauthorized visitors. Direct manual service-ID requests for restricted types return generic not available without leaking existence.
4. Authenticated eligible clients can see only services to which they are linked, if such client authorization exists; no elevation by editing query parameters or using an invitation URL.
5. Admin toggles per-service visibility and request/confirm policy; changes instantly affect subsequent availability queries/commits; historical appointments and stable IDs remain intact.
6. Start and duration use configured 15-minute scheduling; no race double booking, no horse/instructor/arena/welfare-bypass; public never sees individual staff/horse safety reason.
7. Guest/minor intake respects required guardian verification and policy; no false rider clearance or automated confirmed lesson when prerequisites are absent.
8. Requests/holds/confirmed states and accurate UI/email claims; cancellations and policies follow authorized rules; no real invoice/charge until a separate billing deployment.
9. Site block and CTA are responsive/mobile/keyboard/screen-reader usable; branded like Corner Stone Farm but original implementation.
10. Authorized test suite on existing PHP 8.3/MariaDB and PHP 8.1/MySQL, with recovery/rate limit/role checks; sandbox no-production drift and verified rollback.

## 7. Official Acuity references (public UX only)

- https://acuityscheduling.com/features/online-booking
- https://acuityscheduling.com/features/how-acuity-works
- https://help.acuityscheduling.com/hc/en-us/articles/16676922487949-Add-or-edit-services-on-your-scheduling-page
- https://help.acuityscheduling.com/hc/en-us/articles/30597594204301-Public-and-private-appointment-types-and-classes
- https://help.acuityscheduling.com/hc/en-us/articles/16676933640461-Find-direct-links-to-specific-scheduling-pages
- https://help.acuityscheduling.com/hc/en-us/articles/27141282369037-Limit-when-clients-can-book-edit-or-cancel-their-appointments
- https://help.acuityscheduling.com/hc/en-us/articles/16676921819917-Share-your-scheduling-page-with-clients
- https://help.acuityscheduling.com/hc/en-us/articles/16676894249101-Elements-of-a-scheduler

**Deliverable expected from Codex:** A working backend control panel and staff-only sandbox front-end booking preview, tests proving all suppressed service types truly stay inaccessible, and a precise owner path to review before any public activation. No broad proposal-only handoff.
