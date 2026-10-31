# Sandbox exact-target preflight — pending server evidence

Scope: read-only inspection and deployment dry run only. **No deployment apply, activation,
site setup, backup creation, database writes or Plesk configuration change is authorized.**

## Owner-verified Plesk inventory

These values were supplied by the owner; they are not yet independent server observations.

| Property | Supplied value | Current independent status |
|---|---|---|
| Development URL | https://sandbox.cornerstonefarmomak.com | HTTPS request blocked at cloud CONNECT proxy; no origin response obtained |
| Relative document root | sandbox.cornerstonefarmomak.com | Absolute canonical path unknown; do not infer a /var/www/vhosts path |
| Development Plesk database | wp_mdf4x | wp-config DB_NAME and SELECT DATABASE() not yet observed |
| WordPress | 7.1.4 | Awaiting server readout |
| Domain PHP | 8.3.36 | Awaiting CLI/handler comparison and extension report |
| Kadence | 1.5.2 | Installed version/active parent and actual rendering unverified |
| TLS and redirect | Let's Encrypt enabled; HTTP→HTTPS 301 enabled | Owner-verified Plesk configuration; no external TLS/redirect result obtained |
| Production | cornerstonefarmomak.com / wp_iacb7 | Excluded; no connection/query/modification attempted |
| Additional unassigned database | wp_mdoaq | Excluded; no connection/query/modification attempted |

## Access evidence

The task's current managed runtime reports no outbound identities, secret bindings or runtime
variables. No SSH agent binding or .ssh directory is present in the task account/workspace.
The identity manifest contains no connections. No Plesk/SSH connector is available.
Network policy grants no SSH TCP destinations and does not allow the sandbox HTTPS host.
A HEAD request to the sandbox with redirects disabled returned HTTP 403 from the CONNECT
proxy, not from WordPress; it does not establish a site, DNS, certificate or server failure.
No origin request reached the sandbox through that denied connection.

**Blocked:** the actual absolute path, configured/connected database, live preflight and
server-specific rsync difference cannot be verified without authorized server command output
or secure SSH access plus the required network route. No credentials should be pasted in chat.

## Prepared exact-target checks

`scripts/sandbox-preflight.py` resolves and reports the canonical document root, requires the
owner-confirmed sandbox directory name and a regular wp-config inside it, checks only the
non-secret DB_NAME/table_prefix/WP_ENVIRONMENT_TYPE settings, and rejects any DB_NAME other
than wp_mdf4x before database queries. It refuses production, unassigned and unknown databases.

It uses WP-CLI db query **before wp-settings.php**, not wp eval/option/theme commands, and
uses START TRANSACTION READ ONLY for every SELECT. It first verifies SELECT DATABASE(); only
then reads safe options from the explicitly qualified wp_mdf4x options table. The production
and unassigned databases are never named in queries. WP-CLI still evaluates wp-config.php;
custom code there must be reviewed before claiming absolute side-effect isolation. This
method does not run plugin/theme/MU-plugin initialization. Do not use the older general
bootstrap-based inspector under the current no-database-writes constraint.

The script checks exact home/siteurl, declared development/staging environment, noindex,
permalinks, active real Kadence parent, Kadence version header, CLI WP/PHP versions, required
PHP extensions and linked plugin destinations. It does not activate Kadence, edit settings,
verify web-handler equality or prove browser compatibility. A mismatch is a stop/report,
not permission to fix it. Version drift requires review, not an upgrade/downgrade.

Its optional --dry-run invokes rsync with --dry-run hardcoded for both CSF plugin directories.
There is no --apply flag. It requires a reviewed existing tooling checkout outside the site;
it must not be uploaded/cloned onto the VPS during this read-only task. A remote rsync dry run
from an existing local checkout is the alternative if no server tooling checkout exists.

## Current local validation

- 14 unittest cases pass (including production/unassigned/unknown database subcases), with
  fixture files and mocked command outputs. They verify refusal before queries/copies, read-only
  SQL, exact URL, real-theme identity, CLI mismatch, extension failures, symlink rejection and
  mandatory rsync --dry-run. These are **not** actual sandbox results.
- Existing deployment/backup and generic Plesk-preflight guard suites remain separate local
  tests. Historical rsync fixture output from Milestone B is **not** a sandbox deployment diff.
- Real sandbox dry run: **not executed**. Absolute path and DB verification: **not executed**.
- No backup/restore verification or real Kadence browser acceptance is claimed in this report.

See [the canonical runbook](../05-backup-rollback-deploy.md#sandbox-read-only-exact-target-inspection)
for the next commands. Remain blocked for installation until actual evidence and separate
apply authorization are supplied.
