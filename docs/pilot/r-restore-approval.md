# R-Restore — isolated functional recovery of one exact bundle

**November5 local candidate update:** the table contract now supports exact native12 plus
schema-v3 scheduling15 (27 total), with explicit expected phases and review-required partial
states. The old execution bindings/approval below are historical; they do not authorize the
new payload or a live operation. Use the current SHA manifests and the
[qualified rollout sequence](calendar-activation-readiness.md#exact-installation-and-rollback-sequence-after-separate-execution-authorization).
[Local evidence and limits](../test-results/scheduling-recovery.md) distinguish recovery fidelity
from operational acceptance. A new clone login uses its restored TOTP only inside isolation,
restores all original metadata and never regenerates factors or codes.


**Decision requested: Approve / Reject / Revise / Hold. Current status: HELD.**
R-Backup success does not approve restoration. This packet never approves live rollback,
MFA/provider installation, real account/factor changes or a Plesk-managed clone subscription.

## Current owner authorization card — one private functional restore

**Preparation authorized; execution HELD.** This supersedes the historical command fragments.
The exact completed capture, receipt/manifest fingerprints and one new destination are bound
in the owner-private recovery record; they are not published in GitHub. Do not select “latest”.

| Decision | Proposed scope |
| --- | --- |
| R-Restore | One restore of that exact verified local capture on the designated sandbox host; no live vhost or production writes |
| Executable | [restore_local.py](../../scripts/recovery/restore_local.py), built by [build_local_restore.py](../../scripts/recovery/build_local_restore.py); exact public payload and command-template fingerprints in [LOCAL-RESTORE.sha256](../../scripts/recovery/LOCAL-RESTORE.sha256). Template has no private binding and cannot execute |
| Private command | Owner-private command envelope binds the exact receipt, manifest and exclusive recovery directory. SHA-verify it immediately before Gateway submission with `sensitive_command=true`; never paste its bytes/bindings into GitHub or ordinary output |
| Privilege | Existing authorized SSH identity, `privilege=none`, no sudo/account/security changes. Trusted coordinator creates ephemeral mount/network/PID namespaces; PHP/MariaDB always chrooted to the new recovery directory, existing distinct nonroot system identities, zero capabilities, no-new-privileges |
| Permitted reads | Only that backup plus fixed installed software/library trees and own-worker process metadata; no live SQL connection, live WordPress execution, account enumeration or protected-config discovery |
| New writes | One private0700 recovery directory,0600 raw artifacts/reports/logs; clone-only files/database/config. Read-only software/input mounts disappear with the namespace. No shared mounts, services, ports or Plesk/vhost changes |
| Containment | New private datadir/socket; explicit socket/defaults on every client command, no TCP/event scheduler/local-infile; loopback HTTP inside private network only, no external routes. Mail/process-spawn disabled in PHP; clone-only outbound HTTP/mail guards and cron/update suppression |
| Clone authentication | **Explicitly approve a temporary random password for exactly one existing restored administrator**, solely in the clone; test native login/dashboard/logout, restore original cloned hash/activation/session rows, remove temporary credential/config files. Never use a live password/factor, infer human ownership or create an account. Ambiguous/unsupported security state stops |
| Bounds | Gateway1800s; inner1770s+30s kill grace within one active UTC window<=30min. Retain>=2GiB free space; preflight headroom>=4GiB+5×source file bytes; owned working files8GiB checked once/second, native process file limit4GiB, SQL spool<2GiB. Every stream/process is bounded |
| Acceptance | All source receipt/artifact hashes; safe archives/restored bytes;12-table full SQL import/check/row counts/catalog and artifact-derived complete baseline before clone rewrites; Home/Lessons/Contact; native cloned login/dashboard/logout; source re-verification and exact-child termination |
| Cleanup/retention | pidfd-fenced PID-namespace worker, only own recorded daemons, TERM then bounded KILL/reap. Restricted incomplete marker remains on interruption; no incomplete/partial pass. Retain private evidence/datadir for review, remove temporary plaintext clone credentials/config; no transfer or automatic live rollback |

**Public payload SHA-256:** `85a99b6722464cd6af06e7f9c1b217d4929aba03c5bf73a183b157b937073007`. The exact sensitive envelope SHA is retained
in the owner-private release record, independently checked before submission; it is not the
public template hash. Changing code/binding requires a rebuilt, reviewed envelope.

**Approve / Reject / Hold:** “Approve one R-Restore as scoped above, including the temporary
clone-only administrator password/session test.” Codex supplies non-secret operator/approval
references and the exact execution UTC window after approval. Skipping authentication is possible
only as an explicitly partial exercise and cannot satisfy functional recovery readiness.

**Automatic stop:** changed binding/hash/target, existing destination, unknown active plugin/MFA/
MU/drop-in/multisite security state, unsafe SQL/archive, absent tools/capabilities, insufficient
space, namespace/privilege/network failure, import/integrity/rendering/authentication failure,
source change, timeout or uncertain cleanup. No weakened security guard or automatic repeat of
this write operation. Report the fixed diagnostic code and retained private evidence.

**Limits:** this is application recovery on installed PHP/MariaDB, not full Plesk/Toolkit,
HTTPS/BasicAuth/reverse-proxy/MFA, off-server, staff usability or operational acceptance.
Afterward stop and report; do not silently proceed to GateB. Production, accounts/security/MFA,
public transactions, inquiry and emergency management remain outside this approval.

## Detailed acceptance contract

The executor uses separately approved infrastructure authority, not a prerequisite Farm Master
or new WordPress MFA account. A temporary cloned login proves recovery functionality only;
it does not establish the real account controller or satisfy later private identity/MFA gates.

| Binding | Exact proposed scope / required owner input |
| --- | --- |
| Input | Completed capture input and exact manifest/receipt bindings are available in the owner-private recovery record; identifiers are not published here. **Restore remains HELD**. Do not substitute historical recovery sets |
| Original target | Sandbox URL/root/`wp_mdf4x`/`TXGEh_` as in R-Backup; production excluded |
| Output | One new private0700 recovery directory `/var/www/vhosts/cornerstonefarmomak.com/private/csf-gate-b-recovery-<approved-run-token>`;0600 artifacts/logs, no overwrite/reuse of historical drills |
| Isolation | Private mount/network/PID namespace; no external route; loopback HTTP only; dedicated MariaDB datadir/socket/process and no live DB credentials/socket access |
| Versions | Existing installed PHP8.3.36/MariaDB binaries only after local-path verification; no package install, service restart, DNS/firewall/Plesk configuration change |
| Functional test identity | Privately identify the already restored technical account. Explicitly approve temporary **clone-only** password/session test, then restore its cloned original hash/remove test sessions. No new real account or collection/use of a live password/factor |
| Mail/cron | Clone-only safety config after integrity checks: outbound routes absent, sendmail disabled, external services unavailable and automatic cron disabled **in the clone only**. No live setting altered to force hashes |

**Required before execution:** exact receipt/bundle/manifest binding, private operator/recovery
custodian, verified available disk/namespace tools, new recovery/run paths, cloned identity test
choice and explicit approval for clone-only changes. Without temporary clone credential authority,
record login as **not tested**, not passed. If the captured plugin/MFA/transport state differs from
the reviewed pre-security baseline, stop and revise the safety/recovery test; never skip security
plugins or reuse a live factor to make a login pass.

**Proposed operations:** [canonical isolation/restore commands](../05-backup-rollback-deploy.md#r-restore-proposed-command-catalogue).
Read-only bind only the approved bundle into the isolated workspace; verify all artifacts before
extraction. Restore privately into an empty target, never a live vhost. Initialize only the
dedicated MariaDB instance, event scheduler off/no TCP, explicit private socket on **every** DB
command. Mask live vhosts/DB sockets in the child namespace; do not stop shared services.

**Ordered acceptance:**

1. Check exact input manifest digest, individual hashes and safe archive members; reject traversal,
   absolute/duplicate paths, links/nonregular entries and unexpected database-switch/definer/event
   statements. Review any stored routines/triggers/events before execution; no silent omissions.
2. Import only into the dedicated isolated DB. Verify12 expected tables/engines/columns/indexes
   for the reviewed pre-install phase; run scoped integrity checks and record the **artifact-derived**
   complete per-row/option baseline before any clone-only URL/auth/config rewrite. Compare backup
   schema/metadata and row counts; distinguish independently captured live diagnostic hashes from
   the dump's MVCC instant. Do not demand false equality across different observation instants.
3. Compare every restored file to the captured manifest, wp-config/salts/protected-config artifact
   hashes/modes before modifications. Verify all required secrets are readable privately without
   displaying them. Restoring config archives as files does not prove a working Plesk/BasicAuth stack.
4. Create clone-only config bound to the private socket/DB and loopback URL; do not execute the
   unmodified live config. Preserve the original captured files/evidence. Apply approved mail/cron
   isolation, then representative Home/Lessons/Contact rendering, admin login/dashboard and logout.
   Never use public farm endpoints, send messages or mark real identities/MFA as verified.
5. Recheck namespace/no external route, source safeguards and intended clone-only changes. Restore
   temporary cloned hash/remove test sessions, stop only recorded drill processes and verify no
   recovery listener/daemon remains. Retain private artifacts/evidence, not a public running site.

**Pass:** integrity and source guards, clone login/dashboard/pages, config-secret recoverability
and stopped-service evidence all pass. **Partial/blocked:** missing private input, unproven isolation,
unapproved clone identity test, absent tools or changed captured state. **Fail:** target/hash/data/
integrity/leakage/cleanup failure. Never claim Plesk Toolkit, MFA, mobile or off-server functional
recovery from this PHP application rehearsal; those need their separate scopes/tests.

**Abort/rollback:** terminate only this namespace's recorded processes, retain its private failed
evidence, remove only its approved temporary secrets. No live import/config restore, account
reset, lost-code revival, database drop, stale options replacement or shared service shutdown.
A proposed live recovery later needs separate X-Restore authorization, intervening-change
reconciliation and current approved authentication/revocation safeguards.

**Owner authorization record:** exact bundle/manifest/recovery paths, run/window/operator, clone
credential-test choice and decision/date. Blank is Hold. **Stop after the recovery report.** No
account-security, Toolkit rehearsal, calendar installation or later Gate B step follows automatically.
