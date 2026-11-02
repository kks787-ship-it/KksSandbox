# Digital Ranch — Gate B owner decision package

## Current identity correction — November 2, 2026; design only

[Owner instructions](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6584204812)
supersede the recent same-account reuse recommendation. **HTTP Basic Auth is only the protected
site gate; it is not the Plesk-created WordPress Administrator, a staff account or an MFA proof.**
The reported Plesk screenshot establishes a configured technical WordPress login, not its human
controller, numeric UID, recovery custody or authority to become Farm Master. Keep all actual
logins, private emails, IDs, factors and screenshots outside Git/chat. Plesk control-panel
management is separate; a native WP Administrator does not automatically gain Plesk rights.

**Recommended model, requiring owner approval:** preserve the existing technical WordPress
Administrator unchanged for maintenance/operator duties after private verification, and establish
a separately approved Kris business WordPress Subscriber account for protected Farm Master.
Beth receives her own independently verified Subscriber/staff identity and reviewed farm rights.
No actual identity is mapped/created by this design; no throwaway or additional Administrator,
Beth promotion, account renaming/demotion or same-UID bootstrap exception is proposed.

The current source **2fd02a2** already supports this model: operator and owner must be different
WordPress IDs with their own fresh actual MFA browser approvals. Preserve that constraint and
transaction/audit. Different accounts do not prove different humans; privately verify controllers
and decide whether separate-human approval is required before O-Master. A sole-UID alternative
would need a new independently reviewed protocol, tests and release pin; do not remove the
constraint or use a checkbox/CLI/Plesk screenshot as approval. **No code change is recommended.**

