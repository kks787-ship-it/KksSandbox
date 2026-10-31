# Digital Ranch takeover audit

Assessment date: October 30, 2026 (America/Los_Angeles). Baseline:
`c44240e9994c5c693360bef21d9aa2757de79c8e`, matched by fetching
`origin/claude/csf-digital-ranch-spec-cch2af`. Work branch: `codex/takeover-audit`.
No production or Plesk access, configuration changes, deployment, or push was performed.

## Recommendation

**Continue, with targeted hardening.** Rebuilding would discard useful content architecture,
21 page layouts, 35 patterns, theme-independent emergency templates, and clear plugin
boundaries without resolving the remaining operational dependencies. The two plugins use
WordPress APIs with a small production dependency footprint. The present code is suitable
for continued development and review, not operational emergency reliance or production launch.

This is a source review plus targeted local runtime validation. It is not a penetration
test, a complete vulnerability guarantee, a legal review or owner acceptance. Findings below
identify whether behavior was reproduced or inferred from inspected code.

## Architecture and quality

`csf-core` owns brand presets, locally hosted fonts, Gutenberg markup/patterns, page/menu/footer
setup, public resource/horse/event post types and shared booking/contact settings. Kadence
supplies the actual header/footer and page layout. Public marketing records use ordinary
WordPress editing capabilities. No private portal should reuse these public post types.

`csf-emergency` owns the non-public emergency horse CPT, approval metadata, random tag tokens,
standalone public/print routes, farm-state option, confirmation transients, coordinator
capabilities and custom audit table. Emergency templates operate independently of Kadence.
The banner integrates through WordPress hooks. Notification and cache-purge hooks are extension
points; notification processing, volunteer dispatch and portals are absent.

Strengths: narrow classes, common markup/field helpers, escaped template output, nonces and
explicit capabilities at emergency HTTP write entry points, prepared audit-log pagination,
retained data on uninstall, public approval controls and an owner-confirmation register.
Self-hosted fonts and no booking embed reduce third-party frontend requests. Listing counts
are bounded; emergency summaries cap at 500. These are design observations, not load testing.

Debt: schema version is marked installed without checking all dbDelta results; several
WordPress writes ignore failure; PHP APIs rely on trusted callers; thin security/failure-path
test coverage; manual Kadence setup; environment-specific test tooling. Class comments claiming
single-use concurrency protection or exactly-once hooks exceed what the implementation proves.

## Ranked security and reliability findings

| ID / severity | Evidence and effect | Disposition |
|---|---|---|
| S1 High: deployment target check fails open | Original `scripts/deploy-plugins.sh` substituted `unknown` on WP-CLI failure, rejecting only `production`. A broken bootstrap could still reach `rsync --delete --apply` on an unverified target. Backup had the same guard pattern. | **Fixed locally**: require successful detection and exact local/development/staging value. 17 script scenarios pass, including failed detection before writes and paths with spaces. |
| S2 High: success despite failed state persistence | `CSF_Emergency_Farm_State::change()` ignored `update_option()` return, then logged/fired hooks as though state had changed. Failure injection reproduces this path. | **Fixed locally**: failed write returns `csf_state_write_failed` before log/hook. Regression verifies unchanged state, no new event and no hook. |
| S3 High before emergency go-live: non-atomic confirmation | `class-csf-emergency-admin.php` reads a transient around line 312 and deletes it around line 321. Concurrent requests can both read before deletion; the duplicate check is also read-then-write. Separate coordinators can confirm stale reviews and overwrite each other. | Source-confirmed race window; no concurrent exploit test performed. Requires atomic consumption, state revision/conflict handling and multi-process tests on target DB/cache. Do not add delivery integrations on an exactly-once assumption. |
| S4 High before emergency go-live: state/audit consistency | Farm and horse writes ignore `CSF_Emergency_Event_Log::record()` failure. Farm state can change without an audit row; hooks may still run. S2 fixes state failure only. | Source-confirmed unchecked result; audit-insert failure not injected in this pass. Design recoverable state/event/outbox persistence and explicit partial-failure UI. Avoid an ad hoc rollback that could overwrite another coordinator's change. |
| S5 Medium: ancillary corrections rejected as duplicates | `is_duplicate()` compares only status/message for 300 seconds, ignoring public_info and next_update. A correction to evacuation info with the same message is rejected. | Confirmed from comparison; add full-payload/revision semantics with concurrency hardening. |
| S6 Medium: permanent token reservation incomplete | `generate_unique()` queries `post_status=any`, which excludes trash; allocation is separate from post-meta insertion and has no global unique token constraint. Deletion also discards the reservation. Random collisions are very unlikely, but permanent tags require stronger lifecycle guarantees. | Source review. Add durable token registry/tombstones and collision/concurrent-allocation tests; never recycle issued identifiers. |
| S7 Medium: MFA advisory only | `render_mfa_warning()` warns but does not gate access; no supported factor is configured locally. | Known MVP limitation, not a new bypass. Verify server policy and recovery procedures before operational use. |
| S8 Medium: optional public horse directory | `templates/emergency-landing.php` lists individual links during an active emergency when show_horse_list is enabled. Default is off, but the specification says no public directory. | Default privacy validated; owner must resolve the documented opt-in exception. Do not enable it silently. |
| S9 Low: request-shape robustness | Several emergency sanitizers pass array-shaped submitted values into string/array-key operations; malformed authenticated input can cause PHP errors. | Source observation; add scalar/type validation alongside failure-path hardening. No unauthenticated write authorization bypass established. |

