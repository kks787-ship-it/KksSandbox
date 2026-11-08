# Horse archive/restore — local 0.4.11-dev candidate

November 8, 2026. **Local implementation only; not deployed or migration-approved.**
Extends the unused-entry correction increment in `6d0434f`. Issue #11 / master spec §4.

## Implemented behavior

- Farm Setup has named Archive/Restore controls with a reason and typed current
  horse name. `horse_status_saved` requires effective `resource_manage`, ordinary
  MFA, nonce, facility revision, horse revision, transaction and actor-bound replay.
- The horse UUID, classification, suitability, enrollment, restrictions, appointments
  and earlier history remain. Status changes append before/after/reason history and
  the ordinary command audit. No record deletion or automatic cancellation occurs.
- Future requests, holds (including expired holds until explicitly reconciled) and
  confirmed lessons prevent status changes. A failed dependency read fails closed.
- Archived horses leave new booking selectors and active training-cap counts. The
  server booking gate rejects them even if a caller forges the selector. Historical
  labels remain readable; past/canceled display does not become ineligible solely
  because the horse was later archived.
- Ordinary horse edits preserve the stored archive state; callers cannot submit an
  `archived` field to bypass the lifecycle command. Restore rechecks recorded rider
  and service references, active unique school resource and availability document,
  active training cap and current protective blocks. Normal booking-time availability,
  suitability and welfare checks still apply after restoration.

## Compatibility marker and recovery hold

The data contract advances explicitly from schema 3 to **schema 4**, retaining the
same 15 scheduling table definitions. This marker is required because 0.4.9 would
not understand archive eligibility. New code denies normal access until explicit
migration; old schema-3 code must deny a schema-4 database and refuse downgrade.
The marker and migration audit use the existing migration transaction. Existing
policy, identities and grants are not regenerated on the v3→v4 path.

**No migration ran against WordPress or a real database in this session.** The
existing recovery baseline classifier in `docs/pilot/baseline-select-plan.py.txt`
recognizes complete scheduling v3 but treats marker 4 as `partial_migration`.
Its signed/generated recovery payloads remain unchanged. This is an explicit
rollout blocker: prepare and review schema-4 recovery classification, regenerated
payload hashes and an isolated restore rehearsal before proposing deployment.
Do not weaken or bypass the current validator. A source-only rollback to 0.4.9
is not a supported rollback after this migration. Never lower the marker or restore
over newer owner edits; use a separately reviewed recovery plan and fresh backup.

## Validation actually executed

- 33 isolated lifecycle checks passed under PHP WebAssembly **8.1.34 and 8.3.36**.
  They call the actual lifecycle and booking code using an in-memory database double:
  authorization/transaction gate, typed confirmation, stale version, empty reason,
  future dependency/read failure, history retention, archived booking rejection,
  restore, missing suitability/resource references, training capacity and active
  welfare block rejection. These are not real SQL/isolation tests.
- All 26 unused-horse mapping guard checks still pass on both versions.
- All 100 plugin PHP files parse on both versions; new database and race-worker PHP
  files pass syntax checks. The shell runners and `git diff --check` pass.
- 12 existing scheduling-recovery Python tests pass with local temporary-file access.
  They verify the existing v3 recovery contract, not schema-4 recovery acceptance.

Tooling remains temporary official WordPress WebAssembly PHP, with no system PHP,
Composer or Docker installation. No farm connection or outward notification occurred.

## Prepared database coverage — NOT RUN

`horse-lifecycle.php` covers populated v3→v4 migration, failed-audit rollback, exact
all-table preservation, pinned 0.4.9 schema-code rejection of marker 4, idempotent
archive, ordinary-edit preservation, future booking rejection, permission denial,
restore and active training-cap behavior. The runner extracts the exact baseline
schema source from `22657b8` into a disposable fixture for the old-code test.

`horse-lifecycle-races.sh` and its worker race actual booking versus archive. Expected
outcomes: booking wins and archive rejects future bookings, or archive wins and the
booking rejects an archived horse. A stale worker retries with a newly reviewed
fixture revision; only one action may commit. This is authored, not an executed race.

MariaDB/MySQL migration/integration/races, protected/browser accessibility acceptance,
PHPCS, and schema-4 recovery rehearsal remain required. Do not describe this as a
release-ready build. Future-booking detail/impact presentation remains a UX follow-up;
current rejection preserves every booking but does not show a dedicated impact list.

## Remaining approved work

Used-horse classification/remapping, conditional unused-entry deletion, family visits,
shared outer buffers and override auditing, dashboard and notification infrastructure
remain pending. Real email, staff grants, public booking, payments, emergency operations,
production and account/MFA changes remain held. The rejected branch push was not retried.

## November 8 audit increment — conditional unused deletion

0.4.18-dev adds locked `horse_deleted` with exact-name, reason and explicit irreversible
confirmation. It retains setup/history and a UUID tombstone, preserves associated
resources, rejects dependencies/lifecycle history and blocks UUID resurrection.
34 isolated checks pass on PHP 8.1/8.3; DB rollback/replay cases are authored unrun.
No real horse was deleted. See [master audit and gaps](scheduling-master-spec-audit.md).
