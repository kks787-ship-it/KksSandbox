# Proposed billing recovery extension — independent review required

**Not adopted, not a deployment gate, not permission to capture or restore farm data.**
The existing qualified recovery implementation, schema contract, image/interpreter pins,
approval bindings and deployment protocol remain unchanged. Gift checkout is **not live-ready**:
provider transport/signature tests remain mocks, with no verified merchant credentials or
approved public test ingress. No real sales, delivery, grants or account changes.

## Exact scope proposed

Keep the existing 12 native WordPress and 15 scheduling tables exactly as qualified. A future
separately reviewed `billing_v2` phase would additionally require exactly:

| Table | Required retained evidence |
|---|---|
| `csf_billing_guard` | Singleton ID1, schema_version2 |
| `csf_billing_orders` | Immutable product/terms/price, session digest, local/provider IDs, state, revision |
| `csf_billing_certificates` | Unique number digest and ciphertext, original purchase binding, purchased/redeemed counts, hold state |
| `csf_billing_commands` | Exact payload hashes/results, issuance receipts, ride/correction tombstones, capture holds and provider-event receipts |
| `csf_billing_audit` | Original ordered issuance/debit/correction/provider history and actors |
| `csf_billing_throttle` | Exact captured counters/expiry; no normalization during proof of restoration |

All six must be InnoDB with exact physical columns, primary keys and unique order/capture/
certificate bindings. Total full target would be **33 tables**, not a wildcard extension.
Unknown tables, partial billing installation, wrong schema marker, missing uniqueness or
inconsistent history remain review-required; never silently skip, repair, reset or accept them.
The source phase with no billing remains the original 27-table scheduling phase. The current
qualified contract deliberately rejects all six additions; a regression preserves that behavior.

Candidate verifier is isolated in [tests/billing/recovery_contract.py](../../tests/billing/recovery_contract.py).
It is an additional billing invariant checker, **not** a replacement for the existing outer
schema/target/physical metadata/custody checks. It ignores nonbilling rows only because the
outer accepted contract must own them; that behavior must not become a general table exemption.

## Key custody and restore proof

Certificate numbers use AES-256-GCM with a server-provisioned 32-byte data key. Recover the
**original key**, not a regenerated or rotated one. Keep secret material outside Git/public
artifacts/browser/command arguments, and separate from PayPal API credentials. No plaintext
certificate numbers or key bytes belong in a recovery report.

Proposed qualification must establish private key custody, full encrypted-backup custody,
restored config/key binding, decryption of every restored number and equality with its stored
SHA256 number digest. Missing key, wrong key and altered authenticated ciphertext must fail
closed. Current plugin has no multi-key rotation protocol: rotation remains held rather than
inventing a new key identifier or rewriting numbers. A future key-envelope/escrow format needs
its own reviewed version and authorization; it is not silently added here.

The disposable rehearsal retains the key in a restricted 0600 config inside a private 0700
archive directory. **This is not encrypted key escrow or off-server custody qualification.**
No production secret, actual merchant credential or farm backup is used in these tests.

## Reconciliation after restore

Exact DDL/row/file equality is established before isolation overlays. Then all checkout and
webhook routes remain disabled; outbound HTTP and email are blocked. Verify ledger invariants
without making any provider call: purchased rides match original product; redeemed rides equal
ordered debits minus original one-time corrections; provider/certificate bindings remain unique;
receipt replay never reissues; debit/correction tombstones survive; frozen records cannot become
active or lose their negative-payment hold. Restore must not reset claim ages or audit history.

| Restored state | Review disposition; no payment mutation authorized |
|---|---|
| prepared | Retain unpaid purchase; do not create provider order |
| create_uncertain | Merchant lookup of original request/order; never blindly recreate |
| awaiting_approval / approved | Verify current provider state; do not capture during recovery |
| capture_uncertain | Independently verify original order and capture; never recapture |
| issued | Preserve existing certificate/receipt; verify binding, never reissue |
| frozen | Preserve hold and audit; separate reviewed decision required for any release |

The private per-order plan contains only local reference, state and next review action; every
entry explicitly denies external writes. Public evidence contains counts only. Event and
idempotency receipts survive byte-for-byte. Any later real provider reconciliation is a separate
authorized workflow using fresh verified sandbox/live environment bindings; neither archived
provider responses nor restored web sessions are fresh payment evidence. No live reconciliation
is qualified by the mock transport.

## Candidate tests and limits

[Saved native runner](../../tests/billing/recovery-run.py) creates a fresh unique datadir, imports
only a hash-bound synthetic capture, seeds prepared/awaiting/create-uncertain/capture-uncertain
states via the service and mock provider, captures/restores to a new empty database, checks
exact 18-table/3,823-file equality, and verifies the six-table invariants, key, immutable replay
receipts, closed payment paths and optional real browser/official-MFA login. Source data and
restored billing data are compared again after qualification. The runner takes an exclusive fixture lock and verifies its own server PID, exact datadir and
empty source database before importing. Previous databases and evidence
are retained; a populated restore destination is never reused or overwritten.

