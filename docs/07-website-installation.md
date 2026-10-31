# 07 — Website Installation (Development Site)

**Scope:** the separate sandbox on Plesk. Preparation is complete; the two CSF plugins are
not installed or active yet. Each installation/activation/setup stage below requires separate
approval. The production site remains excluded.

Estimated time: 45–60 minutes, most of it in the Kadence Customizer.

## Staged installation approval boundaries

**Final sandbox-only plan; not execution authorization.** Gateway preparation is complete
through a passed exact-target preflight/dry run, with the existing source pinned to d360dbf.
Preserve those results and do not repeat infrastructure preparation or advance the checkout.
The immediate objective is csf-core/csf-emergency testing with **real Kadence 1.5.2**.

| Stage | Proposed action | Authorization/status |
|---|---|---|
| Final gate | Reuse completed backup/structural restore and existing private restore-evidence.txt; verify actual fresh target-bound format | Remote gate not run by this task; conditional limited refresh only if genuinely needed/approved |
| A | Copy only the two new CSF plugin directories, **leave inactive** | Separate files-only installation approval required; not started |
| B | Activate csf-core and csf-emergency; verify activation effects | Separate activation approval required; not started |
| C | Generate/verify the website's 21 pages, menus and synced footer; configure Kadence | Separate site-setup approval required; not started; permalink changes must be explicitly included if needed |
| D | Test the actual public build/editor/navigation/accessibility/emergency rendering on Kadence | Not started; authenticated sandbox checks within approved test scope |

The isolated restoration passed structural/file checks (12 InnoDB tables; 4,336 regular files,
zero mismatches), **not functional WordPress login/page rendering**. Disclose this limitation
in the files-only review; a nonempty restoration-evidence file is not a claim it passed.

## 0. Before you start

Current state is from the owner's completed Gateway report dated October 31, 2026:

- [x] Exact sandbox identity, WP 7.1.4, PHP 8.3.36 and real Kadence 1.5.2 verified.
- [x] Development/editing/debug-display constants verified, syntax passed and live config ownership/mode 0600 preserved. Do not reapply.
- [x] Original config-only recovery evidence retained at `/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak`.
- [x] HTTPS/password protection/indexing checks passed.
- [x] Full backup at `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/`; archive/checksum verification passed.
- [x] New isolated scratch DB restored; all 12 InnoDB tables/count samples and restored 4,336 files verified with zero mismatches.
- [x] Private source staged at d360dbf, plugin trees matched to 52401dc.
- [x] Exact-target Plesk preflight and deployment dry run passed; only two new plugin directories proposed.
- [x] Capture times supplied: DB export October 31, 10:08:58 AM PDT; files 10:09:42 AM PDT; existing scratch DB csf_sandbox_restore_20261031 and private restore-evidence.txt path recorded.
- [ ] Actual installation-time fresh target-bound format/gate verified against those existing artifacts and evidence; no new full preparation required.
- [ ] Functional login/page-rendering test on isolated restoration (explicitly unrun; keep qualification).
- [ ] Separate approval for Stage A file copying; later approvals for B/C and any permalink change.

