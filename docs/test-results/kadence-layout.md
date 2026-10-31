# Issue #1 and saved Contact integration — local candidate, October 31, 2026

**Protected Plesk sandbox acceptance: NOT RUN. No deployment or live content change.**
The owner chose local fixes/review preparation: sandbox network access and a private Basic
Authentication file are not provisioned/verified in this task. Gateway access does not transfer.
The live sandbox remains core **0.2.0**; the combined **0.3.0 candidate** is review code only.
Keep Issue #1 open until the actual site completes its authenticated review and owner acceptance.

## Shared cause and correction

The reported Meet Beth and Camps & Clinics defect reproduces on a separate local WordPress
installation with downloaded Kadence 1.5.2. Computed styles show two interactions:

1. Kadence's later `.entry-content .alignfull` rule sets left/right padding to zero, winning
   over the original section rule with equal specificity. This affects all shared sections.
2. The plugin's `margin-left: 0 !important` pins short leads to that wide parent's edge,
   while WordPress centers the 1160px heading column. At 1280px, heading left = 60, lead left = 0.

The candidate gives CSF full-width sections sufficient specificity to retain their responsive
inner gutters, and derives lead inline-start margin from the same 1160px content width.
Hero styles cover both frontend `.alignfull` and editor wrappers; the hero inner container
avoids duplicate theme padding. Full-bleed backgrounds remain at the viewport edges.
No body margin, page-specific offset, Kadence source edit, copied page, or regeneration is used.
Existing serialized blocks/copy remain intact; the CSS correction needs no setup operation.

Both confirmed URLs produced these measured header coordinates:

| Width | Heading left before | Lead left before | Heading/lead left after | Section padding after |
|---|---:|---:|---:|---:|
| 390 | 0 | 0 | 16 | 16 |
| 768 | 0 | 0 | 30.719 | 30.72 |
| 1280 | 60 | 0 | 60 | 32 |

The [126-row geometry record](kadence-layout-geometry.csv) includes before/after results for
**all 21 pages at 390/768/1280px**, not just those two examples. It records header/lead boxes,
background edges, section padding, checked text counts, gutter failures and overflow.
Full per-node geometry and full-page screenshots remain under `.wp-local/kadence-layout/`;
the runner also captures header crops for both reported URLs at all three widths.

Desktop header crops from the **local downloaded-theme fixture**, not the protected site:

| Page | Before (0778176 CSS) | Candidate |
|---|---|---|
| Meet Beth | ![Local Meet Beth before](../screenshots/kadence-local/header-before-about-meet-beth-1280.png) | ![Local Meet Beth candidate](../screenshots/kadence-local/header-after-about-meet-beth-1280.png) |
| Camps & Clinics | ![Local Camps before](../screenshots/kadence-local/header-before-camps-clinics-1280.png) | ![Local Camps candidate](../screenshots/kadence-local/header-after-camps-clinics-1280.png) |

## Saved Contact upgrade — verified without force

The integration regression evaluates the actual reviewed **0.2.0 Contact source from 52401dc**
and stores it in an isolated WordPress fixture with the normal source/content hashes.

- Dry run previews an update without changing saved content.
- Non-forced apply adds exactly one inquiry block to the same unedited Contact ID and updates
  the source/content hashes. The other twenty pages and menus/locations stay unchanged.
- A repeat run is idempotent; no duplicate block or new page is created.
- Edited Contact content and its metadata remain byte-for-byte unchanged in both dry run/apply.
  Setup now explicitly warns that the block needs manual Gutenberg placement.
- Missing ownership hash is treated as edited. An already manually placed block is preserved.
- Every upgrade path keeps inquiries disabled; no mail is sent.

