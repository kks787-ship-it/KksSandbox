# 12 — Digital Ranch: full system map and capability ledger

**Issue #14 local update:** scheduling0.4.0 + csf-mfa0.2.0 implement separately enabled mandatory
administration MFA and a finite, independently authenticated browser ownership ceremony.
The official Two-Factor0.17.0 ZIP/all21 distribution hashes are now locally verified. Schema3
and15tables are preserved. [Current security validation](test-results/scheduling-security.md)
supersedes earlier local design blockers below; historical pins/reports remain intact.
**GateB still held:** actual identities, provider choice/files, enforcement/recovery custody,
exact release/schema, ownership approval, staff grants/policy and synthetic pilot each need
separate authorization and protected acceptance. No calendar/MFA deployment or actual accounts
were changed; do not mark Beth/Kris acceptance, operational readiness or real scheduling complete.

**Snapshot:** November 1, 2026 (America/Los_Angeles). **Scope:** Corner Stone Farm Digital Ranch, CSF-OS and prospective barn-management product. This is a **capability inventory and conceptual product map**, not a replacement for the canonical [delivery roadmap](02-roadmap.md), [architecture](01-architecture.md), [owner decisions](03-owner-confirmation-register.md) or [security rules](SECURITY.md). Entries must be updated as real code, installations or owner approvals occur. Do not treat a proposed feature or issue assignment as implemented software.

