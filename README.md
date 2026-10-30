# Corner Stone Farm Digital Ranch (CSF-OS)

Source code and documentation for the new Corner Stone Farm website and operating platform
(Omak, Washington — <https://cornerstonefarmomak.com>).

> **The existing production website is not touched by anything in this repository.**
> All work targets the separate development WordPress install on the Plesk VPS. Nothing here
> deploys automatically, and every script refuses to run against a site whose
> `WP_ENVIRONMENT_TYPE` is `production`.

## What is in this repository

| Path | Purpose |
|---|---|
| `wp-content/plugins/csf-emergency/` | **Corner Stone Farm Emergency Management** — horse emergency ID pages (`/h/{code}`), printable QR tags, farm emergency status, sitewide banner, `/emergency` page, event log. |
| `wp-content/plugins/csf-core/` | Site foundations — Knowledge Library content model, configurable “Book a Lesson” button. |
| `scripts/inspect-environment.sh` | Read-only report on the dev server (PHP, DB, web server, plugins, HTTPS, permissions…). |
| `scripts/backup-dev.sh` | Database + `wp-content` backup of the dev site. |
| `scripts/deploy-plugins.sh` | Copy the custom plugins to the dev site (dry run by default). |
| `docs/` | Architecture, roadmap, runbooks, and the list of facts the owners must confirm. |

WordPress core, the Kadence theme, uploads, and `wp-config.php` are **not** committed.

## Start here

1. [`docs/00-environment-inspection.md`](docs/00-environment-inspection.md) — run the inspection on the dev server first.
2. [`docs/03-owner-confirmation-register.md`](docs/03-owner-confirmation-register.md) — business facts that must be confirmed before launch.
3. [`docs/01-architecture.md`](docs/01-architecture.md) — how the platform is structured and why.
4. [`docs/02-roadmap.md`](docs/02-roadmap.md) — phased delivery plan.
5. [`docs/04-emergency-plugin.md`](docs/04-emergency-plugin.md) — emergency system: what works today, what does not, and how to operate it.
6. [`docs/05-backup-rollback-deploy.md`](docs/05-backup-rollback-deploy.md) — backups, rollback, and deployment.
7. [`docs/06-brand-and-site-setup.md`](docs/06-brand-and-site-setup.md) — Kadence/Gutenberg configuration, brand system, page structure.

## Development

```bash
composer install          # WordPress Coding Standards + PHPCompatibility
composer lint             # php -l on every plugin file
vendor/bin/phpcs          # coding standards (must report no errors)
```

Requirements: WordPress 6.4+, PHP 8.1+.
