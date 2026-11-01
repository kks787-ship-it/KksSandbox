# Sprint #9.1 — staff usability validation and pilot readiness

November 1, 2026 · Issue #10 · `codex/takeover-audit` · base
`e2e266e29be8ddf11df7919e6cdb7a7e894e75e2`.
Candidate **csf-scheduling 0.2.1**, retaining **schema v2 / 14 tables**.

**Local development passed; protected pilot and Beth/Kris acceptance remain unapproved.**
No protected sandbox connection, install, migration, provisioning or deployment occurred.
Production, public booking, inquiry settings and emergency activation were not changed.

## Delivered

Native fields now cover multiple riders, a horse pool, individual rotation/partial-turn
rows, recovery-minute proposals, rider/horse suitability, resource creation/maintenance,
availability intervals, protective blocks/releases and cancellation. There is no staff
textarea/structured-data editor. Initial owner policy/account provisioning is still operator
work, not a daily task delegated to Beth.

Ordinary dates use local date/time controls. The server provides valid instants, rejects
spring gaps and requires an explicit first/second fall-back occurrence. Existing instants
are presented in the current facility zone without moving them. A single reviewed render
snapshot binds displayed timezone/policy to the form revision; a concurrent policy change
therefore requires a refresh rather than silently reinterpreting old inputs. An explicit Move action
shifts end/turns together; an unrelated title edit preserves roster and turn values/order.

The new private preview executes the existing transaction/constraint path and rolls back.
It creates no reservation, audit, command result or persisted row. A save checks again under
the original facility lock, permissions, actor-bound command UUID and expected revisions.
InnoDB may consume auto-increment values on rolled-back previews; opaque ID gaps are normal.
Named conflicts refer only to selected resources/riders, not another family's appointment.

Failed edits remain in the authorized response. Unknown outcomes preserve an exact normalized
command for replay, including after a later facility timezone change. New/altered commands
still pass the original scheduling service. Private filters stay in nonce-verified POSTs;
no client-side local storage or protected records in public routes/URLs are introduced.

