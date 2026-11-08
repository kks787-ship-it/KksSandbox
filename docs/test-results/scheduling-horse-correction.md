# Unused horse classification correction — 0.4.10-dev

November 8, 2026. **Unreleased source increment; no sandbox deployment.**
Related: Issue #11, owner-approved master specification §4. This implements only
unused-entry classification/resource correction, not the complete horse lifecycle.

## Implemented

Resource-management staff can edit an existing horse's booking classification and
school resource in Farm Setup. Labels explicitly distinguish booking classification
from legal ownership. The existing `horse_saved` command preserves the horse UUID,
revision, before/after history and audit. The existing field-level permissions,
MFA, facility lock, command replay, training cap, suitability and unique active
school-resource checks remain in place. No permission grant or schema change occurs.

A changed mapping is accepted only when unused. Under the same facility lock it
checks appointment details, other entity history references, horse/old/new resource
restrictions, signed horse slot identities and positive resource allocations. Query
errors fail closed. Requested and canceled appointment references are protected,
as are released restriction records. Ordinary non-mapping edits remain available.
The UI gives a specific dependency error and retains the existing draft/retry path.

This is intentionally conservative: any such history blocks remapping. Used-horse
correction, affected-booking reconciliation, archive/restore and conditional deletion
remain pending. Do not remove real history to make this guard pass. Family visits,
shared buffers, dashboard and notification work remain pending as well.

## Validation actually run

- Official WordPress `@php-wasm/cli` 3.1.62, installed only in a disposable temporary
  directory with lifecycle scripts disabled; no system packages or Docker service.
- PHP 8.1.34 and PHP 8.3: all 100 plugin PHP files parsed with `TOKEN_PARSE`.
  Changed plugin files and the database integration test also passed `php -l`.
- 26 isolated guard checks passed on each PHP version. These exercise both mapping
  directions, each dependency category, query-failure rejection and rejection before
  any database read when outside the transaction dispatcher. Database responses are
  mocks; this is not a MariaDB/MySQL concurrency result.
- JavaScript php-parser 3.7.0 parsed the initially changed PHP files as an additional
  static check. Shell runner syntax and `git diff --check` passed.
- Prior reconciliation checks: 280 Python recovery tests, 22 mock deployment guard
  scenarios and 6 mock preflight scenarios passed. They were not live recovery tests.

## Tests prepared but not run

`tests/scheduling/horse-correction.php` is wired into the existing disposable database
runner. It covers correction in both directions, stable UUID, before/after history,
exact replay, stale edits, invalid/duplicate resources, failed-query rollback,
used-horse rejection, read-only permission denial, protective-block history and
ordinary used-horse edits. It compares all scheduling rows after rejected commands.
The separate isolated guard suite is also wired into that runner.

**MariaDB/MySQL integration, real two-worker correction-versus-booking races, browser
acceptance, and PHPCS remain unrun.** No native PHP/Composer/Docker runtime was present.
The system-wide installation was rejected by automatic review and was not retried.
The later approved WebAssembly alternative provides syntax/unit execution only; it
does not replace the required database engines or prove transaction isolation.
No `.github` workflow exists in the checked-out baseline, and its GitHub combined
status response contained no status checks. No new paid CI infrastructure was created.

## Retest and rollout gates

On the disposable fixture, run the normal `tests/scheduling/run.sh` matrix first,
including MySQL/MariaDB and browser coverage. Add/verify a real simultaneous booking
and correction race: one command must become stale or reject the dependency, never
leave a booked horse using inconsistent slot identities. Verify unauthorized forged
POSTs, resource-only versus suitability-only staff, and mobile/keyboard editors.

Only after validation and the separate recovery-qualified deployment conditions,
use fictional unused horses: client → school with a unique active resource; school
→ client with no school resource. Confirm the same horse UUID and retained history.
A horse with a booking or protective history must reject remapping without any
state/audit/command changes. Do not test by altering actual farm horses.

No server call, policy mutation, MFA change, staff grant, deletion, email, payment,
public booking, emergency activation or production action occurred. Current backup,
isolated recovery, transparent preflight, exact reviewed payload and separate readback
must be verified before any sandbox write. The existing 0.4.9 installation remains
unchanged; owner native-authentication and actual Student 30/60 acceptance remain open.
