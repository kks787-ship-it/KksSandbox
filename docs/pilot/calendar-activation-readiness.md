# Calendar activation readiness after the MFA checkpoint

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

**Concrete pre-migration recovery prerequisite:** the current baseline generator accepts exactly
the 12 native WordPress tables (`docs/pilot/baseline-select-plan.py.txt`), and the isolated restore
parser likewise rejects non-native tables (`scripts/recovery/restore_local.py`). Before applying
schema3 on the protected sandbox, extend and locally qualify the exact backup/restore contract
for those 12 plus the 15 scheduling tables. Do not weaken it to accept arbitrary tables, describe
the earlier 12-table recovery drill as scheduling-data recovery, or migrate first and leave future
backups unable to capture the calendar. This is follow-on recovery engineering, not a bootstrap
exception or permission to change the server.

1. Reverify pinned scheduling20 and MFA25 files, target/configuration and schema preview.
   Preserve inquiry disabled, emergency inactive, existing pages/menus/photos and MFA policy.
2. Verify the isolated recovery procedure; capture and integrity-verify a **fresh** pre-change
   backup including current MFA enrollment metadata. Earlier pre-enrollment recovery snapshots
   are not a reason to overwrite newer factors. Missing protection stops writes.
3. Activate only the reviewed scheduling plugin. Activation has no migration/cron side effects.
4. Preview schema3, all15 statements and current state. Explicitly apply only with the exact
   sandbox URL, verified operator and temporary `CSF_SCHEDULING_MIGRATION_ALLOWED` switch.
   Clear the migration switch afterward. DDL is not atomic: inspect partial state on failure;
   do not drop/recreate tables or force through an error.
5. Execute only a separately approved, implemented bootstrap. Recheck real MFA, one-use expiry,
   release, revision, account and private ownership approval. No employee grants are implied.
6. Configure separately reviewed synthetic policies and seed only fictional records after
   approval; keep real notifications and public booking off. Use the fixture specification
   below and the canonical staff walkthrough. Test on the actual protected/Kadence stack.
7. On failure, stop writes and inventory later owner changes. Roll back only the scoped new
   files/config/options or reviewed partial schema against the fresh baseline. Full restoration
   requires review if it would overwrite later content or MFA changes. Preserve restricted audit
   evidence; never disable security to make the test pass.

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
