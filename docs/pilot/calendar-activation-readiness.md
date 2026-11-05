# Calendar activation readiness after the MFA checkpoint

**November5 execution update:** the owner approved the rollout and initial all20-right Farm
Owner bootstrap. Scheduling0.4.1 from `145e1ff` is now installed/active, with all15 schema-v3
tables ready. Fresh pre-change12-table and post-migration27-table backups both passed isolated
functional recovery, including MFA login/logout and cleanup. The ownership invitation is prepared
for the Technical Master's fresh native MFA/browser review; no grant is assumed before those
steps commit. Employee enrollment/grants and mandatory enforcement remain unchanged. See the
[actual rollout record](../test-results/scheduling-protected-rollout.md). Earlier holds and the
installed2fd02a2 descriptions below are historical, superseded only by this explicitly approved
scheduler rollout; MFA/provider source was not changed.


Implementation pin remains `2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`;
MFA installation tooling is recorded in `82da474`. This package authorizes no writes.
The owner reports the Technical Master completed authenticator login and recovery/reuse
checks. Non-secret corroboration and current target/recovery observations are kept in
the private worksheet. Operations and Head Trainer enrollment remain paused. No completed
email, identity-matching or calendar regression suite was repeated.

## Concrete blocker in the unchanged source

- `CSF_Scheduling_Identity::invitation()` rejects identical operator/owner numeric accounts.
- `CSF_Scheduling_Identity::authorize()` requires mandatory administrator enforcement and
  fresh approved MFA for each ceremony participant, including the proposed owner.
- Normal permissions also require MFA; an Administrator role does not grant calendar access.
- Operations Manager and Head Trainer are employee groups, not bootstrap identities.
  The retired CLI provisioning/master paths are unavailable and must stay unavailable.

Consequently, activating files and creating tables alone cannot deliver an accessible
calendar under the present one-enrolled-account, enforcement-off direction. Two accounts
controlled by the same human are not independent-human verification. Do not pretend that
an unenrolled employee is cleared, use a fixture verifier, promote a native WordPress role,
or turn enforcement on to satisfy the ceremony. Issue #22 is not a prerequisite.

## Local candidate after the separate owner authorization

The owner approved local implementation/testing of the exception described below. Scheduling
**0.4.1** now implements `pilot_bootstrap`; the installed **2fd02a2** pin is unchanged. The new
[candidate inventory](../test-results/scheduling-pilot-bootstrap-inventory.json) is separate
from the historical installation manifest. Deployment, migration and real bootstrap remain held.

The existing invitation fields/three bootstrap steps are preserved. Only this action allows
the operator and owner IDs to match. It requires `local` or `development`, no previous master,
mandatory enforcement off, and `CSF_SCHEDULING_PILOT_APPROVAL` containing the exact canonical
invitation digest from `CSF_Scheduling_Identity::digest()`. The existing private invitation
path/mode protections, release/site/policy/registry bindings, ten-minute maximum invitation,
five-minute challenge proof, native nonce/review and transaction locks still apply. Enrollment,
a WordPress role, CLI selection or a password-only session cannot satisfy these conditions.

The resulting initial Farm Owner has all 20 existing farm privileges, not merely calendar-view
rights. No native WordPress role is promoted. Other accounts receive no membership from this
action. Ordinary ownership/transfer retains its independent-account and enforcement requirements.
There is no pilot transfer/replacement path. Initial ownership, audit and exact retries use the
existing ledger; an uncertain commit must be resolved with the original command/status screen.

For a separately approved execution window, prepare the exact invitation from current facility
and registry state; privately review the numeric identity and verification-record hash. Bind its
canonical digest in approved configuration. The native screen explicitly labels the exception
and requires each reviewed step. After resolving the final result, remove the invitation first,
then its configuration approval digest; do not assume removal can undo a committed grant.
Do not copy fixture helper scripts to a farm or regenerate this invitation automatically after
failure/expiry. The controller supplies a fresh genuine challenge; no factor secrets are collected.

## Approved local implementation scope — no execution permission

Approved scope: **local implementation and testing only** of a development-sandbox, single-controller
pilot bootstrap for the already verified Technical Master identity. It must require a fresh
real MFA challenge, an explicitly owner-approved exact numeric identity/site/release, a private
one-use invitation with at most ten-minute validity, expected registry revision and transactional
audit/idempotency. It must keep mandatory enforcement off, retain ordinary MFA checks, preserve
last-master protection, and grant neither Operations nor Head Trainer access. It must not grant
WordPress capabilities or expose an anonymous/CLI ownership shortcut. No automatic production
or ordinary operational use is included. A narrowly changed tested release requires review
before replacing the current installation pin or executing bootstrap.

This is a locally implemented exception to the ownership ceremony. It has not been installed
or executed on the protected sandbox. Review the tested candidate release and exact initial
Farm Owner grant before separately authorizing that execution; do not substitute the old pin.

## Minimum employee access preview — no grants

| Existing group | Proposed inherited rights after later authorization | Still excluded |
|---|---|---|
| Operations Manager | All-calendar/resource view; create/edit/confirm/cancel; rider view/edit; suitability; protective hold; appointment audit; resource/availability management; manual clearance evidence | Farm policy; welfare release; team security and ownership |
| Head Trainer | All-calendar/resource view; create/edit/confirm/cancel; rider view/edit; suitability; protective hold; appointment audit | Resource/availability management; clearance; farm policy; welfare release; team security and ownership |

