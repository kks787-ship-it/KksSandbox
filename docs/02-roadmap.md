# 02 — Delivery Roadmap

> **Current full-platform snapshot (November 1, 2026):** See [Digital Ranch system map and completion ledger](12-digital-ranch-system-map.md). It reconciles current installed sandbox, Codex scheduling work, photo/content status, emergency operational gates, all future modules and commercialization. Earlier milestones below are historical and some pending statements have since been superseded.

Phases are sequential; each ends with owner review on the development site. Nothing goes to
the production domain until the owners approve the replacement.

## Phase 0 — Foundations *(this repository, in progress)*

- [x] Git repository and coding standards (WPCS, PHPCompatibility)
- [x] Read-only environment inspection script
- [x] Backup and dry-run-first deployment scripts for the dev site
- [x] Gateway inspected the actual sandbox; owner supplied findings (current report: `test-results/sandbox-preflight.md`)
- [x] October 31, 2026: original wp-config backed up; development/editing/debug-display constants applied and verified (configuration-only recovery evidence)
- [x] Full sandbox backup/archive/checksum verification — Gateway, October 31; private csf-sandbox-backup-20261031 directory
- [x] Isolated structural restore: 12 InnoDB tables/count samples and 4,336 regular files, zero mismatches
- [ ] Functional WordPress login/page-rendering test on the isolated restoration (not part of the completed structural verification)
- [x] Private source pinned d360dbf, verified against 52401dc; exact-target Plesk preflight/deployment dry run passed
- [x] Fresh installation gate passed; backup csf-dev-20261031-184634-36879, then owner-approved files-only install and core-only activation
- [x] csf-core 0.2.0 installed/active and public site setup completed through Gateway; emergency 0.1.0 installed/inactive (job evidence in doc 05)
- [ ] Current target-bound backup/evidence gate for any future reviewed code update
- [ ] Owners complete the confirmation register (`03-owner-confirmation-register.md`)
- [ ] Owners supply original logo source files and authentic photography (still outstanding)

## Phase 1 — Emergency horse identification *(code complete for MVP; not operational)*

- [x] `csf-emergency` plugin: horse profiles, permanent `/h/{code}` URLs, printable QR tags,
      print-ready profile, privacy controls (noindex, sitemap/search exclusion)
- [x] Farm status workflow with review/confirm, duplicate protection, audit log
- [x] Sitewide banner and `/emergency` landing page
- [ ] Install & configure Two Factor for all coordinators
- [ ] Enter and verify emergency contact numbers
- [ ] Owners approve public fields for each horse
- [ ] Physical tag material chosen; print tests; scan tests on iPhone + Android, daylight and low light
- [ ] Tabletop drill: change status, verify banner, revert
- [ ] Owner sign-off that it is operational (see `04-emergency-plugin.md`)

## Phase 2 — Public website *(csf-core 0.3.0 active in protected sandbox; site acceptance pending)*

