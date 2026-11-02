# Sprint #11 — Gate A backup and functional recovery

**Gate A passed November 1, 2026, Pacific Standard Time. Stopped after recovery. Gate B remains unauthorized.** The owner explicitly approved only a fresh protected-sandbox backup and private isolated functional restore. No scheduling installation/activation, sandbox schema migration, WordPress account creation, permission grants or pilot records occurred. Production, inquiry settings, emergency management and public content were untouched.

## Fresh target-bound recovery bundle

Target: `https://sandbox.cornerstonefarmomak.com`, root `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`, database **wp_mdf4x**, prefix **TXGEh_**. Literal configuration, connected DB, home/siteurl and development environment were checked before capture. Live WordPress was not bootstrapped for backup or final comparisons.

Private bundle:
`/var/www/vhosts/cornerstonefarmomak.com/private/csf-gate-a-20261102-004845-8d96d856/`

Capture: **November 1, 16:48:45–16:48:54 PST** / November 2, 00:48:45–00:48:54 UTC. Database export used single-transaction/quick mode without table locks; all 12 source tables are InnoDB. Credentials were used only inside the server process/private temporary client file, never returned through Gateway or committed. That temporary client file was removed.

| Artifact | Bytes | SHA-256 |
| --- | ---: | --- |
| database.sql.gz | 58,361 | `5ea84a96f4b01d155c842be36d1da704a8ea6929bba4a7c04e41d5ff7c7c0f86` |
| wp-content.tar.gz | 18,167,953 | `cc8f4586133e7f74ae5ba6046c2e8c4245fd750f9d7205416699f3c783ebc090` |
| wordpress-root.tar.gz | 21,991,905 | `b4979639c3a8ec29194535aae77407c3115f0636135afb78a850ff1a8dac21d3` |

The supplementary root archive contains the remaining installed WordPress files, excluding wp-content and wp-config. Exact current wp-config is separately preserved as **wp-config.php.recovery**, with restricted permissions; its contents remain private. Target/version/plugin metadata, per-file hashes and per-table row fingerprints are retained privately. Six standard backup checksums and 12 recovery checksums passed; compressed export and safe archive-member validation passed. No symlink/nonregular source entries were accepted.

Backup directory is root-owned **0700**, artifacts **0600**. This is a verified local recovery bundle. **No encrypted off-server transfer or custody test was performed**, and this result does not establish full disaster-recovery readiness. Historical October31 backup/restore resources were preserved unchanged.

## Isolated restoration and actual results

Private recovery directory:
`/var/www/vhosts/cornerstonefarmomak.com/private/csf-gate-a-recovery-20261102-005054-a6b56e84/`

The drill used a new private filesystem and separate MariaDB10.6.23 process/datadir, not a new database in the live MariaDB service. Its database `csf_gate_a_restore` existed only in that private instance. MariaDB had TCP disabled and a dedicated Unix socket. A scoped application credential belonged only to the isolated instance; no sandbox DB privileges or WordPress permissions were changed.

A private network/mount/PID namespace had **loopback only and no external route**. Live sandbox/production document roots and the host MariaDB socket directory were masked only inside that private mount namespace; other host processes retained their original mounts. WordPress used a generated clone-only config with the private DB socket, new authentication salts, local URLs, disabled cron/updates, external HTTP blocked and PHP mail disabled. Restored config was hash-verified before this deliberate clone configuration replacement. Backup configuration remained intact.

| Check | Observed outcome |
| --- | --- |
| File recovery | **4,479/4,479 regular-file hashes match** the source, including configuration before isolation changes |
| DB recovery | **12/12 tables**, InnoDB; MariaDB integrity checks all OK |
| Row integrity | Every table's row count and sorted row-data hash matches the captured source before clone-only URL/auth changes |
| Login | Actual login form POST accepted a random temporary password for the existing cloned administrator; authenticated cookie received |
| Dashboard | Authenticated `/wp-admin/` rendered successfully |
| Home | HTTP200, 121,889 bytes, Kadence output |
| Lessons | HTTP200, 96,118 bytes, Kadence output and Contact Beth to Schedule |
| Contact | HTTP200, 75,426 bytes, Kadence output; contact-scheduling CTA present and inquiry form absent |
| Static asset | Kadence stylesheet served successfully |
| State safeguards | Only csf-core active, inquiry disabled, emergency inactive, zero scheduling tables and no accounts created |
| Teardown | Temporary cloned password reverted to the original captured hash, test sessions removed; PHP/MariaDB processes stopped; no recovery processes remain |