This fixture has **12 WordPress + 6 billing = 18 tables**, not the combined 33-table target.
It uses native PHP8.3, MariaDB10.6 and the available supplemental headless browser. The original
qualified container/isolation runtime is not replaced. Before adoption, independently review:

1. Proposed combined phase, physical schema and complete 33-table capture/restore, including
   partial/damaged billing and original scheduling state checks without weakening either.
2. Key-envelope/escrow custody, protected secret-config inventory and fresh target/file/source
   bindings; no secret material in public CI artifacts. Prove missing/wrong key rejection in
   the qualified isolated runtime and cleanup of temporary secret artifacts.
3. Existing manifest, traversal, symlink, timeout, cleanup, uid/gid isolation, newer owner/auth
   preservation, no-overwrite and failure tests applied to the combined fixture.
4. Exact original Docker image/interpreter pins, isolated network/mount/identity boundaries,
   fresh backup gates, reviewed payload and independent post-install readback.
5. Real PayPal sandbox merchant/signature/lifecycle verification and owner decisions separately;
   none is inferred from recovery correctness.

The standalone fixture snapshots are used with stopped test clients and cron disabled. They
are not a substitute for the existing qualified consistent-snapshot capture. Rootless loopback
process isolation does not prove the production namespace/security controls. No target-bound
backup or recovery claim is made.

## Docker-capable CI route — historical preparation

Read-only connector inspection confirmed [PR26](https://github.com/kks787-ship-it/KksSandbox/pull/26)
remains draft/unmerged at `6883b29034b18581e81bf8cadaf52aef9c01a774`. Its published
[disposable workflow](https://github.com/kks787-ship-it/KksSandbox/blob/codex/digital-ranch-continuation/.github/workflows/scheduling-disposable.yml)
uses Ubuntu24.04, Docker, an internal/no-external-network fixture and official hash-bound Python
preparation. Read-only workflow-run lookup confirmed successful [run22](https://github.com/kks787-ship-it/KksSandbox/actions/runs/42472063775)
qualified scheduling, not billing. No new workflow was triggered and no branch published.

A proposed gift recovery job should use that same reviewed mechanism after explicit publication
approval: checkout the exact reviewed gift SHA without persisted Git credentials, download only
official pinned dependencies, verify the original interpreter and image identities, build the
synthetic combined fixture in scratch, and run without farm mounts or protected secrets. Keep
`contents: read`, bounded timeout and redacted pass/fail output; do not upload SQL, configs,
cookies, certificate numbers, private snapshots or runtime bundles. The original privileged
container prerequisites belong only on the disposable runner and do not authorize farm changes.

The current workspace has neither Docker executable nor `/var/run/docker.sock`; unchanged
restore-local.sh stops at its first Docker check. Exact Python3.13.5 was acquired from the
already-qualified Debian packages and matched SHA256
`889c603f0d17cb54060951bcf4c4f9b8c9ebd9e52b392c70209bbb9755d797d9`.
No new runtime upload, protected CI secret changes or pin substitutions are needed or proposed.
The additional combined-schema payload/job remains a review item, not a claimed executed gate.

## Authorized draft publication and candidate rehearsal

Kris subsequently approved publishing the gift branch as a draft PR solely for isolated tests.
[PR27](https://github.com/kks787-ship-it/KksSandbox/pull/27) now contains
`.github/workflows/billing-disposable.yml`: PHP8.1/8.3 contracts and deployment guards, the
**unchanged** qualified recovery suite, then a separate combined-schema candidate rehearsal.
It uses the original image and interpreter hashes, read-only repository permission, no persisted
checkout credentials, no protected secrets or farm mounts, and no artifact uploads.

`tests/billing/combined-recovery.sh` builds a new disposable fixture without changing the original
runner, Dockerfile, baseline table contract or deployment scripts. The candidate captures and
restores 33 tables and exact file hashes; verifies six billing tables and encrypted numbers;
rejects each missing billing table; requires restored official password/TOTP authentication and
a closed gift page; and checks scheduling/billing rows and newer source data remain unchanged.
The restored web identity has database credentials scoped only to its restored database.
Provider HTTP and email are blocked; synthetic records do not prove PayPal verification.

The first CI attempt exposed a missing runner ripgrep dependency, which was added rather than
waiving deployment guards. Review also corrected a database-startup retry exception and removed
a direct-render fallback so actual restored page rendering is required. Final exact-head run
results belong in the PR evidence; the existence of this workflow is not evidence of a pass.

Even a successful combined rehearsal remains a **proposed** billing recovery extension: it does
not adopt a 33-table production contract, qualify protected key escrow/custody, or authorize a
farm backup, migration or deployment. Original qualified failure gates remain separate. Combined
source-capture coordination, damaged-state/no-overwrite and namespace-boundary equivalence need
independent review before extending the protected recovery contract.


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
