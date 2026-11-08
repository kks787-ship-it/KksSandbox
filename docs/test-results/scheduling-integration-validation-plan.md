# Integration validation plan — remaining runtime gaps

Use the final draft-PR head SHA and its scheduling-continuation-inventory.json as
the review baseline. Record any subsequent fix as a new commit and rerun affected
checks. No command below targets the farm. Do not copy farm data/configuration,
credentials, private backup bindings or MFA sessions into the test environment.

## 1. Provision a disposable runner, then verify prerequisites

Required: native PHP 8.1 and 8.3 images, Docker, MariaDB 10.6 and MySQL 8.0 test
images, Node/Chromium/Playwright with permitted loopback sockets, Python, Composer
and locked development dependencies. Record exact versions/image digests. Use
repository lockfiles and existing fixture instructions; do not install system-wide
packages on the farm or weaken its security. The current executor has only PHP
WASM for isolated execution, no database/container runtime; its Chromium cannot
create required sockets and the cloud browser rejects local-file URLs.

`tests/scheduling/run.sh` requires unmodified core at `.wp-local/scheduling-core`,
WP-CLI at `.wp-local/wp-cli.phar`, fixture PHP images and `vendor` from composer.lock.
It creates only synthetic csf_fixture DB/fixture_ tables on an internal Docker
network, with read-only repository mounts. Confirm these guards before execution.
The temporary auth fixture must never be deployed to the farm. Preserve historical
reports; save new raw output/screenshots under a new candidate-specific directory.

Run PHP syntax and `php vendor/bin/phpcs --standard=phpcs.xml.dist
wp-content/plugins/csf-scheduling` first. PHPCS is currently unrun and may require
formatting fixes; parser success is not a coding-standard pass.

## 2. Full database matrix

From repository root, execute separately and retain each complete log/exit code:

```sh
CSF_TEST_PHP_VERSION=8.1 CSF_TEST_DB_IMAGE=mariadb:10.6 bash tests/scheduling/run.sh
CSF_TEST_PHP_VERSION=8.3 CSF_TEST_DB_IMAGE=mariadb:10.6 bash tests/scheduling/run.sh
CSF_TEST_PHP_VERSION=8.1 CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
CSF_TEST_PHP_VERSION=8.3 CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
```

These commands already include foundation/migrations, calendar, family, horse,
permissions and existing races. Newly authored DB assertions cover group rollback,
ledger failure/replay, shared allocation windows, common hold expiry, immutable
notice snapshots, deletion rollback/tombstone/replay and closure-buffer impact.
They have NOT executed here. Stop at first genuine failure; fix and rerun the
specific suite, then the matrix after dependent changes. Do not waive stale fixed
2027 fixture dates: migrate them consistently if execution date reaches them.

Additional acceptance cases still need fixture assertions or a recorded controlled
run (not falsely counted as present automated coverage):

| Case | Required outcome/evidence |
| --- | --- |
| Two confirmed family groups contest identical resources | One whole group wins; loser stale/conflict; zero partial children/notices |
| Held group expiry / concurrent renewal or independent claim | Common expiry releases all windows; renewed/confirmed group revalidates; no expired sibling leaving exposed shared buffers |
| Delete unused horse vs new booking | One serializable outcome: booking wins and delete rejects, or delete wins and booking finds no horse; all history/tombstones consistent |
| Every dependency category / injected SQL or final-ledger failure | All 15 tables identical on rejection; no deleted or half-created records |
| A-to-B instructor reassignment then two further reschedules | A gets old-time-only departure; B/oversight get entitled event; original dates remain immutable; no A link/new private fields |
| Revoked/inactive member, assigned-only, read-only and forged recipient | No private HTML/JSON or historical notices beyond original minimal entitlement; denied MFA/capability mirror remains denied |
| Exact replay vs changed payload/actor | No duplicate children, audit notices or accepted sink event; changed envelope rejects |
| School/client horse correction, archives, deletion/restoration | Used mapping blocked, archives unbookable, deletion only unused, tombstone UUID cannot resurrect; associated resources unchanged |
| 30+30 and 120+120+60, breaks, travel, horse recovery | Itemized teaching preserved; only compatible internal optional buffers merge; mandatory minima/welfare never waived |

