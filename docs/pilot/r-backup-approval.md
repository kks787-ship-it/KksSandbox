# R-Backup — one sandbox recovery capture

**Decision requested: Approve / Reject / Revise / Hold. Current status: HELD.**
This is a proposed execution packet, not permission to run it. Approving R-Backup does **not**
approve R-Restore, accounts, MFA, plugins, scheduling/schema, pilot records or production.

| Binding | Exact proposed scope / required owner input |
| --- | --- |
| Source | `https://sandbox.cornerstonefarmomak.com`; `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`; `wp_mdf4x`; `TXGEh_`; development |
| Reference source | Preserve `2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`; identity correction17fd654. This operation installs no source |
| Actor / window | Privately verified technical operator, explicit approval reference and bounded UTC window; coordinate owner edits without enabling maintenance or disabling cron |
| Destination | One **new**, exclusive0700 directory `/var/www/vhosts/cornerstonefarmomak.com/private/csf-gate-b-backup-<approved-run-token>`; files0600. Token and resulting absolute path recorded privately |
| Credentials | Private approved DB credentials on stdin; anonymous0600-equivalent inherited descriptors, no named client file. Config read without PHP/WordPress execution. No passwords in argv/chat/Git |
| Capture | Single-transaction target-only DB dump; wp-content; remaining WordPress files; exact wp-config/salts; .htaccess; **only** privately verified sandbox nginx/Apache/FPM and BasicAuth credential/config files. No global Plesk DB/config, production files or unrelated DB |
| Limits | Proposed source<=2GiB/100,000 regular files, DB estimate<=1GiB, free space>=2GiB+5×(source+DB estimate+protected bytes)+64MiB, operation<=30minutes. Owner may revise; exceeded/unverified bounds stop before export |
| Custody | Owner selects exact public encryption recipient/fingerprint, off-server destination, existing authorized transfer identity and recovery custodian privately. No default cloud bucket/provider/recipient is selected |

**Required before execution:** private operator mapping, exact run/path/window, reviewed
sandbox-only protected-config path list, disk/object-size budget, current DB/table/active-plugin
state and owner coordination. Last preflight observed12 native InnoDB tables/core-only; if the
new read differs, stop for an updated scope rather than modifying it. Authorize the off-server
recipient/destination explicitly or keep export **separately HELD**; label local capture only,
not off-server/disaster recovery success. No public temporary files or BasicAuth changes.

The actor here is the existing owner-authorized **infrastructure/Gateway executor**. R-Backup
does not require an already provisioned Farm Master, WordPress MFA or a new WordPress account.
Private native-WP controller/UID decisions can remain pending for the later account/security
gates; do not turn them into a circular prerequisite for the backup itself.

