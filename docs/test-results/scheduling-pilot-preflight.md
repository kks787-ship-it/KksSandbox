# Sprint #11 — protected sandbox preflight

Phase 1 completed November 2, 2026. **Stopped at the owner approval gate. No installation, activation, migration, provisioning, pilot records, backup creation or restore was performed.** This report records direct Gateway inspection, not operational acceptance by Beth or Kris.

## Exact release and proposed changes

Reviewed development head: `5741a250d0b32643ae7eed86380a25373e61bb93`. Scheduling **0.2.1** remains pinned to `6f5a9bf51747399a309b39637a135841fe4cf2a1`; plugin subtree `893edbe31278946aa2aaa830a56d5d1812b87323`. Documentation advancing does not move that code pin.

All **15/15 SHA-256 checksums**, Git blob identities and source modes match the pinned GitHub tree and committed [manifest](scheduling-staff-files.sha256). Both reviewed commits contain the same plugin tree. The destination directory is absent. The [exact proposed comparison](../pilot/scheduling-install-diff.json) therefore contains **15 additions, zero replacements, zero deletions**, solely under `wp-content/plugins/csf-scheduling/`, with three new directories (root, assets, includes). Proposed modes: directories 0755/files 0644, subscription ownership. This is a manifest-derived comparison against observed absence; source was not staged remotely and rsync was not run. Recheck destination and perform the canonical exact-endpoint dry run after approval, before copying.

Later authorized state changes would be: active-plugin metadata adding only scheduling; 14 owned schema-v2 tables; migration seed/audit; approved staff capability/allowlist changes; approved facility policy; fictional resources/riders/appointments and associated audit/command records. No existing table alteration, website regeneration, global timezone change or other plugin update is proposed.

## Direct target inspection

Gateway observations span **00:12:56–00:28:05 UTC November 2**. Exact root is `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; home/siteurl both `https://sandbox.cornerstonefarmomak.com`; configured and connected database **wp_mdf4x**, prefix **TXGEh_**, environment **development**. Production and unassigned databases were excluded.

| Item | Observed | Release implication |
| --- | --- | --- |
| WordPress | 7.1.4, from installed source | Actual scheduling runtime remains to be tested |
| PHP | CLI 8.3.36 with mysqli; sandbox PHP 8.3 FPM pool configured | Pure policy/schema source runs on CLI; web runtime acceptance pending |
| MariaDB | 10.6.23-MariaDB-0ubuntu0.22.04.1 | Meets 10.6 family; actual migration/transactions not run |
| Kadence | 1.5.2, active template/stylesheet | Private UI and public smoke checks pending |
| Existing database | 12 InnoDB tables, utf8mb4_unicode_520_ci | Preserve existing tables and collation |
| New-table prerequisites | InnoDB DEFAULT; utf8mb4_unicode_ci available | New 14 tables use their explicit tested collation |
| DB privileges | Schema SELECT/INSERT/UPDATE/DELETE/CREATE/INDEX/ALTER present; not grantable | No privilege changes proposed; no DDL permission probe executed |
| Scheduling | Directory absent; zero owned tables | First-install plan applicable only while this remains true |
| Other plugins | Only csf-core 0.3.0 active; csf-emergency 0.1.0 inactive | Preserve both; no emergency modification |
| Inquiry | Option absent; installed inquiry source matches reviewed disabled-default source | Disabled; no setting writes |
| Hardening | DISALLOW_FILE_EDIT=true, WP_DEBUG_DISPLAY=false, config 0600 | Preserve existing configuration |
| Timezone | WordPress timezone empty / offset 0 (UTC) | Preserve global setting; independent facility timezone proposed |

Root 0750 UID10007/GID1002; plugin parent 0755 UID10007/GID1003; wp-config 0600 UID10007/GID1003. These inspected paths are not symlinks. PHP-FPM runs as the subscription owner. Existing Nginx/Apache configuration contains Basic Auth directives. No MU-plugin directory or database/cache drop-in appeared in the bounded wp-content listing. HTTP protection, MFA, response caching and browser behavior were **not** accepted from configuration alone; those require authorized pilot testing.

Database metadata was selected in explicit **READ ONLY** transactions, without WordPress bootstrap/hooks/cron. No credentials, account names, emails, password hashes or MFA values are included in committed evidence. Gateway's generic execution wrapper can label an execution changed; that wrapper flag is not evidence of a site mutation. Commands here inspected metadata, checked hashes/archives or evaluated pure pinned classes without loading WordPress.

Before/after comparisons: all **59 selected public-content row hashes** and selected settings hash unchanged. Baseline: 22 published pages plus one draft, nine attachments, 25 published menu items and one navigation record. This is the current metadata inventory, not a claim that all pages passed browser checks. Settings hash `e12a82fddc66ec6d859e69fe62e36076284ea27a1fdad4911cb37faa58ecd5f0` includes active plugins, URLs, theme/front-page settings, Kadence theme modifications and inquiry setting. See [sanitized evidence](../pilot/preflight-evidence.json). It does not constitute a complete filesystem integrity audit.

## Schema and compatibility review

The [actual-prefix DDL review](../pilot/scheduling-v2-TXGEh-proposed.ddl.txt) contains exactly 14 tables: settings, resources, appointments, allocations, commands, audit, riders, horses, availability, restrictions, details, history, slots and participants, all prefixed `TXGEh_csf_sched_`. It preserves InnoDB transactions, datetime(6), ASCII binary UUID/hash keys, indexes and signed client-horse slot identifiers. DDL-body SHA-256: `00217723716963ff0afb4a21689bb2776e8bf67edb37c02d4d536389014320a4`.

