# 00 — Development Environment Inspection

**Status:** Not yet performed on the VPS. This repository was built and tested in an isolated
local environment (WordPress 6.5.5, PHP 8.3, SQLite). No recommendation below that depends
on the real server should be acted on until the report exists.

## Why first

The specification requires verifying the environment before changing it. The old site had
broken dependencies, `/index.php/` permalinks, and mixed-content warnings — symptoms of
server configuration as much as of the theme. We need to know which is which.

## How to run (read-only)

On the VPS, as a user who can read the **development** site (not production):

```bash
git clone <this repo> ~/csf-digital-ranch
# Run as the development subscription user, not root.
bash ~/csf-digital-ranch/scripts/inspect-environment.sh /var/www/vhosts/<dev-domain>/httpdocs > ~/env-report.md
```

The script only reads. It never edits files, the database, Plesk, or web server
configuration. It does not print database passwords or salts, but it does include paths and
hostnames: review it before sharing.

If `wp-cli` is not installed, the WordPress sections are skipped; use **Tools → Site Health →
Info → Copy site info to clipboard** in the dev site admin and attach that instead.

## What the report must answer

| Item | Where it comes from | Why it matters |
|---|---|---|
| WordPress version | `wp core version` | Use WordPress 6.6+ for the prepared release; verify theme JSON v3 compatibility. |
| PHP version (domain, not just CLI) | Plesk → PHP Settings; Site Health | Plugins require 8.1+; Select a security-supported PHP release compatible with the installed stack. |
| Database server & version | `SELECT VERSION()` | MySQL 8 / MariaDB 10.6+ recommended. |
| Apache / nginx arrangement | `nginx -v`, `apache2ctl -v`, `.htaccess` | Plesk usually runs nginx as reverse proxy in front of Apache. Determines how rewrites are configured. |
| Installed plugins & themes | `wp plugin list`, `wp theme list` | Identify anything carried over from the old site that should not be. |
| Filesystem permissions & owner | `stat`, world-writable scan | Plesk sites should be owned by the subscription user, not `root` or `www-data`. |
| HTTPS & certificate | `openssl`, response headers | Expect a valid Let’s Encrypt certificate and `home`/`siteurl` on `https://`. |
| Permalink rewriting | Random URL should return a WordPress 404 | If it 404s at the web server level, rewrites are not reaching WordPress — the cause of `/index.php/` URLs. |
| Mixed content | `http://` asset references on the home page | Must be zero. |
| Backup configuration | Plesk Backup Manager | Schedule, retention, and whether a copy leaves the server. |
| Tooling | `git`, `composer`, `wp`, `rsync` | Determines the deployment method. |
| Resource limits | CPU, RAM, disk, PHP limits | Sizing for image processing, backups, and future portals. |

## Initial expectations to verify (not yet confirmed)

- `WP_ENVIRONMENT_TYPE` should be set to `development` in the dev site's `wp-config.php`.
  The backup and deploy scripts refuse to run against `production`.
- Search engine visibility should be **off** on dev (`blog_public = 0`), and the dev site
  should ideally be behind Plesk password protection.
- Permalinks should be `/%postname%/`. If `/index.php/` appears, fix the web server
  rewrite configuration rather than living with it.
- `DISALLOW_FILE_EDIT` should be `true`.

Record the findings in a new section at the bottom of this file once the report exists.

See the current [Milestone B preflight and Plesk checklist](05-backup-rollback-deploy.md#milestone-b--development-deployment-preparation). The inspector now refuses production/unknown environments and reports failed checks. It skips ordinary plugins/themes but existing must-use plugins/drop-ins can still execute during WordPress bootstrap.

## Owner-supplied sandbox identity (subsequent Milestone B preparation)

The owner confirmed the development URL is `https://sandbox.cornerstonefarmomak.com`, its
Plesk-relative document root is `sandbox.cornerstonefarmomak.com`, and its assigned database
is `wp_mdf4x`. Reported stack: WP 7.1.4, PHP 8.3.36, Kadence 1.5.2, Let's Encrypt and
HTTP→HTTPS 301 enabled. Production remains `cornerstonefarmomak.com` / `wp_iacb7`.
`wp_mdoaq` is unassigned and must not be altered.

These are owner/Plesk observations, not an independent wp-config or connected-database check.
The absolute root remains unknown. Under the current prohibition on database writes, use the
new **non-bootstrap** `scripts/sandbox-preflight.py`, not the general inspector above: normal
WordPress bootstrap can write through plugins/MU-plugins even for nominally read commands.
See [current evidence and blockers](test-results/sandbox-preflight.md).