These are the source preset previews (13 and10 rights), not actual effective grants. While
paused/ungranted, protected calendar access remains denied. After a verified farm owner exists,
use the existing transactional group/override preview, exact member IDs, expected revision and
audited confirmation. Evaluate each account's MFA then; no need to enroll paused accounts now.
Never grant `manage_options` or change native Subscriber roles as a workaround.

## Exact installation and rollback sequence after separate execution authorization

**Local recovery qualification completed:** the exact allowlist now covers the 12 native
WordPress tables plus the 15 schema-v3 scheduling tables. Known partial sets are preserved and
reported for review, never accepted as healthy operational recovery. See the
[27-table qualification report](../test-results/scheduling-recovery.md). This is local evidence,
not a new protected-sandbox backup, restore or deployment authorization.

Execute the following only under the applicable reviewed sandbox/security scope. Bind the exact
current source, target and private command hashes immediately before execution; do not reuse a
historical approval envelope or silently replace the installed implementation pin.

1. Reverify target, installed scheduling20/MFA25 hashes, current schema and recovery space.
   Preserve inquiry disabled, emergency inactive, pages, menus, photos and approved F2 policy.
2. Capture a fresh pre-change backup with an explicit new private `run_token` and
   `expected_phase: "pre_scheduling"`. Require complete receipt, all artifact hashes, zero
   unattributed drift and age under24hours. The new recovery payload must match its release
   manifests. Existing destinations are never overwritten. Earlier pre-enrollment backups
   cannot protect current MFA metadata.
3. Qualify that exact backup in isolation before migration. The reviewed restore scope must
   include the clone-only temporary password and native MFA challenge using an ephemeral TOTP
   calculated inside the clone from its restored factor. No factor, code or session is exported;
   seeds/recovery codes are never regenerated. Restore all original cloned user metadata and
   credentials after login/logout; preserve current source data. Retain the30minute bound and
   containment. This package does not execute or authorize that real-account clone test.
4. After the scoped rollout approval, replace only the two changed Scheduling0.4.1 files from
   candidate `145e1ffce2b618339527e20d582cabba14996aa7`, verified against its
   [20-file inventory](../test-results/scheduling-pilot-bootstrap-inventory.json).
   Leave the25 MFA/provider files unchanged. Review promotion from installed source `2fd02a2`
   explicitly; moving documentation HEAD is not source promotion.
5. Activate only scheduling; activation performs no schema migration or cron scheduling.
   Existing MFA adapter hooks on plugin activation may invalidate prior Digital Ranch proofs
   by advancing the security epoch. Require a fresh native MFA login afterward; never reset
   factors or weaken authorization to preserve an old session.
6. Preview schema3 and all15 table statements. Explicitly apply with the exact sandbox URL,
   verified native operator and temporary `CSF_SCHEDULING_MIGRATION_ALLOWED`; remove the switch
   afterward. DDL is not atomic. On failure stop writes, inspect and preserve the actual partial
   state. Never drop/recreate tables or force a retry. A forensic capture must explicitly expect
   `partial_migration` or `damaged_scheduling_v3`; its integrity PASS is not schema readiness.
7. Before further pilot writes, capture with `expected_phase: "scheduling_v3"` and a new nonce;
   isolated-restore all27 tables, rows/indexes/constraints/relationships, current MFA metadata,
   pages and login/logout. Missing scheduling tables, invalid application schema, unexplained
   drift or failed cleanup stop progress. Retain both pre-change and post-migration evidence.
8. **Separate security checkpoint:** the one-use pilot bootstrap grants the Technical Master
   all20 farm privileges, not calendar view alone. Obtain explicit approval of that exact grant,
   account, expiry/revision and private ownership review before execution. Mandatory enforcement
   and employee grants are not implied; paused Operations/Head Trainer enrollments stay paused.
9. With the applicable fictional-pilot policy and grants approved, create synthetic records and
   run the protected acceptance below. No actual mail, public booking, emergency or real records.
10. Rollback: stop writes; inventory newer content/authentication/calendar changes first. Restore
    only reviewed file/config changes or a separately reviewed database rollback. Never apply an
    old12-table snapshot over newer calendar or MFA state automatically. Preserve restricted
    evidence; a fresh27-table recovery set is required before pilot data grows. No production,
    shared-server changes or automatic live restoration.

## Prepared protected acceptance

Use [the fictional specification](../../tests/fixtures/scheduling-pilot-acceptance.json) with
[the existing on-device walkthrough](../13-scheduling-staff-acceptance.md). The new fixture is a
test specification, not a database seeder or approved operating policy. Its hours, capacities,
workload/rest/travel limits, Monday academy and Wednesday locations are fictional proposals.
No genuine route geography is inferred or implemented. Sunday closure and protected schooling
are explicit test restrictions.

Cover private/group/haul-in/off-site work, horse rotation, all resource conflicts, simultaneous
saves, welfare/workload blocks, missing rider clearance, cancellation/audit, uncertain-result
recovery and DST. Use390/768/1280px plus actual owner devices. Parallel saves can use two sessions
of an authorized pilot operator; do not unpause an account to supply a second tester. Beth/Kris
acceptance is recorded only when they perform the applicable tasks themselves. Paused accounts
cannot yet supply authenticated acceptance sessions. Public/unauthorized access must stay denied.
