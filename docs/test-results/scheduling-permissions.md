# Issue #12 — owner dashboard and granular permissions

**Local implementation and validation complete. Not installed or operational.** Candidate
**Digital Ranch Scheduling 0.3.0 / schema 3**, 19 release files, 15 owned tables. Work is based on
`71a235f825444f766bf80099bcaf16d975f0baca`, preserving the owner's Issue #12 architecture,
Gate B package and tracker updates (including ancestor 1b9a418). No protected-server/Gateway operation,
account creation, MFA installation, protected migration/grant/pilot, inquiry change, emergency
activation, production or public-site modification occurred in this assignment.

## Exact release identity and scope

Plugin Git tree: **852837cb22fcf2b9d219cfe918c43d567cc5cc4e**. The immutable implementation
commit pin is recorded in the release-pin follow-up below. The [19-file checksum manifest](scheduling-permissions-files.sha256)
and [exact path/blob/hash inventory](scheduling-permissions-install-inventory.json) define the
candidate. Relative to historical 0.2.1: **four added files, ten modified, five unchanged**.
All 19 belong under `wp-content/plugins/csf-scheduling/`; no security provider, test fixture,
WordPress/Kadence, core/emergency, vendor or installation/deployment script is part of release.
New files: permissions/team service classes and team.js/team.css. A future absent-target install
would add 19 files only after fresh inspection/dry run confirms the historical absence.

Historical approved-for-review code **6f5a9bf51747399a309b39637a135841fe4cf2a1** and its 15-file
manifest remain unchanged. This new source cannot silently replace that pin. **Neither is now
an authorization to provision actual Beth/Kris through the retired broad-capability model.**
The [current Gate B package](../pilot/gate-b-owner-review.md) and [canonical runbook](../05-backup-rollback-deploy.md)
require separate review of this candidate and each account/MFA/bootstrap/install/migration/grant/
configuration/test decision. Source inventory is local Git evidence, not a fresh remote rsync. SQL history filters apply before keyset pagination; current-versus-proposed and Subscriber-owner dashboard cases are covered.

## Delivered behavior

- **Digital Ranch → Team & Permissions:** choose an existing account, load a preset, change
  native checkboxes or Inherit/Grant/Deny selectors, and see group/master defaults, effective access now and the proposed after-save result.
  Reviews use plain permission descriptions. Group copying needs a name, not a technical key.
- Seven editable employee presets, protected Farm Owner / Master identity, custom groups and
  bulk assignment to verified existing employees. Individual overrides/resources survive later
  template changes. Preview lists every affected member, including overrides preserving rights.
- Twenty independent implemented privileges and default-deny scope. Legacy blanket provisioning
  rejects requests. Digital Ranch does not create/promote WordPress roles, accounts or invitations.
- Transactional, actor/revision/UUID-bound preview/save/audit using the existing singleton lock.
  Preview rolls back; signed review binds the exact actor/payload/revision/command and expiry,
  nonce and sensitive confirmation. Current authorization is checked again under the lock.
- Authoritative membership and raw primary-DB mirrors avoid stale object-cache grants. Removing
  access denies the next protected request; disable/transfer revokes sessions after commit.
  Reconciliation failure is reported and exact replay repairs mirrors without duplicate audit.
- Last verified master's security controls cannot be removed through employee/group controls.
  Master transfer/recovery is a separately approved guarded CLI identity ceremony, with exact URL,
  existing numeric identity, independent MFA and verified technical operator. The owner can be
  a Subscriber; ordinary team changes do not need WordPress administration or developer CLI.
- Private permission history with SQL person/time filters and older-matches keyset paging. Appointment history is limited to visible
  records and excludes team payloads; no new public REST/user directory, static record data,
  notifications, private export, payment, signature or emergency integration.

## Exact operation-to-permission mapping

`csf_dr_<key>` is a non-authoritative WordPress mirror. Registry membership, active/verified status,
independent MFA, effective override resolution, full mirror agreement and record scope are also
required. Unknown keys do not authorize anything. This table describes implemented checks, not
approval of a particular person's access.

