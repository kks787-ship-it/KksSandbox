# 09 — Phase 2 Status: Public Website

**Historical milestone record.** For the current server state, including completed October 31, 2026 Gateway infrastructure preparation and the unrun functional restored-site test, see [the reconciled sandbox report](test-results/sandbox-preflight.md). Earlier unrun/pending statements describe the earlier assessment, not current hardening status.

**Status:** development milestone — **not deployed, not production-ready.** Built and tested in a
local environment. Installing on the Plesk development site requires owner approval
(`docs/07-website-installation.md`). Production deployment is a separate, later approval.

## Deliverables

| # | Deliverable | Where |
|---|---|---|
| 1 | WordPress implementation installable on the dev site | `wp-content/plugins/csf-core` (v0.2.0) + `csf-emergency` (unchanged v0.1.0); installation in `docs/07-website-installation.md` |
| 2 | Desktop / tablet / mobile screenshots | `docs/screenshots/` (index below) |
| 3 | Content & asset inventory | `docs/08-content-and-asset-inventory.md` |
| 4 | Installation instructions | `docs/07-website-installation.md` |
| 5 | Automated test results | `docs/test-results/phase-2.md` (regenerate: `npm run test:e2e`) |
| 6 | Remaining owner decisions | Below, and `docs/03-owner-confirmation-register.md` |
| 7 | Incomplete features | Below |
| 8 | Updated roadmap and commit reference | `docs/02-roadmap.md`; commit: see `git log` on branch `claude/csf-digital-ranch-spec-cch2af` (reported with the delivery) |

## How it is built

- **Kadence + Gutenberg, no page builder.** All pages are ordinary WordPress pages made of core
  blocks. Kadence provides the header, footer area, and global settings.
