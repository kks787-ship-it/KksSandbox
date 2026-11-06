# DayPilot Lite decision — retain the native planner

**November 6, 2026 · local synthetic experiment · no farm operation.**

DayPilot Lite is a useful candidate for multi-arena visualization. The tested adapter does **not** justify replacing the current staff calendar: both renderers already open a lesson or candidate start in one click, while DayPilot needs additional keyboard, phone-week and DST work. Keep the native calendar and continue recurring hours/visual closures. No adoption, production dependency or sandbox deployment was performed.

## What actually ran

The official npm **6.2.0** package was installed through its locked SHA-512 integrity, with lifecycle scripts disabled. Bundle header: **2026.4.895-lite**. Runtime dependencies: none declared. The version was published **2026-10-22**. Lockfile installation (`npm ci --ignore-scripts`), registry advisory audit (zero known advisories across the locked dependency set), JavaScript syntax and PHP harness syntax passed. This is not a comprehensive security audit.

The baseline is the **actual native PHP planner renderer, time helper, CSS and JavaScript**, not an illustrative substitute. Source: Scheduling 0.4.6 pin `c2783b5e3f6eba046b97e5dc517a198517532377`; planner/time/JS/planner CSS are byte-identical to 0.4.5. Native access/document functions are CLI-only synthetic stubs. The shell uses a system font and explicitly positions iframe dialogs; no WordPress, Kadence, database, authentication or real booking service is loaded. The fixture clock is fixed to the displayed November week.

Five fixture test groups and **99 browser interaction/safety assertions** passed using pinned Playwright 1.62.1, axe 4.10.3 and system Chromium at **390 / 768 / 1280px**. The source/fixture text receipts are committed beside this report. The six synthetic PNGs are now published with explicit owner approval. The earlier automated publication hold was honored until that approval. All temporary test servers/containers were stopped.

## Findings and limits

| Criterion | Native baseline | DayPilot trial | Decision implication |
| --- | --- | --- | --- |
| Day/week and empty day | Actual renderer; compact mobile day selector | Actual vendor day/week, empty-day rendering | Both usable; no click-count win |
| Instructor/arena lanes | Instructor lanes and existing single-arena filter | Two instructor **or** two arena columns | Genuine advantage for simultaneous arena overview |
| Event and slot interaction | One click to details / fixture start | One click to details / fixture start | Neither is a complete create workflow in this harness |
| Quarter-hour touch geometry | 44px slot height | 22px slot height in the dense trial configuration | Density trades off against touch size; configurable, not an inherent limit |
| Phone week | Compact chosen-day presentation | Seven narrow columns clip labels at 390px | Needs a deliberate compact layout before adoption |
| Raw keyboard event access | Native button, Enter, Escape and focus return tested | Raw blocks had `tabIndex=-1`, no role and could not take focus | Separate keyboard detail/time fallbacks work, but do not repair the raw grid |
| Scoped axe audit | Zero violations in planner region at all widths | One serious `scrollable-region-focusable` finding at each width, vendor-generated `#lite > div:nth-child(2)` | Accessibility acceptance **did not pass** for DayPilot; wrapper's own scroll focus was corrected |
| Spring clock change | **92** real quarter-hour instants | **96** wall-time cells per column | Raw grid exposes a nonexistent hour |
| Fall clock change | **100** distinct offset-labeled instants | **96** wall-time cells per column | Raw grid merges repeated local clock positions |
| DST selection bridge | Existing PHP choices are authoritative | Prototype rejects missing time and requires one of two explicit offsets | Safe selection bridge works; visual DST problem remains |
| Closures | Arena filter disables the affected native slots | Named closure blocks and shaded arena cells | No closure editor was built in either fixture |
| Family/horse behavior | Existing renderer displays projected records | Illustrative 30+30 and 120+120+60 blocks, group summaries, warning/override labels | No actual family-buffer merge, horse rotation or welfare waiver is validated here |
| Writes / resource safety | No booking route in fixture | Move/resize/delete disabled; drag did not change event times | Real PHP conflict/welfare/clearance authority is preserved by **not connecting** a second writer |

