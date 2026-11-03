# Gate B recovery / identity readiness — local preparation

**November 2, 2026, America/Los_Angeles. Documentation/tests/proposed commands only.**
[Assignment](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6585062869).
Starting/corrected identity head17fd654 is preserved; implementation remains
`2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`, Scheduling0.4.0, adapter0.2.0, official
Two-Factor0.17.0, schema3/15 tables and unchanged45-file candidate. No implementation defect
requiring a new application pin was identified by this preparation.

## Policy registration alongside capture reporting

Owner approved the Recovery-First Autonomous Development Policy as FYI with an explicit direction
not to switch tasks. It is [registered in the decision log](../DECISIONS.md#november-2-2026--owner-approved-recovery-first-autonomous-development-policy)
and repository/security guidance. Existing supervised read-only authority continues; broader
sandbox write activation remains gated by required isolated functional recovery and recent-backup
protections; owner ratification of the supplied protocol is recorded, with no repeat policy ask. Owner-controlled exceptions are unchanged. No
additional server operation, restore or deployment was performed for policy registration.
The current work remains the completed local-capture record and its next recovery gate.

## Approved local capture completed — sanitized status

The owner explicitly approved one local-only capture of the committed candidate. The pinned
command reached terminal succeeded/exit0 with LOCAL_CAPTURE_VERIFIED/complete. Its built-in
archive/checksum/receipt and consistency guards passed; no retry or later operation was attempted.
[Public completion evidence](local-backup-capture-evidence.json) contains status/source references
only. Exact recovery identifiers and private execution bindings are retained in an owner-private
working record and are deliberately omitted from GitHub. No protected contents were published.

This verifies local capture/integrity, not functional recoverability. The new set has not been
functionally restored or verified off-server. Earlier historical recovery evidence is not
substituted for the current workflow's pending restore verification. No deployment, protected
migration, account/security/MFA change, transfer or restore occurred. Only approved recovery
artifacts were written; the live application and production were not modified by the operation.

The restore worksheet records that its input is now privately available. Next prepare the
isolated functional restore for its required approval; no restore is authorized by capture
success. Do not rerun the consumed fixed-destination capture. Source pin2fd02a2 is preserved.
The previous local tests remain preparation evidence, not a live recovery test rerun.

## Server-local capture candidate — November 2, 2026

[Assignment](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6594531564).
Preparation only: **no Gateway/farm-server call or actual protected backup was made** during this
work. The earlier890e58a inspection PASS remains observation-time evidence, not authorization or
proof that this new capture release has run. Implementation2fd02a2 is preserved.

### Closed implementation gaps

- New capture_local.py uses the inspection's literal server-local credential parser and bounded
  target-specific discovery. No manual credential/path JSON, selectors, WP execution or guessed
  protected path. DB user/password remain anonymous-descriptor/process-local material.
- Discovery can now supply a private canonical source/alias map and generated symlink text/
  target/uid/gid/mtime metadata internally. Public inspection output stays allowlisted. Same-conf
  aliases are canonicalized and checked against link races; unexpected includes/auth paths block.
- Existing B1–B3 capture engine is reused, with two optional local-only hooks: save protected
  source/topology metadata inside target.json, and revalidate discovery before accepting the
  private receipt. Source map/content/reference/alias changes now invalidate the attempt, even
  if old canonical files still exist. Defaults preserve legacy fixture behavior.
- Native dump explicitly selects utf8mb4 in addition to single-transaction/quick/skip-lock/
  no-tablespaces/hex flags. Only the exact DB is positional; no imports/all-database operation.
- A separate reproducible standalone command uses a fixed one-candidate token/destination and
  exact non-secret input marker. CLI cannot change target, paths, token, SQL, executables or
  custody, nor supply credentials. A pre-existing/partial destination blocks before alteration.
  Shared library changes regenerate legacy release hashes, but do not grant any old command
  approval or change the pinned scheduling/security application.

### Final local validation

| Check | Result / limits |
| --- | --- |
| Recovery regression suite | **149 tests passed**, including17 new full synthetic capture/negative/packaging tests; no farm data or actual source paths in fixtures |
| Existing protections | **14** sandbox-preflight units, **22** development guards and **6** mocked Plesk scenarios passed |
| Native MariaDB concurrent snapshot | **PASS:** real local single-transaction dump remains coherent while a committed writer changes both InnoDB tables |
| Native negative control | **PASS:** removing single-transaction exposes a mixed snapshot; the test detects it |
| Native artifact import | **PASS:** importing full table DDL/data after dropping only fixture tables recovers10000+1 rows and utf8mb4 data in the disposable daemon |
| Native baseline SQL | **PASS:** exact twelve-table catalog/READ ONLY plan executes with binary/NULL framing and23 fixture row identities |
| Native critical drift | **PASS:** changing one critical option is retained as review-required with unknown attribution |
| Integrity/privacy/build | Manifest/receipt tampering, incomplete sets, target/engine/schema drift, concurrent source/reference/alias changes, insufficient disk, interruption, leaked output, credentials, fixed destination/no overwrite, shell/Python syntax, doc links, deterministic builds and release hashes checked |

**196 checks total:149 recovery +42 existing +5 native integration assertions.** The native
script uses existing image sha256:d29a34d05b1de1afa84c88bdad35cbba6dc1d1135ad2a95a8ceb56a710819e82
(MariaDB10.6.28), --pull=never, no network/host mounts/ports, read-only image and ephemeral tmpfs
DB/socket. It uses synthetic rows and no password, and removes only its named local fixture.
It is not an authorized farm R-Restore or WordPress/Plesk recovery acceptance.

Initial local harness failures were corrected: wait for the final database process rather than
entrypoint's temporary bootstrap server; preserve buffered dump output rather than mixing readline
with communicate; and keep the dump's original database name during isolated import (routines/
events output may include USE). Candidate stream capture already uses a file descriptor, not
that faulty pipe-reading pattern. A fixture-only final-row delay makes the concurrent interleaving deterministic despite Docker buffering; no WHERE/SLEEP is added to the capture command. The final integration rerun passes all5 assertions and cleanup.
No actual dump is rewritten or imported into an alternate live database.