**Proposed operations:** use [the canonical command catalogue](../05-backup-rollback-deploy.md#r-backup-proposed-command-catalogue)
and [baseline protocol](../05-backup-rollback-deploy.md#deterministic-baseline-and-drift-review).
Exact private bindings must be resolved before running; no `latest`, glob-selected bundle,
floating source or unspecified helper executable is authorized.

**Outputs:** database.sql.gz, wp-content.tar.gz, wordpress-root.tar.gz, wp-config.php.recovery,
protected-configs archive, target/version/active-plugin/engine/schema metadata, before/after
private per-row snapshots/file inventories, UTC capture interval, fixed artifact SHA256 manifest
and its digest, sanitized capture receipt. Keep raw DB/config, option labels/keys, account IDs
and row-level evidence private; publish only counts/status and approved artifact hashes.

**Consistency/acceptance:** DB dump uses its own InnoDB consistent snapshot; each diagnostic
snapshot uses one read-only MVCC connection for all tables. These are **different observation
instants**, not a shared cross-process snapshot or atomic DB/filesystem backup. Keep exact raw
before/after changes. Compare files, schema, native security settings and application records;
unknown/sensitive changes require review/recapture. Volatile candidates remain recorded.
Verify compressed stream/tar safety, recoverable config/secrets, target binding, every checksum,
file modes and no source mutation. R-Restore later establishes the artifact-derived row baseline
before clone rewrites; no dump/live-byte-equivalence claim is made by capture alone.

**Off-server acceptance, only if selected:** encrypt the entire private recovery set before
transfer, verify ciphertext SHA256 at the approved destination, and have the private key custodian
decrypt/read/check inner manifests in isolation. Do not put the private key on the farm server.
No plaintext backup through a public link, chat, GitHub or unapproved intermediary. Transfer,
recipient availability and decrypt receipt remain pending until actually verified.

**Abort/containment:** stop on target/symlink/engine/credential/space/time/hash/sensitive-drift
failure. Retain only this attempt's restricted artifacts marked incomplete; close its anonymous
client secret descriptors, never existing backups or live files. No restore, database repair, options rewrite,
cron shutdown, plugin operation or automatic retry under a different target. A failed backup
needs no live rollback because no application/configuration write is authorized.

**Owner authorization record:** scope/run/window, named private actor and custody references,
approved bounds/off-server choice, decision/date. A blank field is Hold. **Stop after a verified
capture receipt; submit its exact bundle path/manifest digest for separate R-Restore review.**

## Executable readiness — local engineering complete

The prior b5ea68e local blockers are implemented in the [source](../../scripts/recovery/backup.py),
[standalone payload](../../scripts/recovery/payload.py) and [release manifest](../../scripts/recovery/PAYLOAD.sha256).
B1–B3 now form one workflow; no missing shell helper, archive validator or receipt body is delegated
to the operator. The [inspection command](../../scripts/recovery/inspect.command.sh) and separate
[capture command](../../scripts/recovery/capture.command.sh) execute the same pinned payload with
different modes/time limits. Local fixtures are not live validation. **Capture remains HELD.**

This executable supersedes earlier fragment instructions in this packet/catalogue: credentials are
supplied through private stdin, consumed via anonymous inherited descriptors, and never written to
a named client file. No wp-config execution or credential extraction occurs. A conservative opaque-token checker validates literal target declarations; unsupported/dynamic/conditional declarations stop for private review. Owner review
binds the exact config content/hash and environment; inspection may establish fresh hashes with
null expected hashes, while capture requires all expected hashes. No missing paths are inferred.

### Owner authorization card — read-only prerequisites only

**Decision requested: authorize one bounded inspection / reject / hold. Current status: HELD.**

| Item | Exact scope / decision |
| --- | --- |
| Target | Gateway `srv_01M687VB1R009Y0C3HRN7NMP3A`; sandbox URL/root/DB/prefix in the binding table; production excluded |
| Actor | Existing privately verified infrastructure identity, `privilege="none"`; no sudo/account changes. If required files are inaccessible, stop |
| Proposed command | Exact bytes of [inspect.command.sh](../../scripts/recovery/inspect.command.sh), SHA-pinned by [PAYLOAD.sha256](../../scripts/recovery/PAYLOAD.sha256). Verified standalone code supplied in memory; no staging, shell interpolation of secrets or temp directory |
| Tool call | `gateway.execute_command(server="srv_01M687VB1R009Y0C3HRN7NMP3A", shell="/bin/bash", privilege="none", umask="077", timeout_seconds=120, wait_seconds=1, sensitive_command=true, command=<exact inspect.command.sh>, env_from={"CSF_REVIEWED_PAYLOAD":"r-backup-reviewed-payload"}, stdin_from="r-backup-readonly-bindings")`. This is a proposal; no call made |
| Read permission | Hash/count bounded sandbox root files and directory metadata, read exact wp-config, and **only** the privately approved protected-config files; inspect free space. Run installed MariaDB client versions and fixed native SELECTs on `wp_mdf4x`: home/siteurl/active_plugins, table/column/index/counter/engine/size metadata and stored-object counts. No WordPress bootstrap, user enumeration, row-baseline capture, DDL/DML or daemon operations |
| Bounds | 115second TERM +5second kill grace, total120seconds within approved UTC window; source<=2GiB/100,000 files and100,000 directories; <=64 protected files totaling16MiB; no links, hardlinks, special files or crossed filesystem; required free reserve checked |
| Exposure/output | Credentials/config bytes/paths and SQL responses remain private memory; subprocess env is scrubbed. Owner-only output: PASS/BLOCKED, file/byte counts,12-table/engine result, client versions and fresh config/plugin/protected-alias hashes. No values, passwords, usernames, raw SQL diagnostics, account IDs or row hashes. Reads may cause normal OS atime/DB access logs; no application/config writes |
| Stop | Bad code digest, missing/private input, absent tool/descriptor support, changed bound hash, target/engine/table/object mismatch, scope/size/space/time failure or insufficient read privilege: nonzero/BLOCKED; no fallback, guessed path, escalation, repair, capture or retry |

**Kris must privately bind only:** technical actor/approval reference; start/end UTC (<=120seconds
for this inspection), unique32-hex attempt token; complete exact sandbox-only Apache/nginx/FPM/
BasicAuth file map with safe private aliases and scope attestation; existing DB credential selector
for local host; authorization to read config without executing it. Unknown expected hashes may be
null in inspection only. Public reviewed code belongs in `r-backup-reviewed-payload`; private JSON
uses `r-backup-readonly-bindings`. These are selector names, not claimed existing stored values.
The exact input contract is validated by `bindings()`; never paste credentials into chat/Git.

The returned hashes go into a **new private capture binding** only after owner review. Before
capture, separately approve that binding (mode=capture, exact run/window/path and non-null hashes)
and the distinct capture command. Its hard bounds include1770second TERM +30second kill grace,
2GiB source/1GiB DB estimate/2GiB dump,64MiB per diagnostic response/250,000 rows per baseline,
16MiB protected-config total, disk reserve and active-plugin/config agreement. Streamed outputs maintain a2GiB free-space floor. Raising fixed limits requires a new reviewed payload, never an input override. Local-only custody
is fixed; off-server transfer is deliberately absent from this executable.

On capture failure, anonymous credentials/raw SQL spools disappear with descriptor/process closure;
restricted incomplete artifacts remain. Any missing/malformed receipt, nonzero durable job result,
review-required drift or forced termination means **not accepted**. A receipt never proves a
functional restore. Authorize neither R-Restore nor Gate B here; only the new successful manifest
and receipt can support a later separate restore decision.
