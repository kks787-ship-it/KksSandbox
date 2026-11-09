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


## Qualification work package — synthetic preparation only

| Existing guarantee | Combined candidate evidence | Adoption work still required |
| --- | --- | --- |
| Exact target/approved payload/current backup | No farm contact; pinned synthetic source only | Bind approved root, DB/prefix, source, protected configs, current approval and backup age in reviewed versioned capture/restore contract |
| Stable consistent capture | Stopped fixture clients and cron; exact source before/after; SQL dump single transaction | All-table MVCC canonical snapshot plus before/after schema/file fence under concurrent writes, no global freeze; abort on drift |
| Physical schema and ledger | Strict six-table schema; 273 damage assertions, real ALTER rejection; all seven states, debit/correction/tombstones | Expand original catalog/descriptors through independently reviewed version; never bypass its current rejection of 33 tables |
| Archive integrity and no overwrite | New candidate verifies whole bounded archive/member hashes before destination writes; nine corruption/reuse probes | Bind approved manifest/receipt and complete attributes; full SQL tamper/import-failure, stale evidence and interrupted extraction matrix |
| Isolation | Offline Docker, separate web UID with no supplementary groups, clone-scoped DB user; explicit source DB/private config/archive denial and no external IPv4 route probes | Original per-clone mount/network namespaces, chroot, UID map, privilege/capability and IPv6/egress denial equivalence; process and `/proc` boundary tests |
| Newer data preservation | Source files/rows and later owner note compared after restore | Concurrent newer auth revocations, owner data, source file/mode and operational changes across every injected failure |
| Deadlines and cleanup | Outer container timeout/trap; original qualified suite independently passes failure cleanup | Fault injection in each combined capture/import/login/teardown phase; prove no child processes, credentials, mounts or databases remain; no automatic source restore |
| Key custody | Synthetic correct/missing/wrong/tampered key tests | Approved custodian, encrypted off-server custody, recovery access and target/key binding ceremony below |

The new archive helper is a bounded synthetic candidate, not an authorized bundle format or
replacement for the original preflight. No combined deployment payload is produced and no
production-adoption pass exists. Each remaining row must have executable negative tests and
reviewed evidence before an expanded protected contract can be adopted.

### Proposed key-custody ceremony (not executed)

1. Owner names merchant controller, key custodian and separately accountable recovery operator;
   approves isolated target and encrypted off-server repository. Use an existing reviewed secret
   manager/KMS or approved encrypted backup system; do not invent encryption/escrow code here.
2. Provision one random 32-byte billing number key directly through the chosen secure channel.
   Record an opaque custody object/version reference and target/purpose binding privately. No
   key, certificate number, token or plaintext configuration goes into chat, Git, argv or CI logs.
   Keep it distinct from PayPal credentials, web session secrets and backup encryption keys.
3. Backup captures exact key/config version with database/source bindings in a reviewed encrypted
   envelope. Store decrypt authority separately from backup media; define retention, access audit,
   owner succession and loss response. Permissions alone are not encrypted escrow. Do not rotate
   the number key while certificates exist: no multi-key migration is implemented.
4. Recovery operator obtains the original key via approved secret retrieval into ephemeral private
   storage. Independently verify target/envelope bindings before opening it; deny missing, wrong,
   stale-version or swapped-target envelopes. Then decrypt every restored certificate and check
   its recorded digest without printing values. Keep all provider delivery and checkout off.
5. Independently read back custody audit and successful offline restore, revoke temporary access,
   destroy temporary plaintext and verify cleanup. Record only approved identifiers, digests and
   pass/fail evidence. Repeat with unavailable escrow and denied access to prove fail-closed.

Missing decisions: custody platform/location, named authorized custodians, retention and recovery
access policy. No real key or backup can be provisioned by this preparation; farm access remains
excluded. Financial value/cash-redemption schema changes must also be incorporated into the
future physical and recovery contract before any sale or installation.


## Schema3 monetary extension — candidate only

Schema3 adds required certificate `value_json`, preserving the six-table count. The new physical
contract includes the exact longtext declaration; schema2 is rejected, not silently upgraded.
Recovery independently reconstructs original cent allocation from immutable order valuation,
reconciles consumed ride slots and exact corrections, cash reservations/uncertainty, attested
cash payments, replay receipts and tombstones, and requires nonnegative remaining value. Source
price and policy bind the value document; current catalog prices never revalue old certificates.
The synthetic seed includes one uncertain cash reservation and one recorded payout; no cash moves.
New scratch-table service probes use a real restored password/TOTP session and two PHP workers,
then remove only their scratch tables before rechecking restored ledger preservation.

The original qualified 27-table contract remains unchanged and rejects this 33-table candidate.
This increment is not protected contract adoption, escrow qualification or a migration recipe.
Historical schema2 captures remain retained; any future upgrade needs independent backup/recovery
qualification and legacy audit-to-value reconciliation, never inferred balances or silent defaults.

### Fulfillment candidate follow-up — not recovery-qualified

The November9 increment retains physical schema3 but adds immutable encrypted contact/signature
snapshots in order documents and buyer/admin held intents plus encrypted synthetic sink records
in commands. Existing protected recovery scripts/pins are unchanged. Exact restore comparison
alone is insufficient: extend candidate invariant validation and damaged-capture tests to bind
each intent to its issued certificate/order/audience and verify ciphertext/key recoverability,
snapshot hashes, duplicates, sink records and missing intents. Do not adopt farm recovery or
deploy this increment based on the previous schema3 CI run. Future booking reservations and
number rotations will require a separately reviewed schema/ledger contract and failure matrix.
