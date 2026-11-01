# Issues #3/#4 local delivery — October 31, 2026

## Approved sandbox release — October 31, 2026 (PDT)

The owner explicitly approved plugin deployment and, separately, the public call/email and
21 saved-page/applicable-menu changes. Exact deployed source:
`6a4652100988719cac919780b4ec9103b8c35ada`. This release supersedes earlier pending-deployment
and unset-contact statements below; earlier validation records remain historical evidence.
The internal calendar remains architecture only.

| Operation | Evidence and outcome |
|---|---|
| Exact-target preflight, fresh backup and core-only comparison | Gateway `job_01M6AY6Z0D00HPVMNYJXEZDA9D`, succeeded. Kadence 1.5.2, MariaDB 10.6.23, PHP/WordPress/extensions and sandbox URL checks passed; only csf-core targeted, no deletions. |
| Fresh target-bound backup | `/var/www/vhosts/cornerstonefarmomak.com/private/csf-dev-20261101-065451-968013/`; DB captured November 1 06:54:51 UTC / October 31 23:54:51 PDT. Database gzip, wp-content archive and exact six-file checksum/target/age gate passed. This is not a new restore drill. |
| Core-only files applied | Gateway `job_01M6AY9B9Y00KEP43J45FFCKER`, succeeded. Dry run rechecked against its saved comparison immediately before apply. Installed core tree matches the exact release source; emergency SHA-256 manifest unchanged. Version remains 0.3.0. |
| Saved content/settings applied | Gateway `job_01M6AYBS7Q00XXK7V0X1999HH3`, succeeded. Hash-guarded updates to existing page IDs 6–26; no created pages, force, owner-edit overwrite or site-setup apply. Setup content/source hashes updated after each verified save. Booking title updated to Contact Beth to Schedule. |
| Applicable navigation | Existing menu items 48 and 51 now say Contact Beth to Schedule and reference the Contact page. Item IDs, positions, parent relationships, classes, menu memberships and location assignments retained. |
| Public channels | Farm Details phone `509-557-8467` and email `Beth.Siemon@cornerstonefarmomak.com` saved from explicit owner approval, preserving unrelated fields. Rendered call/email links verified. No SMS or 24-hour/emergency-use approval inferred. |
| Idempotency | Post-migration `wp csf setup --dry-run`: all 21 pages and synced footer #27 `ok`; no further updates. Permalinks and footer placement unchanged. |
| Final server-side verification | Gateway `job_01M6AYD1E500ZSBZ97G4TS91PB`, succeeded. All 49 core PHP files lint; 21 saved public do_blocks renders with one H1 each, no old booking claims/forms; public channels and persisted menus pass; core files 0755/0644 and subscription owned. |

Rollback artifacts are private in the fresh backup: `content-before.json` preserves the
original 21 page records/metadata, menu records and public settings, while `wp-content.tar.gz`
preserves the previous core tree. Deployment dry-run/apply logs and emergency hashes are
retained there. Restore only the affected sandbox core tree/records after reviewing later
changes; never restore the entire subscription or production database. Original structural
restore evidence is retained unchanged; functional restored-site testing is still unrun.

**Protected browser acceptance remains blocked:** the post-release unauthenticated Contact
request returns expected HTTP 401. No Basic Auth value is stored in Gateway yet. A secure
Gateway request for `csf-sandbox-http-auth` is pending; credentials must never enter chat or
Git. Actual protected Kadence review at 390/768/1280px has not run. The prior 441 checks and
19/19 content rerun are private stand-in fixture evidence, not live-site visual acceptance.
Issue #3 stays open for that acceptance; deployment completion must not be called full site
acceptance. No authentication protection was removed or bypassed.

Throughout: inquiry disabled, emergency inactive with unchanged files, no mail sent, no
production/DNS/Plesk/vhost changes and no calendar engine/schema/appointment creation. Cloud
onboarding, installation scripts and the historical private source pin were not modified.


## Current follow-up: runtime evidence and event assertion corrected

The original executor blockers below are historical. Follow-up validation used the existing
private WordPress 7.1.4 / PHP 8.3.36 / SQLite fixture and Kadence **stand-in**, accessed through
Gateway. It is not the protected real-Kadence sandbox or the Codex Cloud runtime. The tested
plugin source remains `c3732b2`; this follow-up changes only the event browser assertion and
documentation, not plugin behavior.