| Entry point / operation | Required permission / scope |
| --- | --- |
| Day/week/calendar detail | calendar_all or calendar_assigned; assigned = explicit resource association or that employee's authored appointment |
| Context/roster reads | resource_view for horses/resources; rider_view plus all-calendar scope for whole-farm roster; assigned-only cannot enumerate it |
| Requested/held creation/edit | appointment_edit; existing appointment and submitted scope rechecked |
| Confirm / edit confirmed | appointment_confirm; creating a new confirmed appointment additionally requires appointment_edit |
| Cancellation, including confirmed | appointment_cancel and appointment scope; history retained |
| Resource create/update | resource_manage |
| Horse write | resource_manage for identity/enrollment changes; suitability_manage for changed service/rider suitability; mixed changes need both |
| Rider write | rider_edit for identity changes; rider_clearance for changed adult/evidence flags; mixed changes need both |
| Availability | availability_manage; explicit existing resource |
| Protective hold | welfare_hold; without resource_manage only a horse target; non-welfare protection additionally needs availability_manage |
| Release restriction | welfare_release, independent of creator; hold permission alone cannot release |
| Facility configure (existing controlled service/CLI) | policy_manage |
| Appointment audit/history | audit_view, limited to visible appointment records |
| Team list/effective access | team_view |
| Assign member / bulk group | team_assign + team_view; identity/MFA validation; non-master cannot self-escalate or change policy/security rights |
| Edit/copy group | team_groups + team_view; diff for all members; delegate cannot grant rights they lack or security/policy authority |
| Permission history | team_audit + authorized private team page |
| Master bootstrap/transfer CLI | not a checkbox; explicit owner acknowledgment, numeric ID, independently verified MFA for operator/target, manage_options operator, private one-purpose identity gate, allowed environment and exact home/siteurl |
| Schema CLI preview/apply | existing verified manage_options operator; exact URL/private migration gate for apply, physical/version checks; no inferred scheduling membership |
| Time AJAX / schedule preview | authenticated native writer, nonce; preview reuses exact appointment authorization/transaction and rolls back |
| Legacy provision APIs / CLI | denied; cannot grant four coarse capabilities |
| Future export/emergency/payment/signature permissions | unregistered and denied |

Both form visibility and backend service checks were exercised; a guessed endpoint or forged
checkbox body cannot bypass the registry. Team state is facility/prefix scoped. A general group
cannot grant WordPress admin or master identity. An authorized record edit is not a legal signature.

## Database migration and rollback impacts

Schema 3 adds **TXGEh_csf_sched_authorization**: id bigint unsigned primary key (singleton 1,
no auto increment), registry_json longtext; InnoDB/utf8mb4_unicode_ci. The existing 14 physical
v2 contracts do not change. [Full actual-prefix DDL review](../pilot/scheduling-v3-TXGEh-proposed.ddl.txt)
contains 15 CREATE statements; source-review file SHA-256
**45ca1b585f5f8bccfdff8b350b2942c65115a57ecf5c648c073fb22f21803dab**.
It was generated locally and was **not** executed against the protected database.

The explicit v2→v3 upgrade preserves old rows, policies, staff provenance, appointments/rosters,
welfare, command/audit history and settings revision. It seeds no master and does not reinterpret
old manage rights as ownership. Legacy IDs are retained but quarantined inactive/unverified
pending owner identity/MFA/group review; an existing installation's access intentionally pauses
rather than escalating unknown users. Fresh installs create 15 owned tables, empty domain data,
reviewable presets and no active grants. Activation creates no schema, users or grants.

Physical drift, populated unversioned tables, missing/extra registry singleton, unknown/future
version and failed DDL/audit/commit prevent readiness. Interrupted additive DDL may remain;
inspect/review before retry, never stamp a version. There is **no destructive down migration**.
Pinned v0.2.1 code refuses schema 3 without changing it. Rollback requires a matched full backup,
review/preservation of later website/account/MFA/scheduling edits, and separate restore/cleanup
approval. Deactivation is containment and preserves data/audit; mirrors can remain inert.
Actual protected rollback has not been performed by this code task.

## Recorded local validation

| Check | MariaDB 10.6.28 | MySQL 8.0.46 |
| --- | ---: | ---: |
| Foundation assertions |88 passed|88 passed|
| Calendar assertions |131 passed|131 passed|
| Native staff usability assertions |66 passed|66 passed|
| New fine-grained authorization assertions |81 passed|81 passed|
| **Total per engine** |**366 passed**|**366 passed**|
| Booking races: empty/populated, horse/instructor/rider/incomplete rotation |6 cases passed|6 cases passed|
| Two-actor group-edit race and revocation between precheck/lock |2 cases passed|2 cases passed|
| Two-worker schema migration |One seed/audit; passed|One seed/audit; passed|
| CLI wrong-target / guarded owner transfer and recovery |Passed|Passed|
| Populated v2→v3 preservation and audit-failure retry |Passed|Passed|
| Pinned-v2 downgrade denial and full v3-data/owner backup restore |Passed|Passed|
| Populated-v1 restore access denial and initial logical restore |Passed|Passed|

**732 database assertions total**, plus the separately recorded race/CLI/restore checks.
Tests cover owner/manager/trainer/working-student/read-only cases, inherited grants, explicit
allows/denials, custom/bulk groups, verified owner as Subscriber without WordPress admin,
identity recovery, malformed/unknown/cross-domain escalation, logged-in revocation, independent
MFA false/error denial, current-versus-proposed display, sole-master mirror recovery, SQL-filtered historical events, full mirror mismatch/replay, previews, stale writes and injected registry/history/
audit/command/commit failures. Existing fixture website/inquiry/emergency state is preserved.

