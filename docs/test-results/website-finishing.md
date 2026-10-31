# Public website finishing — local review candidate, October 31, 2026

**0778176 review record.** Follow-up shared gutter correction, safe saved-Contact upgrade
and downloaded-theme fixture evidence are in [kadence-layout.md](kadence-layout.md).
Authenticated Plesk sandbox acceptance remains unrun; the historical counts below are retained.

Candidate: **csf-core 0.3.0**, prepared from 4635106, reconciled with newer shared-branch documentation through 457fffb.
**Not deployed.** The owner reports core 0.2.0 and all 21 pages already active/published on
the Plesk sandbox. No rebuild, new setup, permalink change, emergency activation or VPS
operation was performed here. Production remains excluded. Current owner-supplied server
state is in [sandbox-preflight](sandbox-preflight.md).

## Changes prepared

- Deployment explicitly normalizes selected plugin directories/files to 0755/0644 under
  private-source umask 077. It retains destination ownership, avoids copying source owner/
  group, and leaves wp-config.php at 0600. New files belong to the executing account: use
  the correct subscription user. --only-core excludes emergency from checks/copies/mode edits.
  Existing environment/URL, backup/evidence and symlink safeguards remain in place.
- Setup checks page insertion/update, footer, taxonomy, menu creation/items/locations, option,
  metadata and rewrite writes. Errors reach the log and admin error notice; CLI exits nonzero
  and does not print success. Failed runs do not stamp a new completion marker. Partial
  changes can persist and are disclosed; unchanged values and owner edits remain protected.
- Optional Farm Inquiry Form lives in core, not another plugin. Disabled by default, no
  recipient automatically written. Recipient changes clear verification/enablement. Uses
  fixed recipient/subject, validated Reply-To, bounded scalar fields, nonce, timing signature,
  honeypot, reply permission, expiring one-use and sending-limit claims. No attachments,
  auto-replies, raw-IP/message storage or retries. Mail rejection is an error, not success.
- Prepared a separate exact-sandbox authenticated Kadence review runner, plus a local
  Chromium executable override. It refuses production and substitute themes, treats 401
  as authentication blocked, keeps credentials/results private and blocks browser writes.
  It does not log into the editor or replace manual visual/keyboard/editor acceptance.

## Executed local validation

| Check | Result |
|---|---|
| composer lint | 69 plugin PHP files passed |
| php vendor/bin/phpcs | Passed; zero errors/warnings |
| Development backup/deployment guards | 22 scenarios passed |
| Plesk preflight wrapper | 6 scenarios passed; runtime checks mocked |
| Python exact-target inspection | 14 tests passed |
| Real rsync permissions regression | Passed initial and repeated applies under umask 077, mutation-free dry run, preserved config 0600, and core-only emergency exclusion; synthetic targets |
| Isolated WordPress setup failure injection | 27 checks passed, including real CLI exit/admin error notice, failed writes, unchanged-value rerun, content/hash preservation and no orphan child pages |
| Isolated inquiry validation | 39 checks passed; all mail intercepted, no external delivery |
| Inquiry HTTP/browser | 17 checks passed: 390/768/1280px, form axe WCAG 2.2 AA checks, keyboard/required fields, 303 confirmation, 429 replay, 400 invalid nonce/GET, exactly one mocked transport call |
| Gutenberg browser suite | 61 checks passed: pages/footer, 35 patterns, deliberate invalid-block control, all 9 dynamic blocks, editor JS |
| Existing takeover checks | 8 passed on disposable local WordPress |
| Existing emergency HTTP/privacy | 14 passed on localhost; no sandbox activation |
| Kadence review runner | Syntax and 6 target/credential/privacy guard tests passed; actual authenticated Kadence run NOT PERFORMED |
| git diff --check | Passed |

Environment: local WordPress 7.1.4, PHP 8.4.24, SQLite integration, Chromium 154.0.8037.57,
**test-only substitute theme**. The new setup/inquiry regression runner deep-copies the local
fixture and selects its own temporary SQLite DB before WordPress loads. It destroys only its
own temporary fixture. Browser inquiry transport is intercepted by a fixture-only MU plugin;
that interceptor is never shipped in application code. Historical phase-2 report/screenshots
were not overwritten. Initial Chromium attempts were blocked by managed socket restrictions;
reruns with the permitted local browser execution completed successfully.

Reproduce with installed dependencies and local fixture:

```bash
composer lint
php vendor/bin/phpcs
bash tests/scripts/development-guards.sh
bash tests/scripts/plesk-preflight.sh
PYTHONDONTWRITEBYTECODE=1 python3 tests/scripts/test_sandbox_preflight.py
PYTHONDONTWRITEBYTECODE=1 python3 tests/scripts/test_kadence_review.py
bash tests/scripts/deployment-permissions.sh  # installed rsync; optional CSF_RSYNC_BIN
CSF_INQUIRY_BROWSER=1 bash tests/integration/run-setup-reporting.sh
node tests/e2e/run.js --only=editor
# Optional browser executable: export CSF_CHROMIUM_EXECUTABLE=/path/to/chromium
# Existing takeover/emergency tests require the disposable localhost:8890 site only.
```

## Remaining acceptance, in order

1. Authenticated real Kadence 1.5.2 review: prioritize six pages, then audit **all 21** at
   390/768/1280px. Newer remote commits report shared intro-gutter **Issue #1**, superseding
   the earlier no-confirmed-defects report. It remains unreproduced/unfixed in this session. Header/mobile navigation, layout metadata, footer
   block 27 placement and Gutenberg saving need actual review. The prepared page runner
   supplements manual acceptance; missing footer placement is configuration, not a proven bug.
2. Review and separately approve the core candidate update, with a current sandbox backup,
   source SHA and core-only dry run. Earlier d360dbf/52401dc and older structural restore
   records are historical; preserve them without claiming they validate the new candidate
   or current installed state. The newer remote gate/copy/activation/setup records were preserved, including exact jobs in doc 05. No new infrastructure backup/restore was executed here.
3. Owner-confirmed inquiry destination is beth.siemon@cornerstonefarmomak.com. Provider is
   unknown; the owner explicitly requests keeping the form disabled. Real MariaDB claim
   behavior, mail service/sender authentication, inbox receipt, cache/proxy/cron/privacy
   configuration and enabled authenticated sandbox form testing remain unverified. Transport
   acceptance is not delivery. No message was sent to Beth by these tests.
4. Finish authentic logo/photos, header/footer/palette/typography configuration, Acuity
   destination verification and the 23 owner-confirmation notes before Beth's acceptance.

Emergency concurrency/audit/token permanence remains separate work in
[DECISIONS](../DECISIONS.md#proposed-emergency-concurrency-fix--design-before-operational-approval)
and [SECURITY](../SECURITY.md). That transactional state/command/audit/outbox proposal is
not implemented by this public-site update. Emergency remains inactive and non-operational.
