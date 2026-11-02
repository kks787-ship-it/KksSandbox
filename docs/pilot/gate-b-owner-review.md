# Digital Ranch — Gate B owner decision package

**Prepared November 1, 2026 (America/Los_Angeles). Proposal only: every execution decision below is unapproved.** This document is the single review entry point for staff access, MFA, fictional policy and installation. Supporting files are evidence, not additional permissions. No server inspection, backup, installation, account change or configuration change was performed while preparing this package.

Tracker commit **1b9a4182ff0e3229dfac5f945ae5788e732f049a** is preserved as the parent baseline. Scheduling remains **v0.2.1**, source **6f5a9bf51747399a309b39637a135841fe4cf2a1**, plugin tree **893edbe31278946aa2aaa830a56d5d1812b87323**, schema2/14 tables. A documentation commit never substitutes a new source pin.

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
| I-Files | Install and activate only the pinned scheduling 15-file release | Approved backup/recovery, verified staff/MFA prerequisites, rechecked destination and exact dry run; no schema apply |
| I-Schema | Preview, review and explicitly apply schema2/14 tables | Scheduling active; actual preview matches reviewed DDL; one-shot private CLI gate; no staff/data population |
| G-Beth / G-Kris | Provision the listed scheduling permissions for each approved ID | Explicit complete staff allowlist, ready schema, effective MFA and permission acceptance; no implied operator membership |
| C-Policy | Apply the exact approved fictional JSON policy | All P decisions resolved, approved authorized configuring actor, current revision and command UUID |
| D-Fixture | Create only the agreed fictional records | Approved policy/permissions; finite availability/suitability; no real client/horse data |
| T-Pilot | Execute protected-stack synthetic acceptance | Approved fixture/access; Beth/Kris acceptance still requires their own hands-on results |
| X-Contain | Authorize only scheduling deactivation if an approved pilot fails | Non-destructive containment; preserve data/audit/grants; no MFA bypass or whole-DB restore |
| X-Restore / X-Cleanup | Approve a specific full restore or destructive cleanup if needed | Separate incident/change reconciliation and exact target/artifact review; never implied by a failed test |

**Recommended sequence:** resolve A/M/P review choices → separately approve the scoped account/MFA work with its exact release evidence → prove authentication → refresh backup/change baseline as needed → I-Files → I-Schema → G rows → C-Policy → D-Fixture → T-Pilot. Stop at each missing approval. A no-change package review authorizes none of this sequence.

## 1. Staff accounts and exact access

The last direct inspection found one administrator account. Its ownership is **unknown**, and neither its display name nor its email establishes Beth/Kris identity. Do not rename, share, transfer or demote it. The owner must privately verify its numeric ID and operator identity before it performs privileged commands. If no suitable existing operator is verified, stop; creating another administrator requires a new explicit proposal.

| Proposed separate account | Base role | Creation-stage access | Later proposed pilot access |
| --- | --- | --- | --- |
| Beth, privately verified by owner | Subscriber | Standard `read` capability (legacy `level_0` only if present), own profile/password and approved MFA self-service; no scheduling access | Same four scheduling capabilities below, plus explicit staff allowlist membership |
| Kris, privately verified by owner | Subscriber | Identical limited baseline | Same four scheduling capabilities and membership; operations work does not require site Administrator |

Do not publish proposed usernames, account emails, numeric identities, credentials or recovery contacts in Git. The private approval record needs: person's confirmed identity; unique approved username; independently controlled confirmed email; numeric ID after creation; existing/new status; base role; approved grants; MFA verification; recovery custodian. Beth's public contact address is not automatically her login/recovery address; no Kris address is inferred. Before creation verify that Subscriber actually has only `read` and any legacy `level_0` marker, without other capabilities on this installation; custom role drift stops provisioning. Use unique passwords and a private owner-approved handoff, not chat/Git. Default account notification emails are not authorized: review and explicitly approve any notification before sending; unknown email delivery must not block secure offline handoff or silently trigger mail. Public registration stays unchanged/closed.

