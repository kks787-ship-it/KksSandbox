# Farm records directory candidate — November 9, 2026

Status: published as draft PR #28; not installed or owner-accepted.
Review: https://github.com/kks787-ship-it/KksSandbox/pull/28
Base: deployed scheduling candidate `6883b29034b18581e81bf8cadaf52aef9c01a774`.
Isolated branch: `codex/farm-setup-ux`. Gift-certificate worktree remains unchanged.

## Why and what changed

Owner screenshot showed a flat, long sequence of unrelated edit, availability,
archive and delete accordions. The new roster groups Riders, Horses, Resources,
and Protective blocks in a task-focused directory, with a name search and visible
record counts. Each horse has one card; edit/suitability is primary and lifecycle
operations are nested separately with every existing confirmation intact.
Resource editing and its dated availability now live in the same record. A
native dialog opens one record without losing the list; Escape/Close restores
focus and retains edits on the current page. No personal record is put into URL,
localStorage or sessionStorage. Without JS, all four sections/native details are
readable; no new command path or no-JS write claim is introduced.

This is a focused cleanup of Farm Records, not a claim to redesign or accept the
whole calendar, lesson catalog, hours and settings interface. Existing booking
rules, permissions, nonces, revisions, command payloads, database and welfare
logic are unchanged. No release version or protected installation manifest is
advanced: a release requires the remaining integration verification first.

## Passed here

- All scheduling PHP syntax checks (PHP 8.4; not claimed PHP 8.1/8.3 reruns).
- Changed production PHP passes repository WPCS.
- JavaScript syntax and `git diff --check`.
- Real `CSF_Scheduling_UI::setup()` rendering with synthetic context: 23 native
  command payload/field contracts exactly match the deployed baseline, ignoring
  new-record UUIDs; balanced markup; hostile labels escaped.
- 33 isolated horse-lifecycle checks and 34 unused-horse-deletion checks.
- 22 deployment/backup guards and six mocked Plesk identity checks.

## Local browser limitation and isolated CI follow-up

`tests/directory/browser.js` is prepared for 390/768/1280px search/category,
keyboard/Enter/Escape/focus, close/reopen draft preservation, unchanged serialized
save contract, resource/availability pairing, destructive confirmation presence,
no-JS fallback, denied controls and axe WCAG checks. It uses actual PHP-emitted
forms and the production staff/directory scripts with intercepted submissions;
it does not qualify DB writes, real authentication, server errors or deployment.

Chromium could not launch: `socket() failed: Operation not permitted (EPERM)`.
The same authorized escalated command also failed. Cloud browser rejects `file:`
URLs. No runtime security settings were changed, no alternate transport was used
to evade either restriction, and no browser assertions/screenshots are claimed.

Run on a supported browser-capable local/isolated test runner:

```sh
npm ci
DIRECTORY_PHP=php CHROMIUM_EXECUTABLE=/usr/bin/chromium bash tests/directory/run.sh
```

Omit `CHROMIUM_EXECUTABLE` to use Playwright's installed browser. The runner writes
synthetic HTML/screenshots to a temporary directory. Inspect the output before
presenting it as a verified visual preview. Also run the existing native WP
fixture suites and actual authorized-role checks; fixture rendering is not MFA
or WordPress integration evidence. Existing legacy browser selectors that depend
on old top-level `Horse: name` / `Rider: name` summaries must be updated to open the
new named record first.

## Remaining before release

- Run browser/a11y suite and inspect screenshots; correct any findings.
- Native WordPress full-flow/permission checks and regression selector updates,
  including error/retry responses and long rosters, and actual phone acceptance.
- Review candidate; prepare a release-specific inventory/version only afterward.
- No merge, deployment, farm config changes, data changes or real actions
  have been performed. No protected recovery promise is made by these local tests.

## Approved publication and isolated browser follow-up

The owner approved publishing this UI branch as a draft pull request to use the
project test system, without installing the redesign. The new narrowly scoped
GitHub workflow targets `codex/digital-ranch-continuation`, explicitly checks out
the PR head and runs the synthetic presentation/browser checks plus unchanged
local safeguards. It has read-only repository permissions and no farm credentials,
deployment steps or real records. Its seven-day review artifact contains only
synthetic fixture HTML and screenshots. Passing it will establish
presentation-fixture results only, not native WordPress/MFA integration acceptance.

The first isolated CI run passed WPCS, syntax and 67 safety unit checks but
stopped before browser tests because ripgrep was absent. The follow-up installs
that standard runner dependency, adds open-dialog accessibility and overflow
checks, and activates the existing full disposable scheduler workflow for this
PR base. Native selector migrations follow the new category/dialog controls.
All further CI results remain pending until recorded here.