New tests show confidential target metadata retains canonical paths/link attributes while the
public output excludes source paths, salts/passwords, values and raw diagnostics. On incomplete
or review-required result, no LOCAL_CAPTURE_VERIFIED is emitted. Anonymous credentials/raw SQL
close on interruption; restricted partial artifacts are retained, never accepted automatically.
The core still records all DB drift with attribution unknown and no wildcard/cause suppression.
Dump and diagnostics are independent MVCC instants; file/config comparisons do not make an
atomic DB/filesystem snapshot. Live cron, writes and owner edits are not disabled.

### Exact release and approval boundary

Command SHA256 `1fc691c2d21ad82694198384e55704255366a8129b1590a6f27828d2e4040fa8`;
payload SHA256 `df364c03ff706ec098e2190986a7853038d39122db570e32571651f7446c6d0e`;
[nine-file manifest](../../scripts/recovery/LOCAL-CAPTURE.sha256) SHA256
`b2188f9985b0c8b81a3aa0fa7c03f93148bd766f22bfa2e0c14abc3a716a6ed6`.
Native test script SHA256 `80e7a7b91bd48b1e0350b4b60dcbd1d9e2d3baac0780ffbd5d20ec73ad1d6fbc`;
synthetic result log SHA256 `e1d0379e72e623b9276426a8908b61e13bc58d6ee0b01609595d6076055a06e6`
(local /workspace/work/local-capture-mariadb-snapshot.log, containing PASS/cleanup text only).

[One-page authorization card](../pilot/r-backup-capture-approval.md) states one exact command,
root/DB, existing SSH actor, exclusive destination/permissions, fixed16+2 artifact list, private
contents, limits, custody, acceptance and stop behavior. No source staging is required; public
code is carried in-memory. Codex generates non-secret approval/actor/UTC-window parameters only
after separate authorization. Accepted output is complete/LOCAL_CAPTURE_VERIFIED with opaque
bundle ID and manifest/receipt hashes; it does not declare a functional restore.

Still unproved without actual capture: live dump privileges/flags and completion on the current
farm volume, available resources across the whole job, actual concurrent drift, creation/checksums/
permissions of a real set and recoverability of its contents. The prior read-only PASS and local
native test do not substitute for those. A fresh scan inside the approved capture rechecks all
bindings; source/config changes or schema phase mismatch stop it. Disk/free-space guards include
protected archives and metadata overhead and maintain2GiB reserve during streamed writes.

