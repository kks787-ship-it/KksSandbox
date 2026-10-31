# 07 — Website Installation (Development Site)

**Scope:** the separate *development* WordPress install on the Plesk VPS. **Never the production
site.** Nothing here has been deployed; deployment to the dev site also waits for owner approval.

Estimated time: 45–60 minutes, most of it in the Kadence Customizer.

## 0. Before you start

- [ ] Environment inspection report exists (`docs/00-environment-inspection.md`) and confirms
      WordPress ≥ 6.6 for this prepared release (current supported release preferred) and PHP ≥ 8.1.
- [ ] Dev `wp-config.php` contains `define( 'WP_ENVIRONMENT_TYPE', 'development' );`
- [ ] Dev site is not indexed (Settings → Reading → *Discourage search engines*) and ideally behind
      Plesk password protection.
- [ ] Kadence theme is active (it already is, per the spec).
- [ ] Back up the dev site: `bash scripts/backup-dev.sh /var/www/vhosts/<dev>/httpdocs ~/csf-backups`

## 1. Copy the plugins

Use the reviewed release on `codex/takeover-audit`, not a moving Claude branch tip.
Follow the [Milestone B procedure](05-backup-rollback-deploy.md#milestone-b--development-deployment-preparation)
for exact URL/path identity, read-only preflight, verified backup and restore evidence.
Preparation runs only the dry run; copying, activation and setup wait for explicit authorization.

```bash
bash scripts/deploy-plugins.sh "$CSF_DEV_ROOT" --expected-url "$CSF_DEV_URL"
```

After explicit approval, the same script requires `--backup`, `--restore-evidence`,
`--expected-url` and `--apply`. WordPress ZIP upload requires the same verified backup,
identity checks and approval; it is not a shortcut around those safeguards.

## 2. Activate

**Plugins** → activate **Corner Stone Farm Core** and **Corner Stone Farm Emergency Management**.

Activation only registers content types. It does not create pages.

## 3. Run the site setup

**Tools → Corner Stone Farm Setup** → tick *Dry run* → **Run site setup** → review → run again
without *Dry run*. Or with WP-CLI:

```bash
wp csf setup --dry-run
wp csf setup --set-permalinks
wp csf status
```

This creates the 21 pages (hierarchy below), sets **Home** as the front page, creates the
*Primary Navigation* and *Footer Navigation* menus, and creates a synced pattern called
**Site Footer**. It is safe to run again: pages edited in WordPress are never overwritten.

If pages with the same paths already exist on the dev site (e.g. an old "Contact" page), setup
keeps them untouched and reports `kept`. Delete or rename them first if you want the new layout,
or insert the layout from **Patterns → Corner Stone Farm: Page layouts**.

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

## 7. Rollback

Deactivate **Corner Stone Farm Core**. Pages remain (as content) but dynamic listings and brand
styling disappear. To fully revert, restore the backup from step 0. Emergency data is unaffected.

## Local development environment

```bash
bash scripts/local-dev.sh install   # WordPress + SQLite + both plugins + site setup
bash scripts/local-dev.sh start     # http://localhost:8890  (admin / localtest123, local only)
npm install && npm run test:e2e     # automated tests
KADENCE_ZIP=/path/to/kadence.zip bash scripts/local-dev.sh reset   # test with real Kadence
```