Review the [canonical account inventory, bootstrap analysis and Accounts & Access UI proposal](../01-architecture.md#accounts--access--corrected-identity-model-november-2-2026)
and [owner register](../03-owner-confirmation-register.md#corrected-account-layers-and-owner-decisions--november-2-2026).
The proposed dashboard distinguishes native administration, farm group/overrides, enrollment,
**your own** current-session proof, recovery-readiness and Plesk/API dependencies without exposing
secrets or giving farm owners server-administration powers. It is **design only**; reuse the
existing Team & Permissions engine in any later authorized UI implementation.

**Plesk integration remains a dependency to protect:** verify its actual Toolkit/login/SSO and
recovery pathways before separately approved mandatory MFA. Do not assume application-password
or XML-RPC use, infer a completed challenge from SSO, or weaken enforcement to accommodate it.
The earlier options digest difference is an unattributed observation, not evidence of compromise;
reported backend activity is plausible but unproven. Review critical settings and a fresh baseline,
without a broad automatic investigation, repairing options or disabling cron.

**Decisions still held:** separate business-account design, exact private controllers/UIDs and
human-independence policy; Kris/Beth account details and rights; technical-inventory visibility,
recovery/notifications; Plesk compatibility; every existing R/A/M/I/O/G/P/C/D/T/X execution row.
This correction uses GitHub/local documentation/source review only. No server calls, accounts,
passwords, MFA, privileges, schema, backup/restore, plugins or public-site changes occurred.

## November 2 current-server decision sheet — execution remains on hold

Read-only assessment complete against starting head `1fe8947`; implementation remains exactly
**`2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`** (Scheduling0.4.0, adapter0.2.0, official
Two-Factor0.17.0). [Current-server report](../test-results/gate-b-readiness.md),
[exact proposed +45/replace0/delete0 diff](gate-b-installation-proposed.json),
[ordered execution/rollback](../05-backup-rollback-deploy.md#gate-b-current-server-staged-execution-plan--november-2-2026).
This is the **single owner decision entry point**; supporting evidence grants no permission.
Later sections retain historical source/review context; this dated sheet controls current choices.

**Verified:** expected WP7.1.4/PHP8.3.36/MariaDB10.6.23/Kadence1.5.2 stack, only csf-core active,
one unmapped Administrator, zero scheduling/MFA files/tables/grants, no application-password
records, no MU/cache drop-ins; BasicAuth401 on existing static CSS. Existing GateA archives and
6+12 checksums remain intact, all4,479 live files match its baseline, drill services are stopped.
**Not verified:** human account ownership, actual staff MFA/recovery custody, every external
API/Toolkit dependency, new plugins' protected FPM/proxy/device behavior, off-server recovery,
or Beth/Kris acceptance. No backup/restore, account or server change occurred in this assessment.
The final snapshot found **options-table digest drift** with unchanged row count/selected settings;
the other11 tables and all4,479 files matched. The changed key/actor is unknown. Review activity
and establish a fresh frozen baseline; do not treat this preflight as an unchanged DB snapshot.
All31 proposed PHP source files passed syntax-only checks on installed PHP8.3.36 via stdin,
without installation or WordPress execution.

### Decide each stage independently

For each row record **Approve / Reject / Revise / Hold**, exact target/source/values, authorized
actor, date and private verification reference where applicable. Blank means Hold. A review or
selection approval does not authorize execution; dependencies and later rows remain separate.
Do not submit IDs, private emails, passwords, factors, codes or verification evidence in chat/Git.

| Decision | Concrete scope for owner review | Prerequisite / status |
| --- | --- | --- |
| V-Identity | Privately verify the existing Plesk-created technical WordPress administrator's controller/UID and separate HTTP/Plesk contexts; independently verify proposed Kris business and Beth staff identities | **Unverified.** Screenshot/protection login does not establish the WordPress controller or MFA. No account linkage/reuse inferred |
| V-Baseline | Owner reports likely admin login activity during inspection; confirm stable security-critical options and capture a fresh consistent change-window snapshot | **Likely routine owner activity; specific changed option unproven.** No automatic breach conclusion or expensive forensic hold; investigate only unexpected security-sensitive drift. No settings repair/restore authorized |
| M-Choice / M-Update | Choose exact official0.17.0 + adapter0.2.0, TOTP and single-use backups, supervised update custodian | Hold. Actual ZIP/inventory verified; manual monitoring/staged repinning must have a named owner |
| R-Backup | Fresh exact sandbox DB/wp-content/config/salts backup; current file/table/role/settings baseline and private0700/0600 custody | Hold. No backup created here; current GateA evidence is historical recovery proof |
| R-Restore | Functional restore of that fresh bundle into private isolation; integrity, clone login, dashboard/pages and security-secret recoverability | Hold separately. No live restore, real password verification, public clone or mail delivery implied |
| A-Operator | Confirm existing maintenance authority/recovery duties for verified administrator | Hold; no new admin promotion, account reassignment or farm membership |
| A-Kris / A-Beth | **Proposal: separate Kris Subscriber business account, plus independent Beth Subscriber staff account;** preserve existing technical administrator unchanged | Hold per account/action, with private verified identities and separately approved details. No creation, native role change, Farm Master bootstrap, staff grant, MFA enrollment or notification implied |
| M-Install / M-Adapter | Separately install/activate exact21 provider /4 adapter files; only approved adapter enablement constant | Hold per component. No mandatory enforcement or scheduling install implied |
| M-Notify | Approve exact native account/provider security-notification behavior, private recipients and any test delivery | Hold before steps that could send mail. Inquiry disabled does not suppress security mail; no email sent/tested here |
| M-Enroll-operator / M-Recovery-operator | Actual operator TOTP, fresh independent login/logout, single-use backup-code rehearsal, tested trusted physical recovery and custody | Hold. Complete before enforcement; no seed/code/cookie logging |
| M-Enforce | Enable mandatory privileged-web MFA after sole-operator recovery and application-password/XML-RPC/Toolkit dependency review | Hold. Explicitly acknowledge affected API/SSO paths; no password-only exception |
| M-Enroll-Kris / M-Enroll-Beth | Individual actual-factor enrollment/challenge/recovery after approved account/security steps | Hold per person. Safe own-profile grace is not a general admin grant |
| I-Files | Install/activate only exact20 scheduling files with source comparison | Hold. Native active_plugins change is separate from file copy; no schema/grants implied |
| I-Schema | Preview exact15-table schema3, then separately authorized guarded apply and temporary migration flag | Hold. No raw DDL, destructive repair or automatic adoption. Close migration window afterward |
| O-Master | Private verified Kris/operator numeric mapping, finite source/target/policy-bound invitation, independent fresh MFA browser approvals and one-use consumption | Hold; requires schema readiness and fresh proofs after all activation/config changes. No retired CLI bootstrap |
| V-Independence / UI-Design | Decide required human independence and approve/revise Accounts & Access inventory/audience/privacy design | Hold for design review; two accounts do not prove two humans. UI not implemented; no new privileges, source/schema or server operation authorized |
| G-Beth / G-person | Approve exact group, each override, active/verified status and optional resource scope below | Hold per person; owner uses native Team & Permissions after bootstrap |
| P-hours / P-capacity / P-welfare / P-restrictions | Approve fictional-only values and finite test week in the readable table below | Hold separately; no actual horse-care/suitability or published-hours decision inferred |
| C-Policy / D-Fixture | Apply only approved fictional settings / named synthetic records | Hold independently. No actual client, minor or horse data |
| T-Pilot | Protected-stack security/calendar390/768/1280 and staff-device tests; then Beth/Kris own walkthrough | Hold. Automated development is not personal or operational acceptance |
| X-Contain / X-Recover-person / X-Restore / X-Cleanup | Exact scope-specific containment, same-UID physical factor recovery, reconciled full restore or destructive cleanup | Each held separately. No blanket rollback, security disablement, deleted audit or stale DB overwrite |

### Private identity-verification checklist

The operator and owner complete this in their approved private vault/change record, not in this
repository. No actual identity mapping was queried or created by the assessment.

1. Independently establish the existing administrator's human controller and maintenance authority;
   compare their known independent contact channel and controlled login/recovery custody. Do not
   rely on a display name, email similarity, this chat or Plesk/root access as farm ownership.
2. Privately record each exact WordPress numeric ID, unique private login/email, current role,
   verified person and proposed duty; confirm reuse versus separate account creation. Do not rename,
   share or silently promote the existing account. Kris/Beth native baseline is Subscriber.
3. Verify each person controls their own password and authenticator; define custody for one-use
   backup codes and the separately trusted physical operator's recovery. Verify BasicAuth access
   separately; it is not a second WordPress factor or individual farm identity.
4. Approve who may recover the sole administrator and existing master **without replacing the
   numeric identity**, how independent owner consent is checked, and where the private incident
   record is retained. Lost/deleted/replaced identities stop the normal ceremony for separate review.
5. Inventory mobile/publishing clients, scheduled jobs, monitoring/deployment/API clients and
   Plesk Toolkit/SSO users. Record application-password/XML-RPC needs, owner retirement/replacement
   decision and post-enforcement checks. Zero stored credentials/log silence is not a complete inventory.
6. Before O-Master, approve intended ownership and distinct operator/owner IDs and evidence hash;
   each must personally complete a fresh factor in a separate browser. Before G-Beth, approve
   the exact effective-access preview below. Redact IDs/addresses/codes from shared screenshots.

### Proposed implemented privileges — not actual grants

Kris's verified master would control all20 **implemented scheduling** privileges without native
WordPress Administrator. Future finance, operations modules, legal agreements, billing and
emergency powers are not supplied by that identity today. The technical operator retains only
privately reviewed native maintenance authority and receives **no automatic Digital Ranch rights**.

Beth proposal: Head Trainer preset plus **two individually reviewed grants** for horse/resource
identity management and availability. These two grants extend the preset to meet the requested
horse-management duties; they are not silently part of the default. Rider clearance, another
person's welfare release, policies and team security remain separate owner choices, default off.
Kris can later change groups/individual overrides in Team & Permissions; no developer is needed.

| Implemented privilege | Kris master proposal | Beth proposal |
| --- | --- | --- |
| View all calendar | Allow | Allow — preset |
| View assigned calendar | Allow | Off separately; all-calendar view already proposed |
| Create/edit requested or held appointments | Allow | Allow — preset |
| Confirm/edit confirmed appointments | Allow | Allow — preset |
| Cancel appointments | Allow | Allow — preset |
| View riders | Allow | Allow — preset |
| Edit rider identity | Allow | Allow — preset |
| Verify participation evidence, not e-signatures | Allow | Off — separate decision |
| View horses/resources/restrictions | Allow | Allow — preset |
| Edit horse/resource identities | Allow | **Proposed individual grant** |
| Manage availability/protected intervals | Allow | **Proposed individual grant** |
| Manage horse/service/rider suitability | Allow | Allow — preset |
| Raise protective welfare hold | Allow | Allow — preset |
| Release welfare hold, including another person's | Allow | Off — separate decision |
| Change farm policies | Allow | Off |
| View permitted appointment history | Allow | Allow — preset |
| View team/effective permissions | Allow | Off |
| Assign groups/individual overrides | Allow | Off |
| Edit permission groups | Allow | Off |
| View private permission audit | Allow | Off |

No public account listing or private identity directory is produced. Initial group/override
and scope choices remain reviewable and auditable. The last verified master cannot be removed
through ordinary group changes; ownership transfer needs its own independent consent ceremony.

### Fictional policy decisions and safest next step

The existing [plain-English policy table](#3-fictional-pilot-policy--readable-comparison) and
[proposed JSON](csf-synthetic-policy-proposed.json) remain unchanged, SHA256
`3bb1624b169a1d901ada3372b89ccb8b7c1d7cdb1436ca4060493e31d8bf2aa3`.
Confirmed product inputs: Sunday client closure, Wednesday **preferred** off-site day and
maximum five full-training horses. Exact hours, session/group limits, individual horse workload,
rest/travel intervals, seasonal Academy rules and a finite pilot week still require owner review.
For the fictional pilot only: Mon–Sat09:00–18:00;60-minute sessions; group cap4; daily300minutes,
consecutive120minutes then15-minute recovery;15-minute travel; Academy disabled/manual examples.
Fictional horses are Pilot Maple/Pilot Cedar and Pilot Client Horse, riders A–D, two instructors,
two arenas and one off-site location. None has been entered. No recurrence/routes, real welfare
guidance or electronic-signature clearance is implied.

**Next:** privately resolve V-Identity/M-Choice/dependencies, then consider **R-Backup and
R-Restore only**, with no account/security installation included. After recovery passes, review
the smallest explicit account/provider/enrollment window. Enforce only after operator recovery;
scheduling files/schema and all later O/G/P/C/D/T work remain separate. Do not approve “all Gate B.”

## Current Issue #14 decision override — all execution rows held

Exact implementation source **`2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`**; [pinned release and tests](../test-results/scheduling-security.md).
This immutable source replaces no historical approval; every execution row below remains Hold.

**Current local review candidate:** Scheduling **0.4.0** / csf-mfa **0.2.0** / official Two-Factor
**0.17.0**, unchanged schema3/15tables. The [new security report](../test-results/scheduling-security.md)
and its exact45-file installation inventory supersede the older candidate specifications below.
Earlier source pins, reports, GateA recovery and the owner's Issue14 **local development** approval
are preserved. None authorizes protected changes. The officialZIP is now independently verified
(91,837bytes; SHA256 `59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`).
Do not execute the historical CLI bootstrap or substitute earlier source to bypass this review.

Each row can independently be **Approve / Reject / Revise / Hold**; blank means Hold. Approval
must name its row, exact release/target/values, authorized actor and scope. Selection/review is
not execution approval, and no row approves its dependencies or successors.

| Row | Concrete proposed change | Current status / owner decision needed |
| --- | --- | --- |
| A-Operator | Privately identify the existing technical administrator, numeric UID and recovery custodian; retain existing native role | Hold. Existing account ownership remains unverified; no automatic farm ownership |
| A-Beth / A-Kris | Separate individually controlled accounts, proposed native Subscriber baseline; no farm grants on creation | Hold separately for each identity/login/private email/UID and handoff; do not reuse public contact information without consent |
| M-Choice | Select Two-Factor0.17.0 + exact adapter0.2.0 candidate | Hold; selection alone does not install or enroll |
| M-Install | Install/activate only21 verified vendor files | Hold; exact release comparison, fresh backup/recovery evidence and private access prerequisites |
| M-Adapter | Install/activate only4 exact adapter files, approve `CSF_MFA_ENABLED=true` | Hold; no enforcement or account enrollment implied |
| M-Enroll-person | Per-identity enrollment, fresh real challenge, single-use recovery-code rehearsal and private custody record | Hold for each verified person; no shared factor or unsolicited email |
| M-Enforce | Separately set `CSF_MFA_ENFORCE_ADMIN=true` after enrollment/recovery verification | Hold; acknowledge privileged-web/API impact, disable WP application passwords/authenticated XML-RPC, no bypass for unknown administrators; BasicAuth remains separate |
| M-Recover-person / M-Notify | Exact same-UID physical recovery procedure / any requested notification | Hold separately; no master replacement, MFA switch-off or automatic email approval |
| M-Update | Manual monitoring custodian and staged pinned security-update process | Hold; no silent vendor auto-update/hash exception |
| I-Files | Install only20 exact scheduling files; no core/emergency/theme/media/site-content changes | Hold; source pin, manifest comparison and fresh target-bound backup required |
| I-Schema | Explicit guarded preview/application of schema3/15tables if still absent; no new Issue14 DDL | Hold; exact current target/prefix, InnoDB/DDL, config migration window, supported SQL/PHP and restore evidence |
| O-Master | Independently verify proposed Kris owner (not assumed), exact numeric operator/owner IDs and private verification record; approve one finite bootstrap invitation and independent browser ceremony | Hold; protected MFA/recovery prerequisites must pass first. This does not create accounts or farm members |
| O-Transfer | Any later finite transfer with outgoing-master independent consent | Hold separately, never implied by bootstrap; lost/deleted identities require separately reviewed recovery |
| G-person / G-group | Exact preset/custom group and individual privilege overrides selected through native dashboard | Hold separately; no equal broad grants to Beth/Kris; master doesn't grant WordPress administration |
| P-hours / P-capacity / P-welfare / P-restrictions | Review each fictional value against the readable policy table below | Hold; test values are not actual farm policy |
| C-Policy / D-Fixture | Apply only approved fictional configuration / finite synthetic records | Hold independently; no real customer/horse information |
| T-Pilot | Protected-stack security/calendar/mobile synthetic tests and Beth/Kris hands-on review | Hold; tests cannot claim owner acceptance |
| X-Contain | Scope-specific non-destructive containment after a failed approved change | Hold; deactivate scheduling only if explicitly authorized; retain audit/data and MFA enforcement |
| X-Restore / X-Cleanup | A separately identified restore or destructive cleanup | Hold; reconcile intervening website/user writes first; no blind whole-DB restore |

**Exact proposed footprint:** 20 scheduling +4 adapter +21 vendor files; unchanged15-table
schema3. Provider/adapter use native options/usermeta for MFA enrollment, attempt state, credential
epochs and session-bound proofs. Ownership writes existing authorization/history/settings/commands/
audit and native farm-capability mirrors/session revocations atomically. A private0600 invitation
outside the webroot is temporary approval scope, not a public file or reusable bypass. No invitation
for actual people is prepared by this local work. Exact fields, expiry, signatures and tables are
in the security report and source inventory. No extra role, new WordPress admin right or table is implied.

**Safe execution sequence, only after specific approvals:** privately confirm identities and
recovery access → fresh target-bound DB/wp-content/config backup plus isolated functional restore
and reconciled change baseline → exact45-file dry-run comparison → independently approved vendor/
adapter install/enrollment/enforcement → independently approved scheduling files/schema preview
and guarded application → fresh MFA/browser proof tests → exact one-use owner bootstrap → approved
per-person grants/policies/fictional records → synthetic protected acceptance → Beth/Kris own
hands-on checklist. Never bundle these gates. Use the [canonical runbook](../05-backup-rollback-deploy.md)
and [staff walkthrough](../13-scheduling-staff-acceptance.md).

**Release/recovery acceptance:** check every source/vendor hash, all15 table definitions and
InnoDB native security tables; password-only admin must have only own enrollment grace; factor
enrollment alone must not unlock; test fresh TOTP/backup code, logout/revocation/credential reset,
native REST/nonces, blocked alternate auth and owner role boundaries. Rehearse separate owner
approval, denied selfappointment, expiry, outgoing consent, concurrent/exact UUID replay, SQL
rollback and lost-response status. Then repeat the calendar's private/group/rotation/haul-in/
welfare/conflict/cancel/clearance scenarios at390/768/1280 on the actual Plesk/Kadence stack.
Do not inject SQL failures or destructive recovery experiments into the protected sandbox; those
belong in its separately isolated restore. Preserve native current-master deletion/role guards.

**Rollback:** stop at any mismatch before files/schema; after approved installation, contain
scheduling without deleting its tables/audit or treating older CLI code as a recovery path. Do
not disable/remove mandatory MFA merely to regain web access. Use separately approved private
same-UID physical recovery if factors are lost; maintain enforcement and repeat real MFA. A
full database/config restore needs its own exact artifact/target approval, reconciliation of
intervening website/account writes, protected recovery rehearsal and post-restore revocation.
Earlier code cannot safely read a new authorization policy unless compatibility was tested;
schema reset/drop is never implied. GateA's existing evidence is not a fresh change backup.

The remaining sections preserve earlier evidence and detailed policy/group choices; where
versions, counts, CLI or ZIP status differ, **this current override and the new pinned report control**.

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

## Issue #14 owner-approval hold — mandatory WP admin MFA and safe Master bootstrap

**Current technical candidate (LOCAL ONLY):** Scheduling 0.3.1 / schema v3 / 15 tables at [2c08321](https://github.com/kks787-ship-it/KksSandbox/commit/2c08321d98566bc50ccb6d62712abf798cc16d0d), separate `csf-mfa` 0.1.0 and proposed WordPress Two-Factor 0.17.0. The [current validation](../test-results/scheduling-mfa.md) reports 732 scheduling assertions, 16 MFA prerequisite assertions, 198 browser checks and 86 PHP files in disposable fixtures. [Issue #13](https://github.com/kks787-ship-it/KksSandbox/issues/13) is closed as **local code complete**, NOT installed. The exact historical code/source pins and Gate A evidence are preserved. 

**New execution blockers tracked as [Issue #14](https://github.com/kks787-ship-it/KksSandbox/issues/14):**

1. Complete and test a **one-use, operator/owner independently authenticated Master bootstrap/recovery handoff**. The previous CLI gate **correctly cannot** present proof of a browser MFA challenge; nobody may set a fake filter, bypass flag or WordPress role to bootstrap owner privileges.
2. **Require MFA for privileged WordPress administration**, not merely Digital Ranch. WordPress Two-Factor 0.17.0 is current and compatible by published minimum versions, but its ordinary configuration does not independently mandate enrollment for all admins. Protect/review alternate login and app-password paths, the sole existing unknown administrator account, enrollment, recovery and last-admin lockout.
3. Verify the exact **official two-factor.zip release bytes** (vendor metadata: 91,837 bytes, SHA-256 `59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`) and extracted file inventory. This digest is the **published GitHub metadata**, not an independent hash of downloaded bytes; existing local fixtures used a separately reviewed reconstructed source distribution. Do not silently substitute or disable provider integrity checks.
4. New immutable code source/release hashes, updated exact plugin/MFA/provider file and option/user-meta changes, specific owner M-Choice/M-Install/M-Adapter/M-Enforce and A/O/G/P/I/D/T permissions, Gate A freshness/restore and real protected WP7.1.4/PHP8.3.36/MariaDB10.6.23/Plesk/Basic Auth checks all remain **separate owner decisions**.

**No Gate B approval is granted or implied.** No provider installation, login setting, WP account creation, MFA enrollment, Master bootstrap, scheduling activation/migration, staff provisioning, fictional pilot, real horse/client record, production, inquiry or emergency changes. If Issue #14 cannot close the security gaps, retain default deny and request an alternate reviewed design rather than bypassing controls.
