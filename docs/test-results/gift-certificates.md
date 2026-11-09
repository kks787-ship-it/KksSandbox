# Gift-certificate development evidence — November 8, 2026

Status: **published draft candidate for isolated tests, not deployed or ready for live sales**.
Published with Kris's explicit approval in [draft PR27](https://github.com/kks787-ship-it/KksSandbox/pull/27),
stacked on the unchanged scheduling continuation branch. Publication does not authorize installation,
merging, payment enablement or financial grants. Earlier unpublished status below is historical.
The PR records the exact final source tree and CI outcome; do not treat a pending run as a pass.
Branch `codex/gift-certificates`; foundation commit `4c7d6d7` is superseded by this
0.2.0-dev increment. The follow-up below advances the local candidate to 0.2.1-dev. Installed scheduling, draft PR #26, farm data/configuration and all
existing holds are unchanged. No actual merchant account or credentials have been verified.

## Implemented

Separate `csf-billing` plugin, proposed schema 2 with six InnoDB tables (guard, orders,
certificates, command receipts, audit and throttle). Neither schema version has been installed
on the farm. Activation performs no migrations, grants or page publication. There is no live
payment adapter, email delivery, quarterly collection, public booking or automatic expiration.

- Closed-by-default Gutenberg/shortcode presentation; explicitly enabled local/development
  sandbox checkout with no built-in prices or terms. Synthetic fixture offers are not Beth's terms.
- Two-hour random signed site-bound anonymous session, Secure/HttpOnly/SameSite cookie,
  separate CSRF proof, exact HTTPS Origin/JSON/size checks, uncached responses and atomic
  per-route rate limiting. Certificate secrets never enter URLs or browser storage.
- Immutable accepted product/price/terms version and hash before hosted sandbox approval;
  server-verified final completed amount, currency, merchant, order and capture bindings before
  issuing an encrypted random unique certificate. Private receipt and print/save flow.
- Fresh explicit financial capability and existing real browser MFA proof plus REST nonce for
  purchase history, audit, redemption, correction and manual lost-create reconciliation.
  WordPress Administrator or scheduling rights alone grant no financial access.
- Atomic revision-checked ride debits, command replay, permanent ride-reference tombstones,
  audited one-time correction of original debits. These are operator-attested rides, not a
  scheduling integration: a deliberate new reference can still represent a duplicate real ride.
- Durable create/capture uncertainty, reconciliation without recapture, verified manual linking
  of a lost-create provider order, exact retry binding. Linking alone never issues credit.
- Pending/denied/completed capture handling; refund/reversal/dispute freezes; out-of-order
  negative-event capture holds and private review queue. Resolved disputes do not auto-release.
  Unmapped negative events enter the private review queue; unsupported event types are
  rejected for separate provider-side review. No automated cash/refund workflow.
- Fixed PayPal sandbox hosts, TLS verification, no redirects and idempotency keys. Webhook route
  has its own default-off qualification flag. Protected sandbox Basic Auth is not relaxed.

Real WordPress testing found that its generic `object_id` field format coerced audit UUIDs to
integers. Explicit SQL insert/update formats now preserve UUID links. The original affected
synthetic fixture was retained privately; HTTP/browser regressions verify populated audit
history and UI correction selection. No owner records were involved.

## Tests executed

- Synthetic domain: 52 assertions. Provider/service: **264 cumulative** (includes domain 52).
  Schema inspection: **62 cumulative**. Anonymous session/origin: **75 cumulative**.
  Service suite passed PHP 8.1 and 8.3 WASM engines and native PHP 8.4.24. Provider transport
  and fake-store tests do not establish actual PayPal or database behavior.
- **Real HTTPS/WordPress/MariaDB/official MFA** test: **48 assertions** in the final extended run, including lost-create merchant-order mismatch,
  linking, exact replay and subsequent issuance. Tests exercise InnoDB audit failure
  rollback, two distinct PHP workers racing the last balance (one winner), no overspend,
  exact replay, revoked grant/expired MFA denial, wrong-session privacy and refund freeze.
