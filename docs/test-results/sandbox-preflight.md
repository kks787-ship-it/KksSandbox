# Sandbox exact-target preflight — Gateway evidence received

Source: the owner's forwarded Gateway SSH report from `serve1`. These are Gateway-reported
server observations, not commands independently executed by this Codex session.
Scope remains read-only inspection and deployment dry run only. No apply, activation, site
setup, backup creation, database writes or Plesk configuration changes are authorized.

## Verified in the supplied Gateway report

| Check | Gateway-reported result |
|---|---|
| Document root and canonical path | `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; Plesk path equals resolved path |
| Configured DB_NAME | `wp_mdf4x` |
| SELECT DATABASE() | `wp_mdf4x` |
| Database session | `tx_read_only = 1` |
| MariaDB | 10.6.23; default engine InnoDB (individual future emergency tables still need engine verification) |
| Production document root | `/var/www/vhosts/cornerstonefarmomak.com/httpdocs`; excluded from changes |
| HTTPS and access protection | Gateway reports valid Let's Encrypt certificate, TLS 1.3, expiry January 29, 2027; HTTP 301 to HTTPS; unauthenticated HTTPS returns HTTP 401 |
| Options table | `wp_mdf4x.TXGEh_options` |
| Stored home/siteurl | Both `https://sandbox.cornerstonefarmomak.com` |
| WordPress | Version file 7.1.4; DB schema version matches core file |
| Domain PHP | 8.3.36; `plesk-php83-fpm`; FPM binary version confirmed |
| PHP extensions | Required checks passed; mysqli, curl, mbstring, XML/DOM, ZIP, GD, Imagick, OpenSSL, sodium and intl present |
| Real Kadence | 1.5.2; template and stylesheet both kadence (active parent theme) |
| Indexing | blog_public = 0 |
| Permalink structure | Empty; plain URLs |

Plesk's connection initially selected its own `psa` database. Gateway's identity guard stopped
before any WordPress options query; explicitly selecting `wp_mdf4x` resolved the mismatch.
Retain this guard on every new DB connection: never assume Plesk's default schema is WordPress.
Use the explicit database and qualified `wp_mdf4x.TXGEh_options` for sandbox option reads.
The report states that neither `wp_iacb7` nor `wp_mdoaq` was queried, no credentials were
printed, WordPress/plugins/themes were not initialized, and no changes were made.

## Outstanding gates

| Item | Status and next read-only action |
|---|---|
| Environment designation | Latest Gateway report finds WP_ENVIRONMENT_TYPE not explicitly defined. Existing guards must refuse deployment. Confirm any inherited/included source before concluding the effective runtime value; proposed development constant awaits approval. |
| Debug display | Gateway reports WP_DEBUG enabled and WP_DEBUG_DISPLAY not explicitly disabled. Proposed WP_DEBUG_DISPLAY=false and DISALLOW_FILE_EDIT=true are not applied. Retain existing WP_DEBUG; inspect private logging separately. |
| Effective URL overrides | Stored URLs match. Inspect only WP_HOME/WP_SITEURL definitions and their referenced sources, reporting literal/absent/dynamic without printing configuration files or other constants. |
| Pretty permalinks | Not configured. Controlled future configuration/setup needs separate authorization; do not save permalinks now. |
| Real Kadence behavior | Version and active theme identity pass; layouts, editor, navigation, accessibility and cache behavior remain untested. |
| Backup and restore | Gateway found an older archive, not a verified current recovery point or completed scratch restore. A fresh pre-change backup and scratch restore require explicit authorization. |
| Exact-target file dry run | Not executed. Gateway reports Git, WP-CLI and rsync available, but no application checkout on the VPS. Staging source requires authorization. A file-only comparison does not authorize plugin installation. |

WordPress 7.1.4's `wp_get_environment_type()` checks the environment variable, then a nonempty
constant (which takes precedence), and falls back to **production** for absent/invalid values.
This ordering was checked in the local same-version core source, not executed on the VPS.
A sandbox hostname or `blog_public=0` does not establish a staging/development environment.
If constant/environment sources are dynamic or unresolved, preserve status as unknown.

The original authorization permits read-only predeployment inspection. Reading just the
non-secret WP_ENVIRONMENT_TYPE, WP_HOME and WP_SITEURL keys does not require permission to
change them; expand Gateway's self-imposed DB_NAME-only inspection to those named values.
Do not dump wp-config, passwords, salts, arbitrary includes or the environment. No wp eval
or normal WordPress initialization is needed for this follow-up.

## Dry-run boundary

Use the exact canonical root above and only its csf-core/csf-emergency plugin directories.
Confirm content/plugins paths are not symlinks to a different site. Compare against reviewed
release commit `52401dcd9a90ca87ad074c1078ad6633df5be77e` (prepared plugin ZIPs remain unchanged).
Do not clone/upload/extract sources onto the VPS under the current scope. An existing reviewed
checkout outside web roots or an existing secure local-to-remote rsync path is needed.
If neither is available, report the missing source/access prerequisite rather than staging files.

Use mandatory `rsync --dry-run --itemize-changes --delete` and preserve its exit status/output;
no plugin activation, setup or WordPress bootstrap. Record proposed added/changed/deleted
files and source commit. Do not remove --dry-run. No actual server diff is claimed here.
The strict deployment/preflight scripts remain unchanged and must continue to block unknown
or production environment type and failed installation prerequisites. This file-only operation
is distinct from installation readiness; it cannot satisfy the backup or environment gates.

## Local validation and access limitations

The prior 14 sandbox guard unittest cases, 22 deployment/backup scenarios and six generic
Plesk-preflight scenarios passed locally using fixtures/mocked outputs. They do not establish
VPS behavior. Historical local rsync output remains a fixture, not a sandbox diff.
This update changes documentation only; no application behavior or guards were weakened.

Gateway's SSH execution tools are still not exposed in this Codex session. Its native cloud
network denied a prior sandbox HTTPS CONNECT request; that is not evidence of an origin/TLS
failure. Continue the server commands in the working Gateway conversation and supply the
non-secret results here. Real sandbox dry run and full installation readiness remain pending.

## Proposed next authorization package

The latest Gateway report recommends changes; it is not itself the owner's explicit approval.
Current server scope remains read-only. The prepared [sandbox preparation package](../05-backup-rollback-deploy.md#sandbox-preparation-approval-package)
puts a pre-change backup before configuration edits, isolates a scratch restore from all three
existing databases, pins source, and ends at a reviewed file-only dry run. It excludes plugin
installation/activation, permalink/site-setup changes and production operations. The strict
preflight must continue reporting unresolved permalink/URL/backup gates rather than being
weakened to obtain a passing result. Gateway remains unavailable as a callable tool in this
Codex session; no direct execution capability is implied by receiving its reports here.
