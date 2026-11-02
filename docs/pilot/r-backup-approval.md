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