- **32 real browser assertions**, Chromium headless shell 150.0.7871.181: mobile checkout,
  hosted mock approval/receipt, real official TOTP challenge, technical-admin denial, staff
  UI debit/correction, audit UUID regression, zero billing-widget WCAG A/AA axe violations,
  refund freeze and no JavaScript errors. Mobile/private screenshots inspected.
- Full repository PHPCS: **118 files**, zero errors/warnings; PHP syntax **123 plugin files**.
  Existing deployment/backup guard suite **22 scenarios**; Plesk guard suite **6 mocked
  identity scenarios**. Locked coding standards/dependency pins unchanged.

Native fixture: WordPress **7.1.4**, PHP **8.4.24**, MariaDB **11.8.6**. Official Two-Factor
**0.17.0**, verified ZIP and all 21 files; actual password/TOTP login and CSF session proof.
PayPal network/signature responses are **explicit mocks**, not actual merchant callbacks.
Only synthetic local users receive fixture grants; no actual account or MFA is modified.

## Supplemental functional recovery executed

Fresh synthetic database dump and file archive restored to a separate initially empty database
and private directory. Exact **3,823-file** hash comparison and **18-table** DDL/row comparison
passed before explicit isolation overlays. Restored WordPress bootstrapped, decrypted existing
certificate ciphertexts using the restored key and retained official MFA enrollment. HTTPS
public closed-page rendering and real restored password/TOTP login passed; checkout route was
absent and outbound HTTP/mail blocks were exercised. Financial tables were unchanged by the
initial functional bootstrap. The restore contains two synthetic certificates from the prior
42-assertion HTTP and 32-assertion browser runs; later test purchases are not in that snapshot.

Private evidence `/tmp/csf-billing-fixture/recovery-evidence.json`:
- File archive SHA256: `5ffabe2aaf438dce70584afa1ccaf920978d37a1b0c4d981f1a49bf83ad08de9`.
- SQL SHA256: `882b0ccbcdb309140efaeca033c35f0407fc95a19e997fe7d55ff6d9f78a552f`.
- Canonical exact DB snapshot SHA256: `371df9a1630c43564e4be3e8b7f155bd4a484eae4c74c91524b28487e662c137`.

This is **supplemental disposable native recovery**, not approved exact-pin target-bound
recovery qualification. Original protected recovery pins and schema allowlists are unchanged;
they must reject the six new billing tables until separately reviewed and qualified. Keys,
SQL dumps, session material and restored config remain private, outside Git.

## Reproduction and remaining gates

[Test fixture instructions](../../tests/billing/README.md) describe the isolated runner and
runtime artifacts. Debian binaries were extracted unprivileged from signed official package
metadata; no system install or farm access. The exact pinned Playwright Chromium download
returned invalid/empty archives. System Chromium could not create its required AF_UNIX
singleton socket. Matching official Debian headless-shell/common packages ran successfully;
this alternative is supplemental and does not waive the pinned browser matrix.

Still required:
1. The native matrix, schema rejection/failure probes, same-capture race and injected failed
   COMMIT rollback are now completed below. A precisely synchronized grant-revocation race
   and real connection-loss-at-COMMIT uncertainty test remain distinct further qualification;
   current tests cover fresh revoked grants and a COMMIT SQL failure before commit.
2. Actual PayPal sandbox Business app/merchant ownership, secure credentials and signed
   lifecycle callbacks through separately approved ingress. Simulator/mock verification is
   insufficient. Never weaken Basic Auth to receive webhooks.
3. Final prices/products and legally reviewed exact Beth terms; quarterly credit/collection
   mechanics, recipient/transfer policy, delivery and retention, actual financial operators.
   All remain provisional; see doc 03 and doc 14. No credentials in chat or Git.
4. Operational recovery for six tables and encryption-key escrow, exact-pin runtime and
   target-bound current backup/isolated functional restore/preflight/separate readback,
   separately approved install and owner acceptance. This local test does not authorize any.
