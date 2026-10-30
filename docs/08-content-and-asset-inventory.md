# 08 — Content & Asset Inventory

Everything the public website contains, where it comes from, and what is still needed from the
owners. "Owner notes" are the dashed **Owner to confirm** boxes visible on the pages; the live
count is in `docs/test-results/phase-2.md` and in **Tools → Corner Stone Farm Setup**.

## Pages (21)

All pages are ordinary WordPress pages built from core blocks, editable in the block editor.
Layouts are also registered as patterns (**Patterns → Corner Stone Farm: Page layouts**) so a
page can be rebuilt from its original layout at any time.

| Page | Path | Built from | Live (dynamic) content | Photo placeholders | Owner notes |
|---|---|---|---|---|---|
| Home | `/` | Hero, Pathways, Philosophy, Meet Beth, Mentorship, Featured programs, Featured horses, Resources teaser, CTA | Sale horses (hidden when none), latest articles (hidden when none), booking button | hero-home, lessons, training, camps, beth-portrait, mentorship | 1 |
| Lessons | `/lessons/` | Header, What we teach, Program cards, Getting started, Pricing, CTA | Booking | lessons, youth-riders, adult-amateurs, dressage, offsite, after-school, leasing | 2 |
| Youth Riders | `/lessons/youth-riders/` | Header, What riders learn, For parents, CTA | Booking | youth-riders | 1 |
| Adult Amateurs | `/lessons/adult-amateurs/` | Header, Riding on your terms, Getting started, CTA | Booking | adult-amateurs | 1 |
| Dressage Foundations | `/lessons/dressage-foundations/` | Header, Who it is for, CTA | Booking | dressage | 1 |
| Off-Site Lessons | `/lessons/off-site-lessons/` | Header, How it works (Wednesdays, grouped by area), CTA | Booking | offsite | 1 |
| After-School Program | `/lessons/after-school-program/` | Header, About the program, CTA | Booking | after-school | 1 — **do not publish until details are supplied** |
| Horse Training | `/horse-training/` | Header, Services, Intake steps, Young horses, CTA | Booking | training, young-horse | 2 |
| FEI Young Horse Development | `/horse-training/fei-young-horse-development/` | Header, Approach, CTA | Booking | young-horse | 1 |
| Camps & Clinics | `/camps-clinics/` | Header, Upcoming dates, What we offer, CTA | Upcoming events (past events hide automatically) | camps | 1 |
| Horse Sales | `/horse-sales/` | Header, Listing, How we sell, CTA | Available sale horses | horse-sales | 1 |
| Leasing & Mentorship | `/horse-sales/leasing-mentorship/` | Header, Leases / Mentorship / Working students, Lease horses, CTA | Available lease horses | — | 1 |
| About (Our Philosophy) | `/about/` | Header, Who we are, Philosophy, Sustainable by design, Get to know us, CTA | Booking | philosophy | 2 |
| Meet Beth | `/about/meet-beth/` | Header, Bio & credentials, CTA | Booking | beth-portrait | 1 |
| Meet the Team | `/about/meet-the-team/` | Header, Beth & Kris cards, CTA | Booking | beth-portrait, kris-portrait | 1 |
| Meet the Horses | `/about/meet-the-horses/` | Header, Listing, CTA | Farm horses | — | 1 |
| Facilities | `/about/facilities/` | Header, three photo placeholders, CTA — **no facility descriptions are invented** | Booking | facility-arena, facility-barn, facility-grounds | 1 |
| Resources | `/resources/` | Header, Topics, Articles, FAQ & New-rider cards, CTA | Library topics and articles | — | 0 |
| FAQ | `/resources/faq/` | Header, Q&A (Details blocks), CTA | Booking | — | 1 |
| Contact | `/contact/` | Header, Contact details, Booking, Writing guidance, Emergencies | Farm contact details (from settings) | — | 1 |
| Book a Lesson | `/booking/` | Header, Online booking, Before your first ride, Not booked online | Booking | — | 1 |

Also present: **Site Footer** (synced pattern), *Primary Navigation* and *Footer Navigation* menus,
WordPress's default *Privacy Policy* page (draft — needs owner/legal content before launch).

## Structured content (custom post types)

Only content that is genuinely structured, repeated, and listed in several places gets its own
type. Programs are ordinary pages (no duplicate "program" records to keep in sync).