No confirmed critical issue or unauthenticated sensitive write was established in this review.
That is a scoped finding, not proof of absence. Public-approved fields remain public; attachments
must never be used to store confidential records merely because their parent CPT is unlisted.

## Other defects and compatibility

- **Fixed:** `CSF_Core_Blocks::card()` selected the absent `heading` key when its fallback `h3`
  passed validation, causing warnings and malformed headings. Regression checks the default H3,
  explicit H2 and escaped title. Existing structured-content tests did not assert this markup.
- Setup updates and menu/footer creation do not consistently propagate WordPress errors;
  `CSF_Core_CLI::setup()` prints success even if the collected log contains errors. Improve
  failure propagation before relying on unattended installation.
- `csf-core` advertises WordPress 6.5+, but brand data writes theme JSON schema version 3
  (introduced in WordPress 6.6). Verify the minimum-version behavior or align the declared minimum.
  This is a compatibility risk; failure on 6.5 was not reproduced.
- Current verified runtime: PHP 8.4.24, WordPress 7.1.4, SQLite integration 3.1.2 and the test-only
  Kadence stand-in. PHP lint and WPCS/PHPCompatibility passed across 68 plugin PHP files.
  These checks do not execute a PHP 8.1 or WordPress 6.5 compatibility matrix.
- Real Kadence, MySQL/MariaDB, Plesk PHP handler, nginx/Apache rewrites, HTTPS, cache/CDN and
  filesystem ownership remain **unverified**. No real Kadence theme package was provided.
- CSS includes breakpoints, visible focus styles and reduced-motion support. Browser
  accessibility/responsive/editor checks could not be rerun; screen-reader and real-phone
  assessment remains required. No Core Web Vitals or concurrent-load claim is made.

## Feature completion matrix

“Implemented” means code exists; it does not mean deployed or owner-approved.

| Feature | Source status | Current independent evidence / remaining gate |
|---|---|---|
| 21 public page layouts, 35 patterns, navigation/footer | Implemented | Local setup and representative HTTP pages pass; historical browser report only; actual Kadence pending |
| Brand colors, local fonts, headline | Implemented | Source reviewed; authentic logo/photos and palette approval outstanding |
| Shared booking and contact details | Implemented | Acuity URL present; booking destination and confirmed contacts unverified |
| Library topics, reading time, review date, related content | Implemented | Source reviewed; no approved starter educational articles supplied |
| Public horses and camps/clinics | Implemented | Source reviewed; shared card renderer regression fixed; full browser behavior pending |
| QR identifiers, profiles, print views | Implemented MVP | Token persistence, approved/hidden HTTP views, escaping, microchip masking, headers pass; physical QR decoding/scanning not rerun |
| Farm dashboard, banner, log | Implemented MVP | State success/failure/duplicate tests pass; concurrency, audit consistency, actual cache and coordinator UI testing pending |
| Notification delivery and consent | Not implemented | Hooks and honest “no notifications” UI only |
| Assistance requests, responders, capacity, dispatch | Not implemented | Phone fallback and do-not-self-deploy text only |
| Household/rider onboarding, agreements, signatures, gating | Planned | Requires reviewed legal templates and authorization design |
| Client, horse-owner and staff portals | Planned | No private-record implementation |
| AI assistant, store, automation, reporting | Planned | Public-content-only AI boundary documented |
| Deployment/rollback | Scripts and runbooks exist | Guard tests pass; no actual Plesk deploy or restore drill performed |

## Test coverage and results

Current targeted results are separate from `docs/test-results/phase-2.md`, whose historical
**472 passed** were recorded by the previous implementation on the stand-in theme.