5. Reviewed handling of partial refunds, statutory cash redemption, dispute release and
   manual exception resolution; conservative whole-certificate freeze is the current behavior.
   Session-only receipt access expires after two hours; durable delivery/account retrieval
   awaits the owner privacy/delivery decision.

Required configuration remains external: sandbox flag and local/development environment,
32-byte AES-GCM data key, approved catalog/terms, sandbox client ID/secret/merchant ID and
webhook ID. The webhook qualification flag is independently held. Never enable live sales.

Adapter references: https://developer.paypal.com/api/orders/v2,
https://developer.paypal.com/api/rest/reference/idempotency/,
https://developer.paypal.com/api/rest/webhooks/rest/,
https://github.com/paypal/paypal-rest-api-specifications/blob/main/openapi/notifications_webhooks_v1.json.

## Supported native matrix and payment review follow-up — November 8

Local candidate **0.2.1-dev**, schema2 unchanged. Independent source-path review found and
fixed two state-transition defects: (1) manually recovered already-approved provider orders
could not enter capture because the approval command expected only awaiting_approval;
(2) independently completed payments could remain stuck while local approval was pending.
Both paths now preserve exact provider/merchant/amount binding, reconcile verified completion
without recapture, and cannot reactivate frozen orders. Regression suite passes **300 cumulative
synthetic service assertions on native PHP8.1 and native PHP8.3**. No live adapter was added.

All four native cells passed **51 real HTTP/database/official-MFA assertions plus 19 real SQL
probes each** (counts describe separate runs, not unique distinct scenarios):

| Native PHP | MariaDB 10.6.23 | MySQL 8.0.46 |
|---|---|---|
| 8.1.2-1ubuntu2.28 | 51 HTTP + 19 SQL, pass | 51 HTTP + 19 SQL, pass |
| 8.3.6-0ubuntu0.24.04.13 | 51 HTTP + 19 SQL, pass | 51 HTTP + 19 SQL, pass |

These are Ubuntu-maintained package versions with backported fixes, extracted from verified
signed official jammy/noble repositories; not WASM or substituted PHP8.4/MariaDB11.8. No system
package installation, elevated service, farm account or protected CI secret changes. MySQL's
default secure-file directory was unavailable; the disposable process uses secure-file-priv=NULL
(disables server file import/export), not an unrestricted directory. Servers listened only on
loopback13379; stopped cleanly after each test.

Real two-worker tests now include last-balance redemption **and same-capture issuance**; one
certificate was returned to both issuance workers. Recovered already-approved orders also
pass real HTTP capture. SQL probes create only unique temporary billing tables: fresh DDL,
reapply rejection, missing unique index/table, non-InnoDB audit, wrong schema version, injected
failed COMMIT with audit/receipt rollback, exact replay, changed-payload rejection and UUID
preservation. Temporary probe tables are cleaned in finally; prior fixture evidence remains.
The probe counter's initial WP-CLI scope error was corrected and all four probe cells rerun
with the expected 19 count. Browser 32 and supplemental restore evidence above are earlier
0.2.0 evidence, not reruns on 0.2.1; this increment changes backend state handling only.

[Native matrix evidence](../../tests/billing/native-matrix-evidence.json) records exact package
checksums and final HTTP/SQL log hashes. [Runner](../../tests/billing/native-matrix.py) refuses
to import over a populated fixture database. Changed plugin PHPCS passes 12 files, supported
native PHP syntax checks pass, and git diff whitespace checks pass.

### Exact-pin recovery prerequisite outcome

The unchanged `tests/recovery/prepare-pinned-python.sh` succeeded from the official Debian
source: all three package hashes and interpreter SHA256
`889c603f0d17cb54060951bcf4c4f9b8c9ebd9e52b392c70209bbb9755d797d9` match. Interpreter available
at `/tmp/csf-gift-pinned-python/root/usr/bin/python3.13`; set
`CSF_RECOVERY_PYTHON_ROOT=/tmp/csf-gift-pinned-python/root/usr`. The verified interpreter also starts successfully as Python 3.13.5 and imports hashlib/json.
No runtime uploaded, rebuilt
with different flags, secret provisioned, or pin substituted.

