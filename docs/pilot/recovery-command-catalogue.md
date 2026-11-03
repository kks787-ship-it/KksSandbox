# Proposed recovery operation commands — NOT EXECUTED

Technical appendix to [canonical runbook05](../05-backup-rollback-deploy.md), not independent
authority. [R-Backup](r-backup-approval.md) and [R-Restore](r-restore-approval.md) are separate
decisions. Bind the exact approved documentation commit/command-text digests and private input
record before execution. A placeholder is **not** a runnable default or permission to improvise.
No farm connection, staging, command or backup/restore occurred while writing this appendix.

## Current isolated restore executable — supersedes restore fragments below

Use [restore_local.py](../../scripts/recovery/restore_local.py) and its deterministic builder,
[release manifest](../../scripts/recovery/LOCAL-RESTORE.sha256) and [owner card](r-restore-approval.md#current-owner-authorization-card--one-private-functional-restore).
The committed shell template is intentionally non-executable without owner-private artifact
bindings. Build the sensitive envelope privately; verify its SHA against the private release
record before submission. No secret-selector/manual SQL credential input is needed.
Gateway: existing designated server, bash, umask077, privilege none, sensitive command handling,
timeout1800s. Only non-secret actor_ref, approval_ref, UTC start/end, authorization marker
`one-isolated-functional-restore` and explicit `login_test=clone-admin` go on stdin. No target,
SQL, path, executable, credential or account override is accepted. Follow job to terminal state.


## Current B1–B3 executable — supersedes the historical fragments below

Use [backup.py](../../scripts/recovery/backup.py), deterministically built into the standalone
[payload.py](../../scripts/recovery/payload.py), with [fixed file hashes](../../scripts/recovery/PAYLOAD.sha256).
[build.py](../../scripts/recovery/build.py) produces the in-memory inspect/capture commands; no
server staging is needed. The owner approves only the exact inspection command first, using
[the current card](r-backup-approval.md#owner-authorization-card--read-only-prerequisites-only).
The distinct capture command is **not** authorized by inspection approval.

All B1–B3 helpers are implemented: bounded no-follow inventory/attributes, empty directories,
private native-client descriptor, one-connection MVCC diagnostic SELECT plans, independent
single-transaction dump, private anonymous raw spool and CRC-tested gzip, protected-config
allowlist/hash/attributes, archives whose effective headers AND file bytes are verified, full
pre/post raw drift report, fixed16-artifact manifest, strict verifier, sanitized receipt/hash
and incomplete/error cleanup. Output state is complete/review-required/incomplete; CLI exit0
only for complete or inspection PASS. No drift is discarded or assigned to a writer.

Operational constants: source2GiB/100,000 files/100,000 directories; DB estimate1GiB/dump2GiB;
protected<=64 files/16MiB; diagnostic response64MiB and250,000 rows; metadata header<=1MiB;
archive entry/count/decompressed-byte caps. Native children have RLIMIT_FSIZE, sanitized env,
first-option defaults-file and reaping in finally. Capture checks free space before export,
during native dump and before completion. Files are streamed; large media are not loaded whole.
The complete source, parser/build/synthetic integration/negative/privacy tests are reviewable.

Payload JSON has EXACT fields: mode, source_pin, run_token, actor_ref, approval_ref, start/end
(strict UTC Z), custody(local-only), config_reviewed(true), wp_config_sha256, plugins_sha256,
protected and db_secret. protected is an explicit nonempty list of path/name/sha256/scope_verified;
no glob/discovery/default paths. db_secret has user/password/host(localhost or127.0.0.1) only.
Inspection permits null expected hashes to establish fresh private bindings. Capture permits no
null hash. No target override, custom SQL, custom executable or owner policy bypass is exposed
by CLI. Injectable scope/DB parameters exist for local fixtures only, never from input JSON.

No field is an authentication substitute: private approval references are owner-controlled
records, not cryptographic proof or blanket authorization. Complete means captured/checked,
**not restored**. Caller MUST follow the Gateway job to terminal exit0, match the returned receipt
hash to receipt.json and verify its exact manifest; do not choose latest/retry/accept partial data.
Hard kill can prevent a receipt. Anonymous descriptors close on death; the new restricted attempt
remains incomplete and is not automatically deleted. There is no live rollback because source
writes/restore are absent. Manifest excludes receipt to avoid circular hashing; output separately
binds the canonical receipt hash. verify_receipt() rejects incomplete/review-required receipts.

The B4/off-server and R-Restore specifications below remain separately held and are NOT bundled.
The older B1–B3 shell snippets are historical review context, **not the execution payload**.

Gateway operation proposed for each composed lifecycle: `execute_command`, canonical server
`srv_01M687VB1R009Y0C3HRN7NMP3A`, shell `/bin/bash`, umask077, existing verified privilege only,
bounded timeout. The composed capture must run under `timeout --signal=TERM --kill-after=30s 1770s /bin/bash`
inside a Gateway job with `timeout_seconds=1800`; bind the complete script on stdin, not shell-interpolated
private values. Timeout terminates the attempt, never promotes partial artifacts to success. Private values use approved `stdin_from`/`env_from` selectors, never literals.
Fragments within a lifecycle are one reviewed shell payload; do not assume variables/functions
persist between Gateway jobs. Follow the durable job to terminal status and inspect its actual
sanitized acceptance receipt, not merely wrapper success.

## R-Backup command payload

### B1 — source guards and private client material

Required private bindings: `CSF_RUN_TOKEN` (32 lowercase hex, owner-approved unique attempt),
approval/window/actor reference, approved command text and `CSF_SELECT_PLAN_SOURCE` (the exact
[pure generator text](baseline-select-plan.py.txt), outside the webroot; approved SHA256).
Bounded readonly inspection resolves additional sandbox-only protected-config paths privately.
No actual path list containing credential-file identifiers is published here.

```bash
set -euo pipefail
set +x
umask 077
CSF_ROOT=/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com
CSF_PARENT=/var/www/vhosts/cornerstonefarmomak.com/private
CSF_DB=wp_mdf4x
test "$(realpath -e "$CSF_ROOT")" = "$CSF_ROOT"
test "$(realpath -e "$CSF_PARENT")" = "$CSF_PARENT"
[[ ${CSF_RUN_TOKEN:?private approved run token required} =~ ^[a-f0-9]{32}$ ]]
test -f "$CSF_ROOT/wp-config.php" && test ! -L "$CSF_ROOT/wp-config.php"
test -z "$(find "$CSF_ROOT" \( -type l -o -type b -o -type c -o -type p -o -type s \) -printf x -quit)"
test -z "$(find "$CSF_ROOT" -type f -links +1 -printf x -quit)"
test "$(find "$CSF_ROOT" -type d -printf '%D\n' | sort -u | wc -l)" -eq 1
test "$(find "$CSF_ROOT" -type f -printf x | wc -c)" -le 100000
CSF_SOURCE_BYTES=$(du -sb "$CSF_ROOT" | cut -f1)
test "$CSF_SOURCE_BYTES" -le 2147483648
CSF_BUNDLE="$CSF_PARENT/csf-gate-b-backup-$CSF_RUN_TOKEN"
test ! -e "$CSF_BUNDLE" && test ! -L "$CSF_BUNDLE"
mkdir -m 0700 -- "$CSF_BUNDLE"
exec 3>&1
exec > "$CSF_BUNDLE/operator-private.stdout" 2> "$CSF_BUNDLE/operator-private.stderr"
CSF_CLIENT="$CSF_BUNDLE/.source-client.cnf"
trap 'rm -f -- "$CSF_CLIENT"' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
python3 -I -B - "$CSF_ROOT/wp-config.php" "$CSF_CLIENT" <<'PY'
import os,pathlib,re,sys
config=pathlib.Path(sys.argv[1]).read_text()
def literal(name):
    matches=re.findall(r"define\s*\(\s*['\"]"+re.escape(name)+r"['\"]\s*,\s*('(?:[^'\\]|\\.)*'|\"[^\"\\$]*\")\s*\)",config)
    assert len(matches)==1,'Missing/ambiguous literal; do not execute wp-config'
    v=matches[0];v=re.sub(r"\\(['\\])",r'\1',v[1:-1]) if v[0]=="'" else v[1:-1]
    assert not any(c in v for c in '\r\n\0'),'Unsupported client value'
    return v
assert literal('DB_NAME')=='wp_mdf4x'
assert literal('WP_ENVIRONMENT_TYPE')=='development'
assert literal('DB_HOST') in ['localhost','localhost:3306','127.0.0.1','127.0.0.1:3306']
assert re.findall(r"\$table_prefix\s*=\s*['\"]([A-Za-z0-9_]+)['\"]\s*;",config)==['TXGEh_']
def quote(v):return '"'+v.replace('\\','\\\\').replace('"','\\"')+'"'
host=literal('DB_HOST').split(':')[0]
body='[client]\nhost='+quote(host)+'\nport=3306\nuser='+quote(literal('DB_USER'))+'\npassword='+quote(literal('DB_PASSWORD'))+'\n'
fd=os.open(sys.argv[2],os.O_WRONLY|os.O_CREAT|os.O_EXCL|os.O_NOFOLLOW,0o600)
with os.fdopen(fd,'w') as out:out.write(body)
PY
CSF_MYSQL=(/usr/bin/mariadb --defaults-file="$CSF_CLIENT" --batch --raw --skip-column-names --default-character-set=utf8mb4 --database="$CSF_DB")
CSF_GUARD=$("${CSF_MYSQL[@]}" --execute="SELECT DATABASE(),(SELECT option_value FROM TXGEh_options WHERE option_name='home'),(SELECT option_value FROM TXGEh_options WHERE option_name='siteurl')")
test "$CSF_GUARD" = $'wp_mdf4x\thttps://sandbox.cornerstonefarmomak.com\thttps://sandbox.cornerstonefarmomak.com'
CSF_DB_ESTIMATE=$("${CSF_MYSQL[@]}" --execute='SELECT COALESCE(SUM(DATA_LENGTH+INDEX_LENGTH),0) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')
test "$CSF_DB_ESTIMATE" -le 1073741824
CSF_NEEDED=$((5*(CSF_SOURCE_BYTES+CSF_DB_ESTIMATE)))
test "$CSF_NEEDED" -ge 2147483648 || CSF_NEEDED=2147483648
test "$(df -B1 --output=avail "$CSF_PARENT" | tail -n1)" -ge "$CSF_NEEDED"
```

Use `--defaults-file` as the first client/dump option, not `--defaults-extra-file`: inheriting
host option files could introduce unreviewed init commands, destination/protocol settings or
dump scope flags. Bind/clear relevant client environment and verify installed binary behavior
in the approved prerequisite check. No silent switch to another client/version is permitted.

These proposed bounds need owner confirmation. Stop for changed host/environment/prefix,
non-InnoDB/unknown tables, unexpected active/security plugins, nonzero stored routine/event/
trigger counts, symlinks/hardlinks/cross-filesystem source or missing tools; no package install
or guard relaxation. SQL lifecycle uses only this DB. Do not run existing `backup-dev.sh` as
a substitute: it bootstraps WordPress and does not alone capture this recovery/config scope.

### B2 — deterministic source observations, then consistent dump

For file manifests use relative paths so recovery can check them in the clone. Keep outputs
private. Preserve modes/ownership/mtime separately in a private metadata inventory; do not
reinterpret access-time updates caused by reads as a content change.

```bash
date -u +%FT%TZ > "$CSF_BUNDLE/capture-start-utc.txt"
(cd "$CSF_ROOT"; find . -xdev -type f -print0 | LC_ALL=C sort -z | xargs -r -0 sha256sum) > "$CSF_BUNDLE/before-files.sha256"
```

Collect catalog JSON with the following **SELECTs**, order fixed. The column-row JSON keys
match the generator input. Run through `CSF_MYSQL`; redirect output to new private files,
parse each JSON line and combine as `{"columns":[...],"indexes":[...]}` without printing values.
Retain AUTO_INCREMENT counter observations separately, outside the physical-schema digest.

```sql
SELECT JSON_OBJECT('table',c.TABLE_NAME,'column',c.COLUMN_NAME,'ordinal',c.ORDINAL_POSITION,
 'type',c.COLUMN_TYPE,'nullable',c.IS_NULLABLE,'default',c.COLUMN_DEFAULT,'extra',c.EXTRA,
 'charset',c.CHARACTER_SET_NAME,'collation',c.COLLATION_NAME,'generated',c.GENERATION_EXPRESSION,
 'engine',t.ENGINE,'table_collation',t.TABLE_COLLATION,'pk_order',s.SEQ_IN_INDEX)
FROM information_schema.COLUMNS c
JOIN information_schema.TABLES t ON t.TABLE_SCHEMA=c.TABLE_SCHEMA AND t.TABLE_NAME=c.TABLE_NAME
LEFT JOIN information_schema.STATISTICS s ON s.TABLE_SCHEMA=c.TABLE_SCHEMA
 AND s.TABLE_NAME=c.TABLE_NAME AND s.COLUMN_NAME=c.COLUMN_NAME AND s.INDEX_NAME='PRIMARY'
WHERE c.TABLE_SCHEMA=DATABASE() ORDER BY c.TABLE_NAME,c.ORDINAL_POSITION;
SELECT JSON_OBJECT('table',TABLE_NAME,'name',INDEX_NAME,'sequence',SEQ_IN_INDEX,'column',COLUMN_NAME,
 'non_unique',NON_UNIQUE,'prefix_length',SUB_PART,'collation',COLLATION,'kind',INDEX_TYPE)
FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX;
SELECT JSON_OBJECT('table',TABLE_NAME,'next_id',AUTO_INCREMENT)
FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME;
```

Generate and retain the exact private SELECT plan from the reviewed catalog. The generator
has no database/network calls, validates the expected twelve native InnoDB tables/identifiers/
ordered primary keys, and emits **one read-only consistent transaction** covering all rows.
Before/after captures use the same source/origin/schema binding and canonical framing.

```bash
: "${CSF_SELECT_PLAN_SOURCE:?approved generator text path required}"
test -f "$CSF_SELECT_PLAN_SOURCE" && test ! -L "$CSF_SELECT_PLAN_SOURCE"
test "$(sha256sum "$CSF_SELECT_PLAN_SOURCE" | cut -d' ' -f1)" = 509c5acd35e2e165fd1c46a260bc3ec3d117a79b96e92b726cd83dfdd7b13b46
python3 -I -B "$CSF_SELECT_PLAN_SOURCE" "$CSF_BUNDLE/before.catalog.json" > "$CSF_BUNDLE/before.select.sql"
"${CSF_MYSQL[@]}" < "$CSF_BUNDLE/before.select.sql" > "$CSF_BUNDLE/before.rows.ndjson"
timeout 900 /usr/bin/mariadb-dump --defaults-file="$CSF_CLIENT" \
  --single-transaction --quick --skip-lock-tables --skip-add-locks --no-tablespaces \
  --hex-blob --routines --events --triggers --skip-comments --skip-dump-date \
  "$CSF_DB" | gzip -n > "$CSF_BUNDLE/database.sql.gz"
gzip -t "$CSF_BUNDLE/database.sql.gz"
```

The diagnostic transaction is **not** mysqldump's transaction. Retain each observation time;
never call independently captured hashes an exact matched-dump snapshot. R-Restore derives
the authoritative artifact baseline from the imported dump before clone changes.

### B3 — files/configuration and final receipt

```bash
tar --one-file-system -czf "$CSF_BUNDLE/wp-content.tar.gz" -C "$CSF_ROOT" wp-content
tar --one-file-system --exclude=./wp-content --exclude=./wp-config.php \
  -czf "$CSF_BUNDLE/wordpress-root.tar.gz" -C "$CSF_ROOT" .
install -m 0600 -- "$CSF_ROOT/wp-config.php" "$CSF_BUNDLE/wp-config.php.recovery"
(cd "$CSF_ROOT"; find . -xdev -type f -print0 | LC_ALL=C sort -z | xargs -r -0 sha256sum) > "$CSF_BUNDLE/after-files.sha256"
cmp "$CSF_BUNDLE/before-files.sha256" "$CSF_BUNDLE/after-files.sha256"
date -u +%FT%TZ > "$CSF_BUNDLE/capture-end-utc.txt"
```

Before the end receipt, repeat the exact catalog/row capture as `after.*`, validate each
header/source, reject duplicate row identities and compare complete raw rows plus schema.
Use the [baseline contract](baseline-contract.json) and canonical drift-review rules, **not a
wildcard-ignore or aggregate-only hash**. Do not overwrite `before.*`. Preserve raw snapshots,
public summary counts and reviewed/nonreviewed status separately.

Copy only the privately approved sandbox vhost/pool/HTTP-auth files to `protected-configs/`
with no-follow/0600 handling and record original attributes; never read/export global Plesk
configuration or unrelated vhosts. Exact paths and per-file copy commands are bound in the
private approval record before execution. Hash the resulting protected-config archive and
all fixed artifacts using `sha256sum`, save `SHA256SUMS`, verify with `sha256sum --check --strict`,
then hash that manifest into the owner receipt. A missing config binding is Hold, not authority
to copy the whole subscription. Reject unsafe/duplicate archive members before declaring success. The composed payload must
include a reviewed header-only tar validator (including effective PAX/GNU long names): permit
only relative regular files/directories beneath the expected archive roots; reject absolute,
`..`, duplicate normalized names, links, device/FIFO/sparse/unknown types, control characters
and path-prefix file/directory collisions. Do not extract to validate. This validator is not
implemented by the fragments below; its exact body and synthetic malicious-archive tests are
a capture-readiness blocker. R-Restore must independently revalidate before extraction.
Raw private records stay private; sanitized receipt contains target/run/interval/artifact hashes,
checks/modes, drift categories and unresolved items only. Mark incomplete on any failure.
Return only that validated whitelist receipt on preserved FD3; never cat private logs into a
Gateway result. Hard termination can prevent traps/receipts: mark interrupted, inspect durable
job status and remove only the attempt's approved temporary secret before claiming cleanup.

Fixed private-artifact manifest command (run only after the named metadata/review files exist):

```bash
CSF_ARTIFACTS=(database.sql.gz wp-content.tar.gz wordpress-root.tar.gz wp-config.php.recovery \
  protected-configs.tar.gz target.json file-attributes.json before.catalog.json before.rows.ndjson \
  after.catalog.json after.rows.ndjson before-files.sha256 after-files.sha256 \
  capture-start-utc.txt capture-end-utc.txt drift-review.json)
for CSF_FILE in "${CSF_ARTIFACTS[@]}"; do
  test -f "$CSF_BUNDLE/$CSF_FILE" && test ! -L "$CSF_BUNDLE/$CSF_FILE"
done
(cd "$CSF_BUNDLE"; sha256sum -- "${CSF_ARTIFACTS[@]}" > SHA256SUMS; sha256sum --check --strict SHA256SUMS)
sha256sum "$CSF_BUNDLE/SHA256SUMS" > "$CSF_BUNDLE/manifest.sha256"
```

`target.json` binds root/URL/DB/prefix/environment, source/command digests, versions and observed
plugin/table/engine state plus private before/after counter observations; `file-attributes.json` retains before/after source and protected-config
mode/owner/group/mtime observations; `drift-review.json` records all row/schema/file deltas and
review status. These are private inspection outputs, not invented static receipts. Missing
output or acknowledgment blocks release readiness. The receipt binds the manifest digest without
hashing itself circularly. Exact sandbox config copy operation for each privately approved
canonical path is `install -m 0600 -- "$CSF_APPROVED_CONFIG_SOURCE" "$CSF_BUNDLE/protected-configs/$CSF_APPROVED_CONFIG_NAME"`,
after validating regular/no-follow source, allowed sandbox vhost/pool path, unique basename and
new destination. Then archive only that private directory with tar; never substitute a parent tree.

### B4 — optional owner-bound encrypted off-server custody

Requires separately selected recipient/key fingerprint/destination/transfer identity. Already
installed encryption tooling only; no software installation/keyserver fetch/private-key import.
Verify the supplied public key's fingerprint against the private approval record first.

```bash
: "${CSF_PUBLIC_RECIPIENT:?approved public key file required}"
mkdir -m 0700 -- "$CSF_BUNDLE/encryption-work"
gpg --batch --no-tty --homedir "$CSF_BUNDLE/encryption-work" --with-colons \
  --show-keys --fingerprint "$CSF_PUBLIC_RECIPIENT" > "$CSF_BUNDLE/encryption-work/recipient-metadata"
python3 -I -B - "$CSF_BUNDLE/encryption-work/recipient-metadata" "${CSF_APPROVED_RECIPIENT_FP:?private approved fingerprint required}" <<'PY'
import pathlib,re,sys
rows=[line.split(':') for line in pathlib.Path(sys.argv[1]).read_text().splitlines()]
assert not any(row[0] in ['sec','ssb'] for row in rows),'Never stage a private recipient key on this server'
fingerprints=[row[9] for row in rows if row[0]=='fpr']
assert re.fullmatch(r'(?:[A-F0-9]{40}|[A-F0-9]{64})',sys.argv[2])
assert fingerprints and fingerprints[0]==sys.argv[2],'Unapproved recipient'
PY
tar -cf "$CSF_BUNDLE/offserver.tar" -C "$CSF_BUNDLE" "${CSF_ARTIFACTS[@]}" SHA256SUMS
gpg --batch --no-tty --no-auto-key-retrieve --homedir "$CSF_BUNDLE/encryption-work" \
  --recipient-file "$CSF_PUBLIC_RECIPIENT" --encrypt \
  --output "$CSF_BUNDLE/offserver.tar.gpg" "$CSF_BUNDLE/offserver.tar"
sha256sum "$CSF_BUNDLE/offserver.tar.gpg" > "$CSF_BUNDLE/offserver-ciphertext.sha256"
```

Owner-side transfer proposal, **not a farm-side upload to an unspecified provider**:
`scp -p "$CSF_APPROVED_SSH_ALIAS:$CSF_EXACT_CIPHERTEXT_PATH" "$CSF_APPROVED_VAULT_PATH"`.
Use verified existing SSH host identity and owner-approved destination; values remain private.
Verify ciphertext hash there, decrypt with the off-server custodian's key in private isolation,
check inner hashes/readability, record only the receipt. If inputs/tooling/custody are missing,
stop export and label **local backup only**. Never claim a full off-server functional restore
from checksum/decrypt success. All temporary plaintext/ciphertext stays0700/0600; any cleanup
is restricted to this approved attempt, not old/live data. No automatic live rollback.

## R-Restore command payload

Required private bindings after R-Backup: exact bundle and approved manifest digest, unique
recovery/run paths, existing verified tool paths/hashes, private clone-test identity selector
and explicit clone credential-test choice. The inspected pre-security baseline had no MFA
plugins; a post-MFA bundle requires its own reviewed recovery safety inputs, not a bypass.

### R1 — bind artifact and start isolated lifecycle

```bash
set -euo pipefail
set +x
umask 077
: "${CSF_EXACT_BUNDLE:?approved R-Backup absolute path required}"
: "${CSF_EXPECTED_MANIFEST_SHA256:?owner-bound receipt digest required}"
: "${CSF_RECOVERY:?new approved private recovery path required}"
: "${CSF_RUN:?new approved /tmp/csf-gate-b-restore-run-token path required}"
test -d "$CSF_EXACT_BUNDLE" && test ! -L "$CSF_EXACT_BUNDLE"
python3 -I -B - "$CSF_EXACT_BUNDLE" "$CSF_RECOVERY" "$CSF_RUN" <<'PY'
import pathlib,re,sys
parent='/var/www/vhosts/cornerstonefarmomak.com/private/'
for value,pattern in [(sys.argv[1],re.escape(parent)+r'csf-gate-b-backup-[a-f0-9]{32}'),(sys.argv[2],re.escape(parent)+r'csf-gate-b-recovery-[a-f0-9]{32}'),(sys.argv[3],r'/tmp/csf-gate-b-restore-[a-f0-9]{32}')]:
    assert re.fullmatch(pattern,value) and str(pathlib.Path(value).resolve())==value
bundle=pathlib.Path(sys.argv[1])
expected={'database.sql.gz','wp-content.tar.gz','wordpress-root.tar.gz','wp-config.php.recovery','protected-configs.tar.gz','target.json','file-attributes.json','before.catalog.json','before.rows.ndjson','after.catalog.json','after.rows.ndjson','before-files.sha256','after-files.sha256','capture-start-utc.txt','capture-end-utc.txt','drift-review.json'}
seen=set()
for line in (bundle/'SHA256SUMS').read_text().splitlines():
    digest,name=line.split(None,1)
    assert re.fullmatch('[a-f0-9]{64}',digest) and name in expected and name not in seen
    path=bundle/name
    assert path.is_file() and not path.is_symlink() and path.resolve().parent==bundle
    seen.add(name)
assert seen==expected
PY
CSF_ACTUAL_MANIFEST=$(sha256sum "$CSF_EXACT_BUNDLE/SHA256SUMS" | cut -d' ' -f1)
test "$CSF_ACTUAL_MANIFEST" = "$CSF_EXPECTED_MANIFEST_SHA256"
(cd "$CSF_EXACT_BUNDLE"; sha256sum --check --strict SHA256SUMS)
test ! -e "$CSF_RECOVERY" && mkdir -m 0700 -- "$CSF_RECOVERY"
test ! -e "$CSF_RUN" && mkdir -m 0700 -- "$CSF_RUN"
export CSF_EXACT_BUNDLE CSF_RECOVERY CSF_RUN
```

Validate canonical paths match the exact private approval and neither is a live/document-root,
historical recovery path or symlink. Validate fixed manifest filenames/no absolute or `..`
entries **before** checksum traversal; validate archive members before extraction. Bind only
this artifact read-only; no `latest`/wildcard selection or database import outside isolation.

Start child lifecycle with **`unshare --mount --net --pid --fork --kill-child=TERM --mount-proc /bin/bash`**
and the following reviewed namespace body as stdin. This is a dedicated process/namespace,
not a new global service/Plesk subscription. No shared process or unit is stopped/reloaded.

```bash
set -euo pipefail
set +x
umask 077
mount --make-rprivate /
mount --bind "$CSF_RECOVERY" "$CSF_RUN"
exec 3>&1
exec > "$CSF_RUN/operator-private.stdout" 2> "$CSF_RUN/operator-private.stderr"
mkdir -m 0700 "$CSF_RUN/bundle" "$CSF_RUN/site" "$CSF_RUN/datadir"
mount --bind "$CSF_EXACT_BUNDLE" "$CSF_RUN/bundle"
mount -o remount,bind,ro "$CSF_RUN/bundle"
mount -t tmpfs -o mode=000,nosuid,nodev,noexec tmpfs /var/www/vhosts
# Hide host DB/control sockets in this child only; the private workspace is under /tmp.
mount -t tmpfs -o mode=000,nosuid,nodev,noexec tmpfs /run
ip link set lo up
test -z "$(ip route show default)"
```

Only known existing mountpoints may be masked; if the server layout differs, abort/review—do
not create/mount over guessed global paths. The private recovery bind remains visible at CSF_RUN
after vhosts are hidden. Existing read-only tools needed from a hidden path must be bound by
their exact approved hash into the workspace **before** masking; no downloads/installations.

### R2 — private import and byte/data acceptance before rewriting

```bash
CSF_CLONE_SOCKET="$CSF_RUN/clone.sock"
CSF_CLONE_DATADIR="$CSF_RUN/datadir"
CSF_DB_PID= CSF_PHP_PID=
csf_stop_children() {
  for CSF_CHILD in "$CSF_PHP_PID" "$CSF_DB_PID"; do
    if [[ $CSF_CHILD =~ ^[0-9]+$ ]] && test "$CSF_CHILD" -gt 1; then
      kill "$CSF_CHILD" 2>/dev/null || true
      wait "$CSF_CHILD" 2>/dev/null || true
    fi
  done
}
trap csf_stop_children EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
/usr/bin/mariadb-install-db --no-defaults --datadir="$CSF_CLONE_DATADIR" \
  --auth-root-authentication-method=socket --skip-test-db > "$CSF_RUN/database-init.log" 2>&1
/usr/sbin/mariadbd --no-defaults --user="$(id -un)" --datadir="$CSF_CLONE_DATADIR" \
  --socket="$CSF_CLONE_SOCKET" --pid-file="$CSF_RUN/database.pid" --skip-networking \
  --event-scheduler=OFF --log-error="$CSF_RUN/database.log" &
CSF_DB_PID=$!
CSF_CLONE_ADMIN=(/usr/bin/mariadb --no-defaults --protocol=SOCKET --socket="$CSF_CLONE_SOCKET")
# Bounded readiness loop; each call names the private socket. Never try a default/live socket.
for CSF_TRY in $(seq 1 30); do
  if "${CSF_CLONE_ADMIN[@]}" --execute='SELECT 1' > /dev/null 2>&1; then break; fi
  test "$CSF_TRY" -lt 30 || exit 1
  sleep 1
done
"${CSF_CLONE_ADMIN[@]}" --execute='CREATE DATABASE wp_mdf4x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
# Create an ephemeral SQL service identity on this private daemon only, scoped to this DB.
python3 -I -B - "$CSF_CLONE_SOCKET" "$CSF_RUN/import-client.cnf" <<'PY'
import os,secrets,subprocess,sys
socket,path=sys.argv[1:];password=secrets.token_hex(32)
sql="CREATE USER 'csf_restore_import'@'localhost' IDENTIFIED BY '"+password+"';GRANT ALL PRIVILEGES ON wp_mdf4x.* TO 'csf_restore_import'@'localhost';"
r=subprocess.run(['/usr/bin/mariadb','--no-defaults','--protocol=SOCKET','--socket='+socket],input=sql,text=True,capture_output=True)
assert r.returncode==0,'Private scoped import identity creation failed'
fd=os.open(path,os.O_CREAT|os.O_EXCL|os.O_WRONLY|os.O_NOFOLLOW,0o600)
with os.fdopen(fd,'w') as out:out.write('[client]\nuser=csf_restore_import\npassword='+password+'\nprotocol=SOCKET\nsocket='+socket+'\n')
PY
CSF_CLONE_MYSQL=(/usr/bin/mariadb --defaults-file="$CSF_RUN/import-client.cnf" --protocol=SOCKET --socket="$CSF_CLONE_SOCKET")
gzip -cd "$CSF_RUN/bundle/database.sql.gz" | "${CSF_CLONE_MYSQL[@]}" --database=wp_mdf4x
/usr/bin/mariadb-check --defaults-file="$CSF_RUN/import-client.cnf" --protocol=SOCKET --socket="$CSF_CLONE_SOCKET" \
  --check wp_mdf4x > "$CSF_RUN/database-integrity.log" 2>&1
tar --extract --gzip --no-same-owner -f "$CSF_RUN/bundle/wordpress-root.tar.gz" -C "$CSF_RUN/site"
tar --extract --gzip --no-same-owner -f "$CSF_RUN/bundle/wp-content.tar.gz" -C "$CSF_RUN/site"
install -m 0600 "$CSF_RUN/bundle/wp-config.php.recovery" "$CSF_RUN/site/wp-config.php"
(cd "$CSF_RUN/site"; sha256sum --check --strict "$CSF_RUN/bundle/before-files.sha256") > "$CSF_RUN/file-integrity.log" 2>&1
```

Import requires previously reviewed dump target statements (no database switches/unknown
definers/events). Check isolated datadir/socket identity, source isolation, table/physical schema,
row counts and complete artifact-derived baseline now, **before WordPress/URL/credential changes**.
Capture it with the same reviewed SELECT plan on CSF_CLONE_MYSQL, origin bound to the input
manifest digest. Retain source and artifact baseline differences, not a false shared-snapshot pass.
No actual database name changes on the host instance; the identical name is inside another daemon.

### R3 — clone-only functional acceptance and guaranteed stop

After integrity, write a clone-only0600 config for the private socket, loopback URL, fresh temporary
clone salts, no live service credentials, local environment and disabled **clone** automatic cron.
The literal live config is never executed. Preserve any approved MFA policy; if this bundle has
MFA installed, stop for the reviewed post-MFA clone protocol rather than dropping it. Disable
sendmail via PHP `-d sendmail_path=/bin/false`, keep the network namespace route-free and do not
forward host mail/agent/database sockets. No provider/inquiry/emergency activation is authorized.

Proposed existing binary invocation, only after the approved clone config/phase validation:

```bash
/opt/plesk/php/8.3/bin/php -d sendmail_path=/bin/false -d auto_prepend_file= -d auto_append_file= \
  -S 127.0.0.1:8899 -t "$CSF_RUN/site" > "$CSF_RUN/http-private.log" 2>&1 &
CSF_PHP_PID=$!
```
Use an explicitly reviewed static/directory router if required (including `/wp-admin/`); do not
count a front-page redirect as an authenticated dashboard. Record its PID privately and install
EXIT/TERM traps **before starting either daemon**, stopping only recorded child PIDs and waiting
for their exits. A process-start failure also tears down the PID namespace; never use global pkill.

Approved clone-credential test uses the existing WP-CLI hash-bound tool with
`--path="$CSF_RUN/site"`, inside this isolation only. Load the private cloned-account selector
and generated temporary password from a0600 runtime file, not CLI arguments or chat. Save its
cloned original password/activation state privately, perform the approved clone-only update,
native loopback login/dashboard/logout, restore cloned hash and delete test sessions. No real
password/factor is requested and no new staff identity is created. If this private binding or
scope is absent, login is blocked/not tested, **not passed**. Keep cookies/HTML private and avoid
redirect following outside the loopback origin.

Check Home/Lessons/Contact rendering on loopback, source guards and intended clone deltas; no
outbound email/API. Stop recorded PHP/MariaDB children and prove no drill listener/process remains.
Retain private report/logs, remove only approved temporary secret files, and leave failed data
restricted for review. A body not bound to the exact private clone config/identity/router/tool
inputs must not start. This command catalogue is not a claim those bindings or tests already exist.
R-Restore never imports/configures/restores anything in the protected sandbox.

Private clone-selector handoff, if that test is approved, uses the existing typed operation
`write_secret_file(server=<canonical server>, content_from="gate-b-approved-clone-test-selector",
path=<exact approved CSF_RUN>/clone-selector.json, mode="0600", privilege="none")`.
The selector contains only the privately approved cloned UID/test scope; no live password or
factor is collected. No such value/file is requested or created by this preparation. The
run-specific clone config/credential-test/router/tool input bodies and their digests must be
privately reviewed before R-Restore execution; these cannot be assumed from an old GateA job.
