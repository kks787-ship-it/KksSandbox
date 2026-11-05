# Staff calendar pilot readiness — November 5, 2026

Protected read-only inspection found a genuine usability blocker after owner setup: facility
settings still lacked timezone, hours, enabled services and other required configuration. There
were no appointment, resource, rider or horse records at that inspection. The engine correctly
blocked scheduling, but normal forms could not complete initial policy setup. Ownership and MFA
remain complete; neither was redesigned or repeated to resolve this problem.

## Scheduling0.4.2 candidate

A native Facility settings section lets the verified policy manager enter timezone, split daily
hours, services/capacity/duration/resource requirements, horse workloads/rest, training cap,
Academy season and preferred off-site/travel settings. It uses the existing validated, locked,
audited policy command, facility revision and original command identity. No schema or permission
model changed. Staff without the policy privilege cannot see or post the form.

A fictional pilot example loads into ordinary fields without saving anything or creating
records. Review shows current/proposed settings. Saving requires an explicit checkbox, nonce,
user-bound signed review and current authorization. Altered reviews are rejected; stale edits
retain their draft and require a new review; uncertain saves retain their exact original command.
Existing services cannot accidentally disappear, and split hours survive edits. Configured
settings collapse so the everyday calendar remains easy to reach on a phone. A review shortcut
near the top avoids scrolling through every field when loading the fictional example.

Only four scheduler paths change: header, staff UI, scoped stylesheet and one new settings
class. The scheduler has21 files; the25 MFA/provider files remain unchanged. See the
[exact inventory](scheduling-facility-settings-inventory.json).

## Targeted local validation

Both stacks passed **69 browser checks and15 parser checks per stack**:

- WordPress7.1.4, PHP8.3.36, MariaDB10.6.28.
- WordPress7.1.4, PHP8.1.34, MySQL8.0.46.

Tests use genuine provider-authenticated sessions in disposable offline fixtures. Coverage
includes unsaved example/review, signed-payload/nonce/checkbox protection, rejected lower access,
stale tabs, uncertain commit recovery without duplicate audit, multiple opening intervals,
ordinary resource/availability/rider/horse setup, private request/confirmation, missing-clearance
warnings, named resource conflicts, Sunday closure, four-rider/two-horse rotations, title edits
preserving turns, cancellation and retained audit history. Mobile/tablet/desktop geometry and
axe accessibility checks cover390/768/1280px. Malformed fields, unsafe types, duplicate services,
overlaps and unknown resource kinds are rejected by typed parsing/domain validation.

The three changed PHP files passed syntax and coding standards;22 deployment guards and6 mocked
Plesk checks passed. Raw logs remain private; hashes/counts are in the
[results inventory](scheduling-facility-settings-results.json). Preliminary tests found a generic
stale-error label; the final UI now explains the conflict and preserves the draft. Unused local
Docker builder cache was pruned when the disposable executor ran short of disk; no farm storage
or recovery set was deleted. Completed calendar/security/recovery suites were not broadly rerun.

## Protected execution and human acceptance

The existing fresh post-grant backup and successful isolated restore qualify routine sandbox
changes under the standing policy. Proposed scoped installation is four scheduler paths only,
with exact old/new hash checks and new class before header/UI publication. No plugin activation,
migration, role change, mandatory enforcement, email, public booking, inquiry or emergency change
is part of this update. File rollback preserves all newer owner and calendar data.

Actual protected authenticated appointment writes require the native Technical Master browser
session. No credentials, factor seeds, recovery codes or session cookies are collected/exported;
no fixture verifier or CLI authentication exception is copied to the farm. The protected browser pilot and Beth/Kris acceptance are not replaced by local test results.

After installation, use **Digital Ranch → Facility settings → Load fictional pilot example
(not saved) → Review facility settings → Apply reviewed settings**, then create clearly named
Pilot resources/riders/horses through Farm setup with explicit availability and suitability.
Use the documented fictional working week, then create/edit/confirm/cancel lessons and record
any confusing controls. Sunday closure and Monday/Wednesday dates here are test choices, not
claims of real farm policy or automatic recurrence/route scheduling. Welfare/rest, simultaneous
bookings, clock changes and other staff scenarios remain in the existing walkthrough; this
report does not claim those protected workflows or owner acceptance are already complete.