Executing unchanged `tests/recovery/restore-local.sh` with that root stops at line11:
`docker: command not found`; `/var/run/docker.sock` is also absent. No alternate connected
disposable execution environment is provisioned. Required runner: isolated Linux Docker
host, exact MariaDB image ID `sha256:d29a34d05b1de1afa84c88bdad35cbba6dc1d1135ad2a95a8ceb56a710819e82`,
WordPress image ID `sha256:21ed73c19da1a0208130861a74838f3ee181ae7d77c9bdb5ff0cbec559f9a94c`,
verified Python above, original privileged **disposable container** isolation prerequisites
(SYS_ADMIN/NET_ADMIN, seccomp/AppArmor allowances as already specified), sufficient 10 GiB
memory and tmpfs space, and UID/GID mapping supporting the unchanged root/database/web-user
separation (fixture mysql UID/GID 997). Do not relax host/farm security to make this run.

Existing `.github/workflows/scheduling-disposable.yml` demonstrates an Ubuntu24.04 Docker
runner path and downloads the same Python pins without secrets. It runs published PR source;
the unpublished gift branch cannot be tested there without separately authorized publication.
Even success of its existing recovery test would cover the original scheduling schema only.
Billing requires separately reviewed schema-aware recovery qualification for all six new
tables and retained encryption-key material. Existing protected allowlists/pins remain intact
and correctly reject the gift schema. No farm recovery or deployment was attempted.