See the [current report](test-results/sandbox-preflight.md) and
[gate procedure](05-backup-rollback-deploy.md#backups-and-verified-backup-gate-before-any-apply).
Do not rerun the completed full backup/restore/source staging/dry-run package. Refer to
existing Gateway records for the actual staged checkout, scratch identities and raw output.

## 1. Copy the plugins — Stage A, after approval only

Target: `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`,
URL `https://sandbox.cornerstonefarmomak.com`, DB `wp_mdf4x`. Only csf-core and csf-emergency
directories may be introduced. They must still be absent/inactive at the operation boundary;
if target state has changed, stop for review instead of overwriting unexpected work.

Before approval, use existing records for the actual private source/gate-bundle paths; this
plan does not guess them. The existing restoration evidence path is supplied below and must
be reused, not regenerated. Preserve the existing full backup and functional restore
qualification. Verify the final gate without changing file timestamps or weakening checks.
Do not use the old config-only backup as the full-site/app gate bundle.

The following is the reviewed **future** files-only command. It must not run until Stage A
is explicitly approved, including its known functional-recovery limitation:

```bash
set -euo pipefail
: "${CSF_STAGED_SOURCE:?Use the existing private d360dbf checkout path from Gateway records}"
: "${CSF_GATE_BACKUP:?Use the actual fresh script-compatible target-bound backup path}"
CSF_RESTORE_EVIDENCE='/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt'
CSF_DEV_ROOT='/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com'
CSF_DEV_URL='https://sandbox.cornerstonefarmomak.com'
cd "$CSF_STAGED_SOURCE"

test "$(git rev-parse --verify HEAD)" = 'd360dbfd5a0441fa142a3b963a4a42c5145d9738'
git diff --quiet 52401dcd9a90ca87ad074c1078ad6633df5be77e -- wp-content/plugins/csf-core wp-content/plugins/csf-emergency
test -z "$(git ls-files --others -- wp-content/plugins/csf-core wp-content/plugins/csf-emergency)"
for plugin in csf-core csf-emergency; do
  test ! -e "$CSF_DEV_ROOT/wp-content/plugins/$plugin"
  test ! -L "$CSF_DEV_ROOT/wp-content/plugins/$plugin"
done

bash scripts/verify-dev-backup.sh "$CSF_GATE_BACKUP" "$CSF_DEV_ROOT" "$CSF_DEV_URL" "$CSF_RESTORE_EVIDENCE"
# NO EXECUTION AUTHORIZATION IN THIS DOCUMENT. Run only after explicit Stage A approval:
bash scripts/deploy-plugins.sh "$CSF_DEV_ROOT" --expected-url "$CSF_DEV_URL" \
  --backup "$CSF_GATE_BACKUP" --restore-evidence "$CSF_RESTORE_EVIDENCE" --apply
```

The existing deploy script validates environment/home/siteurl using WordPress initialization;
a source/target-only rsync dry run does not do that. Review existing startup code and the
authorized verification scope before executing it; do not promise a DB-side-effect-free
WordPress bootstrap. The intended Stage A change is inactive plugin files only, not activation
or deliberate application schema/content mutation.

After copying, verify source/target checksums and PHP syntax, expected file ownership/modes,
continued sandbox access protection and unchanged active-plugin state using the agreed
inspection method. Do not activate via the admin UI or execute setup as a verification shortcut.
Keep the source SHA, file-copy exit status and resulting file inventory in the installation record.

Stop for owner review. If Stage A fails, rollback is limited to the two directories recorded
absent before copying and still inactive; do not import a DB, restore production or delete
the private source/backup. If activation unexpectedly happened, stop and use a separate
state-aware recovery plan. Do not perform plugin ZIP upload to bypass the script gate.

## 2. Activate — Stage B, separate approval

Recheck the verified backup/recovery point and approved scope, then activate **Corner Stone
Farm Core** and **Corner Stone Farm Emergency Management** only on the sandbox.

Activation registers content types, seeds public taxonomies, creates/updates emergency tables
and capabilities, and flushes rewrite rules. It is a DB/configuration operation, not file copying.
It does not generate the 21 public pages. Verify plugin status, PHP logs, table/schema engine,
capabilities and retained configuration; report errors instead of continuing to setup.

Keep emergency functionality labeled **non-operational**. MFA, concurrency/audit consistency,
physical tags, mobile/cache checks and owner acceptance remain separate release gates.
A later activation rollback must account for tables/roles/options, not assume removing plugin
files restores the original database. Stop for review before Stage C.

## 3. Run site setup — Stage C, separate approval

Preview Tools → Corner Stone Farm Setup with Dry run, or `wp csf setup --dry-run` after
the activation-stage review. Preserve edited pages and menus. Never use --force on owner content.
If preview finds unexpected existing content or errors, stop and resolve them before execution.

Only after site-setup approval run `wp csf setup` to generate missing layouts, hierarchy,
Home front page, primary/footer menus and synced Site Footer, then `wp csf status`. Verify all
21 expected page entries, one H1 per page and valid editor blocks; setup's success message
alone is insufficient because individual writes may fail.

No permalink change is authorized now. Earlier records had plain URLs; Gateway now reports
preflight passed without providing its exact method. Do not infer a permalink change. If
pretty routes need configuration, obtain explicit authorization for `/%postname%/` and the
corresponding rewrite update before using `wp csf setup --set-permalinks` or saving Permalinks.

After setup/configuration approval, follow sections 4–6 for Kadence/contact/booking setup and
actual testing. Those settings modify the sandbox and are not authorized by preparing this plan.
Use authenticated sandbox browser smoke checks. The repository's full E2E runner refuses remote
URLs and mutates fixtures/setup; use an isolated real-Kadence copy for that suite instead of
pointing it at the live sandbox or using it to imply testing was performed.

## 4. Settings

| Screen | What to enter |
|---|---|
| **Settings → Farm Details** | Confirmed public phone, email, location, hours note, social links, legal name. Empty fields are hidden. Leave *Brand typography* on unless you set fonts in Kadence (step 5b). |
| **Settings → Booking** | Open the Acuity URL, confirm it books the right appointment type, then tick **Verified**. |
| **Farm Emergency → Settings** | Unchanged from Phase 1 — emergency contacts are separate on purpose. |
| **Settings → Reading** | Confirm *Your homepage displays → A static page → Home*. |
| **Settings → Permalinks** | *Post name*. Save once (refreshes rewrite rules). |

## 5. Kadence Customizer (Appearance → Customize)

These are theme settings stored by Kadence, so they are configured by hand rather than written
by a script into Kadence's internal options. Menu names in Kadence can vary slightly by version.

### 5a. Colors → Global Palette

| Kadence slot | Colour | Hex |
|---|---|---|
| Palette 1 (accent) | Heritage Brown | `#4A3C2F` |
| Palette 2 (accent alt) | Heritage Brown dark | `#33291F` |
| Palette 3 (strongest text) | Barn Black | `#111111` |
| Palette 4 (body text) | Barn Black | `#111111` |
| Palette 5 | Ink Gray | `#5E5C58` |
| Palette 6 | Sage Green | `#6B7A6B` |
| Palette 7 | Warm Tan | `#D6C2A6` |
| Palette 8 (site background) | Linen White | `#F5F2EE` |
| Palette 9 | White | `#FFFFFF` |

Muted Gold `#B68B43` is decorative only and is available in the block editor as a brand colour.
The brand colours (Barn Black, Linen, etc.) also appear in every block's colour picker
automatically — they come from the Corner Stone Farm Core plugin.

### 5b. Typography

Either:

- leave **Settings → Farm Details → Brand typography** on (the plugin serves Playfair Display and
  Lato from the site itself — no Google requests), **or**
- set Kadence *Base Font* = Lato and *Heading Font* = Playfair Display with *Load Google Fonts
  Locally* enabled, then switch Brand typography off.

Do not do both.

### 5c. Header (Header Builder)

| Area | Element | Setting |
|---|---|---|
| Main row, left | Logo | Site Identity → Logo = the **original Corner Stone Farm logo** once supplied. Until then the site title text "Corner Stone Farm" shows. Do not substitute another logo. |
| Main row, right | Primary Navigation | Menu = *Primary Navigation* (setup assigns it if the location was empty). |
| Mobile | Mobile Navigation + trigger | Menu = *Primary Navigation*. |
| Header background | | Palette 8 (Linen). Optional bottom border in Warm Tan. |

The last menu item, **Book a Lesson**, carries the CSS class `csf-menu-cta` and is styled as a
button by the plugin; it links to the Booking page, which uses the verified Acuity link.
(Alternatively add a Kadence *Button* element pointing to `/booking/` and remove the menu item.)

Do not make the header sticky until checked with the emergency banner: the banner prints at the
top of the page (`wp_body_open`) and should remain visible.

### 5d. Footer (Footer Builder)

1. Footer Builder → use a single full-width *Widget 1* in the middle row; remove other rows/credit.
2. **Appearance → Widgets → Footer 1** → add a **Pattern** block → choose **Site Footer**.
3. Set the footer row padding to 0 and background to Barn Black (`#111111`) so the pattern's own
   section spans edge to edge.

The footer is a synced pattern: edit it once (Patterns → Site Footer) and it updates everywhere.

### 5e. Page layout (verify)

Setup sets each page's Kadence layout meta to *Fullwidth*, *Unboxed*, *Title hidden*, *no
vertical padding* (`_kad_post_layout`, `_kad_post_content_style`, `_kad_post_title`,
`_kad_post_vertical_padding`). Open **Home** and **Contact** in the editor and check the Kadence
panel shows those values; if a page shows a second title or a boxed content area, set them there.

## 6. Verify

- [ ] Visit Home, Lessons, Contact on a phone and a desktop.
- [ ] Every booking button opens the Acuity page.
- [ ] Footer shows; footer emergency link opens `/emergency/`.
- [ ] **Farm Emergency → Farm Status**: set *Monitoring Conditions* → banner shows above the
      header on the home page in a logged-out browser → set back to *Normal* → banner gone.
- [ ] `wp csf status` (or Tools → Corner Stone Farm Setup) lists all 21 pages.
- [ ] Optional: run the automated suite against a local copy (see `docs/09-phase-2-status.md`).

## 7. Rollback — match the approved stage

Stage A: only the two new plugin directories should have been introduced, still inactive.
Within the approved recovery scope, remove/rename only those recorded new directories.
Do not restore a database or delete existing source/backup/scratch resources for file copying.

Stages B/C: activation/setup can create tables, capabilities, options, pages, menus and rewrite
state. File removal/deactivation alone does not undo them. Use the recorded current backup
and sandbox-only rollback plan after preserving later changes and rechecking exact DB/path.
Never restore a shared subscription or production. Retain hardened constants/mode 0600;
do not restore the old config-only artifact as an automatic deployment rollback.

Functional recovery on the existing scratch restoration remains untested. Do not claim a
functional rollback rehearsal was completed from structural/file checks alone.

## Local development environment

```bash
bash scripts/local-dev.sh install   # WordPress + SQLite + both plugins + site setup
bash scripts/local-dev.sh start     # http://localhost:8890  (admin / localtest123, local only)
npm install && npm run test:e2e     # automated tests
KADENCE_ZIP=/path/to/kadence.zip bash scripts/local-dev.sh reset   # test with real Kadence
```