The pinned Schema class generated that exact DDL hash under sandbox PHP 8.3.36 in a pure process, with no WordPress or database connection. This file is **review material, never an SQL installation script**. Actual installed-plugin migration preview has not run because the plugin is absent and installation is not authorized. Existing configuration contains no literal migration-gate definition; recheck actual guarded runtime during the approved preview. Never enable the gate globally or override an explicit false protection.

First apply should produce 14 owned tables (26 total if the existing inventory is unchanged), settings singleton at schema2/revision0 with no staff and incomplete policy, plus migration audit identity. It must create no appointments/resources/riders or staff grants. Actual-prefix physical inspection, version/drift checks, seed/audit inspection and idempotency remain post-approval checks. Interrupted DDL stops the pilot; do not stamp versions or delete unexpected tables.

Historical [Sprint #9.1 local validation](scheduling-staff.md) covers MariaDB10.6.28 and MySQL8.0.46, 562 database assertions plus race/restore checks, browser tests and PHP8.1.34 checks. Those suites were **not rerun** for this documentation-only phase and do not establish WordPress7.1.4/PHP8.3.36/MariaDB10.6.23 protected-stack acceptance. No scheduling source was changed.

## Backup and recovery evidence

Latest observed private bundle: `/var/www/vhosts/cornerstonefarmomak.com/private/csf-dev-20261101-180328-1386830/`. DB capture approximately November1 18:03:28 UTC; wp-content archive approximately18:03:29 UTC. Directory0700, artifacts0600, subscription-owned. Exact-target path and URL metadata match the sandbox. All **six SHA256SUMS entries passed**, and compressed DB gzip integrity passed. This establishes retained artifact integrity, not freshness for a future installation or database import success. The existing backup script captures DB/wp-content and metadata, not wp-config; private configuration recovery must be paired separately.

Existing October31 `csf-sandbox-backup-20261031/restore-evidence.txt` was retrieved and reviewed (SHA256 `9f3d1a3bc7e559f63f1cf96abf5d8fc16aee3f558cb05e752c24788e2ae3d700`). It records isolated structural restoration of 12 InnoDB tables and **4,336 regular files, zero mismatches**, with representative counts. It explicitly did **not** bootstrap WordPress or verify login/rendering and predates later content. Preserve it; do not recreate that historical scratch environment or claim it proves current functional recovery. Encrypted off-server recovery custody is not verified by this evidence.

After separate approval, a **fresh exact-target backup and isolated functional recovery test** must pass before plugin copying: checksums, DB import/counts, restored files/config, isolated URLs/no outbound mail or public exposure, login and representative page rendering. Record target/time/private recovery location and remove only newly authorized temporary resources after review. No such backup/restore was executed in Phase1.

## Owner decisions and approval gate

Only **one WordPress account** exists, carrying administrator role and no scheduling grants. Its identity is not inferred. No verified distinct Beth/Kris accounts or operator mapping exists in this report. No matching MFA metadata or active MFA plugin was observed; external enforcement is **unverified**, not proven absent. Before any grant: owner verifies the numeric operator ID (manage_options), distinct Beth/Kris identities, intended view/manage/confirm/audit scope, and effective MFA/login evidence privately. Existing provisioning handles verified existing IDs, not automatic account creation. Any account creation or MFA/security installation needs separately scoped approval and cannot be slipped into a scheduling-only file release.

[Facility proposals](../pilot/README.md) distinguish an intentionally incomplete operating draft from a complete **fictional pilot-only test policy**. Unknown real operating hours, capacities, durations, welfare limits, travel buffers, Academy season/capacity, suitability and availability remain unresolved. The illustrative policy is not approval for real horse care or service delivery. Both JSON shapes passed the pinned Policy class under PHP8.3.36; the operating draft deliberately reports missing service hours/Academy/enabled services, while the synthetic proposal has no missing policy fields. Neither has been applied.

**Explicit authorization requested:** approve a protected-sandbox synthetic pilot under the [canonical ordered checklist](../05-backup-rollback-deploy.md#sprint-11--phase-1-evidence-and-approval-checklist-november-2-2026), beginning with a fresh target-bound backup and isolated functional recovery. File installation/activation, guarded migration, staff provisioning, approved fictional policy and synthetic records remain held until their named prerequisites and owner approvals are recorded. Supply verified staff/operator identities and MFA evidence privately and approve or revise the fictional policy before those steps. No approval for real operational scheduling, production, inquiries, emergency features or website changes is implied.

## Evidence provenance and validation

Key terminal Gateway jobs: initial/final read-only metadata `job_01M6CSKZ9800HJGTJESE60ENG4` / `job_01M6CTFNQJ00GBGT3EWWDRPE1W`; plugin listing `job_01M6CSEQYG00V5R9Q25X8DSVYH`; backup checksums `job_01M6CSNPJ700Q6MN6FC5H1T984`; gzip `job_01M6CSVYEM00KQH7X478ZJ8DFB`; retained recovery evidence `job_01M6CSP7RJ00H6Q640J73F2ZRJ`; web-auth configuration `job_01M6CSXDAJ00X6S2JC3Q4BQD6P`; pure policy/DDL review `job_01M6CT5JK8005XVPY6JANV1HAJ`; privilege inspection `job_01M6CT8X2K0094BJAE0CTBER82`; wp-content listing `job_01M6CTC4F900SNDVMRNH2BR1YN`; disabled-default source hash `job_01M6CTCF3R000GAA7QEZBSYJT6`. All completed successfully. Raw account mappings remain private in Gateway.

Local checks for this report: 15 hashes/Git objects/modes against pinned remote tree; 14-table DDL hash against PHP-generated source; JSON parsing; development/deployment guards; documentation diff whitespace check. Browser, migration, concurrent booking and staff hands-on acceptance on the protected stack remain pending approval. No acceptance is claimed on Beth's or Kris's behalf.
