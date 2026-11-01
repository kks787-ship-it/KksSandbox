# Issue #8 — Scheduling foundation local validation

Date: November 1, 2026 (America/Los_Angeles). Base branch snapshot:
`e78e2d5768d0281681692558e5e7baa65c1ef89e`, `codex/takeover-audit`.
The two concurrent documentation commits through `c00d842f43d1c0f6ebde1b2889bb1661aeadf4af`
were reconciled without altering their website findings; the new system ledger now records
only this foundation as local-only.

Candidate plugin: **csf-scheduling 0.1.0**, schema v1. No scheduling plugin, schema, user
grant or appointment was installed on the protected sandbox or production.

## Delivered and bounded

Eight PHP files provide the independent bootstrap, exact schema contract/migration, policy
validation, explicit staff authorization/provisioning, transactional audited foundation
mutations, private diagnostics and CLI. There are six new owned tables, with a singleton
facility write lock, UUID command/event identities and revision checks. Policy/resource
creation and staff provisioning are available only through explicit trusted staff/operator
calls. No public routes, appointment write/confirmation service, calendar UI, recurrence,
billing, mail, SMS, onboarding, emergency integration or commercial SaaS was added.

Activation performs no plugin-initiated database/schema writes, role creation or capability
grants. The real WordPress activation test observes **one unavoidable WordPress framework
update to `active_plugins`**, not a scheduling write. The no-op activation hook, schema preview
and bootstrap initiate zero scheduling writes. This distinction is explicit rather than a
claim that WordPress activation itself can leave its active-plugin option unchanged.

The reviewed exact DDL preview uses an inert `example_` prefix:
[scheduling-foundation-preview.txt](scheduling-foundation-preview.txt). Never execute it as
a substitute for the guarded CLI migration. Runtime/operator contract and Issue #9 handoff
remain in the [canonical architecture](../01-architecture.md#scheduling-foundation-v1-implementation--issue-8).

## Executed test matrix

All database tests used newly created local Docker containers on an **internal-only network**,
with no published ports. Their database was `csf_fixture` and their WordPress prefix `fixture_`.
They used fresh synthetic WordPress installation/configuration, not the protected site's DB,
configuration, secrets, uploads or users. Only the existing local WordPress core/WP-CLI files
were reused as tooling; the copied site's wp-content and wp-config were excluded. Cleanup
removed the database containers, test volumes and test networks after each run.

| Check | Result |
|---|---|
| PHP runtime | PHP **8.1.34** compatibility runtime, WordPress **7.1.4** |
| Full repository PHP lint | **77/77 passed** (69 existing plugin files plus eight scheduling files) |
| WordPress Coding Standards + PHPCompatibilityWP | **77 files passed**, no errors or warnings; scheduling text domain/custom capabilities included in root ruleset |
| MariaDB **10.6.28** | **87 integration assertions passed**, exit 0 |
| MySQL **8.0.46** | **87 integration assertions passed**, exit 0 |
| CLI preview / health and wrong-target apply refusal | Passed on both engines |
| Explicit production migration refusal | Passed on both engines, even with migration constant set |
| Two real simultaneous PHP migration workers | Both acknowledged, one completed schema and exactly one migration audit, both engines |
| Logical database backup / restore | Pre-foundation dump restored; owned tables and scheduling grants absent afterwards, both engines |
| Existing deployment/backup guard tests | 22 scenarios passed; mocked remote commands only |
| Existing Plesk identity guard tests | Six scenarios passed; mocked runtime only |
| Existing sandbox target guard tests | 14 passed; no connection to sandbox |
| Existing booking source audit | Two passed |
| New runner shell syntax and `git diff --check` | Passed |

The Docker PHP image compiles only the bundled mysqli extension, with no new production
dependency. PHP 8.1 is used for requested minimum-version compatibility testing, not a
recommendation to run an unsupported PHP release on the farm. Public image digests used:

- php:8.1-cli-bookworm: `sha256:482717722f08d9a6c2b4f98c40bfee6d7935bd1a17fef928881ecd5884dfd76e`
- mariadb:10.6: `sha256:f1c56f035ed268a0d7c2c2e06dc879f6319b997cb434cf822a4cfd72a7aa184d`
- mysql:8.0: `sha256:7dcddc01f13bab2f15cde676d44d01f61fc9f99fe7785e86196dfc07d358ae2b`

MySQL's command-line client prints a warning for the fixed synthetic test-only database
password. That value is neither a farm secret nor used outside the non-published throwaway
network. No real messages were sent; tests intercept any attempted WordPress mail as failure.

## Integration coverage

The 87 assertions cover activation and zero grants; preview read-only behavior; unsupported
storage; anonymous/editor/client/ungranted Administrator denial; capability-without-membership
and malformed allowlists; explicit ID/owner verification; removed staff; unconfigured and
missing-resource/capability readiness; alternate-facility Sunday-open/timezone/capacity policies;
strict types, impossible Academy dates, JSON object reordering and bad hours/workload; audited
resource/policy creation; actor-bound idempotent replay, payload mismatch and stale revisions;
failed audit/command inserts, private unexpected-exception redaction and rollback; failed capability persistence and replay repair;
UTC/local audit instant equality; invalid engines, missing indexes, changed defaults, future
schema version (including missing tables), interrupted DDL/seed/audit marker and retries;
refusal to adopt populated unversioned tables; data retention on deactivate/reactivate; and
unchanged synthetic website pages/menus, inquiry configuration and inactive emergency state.
No test fixture creates or allocates appointments.

The additional two-worker test exercises **migration concurrency**, not future appointment
resource-conflict correctness. The backup/restore rehearsal drops only the six owned fixture
tables before restoring the fixture's pre-migration logical dump; no destructive rollback
command is shipped in the plugin. Deactivation itself retains data. The original farm's
historical restoration evidence was neither used nor relabeled by these tests.

## Reproduce locally

Requires a local Docker daemon, the already available `.wp-local/site` WordPress core and
`.wp-local/wp-cli.phar`, plus existing locked vendor tooling. No installation script changes.

```bash
bash tests/scheduling/run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
```

Use the recorded image digests above when exact image-byte reproduction is required. The
runner uses explicit local Docker socket, unique container/network/volume names, an internal
network, bounded DB startup checks and cleanup on exit. It refuses missing local core tooling.
It never connects to a real WordPress installation or an externally supplied database URL.
The full test logs were inspected during this task; only this summarized synthetic evidence
and schema preview are committed, not dump files, credentials or local container state.

## Release limits and next work

The scheduling foundation has **not been deployed or migrated** on the farm. No Plesk,
production, DNS, inquiry enablement, emergency activation or existing customer-data changes.
Existing csf-core/csf-emergency source and backup/deployment/installation scripts are unchanged.

Policies and actual resources/staff IDs for CSF require owner review before any operational
configuration. The v1 migration constant and explicit CLI are not authorization to alter a
protected site. Readiness diagnostics are not claims of a usable calendar, safe resource
allocation, legally verified rider clearance, message delivery or emergency readiness.

Issue #9 is prepared for implementation in doc 01: versioned v2 schema, appointment mutation
and conflict/eligibility service, then day/week UI. Its two-worker appointment races, welfare,
rotations, clearance, DST, cancellation and browser scenarios are **not implemented or passed**
by this foundation. Recurrence and farm-specific recurring workflows remain later increments.
MFA policy, actual deployment approval, protected environment compatibility and real staff
acceptance remain separate gates. Persistent object-cache/failover/crash-at-network-COMMIT
matrices are not established by these local SQL-failure tests; no exactly-once claim is made.
