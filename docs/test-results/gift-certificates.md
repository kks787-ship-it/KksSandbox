# Gift-certificate development evidence — November 8, 2026

Status: **sandbox-only local candidate, not published, deployed or ready for live sales**.
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
