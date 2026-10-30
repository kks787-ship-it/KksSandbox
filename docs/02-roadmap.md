# 02 — Delivery Roadmap

Phases are sequential; each ends with owner review on the development site. Nothing goes to
the production domain until the owners approve the replacement.

## Phase 0 — Foundations *(this repository, in progress)*

- [x] Git repository and coding standards (WPCS, PHPCompatibility)
- [x] Read-only environment inspection script
- [x] Backup and dry-run-first deployment scripts for the dev site
- [ ] **Run environment inspection on the VPS** and record findings
- [ ] Owners complete the confirmation register (`03-owner-confirmation-register.md`)
- [ ] Owners supply original logo source files and authentic photography

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

## Phase 2 — Public website

- Kadence global palette, typography, header/footer; logo optimised from approved source
- Homepage (14 sections from the specification) and primary navigation
- Lessons, Training, Camps & Clinics, Horse Sales, About, Resources, Contact, Booking pages
- Knowledge Library launch with initial articles
- Verified Acuity booking buttons
- Accessibility audit (WCAG 2.2 AA), performance budget, redirects from old URLs
- Launch plan for switching the production domain (separate approval)

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
