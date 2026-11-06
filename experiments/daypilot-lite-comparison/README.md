# DayPilot Lite comparison — runnable, synthetic-only

**Decision: retain the native Digital Ranch planner.** DayPilot is a credible resource-column renderer, but this trial does not justify replacing the farm calendar. Read [measured findings](DECISION.md), [browser evidence](evidence/browser-results.json) and the screenshots below. No farm server connection, account, database, migration, notification or deployment is involved.

## Run from the repository

Requirements: Node 22+, PHP 8.1+ CLI **or** the existing local `csf-scheduling-php83` test image, and Chromium for browser tests. The scripts do not provision a server or rebuild the scheduling engine.

```sh
cd experiments/daypilot-lite-comparison
npm ci --ignore-scripts
npm run build:fixtures
npm test
npm start
```

Open **http://127.0.0.1:8777**. The server binds loopback, allowlists fixture assets and rejects all writes. Stop it with Ctrl-C. With local PHP instead of Docker, use `CSF_EXPERIMENT_PHP=php npm run build:fixtures`. Default Docker execution has no network and mounts the repository read-only. Fixture generation never loads WordPress or a database.

To run the browser suite:

```sh
CSF_EXPERIMENT_CHROMIUM=/usr/bin/chromium npm run test:browser
```

Use the actual local Chromium path if different. No browser download or system installation is automatic. Screenshot/measurement outputs go to `.artifacts/`. The pinned Playwright and axe packages are development tools only.

## Run the prebuilt offline ZIP

The delivered ZIP contains the generated **invented** model, native assets and the exact DayPilot runtime, so viewing needs only Node:

```sh
node server.mjs
```

Open the loopback address above. Opening `index.html` directly with `file://` is unsupported because browser modules/fetch require an HTTP origin. The ZIP contains no development environment, credentials or farm export. Its hash is recorded in `evidence/bundle.json`; `python3 bundle.py` recreates the bundle after fixtures, tests and evidence have been generated.

## Try these comparisons

- Day / Week / Resources; two fictional instructors or two fictional arenas. The native arena comparison uses its actual single-resource filter; DayPilot can show both arenas simultaneously.
- **Display → Native full width / DayPilot full width** for a fair viewport comparison. Split panes can trigger native compact mode even on a desktop because each pane is narrower than the browser.
- Click a lesson; click/tap a blank quarter-hour. These produce **read-only fixture previews**, not engine availability or bookings. All drag/move/resize/delete writes are disabled.
- Tuesday's arena closure; Wednesday's 120+120+60 family sequence and parallel instructor; requested/unreserved, held and canceled examples. Family outer buffers and the red override are clearly labeled illustrations, not implemented farm reservations or welfare clearance.
- November 1, 2026 and March 14, 2027: compare the native actual-instant ruler with DayPilot's wall-time grid. The explicit local-time probe rejects the spring gap and asks which fall occurrence was intended.
- Keyboard detail fallback, focus return and phone layout. Raw DayPilot accessibility findings remain in the decision report; fallbacks do not erase them.

The scope selector changes **synthetic fixtures**, not authentication. The bundle contains all invented scopes and is not a production authorization adapter. `project()` demonstrates allowlisting/scoping of an already-authorized read model; real WordPress/MFA authorization must remain server-side before any future integration. Never point this fixture at actual farm records.

## Provenance and licenses

- Exact dependency: `@daypilot/daypilot-lite-javascript@6.2.0`, npm integrity locked. Its bundle identifies itself as **2026.4.895-lite**; these are different package/build version identifiers.
- Apache-2.0 [LICENSE](DAYPILOT-LICENSE.txt) / [NOTICE](DAYPILOT-NOTICE.txt) preserved. No runtime dependencies declared by the vendor.
- Native renderer: actual Scheduling 0.4.6 source pin `c2783b5e3f6eba046b97e5dc517a198517532377`; planner/time/JS unchanged from 0.4.5. System-font/iframe shell and CLI stubs are experimental. See [native notice](NATIVE-NOTICE.md) and [source hashes](evidence/native-source.json).
- Experimental wrapper/distribution: GPL-3.0-or-later, retaining native GPL-2.0-or-later notices. This does not change the scheduling plugin license. No DayPilot files are added to the farm plugins.

## Screenshot evidence and publication status

The run generated six PNGs: phone/tablet/desktop split comparisons, arena columns and full-width native/DayPilot week views. They are in the **private offline ZIP** and local `.artifacts/screenshots/`. Automatic approval review rejected publishing the opaque image blobs to public GitHub because authorization and non-sensitivity for those exact artifacts were not established. They are withheld; no alternate upload or encoded-image workaround is used.

The runnable source, [text findings](DECISION.md), [browser receipt](evidence/browser-results.json) and screenshot hashes remain public. Reproduce the images locally with the browser command above. After reviewing a new local run, `node collect-evidence.mjs` refreshes the private screenshot copies, receipt and checksum allowlist for `python3 bundle.py`. Collection does not commit or upload anything. The dated decision/raw-log hashes remain historical evidence rather than being silently relabeled as a new platform run.

Recurring arena/instructor hours and visual closures remain the next active calendar increment. This experiment does not replace that work or authorize DayPilot adoption.
