# 07 — Website Installation and Acceptance (Sandbox)

**Current state — October 31, 2026:** the owner reports csf-core **0.2.0 active**, all
21 pages published, navigation assigned and Home selected on real **Kadence 1.5.2**,
WordPress 7.1.4 and PHP 8.3.36. **Do not reinstall the foundation or recreate the site.**
csf-emergency 0.1.0 is installed but remains **inactive** and non-operational. Production is excluded.

The newer remote roadmap reports **[Issue #1](https://github.com/kks787-ship-it/KksSandbox/issues/1)**: shared intro paragraphs reach the viewport edge while headings are inset, observed on Meet Beth and Camps & Clinics. This supersedes the earlier no-confirmed-defects report. It has not been independently reproduced or fixed here; comprehensive authenticated browser testing is pending. The historic substitute-theme tests and WordPress do_blocks() results
cannot establish real-theme compatibility; HTTP 401 is expected without Basic Authentication.

## Staged installation approval boundaries

| Stage | Current state / next action |
|---|---|
| Preparation | Full backup, structural/file restoration, private staging and exact-target preflight/dry run completed through Gateway; preserve those results |
| Core installation/setup | Completed through Gateway: active 0.2.0, 21 published pages, primary/mobile/footer menus, static Home |
| Routes | /%postname%/, 162 rewrite rules, standard sandbox .htaccess and passed Apache syntax reported; do not change them again |
| Real Kadence acceptance | Reported gutter Issue #1; authenticated sitewide reproduction, fix and acceptance pending |
| Core update candidate | 0.3.0 in Git, not deployed: deployment permissions, truthful setup errors, optional inquiry form disabled by default |
| Emergency | Inactive; no activation or operational testing authorized |

The original d360dbf staging/dry run describes the old release. Do not silently advance that
pin or claim it validates new code. A reviewed core update needs its own exact source SHA,
current target-bound backup gate, and **core-only** comparison. Do not repeat the completed
infrastructure preparation or structural restore.

## Next sandbox update plan — separate approval required

1. Review the candidate Git diff and [local validation](test-results/website-finishing.md).
   Retrieve the actual private source path from Gateway records. Review/stage an exact
   candidate SHA only under the approved future update scope; this task does not update it.
2. Inspect current core file checksums/ownership, active-plugin state and owner edits. The
   October 31 backup predates the now-completed installation/setup. Preserve it and its
   structural restore evidence, but require a **current** sandbox application backup before
   updating the active site. Verify target path/URL, six-entry manifest, actual age/provenance
   and evidence with [doc 05](05-backup-rollback-deploy.md#backups-and-verified-backup-gate-before-any-apply).
   Do not pretend the older restore proves functional recovery of the current build.
3. Prepare a core-only dry run using the reviewed new script. The command below is a
   **future reference**, not authorization to stage files or execute on the VPS:

```bash
# CSF_REVIEWED_SOURCE must be the privately staged, exact reviewed candidate checkout.
cd "$CSF_REVIEWED_SOURCE"
bash scripts/deploy-plugins.sh \
  /var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com \
  --only-core --expected-url https://sandbox.cornerstonefarmomak.com
```

4. Review the proposed file/mode changes, source SHA, exit status and rollback point. Only
   after explicit update approval, use that same command with --apply, --backup and
   --restore-evidence pointing to the verified current bundle/evidence. Run as the correct
   Plesk subscription account, not an arbitrary root account: existing ownership is retained
   and new files belong to the executing account. Selected plugin directories become 0755
   and files 0644; wp-config.php remains unchanged at 0600. --only-core excludes emergency.
5. Core is already active: do not activate emergency, run setup/--force, change permalinks,
   or recreate pages as an update shortcut. Verify core 0.3.0, PHP errors, unchanged routing,
   menus/content, access protection, disabled inquiry settings and inactive emergency.
6. Separately review content/theme configuration and inquiry enablement below. Installing
   the candidate never enables inquiries or inserts the form into an owner-edited page.

The exact target remains sandbox DB wp_mdf4x (options TXGEh_options), root
/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com. Production httpdocs,
DB wp_iacb7 and unassigned DB wp_mdoaq are excluded. No server operation is authorized by
this plan, and this Codex task has no callable Gateway connection.

## Real Kadence acceptance procedure

Start with Home, /lessons/, /about/meet-beth/, /horse-training/, /camps-clinics/ and /contact/.
Issue #1 requires a **sitewide audit of all 21 pages** at **390, 768 and 1280px**, including
other text sections and Gutenberg. The runner covers all 21; preserve full-bleed backgrounds
and do not force-regenerate pages. Use Basic Authentication supplied
through secure access; never paste its password into chat, source or commands.

The prepared runner performs GET/HEAD page review only, records screenshots, one-H1,
overflow and axe checks, and exercises the mobile trigger if its Kadence selector exists:

```bash
# Private JSON credential file: username/password fields, mode 0600, outside Git/web roots.
# Set its PATH through secure workspace access; never put credential values on the command line.
CSF_HTTP_AUTH_FILE="$CSF_PRIVATE_AUTH_PATH" \
  node tests/e2e/kadence-review.js
# Optional installed Chromium override: CSF_CHROMIUM_EXECUTABLE=/path/to/chromium
```

The runner allows only the exact sandbox origin or localhost, rejects substitute themes,
and reports 401 as blocked authentication. It blocks browser non-GET/HEAD requests; normal
WordPress GETs can still trigger cache/cron activity. Run only within the approved sandbox
inspection scope. Private screenshots/results remain ignored under .wp-local/kadence-review/;
do not publish protected-site artifacts automatically. It does not log into WordPress or
save anything. A script pass is not visual or editor acceptance.

Manually verify header/mobile menus, keyboard submenu access, Escape/focus return, full-width
and unboxed layout, global palette/typography and footer. In an approved authenticated editor
session, inspect the four _kad_* fields listed below and block validity, then test editing/
saving only with separately authorized disposable content. Editor entry can acquire post
locks; do not label it a strict database-read-only operation.

Known configuration/content work: synced **Site Footer block 27 exists but is not placed**
in Kadence Footer Builder; header/palette/typography/navigation await visual review; original
logo/photos are missing; 23 owner-confirmation notes and the Acuity destination remain open.
These configuration tasks are separate from the reported shared gutter defect. Reproduce
Issue #1 and correct the shared rules; do not apply an isolated two-page patch or redesign. With emergency inactive, its landing/banner links are a deferred acceptance
item, not authorization to activate it for a smoke test.

## Optional inquiry form — keep disabled

Owner-confirmed destination: **beth.siemon@cornerstonefarmomak.com**. The delivery service is
unknown; the owner explicitly requested the form remain disabled until delivery is verified.
The code has no hard-coded recipient, provider credentials, attachments or automatic replies.

After a separately approved update, an approved administrator can save this recipient in
Settings → Farm Inquiries, leaving both checkboxes off. Configure one existing WordPress
SMTP/API transport with an authenticated farm sender (not the visitor address), verify
SPF/DKIM and actual inbox/spam receipt with an approved test, and test Reply-To. A wp_mail()
true result proves acceptance by the transport, **not delivery**. No real email was sent here.
Only after inbox verification and explicit enablement approval check Delivery verified and
Enable. Changing the saved recipient clears both. Add the Farm Inquiry Form block to the
existing Contact page in Gutenberg under a content-edit approval; no forced site setup.

Exclude the form page and admin-post submission route from full-page/CDN caching; signed
challenges last two hours and require three seconds before submission. Built-in spam controls:
honeypot, nonce, signed timing challenge, atomic single-use claim, three attempts per client
per clock-hour and twenty site-wide, with hour-boundary bursts possible. REMOTE_ADDR is used,
never forwarded headers; verify Plesk's trusted-proxy handling before enabling. Failed mail
attempts consume allowance and the token; automatic retries are not attempted. These controls
are not a WAF or a guarantee against spam. Provider-specific spam integration is not configured.

The plugin stores no inquiry messages or raw IPs. Salted rate/token identifiers and expiry
values are no-autoload options, cleaned in bounded WP-Cron batches; verify cron execution.
Any SMTP/logging plugin may retain messages independently: review its privacy/retention
settings and the farm privacy notice before launch. Confirm database claim behavior on the
actual MariaDB setup and authenticated form flows before enablement. Local mocked-mail and
substitute-theme browser checks do not clear those acceptance gates.

## 4. Settings

| Screen | What to enter |
|---|---|
| **Settings → Farm Details** | Confirmed public phone, email, location, hours note, social links, legal name. Empty fields are hidden. Leave *Brand typography* on unless you set fonts in Kadence (step 5b). |
| **Settings → Booking** | Open the Acuity URL, confirm it books the right appointment type, then tick **Verified**. |
| **Farm Emergency → Settings** | Unchanged from Phase 1 — emergency contacts are separate on purpose. |
| **Settings → Reading** | Confirm *Your homepage displays → A static page → Home*. |
| **Settings → Permalinks** | Already /%postname%/; inspect only. No save/rewrite change is authorized. |

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

Setup writes each page's Kadence layout meta to *Fullwidth*, *Unboxed*, *Title hidden*, *no
vertical padding* (`_kad_post_layout`, `_kad_post_content_style`, `_kad_post_title`,
`_kad_post_vertical_padding`). Open **Home** and **Contact** in the editor and check the Kadence
panel and actual Kadence 1.5.2 rendering agree with those values. No mismatch is confirmed. If a demonstrated problem requires a change, record it and obtain the relevant sandbox content/configuration approval first.

## 6. Acceptance and rollback

Use the page/device matrix above; record confirmed failures separately from untested items.
Do not activate emergency or alter farm status to test the public website. Footer block 27
placement is pending; if Classic Widgets prevents the block UI, review that configuration
rather than installing another footer plugin or replacing the theme.

For a core-files-only update, preserve the pre-update core tree and exact source/version,
restore **only that sandbox core tree** on an authorized rollback, and verify the already
active 0.2.0 build/pages still render. Do not delete the existing plugin or import a database
merely to undo file copying. If approved follow-on settings/content edits also occurred,
preserve subsequent edits and use a state-aware sandbox-only recovery plan before restoring
DB wp_mdf4x. Never restore the shared subscription or production DB. Keep hardened config
and 0600 permissions. The old pre-hardening config artifact is not an automatic rollback.

The historical isolated restore passed structural/file integrity, not functional login/
page-rendering. Current page/setup state differs from that capture; do not relabel it as a
functional rehearsal of this release. Emergency concurrency and real-world acceptance remain
separate gates in SECURITY.md and DECISIONS.md.

## Local development environment

```bash
bash scripts/local-dev.sh install   # WordPress + SQLite + both plugins + site setup
bash scripts/local-dev.sh start     # http://localhost:8890  (admin / localtest123, local only)
npm ci && npm run test:e2e     # automated tests
KADENCE_ZIP=/path/to/kadence.zip bash scripts/local-dev.sh reset   # test with real Kadence
```
