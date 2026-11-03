# R-Restore — isolated functional recovery of one exact bundle

**Decision requested: Approve / Reject / Revise / Hold. Current status: HELD.**
R-Backup success does not approve restoration. This packet never approves live rollback,
MFA/provider installation, real account/factor changes or a Plesk-managed clone subscription.

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
