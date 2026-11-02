# Digital Ranch — Gate B owner decision package

**Prepared November 1, 2026 (America/Los_Angeles). Proposal only: every execution decision below is unapproved.** This document is the single review entry point for staff access, MFA, fictional policy and installation. Supporting files are evidence, not additional permissions. No server inspection, backup, installation, account change or configuration change was performed while preparing this package.

Tracker commit **1b9a4182ff0e3229dfac5f945ae5788e732f049a** is preserved as the parent baseline. Historical reviewed scheduling remains **v0.2.1**, source **6f5a9bf51747399a309b39637a135841fe4cf2a1**, plugin tree **893edbe31278946aa2aaa830a56d5d1812b87323**, schema2/14 tables. A documentation commit never substitutes a new source pin.

## Owner requirement superseding equal-permission proposal — November 1, 2026

**NEW DECISION / HOLD ALL STAFF GRANTS:** The owner requires a **Farm Owner / Master Account** with a graphical **Team & Permissions** console. It must offer editable **preset groups**, **per-person permission checkboxes/overrides**, and a clear effective-access preview before changes are saved. Track implementation in [Issue #12 — master dashboard and granular access](https://github.com/kks787-ship-it/KksSandbox/issues/12). This requirement is now built in the local 0.3.0 candidate; it is **not delivered by the historical v0.2.1 pin** and remains uninstalled/unapproved.

The former G-Beth/G-Kris entries and later section proposing the same four scheduling capabilities for both staff are **historical options, not the owner's current approved access model**. In v0.2.1, the privileged `csf_schedule_manage` permission mixes bookings with facility policy, riders, horses, suitability and welfare. Its `provision` command grants all four capabilities to every listed user. **Do not execute G-Beth/G-Kris or provision anyone using that all-or-nothing command.** The scheduling source pin/tree remains `6f5a9bf` / `893edbe3` for the **historical** reviewed v0.2.1 candidate; Issue #12 will require a new source version, complete tests and a *fresh* explicit Gate B review before any source replacement or deployment.

The owner requests:
- Privately verified **Kris master identity**, not inferred from the sole existing WordPress Administrator account; ownership bootstrap and MFA require separate scope approval.
- Assign a reusable staff **group** (e.g. Operations Manager, Head Trainer, Instructor, Barn Staff, Working Student, Read-only), then check/uncheck individual privileges, including explicit denials and a visible summary of resulting access.
- Independent access controls for appointment view/edit/confirm/cancel, rider records/clearance evidence, horse availability/suitability, **raise vs release welfare blocks**, farm policies, audits and **team permission management**.
- No granting WordPress Administrator, `manage_options`, emergency operations, payment/contract authority or future modules merely through a farm group.
- Audit, immediate revocation, role-template edit impact previews, deny-by-default checks at the backend, and prevention of losing the last verified master.

**Gate B account creation, MFA vendor installation/enforcement, scheduling files/schema, policy/fictional fixture, and staff grants remain on HOLD.** The account/MFA and policy decision rows below are still useful for owner review, but none is authorization to act. Once Issue #12 is implemented and independently tested locally, produce a revised, pinned, capability-granular Gate B package; do not treat `b312bdd` as approval for the new plugin.

## Current Issue #13 review candidate — no execution authorization

Exact implementation source: **2c08321d98566bc50ccb6d62712abf798cc16d0d**. Source identity is not execution permission.

Recommend Two-Factor0.17.0 + separate csf-mfa0.1.0 for owner selection, with scheduling0.3.1
(schema3/15tables). Exact file/source identities, local validation and limitations are in the
[security release report](../test-results/scheduling-mfa.md). This is a new candidate, not a
replacement approval for c04a243/d09ad92 or the old0.2.1 pin. Mandatory WordPress-admin MFA,
selected artifact verification, safe master bootstrap and protected compatibility remain blocking.

## MFA candidate correction — November 1, 2026 (supercedes historical proposal)

**CURRENT HOLD:** The previously suggested **standalone Wordfence Login Security** plugin should **not be selected for a new deployment**. [Wordfence's official help documentation](https://www.wordfence.com/help/login-security-plugin/) states its standalone product was scheduled for discontinuation around July 2026. Any earlier standalone-plugin recommendation in this document is **historical and superseded**.

[Issue #13 — MFA provider and verified current-session adapter](https://github.com/kks787-ship-it/KksSandbox/issues/13) now owns read-only candidate review and, if supported, disposable local adapter tests. Evaluate maintained [WordPress Two-Factor](https://wordpress.org/plugins/two-factor/) (TOTP and backup codes, **no built-in comprehensive mandatory-MFA enforcement**) versus [full Wordfence](https://wordpress.org/plugins/wordfence/) (2FA with broader WAF/security operational footprint), or a separately verified alternative. Owner choice **M-Choice remains unapproved**. Do not use a plugin's mere *enrolled* flag as proof that the current login session completed MFA. Test real session/token binding, alternate login methods, recovery and lockout before authorizing any provider or Digital Ranch master/bootstrap privileges.

**Preserved Issue #12 scheduling source:** v0.3.0, exact implementation commit [c04a243](https://github.com/kks787-ship-it/KksSandbox/commit/c04a24387e3d26f7cb0a7a13683a3981675a086b), 19 files, schema v3/15 owned tables, [test/release report](../test-results/scheduling-permissions.md). The older 0.2.1 15-file/14-table proposal is **not the current installation specification**. [Issue #12](https://github.com/kks787-ship-it/KksSandbox/issues/12) is closed as local implementation complete. **No Gate B execution row is approved**; Gate A backup/restore evidence remains historical, not permission to install, migrate, create accounts or enroll staff.

## How to decide without authorizing a deployment

For each ID, record **Approve / Reject / Revise / Hold**, the exact approved values, owner and date. A blank entry is Hold. Approving a proposal or one row does not approve its implementation, dependencies or later rows. Execution requires explicit authorization naming the applicable row IDs and satisfying the prerequisites below. Do not interpret “approve Gate B” as blanket permission.

| Decision ID | Specific decision requested | Prerequisite / effect if later authorized |
| --- | --- | --- |
| A-Beth | Create one separate Beth account with Subscriber role and no scheduling grants initially | Owner confirms identity, private login/email and secure handoff; does not authorize Kris account or MFA installation |
| A-Kris | Create one separate Kris account with Subscriber role and no scheduling grants initially | Same independent verification; no existing account is reassigned |
| A-Operator | Identify the existing administrator/operator and approve their installation/migration/provisioning duties | Private numeric-ID verification and MFA; no new admin account, role promotion or scheduling membership implied |
| M-Choice | Select reviewed Two-Factor 0.17.0 + csf-mfa adapter, or request alternative review | Choice/evaluation only; no security plugin files or settings authorized |
| M-Install | Install/activate the separately reviewed exact MFA release | **Hold:** vendor version, checksum/file/DB diff, isolated compatibility results and fresh recovery gate must be attached before execution approval |
| M-Adapter | Install the exact three-file adapter and approve its private enablement switch | Separate code pin/inventory; unchanged default-deny, protected-stack acceptance and rollback evidence; no grant/schema/bootstrap implied |
| M-Notify | Approve provider low-code/password-reset notification effects | Verified delivery/privacy/recipient scope; no email OTP, SMS or inquiry enablement |
| M-Enroll-Beth / M-Enroll-Kris / M-Enroll-Operator | Enroll and verify each named person's MFA independently | Working approved MFA mechanism; owner presence/secure recovery custody; no scheduling grants |
| M-Enforce | Enforce approved MFA coverage and alternate-login restrictions | Enrollment/recovery tests first; exact affected-role/custom-capability inventory and alternate-auth change diff approved |
| P-Hours | Approve the fictional timezone/hours below | Policy approval only; no configuration write |
| P-Services | Approve fictional services/capacities/durations/resource requirements | Independent from actual farm programs |
| P-Welfare | Approve fictional workload/recovery/travel and suitability restrictions | Test data only, not real horse-care policy |
| P-Programs | Approve the test-only training cap, Wednesday preference and Academy-disabled choice | No recurrence, route or Academy cohort implementation |
| R-Backup | Authorize current target-bound backup and isolated recovery validation for the next change window | Gate A evidence is retained; later changes/staleness require refresh, not reuse of its old execution approval |
| I-Files | Install and activate only the new pinned scheduling 19-file v0.3.1 release | Approved backup/recovery, verified staff/MFA prerequisites, rechecked destination and exact dry run; no schema apply |
| I-Schema | Preview, review and explicitly apply schema3/15 tables | Scheduling active; actual preview matches reviewed DDL; one-shot private CLI gate; no staff/data population |
| O-Master | Bootstrap or transfer the separately verified Farm Owner / Master identity | **BLOCKED:** historical CLI gate cannot satisfy web-session MFA. Require separately implemented/tested one-use identity handoff, verified numeric IDs, exact target/revision/command and explicit owner execution scope; never bypass MFA |
| G-Beth / G-Kris | **HOLD:** approve each person’s exact group, individual overrides and resource scope; execute through the verified owner dashboard only after the safe identity ceremony | New fine-grained implementation, tested effective-access preview, explicit owner-approved identities/MFA and a **new source pin/release review** required |
| C-Policy | Apply the exact approved fictional JSON policy | All P decisions resolved, approved authorized configuring actor, current revision and command UUID |
| D-Fixture | Create only the agreed fictional records | Approved policy/permissions; finite availability/suitability; no real client/horse data |
| T-Pilot | Execute protected-stack synthetic acceptance | Approved fixture/access; Beth/Kris acceptance still requires their own hands-on results |
| X-Contain | Authorize only scheduling deactivation if an approved pilot fails | Non-destructive containment; preserve data/audit/grants; no MFA bypass or whole-DB restore |
| X-Restore / X-Cleanup | Approve a specific full restore or destructive cleanup if needed | Separate incident/change reconciliation and exact target/artifact review; never implied by a failed test |

**Recommended sequence:** resolve A/M/P review choices → separately approve the scoped account/MFA work with its exact release evidence → prove authentication → refresh backup/change baseline as needed → I-Files → I-Schema → O-Master → G rows → C-Policy → D-Fixture → T-Pilot. Stop at each missing approval. A no-change package review authorizes none of this sequence.

## 1. Staff accounts and owner-controlled access — superseding the old equal grants

The local 0.3.0 candidate now provides **Digital Ranch → Team & Permissions**. The source and
validation are in [the permission release report](../test-results/scheduling-permissions.md).
This is local implementation, not protected installation or approval of any person's rights.
The historical four-capability table and CLI provisioner are superseded; the latter now rejects
requests. The old 0.2.1 release must not be used to bypass the owner's new model.

| Person / identity | Proposed WordPress baseline | Digital Ranch decision still required |
| --- | --- | --- |
| Beth, exact existing/new identity privately verified | Subscriber, own profile, no WordPress admin or emergency rights | Owner chooses a group and explicit overrides; Head Trainer is a review example, not an assignment |
| Kris, exact existing/new identity privately verified | Subscriber; master does not grant manage_options/create_users/promote_users/plugin/page administration | Separately verify whether this numeric identity is the Farm Owner / Master and approve its guarded bootstrap; never infer it from the sole existing Administrator |
| Existing technical administrator/operator | Existing rights unchanged; ownership still unverified | Privately verify identity/MFA and authorize exact-target install/migration/bootstrap duties; no automatic farm membership |

Account creation remains A-Beth/A-Kris, separate from farm membership. No account is renamed,
shared, created, promoted, demoted or emailed by the permissions dashboard. Privately confirm
unique login/email/numeric ID, controlled password handoff, MFA and recovery custody. Do not
reuse Beth's public address as a login/recovery address without approval or invent Kris's details.
No unsolicited notification email; no public registration or website-page changes.

The protected owner controls editable preset/custom groups and individual **Inherit / Grant /
Deny** checkboxes. Every preview shows defaults, individual choice and effective result, affected
accounts and sensitive changes; ordinary staff management then needs no developer/CLI. Explicit
deny wins. Group changes retain individual overrides; bulk group assignment preserves overrides
and assigned-resource scope. The master is a protected identity, never a freely assignable group.

The 20 implemented rights separately cover all/assigned calendar, requested/held editing,
confirmation/confirmed editing, cancellation, rider view/identity/clearance, resources, availability,
suitability, horse welfare hold/release, farm policy, permitted history and team view/assign/groups/
history. These are **not** general WordPress or future payment/emergency/signature powers.
Unimplemented private exports are unavailable. Head Trainer/Operations/Instructor/Office/Barn/
Working Student/Read-only are editable starting points, not irreversible WordPress roles.

Initial G-Beth/G-Kris approvals must name the exact verified IDs, group defaults, individual
allows/denials, active status and resource scope. No equal broad grants are presumed. Bootstrap
and transfer/recovery require separate owner identity/MFA/URL approval and a guarded technical
ceremony; ordinary changes occur in the owner's native dashboard. The system prevents removing
or stripping the last verified master's team controls, rejects self-escalation and audits changes.

MFA remains an independent prerequisite. The candidate defaults to denying access without the
separately approved trusted provider/session integration. A local fake adapter proves software
paths only; it must never be installed on the farm. Enrollment, alternate-login coverage and
actual staff device verification remain M decisions. No master or employee access is operational.

## 2. Maintained MFA recommendation, recovery and proof — Issue #13

**Recommend for owner selection, not installation:** WordPress **Two-Factor 0.17.0** plus the
separate Digital Ranch **csf-mfa 0.1.0** adapter; scheduling **0.3.1** keeps schema3/15 tables.
Standalone Wordfence Login Security is superseded and must not be selected. Full Wordfence
remains an unselected broader-scope alternative without a verified current artifact here.

Read the [single security/release evidence report](../test-results/scheduling-mfa.md) for the
provider comparison, exact source/artifact digests, 21-file source distribution, three-file
adapter, 19-file scheduling candidate, DB/options/meta effects, tests and limitations. The
vendor ZIP SHA is published metadata only; its bytes have not been independently retrieved.
The local tested source build is explicitly distinguished from the ZIP. Approve the exact
artifact choice and verified inventory before M-Install; never substitute one silently.

**Enforcement proposal requiring separate approval:** TOTP and single-use backup codes only;
email/dummy/unsupported providers excluded site-wide when the private adapter switch is true;
no trusted-device exception; signed per-token proof expires within eight hours and session
expiry, even with Remember Me. Native provider revalidation renews a proof after another actual
challenge. Factor/config changes and revocation invalidate proofs. Recovery-code use invalidates
that user's other proofs conservatively. Alternative CLI/REST/XML-RPC/application-password paths
cannot authorize Digital Ranch. No application password or shared staff identity is proposed.

**Unresolved and blocking:** Two-Factor does not itself enforce enrollment/protection for all
WordPress administration paths. Actual auth plugins, operator MFA and mandatory-admin policy
need isolated verification. The historical CLI master bootstrap correctly fails with browser-only
session evidence. A separately reviewed and tested one-purpose identity handoff is required;
no browser-cookie export, fake MFA filter, blanket CLI exception or temporary global disablement.
O-Master cannot execute until that design exists. Protected PHP8.3.36/MariaDB10.6.23/Kadence/
Plesk/HTTPS/Basic Auth/cache/device compatibility remains unproved for this candidate.

**Enrollment after separately approved account/provider/security work:** privately verify each
numeric identity, unique login/email and base role; each person scans/confirms their own TOTP,
stores individual backup codes in an approved encrypted vault plus protected offline recovery,
then completes fresh-browser login, intended rights/denials, logout and one-time recovery checks.
Never put seeds, QR images, codes, cookies, passwords or recovery values in Git/chat/screenshots.
Owner/group assignment uses verified identity plus enrollment; only that employee's own completed
current-session challenge can unlock private features. No rights for Beth/Kris are decided here.

**Recovery:** lost device → individual recovery code → replace authenticator and backup codes →
revoke sessions → fresh verified login. Lost device and all codes → suspend only that person's
pilot access through an independently authorized actor, verify identity out of band and obtain
scoped recovery approval. Sole-master/operator lockout stops work for separately reviewed secure
recovery; do not disable MFA globally or infer master identity from WordPress Administrator.
A backup restore can resurrect secrets/sessions; reconcile changes and revoke/rotate before
reopening private access. Deactivation is containment, not destructive provider uninstall.

Provider low-backup-code and compromised-password-reset emails need M-Notify review. Local tests
intercepted all mail; no delivered-email claim, SMS use or inquiry enablement follows.

## 3. Fictional pilot policy — readable comparison

The proposed file is [csf-synthetic-policy-proposed.json](csf-synthetic-policy-proposed.json). The exact values below are reviewable now but **unapproved**. The [operating draft](csf-operating-policy-unconfirmed.json) remains intentionally incomplete and unbookable. Approving fictional limits never establishes real horse suitability, medical guidance, service eligibility or actual opening hours.

| Setting | Proposed **fictional pilot** value | Actual Corner Stone Farm status / meaning |
| --- | --- | --- |
| Facility timezone | America/Los_Angeles | Geographic proposal; approve independently; preserve WordPress global UTC |
| Hours | Mon–Sat 09:00–18:00; Sunday closed | Exact hours unconfirmed. Sunday client closure is an owner-supplied requirement |
| Private lesson | 60 minutes,1 rider | Real duration/capacity unconfirmed |
| Group lesson | 60 minutes, up to 4 riders | Real group limits unconfirmed; supports four-rider/two-horse rotation test |
| Client haul-in | 60 minutes,1 rider | Real service rules/duration unconfirmed |
| Off-site lesson | 60 minutes,1 rider | Real route/duration/travel policy unconfirmed |
| Horse training service | 60 minutes, capacity 1 | Test service definition; actual sessions unconfirmed |
| Daily horse workload | Maximum 300 reserved minutes | Fictional test limit, not an approved care policy |
| Consecutive work / recovery | Maximum120 minutes, minimum15-minute break | Fictional test thresholds; actual individual welfare limits unresolved |
| Travel allowance | 15minutes for configured travel-requiring work | Fictional test value; counts in reserved/conflict time where applied |
| Full-training enrollment | Cap 5 | Owner-supplied maximum; separate from daily minutes/service capacity |
| Off-site preference | Wednesday (3) | Owner-supplied preferred day; **preference, not Wednesday-only enforcement or route optimization** |
| Monday Horse Academy | Disabled for pilot; use manual group examples | Monday program is documented; seasonal/capacity rules unresolved. No cohort/recurrence automation |

| Fictional service | Allowed horse source | Required resources |
| --- | --- | --- |
| lesson | School or client-owned | Instructor, arena; school horse reservation through explicit ride/horse assignment |
| group | School | Instructor, horse resource, arena; explicit per-rider rotations |
| haul_in | Client-owned | Instructor, arena; no fictitious school-horse reservation |
| off_site | Client-owned | Instructor, location; configured travel protection |
| horse_training | School | Instructor, horse resource |

Test restrictions: no explicit resource availability means unavailable; unknown horse/service/rider suitability blocks saving unsafe reservations. Welfare restrictions block new unsafe work and visibly flag affected confirmed appointments for review; they do not silently cancel history. Confirmed appointments and rider clearance remain separate: missing manually checked release/guardian/contact evidence produces PRE-RIDE BLOCK, not an electronic signature. Staff must not treat it as permission to begin real services. Horse/instructor/arena/rider collisions are checked transactionally; previews do not reserve. Local opening intervals, DST gap/fold decisions and current policy still apply. Requested records do not reserve resources; held/confirmed work follows the engine’s active-hold/reservation rules. Cancellation retains history and releases allocations; it creates no fee, refund or notification. No crossing closed days, automatic recurrence, route planning, payments or real operational scheduling is authorized.

After D-Fixture approval, propose only Pilot Riders A–D, Pilot Maple/Pilot Cedar school horses, Pilot Client Horse, two Pilot instructors, two Pilot arenas and one Pilot off-site location. Owner selects a finite upcoming test week; no dates are inferred as actual appointments. Enter finite availability and explicit synthetic suitability; start evidence flags unchecked. Use native staff forms. Instructors are resources, not extra WordPress logins. No real minors/parents/horses/contact/medical/contract data.

## 4. Installation approval specification — new candidate, separate decisions

**No installation is authorized.** The historical v0.2.1/15-file pin and original 14-table
proposal remain available in the earlier preflight and manifest. They must not be used for
blanket staff grants or silently substituted with this new candidate. Review the new candidate
pin, [19-file inventory](../test-results/scheduling-permissions-files.sha256),
[v3 DDL](scheduling-v3-TXGEh-proposed.ddl.txt), [release/test report](../test-results/scheduling-permissions.md)
and the canonical [runbook](../05-backup-rollback-deploy.md) before changing any decision row.

| Proposed change | Separate owner checkpoint / exact boundary |
| --- | --- |
| New scheduling source | I-Files: only the candidate's 19 files under wp-content/plugins/csf-scheduling, three directories, no parent sync/delete; verify source tree and every hash before/after copy; activate scheduling only |
| Fresh sandbox DB | I-Schema: 15 owned InnoDB tables from guarded preview/apply; activation alone creates zero tables/grants/records |
| Existing v2 DB, if ever encountered | I-Schema: additive authorization singleton, unchanged physical 14-table contracts and retained policy/data/history; legacy staff provenance retained but inactive/unverified pending explicit review; no inferred master |
| Master identity | New separate identity-bootstrap approval: exact existing numeric ID, independent MFA/session proof, verified technical operator, exact URL and one-purpose private CLI gate; no WordPress role promotion |
| Employee access | G-Beth/G-Kris: choose exact group, overrides and resource scope; owner reviews native UI impact, saves atomically and audits. No old provision CLI or equal grants |
| Initial facility policy | C-Policy: only approved P values; existing controlled configuration service with current revision/UUID; ordinary staff permission changes require no developer |
| Synthetic records | D-Fixture: explicit fictional resources/riders/horses/finite availability, then T-Pilot acceptance. No real clients or horse records |
| MFA/provider adapter | M-Install/M-Enforce: separately pinned security release and approved effective-session adapter; outside the 19-file scheduling release; test before any operational access |

Target stays sandbox.cornerstonefarmomak.com, exact root
`/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`, database **wp_mdf4x**,
prefix **TXGEh_**. Production httpdocs/wp_iacb7 and unassigned wp_mdoaq remain excluded.
Recheck authoritative target and destination before any future write; prior absence is historical
observation, not a fresh dry run. Preserve core/emergency, WordPress/Kadence, pages/photos/menus,
branding, inquiry, hardening and global timezone. No installation/environment script changes.

**Schema detail:** original settings/resources/appointments/allocations/commands/audit/riders/
horses/availability/restrictions/details/history/slots/participants remain physically unchanged.
New `TXGEh_csf_sched_authorization` has primary id bigint unsigned (singleton 1, no auto increment)
and registry_json longtext, InnoDB/utf8mb4_unicode_ci. First apply seeds schema 3 and no master;
no accounts, active employees, grants or domain fixtures are invented. Captured real preview,
physical inspection and one-shot migration gate are mandatory; no raw DDL execution, future-version
adoption, schema stamping or destructive downgrade. Interrupted DDL stops readiness.

**Backup/recovery:** Gate A passed with a private matched backup and isolated functional restore;
it did not exercise Plesk/FPM, actual MFA/Basic Auth/proxy behavior or encrypted off-server recovery.
Before any separately authorized write window, freeze unrelated edits and capture current content,
plugin, role/capability, table, policy and authentication baselines. Use a fresh exact-target
DB/wp-content/config/source backup, 0700/0600 private custody, hashes, readable archives and tested
isolated recovery. Existing verifier requires under 24 hours, but intervening changes require
refresh even when younger. Account/MFA changes make older recovery assumptions incomplete.
Off-server transfer/custody needs an explicit reviewed destination and separate scope.

**Tests:** first verify each actual person's Basic Auth/password/MFA/recovery and repeat after new
capability mirrors. Test owner versus trainer/manager/assigned/read-only access, denied forged
requests, group/override previews, instant signed-in revocation, last-master protection, private
permission history and authorized recovery. Follow the full synthetic calendar walkthrough for
private/group/rotations/client horses, conflicts, cancellation/audit, welfare/clearance, stale saves,
uncertain-command replay and two distinct staff saves. Verify actual protected stack, nonce/role/
cache/privacy behavior, keyboard/axe and 390/768/1280px plus Beth/Kris devices. Smoke-check public
Home/Lessons/Contact and unchanged content/media/menu/theme/inquiry/emergency baselines. No
failure-injection test runner is pointed at the VPS; destructive races stay disposable/local.

**Rollback:** authorize X-Contain if desired, limited to deactivating scheduling and preserving
owned data/audit. Tables and inert mirrors can remain; containment is not full restoration.
X-Restore requires the matched pre-change backup and explicit reconciliation/preservation of
later website, staff, credential/MFA, policy and scheduling changes; never overwrite later owner
content automatically or restore a whole subscription. Old v0.2.1 code denies schema 3; it is not
a down migration. X-Cleanup is separate for accounts/grants/fixtures/audit or MFA deletion.
Revalidate restored authentication/session state, plugins/tables/grants, private access and public
pages. Do not deactivate MFA to bypass a problem. Final real operational use remains unauthorized.

## Owner response worksheet

For each desired row ID above: **Decision: ___ ; Approved values/scope: ___ ; Owner/date: ___ ; Execution authorized now: yes/no.** Account identities, email addresses, IDs and MFA custody details belong in the secure private record, not this repository worksheet. For example, selecting M-Choice approves a candidate, not M-Install; approving P rows approves fictional values, not C-Policy; approving I-Files does not approve I-Schema or G/D rows.

Open inputs: Beth/Kris unique identities/login emails; existing operator ownership/ID and recovery contact; exact group/individual permission decisions; MFA choice and separately pinned security-release evidence; effective MFA/bypass coverage; fictional P decisions and finite pilot week; separate execution authorizations. There is no current execution authorization for any Gate B row.

**Explicit exclusions throughout:** production, real customer/horse records, public booking/Acuity, payments, signatures, inquiry enablement, emergency activation/modification, website redesign/content/media/menu/branding changes and real operational scheduling. Completing a synthetic pilot still needs separate final owner authorization before real appointments.

## Preparation checks

All 15 scheduling SHA-256 checks passed locally; all 15 hashes are reproduced above. Relative
package links, JSON policy values and whitespace checks passed. Tracker content is byte-for-byte
unchanged from 1b9a418; scheduling/plugin scripts and the proposed policy files are unchanged.
No scheduling regressions, server/MFA compatibility tests or protected-site checks were rerun
for this documentation-only package. Vendor documentation was reviewed read-only; M-Install
remains held pending exact release evidence. No Gateway/server calls were made for this task.


## Issue #12 candidate review — installation remains held

The local 0.3.0 implementation adds 20 granular rights, a native owner dashboard and one explicit
v3 authorization table (15 owned tables total). The new 19-file candidate, exact checksums/DDL,
regression results and deployment limitations are recorded in the release report and canonical
runbook. The 15-file/14-table section above is retained **historical 0.2.1 evidence**, not an
execution specification for the new model. I-Files/I-Schema/G decisions require a fresh review
of the new pin, target state, MFA adapter, bootstrap identity and group mappings before any write.
Gate A recovery remains evidence, not blanket permission for this source, accounts or new schema.

Exact local implementation source for the new candidate: **c04a24387e3d26f7cb0a7a13683a3981675a086b**;
plugin tree **852837cb22fcf2b9d219cfe918c43d567cc5cc4e**. The current I-Files/I-Schema
rows describe 19 files/schema 3. Historical v0.2.1 inventories and package-verification statements
remain dated evidence only. No row has execution authorization.

## Issue #13 current review override — no execution authorized

The new local security candidate supersedes earlier 0.3.0 installation assumptions. Preserve
c04a243/d09ad92 as provenance; review the newly pinned 0.3.1 scheduling and separate adapter in
the Issue #13 report. M-Choice is a selection decision, not M-Install/M-Adapter/M-Enforce.
The provider ZIP/source-build choice, mandatory WordPress-admin enforcement, safe master identity
handoff and protected-stack compatibility remain blockers. None of A/M/I/O/G/C/D/T is approved.
Earlier 0.2.1/0.3.0 tables and source pins are historical evidence, never execution authorization.