The bundle's scope selector is a **fictional test harness**, not authentication. It includes all invented scopes. `project()` strictly allowlists fields and filters an already-authorized read model, dropping a synthetic private sentinel and denying malformed/absent view grants. This proves projection behavior, **not** a production authorization integration. A future adapter must obtain its projection from the existing authenticated WordPress/MFA service; it cannot trust a browser-selected scope.

## Measurements

These are single scripted samples at each width, summarized by the median of three widths. They exclude human reading, navigation/scrolling time, rider/resource assignment, actual PHP availability checks and saving. **Time-to-create and Beth/Kris usability were not measured.** A few milliseconds here do not establish a user benefit.

| Fixture task | Native median | DayPilot median | Clicks |
| --- | --- | --- | --- |
| Event → details | 46.1 ms | 41.2 ms | 1 each |
| Cell → candidate caption | 40.9 ms | 41.8 ms | 1 each |

Vendor JavaScript is **428,381 bytes** (**104,570 bytes** gzip-9), versus **4,101 bytes** for the native planner JavaScript alone. This excludes shared WordPress assets and PHP-generated HTML, so it is not a whole-site payload comparison. The experiment downloads cached synthetic snapshots for multiple views; its combined-page load timing is not a fair WordPress-versus-DayPilot performance benchmark. Raw render samples are retained for reproducibility, not used to claim a speed winner.

Full-width display controls and screenshots compare each calendar at the same viewport. **Split panes reduce the native iframe below its compact breakpoint even on desktop**; do not infer that the native calendar lacks a desktop seven-column week from the split screenshot.

## Supply chain and maintenance

- Vendor Apache-2.0 LICENSE/NOTICE, exact npm integrity, per-file SHA-256 and build identity are retained in [provenance](evidence/vendor-provenance.json). No transitive runtime dependencies are declared. Playwright/axe are isolated development dependencies.
- The new adapter still needs ownership: scoped projection integration, timezone mapping, responsive presentation, accessibility, update regression and mapping click-to-start into the existing reviewed preview/commit. The library does not implement horse suitability, shared-resource locks, rest, guardian clearance, MFA or audit.
- The native source declares GPL-2.0-or-later. This **separate experimental distribution** chooses GPL-3.0-or-later and retains Apache notices. No farm plugin license changed. License compatibility and corresponding-source obligations need review before an adopted combined distribution; Apache-2.0 is not a license for proprietary Acuity code.
- No farm records, account details, credentials or protected configuration were read. Browser requests stayed on loopback; all application interactions issued only GET/HEAD. A deliberate POST was rejected with 405; protected-config and other non-allowlisted routes return 404. No real notification, payment, MFA/grant, schema or scheduling source change occurred.

## Deliverable and recommendation

Use the [README](README.md) to run the prototype or its prebuilt offline ZIP. Inspect the owner-approved [native full-width week](evidence/screenshots/native-week-1280.png), [DayPilot full-width week](evidence/screenshots/daypilot-week-1280.png), [phone comparison](evidence/screenshots/comparison-390.png) and [arena columns](evidence/screenshots/resources-1280.png). [Browser measurements](evidence/browser-results.json) and per-file screenshot hashes identify the exact evidence. Public image publication was initially held by automatic review; the owner subsequently explicitly approved these six synthetic screenshots. No farm deployment or DayPilot adoption is implied.

**Retain the native planner.** Keep this runnable trial for future resource-overview review. Adoption would need a measurable owner usability advantage and resolution of the recorded raw accessibility and DST presentation gaps, while preserving the current server-authoritative engine and recoverable data. No owner approval or server change is needed to keep the current calendar. The ongoing recurring arena/instructor hours and visual-closure work remains the next active feature; this experiment is not a replacement engine or new architecture sprint.
