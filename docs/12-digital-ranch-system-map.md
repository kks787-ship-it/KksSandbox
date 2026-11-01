# 12 — Digital Ranch: full system map and capability ledger

**Snapshot:** November 1, 2026 (America/Los_Angeles). **Scope:** Corner Stone Farm Digital Ranch, CSF-OS and prospective barn-management product. This is a **capability inventory and conceptual product map**, not a replacement for the canonical [delivery roadmap](02-roadmap.md), [architecture](01-architecture.md), [owner decisions](03-owner-confirmation-register.md) or [security rules](SECURITY.md). Entries must be updated as real code, installations or owner approvals occur. Do not treat a proposed feature or issue assignment as implemented software.

**Evidence reviewed:** GitHub branch codex/takeover-audit at e78e2d5768d0281681692558e5e7baa65c1ef89e, Issues #1–#9, and a read-only WP-CLI inspection of the actual protected sandbox on November 1. No server changes were made during this inventory. The website baseline below retains that inspection. The current commit now adds the **local-only** Sprint 1 foundation and its [recorded tests](test-results/scheduling-foundation.md); it is not installed or operational on the sandbox.

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
| Internal scheduling | Issue #4 architecture complete; csf-scheduling 0.1.0 foundation implemented/tested locally; #9 calendar follows; **no scheduling plugin or schema installed on sandbox** | BUILT — local-only foundation; calendar NOT BUILT |
| Production replacement | Existing old production website remains separate; protected new site not launched publicly | NOT DONE |

The current backup bundle includes the post-contact/pre-photo state and the later protected-sandbox photo update. Backups are private; full real-life operational recovery must still be validated beyond the prior structural restoration.

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
| **Staff scheduling foundation** | BUILT — local-only 0.1.0 foundation in this commit; [validation](test-results/scheduling-foundation.md) on disposable MariaDB/MySQL; no protected install | Separate csf-scheduling plugin; strict install/migration; InnoDB tables; facility policies; private staff permissions |
| **Usable day/week calendar** | NOT BUILT; Sprint [#9](https://github.com/kks787-ship-it/KksSandbox/issues/9) specified | Beth/Kris manual requested/hold/confirmed/canceled appointments; audited changes, robust horse/instructor/arena/participant conflicts, protected blocks |
| **Recurring lessons and Horse Academy roster** | DESIGNED, not coded | DST-safe recurrence, exceptions, resource rotations, participant/group cap, makeups only after policy approval |
| **Household CRM / one-time intake** | NOT BUILT; workflow requirements documented from experience with multi-rider families | One household, parents/guardians, multiple riders, related horse owners/horses, emergency contacts; avoid duplicate entry |
| **Digital agreements & e-signatures** | NOT BUILT; prior paper/PDF forms exist outside the software | Review state-specific agreements, version them, capture who signed and when, securely store executed documents and gate service start |
| **New-rider check-in and participation clearance** | DESIGNED in calendar architecture | Separate calendar confirmation from signed release/guardian/emergency details; staff-visible not-cleared status |
| **Lesson delivery** | NOT BUILT | Attendance, instructor notes, school horse assignments, rider goals, learning plans and makeup rules |
| **School horses / resource welfare** | DESIGNED as scheduling constraints | Workload, suitability, rest, injury/unavailable periods, tack fit and staff-confirmed horse care blocks |
| **Boarding** | NOT BUILT | Board plans, paddock/stall allocations, care tasks, feed instructions, extras, contracts and 30-day notice workflow |
| **Daily horse care and provider scheduling** | NOT BUILT | Feed/medication instructions with appropriate access, farrier/vet/saddle fitter calendars, care history and supplier/team contacts |
| **Training and FEI horse development** | NOT BUILT | Enrollment cap, training journals, progressive goals, videos, owner updates, horse welfare and sale preparation |
| **Horse sales / consignments / leases** | Marketing structures ONLY | Horse listing workflow, proof of owner authorization, evaluations/PPE disclaimers, sale agreements, deposits, commissions, lease eligibility |
| **Camps and clinics registration** | Marketing structures ONLY | Registrations, age/capacity, staff assignments, camp-specific signed permissions, emergency contacts, rosters and check-in |
| **Youth mentorship and working students** | NOT BUILT | Staff-approved work/lesson exchange, eligibility, mentorship hours, assignments, parental clearance and safeguards |
| **Nonprofit / scholarships / 4-H/Pony Club outreach** | NOT BUILT | Separate scholarship application/awards and impact accounting from fee-based farm operations; legally appropriate separation |
| **Billing and payments** | NOT BUILT | Service ledger, recurring board/lessons, invoices, payment reconciliation, deposits/credits/commissions; do not automate unapproved fees |
| **Inventory / feed / expenses and reporting** | NOT BUILT | Feed and supplies stock, cost attribution, care consumption, maintenance, financial dashboards and cost/time reporting |
| **Client and horse-owner portals** | NOT BUILT | Family-specific schedule, agreement status, invoices, horse reports and education; per-record authorization |
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
- [ ] **Sprint 2 Issue #9:** staff day/week calendar, explicit appointment confirmation, held resources, conflict/clearance checks, cancellation audit.
- [ ] Approve owner-specific actual capacities, resource roster, timezone and staffing identities before adding real appointments to the protected sandbox.
- [ ] Pilot Beth/Kris calendar with manual existing appointments; no Acuity import or public self-booking.

### Stage C — remove the paperwork bottleneck (highest-value connected workflow)

- [ ] Household/guardian/horse-owner intake once per family.
- [ ] Legally reviewed versioned rider, training, board, lease, camp and sale agreements with required signers.
- [ ] Staff audit, completion checks, no-service-before-required-signature gates and secure copy/receipt for clients.
- [ ] Link verified participation clearance to scheduling without duplicating sensitive records.

### Stage D — connect daily barn work to financial operations

- [ ] Lesson attendance, progress and horse use/workload.
- [ ] Training/FEI progression, owner reports, care plans and horse-provider scheduling.
- [ ] Boarding/care/extras, leases, mentorship and camp capacity.
- [ ] Completed service events → invoices/recurring charges, deposits, financial records and analytics.
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

- Exactly what riders and horses need for arrival/lesson/boarding and legally enforceable agreement templates. Existing camp registration has a photo-consent clause, but its stored execution/coverage is not yet integrated into the new software.
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
| [#8](https://github.com/kks787-ship-it/KksSandbox/issues/8) | Scheduling foundation | Local implementation and validation delivered in this commit; not deployed; see foundation report |
| [#9](https://github.com/kks787-ship-it/KksSandbox/issues/9) | Working staff calendar | Specified, follows #8 |

**Update this ledger from evidence, not assumptions.** Mark a task DONE only with commit/test references and, if relevant, installation/operational-acceptance evidence. If code is finished but not deployed, mark it BUILT — inactive or local-only. If active in a sandbox but unapproved for real emergency/financial use, do not mark it operational. Preserve historical sources; later evidence supersedes older paragraphs elsewhere in the repository.