| Type | Admin menu | Public URL | Fields | Used on |
|---|---|---|---|---|
| Library Article (`csf_resource`) | Knowledge Library | `/library/{article}/`, `/library/topic/{topic}/`, archive `/library/` | Title, body, **Summary** (excerpt), Topics (15 seeded), Audiences, featured image, **Reviewed** date; automatic **reading time**, **Updated** date (last revision), **related articles**; WordPress statuses (draft / pending review / published) and revisions | Resources, Home |
| Horse (`csf_horse`) | Horses | `/horses/{name}/` | Listing (Farm Horse / For Sale / For Lease), registered name, breed, sex, year foaled → age, height, colour, discipline, suitability, training history, availability, price **+ "owner approved" checkbox**, video URL, photos | Horse Sales, Leasing, Meet the Horses, Home |
| Camp/Clinic (`csf_event`) | Camps & Clinics | `/events/{name}/` | Type (8 seeded), start/end dates, times, ages, location, group size, price **+ approval**, registration status, registration link | Camps & Clinics |

Public horse profiles are separate from the unlisted emergency ID profiles (`csf-emergency`).
Nothing is shared between them automatically.

## Block patterns (14 section patterns, 21 page layouts)

Section patterns (**Corner Stone Farm: Sections**): Home hero · Pathways · Our philosophy · Meet
Beth · Mentorship & community · Featured programs · Featured horses for sale · Educational
resources teaser · Call to action · How to get started · Page header · Image and text ·
Testimonial (real quotes only — not placed on any page) · Site footer.

## Dynamic blocks (category "Corner Stone Farm")

Book a Lesson Button · Farm Contact Details · Horse Listing · Upcoming Camps & Clinics · Library
Articles · Library Topics · Related Articles · Copyright Line.

## Block styles editors can apply

Group: Section Linen / White / Warm Tan / Barn Black / Heritage Brown, Card · Button: CSF Primary /
Outline / Light · Paragraph: Eyebrow, Lead · List: Checklist · Image: Soft corners ·
Separator: Ornament.

## Assets

| Asset | Status | Source / licence |
|---|---|---|
| **Corner Stone Farm logo** | **Missing — needed.** The site shows the text "Corner Stone Farm" until the original artwork is supplied. No substitute logo was made. | Owners (vector preferred) |
| Tagline "Perfection Found Through Play and Practice" | Used as text in the philosophy band and footer | Owners |
| Playfair Display (400, 400 italic, 600, 700) | Bundled, self-hosted, Latin subset (≈212 KB total with Lato) | SIL Open Font License 1.1 (`assets/fonts/OFL-*.txt`) |
| Lato (400, 400 italic, 700, 700 italic) | Bundled, self-hosted | SIL Open Font License 1.1 |
| Photo placeholders (22) | `csf-core/assets/placeholders/*.svg`, generated by `scripts/dev/generate-placeholders.py`; each is labelled **PHOTO NEEDED** and names the shot required; alt text begins "Photo placeholder:" | Project-made |
| Testimonials | **None used.** Pattern exists for real, permitted quotes only. | Owners |
| Social links | Hidden until entered in Settings → Farm Details | Owners |

## Photographs needed (shot list)

Replace each placeholder by selecting the image block → **Replace** → Media Library. Photos of
children need written parental permission.

| Placeholder | Shot needed | Used on |
|---|---|---|
| hero-home | Horse and rider together at Corner Stone Farm — wide, calm, natural light (landscape, ≥1920 px wide; subject away from the left third where the headline sits) | Home |
| lessons | Beth teaching a lesson in the arena | Home, Lessons |
| youth-riders | Young rider with a lesson horse | Lessons, Youth Riders |
| adult-amateurs | Adult amateur rider schooling | Lessons, Adult Amateurs |
| dressage | Dressage foundations work | Lessons, Dressage Foundations |
| offsite | Off-site or haul-in lesson | Lessons, Off-Site Lessons |
| after-school | Young riders learning horsemanship on the ground | Lessons, After-School |
| training | Beth riding a young horse in training | Home, Horse Training |
| young-horse | Young horse development (groundwork or early rides) | Horse Training, FEI page |
| camps | Camp group with horses | Home, Camps & Clinics |
| horse-sales | Side-on sale horse photo | Horse Sales |
| leasing | Rider with a lease horse | Lessons |
| beth-portrait | Portrait of Beth (portrait orientation) | Home, Meet Beth, Team |
| kris-portrait | Portrait of Kris | Team |
| philosophy | Quiet moment between horse and rider | About |
| mentorship | Working students / youth mentorship | Home |
| facility-arena / facility-barn / facility-grounds | The real arena, barn, and grounds | Facilities |
| herd, resources, contact | Optional extra shots (available in patterns) | — |

## External services

| Service | Use | Status |
|---|---|---|
| Acuity Scheduling — `https://cornerstonefarm.as.me/?appointmentType=70075205` | All "Book a Lesson" buttons (one setting) | **Not verified** — unreachable from the build environment; owner must open it and tick *Verified* |
| Google Fonts | Not used (fonts are self-hosted) | — |
| Analytics, chat, embeds | None | — |
