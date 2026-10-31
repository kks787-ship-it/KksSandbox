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
| `wp-content/plugins/csf-core/` | **The public website** — brand design system, block patterns and page layouts, repeatable site setup, Knowledge Library, horses, camps & clinics, farm contact details, booking buttons, optional inquiry form (disabled until delivery is verified). |
| `scripts/inspect-environment.sh` | Read-only report on the dev server (PHP, DB, web server, plugins, HTTPS, permissions…). |
| `scripts/backup-dev.sh` | Database + `wp-content` backup of the dev site. |
| `scripts/deploy-plugins.sh` | Copy the custom plugins to the dev site (dry run by default). |
| `scripts/local-dev.sh` | Build/serve a disposable local WordPress (SQLite) for development and tests. |
| `tests/e2e/` | Automated browser tests (accessibility, responsive, links, editor validity, emergency banner…). |
| `tests/fixtures/themes/csf-kadence-standin/` | **Test-only** stand-in theme used when Kadence is unavailable. Never deploy. |
| `docs/` | Architecture, roadmap, runbooks, and the list of facts the owners must confirm. |

WordPress core, the Kadence theme, uploads, and `wp-config.php` are **not** committed.

## Start here

1. [`docs/00-environment-inspection.md`](docs/00-environment-inspection.md) — run the inspection on the dev server first.
2. [`docs/03-owner-confirmation-register.md`](docs/03-owner-confirmation-register.md) — business facts that must be confirmed before launch.
3. [`docs/01-architecture.md`](docs/01-architecture.md) — how the platform is structured and why.
4. [`docs/02-roadmap.md`](docs/02-roadmap.md) — phased delivery plan.
5. [`docs/04-emergency-plugin.md`](docs/04-emergency-plugin.md) — emergency system: what works today, what does not, and how to operate it.
6. [`docs/05-backup-rollback-deploy.md`](docs/05-backup-rollback-deploy.md) — backups, rollback, and deployment.
7. [`docs/06-brand-and-site-setup.md`](docs/06-brand-and-site-setup.md) — brand system and page structure.
8. [`docs/07-website-installation.md`](docs/07-website-installation.md) — installing the website on the dev site (Kadence steps).
9. [`docs/08-content-and-asset-inventory.md`](docs/08-content-and-asset-inventory.md) — pages, content types, assets, photo shot list.
10. [`docs/09-phase-2-status.md`](docs/09-phase-2-status.md) — Phase 2 status, limitations, incomplete features, owner decisions.
11. [`docs/test-results/phase-2.md`](docs/test-results/phase-2.md) and [`docs/screenshots/`](docs/screenshots/) — test results and screenshots.

## Current build

The owner reports csf-core 0.2.0 active and all 21 pages published on the protected sandbox.
Real Kadence 1.5.2 sitewide acceptance is pending; the newer roadmap reports shared gutter
[Issue #1](https://github.com/kks787-ship-it/KksSandbox/issues/1), not yet reproduced/fixed here.
The local 0.3.0 candidate has not been deployed; its inquiry form remains off by default.
See [current status](docs/test-results/sandbox-preflight.md) and [local validation](docs/test-results/website-finishing.md).

## Development

```bash
composer install          # WordPress Coding Standards + PHPCompatibility
composer lint             # php -l on every plugin file
vendor/bin/phpcs          # coding standards (must report no errors)

bash scripts/local-dev.sh install && bash scripts/local-dev.sh start   # local site on :8890
npm ci && npm run test:e2e                                        # browser tests
```

Requirements: WordPress 6.5+ (csf-core), 6.4+ (csf-emergency), PHP 8.1+.

## Codex takeover

Start with [AGENTS.md](AGENTS.md), [business context](docs/CSF-BUSINESS-CONTEXT.md),
and the [takeover audit and current validation](docs/10-takeover-audit.md).
The original architecture, roadmap and deployment runbooks remain canonical.