**Stop for owner authorization of precisely one local-only capture.** Standing diagnostic
permission does not authorize copying the DB/config/salts/auth credentials to new recovery
artifacts. Existing backup sets remain untouched. No off-server encryption/export/transfer,
restore, account/security/MFA change, installation/migration, inquiry/emergency or production
change occurred or is requested. GateB remains held. No protected backup has been executed.

## Supervised diagnostic session — November 2, 2026

Owner explicitly adopted the standing policy, recorded before server work in commit
`e2790d46db6b7212cb50ad0c372482f728b91b6d` and Issue11. Routine bounded read-only repeats/local
engineering are prospectively authorized; recovery/deployment/security/business change gates
remain separate. [Sanitized machine evidence](supervised-diagnostic-evidence.json) records each
reason, read scope, command digest, bound and terminal outcome. No Gateway metadata containing
host/SSH/account identifiers or protected config contents is copied into this report.

**Verified final result: prerequisites-pass / CHECKS_PASSED / complete, terminal succeeded,
exit0.** This is a real bounded development-sandbox inspection result, not a mocked PASS.
It establishes only the implemented target/credential/config/client/schema/inventory/space
checks at that observation time. No backup exists from this session, no restore is proved,
no WordPress/Plesk UI/MFA/staff workflow acceptance is implied, and no actual version/hash/map
beyond the sanitized allowlisted receipts is claimed. Raw credentials stayed server-local.

Ordinary failures and local corrections, in order:

1. INPUT_UNVERIFIED: the submitted non-secret token was34 hex characters instead of32. This was
   an avoidable launch-parameter mistake. A local generator now uses token_hex(16), validates
   references and constructs an exact120second UTC window before submission. Regression tests
   reject overlong generator output. No server guard was loosened.
2. HTTP_AUTH_SCOPE_BLOCKED: a reference-only30second probe read3 target configs and found4 auth
   references belonging to the sandbox's own Plesk password directory, not the parent subscription.
   Auth files read by these probes:0. Local runtime scope was corrected to the exact sandbox area,
   narrowing away the parent area; no credential/configuration/security setting was changed.
3. PLESK_ROOT_MISMATCH: bounded structural probes distinguished3 sandbox document-root references
   from4 shared static references. Nested nginx parsing confirmed2 ACME and2 PKI validation
   locations. The validator now permits only that exact shared static root within anchored
   certificate-validation location prefixes, while main/Apache roots must remain sandbox-only.
   It never reads the shared static directory. Unrelated/production roots and parsing ambiguity
   still block. Counter comparison ensures directives cannot silently disappear from validation.
4. Final repeat, after local tests, completed CHECKS_PASSED. All jobs used privilege none, no sudo,
   canonical development target and30/120second bounds. No blind loop, automatic scope expansion,
   service/account change, installation, DB write, backup, transfer, restore or deployment occurred.

Intermediate probes progressed from category-only roots, to simple context, directive kind and
nested context; each increased diagnostic precision over the same3 target-specific config files.
The evidence records these reasons individually. Prior generic failure is not retrospectively
assigned a sole cause; the newly observed input/scope failures are directly supported by receipts.

**Local verification:132 recovery tests +14 preflight units +22 development guards +6 mocked
Plesk scenarios =174 passed.** Added regressions cover generated non-secret parameters, auth
scope, no-auth-read probes, nested certificate roots and rejection of main shared/production or
unrelated-location roots. Existing diagnostic/privacy/timeout/hash/build/syntax checks pass.
Application/PHP/browser suites and off-server/restore/MFA tests were not rerun. These local
checks supplement, rather than replace, the separately recorded live read-only result.

Current inspection command SHA256:
`c02d0989efcd5c4b51460570cef840773bf825773c6f1120489f0a2b2ef4a8c1`;
payload `b5819e27b2452271c0833fd0a9897d73bc16359a32b2e02b210a1fc50e4e8d2a`;
manifest `b0127abca2125abc92e73d62e59ad3be94a60c25bf0d147651b66ff1079db67d`.
All8 release files match the manifest; runtime fixes do not alter scheduling implementation2fd02a2.

**Next recommended development action:** prepare a fresh target-bound local R-Backup capture
with server-local credential/config mapping and exact backup scope for separate owner approval.
Do not execute the legacy manual-input capture as a substitute. Its complete artifact scope,
private custody/retention, current disk/config binding, failure handling and subsequent isolated
restore remain independently reviewable decisions. Capture, off-server transfer, restore,
account/MFA/security changes, installation/migration and GateB remain held. Inquiry/emergency/
production untouched. No additional diagnostic repeats are needed after this successful result.

