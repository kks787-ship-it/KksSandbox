# Optional buffer exceptions — local 0.4.16-dev candidate

November 8, 2026. Local source only. No real permission grants, publication,
deployment, migration, account/MFA changes or booking writes.

The native lesson detail screen provides a signed preview/final approval for one
optional resource segment. The dedicated `buffer_override` right defaults off in
all presets and for the master; a separately reviewed explicit grant is required.
The actor must also have confirmation authority and current appointment visibility.
Only future confirmed appointments with a saved reservation snapshot qualify.

The server permits a decrease of one preparation/wrap-up segment, bounded by
mandatory travel and teaching time. The command reuses the normal appointment
writer inside the existing facility lock, revision check, ledger and audit
transaction. It cannot waive occupied time, resource/facility closures, horse
eligibility, protective blocks or welfare. Preview rolls back. Exact successful
replay cannot create another approval or notice. The command records the reason,
approver, timestamp, facility/appointment revisions and exact before/after windows.
Family membership remains unchanged. Ordinary single or whole-family saves restore
standard buffers and remove current exception markers; prior approvals stay in
history. Only actual saved overrides display the red textual BUFFER OVERRIDE
marker. Normal family sharing is not labeled an exception.

## Validation actually run

On existing temporary WordPress Playground PHP runtimes 8.1.34 and 8.3.36:

- 63 family/command checks, including explicit-right denial, one-segment changes,
  mandatory minimum/teaching protection, restrictive-block rejection, context
  cleanup, retained family membership and standard-buffer recomputation.
- 20 notification/permission checks, including master default-deny, no preset
  grant, explicit grant and deny precedence, using the real effective-rights code.
- Full isolated suite: 291 checks per PHP version (103 planner, 63 commands,
  26 horse correction, 33 lifecycle, 17 recurring hours, 20 closure edit,
  9 dashboard and 20 notifications). Boundary doubles are not database isolation.
- 162 plugin/test PHP files parsed for both PHP versions with php-parser 3.7.0.
- Synthetic PHP rendering of the real family/dashboard/exception components
  produced 24 labels and 19 inputs without PHP warnings/fatal errors. This is
  rendering smoke coverage only, not visual, accessibility or WordPress acceptance.

Disposable database cases were authored for denied defaults, a temporary synthetic
fixture grant/restoration, all-table preview rollback, commit, exact replay and
persisted approval markers. They were NOT RUN: no disposable WordPress/MariaDB
runtime is available. Native PHPCS, full SQL/races, HTTP scope/nonce/MFA and browser
acceptance remain pending. Chromium aborted because socket creation is prohibited;
the cloud browser rejected the local file URL because only HTTP(S) is allowed.
No serving, publication or security-policy workaround was attempted.

## Release holds

Schema remains marker 4 with the same 15 physical scheduling tables. The existing
recovery selector/payload is for v3 and has not been updated or rehearsed for this
candidate. Older v4 development revisions also lack current reservation/override
semantics: do not use a source-only downgrade as recovery. Validate the full
candidate and a coherent restored source/data pair before any reviewed deployment.
All fresh-backup, isolated functional recovery, transparent preflight, exact payload
and independent readback gates remain mandatory. Email/push, real grants, payments,
public/emergency booking and production remain held.
