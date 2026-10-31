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
- [x] Fresh installation-specific target-bound backup created and verifier passed October 31, 2026 (time-limited gate; recheck age at installation)
- [x] Owner approved sandbox files-only installation; `csf-core` 0.2.0 and `csf-emergency` 0.1.0 copied inactive October 31, 2026, contents verified against reviewed source; 68 PHP files linted
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

## Phase 2 — Public website *(files staged in sandbox; plugins inactive; site not set up)*

- [x] Brand design system in `csf-core`: palette + self-hosted Playfair Display/Lato as editor presets, block styles, accessible colour pairings
- [x] 14 section patterns and 21 page layouts (core blocks only; editable in Gutenberg)
- [x] All pages from the sitemap, hierarchy, primary & footer menus, synced footer, via idempotent `wp csf setup` / Tools screen
- [x] Knowledge Library: summary, topics, audiences, reading time, review date, updated date, related articles, `/library/`
- [x] Horses (farm / sale / lease) and Camps & Clinics as structured content with listing blocks
- [x] Single shared Acuity booking button; farm contact details block (empty until confirmed)
- [x] Emergency banner verified on the new pages (independent plugin unchanged)
- [x] Automated tests: accessibility (axe WCAG 2.2 AA), responsive, links, navigation, booking, block-editor validity, emergency, structured content, setup idempotency
- [x] Repeatable local environment (`scripts/local-dev.sh`)
- [x] **Files-only owner approval and sandbox copy** (doc 07); activation, site setup and permalink changes remain separately unauthorized
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

Gateway completed hardening, full backup, structural/file restore, source staging and
preflight/deployment dry run. Do not repeat them. Full backup:
`/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/`.
Staged source stays at d360dbf; plugin trees match 52401dc. The original config-only
backup stays at `/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak`.

Restoration qualification: 12 InnoDB tables/count samples and 4,336 regular files verified,
zero mismatches; functional login/page-rendering on the restoration NOT RUN.

The installation-specific gate backup was captured on October 31, 2026 at about 18:46 UTC
(11:46 AM PDT), and `scripts/verify-dev-backup.sh` passed all six checks. Its age is
limited to 24 hours and must be rechecked immediately before any installation.
Files-only installation was subsequently explicitly approved by the owner and completed via Gateway; both plugins remain inactive. Activation, 21-page setup/permalinks and real Kadence testing are separate stages.
No activation/setup/permalink/production change is authorized by this documentation update. See the [current plan](07-website-installation.md#staged-installation-approval-boundaries).


Gateway's subsequent execution detail records database export at October 31, 2026,
10:08:58 AM PDT and file archive at 10:09:42 AM PDT. Scratch DB:
csf_sandbox_restore_20261031. Existing private evidence:
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt.
The completed full backup, scratch restore and pinned source were reused. A new script-format
backup gate bundle was captured separately at:
`/var/www/vhosts/cornerstonefarmomak.com/private/csf-dev-20261031-184634-36879/`.
Verifier job: `job_01M69MJJ04000DFWPDVJ254SKY` (succeeded). The bundle is not a replacement
for the full backup or a new restoration drill. **Approved sandbox-only file copy succeeded**
under Gateway job `job_01M69NHGP200KW7JZK6HD2V9YZ`, with post-copy validation under
`job_01M69NPPXH00VQVHRVJ8K2KKQ8`. Both plugin directories and all files match the pinned
reviewed source byte-for-byte. PHP lint passed for 68 files. WordPress confirms both plugins
**inactive** and the sandbox database remains `wp_mdf4x` (5 posts; no page setup).
Permissions in those two new plugin trees were normalized to directories `0755` and files
`0644`, matching existing WordPress plugin conventions. **Important follow-up:** staging
Git checkout uses restrictive modes; the `rsync -a` deployment script can reset permissions
on later deployments. Fix that in a separate code review (e.g. enforce destination modes)
before subsequent application updates. Production was not modified. Next: owner approval
for sandbox plugin activation (recommend `csf-core` first), followed by separate page/setup
and permalink authorization.