## Safe diagnostic revision — November 2, 2026, local only

[Assignment](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6593883604).
The earlier authorized inspection reached terminal failed/exit2 with only generic BLOCKED.
No failed guard or underlying cause can be established from that output. **No further Gateway,
server, credential or configuration access occurred.** New diagnostics require a fresh approval.

The local guard review covers input/window, root/path, literal config extraction, scoped Plesk/
auth/pool/include reads, native client/target/metadata/schema, inventory/config recheck, disk,
signals and serialization. No particular category is asserted as the original failure.
inspect_local.py now attaches fixed code IDs to known guarded operations, maps stage solely
from a constant allowlist, and emits only state/code/stage on success OR failure. Unknown errors,
malformed diagnostic attributes and attempted success-code exceptions become BLOCKED_UNCLASSIFIED.
No arbitrary exception text, source snippets, config/password/user values, private hashes, paths,
SQL output or environment metadata are serialized. Even a private-rich successful internal
result is reduced to CHECKS_PASSED/complete. Output uses fixed strings, avoiding arbitrary JSON
object serialization. Codes identify operation categories, never a credential value or writer.

Read-only/target/no-sudo/no-artifact/no-capture boundaries, all file/include/client/disk guards,
non-secret input fields and one-job policy remain unchanged. Secret dictionaries clear in
finally on both successful and failed inspections; immutable buffers are not claimed securely
zeroized. Signal diagnostics distinguish SIGALRM=TIME_LIMIT_REACHED from TERM/INT=EXECUTION_INTERRUPTED;
TERM could mean supervisor timeout or cancellation and is not attributed to either. Inner115s
TERM+5s kill grace and Gateway120s cap remain. Missing receipt/forced kill is never PASS.

**Final local validation:120 recovery tests +14 preflight units +22 deployment guards +6 mocked
Plesk checks =162 passed.** Fifteen new diagnostic tests cover real synthetic failures at each
classified operation, all allowlisted branch outputs, unknown/unhashable/spoofed codes, private
success-result filtering, signal timer/disarming, and local GNU timeout force-kill semantics
using a short-lived synthetic child. This tests local process behavior, not farm cancellation.
All output fixtures are synthetic. No real inspection, native DB connection, backup, repair,
account, plugin, inquiry/emergency or production changes occurred. Existing broader application
suite and prior client-only fixture results remain historical, not rerun live evidence.

New command SHA256 `e65ef41956510172e93fd13149a7360be6bbd58856ed0a24f7d1d0ae42110b98`;
payload SHA256 `a43535e7c9929d9ed60b5764f0fe7343e29daef2a93d284e44da09c410ac45d6`;
manifest SHA256 `4815e092f6e3e560a1921432b3916b5fc8a2785a09957020fd552034a9985b20`.
All8 release inputs match the manifest; deterministic rebuild and Python/Bash syntax/privacy/
document-link checks pass. Legacy backup runtime/capture commands are unchanged in this revision.
Changed files: inspect_local.py, inspect-local.payload.py, inspect-local.command.sh,
LOCAL-INSPECTION.sha256, test_inspection_diagnostics.py, canonical05, R-Backup card and this report.
Implementation2fd02a2, scheduling/security/plugin/install source and actual server remain untouched.

