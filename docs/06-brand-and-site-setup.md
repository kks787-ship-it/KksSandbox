# 06 — Brand System & Site Setup (Kadence + Gutenberg)

## Logo

Use only the authentic Corner Stone Farm logo supplied by the owners (stylized horse,
“Corner Stone” script, spaced “FARM”, tagline **PERFECTION FOUND THROUGH PLAY AND PRACTICE**).
Do not redraw, approximate, or regenerate it.

From the approved source file, produce:

| Asset | Format | Use |
|---|---|---|
| Primary logo | SVG (if vector source exists) + PNG @2x | Header |
| Logo on dark | SVG/PNG, light ink | Footer, dark sections |
| Mark only (if owners approve cropping) | PNG/SVG | Favicon / site icon (512 × 512) |
| Social share image | 1200 × 630 JPG | Open Graph default |

**Status:** awaiting source files (register item 19).

## Kadence global palette

Preliminary palette from the specification, pending approval against the original logo.
Configure in **Appearance → Customize → Colors → Global Palette**:

| Kadence slot | Name | Hex | Notes |
|---|---|---|---|
| Palette 1 (accent) | Heritage Brown | `#4A3C2F` | Buttons, links (contrast on Linen 9.5:1) |
| Palette 2 (accent alt) | Muted Gold | `#B68B43` | Decorative only — **not** for body text on light backgrounds (2.8:1) |
| Palette 3 (strongest text) | Barn Black | `#111111` | Headings |
| Palette 4 | Barn Black | `#111111` | Body text |
| Palette 5 | Stone Gray | `#8A8883` | Large/secondary text only (3.2:1 on Linen — fails for small text) |
| Palette 6 | Sage Green | `#6B7A6B` | Accents, borders |
| Palette 7 | Warm Tan | `#D6C2A6` | Section backgrounds |
| Palette 8 | Linen White | `#F5F2EE` | Page background |
| Palette 9 | White | `#FFFFFF` | Cards |

For small supporting text use a darker gray such as `#5E5C58` (≈6:1 on Linen) instead of
Stone Gray. Emergency colours are defined separately in `csf-emergency` and intentionally
ignore the brand palette.

## Typography

- Headings: **Playfair Display** (600/700). Body: **Lato** (400/700).
- Host fonts locally (Kadence → Customize → Performance → *Load Google Fonts Locally*) to avoid
  third-party requests and privacy issues.
- Body size 18px, line-height 1.6. The logo typography is never re-set in a web font.

## Page structure

```
Home
Lessons                       (Riding Lessons)
  ├─ Youth Riders
  ├─ Adult Amateurs
  ├─ Dressage Foundations
  ├─ Off-Site Lessons
  └─ After-School Program
Horse Training
  └─ FEI Young Horse Development
Camps & Clinics
Horse Sales
  └─ Leasing & Mentorship
About                         (Our Philosophy)
  ├─ Meet Beth
  ├─ Meet the Team
  ├─ Meet the Horses
  └─ Facilities
Resources                     (curated landing page; articles live at /library/{article}/)
  └─ FAQ
Contact
Booking                       ← “Book a Lesson” header button
```

The header "Book a Lesson" item links to `/booking/`; every booking button uses the shared Acuity
URL (Settings → Booking). Implementation status and Kadence configuration steps: see
`docs/07-website-installation.md` and `docs/09-phase-2-status.md`.
The emergency pages (`/emergency`, `/h/…`) are **not** in navigation; `/emergency` is linked
from the footer and the banner.

## Plugins (proposed, minimal)

| Plugin | Purpose |
|---|---|
| Kadence Blocks | Layout blocks for Gutenberg |
| Two Factor | MFA for administrators and coordinators |
| A form plugin (to be chosen in Phase 2; candidates: Gravity Forms or Fluent Forms) | Contact, inquiries, later onboarding |
| An SEO plugin *or* core + a small custom meta setup | Titles, OG tags, redirects from old URLs |

Explicitly excluded: Elementor, Divi, WPBakery, commercial multipurpose themes, duplicate
lazy-load/optimisation plugins (WordPress core already lazy-loads images).