| Check | Current result |
|---|---|
| `composer lint` | Pass, 68 plugin PHP files |
| `php vendor/bin/phpcs` | Pass, 68 plugin PHP files, no reported errors/warnings |
| `bash tests/scripts/development-guards.sh` | 17 scenarios pass; mocked WP-CLI/rsync, real script control flow and backup archive/checksum commands |
| `wp eval-file tests/integration/takeover.php` | 8 checks pass on local WordPress; state failure injection, success/log/hook, sequential duplicate, card markup/escaping |
| `python3 tests/integration/emergency-http.py` | 14 checks pass; synthetic records removed in finally |
| Representative Home/Lessons/Emergency HTTP | 200 with expected content |
| Shell syntax and `git diff --check` | Pass |
| Chromium installation / 472 browser checks rerun | Blocked / **not run**: cdn.playwright.dev returns 403 Domain forbidden |
| Kadence, MySQL/MariaDB, Plesk deployment, MFA, physical/mobile-network drill | **Not run** |

The first HTTP search assertion matched its own reflected query; it was corrected to check
for the synthetic horse's full name using a shorter search query, and all 14 checks reran.

The existing browser suite has valuable broad page, responsive, axe, block-validity and setup
checks, including a deliberately invalid-block control. Limitations: Playwright is required
but absent from package.json/lockfile; this cloud image happens to provide it. The suite is
coupled to substitute-theme selectors, has little parallel-request or persistence-failure
coverage, and does not prove emergency delivery. `testSetup()` calls `--force` in cleanup,
which can overwrite pre-existing local edits. Run the full suite only against a disposable
fixture site; it rewrites the historical report. Pin browser tooling in a follow-up and add
isolated test targets/output options rather than treating a zero-test run as success.

## Development deployment plan

Follow [the existing runbook](05-backup-rollback-deploy.md) and
[website installation guide](07-website-installation.md); the takeover preparation below
adds explicit evidence gates. No server address/path/authentication is available in this task,
so an actual Plesk installation cannot be validated here.

1. Review this branch/release and its tests. Obtain the precise **development** hostname,
   document root and authorized access through secure configuration; inspect existing bindings
   before requesting credentials. Do not put secrets in chat or repository files.
2. Run the existing read-only environment inspector as the subscription user. Record WP/PHP/DB,
   Kadence version, web server/rewrite behavior, HTTPS, resources, ownership, plugins, caches,
   cron and backups. Do not print wp-config or credentials. Verify WP_ENVIRONMENT_TYPE is
   development/staging; production/default/unknown detection must block deployment.
3. Create a restricted backup outside the public web root; record checksums and an encrypted
   off-server copy. Capture wp-config securely via the existing server backup facility (the
   script archives DB/wp-content, not wp-config). Restore to a scratch development subscription
   and verify it before proceeding. Mock backup tests are not a real restore drill.
4. Build a plugin-only release from the reviewed commit; exclude .wp-local, vendor,
   node_modules, tests/fixtures and private configuration. Record release hash and previous
   version. Run deploy-plugins.sh without --apply; inspect the exact rsync deletion/copy plan.
5. Request approval for the concrete development deployment and backup/rollback plan. Only
   after approval apply it, activate plugins, and preview `wp csf setup --dry-run`; avoid --force
   on edited content. Follow doc 07 for Kadence header/footer/palette and per-page layout.
6. Verify logged-out routes, no duplicate H1, booking, editor validity, accessibility, phone
   and desktop layouts, error logs, emergency capability checks, MFA and cache freshness.
   Revert test status to Normal. Keep emergency workflows labeled non-operational.
7. Roll back plugin files to the recorded prior release if necessary; restore DB/wp-content
   only after reviewing the impact on later changes. Verify checksums and recheck URLs.
   Production changes and any launch/DNS changes require a separate plan and approval.

## Next three implementation milestones

1. **Complete takeover hardening and development installation (Milestones A/B).** Atomic
   confirmation/state revision and recoverable audit persistence; pin browser tooling and
   establish disposable test isolation; prove target-version/Kadence behavior; perform Plesk
   inspection, approved deployment and restore drill. Exit: reviewed fixes and a measured,
   reproducible development install with rollback evidence.
2. **Complete the public website (Milestone C).** Owner-supplied original logo/photos, verified
   contacts/copy/Acuity, resolved owner notes, Kadence editor/header/footer, real-device and
   accessibility testing, privacy/SEO/redirect preparation. Exit: owner-approved development
   website, with production approval still separate.
3. **Complete emergency workflows (Milestone D), then household onboarding (E).** Build
   consent/delivery/outbox failure reporting before assistance/dispatch; verify MFA, caches,
   physical tags, mobile coverage, fallback procedures and drills. Exit: owner acceptance of
   tested emergency workflows. Next implement household/rider intake using legally reviewed
   templates and per-record authorization; do not build all portals simultaneously.