**Owner decision:** [fresh card](../pilot/r-backup-approval.md#single-revised-inspection-authorization-card)
for exactly one diagnostic inspection using the new hash and the same sandbox-only scope.
No automatic renewal/retry, two-renewal mechanism or guard relaxation. Capture/transfer/restore/
MFA/Gate B remain held. Issue11 receives only a minimal sanitized local-delivery status comment.

## Server-local inspection revision — November 2, 2026, local only

Owner reports upgraded root SSH access and requests server-local credentials/discovery without
manual secret/path assembly. Revised source and embedded, hash-verified command require no
stored selectors. Non-secret actor/approval/window/token parameters are the only stdin fields.
New [authorization card](../pilot/r-backup-approval.md#single-revised-inspection-authorization-card)
explicitly reviews existing-root access vs no sudo, scoped reference discovery and credential
read permissions. Reported root connectivity remains **unverified**, not execution authority.

Credential parser extends the existing opaque-token target validator with optional literal
DB_USER/DB_PASSWORD extraction. Unsupported double-quoted escapes/interpolation, expressions,
comments/conditional/duplicate/dynamic declarations or remote host fail closed. No PHP execution,
credential export or copied named client file. Existing MariaDB adapter uses private anonymous
FD/scrubbed subprocess env and fixed SELECTs. No dump/capture dispatch or public mode selector
exists in the new entrypoint. Immutable Python strings are not claimed securely zeroized.

Discovery is bounded to sandbox-specific conf/fixed pool/.htaccess plus literal same-conf includes
and explicitly referenced auth files in the listed protected area. Same-conf generated symlinks
are resolved/checked; other links/traversal fail. Missing actual layout, external/wildcard includes,
production root or nonlocal credential source is BLOCKED, not an invitation to read global files.
Actual paths never leave the process; a map hash is returned. A later capture needs a separate
reviewed approach to rediscover/bind its map; this revision does not authorize recovery writes.

**Tests:**105 recovery tests passed (13 new server-local fixture tests), plus14 existing
sandbox-preflight units,22 development guards and6 mocked Plesk checks = **147 local checks**.
New tests cover successful no-export/no-write/no-dump inspection, literal escape handling,
unsafe credential sources, missing pool, production root, external includes/auth paths,
allowed same-conf generated symlink vs out-of-scope link, extra credential/mode parameters,
window failure, client incompatibility, selector-free standalone command, reproducibility,
release checksums and shell syntax. All data/scope/DBs are synthetic; no real backup occurred.
Existing API-contract and client-only tests remain historical, not new live compatibility proof.

New command SHA256 `adbea0af833502bccaee05673f3420f8d9b4125fc7cacc4c03c60afc9eb0e81a`;
standalone payload SHA256 `5bfaa57cbb90e4676d0b4ed23a8c81f3a28ccce32835c10d8b01b3e064a21b51`;
manifest SHA256 `be9702798e875a748b6edc790c859e83bb61880f4317af9d0dfdf8e09dcd3d6e`.
All8 new release inputs and8 regenerated legacy inputs match checksums. Legacy generated command
hashes changed because the shared literal validator gained the optional credential-return path;
no previous approval applies to those rebuilt bytes. No application/security/plugin/schema or
installation source changed; implementation2fd02a2 preserved.

**Stop:** fresh explicit owner authorization for the new command/read/root scope before any
Gateway/server call. Record non-secret exact single-attempt execution parameters at launch.
No real credential, private selector, config path map or identity evidence was invented. No
production, account, MFA, inquiry/emergency or server changes occurred. Capture, transfer,
restore and Gate B remain held. Prior manual-input checklist is superseded for inspection.

## Final inspection authorization readiness — November 2, 2026

Continuation from48ba6b2. **No Gateway call of any kind was made.** Only the available tool
catalog/operating-doctrine skill was read; no stored-value metadata/content, server identity,
SSH connection or job was requested. No credentials were created/exposed. This is contract/local
verification, not a live connection, actual privilege or stored-selector verification.

Inspection command/runtime unchanged: all8 committed release-file checksums passed. Command
SHA256 `6a362d2c18072b3230a9f5fd2db0705696ad5cff222040821f2468314acd2138`;
payload SHA256 `8bd20008c495a3dacdacae29324d98c0264307a54dc28a82f4f3229b2088f2ed`.
The [single authorization card/checklist](../pilot/r-backup-approval.md#owner-authorization-card--read-only-prerequisites-only)
now records exact private bindings/storage/purpose and unverified status. No runtime change
or fresh backup-source pin is necessary; application implementation remains2fd02a2.

Available Gateway schema confirms named stored stdin/environment selectors, sensitive command
flag, explicit none privilege, umask,120second execution timeout and separate early-yield wait.
Doctrine confirms durable terminal-job monitoring, scope/purpose/expiry checks, one-time input
consumption and secure setup handoffs. No live transport/40KiB environment limit/timeout test
was performed. none is no requested sudo, **not** proof of a non-root identity or narrow SQL
permissions. Sensitive redaction does not protect arbitrary partial/transformed echoes; reviewed
output filtering is required. Normal Gateway wrapper records/read-atime/DB logs are acknowledged.

Ten new local gate tests cover: fixed inspect dispatch with capture/file-write sentinels;
mode=capture input rejection; every missing binding; missing and permission-denied protected
config; sanitized timeout; unexpected DB/home/siteurl identity; unsupported client flags/version;
fixed inspection SQL containing only SELECT and no users/usermeta row reads/writes; appended
capture argument unable to alter the in-memory command. All use disposable synthetic files,
fake DB responses and local subprocesses. Inspection PASS never calls dump or row-baseline query.

Final rerun: **92 recovery tests +14 sandbox-preflight units +22 development guards +6 mocked
Plesk scenarios =134 passed**. Python/shell syntax, reproducible generation, documentation links,
privacy and8 release hashes are included. Existing real local native-client checks from48ba6b2
remain historical and were not rerun; actual protected-client/server behavior remains untested.
No WordPress/PHP/browser suite rerun was needed because application/runtime source is unchanged.

Only4 files change: this report, canonical05, existing R-Backup decision card/operator checklist,
and tests/recovery/test_inspection_gate.py. No new architecture, runtime rebuild, application,
installation script or server change. Issue11 is updated by a minimal status comment only;
no old infrastructure-rich issue body is republished. Capture, transfer, restore and Gate B held.

**Stop/owner decision:** authorize or reject one exact120second read-only inspection after
privately completing actor/approval/window/run, complete protected-config map/read consent and
existing credential input selectors. All these private states remain explicitly unverified;
the card is ready for a conditional decision, not a claim that execution inputs already exist.
No server call until explicit owner authorization and verified private prerequisites.

## Executable R-Backup completion — November 2, 2026

Continuation from b5ea68e, **local engineering only**. The complete executable workflow and
[read-only authorization card](../pilot/r-backup-approval.md#owner-authorization-card--read-only-prerequisites-only)
supersede the earlier local NO-GO findings below. Capture is still HELD for actual private
bindings/live prerequisites and separate approval; local helpers are no longer missing.
Implementation2fd02a2 and all scheduling/security/plugin/installation sources remain untouched.

**Delivered:** reviewable Python source, reproducible standalone payload, exact separate
in-memory inspection/capture commands, local checksum launcher, fixed release manifest and
synthetic test modules. B1–B3 includes empty-directory/file/attribute inventories, independent
MVCC snapshots and consistent dump, explicit protected-config path/hash scope, anonymous client
and raw-SQL descriptors, compressed stream CRC, archive header AND file-content verification,
pre/post raw drift categories,16-artifact manifest, receipt/hash and strict accepted-state verifier.
No server-side staging is needed for the proposed inspection. No missing path is guessed. A non-executing opaque-token config checker validates literal DB name/host, development environment and table prefix; comments, quoted fake declarations, duplicate/conditional/dynamic targets fail closed. This does not replace private review of the complete PHP configuration or prove WordPress runtime behavior.

Archive tests cover effective PAX/GNU traversal/absolute/control names, normalized duplicates,
links/special/sparse/unknown types, both prefix-collision orders, oversized metadata, byte/entry
limits, CRC truncation, hidden headers after tar EOF, missing expected members and wrong file
bytes. Metadata is inspected, never extracted. Source reads and archive hashing stream large
files. A2GiB free-space floor is checked throughout streamed capture writes; initial
capacity additionally requires5×(source+DB estimate+protected bytes)+64MiB workspace overhead.
Loss of reserve mid-workflow is covered by a failing-path test. Snapshot responses are bounded64MiB/250,000 rows; client output has kernel RLIMIT_FSIZE
and a conservative rejection at the exact cap (the truncation-with-exit0 regression is covered).
Anonymous descriptors close on interruption; no named credential file or uncompressed SQL
spool can be orphaned. Partial restricted capture artifacts are intentionally retained, never
accepted solely because a checksum file exists. Hard kill/no receipt/nonzero job is incomplete.

### Final validation rerun

| Check | Result and practical limit |
| --- | --- |
| Recovery suite | **82 tests passed**: previous25 reference/SELECT tests plus57 new workflow/negative/archive/packaging/syntax/link/privacy tests. Fixtures are disposable synthetic files, fake DB responses and mock client subprocesses; no farm target/config access |
| Existing protections | **14** sandbox-preflight units, **22** development guard scenarios, **6** mocked Plesk scenarios passed |
| Real local native-client defaults | **2 checks passed**, one each for mariadb and mariadb-dump: explicit first-option defaults-file on inherited descriptor excludes hostile user defaults. No SQL server/network/database dump was run |
| Syntax and packaging | Source/build/generated Python parsed;4 shell entrypoints passed bash -n; generated payload/commands reproduce byte-for-byte;8 release files match manifest; relative doc links and secret-pattern/receipt privacy tests pass |
| Application | Scheduling, browser/PHP/MFA/security historical results were NOT rerun; no application source changed |

**Total126 checks:82 recovery +42 existing +2 native-client checks.** This is not a live backup,
restore, Plesk compatibility test, authenticated server inspection or operational acceptance.
The actual protected stack's current client versions/flags, descriptor support, paths, disk,
private config scope/environment mapping and DB credentials remain unverified. No Docker
network/service, real secret, real customer record or farm-server call was used. The local
Docker daemon required normal tool escalation; it was not a farm access approval.

Local client evidence: MariaDB10.6.28 (not the historical farm10.6.23), Debian x86_64.
Image `sha256:d29a34d05b1de1afa84c88bdad35cbba6dc1d1135ad2a95a8ceb56a710819e82`.
Client SHA256 `553e43e119fe85ee663669e989f1cc865356fdd79392f8de70a10ca91db1e651`;
dump client SHA256 `b378c34489e61a72eb756949a66ee304ec7a3c23a81b7fcb1487789895c0e672`.
The fixture test is pinned to that existing image, without pulling/installing an environment.

Release SHA256:

| Artifact | SHA256 |
| --- | --- |
| PAYLOAD.sha256 | `116f61fbfc9f3f7a45bf7d550118198a2873e0adb72349489b25c22e61912f49` |
| Standalone payload | `8bd20008c495a3dacdacae29324d98c0264307a54dc28a82f4f3229b2088f2ed` |
| Inspection command | `6a362d2c18072b3230a9f5fd2db0705696ad5cff222040821f2468314acd2138` |
| Capture command — held | `2f8d864733c2a8aebf949f21ca37c8c5b7b1a55a479c98f05e29a689cfc02745` |

[The fixed manifest](../../scripts/recovery/PAYLOAD.sha256) pins all8 runtime/build/source inputs.
No execution approval follows a Git commit or matching hash. Private stdin uses the exact
allowlisted JSON fields; it is not part of the public source release. Config/plugin hashes may
be null ONLY to establish observations in inspection; capture requires a newly reviewed exact
hash binding. DB credentials never enter argv, child environment, stdout or named files.

### Remaining blockers — owner decisions only

1. Approve/reject the single **read-only inspection** card. Privately bind actor/approval,
   <=120second UTC window/run token, exact complete sandbox-only protected-config path/alias
   map with scope attestation, permission to read root/config, and existing DB credential
   selector. Code selector contains exactly the pinned public payload. No literal credential,
   UID, email or private path is requested in chat/Git; no path discovery is implied.
2. After inspection, review actual hashes/versions/permissions/table/plugin/disk results. Any
   unsupported/mismatched prerequisite is BLOCKED, not permission to install/change/relax a
   guard. Capture needs its own exact private non-null-hash binding/UTC window and explicit
   owner authorization. Read-only PASS does not authorize capture or create a new baseline.
3. Local-only custody is fixed for this payload. Select retention custodian with capture approval;
   encrypted off-server recipient/destination/transfer and R-Restore are later separate decisions.
   Do not queue restore until the new accepted receipt/manifest exists. MFA/accounts/security/
   scheduling Gate B, production, inquiry and emergency remain held/unchanged.

Reproduce locally: `python3 -B scripts/recovery/build.py`, then
`PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests/recovery -p 'test_*.py'`;
run existing guard/preflight commands and `bash tests/recovery/mariadb-defaults.sh` on the
local Docker fixture only. **Do not invoke capture/inspection against any farm target** under
this assignment. Existing local logs are /workspace/work/r-backup-executable-*; public hashes
of those synthetic logs follow (no private data):

- units: `06d55a2c0a2e7bfdd44b2847ea0b69299481c7d6f6543c2a5cbd612bd822cc9e`
- preflight: `3b82a5edb3bd49215f35f4dc7214845621b4788fb7d7ded298f1465ff900db15`
- guards: `460e8584f7cae831f2627f11400f4b049bc3b2dcecac39663b8c68a6fbb66d98`
- plesk: `cc8c58f5dd1428a983c6d5214a1bf4a53b653cd50e1a48ef51b7274e7803ae52`
- mariadb-defaults: `c1391f503efcffb4c5da9bf1cc20b49164bd5b35ec608d2f0af51de6e009043b`

Earlier sections are historical planning/review evidence, not outstanding local engineering tasks.

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
