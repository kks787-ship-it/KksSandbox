# 02 — Delivery Roadmap

Phases are sequential; each ends with owner review on the development site. Nothing goes to
the production domain until the owners approve the replacement.

## Phase 0 — Foundations *(this repository, in progress)*

- [x] Git repository and coding standards (WPCS, PHPCompatibility)
- [x] Read-only environment inspection script
- [x] Backup and dry-run-first deployment scripts for the dev site
- [x] Gateway inspected the actual sandbox; owner supplied findings (current report: `test-results/sandbox-preflight.md`)
- [x] October 31, 2026: original wp-config backed up; development/editing/debug-display constants applied and verified (configuration-only recovery evidence)
- [ ] Full current sandbox files/config/database backup
- [ ] Isolated scratch restore verification in a new database
- [ ] Private pinned application source staging and exact-target deployment dry run
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

## Phase 2 — Public website *(code complete for review; not deployed)*

- [x] Brand design system in `csf-core`: palette + self-hosted Playfair Display/Lato as editor presets, block styles, accessible colour pairings
- [x] 14 section patterns and 21 page layouts (core blocks only; editable in Gutenberg)
- [x] All pages from the sitemap, hierarchy, primary & footer menus, synced footer, via idempotent `wp csf setup` / Tools screen
- [x] Knowledge Library: summary, topics, audiences, reading time, review date, updated date, related articles, `/library/`
- [x] Horses (farm / sale / lease) and Camps & Clinics as structured content with listing blocks
- [x] Single shared Acuity booking button; farm contact details block (empty until confirmed)
- [x] Emergency banner verified on the new pages (independent plugin unchanged)
- [x] Automated tests: accessibility (axe WCAG 2.2 AA), responsive, links, navigation, booking, block-editor validity, emergency, structured content, setup idempotency
- [x] Repeatable local environment (`scripts/local-dev.sh`)
- [ ] **Owner approval to install on the Plesk development site** (doc 07)
- [ ] Verify on Kadence: Customizer palette, header, footer, page-layout settings
- [ ] Logo, photographs, contact details, Acuity verification, resolve 23 owner notes (doc 09)
- [ ] Phase 2b: contact/inquiry form with spam protection and tested delivery (owner picks approach)
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

## Codex takeover priorities

Continue from Claude commit `c44240e` on a separate branch. The historical phase checkboxes
above describe prior work, not a new independent verification. Follow the next three
milestones and evidence gates in [the takeover audit](10-takeover-audit.md):

1. Emergency persistence/concurrency hardening, reproducible tests, approved Plesk dev install.
2. Authentic assets, verified copy/booking, real Kadence and device acceptance.
3. Notification/assistance reliability and drills, followed by streamlined household onboarding.

[Business context](CSF-BUSINESS-CONTEXT.md) records supplied operational requirements;
public wording and missing details remain in the owner confirmation register.

## Current Milestone B status — October 31, 2026

Gateway's completed three-constant hardening must not be repeated. The original configuration
backup is `/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak`; retain it as
configuration-only evidence. The remaining execution sequence is full sandbox backup →
isolated scratch restoration → private source staging → preflight/deployment dry run →
review and separate installation approval. Those four preparation operations remain undone
and require approval; plugin installation/activation, permalink changes and production
changes remain unauthorized. See the current [preparation package](05-backup-rollback-deploy.md#sandbox-preparation-approval-package).
