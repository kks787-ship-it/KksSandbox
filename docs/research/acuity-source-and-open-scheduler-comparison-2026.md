# Digital Ranch Research — Acuity Source, Open-Source Booking Engines and Calendar UI

**Research date:** 2026-11-06 · **Scope:** Publicly accessible repositories, official project documentation, API/feature documentation and currently visible license statements. **No external software installed or used with farm data.**

**Question:** Is Acuity's actual source available, or is there an open-source scheduling platform with Acuity-like end-to-end function we can reuse rather than rebuild?

## Executive conclusion

**Part of Acuity's actual calendar source IS PUBLIC.** [`AcuityScheduling/acuity-calendar`](https://github.com/AcuityScheduling/acuity-calendar) explicitly says it is a React calendar component library used in Acuity Scheduling, package version `0.2.3`, `"license": "MIT"`. It provides `DayGrid`, `TimeGrid`, `CalendarWeek`, `CalendarMonth`, `CalendarGroups`, select-slot and event UI; the repo explicitly warns **not production-ready**. Its package uses React 16-era, Moment 2.24, Webpack 4 dependencies. **It is NOT the full Acuity backend, merchant account, notification engine, client/administration system, or current Acuity deployment source.**

Other Acuity public source:
- [Acuity calendar](https://github.com/AcuityScheduling/acuity-calendar) — actual general-purpose calendar UI component source (MIT declaration; not production-ready).
- [Acuity Class Calendar Demo](https://github.com/AcuityScheduling/class-calendar-demo) — MIT example with Express + Acuity API + FullCalendar.
- [Acuity JS API Toolkit](https://github.com/AcuityScheduling/acuity-js) and [PHP Toolkit](https://github.com/AcuityScheduling/acuity-php) — API clients to a **hosted Acuity instance**, not replacement scheduler code.
- [Acuity developer documentation](https://developers.acuityscheduling.com/) — official embeds, API, OAuth/webhooks; no full-server source.

**Strongest immediately usable UI candidate for our existing PHP/WordPress engine:** [DayPilot Lite](https://javascript.daypilot.org/open-source/) (Apache-2.0, plain JS, day/week/month, instructor/arena resource columns and selectable/editable blocks). If desired, build an isolated proof-of-concept to compare with our current custom v0.4.5 grid. [FullCalendar Standard](https://fullcalendar.io/license) is MIT and mature, but **vertical resource columns/resource timelines are Premium** under different commercial/AGPL terms. Acuity's actual 0.2.3 React library is a valuable architectural reference but is a poor unreviewed drop-in production dependency.

**Closest complete PHP-based open-source scheduling system:** [Easy!Appointments](https://github.com/alextselegidis/easyappointments), GPL-3.0, with [official WordPress bridge](https://github.com/alextselegidis/easyappointments-wordpress). It offers service/provider settings, public booking, staff hours/calendars, customers, notifications, and self-hosting. But it stores bookings in its own application/database and is NOT known to natively enforce simultaneous instructor+arena+horse suitability/workload/rest/guardian approvals. Replacing Digital Ranch with it would introduce a competing booking authority or require significant backend surgery. Assess in a *disposable isolated instance* only before proposing a migration.

**Most feature-rich full-stack source to study:** [Cal.diy](https://github.com/calcom/cal.diy), MIT fork of Cal.com. Has self-hostable booking engine, event types, buffers, booking pages, date overrides and documented Stripe integration. In April 2026 Cal.com moved its commercial production source to private repos; Cal.diy retains community open source but **teams, organizational admin and many enterprise features were removed**. The official repo explicitly recommends it primarily for personal/non-production use. It also needs Node/Next.js/PostgreSQL rather than native Plesk/PHP/MySQL. It is not a drop-in substitute for the farm's multi-resource scheduling.

**Resource reservation engine worth studying:** [LibreBooking](https://github.com/LibreBooking/librebooking), GPL-3.0, PHP/MariaDB, resource roles, quotas/credits and waitlists, but its resource-booking domain differs from family lessons/intake/billing. Critical authentication vulnerabilities were disclosed in older releases; release **7.1.0 (Oct 13 2026) or later is essential** for any testing. Do not deploy old forks/releases.

**Design choice for the farm:** Keep our tested authoritative WordPress scheduling engine (v0.4.5, 27-table recovery). Borrow *documented workflows* and evaluate carefully licensed visual components. Maintain one source of truth; do not install a second active booking engine as an unsupervised shortcut.

## 1. Verified repositories, functionality and maintenance caveats

| Candidate / source | License/source status | Confirmed product-level strengths | Main drawback for Digital Ranch | Recommended use |
| --- | --- | --- | --- | --- |
| **Acuity Calendar** [repo](https://github.com/AcuityScheduling/acuity-calendar) | `package.json` says MIT; v0.2.3; README says not production-ready | Actual Acuity-used React day/week/month TimeGrid/DayGrid, event grouping, select-slot primitives | Old React/Moment/Webpack dependency stack; UI ONLY, no booking backend | Read code and concepts; optional license- and compatibility-reviewed isolated UI prototype |
| **Acuity API SDKs / class demo** [org](https://github.com/AcuityScheduling) | API code published; class demo MIT | Client APIs, calendar demo; tells us how Acuity's public integration feels | Requires hosted Acuity account/service, not self-hostable Acuity backend | API and interaction reference only |
| **Easy!Appointments** [repo](https://github.com/alextselegidis/easyappointments) + [WP bridge](https://github.com/alextselegidis/easyappointments-wordpress) | GPL-3.0; current release shown as 1.6.1 Oct 2026 | Self-hosted PHP/MySQL, admin day/week calendar, client service→date/time booking, providers/working plans, confirmation emails, customer management, WordPress Gutenberg/Elementor/shortcode embed | Separate PHP application/backend and DB, WP bridge embeds its own scheduler; multi-resource horse/arena welfare and advanced farm billing not established | Best full-PHP reference/isolated side-by-side demo; avoid duplicate authority |
| **Cal.diy** [repo](https://github.com/calcom/cal.diy) | MIT; commercial Cal.com private since Apr 2026 | Rich event types, availability/date overrides, padding, booking pages, Stripe payment code, many calendar integrations | Removed teams/org/workflows/SSO enterprise functionality; official non-production/self-host warning; Node/Postgres stack | Review architecture for future client portal/checkout, not native WP replacement |
| **LibreBooking** [repo](https://github.com/LibreBooking/librebooking) | GPL-3.0; actively developed PHP + MariaDB/MySQL | Multiple resources, permissions/roles, quotas, waitlists, reporting, calendaring | Resource scheduling first, not family lesson/payment/intake first; serious 2026 security advisories on pre-7.1 versions | Review resource allocation/closures and reporting patterns in isolated latest secure release |
| **OpenCalendar** [repo](https://github.com/kudretyilmazz/opencalendar) | AGPL-3.0, community project | Public booking, round-robin/collective team scheduling, routing, workflows and webhooks advertised | More independent application complexity; verify maintenance/test/security maturity before adopting; Node/Postgres | Secondary research/architecture comparison |
| **Tymeslot** [repo](https://github.com/tymeslot/tymeslot) | AGPL-3.0 | Self-host booking pages, calendar sync and notices | Elixir/Phoenix, different stack and focus; not shown to cover farm resource combinations | Secondary reference, not likely a replacement |
| **Invoice Ninja 5** [repo](https://github.com/invoiceninja/invoiceninja) | **Source-available**, not equivalent to unconditional MIT/GPL rights; check current Elastic License 2.0 terms and white-label rules | Separate invoicing, recurring charges, client portal, online payment integrations | Not an appointment engine; license/branding/integration require review | FUTURE billing Issue #24 candidate, not the calendar engine |

### Easy!Appointments technical review
- Official [application source](https://github.com/alextselegidis/easyappointments) is PHP 8.2+/MySQL, GPL-3.0. Official [setup docs](https://github.com/alextselegidis/easyappointments/blob/main/docs/installation-guide.md) describe service names, durations, prices, providers and their working hours, optional secretaries and client booking.
- Official [WordPress bridge](https://github.com/alextselegidis/easyappointments-wordpress) is GPL-3.0 and supports Gutenberg block, Elementor widget and shortcode. **Requires a separately installed Easy!Appointments engine; plugin is an embed/bridge, not the authoritative WP scheduling engine.**
- Its standard online appointment workflow is valuable as the *UX reference*, but native support for simultaneous exclusive arena, instructor and horse scheduling, bounded 30-min horse consecutive work, rest cycles, family 30+30 / 120+120+60, minor rider clearance, and 15-min merged family buffers is **not established from the reviewed documentation**. Treat as gaps requiring proof or custom development; no assumption that it covers them.
- Online-payment features are not validated in the reviewed base documentation. Do not infer Stripe/ACH/recurring boarding invoices are included just because service price is configurable.

### Cal.diy licensing/architecture update
- [Cal.com's own April 15, 2026 announcement](https://website.cal.com/blog/cal-diy-open-source-to-closed-source) states production code moved private; public `cal.diy` retained self-host core without teams/org/commercial modules.
- [Current public repository](https://github.com/calcom/cal.diy) declares MIT and warns users to consider non-production use. [Current docs](https://github.com/calcom/cal.diy/blob/main/apps/docs/content/index.mdx) indicate paid events via Stripe/PayPal, buffers, date overrides, minimum notice and booking limits, but lack team/organization workflows. [Stripe integration source](https://github.com/calcom/cal.diy/blob/main/packages/app-store/stripepayment/README.md) exists; quality/security suitability not independently tested here.
- A farm would need multiple staff, horses, arenas, roles, intake and client-account billing. Adopting Cal.diy wholesale would require rebuilding or reconnecting much of our validated PHP booking model.

### LibreBooking security warning
- The current [LibreBooking README](https://github.com/LibreBooking/librebooking) warns that **pre-7.1.0** versions allow unauthenticated forged remember-me cookies to sign in as other users, including administrators; versions **pre-7.0** had a separate critical administrator takeover risk. The GitHub [release list](https://github.com/LibreBooking/librebooking/releases) identifies 7.1.0 dated October 13, 2026. Do not assume old Booked Scheduler forks, stale tutorials or pre-7.1 releases are safe. Read advisories before even isolated deployment.

## 2. Comparison of calendar UI libraries: avoid reinventing the timeline

| Visual calendar | License / available features | Fit for our existing WP/PHP booking engine | Caution |
| --- | --- | --- | --- |
| [**DayPilot Lite**](https://javascript.daypilot.org/open-source/) | **Apache-2.0**, JS with day/week/month and **multiple-resource columns**, drag/create/move/resize events, timeline + month/date navigator; current releases visible in 2026 | **Strong candidate**. Can consume permission-filtered JSON from existing PHP without replacing booking domain | Review integration size, accessibility, timezone/DST, XSS, Apache-2.0 notices and compatible GPL distribution terms; never let drag commit without server preview |
| [**FullCalendar Standard**](https://fullcalendar.io/license) | MIT, mature date/time grid, click/select, event details, month, mobile | Strong if default days/time-grid sufficient; well documented | **Resource timeline and vertical resource columns require Premium** for commercial farm deployment or full AGPLv3 compliance; may require paid license |
| [**Acuity Calendar**](https://github.com/AcuityScheduling/acuity-calendar) | MIT *declared in package*; time-grid selector, grouping, month/day/week | Exact Acuity heritage; helpful for understanding view composition | README warns not production-ready, 0.2.3 and old dependencies; could be more effort to modernize than our current v0.4.5 |
| [**TOAST UI Calendar**](https://github.com/nhn/tui.calendar) | MIT, day/week/month and click/drag | Technically relevant | Repository **archived September 2, 2026**: no upstream security/maintenance response; avoid adding it as a new primary dependency |
| [DayPilot Lite PHP calendar tutorial](https://code.daypilot.org/17910/html5-event-calendar-open-source) | Apache-2.0 example with PHP+SQLite backend | Useful concrete frontend↔server pattern for Codex | Sample/demo only, not production booking authority |

[FullCalendar's license details](https://fullcalendar.io/license) explicitly differentiate MIT standard from Premium commercial/AGPL terms. [DayPilot Lite's open-source list](https://javascript.daypilot.org/open-source/) explicitly includes resource columns in the Lite package. This is the biggest immediate reuse opportunity.

## 3. Feature gap comparison for Corner Stone Farm

**Legend:** `documented` means public product documentation describes the capability; `partial` means not enough proof of our exact horse-specific rule; `unverified` means documentation reviewed does not establish it. These are not claims that a repo cannot be extended.

| Farm requirement | Acuity/normal scheduler | Easy!Appointments | LibreBooking | Cal.diy | Our Digital Ranch |
| --- | --- | --- | --- | --- | --- |
| Public service-first booking | Documented | Documented | Resource-first | Documented | Future gated frontend Issue #25 |
| Staff visual day/week calendar | Documented | Documented | Documented | Documented | **Installed v0.4.5** |
| Resource/room/instructor availability | General availability & resources | Provider/location (partial multi-resource) | Multi-resource documented | Team support removed in community | Instructor+arena+horse domain implemented |
| Per-service duration, dates, notice | Documented | Documented | Resource booking settings | Documented | v0.4.5 durations, publish controls future |
| Family grouped 30+30, 120+120+60 shared outer buffers | Not established | Not established | Not established | Not established | Owner-approved spec, pending |
| Horse suitability + individual work/rest/rotations | Not established | Not established | Not established | Not established | Farm-specific authoritative rules implemented |
| Guardian intake / clearance before riding | General intake (not identical) | General customer fields (not identical) | Not established | Booking questions (not identical) | Future household/portal and staff clearance control |
| Booking notifications | Documented | Documented | Notifications documented | Documented | Planned outbox; real send held |
| Booking-time Stripe/deposits | Acuity integrated | Not verified in base release | Not equivalent to full billing | Stripe code exists | Future Issue #24 |
| Boarder recurring invoices + training client account ledger | Outside core appointment purpose | Not established | Quotas/credits ≠ invoicing | Not established | Future Issue #24 |
| WordPress native identity/MFA and existing 27-table recovery | Different service | Separate app/DB via WP bridge | Separate app | Separate Node/Postgres app | **Already qualified** |

## 4. Recommended reuse strategy (not automatic installation)

### First choice — preserve authoritative Digital Ranch scheduling
- **Do not replace or run parallel live scheduling engines.** Our booking engine already supports multi-resource conflicts, welfare rules, 15-minute times, locked transaction/audit, WordPress account MFA, 27-table restore, and functioning visual planner. A second engine would risk disagreement over who owns a slot, horse fatigue and booking/payment state.
- **Evaluate DayPilot Lite in an offline, isolated, license-reviewed UI prototype** as a possible future *visual layer only*, using exact synthetic copies of our day/week event and resource-lane read-model contract. Compare against current custom grid before deciding to replace it.
- In parallel, **review Acuity's published MIT React components** for select-slot model, grouped events and day/week composition; treat them as references, not a direct dependency. Inspect dependencies, access support, license notices and DST behavior.
- **Review Easy!Appointments** as a mature example of service catalog administration, working hours/blocked-time UX, client scheduler and WordPress bridge, not as the farm's system of record.
- **Review Cal.diy** booking page and payment/webhook patterns for Issue #25 (client booking) and Issue #24 (future invoicing/checkout), not as a reason to move the existing WordPress plugin into Node/Postgres.

### Bounded local evaluation that Codex can perform without disrupting ongoing calendar
1. Inspect the published repositories at pinned versions/commits, and confirm licenses and actual last-commit/release dates. Record provenance and dependency/SBOM findings. No unverified drop-in imports.
2. Build a **static local test with synthetic event/resource records** using DayPilot Lite (and optionally FullCalendar Standard), consuming the current **read-only** Digital Ranch data shape. Compare visual capability at 390/768/1280px, event click, click-to-start, resource lanes, accessibility, 15-min grid, DST folds/gaps, empty-day behavior, overlapping lessons, availability/closure blocks, family grouping and expansion.
3. Verify technical costs: JS size, load performance, dependency/license requirements, update policy, PHP/WordPress shortcode integration, and embedding without duplicate source of truth. DayPilot Lite open source does not authorize bypassing our own server checks.
4. **Decision gate:** Retain current built-in planner if mature component adds complexity without meaningful usability improvement. Otherwise prepare a guarded, file-only frontend adapter proposal; **do not replace current sandbox code without a separate evidenced owner decision** if the switch is substantial.
5. Issue #11's **next active milestone remains recurring arena/instructor hours and visual closure editing** plus remaining booked family/buffer/horse-correction tasks. Do not derail active Codex work to rewrite the whole calendar because an alternative project looks polished.

## 5. Licensing and security notes

- **MIT** and **Apache-2.0** generally permit commercial modifications subject to conditions (including original license/notice retention). If including third-party files in a WordPress plugin, verify notices, transitive dependencies and distribution compatibility with the GPL licensing of the existing plugin. **A public GitHub repo alone is not a reuse license.**
- **GPL-3.0** and **AGPL-3.0** carry source-availability obligations in defined distribution/network circumstances, and integrating code into another work may alter the licensing position. Seek legal/licensing review before importing these full backend modules into current Digital Ranch source. Do not simply copy proprietary Acuity backend/browser bundles or hidden network implementation.
- These are **research findings, not a live code security audit**. Any third-party service or library requires dependency review, test and current security patch status before it handles farm or student data.
- [TOAST UI Calendar](https://github.com/nhn/tui.calendar) is archived (2026-09-02); don't choose archived software merely because earlier blog posts call it maintained.
- [Invoice Ninja](https://github.com/invoiceninja/invoiceninja) is **source available**, with licensing/white-label constraints; do not treat it as freely embeddable GPL/MIT code without checking the current terms.

## 6. Decision for this sprint and future revisit

**Now:** Keep protected calendar v0.4.5 and existing 27-table backend. Finish recurring arena/instructor hours/visual closures, grouped family visit/buffers, horse corrections and owner acceptance. Existing Acuity-like staff/frontend design specification remains valid.

**Optional lightweight R&D:** Compare DayPilot Lite with current planner in disconnected fixtures, plus a read-only audit of Acuity's published React components.

**Later:** Add the owner-controlled frontend booking preview (Issue #25) without turning on real public self-service; integrate payment/invoice ledger after separate implementation and merchant decision (Issue #24). Consider Invoice Ninja or another standalone invoicing product only after end-to-end accounting needs are defined.

## 7. Primary sources

- Acuity official organization/repositories: https://github.com/AcuityScheduling and https://github.com/AcuityScheduling/acuity-calendar
- Acuity calendar React source (MIT in package manifest): https://github.com/AcuityScheduling/acuity-calendar/blob/master/package.json
- Acuity API docs: https://developers.acuityscheduling.com/
- Acuity demo: https://github.com/AcuityScheduling/class-calendar-demo
- Easy!Appointments engine: https://github.com/alextselegidis/easyappointments and https://easyappointments.org/
- Easy!Appointments WordPress bridge: https://github.com/alextselegidis/easyappointments-wordpress
- Easy!Appointments install: https://github.com/alextselegidis/easyappointments/blob/main/docs/installation-guide.md
- Cal.diy source/limits: https://github.com/calcom/cal.diy and https://website.cal.com/blog/cal-diy-open-source-to-closed-source
- Cal.diy Stripe example: https://github.com/calcom/cal.diy/blob/main/packages/app-store/stripepayment/README.md
- LibreBooking source/security/release: https://github.com/LibreBooking/librebooking and https://github.com/LibreBooking/librebooking/releases
- OpenCalendar source: https://github.com/kudretyilmazz/opencalendar
- Tymeslot source: https://github.com/tymeslot/tymeslot
- DayPilot Lite: https://javascript.daypilot.org/open-source/ and https://javascript.daypilot.org/download/
- FullCalendar Standard/Premium licensing: https://fullcalendar.io/license
- TOAST UI (archived): https://github.com/nhn/tui.calendar
- Invoice Ninja 5: https://github.com/invoiceninja/invoiceninja
