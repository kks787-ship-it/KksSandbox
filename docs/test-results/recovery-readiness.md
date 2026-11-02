# Gate B recovery / identity readiness — local preparation

**November 2, 2026, America/Los_Angeles. Documentation/tests/proposed commands only.**
[Assignment](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6585062869).
Starting/corrected identity head17fd654 is preserved; implementation remains
`2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`, Scheduling0.4.0, adapter0.2.0, official
Two-Factor0.17.0, schema3/15 tables and unchanged45-file candidate. No implementation defect
requiring a new application pin was identified by this preparation.

## Final execution review of367294c — November 2, 2026

[Assignment](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6586718406).
**NO-GO for capture until bindings and the composed payload are resolved.** No new broad
redesign or application changes: the existing [R-Backup decision card](../pilot/r-backup-approval.md#final-execution-review--november-2-2026)
now lists the minimum owner choices, proposed inspection call, private exposure and stop rules.

Concrete findings: fragments lacked an enclosing30minute limit; only the dump had900seconds.
Whole-job timeout/kill grace is now specified. Native client/dump `--defaults-extra-file` could
inherit unreviewed host options; proposed commands now use `--defaults-file` first. These are
local text corrections, not proof of installed-client compatibility. Archive validation,
metadata/receipt assembly, protected-config path/copy bodies, drift review and bounded overhead
must still be assembled/tested in the exact job; the catalogue did not implement them. Literal
config regex matching alone cannot prove agreement with executable PHP (comments/conditionals).
Archive traversal/links/extended names/prefix collisions require a reviewed header validator;
no recovery extraction is authorized. Disk estimates alone do not bound config/workspace overhead.

Safe proposed first choice is local-only custody. Namespace/daemon startup belongs to restore,
not capture; off-server recipient/transfer/decrypt belongs to its own custody decision. No new
WP identities, Master, MFA or real account enumeration is needed to authorize infrastructure
capture. No actual server dependency, path, free space or client behavior was verified here.

Rerun results:25 recovery synthetic units +14 sandbox-preflight units +22 development guards
+6 mocked Plesk checks = **67 passed**. Ten Bash fragments and four embedded Python fragments
parsed successfully, none executed. No new runtime regression result is claimed for document
changes. Existing application tests were not rerun; the implementation/source pin2fd02a2,
installation scripts and historical evidence are unchanged. Five documentation files changed.

Revised command catalogue SHA256:
`be741cbef608691214f57e32efa166bc99f079b7289f56c976dd299c0a39bc5e`.
The earlier `aa0a4e…` digest below identifies the367294c proposal only and is superseded for
future command review. Generator digest remains unchanged. No Gateway/server call, staging,
backup, restore, production, account, MFA, plugin, inquiry or emergency action occurred.

Stop: resolve the minimum private bindings and exact prerequisite read scope, complete/hash
and review the bounded payload, then request authorization for **one exact R-Backup**. No
placeholder approval and no R-Restore queue: its new manifest does not yet exist.

## Delivered for owner review

- **[R-Backup packet](../pilot/r-backup-approval.md):** one bounded exact-sandbox capture,
  private DB/files/config/salts/protected-auth scope, source/disk/engine/credential guards,
  consistent dump plus separate diagnostic observations, fixed artifact manifest and explicit
  encrypted custody choices. No blanket restore/security/software permission.
- **[R-Restore packet](../pilot/r-restore-approval.md):** separately approved exact new bundle/
  manifest, private network/mount/PID isolation, dedicated DB/socket/scoped import identity,
  artifact-derived baseline before rewrites, explicit clone-only login choice and stopped-service
  evidence. No live import, actual account/password/factor operation or Plesk clone provision.
- **[Canonical runbook](../05-backup-rollback-deploy.md#recovery-and-identity-preparation--separate-decisions-no-execution):**
  exact proposed operations, consistency/receipt/abort rules, drift protocol and recoverable
  Toolkit/MFA matrix. The [technical appendix](../pilot/recovery-command-catalogue.md) specifies
  guarded shell/SQL/encryption/isolation operations and private input bindings. It is not a
  tested automatic backup program; run-specific inputs/payload digests must be reviewed before use.
- **[Owner decision worksheet](../pilot/gate-b-owner-review.md#private-owner-choices-to-finalize-before-the-applicable-gate):**
  separate technical admin, HTTP gate, Kris business and Beth staff identities; required human
  independence; recovery custodians; MFA app/device; security update/escalation contact; permitted
  enrollment/notifications; Plesk/client dependencies and off-server destination/key custody.
- **[Baseline contract](../pilot/baseline-contract.json) and [pure proposed SELECT generator](../pilot/baseline-select-plan.py.txt):**
  exact byte framing/NULL/key/schema/origin binding; all-table one-connection read-only MVCC;
  per-row added/removed/modified comparison; private manifests; no wildcard transient suppression,
  writer attribution, options restoration, live cron disablement or technical site freeze.

R-Backup/R-Restore use owner-approved infrastructure authority, not a prerequisite new Farm
Master/WordPress MFA identity. Later private identity decisions can proceed in parallel. No
temporary cloned login or an enrolled factor establishes real-person authentication/ownership.

## New local validation

| Check | Result / limits |
| --- | --- |
| Synthetic baseline invariants | 17 unit tests passed: bytes/NULL/type framing, primary keys/order, manifest tamper, source/schema/origin mismatch, exact volatile candidates retained, critical/unknown/auth/audit changes never hidden |
| Pure SELECT-plan safety | 8 unit tests passed: one read-only transaction/twelve-table scope, deterministic catalog ordering, identifier injection, missing/extra/wrong-prefix/non-InnoDB tables, primary-key/column failure and composite ordering |
| Proposed command syntax | 10 Bash fragments parsed with `bash -n`;4 embedded Python bodies and generator parsed; **no command executed** |
| Existing local protections | 22 deployment/backup guards,6 mocked Plesk checks and14 sandbox-preflight units passed |
| Documentation / integrity | Relative links/anchors, JSON, whitespace and all24 custom release hashes validated; app source/deployment scripts/release and historical GateA artifacts unchanged |

These **25 new synthetic tests +42 existing guard/mock/unit checks** are not database,
namespace, encryption, actual backup or Plesk tests. MariaDB SQL/CLI/isolation/private-path/tool
behavior remains unexecuted under this assignment. The prior748/354/88 application suite and
31 protected-PHP syntax checks remain historical evidence and were not rerun.

Proposed command text SHA256:
`aa0a4e0d03ccd0583c1b9ddd65369f216b90d1f639c4edf285158649a9a078f4`.
SELECT generator SHA256:
`509c5acd35e2e165fd1c46a260bc3ec3d117a79b96e92b726cd83dfdd7b13b46`.
Owner execution record must bind those reviewed texts and the published delivery commit (linked
in Issue11), not an automatically moving branch. No private snapshots or credential material
were created or committed; test inputs are synthetic. No farm/Gateway connection was made.

## Remaining blockers and exact next decisions

1. **R-Backup:** choose run/window/authorized infrastructure actor, protected-config path list,
   current-state/size/disk bounds and exact command inputs privately. Last preflight values are
   historical, not new inspection. Scope includes private prerequisite checks, not live repair.
2. **Custody:** choose recipient/fingerprint/destination/transfer identity/key custodian or
   explicitly approve local-only capture with off-server readiness unproved. No recipient,
   bucket, private-key import or transfer was selected by Codex.
3. **R-Restore:** needs the actual successful new bundle/manifest digest, private recovery/run/
   tool/config/router/clone-test bindings and separate approval. These inputs do not exist yet;
   GateA is not a substitute. No feature or security gate is implied by restore success.
4. **Identity/security:** private controllers/UIDs/account details, independence assurance,
   custodians, authenticator, update contact and enrollment/notification decisions still need
   owner choices. Do not infer the technical WP administrator from the HTTP protection login.
5. **Toolkit/MFA:** identify actual essential mechanisms and approve a recoverable private
   rehearsal target. A namespace PHP recovery cannot prove actual Plesk SSO. Essential unknown
   paths are **BLOCKED**, not compatible/incompatible. T-AuthClone/T-Toolkit and M-Enforce remain held.
6. **Later deployment:** account creation, vendor/adapter/enforcement, scheduling files/schema,
   independent ownership ceremony, per-person grants/policy, synthetic records and Beth/Kris
   hands-on acceptance remain separate. No Issues15–17 implementation or real operational release.

The earlier options digest difference still has no proven key/writer; ordinary backend activity
is plausible and no incident is established. New raw per-row observations enable focused review
in a later approved window, not a retroactive claim about that earlier snapshot.

## Changed files and stop boundary

Twelve files: canonical05, owner register03, master tracker12, owner worksheet; two approval
packets; command appendix; baseline JSON contract and proposed generator text; this report;
two synthetic test modules under tests/recovery. No plugin, installation/deployment script,
application schema, live account, recovery artifact or original historical evidence changed.

**Stop here.** Minimal next owner decision is R-Backup only with resolved private inputs/custody
choice; review its actual receipt, then decide R-Restore independently. No farm server inspection,
backup, restore, account/factor/role/config/plugin/database write, inquiry/emergency/public booking
or production action was performed or authorized by this preparation. Gate B remains HELD.
