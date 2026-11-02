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
| Credentials | Existing literal sandbox config read privately without executing PHP/WordPress; temporary0600 DB client file only inside this new directory, removed at command exit. No passwords in argv/chat/Git |
| Capture | Single-transaction target-only DB dump; wp-content; remaining WordPress files; exact wp-config/salts; .htaccess; **only** privately verified sandbox nginx/Apache/FPM and BasicAuth credential/config files. No global Plesk DB/config, production files or unrelated DB |
| Limits | Proposed source<=2GiB/100,000 regular files, DB estimate<=1GiB, free space>=max(2GiB,5×source+DB estimate), operation<=30minutes. Owner may revise; exceeded/unverified bounds stop before export |
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
failure. Retain only this attempt's restricted artifacts marked incomplete; remove its temporary
client secret, never existing backups or live files. No restore, database repair, options rewrite,
cron shutdown, plugin operation or automatic retry under a different target. A failed backup
needs no live rollback because no application/configuration write is authorized.

**Owner authorization record:** scope/run/window, named private actor and custody references,
approved bounds/off-server choice, decision/date. A blank field is Hold. **Stop after a verified
capture receipt; submit its exact bundle path/manifest digest for separate R-Restore review.**

## Final execution review — November 2, 2026

**Decision now: NO-GO for capture.** Planning is complete, but the catalogue is not yet one
fully assembled/tested job. This is a narrow execution gap, not a new architecture sprint.
[Final review instructions](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6586718406).

### One owner decision card

Approve only **one local restricted capture** of the exact source/destination family in the
binding table above, by the existing owner-authorized Gateway infrastructure executor. Preserve
production, website content/settings and all account/plugin/security state. Default bounds are
2GiB source /100,000 regular files,1GiB DB estimate,30minutes total, free space at least
max(2GiB,5×source-plus-DB estimate), **plus** protected-config/archive/workspace overhead.
No restore, off-server transfer, security deployment or scheduling install follows from approval.

Kris must privately bind: (1) executor/approval reference and start/end window; (2) unique32-hex
run token/exact new directory; (3) exact sandbox-only protected-config path map and permitted
existing privilege; (4) local-only custody/retention owner and explicit export HOLD. Recommended
first scope is local-only, avoiding key/transfer decisions blocking capture. Encryption recipient,
fingerprint, destination and transfer identity are separate later choices. No passwords here.

**Before this card can become executable:**

- [ ] Assemble/hash the complete B1–B3 payload: catalog JSON assembly, attributes/target receipts,
  pre/post comparison, private protected-config copy/archive, validators and sanitized FD3 receipt.
  Fragment approval does not authorize improvising missing helper bodies on the server.
- [ ] Add/test header-only archive validation for traversal, effective extended names, duplicates,
  links/special files and prefix collisions; checksum validation alone is insufficient.
- [ ] Enforce the whole-job timeout, file/config/archive counts and bytes, actual disk reserve and
  approved interval; the old dump-only900second timeout did not bound the full operation.
- [ ] Privately verify literal config parsing agrees with actual target settings. Regex matches can
  include commented defines or conditional code; ambiguous/dynamic configuration means STOP,
  never execute wp-config to resolve it. Existing parser acceptance alone is insufficient proof.
- [ ] Complete the separately authorized read-only prerequisites below. Prior server facts are stale.
- [ ] Record the final command digest/private bindings, review their local tests, then request
  owner execution authorization for that exact job. Missing binding/check means HOLD.

On any failed guard/time/disk/archive/hash/critical-drift check: stop, mark restricted artifacts
incomplete, remove only the attempt's temporary DB client secret, preserve evidence and report.
No production read/write, live repair, cron shutdown, global lock, automatic retry or old-backup
cleanup. Interrupted jobs require terminal-status and temporary-secret cleanup verification.

### Proposed read-only prerequisite call — not made or authorized here

Application-specific inspection uses `gateway.execute_command(server="srv_01M687VB1R009Y0C3HRN7NMP3A",
shell="/bin/bash", privilege="none", umask="077", timeout_seconds=120, wait_seconds=1,
sensitive_command=true, command=<reviewed inspection-only payload>)`. If privilege is insufficient,
report BLOCKED and request the exact additional read scope; do not auto-escalate. No staging files,
WordPress bootstrap, package installation or config execution in this call. First confirm current
Gateway typed-tool coverage before using the shell fallback; this is a proposed call specification.

The payload must contain these bounded checks and emit only the whitelisted result below:

| Read-only check / exact primitive | Purpose and expected sanitized output | Private exposure |
| --- | --- | --- |
| `realpath -e`/`stat` on exact root, private parent and wp-config; bounded `find -xdev`/`du -sb`; `df -B1 --output=avail` on private parent | Canonical identity, no link/special/hardlink/cross-device source; byte/count/reserve PASS or STOP | Paths/ownership metadata; no file bodies returned |
| `command -v`, `--version` for Bash/Python3/mariadb/mariadb-dump/tar/gzip/sha256sum/timeout and required filesystem tools | Existing paths/versions and supported flags, PASS/BLOCKED; no installs | Tool/host metadata only |
| Literal config inspection in memory; target-specific sandbox Apache/nginx/PHP-FPM and BasicAuth directives only | Private exact config path map, parser/target agreement and accessible recoverable secrets; public only scope/count/status | DB credentials/salts/auth paths may be read privately, never echoed; no global Plesk config dump |
| Native client through approved in-memory credential handoff; `SELECT DATABASE()` and exact `TXGEh_options` home/siteurl; information_schema table/engine/size/stored-object SELECTs; exact active_plugins option | DB/root/URL/prefix agreement,12 native InnoDB tables, reviewed plugin list and zero stored objects; only counts/PASS/BLOCKED returned | Credentials and option content remain private; no users/usermeta enumeration |

The native-client credential channel for this read-only job must be reviewed explicitly (e.g.
private inherited descriptor supported by the client). If unavailable, STOP: do not write a client
file under a read-only approval or put a password in argv/environment/logs. Protected-config discovery
must stop if Plesk-generated files resolve outside the sandbox-specific approved scope. Operator
reviews exact paths privately; an aggregate PASS cannot replace that binding. Total job cap120s.
No `nginx -T`, global Plesk database export or unrestricted recursive credential search.

`unshare`, mount/network/PID namespaces, loopback routing and private MariaDB/PHP startup are
**R-Restore** dependencies, not prerequisites to a local capture. Their privileged functional
probe is not a read-only check and remains separately held. Encryption/GPG/SSH recipient and
host-key checks belong to separately approved off-server custody. A capture can succeed while
both off-server recovery and independent restoration remain **unverified**.

**Next authorization sequence:** approve only the exact prerequisite read scope if needed to
resolve private bindings; finish the bounded payload review; then approve one exact R-Backup.
Do not request/queue R-Restore until the new manifest receipt exists. No placeholder execution
approval is requested or implied by this card.
