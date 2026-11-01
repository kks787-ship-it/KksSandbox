# Issues #3/#4 local delivery — October 31, 2026

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
