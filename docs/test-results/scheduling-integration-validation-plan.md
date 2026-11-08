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