## 3. Browser and HTTP acceptance

After database success, run `CSF_TEST_BROWSER=1` with the same PHP/DB environment
through run.sh. Existing browser scripts cover the established planner/catalog/
resource UI; extend them for new family/deletion/notification flows before claiming
complete automated coverage. Use the guarded local browser relay only.

Capture 390, 768 and 1280px screens plus keyboard/screen-reader checks for:
(1) grid click -> Student 30/60 -> derived finish -> one explicit save;
(2) day closure, weekly split hours and affected-buffer warning;
(3) original lesson -> Book another -> sibling detail -> combined review/save;
(4) multi-rider turns, gap, expired common hold, renewal and cancel one child;
(5) unused deletion exact-name/irreversible confirmation and dependent rejection;
(6) dashboard family windows, ordinary non-red group and real red override;
(7) A/B/oversight notices, immutable history and acknowledgements.
Count user actions and scroll jumps; verify focus return, labels, tab order, tap
sizes and no horizontal overflow. Verify no-JS fallback behavior explicitly.

HTTP tests must cover wrong/missing nonce, forged signed review, changed session,
stale facility/child revisions, revoked rights between preview and save, concurrent
submit/replay and uncertain response recovery. Assert no private cache/public routes.
Native MFA validation uses the separate existing `tests/scheduling/mfa-run.sh`
fixture and approved provider artifact; synthetic hooks do not establish real owner
MFA acceptance. Do not reset MFA or make real grants to obtain a passing test.

## 4. Recovery qualification

Run `PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests/recovery -p
 'test_*.py'`, verify all public recovery checksum files, and rerun guard scripts.
Then satisfy the exact pinned image/provider/core/Python prerequisites in
`tests/recovery/restore-local.sh`; do not relax its pins to make it run. Execute its
pre/full/partial/damaged phases separately for PHP variants 81 and 83 in the offline
container. Require 27-table source/data equality including tombstone/family/override/
notice metadata, intact auth/MFA, functional pages/login/logout, no outbound mail or
network, preserved newer source data, cleanup, failure containment and replay refusal.
Add an explicit mismatched v3-source/v4-data fixture and require readiness failure.
A Python unit pass or structural extraction alone is not functional recovery.

## 5. Review and protected acceptance gates

Only after candidate validation: reconcile independent review, review exact payload
and source/data recovery pairing, establish fresh complete backup (<24h) and usable
isolated functional recovery, perform transparent target preflight, and follow the
existing separately authorized deployment and independent readback process. This
plan does not authorize deployment or a farm fixture. Verify actual saved/enabled
Student 30/60 policy via authorized read-only inspection, then obtain exact owner
configuration review before any policy write. Only actual owner workflow acceptance
can close §1B/§6D. Real delivery, grants, payments, public/emergency booking and
production remain separate holds. Do not merge the draft PR as a substitute.

## Disposable CI follow-up (2026-11-08)

The review branch now proposes `.github/workflows/scheduling-disposable.yml`:
standard Ubuntu 24.04 GitHub-hosted runners, PHP 8.1/8.3 × MariaDB 10.6/MySQL 8.0,
25-minute job bound, two concurrent jobs, read-only repository token, no persisted
checkout credentials, secrets, caches, uploaded artifacts, farm access, or deployment.
It invokes the existing internal Docker fixture including its PHPCS gate unchanged.
WordPress 6.8.3 is fetched from wordpress.org and checked against its published SHA1;
WP-CLI 2.12.0 comes from the official GitHub release. These are test fixtures, not
installed-version acceptance. Browser/MFA and functional recovery remain separate
pending work. A proposed workflow is not evidence that GitHub has accepted or run it.