**Evidence reviewed:** GitHub branch `codex/takeover-audit` at [a105868](https://github.com/kks787-ship-it/KksSandbox/commit/a1058688544fed2c7103c59612b6f53a35d07118), Issues #1–#10, local [Sprint #8 foundation](test-results/scheduling-foundation.md) and [Sprint #9 calendar](test-results/scheduling-calendar.md) validation, plus a **November 1, 2026 read-only Gateway verification** of the actual protected sandbox. The public website baseline remains separately verified. **The current 0.2.1 source adds locally validated native staff forms; it is not installed, migrated or operational on the protected sandbox.** The prior read-only inspection reported zero scheduling tables, and this sprint made no server changes. Protected compatibility and Beth/Kris acceptance remain release gates.

## 1. Product mission and scope

**Digital Ranch should be the operating system behind a relationship-driven equestrian facility:** one coordinated place to discover services, intake a family or horse owner once, collect service-specific signed agreements, confirm safe appointments, allocate horses and people, perform and record lessons/training/care, bill correctly, educate riders, and support an emergency without exposing private records.

Corner Stone Farm is the first customer and validation site, with its boutique training, youth/mentor, Horse Academy, lessons, FEI foundations, boarding, camps, clinics, sales and leases. It must protect Beth's teaching/training time and Kris's operational workload.

**Potential later business:** a managed product for other lesson facilities, boarding barns, training operations and equestrian centers. Beth has noted horse QR identification, evacuation accountability and incident coordination as a potentially strong differentiator. Commercial differentiation and safety effectiveness are **hypotheses to validate**, not currently shipped claims.

**What Digital Ranch is not:** an automatic booking engine, a GPS tracker, a substitute for a veterinarian or emergency response authority, a public directory of client/horse medical information, an already operational emergency notification service, or a launched multi-facility SaaS.

## 2. End-to-end workflow we are building

1. **Discover** — Public website, brand, programs, photos, real team biographies, horse/event listings and educational articles.
2. **Connect** — Parent, adult rider, horse owner or prospective client contacts the farm. Beth or Kris responds; no Acuity and no automatic reservation.
3. **Enroll** — A single household record holds guardian and emergency contacts, related riders, horse ownership, service selections, and legally reviewed agreements/versioned signatures. Services cannot silently start with unsigned agreements.
4. **Plan** — Staff calendar manages appointments/holds, participants, instructors, school or client horses, arenas, haul-in/off-site routes, rest blocks, recurrence and regional travel. A confirmed appointment is not the same as being cleared to ride.
5. **Deliver** — Lessons, Academy, camps/clinics, schooling, training, boarding/care, leases/mentorship and showing/haul-in coaching generate staff tasks, attendance and horse-training/care notes.
6. **Account** — Completed billable work and recurring services feed invoices, collections, deposits, commissions, discounts and financial reporting after approved payment/fee policies.
7. **Grow** — Owner-approved progress reports, parent/horse-owner portals, homework, knowledge library, educational programs, campaigns, scholarship/outreach and reputation/retention metrics.
8. **Respond to emergencies** — Separate, resilient horse ID/QR and public emergency status; private horse-location/check-in/transport accountability; approved responder requests and opt-in notifications with verified delivery state. Manual fallback always available.
9. **Productize later** — Facility-level configuration, isolated customer records, reliable support, tested restore/export, legal/privacy review and external pilots. Do not build multi-tenant infrastructure before validation.

The arrows describe **future data flow**, not integrations that already exist.

## 3. Status legend

| Marker | Meaning |
|---|---|
| **DONE — sandbox** | Installed and independently verified on password-protected development site; not necessarily public/production-ready |
| **BUILT — inactive** | Actual source/plugin and local tests exist, but feature is disabled or not cleared for operations |
| **STRUCTURE ONLY** | Database/content-type/presentation capability exists, but no real records, enrollment or live workflow |
| **DESIGNED** | Requirements, architecture or test plan exist; working code not yet demonstrated |
| **ASSIGNED / IN PROGRESS** | Coding ticket is active or assigned; no commit/acceptance counted until independently verified |
| **NOT BUILT** | Proposed or prioritized work; no verified working implementation |
| **LAUNCH GATE** | Legal, owner approval, configuration, security, real-device, data or payment validation required |

## 4. Verified current sandbox baseline

| Item | Current evidence | Status |
|---|---|---|
| Infrastructure | Plesk VPS, protected WordPress 7.1.4 / PHP 8.3 / Kadence 1.5.2 development site, private GitHub branch and repeatable backup/restore procedures | DONE — sandbox |
| WordPress core website | 21 generated/publication pages (23 total page records; 22 published, including one non-generated page), home page, menus, section patterns, reusable design system, header and footer | DONE — sandbox |
| Protected browser acceptance | Real Kadence 21 pages × 3 viewports = **63/63 passing**, with zero reported automated geometry and accessibility failures after header/footer repair | DONE — sandbox; real devices/manual visual review still separate |
| Marketing photographs | Nine genuine historical images imported, **22/30 image placements** replaced across 11 pages; **8 remain** (Beth portrait on 3 pages, Kris portrait, sale horse and 3 facility photos) | PARTIAL — sandbox |
| Site content approval | **23 owner-to-confirm notes** remain embedded in saved pages | LAUNCH GATE |
| Original logo | Original-looking farm assets recovered from earlier website; official mark not yet applied in site header/footer | LAUNCH GATE |
| Contact channel | Acuity removed; **Contact Beth to Schedule** points to Contact, approved public phone/email configured; manual confirmation by Beth/Kris | DONE — sandbox |
| Public inquiry form | Secure optional form/receipt code included in csf-core; **form disabled** until real email delivery and anti-spam verified | BUILT — inactive |
| Knowledge Library | Custom post type, categories/topics, listing and display functionality | STRUCTURE ONLY; **0 published content records in current sandbox** |
| Horse sale/lease/farm listings | Horse custom post type, listing fields and visibility/price controls | STRUCTURE ONLY; **0 horse records in sandbox** |
| Camps and clinics catalog | Event custom post type and upcoming-event/listing presentation | STRUCTURE ONLY; **0 event records in sandbox** |
| Emergency IDs / banner | csf-emergency plugin version 0.1.0 installed; QR horse ID/public unlisted pages, farm status workflow, basic log/banner locally built | BUILT — inactive; **0 configured operational horse profiles** |
| Internal scheduling | Issue #4 architecture complete; csf-scheduling 0.2.1 foundation/calendar/staff forms implemented and tested locally; **no scheduling plugin or schema installed on sandbox** | BUILT — local-only calendar candidate; staff pilot pending |
| Production replacement | Existing old production website remains separate; protected new site not launched publicly | NOT DONE |

**November 1, 2026 recovery milestone — Gate A passed:** A fresh exact-target sandbox backup and an isolated **functional** WordPress/Kadence recovery passed: all 12 InnoDB tables and 4,479 file hashes matched, cloned administrator login/dashboard and Home/Lessons/Contact rendered. [Committed evidence](test-results/scheduling-pilot-gate-a.md). The recovered app ran on isolated PHP/MariaDB rather than full Plesk/Nginx/Apache; production-like reverse-proxy/Basic Auth/MFA and encrypted off-server backup custody remain unverified. Live sandbox files and DB were hash-checked unchanged. This was **not a real sandbox rollback** or approval to install scheduling.

## 5. Capability map — public-facing farm

| Capability | Built / demonstrated | Remaining work |
|---|---|---|
| Brand, public site and mobile design | DONE — sandbox; 21 generated pages and real Kadence automated acceptance | Resolve owner copy notes, official logo, 8 photos, manual device checks, social media URLs |
| Contact and scheduling requests | DONE — direct call/email, Contact CTA, Acuity canceled | Publish final service hours/prices/FAQ; verify every published CTA after content edits; no self-booking assumption |
| Lessons and Horse Academy marketing | DONE — existing program pages | Approve ages, tuition, season, enrollment requirements, safety and cancellation terms |
| Camps and clinics marketing | DONE — event templates and listing logic | Populate approved real dates, program capacities, camp registrations, consent/e-sign storage and payments |
| Horse training, FEI, sales and leasing marketing | DONE — pages and listing structures | Genuine current horse profiles/photos, service packages, pricing/sales rules, owner approval of horse details |
| Knowledge Library | DONE — article engine and rendering | Beth-reviewed horsemanship articles, content review/version workflow, curated audience pathways |
| Website launch mechanics | PARTIAL — development URL, SSL, responsive testing and protected access | Privacy/legal pages, SEO/social previews, old URL redirects, analytics consent decisions, public launch and rollback approval |
| Commercial ecommerce / seasonal orders | NOT BUILT in this release | Separate store/cart/payment, product options, fulfillment states for approved seasonal ventures (e.g. wreath preorders) |

## 6. Capability map — private barn operations

| Capability | Current state | Target and first dependency |
|---|---|---|
| **Staff scheduling foundation** | BUILT — locally tested through scheduling 0.2.1; [validation](test-results/scheduling-staff.md); no protected installation | Separate plugin, explicit migration, resource policies and **granular group/owner access model implemented locally in Issue #12; protected staff grants remain unauthorized** |
| **Farm Master Account and permission groups** | **BUILT — LOCAL ONLY** in scheduling 0.3.0/schema 3; [permissions validation](test-results/scheduling-permissions.md); not installed or operational | [Issue #12](https://github.com/kks787-ship-it/KksSandbox/issues/12): Kris's verified master dashboard, editable group presets, individual allow/deny checkboxes, effective permissions preview, audited instant revocation and last-master protection. **Supersedes blanket Beth/Kris grants in Gate B; no execution approval.** |
| **Usable day/week calendar** | BUILT — local-only Sprint [#9](https://github.com/kks787-ship-it/KksSandbox/issues/9) candidate; [tests/limits](test-results/scheduling-calendar.md); no real staff acceptance | Beth/Kris manual requested/hold/confirmed/canceled appointments; audited changes, robust horse/instructor/arena/participant conflicts, protected blocks |
| **Recurring lessons and Horse Academy roster** | DESIGNED, not coded | DST-safe recurrence, exceptions, resource rotations, participant/group cap, makeups only after policy approval |
| **Household CRM / one-time intake** | **NOT BUILT — Issue #15 planned** | One household, guardians, multiple riders and horses, separate owner/payer authority; mobile intake once and before arrival; [integrated blueprint](14-client-readiness-onboarding-payments.md) |
| **Digital agreements & e-signatures** | **NOT BUILT — Issue #15 planned**; historical PDFs only | Attorney-approved liability/hold harmless, instruction, boarding/training, lease/camp/sale agreements, exact adult/guardian/horse-owner signers, executed immutable PDFs, independent consent and readiness checks |
| **New-rider check-in and participation clearance** | BUILT — local manual evidence flags; onboarding/signatures NOT BUILT | Separate calendar confirmation from signed release/guardian/emergency details; staff-visible not-cleared status |
| **Lesson delivery** | NOT BUILT | Attendance, instructor notes, school horse assignments, rider goals, learning plans and makeup rules |
| **School horses / resource welfare** | BUILT — local suitability/workload/restriction checks; care workflows NOT BUILT | Workload, suitability, rest, injury/unavailable periods, tack fit and staff-confirmed horse care blocks |
| **Boarding** | NOT BUILT | Board plans, paddock/stall allocations, care tasks, feed instructions, extras, contracts and 30-day notice workflow |
| **Daily horse care and provider scheduling** | NOT BUILT | Feed/medication instructions with appropriate access, farrier/vet/saddle fitter calendars, care history and supplier/team contacts |
| **Training and FEI horse development** | NOT BUILT | Enrollment cap, training journals, progressive goals, videos, owner updates, horse welfare and sale preparation |
| **Horse sales / consignments / leases** | Marketing structures ONLY | Horse listing workflow, proof of owner authorization, evaluations/PPE disclaimers, sale agreements, deposits, commissions, lease eligibility |
| **Camps and clinics registration** | Marketing structures ONLY | Registrations, age/capacity, staff assignments, camp-specific signed permissions, emergency contacts, rosters and check-in |
| **Youth mentorship and working students** | NOT BUILT | Staff-approved work/lesson exchange, eligibility, mentorship hours, assignments, parental clearance and safeguards |
| **Nonprofit / scholarships / 4-H/Pony Club outreach** | NOT BUILT | Separate scholarship application/awards and impact accounting from fee-based farm operations; legally appropriate separation |
| **Billing and payments** | **NOT BUILT — Issue #16 planned** | Hosted pay-at-booking checkout, lesson credits/packages, board/training recurring invoices and optional consented auto-pay, deposits, ACH, auditable ledger, manual receipts, refunds, reconciliation, nonprofit separation; no provider selected |
| **Inventory / feed / expenses and reporting** | NOT BUILT | Feed and supplies stock, cost attribution, care consumption, maintenance, financial dashboards and cost/time reporting |
| **Client and horse-owner portals** | **NOT BUILT — Issues #15–17 planned** | Secure pre-arrival checklist, required signatures, invoices, receipts, approved bookings and horse services; household/guardian/payer per-record authorization |
| **Trainer, staff and volunteer portal** | NOT BUILT | Role-restricted tasks, lesson rosters, facility maintenance, training time, delegated scheduling and audit history |
| **Client notifications** | NOT BUILT | Email/push/SMS opt-in, verified provider delivery, client preferences, retries, no unverified “delivered” claims |
| **Educational AI / learning assistant** | NOT BUILT | Restrict public advice to approved published knowledge; any private assistant needs separate authorization controls |
| **Marketing and retention hub** | NOT BUILT | Lead source tracking, approved newsletters, campaigns, social posts, referral/retention analytics and consent handling |

## 7. Capability map — emergency and evacuation (distinct safety module)

**Existing code: csf-emergency 0.1.0, installed but inactive.** Locally developed: permanent QR horse IDs and print views, controlled public emergency info, farm status and banner, role/capability structure and basic audit. **Not a live emergency service.**

| Component | Status | Pending operational acceptance |
|---|---|---|
| Permanent horse ID URLs and QR print layout | BUILT — inactive | Owner-verified profile fields, reliable public phone, approved photos, actual printed tags, iOS/Android daylight/low-light scans |
| Public status and /emergency page/banner | BUILT — inactive | Fix atomic status/revision/audit persistence, real MariaDB concurrent submit tests, mobile/cache tests, owner drill |
| Incident horse accountability (where last seen; loaded/transported/arrived/missing/found) | NOT BUILT as full end-to-end tracker | Role-restricted incident ledger, owner-validated status transitions, time/actor attribution, multiple yards/destinations and reconciliation |
| Rescue/evacuation coordination dashboard | NOT BUILT | Responder requests, offers vs accepted capacity, loading roster, transport and shelter coordination, staff assignment and acknowledgement |
| Notifications (email/push/SMS) | NOT BUILT | Consent/opt-out, provider setup, durable outbox/retry/delivery receipts, safe deduplication and failures visible to operators |
| Emergency staff access and controls | PARTIAL source only | MFA, explicit coordinator user setup, privacy testing, controls for compromised devices/accounts |
| Offline/low-signal recovery | NOT IMPLEMENTED as a reliable offline platform | Test printed human-readable code and phone fallback, weak-cellular behavior; any offline app/sync requires independent development |
| GPS/live location | NOT BUILT and not implied by QR scans | Only if explicitly justified, consented, securely sourced and validated. A scanned QR or manually entered location is **not** continuous GPS tracking |
| Operational launch and market claims | NOT APPROVED | Tabletop and physical drills, availability planning, incident security and liability review, backup/restore and user-facing limitations |

**Core distinction:** QR identification, human-reported location/status and true GPS location are three different capabilities. Product marketing must not conflate them. The system cannot promise to rescue horses or replace local emergency agencies.

This whole module stays isolated from private client/payment data and from routine calendar work. Owner-supplied evacuation protocols, haul routes, barns and physical equipment are **real operational procedures outside the software**, not evidence the digital incident manager is working.

## 8. Architecture rules that apply to every future module

- **First implementation:** Corner Stone Farm pilot. Public site = csf-core + Kadence/Gutenberg. Scheduling, onboarding, emergency/incident response, notifications, billing and portals remain separately testable modules; no giant plugin.
- **Facility configuration from day one:** Sunday closure, Wednesday off-site, Monday Horse Academy at 2:30–5, max five concurrent full-training horses, protected Beth schooling and pricing are CSF-specific policies—not hard-coded global product laws. Other barns need their own business rules.
- **Authoritative identities:** Facility → staff/roles → household/riders/guardians → horse identities → services → appointment/resource allocations → signed document and service records → billable events. Emergency public QR profiles expose **only explicitly approved public fields**.
- **Permissions and minors:** staff-owned private records, adult/guardian-managed family access, separate photo/media consent checks, least-privilege access, audit, data minimization and retention. Camp enrollment's existing photo permission is a consent source, not proof every historical picture is cleared.
- **Correctness:** use transactional, concurrency-tested MariaDB mutations for resource reservations, emergency event/audit/outbox integrity and financial records. Fail closed on incomplete migrations/unknown policies. Never present a failed email/notification as delivered.
- **Operations:** private backups, tested restoration and recovery plans; staging and production distinct; deployment requires approval. No modification to active site merely because code or a draft is finished.
- **Product ownership:** current project code explicitly declares GPL-2.0-or-later licensing; legal review is needed before choosing a commercial license/distribution/hosting model. Brand ownership, contributor IP and customer data rights must be defined before resale.
- **Commercial tenancy:** no other barn may see another's clients, minors, horse history or finances. Choose managed single-tenant first vs SaaS after security, pricing and support research.

## 9. Execution plan by dependency (not calendar promises)

### Stage A — finish the public foundation (parallel with calendar coding)

- [x] Install/test 21-page protected website, direct Contact scheduling, approved phone/email, header/footer and 63/63 browser baseline.
- [x] Import first nine archival photos into 22/30 placements and verify all current images load.
- [ ] Replace eight remaining images and choose authentic official header/footer logo.
- [ ] Resolve 23 owner copy notes and actual lesson/camp/horse/facility information; verify permissions for identifiable images.
- [ ] Approve privacy/legal pages, SEO, accessibility on real devices, redirect plan and public production release.

### Stage B — build the first private tool (Codex focus NOW)

- [x] Architecture/requirements for staff calendar including welfare, conflict, group rotations, staff-confirmed appointments and DST.
- [x] **Sprint 1 Issue #8 local implementation:** csf-scheduling 0.1.0 foundation, explicit schema, private staff permissions and tests are in this commit; 87 integration checks per database plus migration concurrency/backup restore. [Evidence and limitations](test-results/scheduling-foundation.md). Not installed or approved for real scheduling.
- [x] **Sprint 2 Issue #9 local candidate:** staff day/week calendar, explicit confirmation/holds, conflicts, welfare and pre-ride flags, cancellation history. [Validation and pilot limits](test-results/scheduling-calendar.md). No protected install or Beth/Kris operational acceptance.
- [x] **Sprint #9.1 Issue #10 local implementation:** 0.2.1 native group/roster/suitability/availability/welfare fields, local-time choices, preview and synthetic acceptance packet. [Validation](test-results/scheduling-staff.md). No protected pilot or Beth/Kris acceptance yet.
- [x] **Gate A completed — Issue #11:** owner-approved fresh sandbox backup and isolated **functional** restore; 12/12 tables, 4,479/4,479 file hashes, cloned login/dashboard and representative public pages passed. [Recovery evidence](test-results/scheduling-pilot-gate-a.md), commit `0c5bb66`. The exact new backup's age must be rechecked before any future write. No scheduling install/migration was performed.
- [ ] **Gate B held — Issue #11:** the granular Farm Master/permissions UI is coded; **Issue #13's session-bound MFA adapter is also coded locally** (scheduling v0.3.1 / csf-mfa 0.1.0 / candidate Two-Factor 0.17.0). [Issue #14](https://github.com/kks787-ship-it/KksSandbox/issues/14) must resolve mandatory WordPress-admin MFA, safe one-use Master identity bootstrap and vendor ZIP-byte release validation before any account/master installation review. The actual owner/operator/Beth/Kris identities, MFA choice, fresh exact source hashes, pilot policy, new backup/real-stack compatibility and direct owner approvals remain pending. Standalone Wordfence Login Security is not the preferred maintained candidate; Gate A functional recovery did not validate Plesk/Basic Auth/off-server custody.
- [ ] Approve owner-specific actual capacities, resource roster, timezone and staffing identities before adding real appointments to the protected sandbox.
- [ ] Pilot Beth/Kris calendar with manual existing appointments; no Acuity import or public self-booking.

### Stage C — remove the paperwork and payment bottleneck (joined client-readiness workflow)

**Owner requirement, November 2026:** A new lesson family must complete onboarding,
the appropriate signed adult/guardian rider waivers and **applicable prepayment** before
arriving at the barn. A board/training horse owner must complete approved horse intake,
care/emergency authorizations, executed board/training contracts and initial payment
(or approved exception) **before trailering the horse to the property**. New [end-to-end
specification](14-client-readiness-onboarding-payments.md) links [#15](https://github.com/kks787-ship-it/KksSandbox/issues/15)
onboarding/e-sign, [#16](https://github.com/kks787-ship-it/KksSandbox/issues/16)
billing/payments and [#17](https://github.com/kks787-ship-it/KksSandbox/issues/17)
controlled paid booking. ALL THREE ARE PLANNED ONLY, NOT BUILT OR INSTALLED.

- [ ] One secure household/family profile, adults/guardians, multiple riders, horse
  owner/authorized agents, payer, emergency contacts; mobile save/resume and
  no duplicate rider/owner/billing questions.
- [ ] Legally reviewed executed/versioned liability/hold harmless, rider instruction,
  boarding/training, sales/lease, camp/clinic, volunteer and optional independent
  photo/media consent; signed by properly verified adults/guardians/owners.
- [ ] Signed documentary audit/countersignatures in protected storage and only
  applicable renewed/updated agreements; service cannot be represented as
  cleared by a false 'verified' Boolean, a submitted invitation or paid invoice.
- [ ] Pre-arrival 'Ready to Ride' and 'Ready for Horse Arrival' status aggregates
  **legal signatures + applicable finance + horse/rider safety**; clear actions for
  Beth/Kris and welfare-first emergencies. Proper document and payment evidence
  are separate sources of truth, linked by canonical IDs.
- [ ] Client/owner portal shows completed documents, missing tasks, scheduled
  lessons, invoices and receipts without exposing other households.
- [ ] Initial paid scheduling remains **Beth/Kris-confirmed**; client request,
  published availability and instant self-booking are distinct later owner
  decisions with strong anti-conflict and payment-state checks.

No payment-processor account, signed document, real rider, invoice or booking was
created by accepting this product design. Public site still says Contact Beth
to Schedule, inquiry remains disabled and emergency inactive.

### Stage D — connect daily barn work to financial operations

- [ ] Lesson attendance, progress and horse use/workload.
- [ ] Training/FEI progression, owner reports, care plans and horse-provider scheduling.
- [ ] Boarding/care/extras, leases, mentorship and camp capacity.
- [ ] **Issue #16:** Provider-hosted checkout linked to an approved reservation,
  recurring board/training billing, authorized card-on-file/ACH, invoice ledger,
  refunds/credits, manual cash/check postings and verified webhook reconciliation.
- [ ] **Issue #17:** Client request/hold/confirmed/paid stages without double booking or
  billing; smoke/fire/horse-welfare exceptions and signed 48-hour policy under
  owner-configured rules.
- [ ] Completed service events → audited invoices/recurring charges, deposits,
  financial records and analytics, with separate nonprofit entity.
- [ ] Client/owner/staff portals and communications based on proved workflows.

### Stage E — emergency operational hardening and advanced incident coordination

- [ ] Atomic InnoDB state/audit/outbox implementation with multi-worker tests.
- [ ] Verified emergency contacts, coordinator MFA, privacy checks, real QR tags and real-device scans.
- [ ] Farm/horse transport, arrival and found-horse accountability with audit/reconciliation.
- [ ] Responder offers/dispatch capacity, alerts with confirmed delivery states, tabletop drills and manual fallback.
- [ ] Owner safety go-live review. No activation earlier merely to demonstrate a feature.

### Stage F — commercial product discovery and optional expansion

- [ ] Interview 8–12 independent facilities and validate urgent needs, alternatives and willingness to pay.
- [ ] Pilot with 2–3 external design partners only after CSF workflows and security are proven.
- [ ] Choose single-tenant/managed vs multi-tenant architecture, branding/trademark, licensing and customer data contracts.
- [ ] Support playbooks, customer migration, billing, data export, insurance, service levels, security and privacy operations.
- [ ] Optional educational products/store and nonprofit/outreach capabilities only if they advance the core mission.

Stages B/C/E can have overlapping **design** work without merging their deployment approvals or treating unfinished modules as live.

## 10. Owner decisions still open (do not invent values)

- Exactly what riders and horses need for arrival/lesson/boarding and legally enforceable agreement templates; verify signer authority, minor/guardian requirements, optional photo consent, waiver/counsel review and signed document retention. Existing camp registration has a photo-consent clause, but its stored execution/coverage is not yet integrated into the new software.
- Processor choice for Digital Ranch (Stripe/Square/PayPal or alternative), existing merchant accounts, ACH/card fees, card-on-file and autobill consent, refund/48-hour cancellation terms, board deposits/prorations/notice and camp/nonprofit payment entity separation. No processor or payment policy is approved yet.
- Current ages, helmets, rider observation, cancellation/makeup rules, current pricing/package terms, Academy enrollment limits, service area/travel minimum, actual training terms, sales/lease commission and camp enrollment.
- Staff user IDs/permissions, resource capacity, horse welfare hours/rest, arena allocation, timezone and health privacy gates.
- Official original brand mark variants and accurate photography of Beth, Kris, sales horses and actual facilities, plus publication rights.
- Emergency phone(s), primary/secondary destinations, per-horse approved public information, working physical tags, consent to notifications and emergency coordinator signoff.
- Business/legal entity structure, scholarships/nonprofit data separation, privacy/retention policies and software ownership for eventual resale.
- What to **exclude from the initial product** to avoid trainer burnout and operations burden.

## 11. GitHub issue anchors and update convention

| Ticket | Concern | Snapshot disposition |
|---|---|---|
| [#1](https://github.com/kks787-ship-it/KksSandbox/issues/1) | Sitewide Kadence layout/header/footer | Fix applied; protected browser 63/63; issue open pending remaining owner visual review |
| [#2](https://github.com/kks787-ship-it/KksSandbox/issues/2) | Inquiry acknowledgment security | Closed; code fixed; optional form still disabled |
| [#3](https://github.com/kks787-ship-it/KksSandbox/issues/3) | Acuity removal/contact scheduling | Implemented on sandbox, pending issue closure/release reconciliation |
| [#4](https://github.com/kks787-ship-it/KksSandbox/issues/4) | Staff calendar architecture | Architecture complete; implementation tracked as #8 and #9 |
| [#5](https://github.com/kks787-ship-it/KksSandbox/issues/5) | Logo and authentic photos | First 22/30 placed; eight remain |
| [#6](https://github.com/kks787-ship-it/KksSandbox/issues/6) | Owner-review page copy/policies | 23 open notes; copy draft prepared |
| [#7](https://github.com/kks787-ship-it/KksSandbox/issues/7) | Commercial product discovery | Opportunity recorded; no commercial platform |
| [#8](https://github.com/kks787-ship-it/KksSandbox/issues/8) | Scheduling foundation | **Issue closed — completed local implementation and validation** at `4c9abf3`; not deployed |
| [#9](https://github.com/kks787-ship-it/KksSandbox/issues/9) | Working staff calendar | Local v0.2.0 at `a105868` and tests delivered; remains open for protected install/real staff acceptance |
| [#10](https://github.com/kks787-ship-it/KksSandbox/issues/10) | Staff usability and pilot readiness | **Local implementation delivered** — native staff forms and synthetic acceptance packet; protected pilot/human acceptance pending |
| [#11](https://github.com/kks787-ship-it/KksSandbox/issues/11) | Scheduling synthetic sandbox pilot | **Gate A passed** at `0c5bb66` (backup and isolated functional restore); **Gate B not authorized**, no plugin install, grants or real data |
| [#12](https://github.com/kks787-ship-it/KksSandbox/issues/12) | Master account + permission groups/checkboxes | **Closed — code completed locally**, v0.3.0/20 privileges at `c04a243` with [reported validation](test-results/scheduling-permissions.md); no protected install or owner acceptance |
| [#13](https://github.com/kks787-ship-it/KksSandbox/issues/13) | Maintained MFA provider / current-session integration | **Closed — local-only implementation delivered** at `2c08321`: separate csf-mfa 0.1.0 session adapter with scheduling 0.3.1; [Codex validation](test-results/scheduling-mfa.md); no installation or human acceptance |
| [#14](https://github.com/kks787-ship-it/KksSandbox/issues/14) | WordPress admin MFA enforcement + one-use Master bootstrap + official ZIP verification | **Open — next local-only security engineering gate.** Must be independently tested before any Gate B account, master or protected installation approval |
| [#15](https://github.com/kks787-ship-it/KksSandbox/issues/15) | Household/horse onboarding, electronic agreements and pre-arrival clearance | **PLANNED — NOT BUILT**, legally reviewed signatures and portal needs; see [joint blueprint](14-client-readiness-onboarding-payments.md) |
| [#16](https://github.com/kks787-ship-it/KksSandbox/issues/16) | Billing/processor checkout, board/training auto-pay and ledger | **PLANNED — NOT BUILT**, payment provider and real merchant authorization unselected |
| [#17](https://github.com/kks787-ship-it/KksSandbox/issues/17) | Controlled client lesson booking and pay-at-scheduling | **PLANNED — NOT BUILT**, no public booking until separate approval; no Acuity |

**Update this ledger from evidence, not assumptions.** Mark a task DONE only with commit/test references and, if relevant, installation/operational-acceptance evidence. If code is finished but not deployed, mark it BUILT — inactive or local-only. If active in a sandbox but unapproved for real emergency/financial use, do not mark it operational. Preserve historical sources; later evidence supersedes older paragraphs elsewhere in the repository.

Sprint #9.1 follow-up: the local calendar candidate is now 0.2.1, with no raw structured editor
required for staff tasks. Schema stays v2; owner-approved policy, staff identities/MFA, actual
protected-stack testing and Beth/Kris acceptance remain outstanding. See [current validation](test-results/scheduling-staff.md).
No website baseline, inquiry state, emergency activation or production status was changed.


Issue #12 follow-up: local scheduling 0.3.0/schema 3 now provides Team & Permissions, editable
employee/custom groups, individual grant/deny/inherit controls, audited previews, immediate
revocation and protected master recovery. Validation: 732 assertions across MariaDB/MySQL,
59 calendar + 39 permissions browser checks, 84 PHP files lint/standards. See the new candidate
release report/checksum inventory. The owner-required tracker/architecture history is preserved.
No actual owner or staff identity is assigned; MFA/provider adapter, new source/schema approval,
protected installation and Beth/Kris acceptance remain held. No server, inquiry, emergency or
production changes occurred. This does not establish operational or commercial-product acceptance.

Issue #13 follow-up: the current v0.3.0 permission engine intentionally defaults protected access to denied until verified independent MFA session evidence exists. A Gate B review must not confuse enrolled TOTP with verified current session or assume the previously proposed standalone Wordfence Login Security provider is supported. See [MFA integration task](https://github.com/kks787-ship-it/KksSandbox/issues/13) and [Gate B corrections](pilot/gate-b-owner-review.md). No protected account/server changes are authorized.

## Issue #13 local security candidate — no protected installation

A separate csf-mfa0.1.0 adapter now integrates pinned Two-Factor0.17.0 successful challenges with
current-session Digital Ranch authorization. Scheduling0.3.1 keeps schema3/15tables. Provider
selection remains owner review; [security evidence and limitations](test-results/scheduling-mfa.md)
distinguish the tested source build from the unverified official ZIP bytes. No real staff identity,
MFA enrollment, master, grant or server state changed. All prior tracking updates are preserved.

GateB remains held for mandatory WordPress-admin enforcement, a safe master identity ceremony
(the old CLI gate intentionally cannot pass web-session MFA), exact artifact/security release
review, protected-stack acceptance and separately authorized accounts/policies/pilot. Beth/Kris
hands-on acceptance and real operational scheduling are not claimed by local tests.

## Security release follow-up — Issue #13 closed, Issue #14 open (November 1, 2026)

Codex's [recorded local tests](test-results/scheduling-mfa.md) cover 732 scheduling assertions, 16 MFA prerequisite checks, 198 browser checks and 86 custom PHP files across the stated disposable WordPress/MariaDB/MySQL fixtures. Source [2c08321](https://github.com/kks787-ship-it/KksSandbox/commit/2c08321d98566bc50ccb6d62712abf798cc16d0d) adds a separate challenge-backed `csf-mfa` adapter, and documentation head `69e51da` preserves the source. The *official vendor release metadata* for WordPress Two-Factor 0.17.0 lists a 91,837-byte ZIP and SHA-256 `59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`; the **ZIP itself has not been independently downloaded/hashed**. Source-built fixtures are distinct.

[Issue #13](https://github.com/kks787-ship-it/KksSandbox/issues/13) is closed as **local development complete only**. [Issue #14](https://github.com/kks787-ship-it/KksSandbox/issues/14) is the next local build: safe one-use verified Master bootstrap, mandatory WordPress-admin MFA/session enforcement and official vendor artifact proof. None of the provider, adapter, scheduling plugin, schema, owners/staff accounts, MFA enrollment or pilot fixtures has been installed or approved on the protected sandbox. **Gate B [#11](https://github.com/kks787-ship-it/KksSandbox/issues/11) remains held;** production, inquiry and emergency remain unchanged. Historical Gate A isolated restoration remains evidence, not an authorization for new writes.

## Integrated client readiness — owner requirement (November 2026)

The full requirement is documented in [docs/14-client-readiness-onboarding-payments.md](14-client-readiness-onboarding-payments.md). Lessons: **family completes necessary releases + payment or approved credit before saddle time**. Boarding/training: **horse-owner completes intake, care/emergency authorization, signed contracts and initial financial obligation before arrival**, with documented welfare-first exceptions. Every event is checked against the appropriate authoritative source; a signed waiver cannot be fabricated from a rider evidence flag and a paid transaction cannot fabricate a confirmed lesson. One household/horse/contract/payer relationship avoids repeat forms. This is **future design, NOT delivered code or live payment/signed contracts**, and does not change current staff scheduling source, protected Gate B, public Contact CTA, inquiry, emergency or production.
