# 07 — Website Installation and Acceptance (Sandbox)

**Current state — October 31, 2026:** Gateway verified and deployed **csf-core 0.3.0**, commit `0f8530e`, to the password-protected sandbox only. All 21 generated site pages were preserved; no setup or Contact content update was run. Kadence 1.5.2, WordPress 7.1.4 and PHP 8.3.36 remain. **Do not reinstall the foundation or recreate the site.** `csf-emergency` 0.1.0 remains **inactive**; production is excluded.

The newer remote roadmap reports **[Issue #1](https://github.com/kks787-ship-it/KksSandbox/issues/1)**: shared intro paragraphs reach the viewport edge while headings are inset, observed on Meet Beth and Camps & Clinics. This supersedes the earlier no-confirmed-defects report. A shared CSS correction has been **deployed to the protected sandbox**, and the owner visually accepted the mobile layout on the pages viewed. **Authenticated all-21-page Plesk acceptance remains NOT RUN**; tablet/desktop and comprehensive audit are pending, so Issue #1 remains open. The historic substitute-theme tests and WordPress do_blocks() results
cannot establish real-theme compatibility; HTTP 401 is expected without Basic Authentication.

## Canceled Acuity — new scheduling workflow (owner confirmed October 31, 2026)

The owner canceled Acuity. The public call to action must be **Contact Beth to Schedule** linked to `/contact/`. Show only approved, verified call/message channels. The inquiry form remains **disabled**; it is not a public scheduling fallback. Do not advertise available times, online booking, automated confirmations, or Acuity imports. Beth or Kris confirms appointments directly, and the future staff calendar uses only their confirmed entries. The local code/default changes are prepared; previously saved sandbox page text has **not yet been migrated**; see [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3). The website update and saved-page/menu update require separate explicit approvals.

## Staged installation approval boundaries

| Stage | Current state / next action |
|---|---|
| Preparation | Full backup, structural/file restoration, private staging and exact-target preflight/dry run completed through Gateway; preserve those results |
| Core installation/setup | Completed through Gateway: 0.3.0 active in protected sandbox, 21 generated pages/menus and static Home preserved; no latest-update site setup run |
| Routes | /%postname%/, 162 rewrite rules, standard sandbox .htaccess and passed Apache syntax reported; do not change them again |
| Protected sandbox acceptance | NOT RUN: no verified network access or private Basic Auth file in this task; local downloaded-theme results are separate |
| Core release | 0.3.0 deployed after fresh target-bound backup and exact-source core-only dry run. Shared gutter fix installed; inquiry form disabled/unconfigured and Contact saved page unchanged. |
| Emergency | Inactive; no activation or operational testing authorized |

The original d360dbf staging/dry run describes the old release. Do not silently advance that
pin or claim it validates new code. A reviewed core update needs its own exact source SHA,
current target-bound backup gate, and **core-only** comparison. Do not repeat the completed
infrastructure preparation or structural restore.

## Procedure for any future sandbox update — separate approval required

The reviewed 0.3.0 core-only update has **already been deployed** through Gateway. The following procedure is retained for a future revision only; it is **not** authorization to rerun 0.3.0 or site setup.

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
5. Core is already active: do not activate emergency, use --force, change permalinks, or
   recreate pages. The narrowly reviewed non-forced Contact integration below is a separate
   content operation after approval, not automatic plugin-install behavior. Verify core 0.3.0, PHP errors, unchanged routing,
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
logo/photos are missing; 23 owner-confirmation notes and verified public call/message channels remain open. The former Acuity booking provider is canceled; [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3) covers its removal from code and saved public copy.
These configuration tasks are separate from the shared gutter candidate. Verify that candidate
on the actual sandbox; do not treat a local theme run as site acceptance. With emergency inactive, its landing/banner links are a deferred acceptance
item, not authorization to activate it for a smoke test.

## Safe Contact-page integration — after separately approved update

The Contact page is saved WordPress content. Copying 0.3.0 plugin files changes its template
source, not the stored page. The CSS correction itself requires **no page regeneration**.
The isolated regression uses the reviewed 0.2.0 Contact template from 52401dc and verifies:

- Existing unedited Contact: a non-forced dry run previews an update; a non-forced apply
  inserts exactly one inquiry block into the same page ID and updates source/content hashes.
- Other twenty pages and menus/locations remain unchanged; repeating setup adds no duplicates.
- Owner-edited Contact or missing ownership hash: dry run/apply preserve content and metadata
  exactly. Setup warns that the Farm Inquiry Form block requires manual placement in Gutenberg.
- An already manually placed block is preserved. Every path keeps inquiries disabled.

After approved file deployment and confirmation that the **installed** code is the reviewed
0.3.0, verify the exact sandbox/current backup and run the following preview within its
approved inspection scope. Do not bootstrap staged PHP over the old live plugin to bypass
the file-update boundary:

```bash
# Future reference only; not authorization to run on the VPS now.
wp --path=/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com csf setup --dry-run
```

For an unedited Contact page, expect `update /contact/`; existing pages/footer should remain
`ok` and there should be no unexpected creations or other content updates. If the delta is
larger or any error appears, stop for review. Only under explicit **Contact content-update
approval**, run that same command without --dry-run (never --force or --set-permalinks), then
check the unchanged Contact ID, one inquiry block, other page/menu content and disabled form.

If Contact is `kept (edited in WordPress)`, do not force it. Under the content-edit approval,
open the existing page in Gutenberg, place a single Farm Inquiry Form block at the agreed
location and preserve Beth's wording/design. Review/save only that change and verify no
public form appears. Copying files or a successful setup message alone does not prove the
saved page received the block. Re-run the dry preview afterwards; edited content remains kept.
The standard non-forced runner also seeds missing default terms, fills missing site/menu
configuration, soft-flushes rewrites and refreshes its completion marker. Its preview is
not an atomic Contact-only transaction. Confirm the existing configuration and include that
housekeeping in any future setup approval; use manual block placement if strictly one-post
editing is required. No live Contact hash or content was inspected/changed by this task.

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
Enable. Changing the saved recipient clears both. Use the safe Contact integration above
for block placement; adding the block never grants permission to enable or send mail.

Confirmation integrity (Issue #2) is included in this consolidated candidate. A successful POST
now redirects with a signed, site-bound receipt, valid for five minutes and one acknowledgment.
The old public submitted flag cannot acknowledge anything. Receipt URLs contain no sender or
message data; only a digest/expiry marker is stored. Receipt responses use no-store/private
and no-referrer headers. Check valid submission, refresh/replay, fabricated URL and mail failure
in the separately approved enabled-form test; no email-delivery claim follows from the receipt.
See [current regression evidence](test-results/website-finishing.md#issue-2--confirmation-integrity-follow-up).

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
local-theme browser checks do not clear those acceptance gates.

## Local layout reproduction and sandbox review handoff

Local tests use the public Kadence 1.5.2 release on a separate SQLite fixture, with emergency
inactive and mail blocked. Source/provenance and results are in
[test-results/kadence-layout.md](test-results/kadence-layout.md). They do not read the real
sandbox database, customized theme settings, owner-edited content or HTTP authentication.
The owner explicitly chose **finish local fixes; prepare sandbox review**. Network access
and a private Basic Authentication file are not provisioned/verified here; Gateway access
does not transfer into this cloud task.

When separately arranged, use the existing exact-sandbox runner above for all 21 pages at
390/768/1280. It now records heading/lead boxes, responsive gutter lengths, section bounds,
text/card/image/button bounds and overflow, alongside axe and screenshots. It waits for
Kadence's drawer initialization and closure, rather than treating animation timing as a
navigation defect. Inspect mobile/desktop keyboard behavior manually too.

The untouched local theme defaults hid the text-only brand on tablet, producing an unnamed
home link before a logo exists. The local configured-fixture run explicitly uses Kadence's
native Logo Layout → Logo & Title at all breakpoints. **This setting was not applied to the
sandbox**, and the finding is not proof its current header has the same setting. During its
approved Customizer review, keep the site title visible at tablet/mobile until the authentic
logo/accessibility name is verified. This is configuration work, not another plugin.

Keep Issue #1 open until the actual protected site's complete review/owner acceptance and
approved update are recorded. Do not claim a local pass verifies real Plesk PHP/MariaDB,
mail delivery, header/footer configuration or the isolated backup's functional restoration.

## 4. Settings

| Screen | What to enter |
|---|---|
| **Settings → Farm Details** | Confirmed public phone, email, location, hours note, social links, legal name. Empty fields are hidden. Leave *Brand typography* on unless you set fonts in Kadence (step 5b). |
| **Settings → Booking** | **Deprecated.** Do not configure or verify the canceled Acuity provider. [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3) now prepares the fixed local contact route in place of the old booking link/default with **Contact Beth to Schedule → /contact/** and remove online-booking claims. |
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

The existing **Book a Lesson** nav CTA is styled via `csf-menu-cta` and currently links to the saved Booking page. [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3) changes the visitor-facing CTA to **Contact Beth to Schedule** and routes scheduling inquiries to `/contact/`, with verified phone/message details only. Update the saved menu and Booking page through a reviewed, owner-safe content migration; do not use canceled external URLs or invent a live booking calendar.

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


## Issue #3 saved-content migration proposal (not executed on sandbox)

Code files do not rewrite saved Gutenberg content. The fixed dynamic button ignores legacy
provider options and legacy custom labels without deleting saved attributes. Existing classic
booking page menu items and `csf-menu-cta` items render Contact Beth to Schedule → /contact/
without changing the stored menu; `/booking/` remains a useful explanation route. Old event
registration URLs are retained as unused metadata and cannot render a scheduling link.
Settings → Scheduling is informational; no provider URL or verification control remains.

After code review and separate approval for the exact protected target, prepare these
read-only reports against its current backup-bound state:

```bash
wp csf status --format=json
wp csf setup --dry-run
```

Review every one of the 21 page paths as `ok`, `update`, or `kept` (edited or untracked).
Also review primary/mobile/footer saved menus, custom Kadence header buttons, synced Site
Footer block, reusable blocks and owner-authored event content for legacy external links and
online-booking claims. Dynamic rendering does not sanitize arbitrary saved HTML or text.
Do not assume a plugin-only update removes all old claims from those saved records.

Submit the exact dry-run page list and proposed Gutenberg/menu changes for **separate content
approval**. Never use `--force`. If approved, non-forced setup updates only pages whose saved
content still matches `_csf_setup_hash`; owner-edited/no-hash pages stay unchanged and require
an exact manual edit proposal. Existing synced footer is kept by setup and must be reviewed
individually. Preserve IDs, hierarchy, unrelated owner text, menu assignments and content
hashes. Setup can partially fail: inspect errors; never force-retry a partial migration.

After an approved content operation, rerun the dry run for idempotency and review all 21
saved rendered routes at 390/768/1280px, accessible names/targets, absence of public forms and
external scheduling links, plus admin/editor preview. Reverting files does not restore saved
content; use reviewed per-record revisions/backup reconciliation for rollback. This proposal
is not authorization to run any of those commands on the sandbox. Local results and blocked
checks: [scheduling report](test-results/scheduling-contact.md).