Completion: **November 1, 16:56:27 PST** / November 2 00:56:27 UTC. Recovery directory is now **0700**; it is not publicly served and no drill service remains running. Recovery files/data/logs are retained privately for inspection, not deleted. Successful evidence is in both `recovery-evidence.json` in the recovery directory and `functional-restore-evidence.json` in the backup bundle.

The login drill changed only an existing cloned account's password temporarily. It did not create any account, reveal or validate the live administrator's password, resolve Beth/Kris identities, prove MFA or grant scheduling access. Clone-only URL rewrites and authentication/session changes happened after exact database recovery was verified.

## Failures and corrections

The first backup attempt stopped at the DB-host guard before creating any bundle: actual local host configuration includes `localhost:3306`. The corrected exact local-host/port handling then captured the successful bundle above. It did not relax target DB/root/URL checks.

The first restore passed file/database integrity, then a WP-CLI invocation failed. An isolated retry with explicit CLI configuration/cache locations succeeded. A later dashboard assertion followed a public-home response: the fixture's PHP router was incorrectly routing directory URLs, including `/wp-admin/`, through the front controller. The router was corrected to serve existing directories normally. A discarded controller revision also had a quoting/syntax error before it could execute. These unsuccessful attempts are preserved by their Gateway job IDs; none is counted as a passed recovery.

The successful final run restored the same bundle again within the same new private recovery resource, repeated the integrity checks, and passed login/dashboard/page checks. No historical scratch resource was recreated, and no live DB restore was attempted.

## Final live-site and rollback checks

At **November 1, 16:57:37 PST** / November 2 00:57:37 UTC:

- All **12 live table fingerprints** still match the pre-backup snapshot.
- All **4,479 live file hashes and the file inventory** still match the pre-backup snapshot, including wp-config and the installed core/emergency plugins.
- Only csf-core remains active; inquiry option remains absent with the verified disabled default; scheduling directory remains absent and scheduling tables remain zero. Emergency remains inactive.
- Live config remains 0600; backup/config recovery and private recovery modes remain restricted.
- Existing `scripts/verify-dev-backup.sh` passed against this exact bundle/root/URL and the new successful functional-restore evidence; checksum set, target binding, readable archives and under 24-hour freshness passed.
- No recovery service remains running. No live database import, production operation or website file/content change occurred.

This demonstrates matched-backup recovery into isolation and current rollback readiness, **not an executed rollback of the sandbox**. A future restore must first preserve/reconcile edits made after capture and use the reviewed exact target/config/files/database pair. The under 24-hour backup rule does not permit overwriting later owner changes; refresh backup/evidence as needed before any separately approved release.

## Limits and next gate

Functional HTTP tests used the installed PHP 8.3.36 built-in server with restored WordPress 7.1.4/Kadence 1.5.2, not a restored Plesk/Nginx/Apache/FPM configuration. They establish application login/dashboard/representative rendering, not visual/device/accessibility acceptance, reverse-proxy/Basic Auth/cache behavior, MFA, delivered email or scheduling acceptance. No scheduling code was installed in either the live sandbox or this recovery clone. The scheduling pin/tree remain unchanged.

**Gate B is still on hold.** Before a separate approval: owner-verifies distinct Beth/Kris account identities and numeric operator mapping, effective MFA and permission scope, and approves/revises the fictional pilot policy. Account creation or MFA infrastructure changes require separately defined scope. Real appointments/customer information, production, public booking, inquiry enablement and emergency management remain excluded. Beth/Kris hands-on calendar acceptance has not occurred.

## Evidence and report validation

[Machine-readable sanitized evidence](../pilot/gate-a-evidence.json) records successful outcomes, limitations and Gateway job IDs. Successful backup: `job_01M6CVNHM600FZ8M2P8091XHM3`; functional restore: `job_01M6CW36Z900CTWJH46TP6BDQ6`; final safeguards/backup verification: `job_01M6CW5GCY00A4DH2NQ4187ZAJ`. All are terminal successful jobs, and the internal result statuses are also verified/passed (not inferred merely from wrapper exit codes).

This is a documentation/evidence-only repository change. Scheduling plugin tree remains `893edbe31278946aa2aaa830a56d5d1812b87323`, source pin `6f5a9bf51747399a309b39637a135841fe4cf2a1`. JSON/whitespace and local guard checks validate the report change; prior scheduling regression/browser suites are historical and were not rerun for Gate A.