[Implemented architecture](../01-architecture.md#sprint-91-staff-usability--021-schema-v2-retained)
· [Beth/Kris synthetic walkthrough and screenshots](../13-scheduling-staff-acceptance.md)
· [Bounded executed output](scheduling-staff-output.txt)

## Final executed results

| Validation | MariaDB 10.6.28 | MySQL 8.0.46 |
|---|---:|---:|
| Existing foundation assertions | 87 passed | 87 passed |
| Existing v2 calendar assertions | 128 passed | 128 passed |
| Staff usability assertions | 66 passed | 66 passed |
| Empty + populated two-process booking races | 2 passed | 2 passed |
| Isolated group horse/instructor/rider/incomplete-data races | 4 passed | 4 passed |
| Two-process migration race | Passed, one audit | Passed, one audit |
| Populated-v1 logical restore | Passed; v2 access denied on v1 | Passed; v2 access denied on v1 |
| Pre-plugin logical restore | Passed; no scheduling tables/grants | Passed; no scheduling tables/grants |
| CLI/URL/production environment guards | Passed | Passed |

**562 database assertions total** (281 per engine), plus the separate concurrency/restore
checks above. Each booking race has independent PHP processes and a barrier. A losing stale
review retries with a new reviewed revision/command and is rejected by the actual allocation
or incomplete-horse gate. Exactly one appointment commits. Horse, instructor and rider races
use different other resources to isolate the intended collision; the rider case rejects on
participant allocation, not an accidental shared arena.

| Other checks | Final result |
|---|---|
| PHP 8.1.34 lint + WPCS/PHPCompatibilityWP | **82 plugin files passed** |
| Chromium 154 staff walkthrough | **59 checks passed**; zero unexpected JS/transport errors; deliberate connection-loss case passed; one bounded GET-only transport recovery |
| Responsive staff interface | **390 / 768 / 1280px**; no horizontal overflow, four preserved rotation rows |
| axe component accessibility | **Zero violations** at each width with group editor open; final mobile scan also passed |
| Existing deployment guards | 22 passed |
| Mocked Plesk identity guards | 6 passed; no Plesk action |
| Sandbox preflight units | 14 passed; no sandbox connection |
| Public booking source regression | 2 passed |
| Core/emergency/deployment source diff against base | **Zero changes** |
| Node/shell syntax and `git diff --check` | Passed |

Only the old partial-ride presentation assertion changed: it now checks the preserved native
09:30 input and ride-row container instead of expecting the removed structured editor.
The existing 128 calendar assertions otherwise remain in place. The race worker accepts an
additional pair of fixture bodies; the original empty/populated cases are retained.

## Preservation and failure evidence

- Preview and render snapshots compare **all rows in all fourteen tables**, including seeded
  non-empty welfare restrictions, an existing canceled appointment, rosters, assignments,
  allocations, history, audit and command results. No row changes are acknowledged by preview.
- The UI title-only group edit preserves every original rider and partial turn value. The
  canceled state, notice and history are not converted into an editable confirmed appointment.
- Reduced permissions, generic administrator/anonymous denial, confirmation capability,
  nonce rejection, missing availability/suitability, service capacity, training enrollment,
  daily/consecutive/break limits, hold expiry and existing permission/schema rejection tests pass.
- Existing lost-acknowledgement, failed conflict-read, audit/ledger failure and interrupted
  commit tests pass. New exact-UI replay survives a subsequent timezone policy change without
  changing data. Malformed presentation fields do not become a rendered arbitrary draft.
- DST tests include Los Angeles gaps/folds, London fall-back and Lord Howe's half-hour fold;
  forged/missing choices cannot silently choose an instant. Browser gap/fold controls pass.
- An explicitly simulated preview connection failure preserves the typed draft and leaves Save
  disabled. No booking request is automatically repeated; staff can check the schedule again.
- Public Contact Beth to Schedule survives forced legacy settings. Inquiry remains disabled;
  emergency stays inactive; no scheduling REST namespace or anonymous time/preview handler exists.

## Fixture and reproduction

The standard [local runner](../../tests/scheduling/run.sh) uses the existing PHP/mysqli image,
stock **WordPress 6.8.3** core and a test theme stand-in, on disposable internal-only Docker
DB networks with tmpfs database storage. No farm configuration, credentials or real records
are copied. The stock core image provenance/digest is recorded in the preceding
[calendar report](scheduling-calendar.md#fixture-provenance-and-reproduction).

The web fixture has no published port. A loopback socket relay forwards complete data through
the local Docker socket, waiting for child output to close. Browser requests are restricted
to loopback; avatars are disabled. The harness allows bounded **GET-only** recovery for
Chromium `ERR_NETWORK_CHANGED`, never repeats a booking POST and fails all other errors.
**Final execution used one GET-only recovery for a managed-network change.** No booking POST was repeated. Standard runner cleanup removes its containers,
networks and site volumes; test images/core remain cached locally. No environment onboarding,
installation-script changes or environment publication were performed.

```sh
CSF_TEST_BROWSER=1 CSF_SCREENSHOT_DIR="$PWD/docs/screenshots/scheduling-staff" bash tests/scheduling/run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
```

Screenshots show fictional Pilot records only. Browser emulation/axe checks are not physical
phone/tablet testing, manual screen-reader testing or an independent Beth/Kris signoff.
The protected site's actual WordPress/Kadence/MariaDB stack was **not** tested here. Existing
website visual reports and owner-reported protected inspections are not reruns of this code.
Emergency activation/integration suites were not run: emergency source is unchanged and
activation remains excluded by the requested scope.

## Remaining operational blockers

1. Separate review/approval for the exact scheduling-only source, fresh target-bound backup,
   guarded schema install and owner-verified pilot staff IDs/MFA. The canonical deployment
   runbook contains the pinned pilot proposal, not an instruction executed in this task.
2. Confirm actual timezone, service hours/capacities/durations, required resources, Academy
   setting, training cap, school/client horse sources, horse/rider suitability, resource
   availability, workload/break limits and travel buffers. No real policy values are inferred.
3. Verify actual protected-stack compatibility, auth/privacy/cache behavior, rollback and
   synthetic-record workflows before any real appointment. No protected database was migrated.
4. Beth/Kris complete the [unfilled human acceptance checklist](../13-scheduling-staff-acceptance.md)
   on actual devices. Every local pass is an automated development result, not their approval.
5. Initial policy and staff provisioning still require an authorized operator. Availability
   is explicit finite intervals; large-roster pagination/performance, drag/drop, recurrence,
   Academy cohort generation and route optimization are not delivered. The two-step school
   horse/resource association is explained by native selectors; client horses need none.
6. Evidence flags are manual, not legally signed documents or automatic service-start
   enforcement. Household onboarding, guardian/owner relationships and legally reviewed
   versioned agreements remain Sprint #10. No fees, payments, email/SMS or emergency integration.

**Readiness:** reviewable scheduling-only candidate for a separately approved synthetic pilot.
Not approved for operational scheduling, protected installation, production or real client data.
