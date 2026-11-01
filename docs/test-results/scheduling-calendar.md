# Sprint #9 — Working staff calendar: local validation and release review

Date: November 1, 2026. Branch `codex/takeover-audit`, based on
`4c9abf339339185ce4e10cbd945cd23a989d025c`. Candidate: **csf-scheduling 0.2.0**, schema **v2**.

The candidate extends the committed foundation. No protected sandbox install, live migration,
production operation, deployment, inquiry enablement or emergency activation occurred.
The public website plugins and deployment/installation scripts are unchanged.

## Delivered

- Additive, explicit v1 → v2 migration: eight new tables, fourteen total. Exact physical
  verification, intact populated-v1 preservation, interrupted-DDL recovery, no automatic
  activation migration or role grants. [Inert DDL preview](scheduling-calendar-preview.txt).
- The foundation's existing singleton transaction, canonical actor-bound command ledger,
  optimistic revisions, audit and checked commit now cover roster, availability, protective
  restrictions and manual appointments. No separate booking transaction implementation.
- Requested, expiring held, confirmed and terminal canceled states; atomic edits and complete
  cancellation records. Lost commit acknowledgements recover via the original command;
  the staff UI retains that original command for an explicit retry.
- Conflicts for instructors, arenas, locations, riders and school/client horses. Explicit
  availability, travel buffers, service capacity, training enrollment cap, horse-to-service
  and horse-to-rider suitability, daily/consecutive workload and rest restrictions.
- Sequential shared-horse ride slots and client horses without fictitious school resources.
  Protective blocks can flag existing bookings, then be released with retained history.
- Private day/rolling-week views, resource filters, ordinary create/edit/confirm/cancel fields,
  independent pre-ride clearance warnings, and audit-capable history. Staff allowlist,
  capabilities, nonce protection and escaped output; no public routes or self-booking.

