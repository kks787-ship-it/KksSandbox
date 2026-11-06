# DayPilot Lite side-by-side evaluation — Digital Ranch

**Status:** POC candidate / synthetic-only. Not a production dependency or approval to change the farm's calendar. Created November 6, 2026.

## Why

The owner approved comparing **DayPilot Lite 6.2.0** with the existing **Scheduling 0.4.5** visual planner to decide whether a mature UI component offers a noticeably better 15-minute grid, resource lanes, click interactions, and mobile usability without replacing the authoritative PHP booking system.

I created a standalone offline-data ZIP prototype in the owner conversation (`Corner_Stone_Farm_DayPilot_Lite_POC.zip`) containing:
- `index.html`: paired illustrative Digital Ranch grid and DayPilot Lite day/week/resource calendar; **not** the real sandbox UI
- `fixtures.mjs`: invented instructor, arena, horse, family lessons and downtime, no farm data
- `prototype.mjs`: Lite calendar adapter, view switching, event details and no-write time-range selection
- `fixture.test.mjs`: pure fixture sanity assertions
- `README.md`: instructions and limitations

To reconstruct inside a Codex cloud workspace, ask for the POC ZIP from the owner's current conversation or rebuild from this specification and pin `@daypilot/daypilot-lite-javascript@6.2.0` (Apache-2.0). **Do not treat this README alone as a browser-tested POC.** Generated artifact links are conversation-local and should not be copied into public GitHub.

## Pinned dependency and official patterns

- Package: https://www.npmjs.com/package/@daypilot/daypilot-lite-javascript ; version 6.2.0, Apache-2.0.
- Documentation: https://javascript.daypilot.org/open-source/
- Working Lite resource example: https://javascript.daypilot.org/demo/lite/calendar/resources.html
- API: `new DayPilot.Calendar("calendar", { startDate, viewType: "Week"|"Day"|"Resources", columns, cellDuration: 15, onEventClick, onTimeRangeSelected })`.
- For isolated browser trial: use NPM-pinned vendor files with license/NOTICE retained, no live CDN requests from the protected farm website.

## Synthetic comparison dataset

- Two sibling lessons Monday 10:00–10:30 and 10:30–11:00, same parent/family visit, two horses, one instructor/arena. Show **family outer 15-minute buffers conceptually only**, not as already-booked intervals.
- One separate Tuesday 60-minute lesson marked **BUFFER OVERRIDE** (red + label, illustrative).
- Wednesday 120+120+60 consecutive family lessons (five instructional hours; horses rotate).
- One Thursday requested/unreserved 30-minute lesson.
- Tuesday Main Arena closed 09:00–11:00 and Friday Covered Arena closed 12:00–15:00.
- Two fictional instructors, two fictional arenas, explicit resource grouping modes. Every entity invented; never copy staff, actual horses, clients, emails or credentials.

## Must test and measure

1. Owner-comprehensible **Day/Week/Resources** navigation, 15-minute grid, click exact date/time, click event details, visual statuses and simultaneous instructor/resource lanes.
2. Mobile 390px, tablet 768px, desktop 1280px; keyboard/screen reader, focus after dialog, event overlap and touch targets.
3. The November PST week plus spring/fall DST test cases: no nonexistent local times, correct repeated occurrence. All actual dates/offsets remain server authoritative.
4. Closed/unavailable blocks, instructor/arena availability and horse/welfare alerts **must never become editable booking authorities** just because DayPilot allows drag/drop.
5. Build a permission-filtered, **read-only** adapter to current PHP calendar projection in an isolated fixture. No additional booking engine or actual farm DB access.
6. Compare a current UI screenshot or safe local copy against DayPilot; record whether time-to-create, click count, readability, resource closure editing and code maintenance materially improve.
7. Audit version/security, bundle size, dependency/license notices and GPL-compatibility before proposing any production inclusion.

## Success gate / recommended decision process

- Favor **retaining native v0.4.5** if the new renderer is merely prettier or introduces complex dependencies/DST or a second booking write path.
- Consider a **frontend-only adapter** if the owner sees a real usability improvement and tests prove we can preserve all existing guarded preview/commit, service types, horse-rest restrictions, resource availability and audit behavior.
- **Do not deploy or replace the actual farm scheduler** based on this experiment without a separate tested owner review.
- The active next production feature remains recurring arena/instructor hours and visual closures under Issue #11. This POC must not block that work.

References: [active calendar Issue #11](https://github.com/kks787-ship-it/KksSandbox/issues/11), [source comparison report](../../docs/research/acuity-source-and-open-scheduler-comparison-2026.md), [client booking issue #25](https://github.com/kks787-ship-it/KksSandbox/issues/25).