The secure PayPal account/credential/merchant verification path is now specified in
[doc14](../14-client-readiness-onboarding-payments.md#paypal-sandbox-credential-and-merchant-verification-handoff-prepared-not-executed).
It requires an owner-verified seller/app and approved isolated HTTPS listener, followed by
secure stored-value/secret-manager provisioning. No secrets are requested in chat. All catalog,
terms, real sales, access grants, publication and deployment holds remain.

## Billing-specific recovery candidate — independent review pending

[Proposed extension](../pilot/billing-recovery-proposal.md) and test-only invariant/key/replay
checks are prepared separately from the qualified recovery implementation. **No production
plugin, qualified recovery source, schema allowlist, runtime pin or active CI workflow changed
in this increment.** The proposed full target is 33 tables (existing 27 + billing 6); the executed
fixture is 18 tables (WordPress 12 + billing 6), not full combined-target qualification.

The saved `tests/billing/recovery-run.py` executed on native PHP8.3/MariaDB10.6, with a fresh
unique datadir, private hash-bound synthetic input, explicit empty-destination checks and
source preservation. It verifies exact files/DDL/rows, schema2's six-table logical/physical
invariants, frozen credit, original purchase/terms/balances, debit/correction history,
idempotency receipts and permanent tombstones. Private per-order reconciliation plans permit
no external writes, claim resets, recapture, reissuance or automatic release.

Validation: **24 recovery-contract assertions**, **34 restored-key/issuance-replay/closed-payment
assertions**, standalone missing-key rejection on native PHP8.1 and8.3, and actual restored
HTTPS rendering/password/official-TOTP login passed. Tampered ciphertext and wrong keys fail;
all 8 existing certificate plaintexts match their stored hashes without printing them. Issuance
receipt replay never enters the mutation callback. Checkout routes are absent; HTTP/mail are
blocked; source data and restored billing data remain exactly equal to their respective
prequalification snapshots. Expanded prepared/awaiting/create-uncertain/capture-uncertain
fixtures remain unresolved after restore. Fresh provider proof remains required later.

An initial integrated attempt caught a missing `esc_html` stub in the standalone missing-key
harness. It was corrected without product or gate changes, and the integrated run was repeated
on a new datadir. Prior captures/restores and failure logs were preserved. No artifact was
accepted by waiving the failing check.

The candidate fixture retains keys only in private 0600 config/archive custody; **encrypted
escrow/off-server custody is not qualified**. Missing/wrong-key tests do not constitute a
rotation design. Original pins and schema contract remain byte-for-byte unchanged; a test
confirms the qualified 27-table contract still rejects proposed 33-table billing sources.

Read-only GitHub checks verified PR26 remains draft/unmerged at 6883b290 and run 42472063775
completed successfully. Its Ubuntu24.04 Docker/official pinned-Python mechanism remains a
viable **future authorized publication** path, not a test of this unpublished gift branch. No
workflow dispatch, branch publication, artifact/runtime upload or protected secret change was
attempted. Docker remains absent locally. Combined 33-table exact-pin isolation and failure
qualification, independent review, key custody, target gates and owner decisions remain held.

**PayPal is not live-ready.** No genuine sandbox merchant/signature/lifecycle verification has
been performed. Provider transport remains mocked; terms/prices provisional; real sales and
all existing payment/access/deployment holds remain. Public result hashes/counts are recorded
in `tests/billing/recovery-evidence.json`; private SQL/config/keys/plans remain outside Git.


## Independent review follow-up: physical schema and CI provenance

The initial recovery candidate checked physical column names but not all declarations. A
`redeemed` column changed from unsigned integer to varchar could pass when the captured and
restored copies were identically damaged. The revised schema-2 contract independently checks
complete ordered declarations (type/size/signedness/nullability/default/charset/collation/extra),
primary/unique/secondary indexes and table options for all six tables. Only known integer
display widths and the audit AUTO_INCREMENT sequence value are normalized. Unknown layouts
fail closed; valid capture/restore equality is still required separately. The original qualified
scheduling contract and deployment/capture scripts remain unchanged.

273 adversarial assertions on a real synthetic snapshot reject damaged capture and identical
damaged capture/restore pairs; the existing 24 recovery assertions also pass locally. Combined
CI adds an actual ALTER-to-varchar probe, checks all seven stored payment states, two debit
audit trails and one correction with tombstones, and rejects missing/wrong synthetic keys.
These ledger fixtures are explicitly seeded through Store transactions, not evidence of actual
provider payment or an authenticated redemption service flow. The separate prior native tests
cover service behavior; recovery verifies preserved records and invariants.

Correction: CI run42514457937 used GitHub's PR merge commit, not literal branch head. The revised
workflow explicitly checks out `github.event.pull_request.head.sha` and records commit/tree in
each job. Final run and exact tested identifiers are recorded in draft PR27 after completion.

Remaining adoption gaps are unchanged: the combined runner is separate from the qualified
deployment/capture contract; full combined damaged-capture, timeout, cleanup, namespace and
no-overwrite equivalence and consistent capture coordination require independent review and
qualification. Successful synthetic missing/wrong-key tests do not establish secure escrow,
off-server custody or farm key/config/target bindings. No production adoption or installation
is authorized by these tests, and no real PayPal verification is claimed.


## Monetary ledger increment — schema3 / 0.3.0-dev

This supersedes the earlier ride-only implementation gap, without approving sales or terms.
Certificates now retain an authoritative value document: original paid USD cents, immutable
allocation policy/version, per-ride cent allocations, consumed ride ordinals, recorded cash
paid and one reserved/uncertain cash request. Schema3 adds required `value_json` to certificates;
six billing tables remain. Original source order price/terms are unchanged.

Two configurable proposals are implemented, neither owner/legal-approved by default:
`proportional_floor_v1` assigns ride i `floor(P*(i+1)/N)-floor(P*i/N)` cents; `front_remainder_v1`
assigns `floor(P/N)` plus one cent to the first `P mod N` rides. Sum is exactly paid value with
no floating point. Correcting a debit restores its original ride slots/cents, not a recomputed
average. Policies must be confirmed via `CSF_BILLING_VALUATION_JSON` with method/version/approved;
purchase preparation fails until approved. These algorithm names are configuration, not terms.

Private managers with current direct capability, real MFA and REST nonce may reserve the entire
remaining value, mark the payout uncertain, record a full cash payout already made against a
private evidence UUID, or release a reservation after independently confirming no payout.
No workflow calls PayPal, transfers funds or creates a refund. Reservation identity and paid
payout-evidence UUID have permanent tombstones. Command replay, singleton InnoDB lock, expected
revision and transactional audit prevent concurrent ride/cash double use. Unknown payout remains
reserved. A later provider freeze is not cleared by recording/canceling cash. Evidence must be
real independently reviewed documentation; an operator attestation is not external payment proof.

Remaining value is paid cents minus consumed slot cents minus recorded cash; reserved value is
shown separately and remains owned value. Pending cash blocks ride/correction commands; cash
exhaustion prevents spending still-listed rides. An exact mistaken-ride correction can restore
value after a completed cash payout. The private UI shows value alongside rides and flags a
post-ride positive balance below $5. It supports full remaining cash requests at any amount;
it does not purport to decide legal entitlement or automate payment. Partial cash payouts and
correction of a falsely attested payout are intentionally blocked pending a reviewed workflow.

Private management has a separate default-off `CSF_BILLING_MANAGEMENT_ENABLED` gate; this never
enables public checkout. No farm constant/grant was set. Existing schema2 ledgers fail closed;
there is no automatic migration or speculative monetary backfill. An independently reviewed
legacy reconciliation/migration is required for existing records. This project has no installed
farm billing schema; previous local captures remain untouched as historical evidence.

Local value suite passes 45 assertions on PHP8.1/8.3 plus existing 300 cumulative service checks;
plugin PHPCS and JS syntax pass. New real-DB/MFA two-worker and strict monetary recovery probes
run in draft CI; final result/commit is recorded in PR27. The combined recovery seed contains
uncertain cash and recorded payout histories, all payment states and exact correction evidence.
Do not substitute historical native HTTP/browser results for new schema3 acceptance.


Schema3 validation follow-up: fresh scratch-prefix SQL probes passed on all four native cells
(PHP8.1/8.3 × MariaDB10.6.23/MySQL8.0.46), preserving prior fixture data. Supplemental Chromium
UI checks passed 14 assertions with mocked API responses: exact cents, required attestation,
reservation identity, uncertain outcome and recorded payout presentation. This is UI evidence,
not backend/MFA or provider verification. Run7 passed the first schema3 real-DB/official-MFA
workflow and two-worker race suite, monetary recovery and unchanged qualified recovery. The
subsequent review hardens duplicate cash audit identities and type checks; final exact-head run
is recorded in PR27. No actual payout, external provider call or farm access occurred.


Management-route review correction: registration previously returned early when sandbox checkout
was disabled, even when private management was explicitly enabled. Public checkout and private
route registration now use separate gates; the webhook still requires both sandbox checkout
and its independent qualification flag. Both switches default off; no farm configuration changed.
`management-rest.py` adds actual WordPress REST tests over a locally trusted loopback TLS
terminator, real official TOTP and fresh scratch financial tables. It exercises list/detail/cash
with sandbox=false/management=true, public/webhook absence even for an authenticated manager,
both-gates-off absence, anonymous/bad-nonce/wrong-origin denial, and the closed gift page. TLS
keys, test configuration and scratch tables are cleaned up; restored financial rows are compared
again. This is a disposable test terminator, not a change to production proxy trust or MFA.
Final exact-head outcome is recorded in draft PR27; implementation alone is not a passing test.

## November 9 local printable fulfillment increment

Local synthetic validation: PHP8.4.27 extracted from configured official Debian signed snapshot
packages, not substituted for required PHP8.1/8.3 or exact recovery pins. Base service 300 cumulative
assertions; 69 additional fulfillment assertions (including provider transport checks), monetary
45 additional, schema62 cumulative, session75 cumulative, missing-key fail-closed passed. Deployment
guards22 and mocked Plesk identity6 passed. Chromium156.0.8078.79: 10 additional assertions for
buyer design/email submission, sandboxed real-PHP certificate rendering, pending signature, mobile
width and generated PDF bytes; checkout API fully mocked, no real WordPress/MFA/provider proof.

New issuance queues two held intents atomically. Tests cover replay, encrypted contact/signature,
invalid inputs, payment-status rendering denial, escaping, audit-failure rollback, sink replay,
minimal administration bundle and no plaintext code/contact in sink storage. Transparent synthetic
PNG fixture is not Beth artwork. At this historical increment, no mail transport, customer booking reservation, number rotation
or stored PDF delivery was implemented; the subsequent increment below supersedes that status. PHP8.1/8.3 CI, real database concurrency, complete WordPress
REST/MFA and expanded recovery invariants must be rerun/extended for this increment. Previous
passing head evidence does not qualify these changes.

All 14 billing PHP files pass the unchanged WordPress/PHPCompatibility coding rules. Full
plugin PHP syntax lint, billing JavaScript syntax and git diff whitespace checks pass. Locked
Composer dependencies were installed without changing composer.lock. Browser test caught and
fixed the theme selector's accessible-name issue. These are local results, not a rerun of the
previous exact-head CI evidence.

## Continued PDF/dispatch/rotation/reservation candidate (local results)

Added 21 synthetic PDF/dispatch/rotation assertions and 16 simulated-transaction booking
participant assertions. Existing 300 service, 69 fulfillment, monetary45, browser10 and cashUI14
regressions retained. An independent Poppler parser recognizes the generated PDF as one page,
PDF1.4, without JavaScript/forms; a raster rendering was inspected. This is not final visual
owner acceptance. PHP8.1/8.3 CI and new combined recovery/real-SQL probes are pending at commit.

New real SQL fixture (not yet claimed passing here) uses scratch tables and genuine existing
restored MFA/nonce, exercising persisted PDF replay, rotation replay, joined reservation rollback,
cash exclusion and two-worker last-slot races. It sets an internal calendar transaction marker
only within the guarded synthetic probe; it does not test the full scheduler availability or
private/public UI path and does not bypass MFA. Public booking stays held.


Recovery follow-up: draft head3d263acc/treef91df674 packages the fulfillment modules in the
isolated image and adds a third issued certificate with a rotated number and a reservation
linked to an actual synthetic scheduler row. Candidate qualification binds issuance/rotation
history, retired codes, reservation transition history and scheduler detail/state; 28 damaged
fulfillment capture/pair checks are scheduled. CI run12 on00a284c passed both PHP versions and
unchanged qualified recovery but failed combined startup because a module was omitted from
its image; no combined pass is claimed. Run13 is pending. Exact final outcomes belong in PR27.

Local follow-up: dispatch21 and simulated-booking16 rerun passed, as did fulfillment browser10
and cash UI14. Poppler visual inspection found proportional font substitution spacing defects;
standard Courier fixed the inspected landscape PDF. Stored artifacts remain encrypted in the
ledger; downloaded PDF bytes are ordinary bearer documents and must be handled privately.
Signature raster rendering still requires GD and authorized artwork; actual Beth artwork and
final designs are not qualified. No real sending, financial movement, farm access or deployment.

Additional PDF fail-closed tests bring dispatch/rotation assertions to24: unsupported non-ASCII
text, layout overflow, and authorized synthetic raster handling. Both GD-present and GD-absent
paths passed on temporary official PHP8.4.27; the old synthetic PNG was corrupt and replaced
with a generated transparent pixel. This is not testing Beth artwork or qualifying final design.

Run13 on3d263acc passed both PHP jobs and unchanged recovery. Combined recovery passed all
33-table restore, schema279/monetary20/fulfillment28 damaged capture checks, key/tamper,
source isolation and genuine restored MFA, then failed the real number-rotation service probe.
The real Store lookup allowlist omitted number_hash (the mock did not); this also blocked
reservations. The narrow indexed field was added. The same real SQL probes must pass on the
next exact head; no combined success is claimed for run13.
