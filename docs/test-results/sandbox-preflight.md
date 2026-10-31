# Sandbox status — October 31, 2026

Current source: the owner's latest Gateway execution and real-Kadence clarification. These
are owner-supplied remote results; this Codex task did not execute the VPS operations.
Earlier pending-installation/setup/permalink statements are superseded. **Do not repeat
completed work.** No new VPS changes or code deployment are authorized.

## Exact target

| Property | Reported verified value |
|---|---|
| Sandbox | https://sandbox.cornerstonefarmomak.com |
| Canonical document root | `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com` |
| Configured / connected DB | `wp_mdf4x` / `wp_mdf4x`; options `TXGEh_options` |
| Stack | WordPress 7.1.4, domain PHP 8.3.36 (plesk-php83-fpm), MariaDB 10.6.23, real Kadence 1.5.2 active |
| Security / config | Valid Let's Encrypt/TLS 1.3, HTTP→HTTPS 301, HTTP 401 password protection, blog_public=0; development environment, DISALLOW_FILE_EDIT=true, WP_DEBUG_DISPLAY=false; syntax passed, live config ownership preserved and mode 0600 |
| Full backup directory | `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/` |
| Backup captures | October 31, 2026: DB export **10:08:58 AM PDT** (17:08:58 UTC); file archive **10:09:42 AM PDT** (17:09:42 UTC); current hardened config and complete sandbox files included |
| Existing scratch DB | `csf_sandbox_restore_20261031`; private restored filesystem not publicly served |
| Existing private evidence | `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt` |
| Historical staged private source | Pinned to `d360dbfd5a0441fa142a3b963a4a42c5145d9738`; exact existing checkout path not included in the forwarded report |
| Historical reviewed plugin release | Trees verified against `52401dcd9a90ca87ad074c1078ad6633df5be77e` |
| Production | `/var/www/vhosts/cornerstonefarmomak.com/httpdocs`, DB `wp_iacb7`; untouched/excluded |
| Unassigned DB | `wp_mdoaq`; excluded, including as scratch restore target |

## Completed work

| Task | Gateway-reported result |
|---|---|
| Full sandbox files/config/DB backup and archive/checksums | Complete at the private directory above |
| Isolated scratch restore | All 12 InnoDB tables/count samples verified; 4,336 regular files, zero mismatches |
| Functional WordPress login/rendering on restoration | NOT RUN; structural recovery is not functional recovery |
| Fresh gate bundle | csf-dev-20261031-184634-36879 at about 18:46 UTC; verifier passed; job evidence in doc 05 |
| Private source staging | d360dbf; plugin trees matched reviewed 52401dc at preparation time |
| Exact-target Plesk preflight and deployment dry run | Passed for that historical release |
| Public plugin | csf-core 0.2.0 installed and active |
| Website content | All 21 pages published; all rendered via do_blocks(), one H1 each |
| Navigation and home | Primary/mobile/footer menus assigned; static Home |
| Routes | /%postname%/, 162 rules, standard sandbox .htaccess installed; Apache syntax passed |
| Idempotency preview | Repeated wp csf setup --dry-run reports pages/footer ok |
| Emergency | Inactive and non-operational; no activation authorized |
| Production / excluded DBs | Untouched |

Keep the full backup and existing private restore-evidence.txt unchanged. Its capture predates
the completed installation/setup and must not be represented as a backup of the current build.
The original config-only artifact remains separately at
/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak. The completed structural
restore must not be scheduled again merely because documentation changed.

## Real Kadence — confirmed results versus pending acceptance

Kadence 1.5.2 is active and WordPress-level rendering passed. **The newer remote roadmap reports shared gutter [Issue #1](https://github.com/kks787-ship-it/KksSandbox/issues/1)** after the earlier no-confirmed-defects clarification: intro text reaches the viewport edge while headings are inset, observed on Meet Beth and Camps & Clinics. A shared CSS candidate is locally reproduced/corrected using downloaded Kadence 1.5.2; **no authenticated Plesk sandbox testing was performed**. The owner chose local completion/review preparation until secure access is arranged. See [local layout evidence](kadence-layout.md).
Unauthenticated HTTP 401 is expected password protection, not a route defect or HTTP 200 proof.
Historical substitute-theme tests cannot establish real Kadence compatibility.

Pending configuration/content: Site Footer synced block **27** exists but is not placed in
Footer Builder; header/global palette/typography/mobile navigation need acceptance; original
logo/authentic photos are absent; 23 owner-confirmation notes and the Acuity destination remain
open. Treat those configuration tasks separately from the reported gutter defect; reproduce the shared issue before changing CSS.

Test Home, Lessons, Meet Beth, Horse Training, Camps/Clinics and Contact at 390/768/1280px,
then audit all 21 pages, including other text sections, gutters, keyboard navigation, overflow and Gutenberg. Preserve full-bleed backgrounds and do not force-regenerate content. Verify _kad_post_layout,
_kad_post_content_style, _kad_post_title and _kad_post_vertical_padding against actual 1.5.2.
Use the [authenticated procedure](../07-website-installation.md#real-kadence-acceptance-procedure).

## Current development versus installed release

The new **0.3.0 candidate has not been deployed**. It fixes deployment modes, setup error reporting and shared layout gutters, and adds an inquiry
form that stays disabled until actual delivery is verified. Safe non-forced Contact integration
is regression-tested locally; actual saved sandbox content has not been inspected or changed.
Recipient: beth.siemon@cornerstonefarmomak.com; transport unknown; owner explicitly requests
keeping the form off. Local results are in [website finishing](website-finishing.md).

A future approved core-only update needs the reviewed new source SHA, current sandbox backup
in the existing gate format, evidence provenance and a core-only dry run. Preserve the old
source pin and preparation record; do not silently call their pass a test of 0.3.0. No rebuild,
new setup, emergency activation, permalink change or production operation is part of this task.
Separate WP_HOME/WP_SITEURL override findings have not been forwarded; do not invent them.