The latest notification navigation fix preserves immutable display snapshots but
computes destinations from the currently visible appointment in the facility timezone.
Departure/historical-only projections expose no current destination. All 34 isolated
notification tests passed on PHP 8.1.34 and 8.3.36, including multiple later reschedules
and UTC/local date boundaries. Database/browser acceptance is still pending.

### Executed disposable CI evidence

GitHub Actions runs 42457998962, 42458179716 and 42458327248 prove that
standard hosted runners can install locked dependencies and run the real synthetic
WordPress 6.8.3 fixture with MariaDB/MySQL. Foundation integration passed 88
checks before the calendar phase. Initial runs stopped at PHPCS; the runner now
records its nonzero result, runs independent database checks, and still fails if
PHPCS failed. No gate was disabled.

Calendar integration exposed legacy assertions: back-to-back teaching now correctly
conflicts with independent 15-minute setup/cleanup; the fixture now rejects 10:00
and 10:29 and accepts 10:30 after a 09:00–10:00 lesson. This passed in the next
MariaDB run. The used-horse remapping fixture now selects a fresh resource so the
used-mapping guard is tested independently of the earlier duplicate-resource guard.

The exact lockfile PHPCS tools also run locally under temporary PHP-WASM. PHPCBF
fixed 654 mechanical violations across 15 files; parsed ASTs were identical before
and after formatting. All 397 isolated checks passed on each PHP 8.1.34/8.3.36
after formatting. Remaining PHPCS: 161 errors and 23 warnings (mostly missing
documentation plus input/SQL lint review). These remain failures, not waived checks.
Recovery rerun: 283 tests passed with PYTHONDONTWRITEBYTECODE=1; an initial run
without it generated __pycache__ directories and failed the strict package-file
allowlist. Only generated bytecode was removed; no test was relaxed.

Full database matrix, browser/MFA and functional recovery are still not accepted.
No farm systems, accounts, notifications or deployments were changed.

### Continued qualification

The scheduling PHPCS ruleset now passes locally under the exact lockfile tools.
No new phpcs exclusions, ignored diagnostics, relaxed rules, or bypass flags were
introduced. Custom-table coordination reads and reservation cleanup are consolidated
in the existing Calendar repository, which retains its pre-existing documented
uncached transaction SQL policy; family and notice callers retain their child/recipient
authorization projections. Read-only HTTP filters are explicitly sanitized.
Missing parameter, exception, property and class contracts are documented, and the
fixture email sink and versioned adapter now have separate class files. Neither is
loaded by the plugin bootstrap.

Real CI run 42459087709 passed 45 family, 66 usability, booking/group/archive/family
races and earlier foundation/calendar/horse suites before a fixed permission-count
assertion failed. The fixture now checks every registered permission is denied on
MFA failure, including the additional default-deny buffer override permission.
It does not change MFA or authorization behavior.

The next workflow uses pinned Playwright 1.65.0, its managed Chromium executable,
and the existing official Two-Factor 0.17.0 ZIP verifier (archive plus all 21 file
hashes). Browser/MFA fixtures intercept wp_mail and use internal Docker networking.
Independent browser steps still run after a database failure to gather evidence;
failed steps remain failed. Functional recovery invokes the unchanged qualifier
with exact image and Python binary pins. Missing/mismatched prerequisites are a
failed qualification, never permission to weaken pins or use farm resources.

### CI follow-up: fixture prerequisites and resource ordering

Run 42459852811 passed PHP8.1/MySQL8.0, including unchanged PHPCS. Other matrix runs exposed a random-UUID-dependent family assertion: the approved buffer resource is now selected by UUID, and all other buffers are checked unchanged. No booking rule was weakened. Browser preparation verified official provider bytes, but activation correctly rejected WordPress6.8.3 for the MFA adapter's existing >=7.0 requirement. The isolated MFA site now uses the previously tested official WordPress7.1.4, pinned to published tar SHA1 b175879c4fb2d35300f9abe1153da9b543a35a2b; the independent database fixture remains6.8.3. Activation diagnostics are retained. Hours/browser fixture times now include the approved independent15+15-minute buffers. These corrections require a fresh CI run.