The obsolete `.csf-event-register a` assertion was replaced by two checks scoped to the event
summary: exactly one link with accessible name **Contact Beth to Schedule**, and the exact
internal `/contact/` URL rather than the fixture's saved external registration URL.

**Rerun in this task:** `node tests/e2e/run.js --only=content` — **19 passed, 0 failed**.
Gateway job `job_01M6AXK96W003HZ4FJAAY2MSMQ` completed with exit 0; test-file SHA-256
`001b750eb671976e190a46c6273ca77a1231965667c52a92436f571d0701f11c` matches this checkout.
The suite cleaned up all synthetic records. Inquiry was disabled and emergency inactive
before/after; the local-only server was stopped by the cleanup trap. No live mail or sandbox
writes occurred. The targeted run does not overwrite the historical phase-2 report.

**Prior follow-up results, not rerun here:** PHP syntax 69/69, PHPCS clean, contact scheduling
100, setup reporting 27, Contact upgrade 14, inquiry validation 39, signed integrity 17,
inquiry browser 25, editor 61, navigation 7, responsive/booking/a11y 441 at 390/768/1280px,
deployment guards 22, Plesk identity 6, sandbox target 14 and browser security guards 6.
Provenance is in [Issue #3's validation comment](https://github.com/kks787-ship-it/KksSandbox/issues/3#issuecomment-6554519514).
The 441-pass completion log was independently read from Gateway job
`job_01M6AWRD3E009G36KCEARPV5F4`. The earlier structured-content 17/18 mismatch is now
resolved by the targeted 19/19 rerun above. Keep these results separate from authenticated
real-Kadence and MariaDB acceptance, which remain outstanding.

### Saved sandbox migration preview (existing evidence; not repeated or applied)

Gateway job `job_01M6AWXGW300V1P3VW3GMCZES5` completed successfully. Its installed-code
calibration exactly matched normal `wp csf setup --dry-run`; candidate `c3732b2` proposed
**21 updates, zero kept/owner-edited pages, zero new pages**, retaining synced footer #27.
Before/after saved-page aggregate SHA-256 both were
`368441d82b7911977d38f68fc9fd06848acdf349adac9a8c137b84930dcc9c1c`.
The candidate was previewed without installing its plugin or applying setup. All page paths
listed in doc 07 are proposed updates; there is no approved content write. Persisted Primary
and Footer menu labels need separate review. The earlier installed-code Contact-only preview
must not be mistaken for the candidate's 21-page proposal.

At inspection, public phone/email were unset. The owner has now explicitly approved Beth's
call/email channels in [the confirmation register](../03-owner-confirmation-register.md#public-scheduling-contact-approval).
Those values are recorded, **not yet published or saved on the sandbox**. SMS is not approved.
A separately authorized Farm Details settings step and rendered-link verification are required
before the new Contact workflow can be accepted for publication; the form stays disabled.

Issue #4's five business-rule refinements are incorporated in the canonical architecture and
acceptance matrix. This is still design only: no calendar plugin, schema or bookings. No
production, live plugin, saved page/menu or settings changes were made in this follow-up.


## Original cloud-only execution record (historical)

Source base: `5055e822833ca3a1aae402207153a1df34199284`, branch
`codex/takeover-audit`. No sandbox/production operation, environment onboarding or
republication, installation-script change, inquiry enablement, mail or emergency activation.

## Changes prepared

The common dynamic block/shortcode button always displays **Contact Beth to Schedule** and
uses the site's `/contact/` URL. Old provider options, verified flags and custom labels are
ignored; the option sanitizer normalizes future writes to that fixed contract. Existing
block names, serialized attributes, variants and alignment remain compatible. The editor no
longer offers a label override. Settings is informational; CLI status reports direct contact.

Classic navigation items referencing the Booking page, or marked `csf-menu-cta`, render the
new label and Contact destination without rewriting saved menus. `/booking/` stays available
as an explanation page. Event registration buttons ignore saved external registration URLs
and use the contact component for open/waitlist events; metadata is retained unused.

Generated Booking, Contact, FAQ, Lessons and Off-site copy plus shared CTA/getting-started
patterns distinguish a request from a Beth/Kris-confirmed appointment. Public Farm Details
remain the only channel source; the private inquiry recipient is not copied there. An unset
phone/email produces a neutral public fallback, not fabricated contact information. A public
phone offers Call; it does not assume text-message approval. Inquiry enablement is unchanged.

Issue #4's architecture is in [doc 01](../01-architecture.md#digital-ranch-staff-scheduling--issue-4-architecture-for-review),
covering tables/keys, four states, Beth/Kris permissions, InnoDB concurrency, REST/UI,
recurrence/DST, policy, private audit, migrations/rollback and phased acceptance tests.
No scheduling plugin, schema or hypothetical booking history was created.

## Checks actually run

| Check | Result |
|---|---|
| `python3 tests/scripts/booking-source.py` | PASS: two source-audit tests, all 21 page templates and 14 section patterns scanned; no canceled provider or online-booking claims in active PHP |
| `bash tests/scripts/development-guards.sh` | PASS: 22 mocked deployment/backup scenarios |
| `bash tests/scripts/plesk-preflight.sh` | PASS: six mocked identity scenarios |
| `python3 tests/scripts/test_sandbox_preflight.py` | PASS: 14 tests |
| `python3 tests/scripts/test_kadence_review.py` | PASS: six tests |
| `node --check tests/e2e/run.js`, runner shell syntax, `git diff --check` | PASS |
| Read-only local SQLite page/hash inventory | All 21 generated pages match `_csf_setup_hash`; no owner-edited generated pages in this local fixture. The separate privacy-policy draft is untracked and excluded. No inquiry option; active plugins are core and SQLite integration only. |

Source audits are not PHP parsing, WordPress rendering or visual acceptance. Guard tests
mock remote tools; none constitute a server deployment/preflight in this task.

## Blocked checks and committed regressions

This executor lacks PHP CLI, Composer and rsync. Its inherited HTTP proxy refuses connections,
so normal Git fetch/package access is unavailable. The branch snapshot was recovered through
the connected GitHub read service: text blob, complete tree and head commit SHA checks passed.
Four unchanged historical PNGs could not be decoded by that service and remain excluded from
the local sparse checkout; their tree entries are preserved unchanged. No environment setup
or installation script was modified to work around this.

- `composer lint` and `php vendor/bin/phpcs`: commands attempted, unavailable binaries.
- `bash tests/integration/run-booking-contact.sh`: attempted; stops explicitly before fixture
  mutation because PHP is missing. The new test copies the disposable local fixture to its
  own SQLite DB, requires inquiry off/emergency inactive, intercepts all mail, tests stale
  provider settings/custom labels, generated rendering across all 21 pages, safe channels,
  menus, events and non-forced edited/no-hash preservation. It never enables either feature.
- Existing takeover/emergency HTTP and setup/inquiry PHP integrations: unrun, same PHP blocker.
  Emergency stays inactive; do not run its activation/browser suites on an existing site.
- Full browser suite: default Playwright browser missing; retried with system Chromium via
  `CSF_CHROMIUM_EXECUTABLE=/usr/bin/chromium`, which exits with SIGTRAP after a crashpad socket
  permission error. No browser checks executed and the historical phase-2 report is preserved.
- `bash tests/scripts/deployment-permissions.sh`: attempted, requires installed rsync.
- Actual MariaDB, Kadence, PHP minimum-version and protected-site acceptance: unrun.

The updated browser assertions cover fixed labels/destinations, absence of canceled-provider
claims and 44px touch targets on desktop/tablet/mobile. These are prepared, **not passed**.
Re-run PHP lint/WPCS, the isolated contact regression and local browser acceptance in a working
runtime before treating this as a release candidate. Scheduling-MVP migration/race tests are
design criteria only, because its implementation is outside this architecture delivery.

## Saved-content migration boundary

No existing local or remote saved page/menu was updated. The SQLite inventory above is a
read-only local observation, **not** `wp csf setup --dry-run` output or sandbox evidence.
Actual WordPress dry-run execution is blocked by missing PHP. The precise commands and
separate content-approval process are prepared in [doc 07](../07-website-installation.md#issue-3-saved-content-migration-proposal-not-executed-on-sandbox).

All 21 generated local pages are eligible for non-forced comparison, but only a real dry run
can determine which expanded templates require updates. Shared static pattern text affects
multiple pages. Existing synced footer is kept; owner-authored text, custom header HTML,
custom external menu links and edited/untracked pages need exact manual proposals. A code
update alone cannot establish that every saved rendered page is free of booking claims.
No `--force`, content rewrite, permalink change or deployment was performed. Issue #3 remains
open for runtime validation and separately approved saved-content/site acceptance.