| Capability proposed for **both** Beth and Kris | Concrete authority in pinned code |
| --- | --- |
| `csf_schedule_view` | View private day/week calendar, scheduling records and diagnostics when the schema is ready and their numeric ID is allowlisted |
| `csf_schedule_manage` | Create/edit requested or held appointments, cancel including confirmed appointments with history; manage riders, horse/resource records, availability, suitability and welfare restrictions; run schedule previews and controlled policy/configuration commands |
| `csf_schedule_confirm` | Confirm appointments and edit existing confirmed bookings in addition to manage access; does not verify rider clearance |
| `csf_schedule_audit` | Read scheduling audit/history controls; no audit-delete authority |

Effective scheduling access requires **both** capability and authoritative staff membership, checked against ready schema. No Administrator role bypass is inferred. Existing provisioning grants **all four** capabilities to every listed staff ID; it is not a per-person role designer. The provisioned staff list is a **complete replacement**, not an incremental add: approve the final list containing both verified staff IDs if both are to retain access, and privately record current revision/UUID. Replaying a partial or uncertain operation uses its original actor/payload/command ID; do not rerun with a guessed revision/new UUID.

The grant includes broad farm setup/policy authority, not just appointment entry. If the owner wants Beth or Kris restricted from configuration, cancellation, confirmations or audit, **reject/revise G for that person**; narrower supported provisioning needs a separately reviewed change, not ad hoc SQL or manual capability edits. A third view-only test user is not automatically authorized; use disposable fixtures unless a separate account/grant decision is made.

Neither receives `manage_options`, `create_users`, `edit_users`, `promote_users`, plugin/theme installation/update privileges, page/post/menu/media editing/uploading, site administration or emergency capabilities. They cannot migrate the schema or provision users merely because they manage scheduling. The verified technical operator needs existing `manage_options` for installation/migration/provisioning but is **not** automatically in the scheduling allowlist. Prefer an approved Beth/Kris scheduler for the controlled initial policy command; if the operator also needs scheduling data/configuration access, approve their additional membership separately.

## 2. MFA recommendation, recovery and proof

