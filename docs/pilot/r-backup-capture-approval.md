# R-Backup — one local-only capture approval

**November5 local candidate update:** the table contract now supports exact native12 plus
schema-v3 scheduling15 (27 total), with explicit expected phases and review-required partial
states. The old execution bindings/approval below are historical; they do not authorize the
new payload or a live operation. Use the current SHA manifests and the
[qualified rollout sequence](calendar-activation-readiness.md#exact-installation-and-rollback-sequence-after-separate-execution-authorization).
[Local evidence and limits](../test-results/scheduling-recovery.md) distinguish recovery fidelity
from operational acceptance. A new clone login uses its restored TOTP only inside isolation,
restores all original metadata and never regenerates factors or codes.


**Decision: APPROVE / REJECT / HOLD. Currently HELD; no sandbox capture executed.**

| Item | Exact proposed operation |
| --- | --- |
| Target | `https://sandbox.cornerstonefarmomak.com`; root `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; DB `wp_mdf4x`, prefix `TXGEh_`, development,12 native InnoDB tables |
| Actor | Existing owner-authorized Gateway SSH identity on serve1, currently configured root; `privilege=none`, no sudo escalation or account/grant changes |
| Command | Exact [capture-local.command.sh](../../scripts/recovery/capture-local.command.sh); SHA256 `1fc691c2d21ad82694198384e55704255366a8129b1590a6f27828d2e4040fa8`. Verified code executes in memory; no installation/staging |
| Release | [Nine-file manifest](../../scripts/recovery/LOCAL-CAPTURE.sha256); payload SHA256 `df364c03ff706ec098e2190986a7853038d39122db570e32571651f7446c6d0e`; scheduling implementation remains2fd02a2 |
| New destination | **Only** `/var/www/vhosts/cornerstonefarmomak.com/private/csf-gate-b-backup-0ae84798d9b1c7a26a6df8370b3415ae`; exclusive0700 directory,0600 files, existing SSH owner. Existing/dangling/partial destination means STOP; no overwrite or automatic retry |
| Reads | Sandbox wp-config in memory for literal local credentials; all regular sandbox WordPress files including wp-content; current DB; target-specific Plesk/pool config and explicitly referenced sandbox HTTP-auth files. No production/global-config crawl or other DB |
| Stored private data | Consistent SQL dump; WordPress/config/salts; scoped protected configs and HTTP password hashes; private source-path/alias/attribute map, full pre/post diagnostic evidence, fixed16-artifact checksum manifest and confidential receipt. Contains credentials, account hashes and potentially personal data; **plaintext local recovery set**, not encrypted/off-server storage |
| Limits | One attempt,30minutes total:1770s TERM+30s kill grace within approved UTC window. Source<=2GiB/100,000 files and directories; DB estimate<=1GiB/dump<2GiB; protected configs<=64/16MiB; diagnostic response64MiB/250,000 rows. Initial free space>=2GiB+5×(source+DB estimate+protected bytes)+64MiB; streamed writes maintain2GiB reserve |
| Source preservation | No WordPress/config/account/security changes, SQL import/DDL/DML, global/table locks, cron disablement or maintenance freeze. Logical DB dump and file archives are separate observations, not an atomic DB/filesystem snapshot |
| Custody | Existing owner/infrastructure custodian retains the new restricted set pending review and separately approved recovery/deletion. No scheduled cleanup, upload, email, encryption or transfer |

**PASS:** terminal exit0 and `LOCAL_CAPTURE_VERIFIED`, after compressed-stream/archive-byte/header,
source/config-map stability, raw drift,16-artifact manifest and confidential-receipt verification.
Output contains only status, opaque bundle ID and manifest/receipt hashes; private paths/values
stay in the set. Capture PASS is **not functional recoverability or restore acceptance**.

**STOP:** identity/scope/client/schema/engine/space/time/hash/reference changes, unsafe paths or
any unreconciled drift. Drift returns review-required/nonzero; never infer a writer or discard
volatile changes. Failures retain restricted incomplete artifacts; anonymous credential/raw-SQL
descriptors close. Missing receipt/forced termination/nonzero job is not accepted. No live rollback
or automatic second capture. [Validation/limits](../test-results/recovery-readiness.md#server-local-capture-candidate--november-2-2026).

**Authorization requested:** exactly one new sensitive local recovery copy described above.
Codex supplies only non-secret actor/approval references and exact UTC start/end at launch;
stdin authorization marker is `one-local-backup-capture`. Credentials/paths are discovered locally,
not supplied by the owner. Gateway timeout1800s; monitor to terminal status. Afterward stop/report.
**Off-server transfer, isolated R-Restore, MFA/security/account changes and GateB remain HELD.**