- **Design system in `csf-core`, not in a child theme.** The brand palette and self-hosted fonts are
  registered as WordPress presets (so they appear in every block's colour and typography controls),
  plus block styles (Section: Linen/Tan/Barn Black…, Card, Eyebrow, Checklist, CSF buttons) and one
  stylesheet loaded on the site and inside the editor. Switching or updating Kadence does not lose
  the design. No child theme was necessary.
- **Reusable patterns.** 14 section patterns and 21 full-page layouts are in the inserter under
  *Corner Stone Farm*. Pages are generated from these by a repeatable, idempotent setup routine
  (`wp csf setup` or **Tools → Corner Stone Farm Setup**) that never overwrites edited pages.
- **Structured content only where it is structured:** Library Articles, Horses, Camps & Clinics.
  Programs are plain pages to avoid duplicate records.
- **One fixed scheduling contact route.** Every scheduling button uses **Contact Beth to Schedule → /contact/**; legacy provider settings are ignored.
- **Emergency system untouched and independent.** `csf-emergency` was not modified; its banner is
  tested on the new pages (above the header, mobile and desktop, accessible).

### Trade-offs (explained before introducing anything else)

| Decision | Why | Trade-off |
|---|---|---|
| Core blocks instead of Kadence Blocks | Portable across themes, no extra plugin, testable here, validated against WordPress's own block output | Fewer layout widgets (no tabs/sliders — which the brief avoids anyway). Kadence Blocks can be added later without rework. |
| Design tokens/presets in a plugin instead of a child theme | Survives theme changes; rule 10 says use a child theme only when necessary | Kadence's Global Palette is set by hand once (documented) so header/footer match. |
| Header and footer configured in the Kadence Customizer | That is how Kadence stores them; writing its internal options from a script was judged too fragile | ~30 minutes of manual configuration on the dev site (step-by-step in doc 07). |
| Footer as a synced pattern placed in a Kadence footer widget | Footer edited in one place in the block editor | One manual placement step. |
| No contact form yet | Brief: no public forms until spam protection, secure delivery, and notifications are chosen and tested | Visitors use phone/email/booking. Form plugin choice is an owner decision. |
| Direct scheduling contact | Beth or Kris confirms appointments personally | No public availability or automatic reservations. |

## Testing

`node tests/e2e/run.js` (Chromium + axe-core) runs against the local site. Results:
`docs/test-results/phase-2.md`. Checks include:

- every page: HTTP 200, exactly one H1, no PHP errors, no console errors, no public forms, no
  admin-only hints visible to visitors, alt text on every image;
- **accessibility:** axe-core WCAG 2.0/2.1/2.2 A+AA on every page at mobile and desktop, with and
  without the emergency banner, plus sample article/horse/event pages;
- **responsive:** no horizontal scrolling at 390 px, 768 px, and 1280 px on every page;
- **navigation:** menu order, highlighted Book a Lesson item, 12 submenu pages, keyboard access to
  submenus, mobile menu toggle state;
- **links:** every internal link on every page resolves (external links listed, not fetched);
- **booking:** every scheduling button uses the fixed internal Contact destination;
- **block editor:** every page and the footer open in the WordPress 7.1 block editor and every block
  matches the current block markup exactly (strict `validateBlock`, with a deliberately broken
  control block proving the check works); all 35 patterns valid; 8 dynamic blocks registered;
- **emergency:** banner visible and above the header on several pages and both viewports, no
  overflow, accessible; *Evacuation Help Requested* shows "online volunteer sign-up is not
  available yet" and the do-not-self-deploy statement; no notification claims; `/emergency`
  still noindex; banner removed on return to Normal;
- **structured content** (temporary sample data, removed afterwards): reading time, review date,
  related articles, topic list, drafts hidden, `/library/` archive; sale horse listed, sold horse
  hidden, unapproved price hidden, inquiry button; past events hidden, Register button only when
  open; home "Featured horses" appears/disappears;
- **setup:** re-running changes nothing; edited pages are kept; `--force` restores.
- PHP: `php -l` and WordPress Coding Standards (`vendor/bin/phpcs`) — 0 errors, 0 warnings.

## Known limitations

1. **Not tested on Kadence.** The Kadence theme could not be obtained in the build environment
   (wordpress.org blocked; it is not on public GitHub). Tests ran on a clearly labelled *test-only
   stand-in theme* (`tests/fixtures/themes/csf-kadence-standin`) that mimics Kadence's structure.
   The header, footer, menus, and page-layout settings **must be verified on the dev site with
   Kadence** (doc 07 §6), or locally with `KADENCE_ZIP=… bash scripts/local-dev.sh reset`.
   The Kadence page-layout meta keys set by setup are believed correct but unverified.
2. **Saved scheduling copy migration pending** — preview non-forced setup; preserve owner edits.
3. **No real photographs or logo.** 22 labelled placeholders; the header shows the farm name as text.
4. **23 "Owner to confirm" notes** remain visible on pages (list in the test report).
5. Screenshots show the stand-in header, not Kadence's.
6. Real phones were not tested (Chromium emulation only); VoiceOver/TalkBack not tested.
7. The site is in English only; no SEO plugin is configured yet (titles come from WordPress).
8. WordPress's default Privacy Policy page is still a draft.

## Incomplete / deliberately not built in Phase 2

| Feature | Status |
|---|---|
| Contact / inquiry form | Not enabled. Needs: form plugin choice, spam protection (e.g. Turnstile/hCaptcha + honeypot), SMTP delivery, notification routing, testing. |
| Camp registration / payments | Events link to an external registration URL; no on-site registration. |
| Onboarding, agreements, e-signatures | Phase 4. Pages state agreements are completed before the first ride; no system enforces it yet. |
| Notifications, evacuation assistance, volunteer dispatch | Phase 3. Still not operational and not implied anywhere. |
| Client, horse-owner, staff portals; AI assistant; store | Later phases. |
| Starter Knowledge Library articles | None written: educational content should come from or be reviewed by Beth. |
| Redirects from the old site's URLs (incl. `/index.php/…`) | Needs the old site's URL list (launch phase). |
| SEO metadata, social share image, analytics | Launch phase; owner decisions. |

## Owner decisions needed

**To finish the website**

1. Supply the original logo artwork (vector if possible).
2. Supply authentic photographs (shot list in doc 08) and photo permissions for minors.
3. Enter and confirm public phone, email, hours, social links, legal business name.
4. Review Contact Beth to Schedule links and separately approve saved-page/menu edits.
5. Resolve each "Owner to confirm" note (credential wording; Beth's and Kris's bios; farm story;
   publishing the five-horse limit and Sunday closure; lesson pricing/lengths/cancellation policy;
   minimum age, attire and helmet policy; onboarding steps; off-site service area and travel fee;
   training intake requirements and pricing; young-horse program details; camp registration method;
   lease/mentorship/working-student terms; sale policies and price publishing; facility descriptions;
   FAQ answers).
6. **After-School Program:** supply schedule, ages, pricing, enrollment — or unpublish the page.
7. Choose a contact-form approach (plugin and spam protection) for Phase 2b.
8. Approve the colour palette against the original logo.
9. Privacy policy content (legal review recommended).
10. Approve installation on the development site.

**Before production launch (later)**

11. Approve the production deployment plan and redirect map.
12. Real testimonials (with permission), if wanted.

## Screenshots (`docs/screenshots/`)

Generated by `npm run test:e2e:screenshots` on the local site with the test-only stand-in theme.
Full-page JPEGs.

| Page | Files |
|---|---|
| Home | `home-desktop.jpg`, `home-tablet.jpg`, `home-mobile.jpg` |
| Lessons | `lessons-desktop.jpg`, `lessons-tablet.jpg`, `lessons-mobile.jpg` |
| Horse Training, Camps & Clinics, Horse Sales, About, Meet Beth, Resources, FAQ, Contact, Booking | `<path>-desktop.jpg`, `<path>-mobile.jpg` (child pages use `--`, e.g. `about--meet-beth-mobile.jpg`) |
| Emergency banner active | `emergency-banner-desktop.jpg`, `emergency-banner-mobile.jpg` |
| Block editor (Home) | `editor-home.jpg` |
| Sample structured content (test data) | `sample-article-desktop.jpg`, `sample-horse-desktop.jpg`, `sample-event-desktop.jpg`, `home-with-sample-content-mobile.jpg` |
