# Milestone B preparation — current evidence

October 30, 2026 (America/Los_Angeles). Preparation branch: codex/takeover-audit.
Baseline 676e149 was pushed and the remote SHA verified. No merge, VPS access or deployment.

| Validation | Result |
|---|---|
| `bash tests/scripts/development-guards.sh` | 22 scenarios pass: environment guards, dry-run/apply, backup archives/checksums, paths with spaces, missing prerequisites, wrong URL, corrupted backup, symlink target |
| `bash tests/scripts/plesk-preflight.sh` | 6 scenarios pass: valid dev identity and rejection of production, unknown type, wrong URL, indexing on and stand-in theme; runtime PHP checks mocked |
| `wp eval-file tests/integration/takeover.php` | 8 local WordPress regression checks pass |
| `composer lint` and `php vendor/bin/phpcs` | Pass across 68 plugin PHP files |
| Shell syntax / whitespace | Pass for deployment, inspection, backup, preflight and guard tests |
| Actual rsync 3.4.1 dry run | Proposed plugin file copies and an obsolete-file deletion in an isolated local target; confirmed target remained unchanged |
| System Chromium launch | Passed with Playwright executablePath=/usr/bin/chromium (154.0.8037.57); not a theme compatibility test |
| Real Kadence download | Blocked: downloads.wordpress.org returns HTTP 403. Exact installed version and authentic ZIP not supplied |
| Real Kadence, Plesk PHP/DB/cache/rewrites, backup restore | Not run; cannot claim deployment readiness |
| Emergency parallel MySQL persistence | Proposal documented, not implemented or tested |

No runtime code change was made to emergency concurrency; misleading comments were clarified.
See [the proposed transactional fix](../DECISIONS.md#proposed-emergency-concurrency-fix--design-before-operational-approval).
No mock test certifies the real server, a completed restore or an owner's approval.

The local rsync log is retained outside the checkout at
`/workspace/releases/milestone-b/local-rsync-dry-run.txt`. Plugin-only ZIPs and checksums
are generated under `/workspace/releases/milestone-b` from the preparation commit for review;
they must not be installed until the server identity, backup and Kadence gates are satisfied.