The actual sandbox Contact hash/content was not read or changed. After any separately approved
file update, use the [reviewed integration procedure](../07-website-installation.md#safe-contact-page-integration--after-separately-approved-update).
The normal non-forced setup runner has existing site/term/rewrite housekeeping; it is not an
atomic one-post editor. Its scope must be included in approval; never use `--force` to bypass
Beth's edits. A strict one-post integration uses approved manual block insertion instead.

## Evidence source and fixture configuration

- WordPress **7.1.4**, PHP **8.4.24**, SQLite integration, Chromium **154.0.8037.57**.
  This is not the VPS's PHP 8.3.36 / MariaDB 10.6.23 runtime.
- Kadence **1.5.2**, 552 files / 8,422,935 bytes, fetched from the permitted public CDN mirror
  `https://cdn.jsdelivr.net/wp/themes/kadence/1.5.2/` (directory listing SVN revision 356465).
  Downloaded files are ignored, not committed or deployed. No VPS theme checksums were read.
- Locally assembled theme ZIP SHA-256:
  `b3481da7ae79c750e7917fa1df2db13a902d8a4e141ad2506975b9bc2028ec6c`.
  Per-file manifest SHA-256:
  `8563663bb78b8315c9ee700a73b749be9c7d94768d3e66530aacfb1110b2dbdb`.
  These identify the tested local artifacts, not an independently verified official ZIP.
- The regression copies the disposable local site, selects a separate SQLite DB **before
  WordPress loads**, verifies that binding, and removes only its own temporary fixture.
  Emergency is inactive; a fixture-only MU filter blocks outgoing mail.
- The untouched theme defaults hide the text-only brand at tablet width when no logo is
  installed, causing an unnamed home link in axe. The configured fixture explicitly sets
  native Kadence Logo Layout to **Logo & Title at all breakpoints**. This is local theme
  configuration, not a core workaround or a change to the sandbox. Actual header settings
  must be checked there; do not assume it has the same configuration problem.
- The mobile drawer assertion waits for Kadence's asynchronous `.active` initialization and
  its close/focus timer before testing Escape. An immediate keypress was a test timing issue.
  RichText geometry measures visible words rather than hanging `pre-wrap` spaces.

## Executed checks — local fixtures only

The final isolated regression command exited 0. These results are deliberately separate from
protected-site acceptance, mail delivery, owner content review and functional backup recovery.

| Check | Local result |
|---|---|
| Baseline versus corrected geometry | 63 corrected page/viewport cases passed; 63 baseline records retained; both reported defects reproduced |
| Page HTTP/H1/geometry/axe and mobile trigger | 63 passed on the configured local fixture; mobile Enter/Escape passed |
| Gutenberg validity, patterns and editor geometry | 82 passed, including all 21 page geometry checks, 35 patterns and invalid-block control |
| Populated listings/cards/images and structured content | 36 passed, including 18 populated layout cases across three widths |
| Saved Contact integration | 14 checks passed |
| Setup failure reporting / inquiry validation / inquiry browser | 27 / 39 / 17 passed; mail intercepted |
| PHP syntax / WPCS | 69 plugin files passed / zero errors or warnings |
| Deployment guards / Plesk wrapper / Python target checks / browser credential guards | 22 / 6 / 14 / 6 passed; mocked runtime where noted by those tests |
| Existing takeover / emergency HTTP privacy | 8 / 14 passed on disposable localhost only |

Reproduce with a trusted exact-version theme ZIP and the existing local WordPress fixture:

```bash
export KADENCE_ZIP=/private/path/to/kadence-1.5.2.zip
# Optional local browser override: export CSF_CHROMIUM_EXECUTABLE=/path/to/chromium
bash tests/integration/run-kadence-layout.sh
CSF_INQUIRY_BROWSER=1 bash tests/integration/run-setup-reporting.sh
```

The historical `phase-2.md` and its screenshots were not overwritten. `--only=editor` and
`--only=content` do not rewrite that report. Local runtime logs are outside tracked source.

## Sandbox review handoff — pending secure access and approval

Use the [exact-sandbox runner](../07-website-installation.md#real-kadence-acceptance-procedure)
with securely provisioned network access and a private credential file. It records the same
geometry checks for all 21 pages, plus screenshots/axe and mobile navigation. Do not publish
protected-site artifacts, print credentials, or treat HTTP 401 as a broken page route.

Check actual owner-edited pages, header/mobile/submenu keyboard and focus behavior, native
page metadata, Gutenberg editing/saving and footer block **27** placement. Verify the tablet
site-title/logo accessible name. Authentic logo/photos, the 23 owner notes and Acuity
verification remain open. The inquiry provider is unknown; delivery to
beth.siemon@cornerstonefarmomak.com is unverified and the form must remain disabled.

One future reviewed core-only release may contain the inquiry form, reliability fixes and
this layout candidate. It still needs a current target-bound backup/evidence gate, reviewed
source SHA, core-only deployment comparison and explicit authorization. Preserve completed
Gateway preparation/restore records; do not repeat them or claim the older backup captures
current owner edits. No production changes, excluded database changes, emergency activation,
new sandbox deployment or inbox test is authorized or performed by this task.