**Recommendation:** separate passwords plus TOTP authenticator applications and individual recovery codes, behind the existing HTTPS/Basic Auth protection. SMS and email OTP are not proposed; no delivery service or phone-use permission is assumed. Recommend the **standalone Wordfence Login Security** plugin as the candidate to evaluate, rather than a full firewall/security-suite deployment. Vendor documentation describes authenticator support and recovery codes ([setup/recovery](https://www.wordfence.com/help/tools/two-factor-authentication/)) and enforcement controls ([settings](https://www.wordfence.com/help/login-security/)). These are vendor capabilities, not compatibility results for this sandbox.

**Scope exception requiring a separate decision:** an MFA plugin adds files/settings and may create its own tables. Those are **outside** the 15-file csf-scheduling release. Do not install it under I-Files. M-Install remains blocked until a separate immutable vendor version/artifact SHA-256, complete file/table/option diff, licensing/privacy review and isolated WordPress7.1.4/PHP8.3.36/MariaDB10.6.23 compatibility test are prepared. No vendor release, database count or integration behavior is invented here. If the owner chooses a different MFA mechanism, replace this section and its release evidence before execution. Do not add custom MFA enforcement code to scheduling or modify the pinned plugin.

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

## 4. Installation approval specification

This section summarizes the canonical [deployment/rollback procedure](../05-backup-rollback-deploy.md); it does not replace or loosen it. The [preflight](../test-results/scheduling-pilot-preflight.md) and [Gate A report](../test-results/scheduling-pilot-gate-a.md) are historical observed evidence, not a new server check in this package.

**Only approved target:** `https://sandbox.cornerstonefarmomak.com`; root `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; database **wp_mdf4x**, prefix **TXGEh_**. Stop if any authoritative value differs. Production sibling httpdocs, production DB wp_iacb7 and unassigned DB wp_mdoaq are excluded. No environment onboarding, completed-hardening reapplication, installation-script changes or environment publication.

### Exact scheduling files — I-Files only

At the last inspection scheduling was absent. Proposed change: **15 additions, zero replacements/deletions**, under `wp-content/plugins/csf-scheduling/`; new root/assets/includes directories 0755, files 0644 with subscription ownership. Recheck absence, privately stage the exact code pin, verify no extras, save the canonical endpoint-only rsync dry run and compare against this list. No parent sync or `--delete`. Activation adds only scheduling to active-plugin metadata; it must create no tables, records or grants.

All paths below are relative to `wp-content/plugins/csf-scheduling/`. Each SHA-256 must match before and after copying:

| File | SHA-256 |
| --- | --- |
| assets/staff.css | `3051d46a062d0a245d19d3ba2028ae5e499c14ffe0360b86fe4cc1db268e4ab1` |
| assets/staff.js | `2f474873bcd6b700bfde47ae226d71a3d3fa3a06ec4cc7bcea534bf376806e76` |
| csf-scheduling.php | `6e60bf7df6e0089662118581dd950d8c5151bebf6fcf93c7a9047c9123e8b67a` |
| includes/class-csf-scheduling-access.php | `db98448525502043706c32ea21b17ec5691b033c83f864c6b5511575f4fa5c49` |
| includes/class-csf-scheduling-admin.php | `35b23cfa7f9d1bd8ddcdddab7d24fbb5a3a63299a75be4ea6503a698805eb61e` |
| includes/class-csf-scheduling-audit.php | `6108e49007b383649b0a49bacd89481cf185b8fb6598e8dba855dba9606a6326` |
| includes/class-csf-scheduling-calendar.php | `8b0042df3fa86fe709cc01b9fb5477abbe9bf8b7a9674625eb7d0c9eb946a157` |
| includes/class-csf-scheduling-cli.php | `c3b3070e6807f6f7285ed8ca9ddbcb42475442538b4330f3c624ae3500fe496e` |
| includes/class-csf-scheduling-conflict.php | `75fba13414d029aae6c27396ff4cfe80780fcc72a391f02cb13a6c98873c51c1` |
| includes/class-csf-scheduling-diagnostics.php | `46228d1eda22969de5e73c27e4cf72f55b7ab1fe0861fe89f769149cc88325f7` |
| includes/class-csf-scheduling-policy.php | `3ee1193b5251fc11de9d74e72267b310c345c8eee956c690dc8a92ba5e34214a` |
| includes/class-csf-scheduling-schema.php | `066d32a1687fd26d1c86387ffc5f58773abeacad3d4c6ce7b8d6c8a0f4c9ad59` |
| includes/class-csf-scheduling-store.php | `a7d0ee5fc3f671e849693898aff5dcae4f20f03dd6bffc60ab2d46382202d6b5` |
| includes/class-csf-scheduling-time.php | `cef24403a438357626d968c21d72699aebdeb56784856efdb32ef94866b81279` |
| includes/class-csf-scheduling-ui.php | `4722ffc49f526f0f92514459c23e30372b23b615e596a06c05fd663455c2f2ac` |

MFA files are expressly excluded from this list and I-Files approval. csf-core, csf-emergency, WordPress/Kadence, pages, photos, menus, branding, inquiry settings and production remain untouched.

### Exact owned database changes — I-Schema only

Use the existing guarded migration, **not** raw SQL from a review file. After I-Files approval/activation, the verified operator captures the real read-only CLI preview and checks physical schema/drift/version requirements against [actual-prefix DDL](scheduling-v2-TXGEh-proposed.ddl.txt). An existing explicit false migration gate, unexpected owned data/tables, wrong engine/collation or future schema stops execution. No global configuration flag.

| New table (prefix `TXGEh_csf_sched_`) | Purpose |
| --- | --- |
| settings | Schema version2, singleton revision, full policy, authoritative staff allowlist |
| resources | Horse/instructor/arena/location availability-resource identities |
| appointments | States, UTC/local times/offsets, actor/revision and cancellation metadata |
| allocations | Appointment resource reservations |
| commands | Actor-bound idempotent command/result ledger |
| audit | Append-only change identity, actor/revision/timestamp history |
| riders | Versioned rider records |
| horses | Versioned horse/source/suitability records |
| availability | Explicit availability intervals |
| restrictions | Welfare and other restrictions |
| details | Versioned appointment/rotation details |
| history | Retained appointment history |
| slots | Horse ride/rotation reservation slots |
| participants | Rider participation/conflict records |

All 14 are InnoDB, explicit utf8mb4_unicode_ci with ASCII-binary identifiers/hashes where defined; preserve datetime(6) and signed client-horse slot identifiers. DDL-body SHA-256 `00217723716963ff0afb4a21689bb2776e8bf67edb37c02d4d536389014320a4`. Migration seeds schema2/revision0, empty staff and incomplete policy plus its audit event. No real/pilot domain records or grants are seeded. With the historical 12-table inventory unchanged this would yield 26 tables; later account/MFA installation can change the baseline, so enumerate scheduling-owned 14 independently, never delete other legitimate tables to force total 26.

Use the approved operator ID, exact URL and temporary owner-only private CLI migration bootstrap for the one guarded apply in the canonical runbook; remove it afterward. Verify physical tables/indexes/engine/collation/seed/audit/empty domain counts. Partial DDL is not ready; stop for a reviewed recovery/retry. Never manually stamp version, alter existing tables, disable locks or delete unknown records.

Account creation changes users/usermeta only under A approvals. G provisioning additionally changes the authoritative staff list, revision, command/audit records and per-user four-capability mirror. C-Policy changes full policy/revision/command/audit. D-Fixture changes owned rider/horse/resource/availability/restriction/appointment/slot/participant/detail/history/command/audit records as required. These later writes are **not** included in file or migration approval.

### Backup and recovery requirements — before any authorized write phase

Gate A bundle `csf-gate-a-20261102-004845-8d96d856` passed exact target/checksums, 4,479 files / 12 tables and isolated login/dashboard/Home/Lessons/Contact recovery. It is **retained evidence**, not permanent permission or a backup of later account/MFA changes. Its drill used a private PHP server, not full Plesk/FPM, MFA/proxy/cache validation or encrypted off-server recovery. Do not report those limitations resolved.

Before the next authorized change window, freeze unrelated edits; capture current plugins/roles/capabilities/policy/table inventory and content/settings/file baselines privately. Require an exact-target DB/wp-content backup with paired secure config and source-file recovery, restricted 0700/0600 custody, timestamps, hashes and readable archives. Existing verifier requires DB backup under 24 hours old; refresh after any relevant intervening changes even if under that limit. Refresh the isolated recovery check for changed authentication/backup state before relying on it. Do not recreate the historical October31 scratch environment. Off-server encrypted custody must be explicitly reviewed and authorized as a separate transfer if required; no destination or transfer permission is inferred.

### Test and acceptance checkpoints — T-Pilot only

| Checkpoint | Required evidence / stop condition |
| --- | --- |
| Authentication first | Each person's actual protected-site login and MFA/recovery negatives/positives; repeat after grants; verified numeric actor mapping; no shared staff login |
| Activation/schema | Only intended plugin active-state change; activation zero tables/grants; actual 14-table preview and guarded apply match; no physical drift |
| Authorization/privacy | Subscriber without membership and unapproved admin denied scheduling; approved staff limited as above; logged-out/missing-nonce requests fail; no private response caching, public REST/search or URL/log leakage |
| Core calendar | Native day/week/filter/edit/cancel, private lesson, four-rider/two-horse rotation/recovery, partial-turn title edit, client haul-in, finite availability/suitability and welfare block/release |
| Conflict/transactions | Two distinct approved users preview overlapping reservations then save: at most one commits; stale draft preserved; horse/instructor/arena/rider collisions, canceled allocation release, exact uncertain-command replay |
| Clearance/time | Confirmed versus PRE-RIDE BLOCK understood; local timezone/offsets correct; safe DST cases only within approved policy; injected DB failures and closed-day DST fixture tests stay on disposable environments |
| Actual stack/devices | Protected WordPress7.1.4/PHP8.3.36/MariaDB10.6.23/Kadence1.5.2 compatibility, Basic Auth/proxy/cache/MFA, keyboard/accessibility at 390/768/1280px and actual Beth/Kris phones |
| Existing website | Home/Lessons/Contact smoke checks, Contact Beth to Schedule unchanged, content/photo/menu/theme/file baselines preserved; inquiry off, emergency inactive; expected account/MFA/scheduling changes separately accounted for |
| Human decision | Beth and Kris each complete the [acceptance walkthrough](../13-scheduling-staff-acceptance.md) and record help/confusion/failures; automated/operator success does not constitute their acceptance |

Record observed outcomes, reviewer/device/date, exact release/policy and failures privately. Historical local MariaDB/MySQL regressions and Gate A recovery are not reruns of these pilot checks. Do not run destructive/local-fixture test runners against the VPS. If new code is required to fix a blocker, return to development and approve a new release comparison; do not silently change the pinned plugin.

### Containment and rollback — scoped decisions, no automatic destruction

On failed scheduling acceptance, pause use and deactivate only scheduling under the approved containment scope; preserve owned records/audit and verify public/inquiry/emergency states. Deactivation is **containment**, not removal of tables or custom grants. Do not deactivate MFA to bypass an access problem. A lost second factor follows the verified recovery procedure above.

Full rollback X-Restore requires the exact matched pre-change backup, private preservation of later scheduling/account/MFA changes, owner review of intervening content/credential edits and explicit restoration scope. Restore only approved target files/config/database pair; never restore an entire subscription or overwrite later pages/photos/menus automatically. Check restored plugins/tables/roles/capabilities/allowlist, sessions/MFA, login/public pages and no private cache leakage. Removing accounts, grants, synthetic records, audit or MFA data requires X-Cleanup or an equivalent specifically reviewed plan; preserve audit actor identities and avoid deleting referenced users. No automatic down migration or raw SQL cleanup.

## Owner response worksheet

For each desired row ID above: **Decision: ___ ; Approved values/scope: ___ ; Owner/date: ___ ; Execution authorized now: yes/no.** Account identities, email addresses, IDs and MFA custody details belong in the secure private record, not this repository worksheet. For example, selecting M-Choice approves a candidate, not M-Install; approving P rows approves fictional values, not C-Policy; approving I-Files does not approve I-Schema or G/D rows.

Open inputs: Beth/Kris unique identities/login emails; existing operator ownership/ID and recovery contact; acceptance of equal broad scheduler permissions; MFA choice and separately pinned security-release evidence; effective MFA/bypass coverage; fictional P decisions and finite pilot week; separate execution authorizations. There is no current execution authorization for any Gate B row.

**Explicit exclusions throughout:** production, real customer/horse records, public booking/Acuity, payments, signatures, inquiry enablement, emergency activation/modification, website redesign/content/media/menu/branding changes and real operational scheduling. Completing a synthetic pilot still needs separate final owner authorization before real appointments.

## Preparation checks

All 15 scheduling SHA-256 checks passed locally; all 15 hashes are reproduced above. Relative
package links, JSON policy values and whitespace checks passed. Tracker content is byte-for-byte
unchanged from 1b9a418; scheduling/plugin scripts and the proposed policy files are unchanged.
No scheduling regressions, server/MFA compatibility tests or protected-site checks were rerun
for this documentation-only package. Vendor documentation was reviewed read-only; M-Install
remains held pending exact release evidence. No Gateway/server calls were made for this task.