Chromium on disposable WordPress 6.8.3 / PHP 8.1.34 / MariaDB 10.6.28 with the local stand-in theme:
**59 existing calendar checks + 39 new permissions checks = 98 passed**. Owner interface tests
include 390/768/1280px geometry and axe, keyboard checkbox/override consistency, readable review,
save matching server rights, forged staff URL/POST denial, signed-in revocation, last-master
rejection, unknown-capability denial with a valid owner nonce, and named custom-group creation.
Axe checks reported no violations in the tested calendar/team surfaces. No real Kadence/Plesk,
MFA login provider or Beth/Kris device acceptance is inferred. The final run needed no transport
GET recovery; earlier diagnostic runs encountered recoverable browser network notifications.

All **84 PHP files** across the existing plugins passed PHP 8.1.34 syntax and full project
WPCS/PHPCompatibilityWP. Deployment/backup guards passed 22 scenarios, Plesk identity mocks 6,
and sandbox-preflight unit tests 14. JSON/checksum/relative-link/whitespace checks passed.
No deployment/installation script, other plugin, WordPress/Kadence or public asset was changed.
[Machine-readable evidence](scheduling-permissions-evidence.json) records counts and log digests.

Reproduce with installed locked dependencies and disposable fixtures only:

```sh
bash tests/scheduling/run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
CSF_TEST_BROWSER=1 bash tests/scheduling/run.sh
```

The runner uses an internal Docker network/synthetic DB, never farm configuration. It prepares
only the exact historical 15 plugin blobs for downgrade checks, avoiding unrelated sparse-checkout
media. Cached-image Git build-provenance warnings did not affect the independently hashed source.
The fake MFA adapter is installed only inside guarded disposable /test/site/csf_fixture; it is
excluded from the 19-file release. All this task's retained diagnostic containers/volumes/networks
were removed. No protected-site runner, real customer record, mail or notification was used.

## Failures corrected and practical limitations

Development runs exposed a missing audit action registration, the old blanket-capability test
assumption, a test helper colliding with PHP prev(), a group-impact loop using the wrong member,
WP-CLI's required-boolean flag synopsis, inactive/effective display and history filtering after unrelated records. They were corrected and the full final matrix rerun.
A local documentation helper also removed methods; method inventory and runtime suites verified
the restored implementation. Earlier incomplete runs are not included in passing totals. A browser-only serialize exception during rapid WordPress login/profile navigation led to a test-helper wait for jQuery-ready initialization before leaving the profile. The final clean matrix retained all script-error checks and passed; WordPress core was not changed.

Operational blockers remain:

- **No approved MFA/session adapter/provider is installed.** Integration defaults false; a group,
  owner checkbox or Administrator role cannot substitute for independent MFA. Verify alternative
  login/application-password/session behavior under a separately approved security mechanism.
- Actual numeric owner/operator/staff identities, base roles and MFA remain unverified. No Beth/
  Kris rights are assigned. Presets are editable starting examples requiring owner review.
- Protected WordPress 7.1.4/PHP 8.3.36/MariaDB 10.6.23/Kadence, Basic Auth/cache/proxy behavior and actual
  staff devices are untested with this candidate. Gate A did not prove these or off-server recovery.
- Sensitive transaction/migration/mirror error logging suppresses raw SQL payloads and restores the caller’s prior logging flag. A failed sole-master mirror can be repaired through guarded exact replay of the original identity bootstrap, preserving current authority and audit identity. If the original operator/command or independent MFA is unavailable, stop for separately approved identity recovery.
- Initial facility policy remains the existing controlled configuration workflow. Real operating
  hours/workloads/program rules and fictional pilot values still need separate approvals.
- Owner account creation/invitations are not implemented here; existing verified accounts are
  selected. No outbound emails, signature verification, private exports or later-module privileges.
- Current bounded pilot limits: 50 members, 30 groups, 100 selectable WordPress accounts and 500 matching permission events per page, with older-matches navigation, with explicit assigned-calendar scope and no assigned-only whole-farm
  roster enumeration. Larger deployments, general multi-tenant SaaS and production rollout are not accepted.

Follow the [owner walkthrough](../13-scheduling-staff-acceptance.md) and updated canonical runbook.
Code completion does not approve Gate B installation/MFA/accounts/bootstrap/migration/grants/
fixtures. Real operational scheduling still requires separate final authorization and Beth/Kris
hands-on acceptance. Inquiry stays disabled, emergency inactive and production untouched.
