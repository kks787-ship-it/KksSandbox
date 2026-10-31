# 05 — Backup, Rollback & Deployment

## Environments

| Environment | Purpose | Touched by this repo? |
|---|---|---|
| Production (current site) | Live Tucker/Elementor site | **No.** Not without explicit written approval. |
| Development (new install, Kadence) | Build and review | Yes, via the scripts below. |
| Local | Developer testing | Yes. |

Set `define( 'WP_ENVIRONMENT_TYPE', 'development' );` in the dev site's `wp-config.php`. The
scripts refuse to run against a site reporting `production`.

## Backups

**Before every deployment** to dev:

```bash
bash scripts/backup-dev.sh /var/www/vhosts/<dev-domain>/httpdocs ~/csf-backups
```

Produces `database.sql.gz`, `wp-content.tar.gz`, the WordPress version, the plugin list, and
SHA-256 checksums in a timestamped directory with `700` permissions.

Additionally, configure **Plesk Backup Manager** for the dev subscription (daily, 14-day
retention) and confirm the production subscription has its own scheduled backups stored
**off the server** (e.g. S3-compatible or SFTP remote storage). Verify by actually restoring
one backup to a scratch subscription — an untested backup is not a backup.

Backups contain personal data. Store copies encrypted and delete them per the retention policy.

## Deployment (dev)

```bash
git pull
bash scripts/deploy-plugins.sh /var/www/vhosts/<dev-domain>/httpdocs          # dry run
bash scripts/deploy-plugins.sh /var/www/vhosts/<dev-domain>/httpdocs --apply  # copy
```

Only `wp-content/plugins/csf-*` is touched. Then, if routes changed: **Settings →
Permalinks → Save**.

## Rollback

| Problem | Rollback |
|---|---|
| Bad plugin code | `git checkout <previous-tag>` then `deploy-plugins.sh --apply`. |
| Bad plugin code, site unreachable | Rename `wp-content/plugins/csf-<name>` via Plesk File Manager to deactivate it. Emergency data is retained. |
| Bad content/settings | Restore `database.sql.gz`: `gunzip -c database.sql.gz \| wp db import -` (dev only). |
| Lost uploads/files | Extract `wp-content.tar.gz` over the site. |

Tag each approved release: `git tag -a v0.1.0 -m "…"`.

## Production launch (future, separate approval)

The production switch will be its own documented plan covering: final backup of the old site,
URL redirect map (including old `/index.php/` URLs), DNS/vhost changes in Plesk, certificate,
search-console resubmission, and a rollback window during which the old site remains restorable.

## Takeover deployment safeguards

Deployment and backup now require successful environment detection and accept only `local`,
`development` or `staging`; WP-CLI errors, unknown output and production are rejected before
work. Backups use a restrictive umask and support WordPress paths containing spaces.
The backup script does not include wp-config.php or prove restorability: use a secure server
backup for configuration and perform a scratch restore. Keep backups outside the web root.

See the [takeover development deployment plan](10-takeover-audit.md#development-deployment-plan)
for inspection, release review, backup/restore evidence and approval before applying changes.