See the [implemented contract](../01-architecture.md#sprint-9-implementation--csf-scheduling-020--schema-v2)
for exact command shapes and operational boundaries.

## Executed validation

| Check | Result |
|---|---|
| PHP 8.1.34 syntax + WPCS/PHPCompatibilityWP | **80 plugin PHP files passed** |
| Foundation regression on MariaDB 10.6.28 | **87 assertions passed** |
| Foundation regression on MySQL 8.0.46 | **87 assertions passed** |
| Calendar/upgrade/permissions/failure injection on MariaDB | **128 assertions passed** |
| Same calendar suite on MySQL | **128 assertions passed** |
| Separate-process booking race, empty calendar, each DB | **One commit, one resource conflict** |
| Separate-process booking race, populated calendar, each DB | **One commit, one resource conflict** |
| Separate-process migration race, each DB | **One migration audit, verified schema** |
| Populated-v1 logical backup/restore, each DB | **Exact settings restored; intact v1 contract; v2 access denied** |
| Pre-plugin logical backup/restore, each DB | **No scheduling tables/grants; plugin inactive** |
| CLI preview/health, wrong URL, production environment | **Passed; production migration denied** |
| Chromium 154 private calendar smoke | **21 checks passed**, 390/768/1280px; zero JS errors |
| axe component accessibility scan | **Zero violations** in `.csf-calendar` |
| Existing deployment guards | **22 passed** |
| Mocked Plesk identity guards | **6 passed**; no Plesk command executed |
| Sandbox preflight unit tests | **14 passed**; no sandbox connection |
| Shell/Node syntax and `git diff --check` | **Passed** |

The populated-v1 fixture uses the retained exact v1 physical contract and synthetic foundation
rows, with the v1 marker restored before the explicit upgrade. It is not an imported live
database or a claim that an old plugin was deployed.

The real booking workers rendezvous at a file barrier in different PHP processes. Both start
with the same reviewed global revision. A losing stale revision is explicitly retried with
a fresh revision/new command to prove the existing allocation itself then rejects the
booking. Counts verify exactly one committed appointment per contested interval, both with
no existing appointments and with an already populated calendar. Sequential cases isolate
horse, instructor, arena and participant conflicts independently.

Failure tests cover stale/altered/other-actor replay, view-only/unlisted/anonymous identities,
missing confirmation capability, failed conflict reads, audit and command insertion failure,
interrupted commit, and a commit that executes but loses its acknowledgement. Migration
failure cannot stamp v2 prematurely. DST tests reject gaps, missing/wrong offsets and invalid
dates and distinguish both folds. Configured closed Sunday, Saturday-to-Sunday crossing,
Sunday-open alternate policy, capacity, training enrollment, hold expiry, shared-horse
rotation, client-horse off-site, welfare, workload/breaks and rider clearance are exercised.

Browser checks perform actual WordPress login, day/week navigation, responsive geometry,
creation, editing, explicit confirmation, cancellation and missing-nonce HTTP403. They also
check the authenticated admin entry, component accessibility and JavaScript errors. This is
browser emulation and automated accessibility, not physical-device or human acceptance.

## Fixture provenance and reproduction

Final runs use **unmodified WordPress 6.8.3** from the official
`wordpress:6.8.3-php8.1-apache` image, digest
`sha256:eeef49fcf96858412e393d73d7b2894610ade3af929da3975ae463584ff7ce46`.
Only its core is extracted into the ignored `.wp-local/scheduling-core` fixture; the actual
runtime is the existing PHP 8.1 mysqli test container. The earlier reused local 7.1 fixture
had unrelated admin bundle/dependency errors and was rejected for final browser acceptance.
No WordPress source was patched to suppress errors. The protected site's WordPress/Kadence
versions were not tested or changed here.

MariaDB and MySQL run in temporary local Docker containers on internal-only networks, with
DB storage in tmpfs and no published ports. No farm configuration, records or credentials
are imported. The web fixture is exposed to the browser only through a loopback socket relay
to the Docker container; the Docker daemon and executor have separate network namespaces.
All users, policies, horses and appointment names are synthetic. Browser requests are restricted
to the loopback origin and fixture avatars are disabled. The harness forwards all socket
bytes and waits for child output to close. Chromium `ERR_NETWORK_CHANGED` permits at most
two GET-only recoveries per navigation; it never repeats a booking POST or tolerates other
script/transport failures. The final output records any recoveries. A test theme stand-in is
used; no real-Kadence public website acceptance is claimed. Containers/networks/volumes are
removed by the runner on success or failure; the stock image/core cache remains local.

With stock core cached as above, existing `.wp-local/wp-cli.phar`, locked Composer dependencies,
Node/Playwright/axe and Chromium available:

```sh
CSF_TEST_BROWSER=1 bash tests/scheduling/run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
```

The runner never copies `.wp-local/site/wp-config.php`. Stock core preparation is a test
fixture operation: extract `/usr/src/wordpress` from the pinned official image to
`.wp-local/scheduling-core`, not the existing website. No environment onboarding,
installation script, environment publication or server configuration change is required.
[Bounded final output](scheduling-calendar-output.txt) records executed markers.

## Limitations and deployment readiness

**Ready for source/release review, not approved for real scheduling or protected deployment.**
This is a small-facility candidate with serialized writes, bounded calendar output and
application-validated JSON documents. Large-barn throughput, multisite/multi-tenant operation,
server crash/power-loss recovery, network partitions and physical devices are not proven.
Injected commit failures are not a claim of hardware failure testing.

Routine appointments use ordinary fields, but group/partial ride slots, roster, suitability, availability
and protective blocks still use an advanced structured editor. Dates require explicit UTC
offsets; same-day service intervals only. Horse workload conservatively includes reserved
travel time. Recurrence, Academy cohorts, geographical route optimization and automatic
policy scheduling remain later increments. View-only staff can read but not mutate; the
foundation's all-capability diagnostic can report incomplete grants for deliberately reduced
staff permissions. Actual MFA and identity provisioning need protected-site review.

Current rider evidence is manually verified booleans with history. It neither stores signed
agreements nor enforces an attendance/service-start workflow. It visibly distinguishes
calendar confirmation from pre-ride clearance. Sprint #10 must link canonical household,
guardian/rider/horse-owner identities and legally reviewed versioned signing evidence;
no onboarding/e-signature code is included now.

Before any separately authorized install: review this exact commit and schema preview;
verify current target-bound backup and actual restore procedure; compare scheduling-plugin
files only; preview migration against the actual installed state; approve facility policies,
resource/horse availability, suitability, staff IDs and access/MFA; apply only through the
existing explicit migration gate; then run protected-stack security/responsive/staff
acceptance with synthetic records before real appointments. No live apply is implied here.

Rollback uses the matching pre-change logical DB backup and reviewed plugin version; it
is not a destructive automatic down migration. A restored v1 database with v2 code fails
closed until the matching version is restored or the reviewed upgrade is reapplied. An
old v1 plugin also fails closed on a v2 marker. Preserve post-upgrade records before any
rollback: the local fixture restore proves mechanics, not permission to discard live data.
Inquiry remains disabled, emergency inactive, production excluded.
