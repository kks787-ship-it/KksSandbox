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

## MFA candidate correction — November 1, 2026 (supercedes historical proposal)

**CURRENT HOLD:** The previously suggested **standalone Wordfence Login Security** plugin should **not be selected for a new deployment**. [Wordfence's official help documentation](https://www.wordfence.com/help/login-security-plugin/) states its standalone product was scheduled for discontinuation around July 2026. Any earlier standalone-plugin recommendation in this document is **historical and superseded**.

[Issue #13 — MFA provider and verified current-session adapter](https://github.com/kks787-ship-it/KksSandbox/issues/13) now owns read-only candidate review and, if supported, disposable local adapter tests. Evaluate maintained [WordPress Two-Factor](https://wordpress.org/plugins/two-factor/) (TOTP and backup codes, **no built-in comprehensive mandatory-MFA enforcement**) versus [full Wordfence](https://wordpress.org/plugins/wordfence/) (2FA with broader WAF/security operational footprint), or a separately verified alternative. Owner choice **M-Choice remains unapproved**. Do not use a plugin's mere *enrolled* flag as proof that the current login session completed MFA. Test real session/token binding, alternate login methods, recovery and lockout before authorizing any provider or Digital Ranch master/bootstrap privileges.

**Latest scheduling code candidate:** v0.3.0, exact implementation commit [c04a243](https://github.com/kks787-ship-it/KksSandbox/commit/c04a24387e3d26f7cb0a7a13683a3981675a086b), 19 files, schema v3/15 owned tables, [test/release report](../test-results/scheduling-permissions.md). The older 0.2.1 15-file/14-table proposal is **not the current installation specification**. [Issue #12](https://github.com/kks787-ship-it/KksSandbox/issues/12) is closed as local implementation complete. **No Gate B execution row is approved**; Gate A backup/restore evidence remains historical, not permission to install, migrate, create accounts or enroll staff.

## How to decide without authorizing a deployment

For each ID, record **Approve / Reject / Revise / Hold**, the exact approved values, owner and date. A blank entry is Hold. Approving a proposal or one row does not approve its implementation, dependencies or later rows. Execution requires explicit authorization naming the applicable row IDs and satisfying the prerequisites below. Do not interpret “approve Gate B” as blanket permission.

| Decision ID | Specific decision requested | Prerequisite / effect if later authorized |
| --- | --- | --- |
| A-Beth | Create one separate Beth account with Subscriber role and no scheduling grants initially | Owner confirms identity, private login/email and secure handoff; does not authorize Kris account or MFA installation |
| A-Kris | Create one separate Kris account with Subscriber role and no scheduling grants initially | Same independent verification; no existing account is reassigned |
| A-Operator | Identify the existing administrator/operator and approve their installation/migration/provisioning duties | Private numeric-ID verification and MFA; no new admin account, role promotion or scheduling membership implied |
| M-Choice | Select TOTP authenticator + recovery codes; evaluate standalone Wordfence Login Security candidate | Choice/evaluation only; no security plugin files or settings authorized |
| M-Install | Install/activate the separately reviewed exact MFA release | **Hold:** vendor version, checksum/file/DB diff, isolated compatibility results and fresh recovery gate must be attached before execution approval |
| M-Enroll-Beth / M-Enroll-Kris / M-Enroll-Operator | Enroll and verify each named person's MFA independently | Working approved MFA mechanism; owner presence/secure recovery custody; no scheduling grants |
| M-Enforce | Enforce approved MFA coverage and alternate-login restrictions | Enrollment/recovery tests first; exact affected-role/custom-capability inventory and alternate-auth change diff approved |
| P-Hours | Approve the fictional timezone/hours below | Policy approval only; no configuration write |
| P-Services | Approve fictional services/capacities/durations/resource requirements | Independent from actual farm programs |
| P-Welfare | Approve fictional workload/recovery/travel and suitability restrictions | Test data only, not real horse-care policy |
| P-Programs | Approve the test-only training cap, Wednesday preference and Academy-disabled choice | No recurrence, route or Academy cohort implementation |
| R-Backup | Authorize current target-bound backup and isolated recovery validation for the next change window | Gate A evidence is retained; later changes/staleness require refresh, not reuse of its old execution approval |
| I-Files | Install and activate only the new pinned scheduling 19-file v0.3.0 release | Approved backup/recovery, verified staff/MFA prerequisites, rechecked destination and exact dry run; no schema apply |
| I-Schema | Preview, review and explicitly apply schema3/15 tables | Scheduling active; actual preview matches reviewed DDL; one-shot private CLI gate; no staff/data population |
| O-Master | Bootstrap or transfer the separately verified Farm Owner / Master identity | Explicit numeric owner/operator IDs, independent MFA, exact URL, reviewed private CLI gate and command UUID; no inferred identity or WordPress role promotion |
| G-Beth / G-Kris | **SUPERSEDED / HOLD:** individually select group + permission overrides after Issue #12, not the four-capability blanket grant | New fine-grained implementation, tested effective-access preview, explicit owner-approved identities/MFA and a **new source pin/release review** required |
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

## 2. MFA recommendation, recovery and proof

**Recommendation:** separate passwords plus TOTP authenticator applications and individual recovery codes, behind the existing HTTPS/Basic Auth protection. SMS and email OTP are not proposed; no delivery service or phone-use permission is assumed. Recommend the **standalone Wordfence Login Security** plugin as the candidate to evaluate, rather than a full firewall/security-suite deployment. Vendor documentation describes authenticator support and recovery codes ([setup/recovery](https://www.wordfence.com/help/tools/two-factor-authentication/)) and enforcement controls ([settings](https://www.wordfence.com/help/login-security/)). These are vendor capabilities, not compatibility results for this sandbox.

**Scope exception requiring a separate decision:** an MFA plugin adds files/settings and may create its own tables. Those are **outside** the 19-file csf-scheduling release. Do not install it under I-Files. M-Install remains blocked until a separate immutable vendor version/artifact SHA-256, complete file/table/option diff, licensing/privacy review and isolated WordPress7.1.4/PHP8.3.36/MariaDB10.6.23 compatibility test are prepared. No vendor release, database count or integration behavior is invented here. If the owner chooses a different MFA mechanism, replace this section and its release evidence before execution. Do not add custom MFA enforcement code to scheduling or modify the pinned plugin.

Proposed settings, each subject to M-Enforce approval:

- Require TOTP for verified administrator/operator and the two staff accounts after enrollment. Assess the plugin's **Custom Capabilities / Multiple Roles** category as well as Subscriber/Administrator: adding scheduling capabilities can change classification. Inventory every account affected before enabling required rules; applying a rule to that whole category can affect future accounts and must be reviewed before adding them.
- Use a supervised enrollment window, then no remaining enrollment grace for pilot access. Keep an already verified operator session and private recovery channel available while testing a fresh login; do not lock the sole administrator out before they enroll. A “Required” setting alone is insufficient proof.
- Disable remembered-device bypasses, IP bypass allowlists, optional passkey substitutions and reCAPTCHA/cloud integrations for this proposed TOTP-only pilot. Do not add public account/MFA shortcodes or change any website page.
- Propose disabling authenticated XML-RPC if unused, with explicit affected-integration approval; separately inventory WordPress application passwords, REST Basic Auth/custom login paths and existing sessions. The chosen MFA plugin must not be assumed to protect alternate authentication. No application passwords or API credentials are issued to staff. If an alternate path bypasses required MFA, hold access until an approved, tested restriction exists. Do not disable public REST/site functionality speculatively.
- Verify correct server/device time and proxy handling during the approved test; no NTP/firewall/Plesk configuration changes are included. Keep plugin data on deactivation for recovery; do not enable destructive uninstall/deactivation cleanup. Any outbound service use must be included in the MFA release/privacy review.

**Enrollment and recovery procedure (after approval):** each person scans their own secret privately on their own device, confirms a current code, stores recovery codes in an owner-approved encrypted vault with a separately protected offline recovery copy, and completes a fresh-browser login before closing the working session. Never put QR images, TOTP seeds, codes, passwords or private screenshots into Git/chat. Store evidence of successful checks and custody location, never the recovery values.

If a phone is lost, use an individual recovery code, replace the authenticator secret, regenerate recovery codes and revoke sessions. If device and codes are both lost, the verified operator confirms identity through the owner-approved independent recovery route, records the incident and temporarily removes that person's pilot access while re-enrolling. Kris's scheduling permissions do not authorize resetting Beth's MFA or vice versa. If the sole operator is locked out, stop for owner-approved restricted recovery over the existing secure administrative channel; do not broadly disable MFA or publish an unprotected login. A full backup restore can resurrect old sessions/MFA secrets, so recovery requires credential/session review, not automatic re-opening of access.

**Required proof for each account:** correct Basic Auth → correct password without TOTP cannot reach an authenticated WordPress session → incorrect/expired TOTP fails → current TOTP succeeds → dashboard/calendar access matches the approved grants → logout invalidates the session. Test one recovery code successfully and confirm it cannot be reused; regenerate/store replacements. Repeat fresh-browser/mobile login and negative tests after scheduling grants, to catch custom-capability reclassification. Attempt unauthorized admin/user/plugin/page operations and confirm denial. Log operator/reviewer/date/device/result privately, without credentials or token-bearing URLs. Test modestly to avoid unnecessary lockouts; no automated guessing or real-person impersonation.

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
