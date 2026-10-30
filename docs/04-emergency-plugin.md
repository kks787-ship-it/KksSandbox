# 04 — Corner Stone Farm Emergency Management (`csf-emergency`)

## Operational status

> **NOT YET OPERATIONAL.** The code is complete for the Phase 1 MVP and tested locally, but
> the system must not be relied on — or described to clients as live — until every item in
> [Go-live checklist](#go-live-checklist) is signed off by an owner.

### What exists

- Horse Emergency ID profiles (admin: **Farm Emergency → Horse IDs**)
- Permanent public URLs `/h/{code}` and print-ready `/h/{code}/print`
- Printable QR tag per horse (QR + horse name + farm phone + short URL)
- Farm status (8 states) with a choose → review → confirm workflow
- Sitewide emergency banner and `/emergency` landing page
- Event log of farm and horse status changes
- Emergency Coordinator role and explicit capabilities

### What does **not** exist yet (and the UI says so)

- **No notifications of any kind** are sent. The review screen states this before every change.
- **No online volunteer form or dispatch dashboard.** Selecting *Evacuation Help Requested*
  shows the safety statement and asks helpers to phone the farm.
- No SMS/push/email integration; no MFA enforcement (MFA is warned about, not required).

## Setup

1. Activate **Corner Stone Farm Emergency Management**. Administrators receive both
   capabilities; assign other people the **Emergency Coordinator** role.
2. Install the WordPress.org **Two Factor** plugin and enable it for every coordinator.
3. **Farm Emergency → Settings**: enter confirmed emergency phone numbers, the text number for
   found-horse reports, and the official emergency information source.
4. **Settings → Permalinks → Save** once if `/h/…` or `/emergency` returns a theme 404.

## Adding a horse

1. **Farm Emergency → Horse IDs → Add**. Title = barn name.
2. Fill in only what a stranger needs to identify and safely handle the horse. Everything in
   the *Public emergency information* box is public once approved.
3. Set a featured image (primary ID photo) and additional photos (both sides, markings).
4. Set *Information last verified* and tick **Approved for public emergency display**.
5. Publish. The permanent tag URL appears in the sidebar. **It never changes.**
6. **Print QR tag**, print at 100%, and test-scan before attaching.

To retire a horse, untick approval or move to Draft. The tag then shows only the farm
contact page. Do not delete profiles for horses whose tags may still be in circulation.

## Changing farm status

1. **Farm Emergency → Farm Status**.
2. Pick a status, write a short public update (≤ 280 characters; no destinations, no
   private details), optionally add approved public information and the next update time.
3. **Review change** — check the banner preview and the notification line.
4. Tick the confirmation and **Confirm and publish**.

The banner, `/emergency`, and every horse page update immediately; common page-cache plugins
are purged automatically. If the site uses another cache (e.g. Plesk nginx caching or a CDN),
add a purge to the `csf_emergency_purge_caches` action or exclude these URLs from caching.

Set **Normal** to remove the banner. *All Clear* still shows a (green) banner.

## Privacy controls

- `X-Robots-Tag: noindex, nofollow, noarchive` header and robots meta on all emergency routes
- `Disallow: /h/` added to the existing `User-agent: *` group in `robots.txt`
- Post type is non-public: excluded from core sitemaps, Yoast and Rank Math sitemaps,
  site search, REST API, and archives — there is no public directory
- `Referrer-Policy: no-referrer` so tag URLs do not leak to linked sites
- Unknown/unapproved/retired codes all return an identical 404 page with the farm phone
- **Unlisted ≠ private.** Anyone holding a tag can read the page. Treat everything on it as public.

## Tag guidance

- QR uses error-correction level Q (~25% damage tolerance) with a 4-module quiet zone.
- The printed horse name, phone number, and short URL must remain legible without the QR.
- Use UV-resistant, waterproof material (e.g. engraved/laser-etched aluminium or laminated
  tag in a riveted leather holder). Avoid glossy laminate that causes glare.
- Test every printed tag on at least one iPhone and one Android phone, in daylight and
  low light, before attaching it.

## Testing performed (local, 2026-10-30)

WordPress 6.5.5, PHP 8.3, SQLite, Twenty Twenty-Four theme, Chromium at 390 × 844:

| Test | Result |
|---|---|
| Approved profile renders at `/h/{code}` with large CALL / REPORT FOUND / VIEW INFO controls | Pass |
| Unapproved, unknown, and malformed codes → identical 404 page with farm phone | Pass |
| `/h/{code}/print` and `/emergency` render | Pass |
| HTML in fields is escaped | Pass |
| Microchip masked unless explicitly shown | Pass |
| `X-Robots-Tag`, `Referrer-Policy`, no-cache headers | Pass |
| Not present in `wp-sitemap.xml` or site search | Pass |
| Token assigned on every save path (admin, import, WP-CLI) | Pass (bug found and fixed during testing) |
| Status change: review → confirm applies once | Pass |
| Replaying the same confirmation → rejected (“expired”), no change | Pass |
| Identical status + message within 5 min → rejected (“duplicate”) | Pass |
| Anonymous POST to the confirm handler → 400 | Pass |
| Editor role → 403 on all emergency screens; Emergency Coordinator → allowed | Pass |
| Banner appears on theme pages (via `wp_body_open`) and standalone pages | Pass |
| Generated QR decodes to the correct URL | Pass |
| WordPress Coding Standards (phpcs) | 0 errors, 0 warnings |

**Not yet tested:** Kadence theme on the real server, real phones, printed tag material,
real caching layers, MFA.

## Go-live checklist

- [ ] Plugin deployed to the dev site and tested against Kadence
- [ ] Two Factor active for every coordinator
- [ ] Emergency contacts entered and test-called; *Contacts last verified* set
- [ ] Official emergency source confirmed and entered
- [ ] Every horse profile reviewed and approved by an owner
- [ ] Tags printed on final material and scan-tested on iPhone + Android
- [ ] Caching verified: banner appears within 1 minute of a status change on a logged-out device
- [ ] Tabletop drill completed (Normal → Monitoring → Evacuated → Normal) and log reviewed
- [ ] Production deployment approved separately
- [ ] Owner sign-off recorded here: ______________________  Date: __________