Functional recovery qualification found both exact container image IDs available, but /usr/bin/python3.13 was absent on ubuntu24.04. The required binary hash remains889c603f0d17cb54060951bcf4c4f9b8c9ebd9e52b392c70209bbb9755d797d9; no prerequisite was relaxed and no functional restore is claimed. No farm, deployment, actual account/MFA, delivery or payment change occurred.

### Native browser follow-up

Both PHP8.1 database cells passed run42460326836. A source review found the compact block modal handled the first endpoint before a two-endpoint range could finish; the resource handler now prevents that opening until selection completes. The existing range keyboard/focus assertion remains required. Hours coverage now navigates the current Manage Hours screen and Block Time controls, including Add Break and conditional affected-booking acknowledgement. A new real-provider family browser case covers no-write review, tampered signature, atomic save, exact replay, authorized saved-visit query and edit without duplicate lessons. JS/workflow syntax passes; native results are pending CI. The isolated family command suite reran89checks successfully on PHP8.3.

The runner preserves a nonzero browser result while continuing the independent migration/restore assertions; neither a successful second browser nor successful restore can erase the first failure. PHP8.3/MySQL also passed run42460326836. PHP8.3/MariaDB reached browser tests but final logs are pending; it is not yet a complete passing matrix.

Run42460326836 completed: three database jobs passed; the PHP8.3/MariaDB browser-enabled job failed its stale heading assertion after database concurrency checks. The real-provider suite independently passed50MFA and79security browser checks on WordPress7.1.4/PHP8.3.36/MariaDB10.6.28, including disabled-adapter denial. Native UX created and edited lessons, then its duplicate fixture overlapped the newly buffered second lesson; the draft moves to an unoccupied15:30 slot. Resource-hours browser reached and passed its domain suite but used the retired combined-screen entry; current-screen navigation is corrected. Required tests remain active; next-run results are pending.

Recovery runtime source investigation: /usr/bin/python3.13 in this executor matches the existing pinned889c603... hash (3.13.5, built2026-08-10), but this executor has no Docker runtime. The official Debian snapshot python3.13-minimal3.13.5-2+deb13u4 archive was downloaded to an isolated temporary directory, verified against published SHA1 74e55d896b26f35fffd8863b6c23d5c47491f2a5 and extracted without installation/execution. Its interpreter SHA256 is17b78e0a93175e86f9ac03141924fd7a7f0c0c52e66b34bfa0de20ffef989df1, not the required pin. No substitution, package install or relaxed qualification followed. A permitted runner with the original qualified interpreter/libraries and Docker is still needed.

Run42461246411 completed all four database combinations through transaction/race/migration/logical-restore assertions (PHP8.3/MariaDB still red because required browsers failed). Full PHPCS remained clean. Real-provider MFA50/security79 passed again. Recurring-hours acceptance passed32browser+49domain checks, the closure/booking race and catalog logical restore. Family browser passed no-write review, signed payload, tamper rejection, atomic two-lesson save, replay and authorized saved-visit readback; its invalid15-minute separated edit was correctly rejected. The updated case asserts that rejection then uses30minutes.

The accessibility audit caught role=status on a notice article, disallowed by https://www.w3.org/TR/html-aria/#el-article. The article now retains native semantics with aria-live=polite and aria-atomic=true. No accessibility rule was disabled. Native UX's Friday split fixture had been inadvertently changed to a15:30 first finish before a13:00 second start by the earlier duplicate-slot fixture replacement; it now explicitly sets12:00. Older advanced/permission browser action/title selectors also match existing current UI text. All assertions remain required; final browser rerun pending.
