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

## Phase 2 — Public website *(0.2.0 active in sandbox; acceptance pending)*

- [x] Brand design system in `csf-core`: palette + self-hosted Playfair Display/Lato as editor presets, block styles, accessible colour pairings
- [x] 14 section patterns and 21 page layouts (core blocks only; editable in Gutenberg)
- [x] All pages from the sitemap, hierarchy, primary & footer menus, synced footer, via idempotent `wp csf setup` / Tools screen
- [x] Knowledge Library: summary, topics, audiences, reading time, review date, updated date, related articles, `/library/`
- [x] Horses (farm / sale / lease) and Camps & Clinics as structured content with listing blocks
- [x] Single shared Acuity booking button; farm contact details block (empty until confirmed)
- [x] Emergency banner verified on the new pages (independent plugin unchanged)
- [x] Automated tests: accessibility (axe WCAG 2.2 AA), responsive, links, navigation, booking, block-editor validity, emergency, structured content, setup idempotency
- [x] Repeatable local environment (`scripts/local-dev.sh`)
- [x] Installed/active core; all 21 pages published, menus assigned, static Home, /%postname%/ and rewrite/Apache checks passed (Gateway)
- [ ] **SITEWIDE reported real-Kadence gutter defect — [Issue #1](https://github.com/kks787-ship-it/KksSandbox/issues/1):** intro text reaches viewport edge while nearby headings are inset (Meet Beth, Camps & Clinics). Fix shared constrained-content/gutter behavior and inspect all 21 pages, other text sections and Gutenberg at 390/768/1280px; preserve full-bleed backgrounds and owner content. Not independently reproduced here; no speculative two-page patch or deployment.
- [ ] Verify on Kadence: Customizer palette, header, footer, page-layout settings
- [ ] Logo, photographs, contact details, Acuity verification, resolve 23 owner notes (doc 09)
- [x] Local 0.3.0 candidate: deployment permissions, failed-setup reporting, optional inquiry form with spam controls and mocked delivery tests
- [ ] Deploy reviewed core update after separate approval/current backup gate; keep inquiry form disabled
- [ ] Verify actual delivery to beth.siemon@cornerstonefarmomak.com and actual MariaDB/form behavior before approved enablement (provider unknown)
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

## Current assignments — finish the existing site

**Assignment A: finish the public website.** The later remote roadmap records real-Kadence
gutter Issue #1 after the earlier no-confirmed-defects clarification. Authenticated reproduction
and the shared fix remain pending here. Start with six priority pages, then audit all 21
at 390/768/1280px, including other text sections and Gutenberg; preserve full-bleed backgrounds. Local deployment-mode/setup-reporting fixes and the disabled inquiry
form are review candidates, not installed code. Footer block 27 placement, header/palette/
typography, original assets, Acuity verification and owner notes are configuration/content
work, not reasons for another plugin or rebuild. See [current status](test-results/sandbox-preflight.md),
[local validation](test-results/website-finishing.md) and [acceptance/update plan](07-website-installation.md).

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
