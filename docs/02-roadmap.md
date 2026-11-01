# 02 — Delivery Roadmap

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

## Phase 4 — Onboarding & agreements

- Household accounts with multiple riders and emergency contacts
- Agreement templates (legally reviewed) with version tracking and e-signature
- Service gating: a rider or horse cannot be marked active for a service until the required
  agreement version is signed

## Phase 5 — Portals

- Client portal, horse-owner portal, staff portal, with per-record authorization
- Horse sales and leasing listings with inquiry forms

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

Onboarding, legally reviewed agreements, records and portals remain separate future phases.
Do not rebuild the 21-page site or combine the management roadmap into a giant plugin.

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