- [x] Brand design system in `csf-core`: palette + self-hosted Playfair Display/Lato as editor presets, block styles, accessible colour pairings
- [x] 14 section patterns and 21 page layouts (core blocks only; editable in Gutenberg)
- [x] All pages from the sitemap, hierarchy, primary & footer menus, synced footer, via idempotent `wp csf setup` / Tools screen
- [x] Knowledge Library: summary, topics, audiences, reading time, review date, updated date, related articles, `/library/`
- [x] Horses (farm / sale / lease) and Camps & Clinics as structured content with listing blocks
- [x] Reusable booking CTA and farm contact-details block exist, with fixed **Contact Beth to Schedule → /contact/** rendering prepared locally in [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3). Do not use online-booking claims.
- [x] Emergency banner verified on the new pages (independent plugin unchanged)
- [x] Automated tests: accessibility (axe WCAG 2.2 AA), responsive, links, navigation, booking, block-editor validity, emergency, structured content, setup idempotency
- [x] Repeatable local environment (`scripts/local-dev.sh`)
- [x] Installed/active core; all 21 pages published, menus assigned, static Home, /%postname%/ and rewrite/Apache checks passed (Gateway)
- [ ] **Sitewide Kadence gutter — [Issue #1](https://github.com/kks787-ship-it/KksSandbox/issues/1):** the shared CSS fix was deployed in core 0.3.0; the owner visually approved the reviewed **mobile** layout. Tablet/desktop and full authenticated 21-page audit at 390/768/1280px remain pending. Preserve full-bleed backgrounds and owner content; do not close Issue #1 before sitewide acceptance.
- [ ] Verify on Kadence: Customizer palette, header, footer, page-layout settings
- [ ] Logo, authentic photographs, approved call/message contact channels, and remaining owner-content confirmations (doc 09). Acuity is canceled: **no verification or integration**; see [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3).
- [x] csf-core 0.3.0 deployed to protected sandbox with permission fixes, setup error reporting, shared gutter CSS and optional inquiry form **disabled by default**. Local security/browser tests passed; real mail delivery not tested.
- [x] [Issue #2](https://github.com/kks787-ship-it/KksSandbox/issues/2) resolved and included in sandbox core 0.3.0: signed short-lived one-use inquiry acknowledgment, with local forged-URL/replay/mail-failure regressions.
- [x] Reviewed core 0.3.0 update deployed **sandbox only** through Gateway with fresh backup, verified exact source and core-only dry run. Contact page content was preserved, inquiry form disabled, and emergency plugin inactive.
- [ ] Inquiry form stays disabled. Any future enablement requires approved mail provider, verified delivery to beth.siemon@cornerstonefarmomak.com, real MariaDB/anti-spam checks, and separate approval. It is **not** the interim scheduling channel.
- [ ] [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3): Remove canceled Acuity links, defaults and online-booking promises; replace all scheduling CTAs with **Contact Beth to Schedule → /contact/** using approved phone/text/email channels. Code, tests, and saved-page migration require separate review and sandbox update approval.
- [ ] Launch prep: SEO metadata, redirects from old URLs, privacy policy, production deployment plan (separate approval)

## Phase 3 — Notifications & evacuation assistance

- `csf-notify`: separate opt-in lists for *General farm alerts* and *Qualified evacuation
  helpers*; email first, then web push (service worker, VAPID), optional SMS provider
- Test mode, retries, delivery status where the provider reports it, opt-out, per-event
  idempotency on `csf_emergency_status_changed`
- `csf-assist`: assistance request record, responder form, dispatch dashboard,
  offered-vs-committed capacity counters, no automatic dispatch

## Phase 4 — Client readiness: onboarding, digital agreements and pre-arrival clearance (Issue #15)

**Owner requirement added November 2026; PLANNED ONLY, NOT BUILT.**
[One integrated blueprint](14-client-readiness-onboarding-payments.md) joins onboarding,
service-specific legally reviewed e-signatures, protected scheduling and payment status without
copying private records across modules.

- One verified **household** with multiple adults/guardians, minor/adult riders,
  emergency contacts, payer(s), horse owner(s)/authorized agents and 0+ horses; save/resume
  and no re-entering the same household intake on every lesson.
- Send only the required adult/minor liability/hold harmless, instruction, boarding,
  training, leasing, sales and camp agreements for selected services, with correct
  signer authority, opt-in media consent separate from mandatory documents, immutable
  approved template versions, complete signed PDFs, audit/counter-signature and expiry.
- **Pre-arrival gating**: 'Ready to Ride' and 'Ready for Horse Arrival' are computed from
  executed legal evidence + verified authority/guardian and approved safety/care
  prerequisites + applicable finance status, never inferred from a booked slot or a
  paid invoice alone. Missing paperwork remains 'Not Cleared'; unexpected horse-care
  emergencies have a documented welfare-first exception.
- Signing legal templates requires qualified Washington counsel review of RCW 1.80
  e-signatures and RCW 4.24.530/.540 equine liability, including minors and boarding
  terms; historical agreements/old prices/notice periods are not approved by default.
- The source of truth for contract execution lives in a distinct csf-onboarding module,
  linked through stable IDs to scheduling and payments. **Existing manual rider evidence
  checkboxes are not an electronic-signature system**.

## Phase 4B — Invoicing, processor checkout and auto-pay (Issue #16)

**PLANNED, NOT BUILT.** Implement a separate owner-approved csf-billing module for
hosted provider checkout, per-household/horse/service invoices, package balances,
lesson prepayment, deposits, recurring board/training invoices, optional consented
card/ACH auto-pay, receipts, statement/aged receivables, ledger and reconciliation.
Refunds, cash/check payments, charge disputes, partial payments and smoke/horse welfare
cancellation exceptions must retain separate auditable states. Payment processor
(Stripe/Square/PayPal candidate) is **not selected**; do not store card details or
initiate live charges. Separate nonprofit program financial identity/merchant records.

## Phase 5 — Client/horse-owner portal and controlled scheduling (Issue #17)

- Portal for each authorized adult household, horse owner, payer and guardian with
  relevant appointments, document/signature status, invoices, receipts, program credits,
  rider/horse readiness and staff review requests, with strict per-record authorization.
- Initial path: **Beth/Kris confirm appointments**, clients sign/pay using a secure
  completion/checkout link. Optional public client request/instant self-booking only
  after explicit owner approval of availability/safety, privacy, payment and security
  constraints; one canonical scheduling engine with resource collision prevention.
- Acuity canceled. **Public Contact Beth to Schedule must remain unchanged until a
  separate public booking launch is authorized.** Inquiry form remains disabled.
- Horse sales and leasing listings with authorized contract/owner proof and later
  service-specific inquiry/inventory workflows.

## Phase 6 — Growth

- Public AI educational assistant restricted to published Knowledge Library content
- E-commerce, reporting, marketing automation

## Current assignments — finish the existing site and begin internal scheduling

**Assignment A: website finishing and removal of canceled Acuity — [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3).** The user confirmed Acuity has been canceled. All public scheduling CTAs must say **Contact Beth to Schedule** and lead to the internal Contact page with verified/approved call/message options; no live availability, booking confirmations, or enabled inquiry form. The old Acuity default and saved-page claims must be removed by a reviewed, non-forced, editor-safe migration. **Issue #3 source changes are prepared locally; runtime validation and deployment remain pending.** Do not infer online booking from existing core buttons.

The shared gutter fix and optional disabled inquiry form were already installed in core 0.3.0 on the password-protected sandbox through Gateway. The user visually approved the reviewed mobile layout; Issue #1 still requires tablet/desktop and comprehensive authenticated 21-page acceptance. Kadence branding, footer block 27 placement, authentic assets, verified contact channels and owner notes remain. Keep the installed 21 pages intact; no rebuilding.

**Assignment C: internal Digital Ranch calendar architecture — [Issue #4](https://github.com/kks787-ship-it/KksSandbox/issues/4).** This is a separate, modular staff-first system. Confirmed appointments entered/approved by Beth or Kris are the source of truth; there will be **no Acuity synchronization/import**. Model requests versus tentative holds versus confirmed bookings; recurring lessons, horse and instructor/resource conflicts, Monday Horse Academy, Wednesday off-site groupings, Sunday client closure and protected trainer time. Design schema/workflows/tests before implementation; no public self-booking in the initial release. See [owner decision](DECISIONS.md#owner-scheduling-provider-decision--october-31-2026).

**Assignment B: harden emergency operations.** Keep emergency inactive. Atomic review/state/
audit/outbox consistency, permanent token reservation, coordinator security and real MariaDB
concurrency tests precede operational approval. The [proposed persistence fix](DECISIONS.md#proposed-emergency-concurrency-fix--design-before-operational-approval)
remains a design, not an implemented notification or evacuation system.

Onboarding, legally reviewed agreements, client payment/recurring billing, and portals are now explicitly designed as [Issue #15](https://github.com/kks787-ship-it/KksSandbox/issues/15), [Issue #16](https://github.com/kks787-ship-it/KksSandbox/issues/16), and [Issue #17](https://github.com/kks787-ship-it/KksSandbox/issues/17), with a [joined client-readiness blueprint](14-client-readiness-onboarding-payments.md). **They are not built or operational**, and the old calendar MVP deliberately excludes public booking and payment. Design can proceed in parallel with security Issue #14; protected installs and merchant/personal data require distinct approval. Do not rebuild the 21-page site or combine the management roadmap into a giant plugin.

Gateway preparation is complete: original hardened configuration and full backup at the private
csf-sandbox-backup-20261031 directory, structural 12-table/4,336-file restore, private d360dbf
source staging and initial preflight/dry run. Functional restored-site login/rendering was not
run. Preserve those records; no new production, emergency activation, setup, permalink or
code deployment authorization is implied by this roadmap update.

Local downloaded-theme evidence and remaining protected-site acceptance gates:
[test-results/kadence-layout.md](test-results/kadence-layout.md). The approved core-only 0.3.0
update has been deployed to the protected sandbox, but no saved Contact-page edit or
Acuity-removal update has been performed; those remain separate approval boundaries.


### Current local delivery for Issues #3 and #4

Scheduling contact source changes, source/guard test results and the non-forced content
migration plan are in [the current report](test-results/scheduling-contact.md). The original cloud blockers are superseded by private-fixture validation recorded there;
the event assertion now passes 19/19. Real-Kadence acceptance remains pending. Saved sandbox pages/menus have not been changed. [The staff scheduling
architecture](01-architecture.md#digital-ranch-staff-scheduling--issue-4-architecture-for-review)
is delivered for owner review; the MVP and its MariaDB acceptance matrix are unimplemented.


Issue #4's group rotations, client-horse resources, welfare/workload, rider-clearance and
cancellation refinements are now incorporated in doc 01. No calendar implementation begins
until unresolved business policies are approved. Public scheduling call/email values are now
owner-approved in doc 03 but not yet entered into sandbox settings; no SMS approval. Review
plugin files, all 21 candidate page updates and any menu/settings changes as separately scoped
operations. This follow-up authorizes none of those live mutations.


### Approved scheduling release completed

Core source 6a46521, the 21 saved pages, two applicable menu items and approved public call/email
settings are now applied to the protected sandbox after a fresh verified backup. Idempotent
preview and server-side checks passed. Actual Kadence browser acceptance remains pending
Basic Auth credentials; Issue #3 stays open. See the current release section in the scheduling
report. Issue #4 is still design-only; no calendar plugin/schema was deployed.


Protected review is now complete: real Kadence scheduling **63/63 passed**, overall mobile/
desktop **42/42 passed**, tablet **21 views need the same header link-name correction**.
Branding still shows the demo site title/default footer and the CSF footer is not placed.
Issue #3's deployed contact workflow is verified, but overall site acceptance stays open for
these finishing items and remaining owner notes. No theme settings were changed by the review.

## Development handoff — scheduling implementation starts November 1, 2026

The existing `csf-scheduling` calendar document in [doc 01](01-architecture.md#digital-ranch-staff-scheduling--issue-4-architecture-for-review) is **architecture only**; no scheduling plugin or real appointment tables are delivered yet. Issues below are newly authorized as **local development instructions only**. Never infer permission to install or migrate the protected sandbox.

1. **[Sprint 1 — scheduling foundation, Issue #8](https://github.com/kks787-ship-it/KksSandbox/issues/8):** separate modular plugin, fail-closed configuration, private owner-verified staff permissions, exact migration preview/explicit apply, versioned minimal InnoDB tables, idempotent commands/audit and MariaDB integration tests. No real appointments or public pages.
2. **[Sprint 2 — staff calendar, Issue #9](https://github.com/kks787-ship-it/KksSandbox/issues/9):** manual appointment entries, requested/hold/confirmed/canceled states, staff-only week/day view, concurrent resource conflicts, horse welfare, rider-clearance distinctions, cancellation audit. No Acuity or public self-booking.
3. **[Product discovery, Issue #7](https://github.com/kks787-ship-it/KksSandbox/issues/7):** Corner Stone Farm is the first pilot; future barn policy must be configurable instead of hard-coding Beth/Kris, Sunday closure, Wednesday off-site, Monday Academy or the five-full-training-horse cap into all customer installations. Beth additionally identifies QR-tag emergency horse identification, evacuation accountability and responder coordination as a possible unique value proposition **for later independent market/safety evaluation**, not a reason to suspend calendar development or activate the existing emergency plugin.
4. **Website finishing continues separately:** [Issue #5](https://github.com/kks787-ship-it/KksSandbox/issues/5) photo/branding and [Issue #6](https://github.com/kks787-ship-it/KksSandbox/issues/6) content/owner approvals. The protected sandbox already has some recent photo updates that are not reflected in this repository's static historical document sections; inspect live state before proposing a website mutation.

Build and test local code against disposable WordPress/MariaDB fixtures. Owner must separately approve any protected sandbox schema, staff access, use of real horse/client records, or production deployment. Inquiry disabled and emergency inactive; no production changes.

### Issue #8 local foundation candidate

Implemented the isolated scheduling foundation and its read-only diagnostics. Versioned schema,
strict facility policy, explicit staff grants, command replay/audit and disposable MySQL/MariaDB
migration/recovery tests are delivered. It is **not an operational calendar**, and no sandbox
migration or deployment occurred. See [doc 01's v1 contract and Issue #9 handoff](01-architecture.md#scheduling-foundation-v1-implementation--issue-8)
and [current validation](test-results/scheduling-foundation.md). Issue #9 starts with a reviewed
v2 migration and appointment service before its day/week UI; do not reuse public website records.

### Sprint #9 local working-calendar candidate

`csf-scheduling` 0.2.0 adds explicit v2 migration, private day/week views, manual appointments,
state changes, transactional resource/participant conflicts, horse suitability/workload and
pre-ride evidence distinctions. Cancellation and protective-block release preserve history.
See [validation and remaining release gates](test-results/scheduling-calendar.md) and the
[implemented architecture](01-architecture.md#sprint-9-implementation--csf-scheduling-020--schema-v2).
This supersedes the earlier “calendar not built” status for **local code only**. No protected
sandbox install or real scheduling acceptance is implied. Pilot configuration/group entry
still uses an advanced editor; recurrence/Academy/route automation remain future increments.

Next development milestone: Sprint #10 household onboarding and digital agreements, using
shared household/rider/horse identities and legally reviewed versioned signing evidence.
Do not implement payments, public booking or emergency integration as part of that handoff.

### Sprint #9.1 / Issue #10 local usability candidate

0.2.1 replaces structured appointment/group/roster/availability/welfare editing with native
staff fields, validated local times, pre-save preview and clear conflict/clearance messages.
It retains schema v2, existing records and the transactional reservation service. The
[synthetic staff walkthrough](13-scheduling-staff-acceptance.md) and
[validation report](test-results/scheduling-staff.md) prepare release review, not live operation.

Next: separately approve a pinned scheduling-only protected pilot, then Beth/Kris device and
workflow acceptance with synthetic records. Initial real policy/resources, staff IDs and MFA
remain owner/operator decisions. Only after usability/pilot acceptance should Sprint #10
household onboarding and versioned digital agreement work connect to existing rider UUIDs.

### November 8 continuation — local 0.4.16-dev, not released

The current approved native-calendar work supersedes the historical next-sprint
paragraph above. Household onboarding/agreements remain later scope; payments,
public/emergency booking and real messaging remain held. The continuation branch
`codex/digital-ranch-continuation` is based on takeover-audit
`22657b84d192b91bc364553794e4aba9e7ccd1f2`.

Local increments now implement unused-horse classification correction and guarded
archive/restore; atomic confirmed family visits with native all-item review and
shared buffers; independent standard buffers; private weekly dashboard and durable
in-app notices/acknowledgements; future closure editing using actual allocation
impact; and explicitly granted optional-buffer exceptions. Recurring instructor
and arena hours/closures already existed in the 0.4.9 baseline and were preserved.
See [family integration](test-results/scheduling-family-visits.md),
[dashboard/notices](test-results/scheduling-dashboard-notifications.md), and
[latest validation](test-results/scheduling-buffer-overrides.md).

A fresh read-only Gateway file read on November 8, terminal job
`job_01M6WXJK0000A56JY95HPYZQY5`, confirms the installed scheduling entry file
still declares 0.4.9. SHA-256:
`bb5acf5f646e855a925c795760cd575dc6b4c2a845f9b20846c29391a78a77d5`.
This is source identification, not a fresh full runtime/backup/MFA acceptance.
Existing diagnostic job `job_01M6VTKKA70002713ZHP8NQEGC` reported intact source,
ready schema and pending native master MFA authentication. No remote writes occurred.

Before release: run the disposable database/WordPress/race/HTTP/browser suite and
PHPCS, resolve publication authorization, prepare and rehearse schema-4 recovery,
then satisfy fresh backup and every existing deployment/readback gate. The prior
branch push was denied by automatic review as unauthorized external publication;
it was not retried. No PR or deployment exists for these local increments.

Remaining implementation/acceptance: group requested/held expiry, attaching existing
independent lessons, complex multi-horse family editing, reopening all-canceled
visits, further drawer/device usability, editing started closure series prospectively,
dependency-aware used-horse correction/deletion, configurable buffer policy UI,
and separately authorized email transport with bounded claims/retries/uncertain
delivery handling. Held notification envelopes do not implement that transport.
Native owner MFA/calendar acceptance and actual service configuration remain
separate owner/operator work. Do not treat the standalone planner, these local
increments, or their isolated tests as a completed operational release.

### Follow-up continuation — local 0.4.17-dev

Group requested/held states and common expiry, existing-lesson attachment, native
multiple riders/horse turns, draft removal and scoped reuse of canceled visits are
now implemented locally; these supersede the corresponding unfinished entries
above. A cancellation snapshot inconsistency was fixed during integration review.
The email adapter has 31 fixture-only sink checks, including deduplication, bounded
retry and uncertain outcomes; a durable dispatch worker and real delivery remain
unimplemented/held. Schema-4 public recovery source/payloads and offline fixture
inputs are now prepared, with 283 synthetic tests passing; functional restore is
still unqualified. Full candidate PHP isolation suite: 346 checks on each version.

See [family validation](test-results/scheduling-family-visits.md), [email boundary](test-results/scheduling-email-sink.md)
and [recovery qualification](test-results/scheduling-recovery-v4.md). Publication,
real SQL/races, WordPress/MFA/browser acceptance and deployment gates still block
release. Broader device/drawer refinement, prospective editing of started closure
series, used-horse dependency-aware correction/deletion and configurable buffer
policy remain pending; household/agreement/payment scope remains unchanged.

### Master audit / independent review — local 0.4.18-dev

Added guarded unused-horse deletion with retained history/tombstone, direct ordinary
lesson-to-family handoff and combined visit span. Fixed reassignment recipients and
mutable historical notification dates. Full source/status mapping and remaining
implementation are in [the master audit](test-results/scheduling-master-spec-audit.md);
[the integration plan](test-results/scheduling-integration-validation-plan.md) names
the exact missing environment, matrix, additional race/access checks and release
gates. Publication-only approval now permits the branch and draft PR; no merge or
deployment follows. Freeze this review candidate after publication.

### November 8 evidence reconciliation — draft publication, not deployment

The historical pending entries above are superseded by draft PR26 / `4c29c66`:
family holds/expiry/editing/drawer, safe reconciled horse mapping, unused deletion,
prospective started-closure splitting, and durable sink claims/current recipients
are implemented. CI run42468961364 passed all four database combinations and the
functional browser/mail suites; MFA50/security79 passed before the separate missing
exact-Python recovery prerequisite. Source remains undeployed and owner acceptance
open. See the appended [master audit](test-results/scheduling-master-spec-audit.md).

The next increment connects the guarded native-mail fixture to durable dispatch,
adds dashboard/notices browser acceptance, and measures specified family workflows.
Official Debian 3.13.5-2+deb13u5 contains the exact pinned interpreter, enabling a
scratch-only CI preparation path without local-runtime uploads or CI-secret changes.
Functional restore is not passed until all unchanged phases actually execute.
