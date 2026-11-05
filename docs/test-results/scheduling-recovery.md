# Scheduling recovery qualification — November 5, 2026

Local engineering for Issue #11. Application source is unchanged from `145e1ff`; installed
implementation pin `2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1` remains unchanged.
No Gateway server connection, protected backup/restore, installation, migration, account change,
MFA change, permission grant, notification or production operation was performed.

## Exact contract and outcomes

The shared SHA-pinned generator permits all12 native WordPress tables and only the15 reviewed
scheduling schema-v3 names. Every present allowed table is captured; unknown names, missing native
tables, wrong prefixes, non-InnoDB tables and ambiguous primary keys stop. Old12-table catalogs
and receipts remain supported within their original evidence scope. New captures preserve
constraint/reference/check metadata, schema marker and deterministic row/index/schema baselines.

| Synthetic source | Tables | Required isolated outcome |
|---|---:|---|
| Pre-scheduling |12| Verified functional recovery |
| Healthy schema3 |27| Verified functional recovery and application schema inspection |
| Incomplete migration, version0 |20| Data preserved; `SCHEMA_STATE_REQUIRES_REVIEW`, not recovery PASS |
| Version3 marker with missing authorization table |26| Data preserved; `SCHEMA_STATE_REQUIRES_REVIEW`, not recovery PASS |

Each capture must match its explicit `expected_phase`. In particular, a post-migration capture
expecting `scheduling_v3` rejects disappearance of all15 scheduling tables rather than treating
that loss as a healthy pre-installation state. Phase labels describe observed shape/marker,
not the cause or writer of a failure. Partial captures are forensic integrity evidence only.
Fresh routine captures accept an explicit32-hex private `run_token`; existing destinations cannot
be reused or overwritten. Older command bindings are not automatic authorization for new code.

## Qualification evidence

The committed [evidence inventory](scheduling-recovery-results.json) records exact source and
log hashes, environments and counts. Raw local logs stay outside the public repository.
The two offline runs use synthetic WordPress7.1.4, official Two-Factor0.17.0, the existing adapter,
Scheduling0.4.1, MariaDB10.6.28, and PHP8.1/PHP8.3.36. No live data or credentials enter the fixtures.
Each run exercises all four states above through actual dump, archive, import and native HTTP.

The full27-table fixture includes records in every scheduling table, confirmed/canceled
appointments, assignments/participants/turns, welfare/availability records, authorization and audit
history. This is storage-fidelity data, not a new booking-engine acceptance test. Exact physical
catalog/row hashes and counts must match before any clone rewrite. Synthetic foreign-key and CHECK
constraints test constraint preservation; five logical appointment/resource/rider joins reject
orphans. Full-v3 recovery also requires the plugin's existing schema inspection to report ready.

Each phase verifies Home/Lessons/Contact, native administrator login/dashboard/logout, isolation,
service termination and credential-file cleanup. MFA-enabled fixtures complete the real native
TOTP challenge inside the isolated clone. The ephemeral TOTP is computed there from the restored
factor; it is never included in returned output or exported outside isolation. Existing anti-replay state is respected with a bounded wait.
Seeds and recovery codes are not regenerated or consumed. Original cloned password/activation key
and all usermeta rows, including NULL values, factor data, recovery hashes, session state and row
identities, are restored exactly afterward. Source-side newer owner notes and authentication epoch
changes, inserted after capture, remain untouched and independently verified.

Negative drills reject manifest mismatch, truncated gzip and unexpected SQL before clone creation;
a real duplicate-row import failure stops/reaps clone services. Every phase forces a short deadline
and verifies no orphan clone DB/PHP process remains. Completed restore destinations cannot be
reused. Stale snapshots are reported as not fresh: an isolated historical recovery exercise is
allowed, but cannot satisfy the under24hour deployment prerequisite. Unit coverage additionally
checks archive traversal/PAX/GNU/link/special/collision guards, malformed SQL SET/INSERT/ALTER,
missing/extra tables, marker/constraint drift, phase mismatch, incomplete receipts, credential
custody, interrupted work, disk limits and default-deny paths. Drift remains critical and
unattributed without evidence.

## Scope and limitations

The qualified contained database runtime is MariaDB10.6. Non-MariaDB sources stop before clone
writes; this task does not qualify a MySQL-source restore or cross-engine conversion. Earlier
MySQL calendar tests are separate evidence. The farm's exact patch level, Plesk/Kadence, Basic Auth,
reverse proxy, real enrolled-account clone login and device acceptance still need the protected
execution window. Local recovery fidelity is not Beth/Kris operational acceptance.

Legacy12-table snapshots lacking new constraint descriptors retain their older evidence scope;
no missing historical evidence is invented. New dumps/baselines are separate MVCC observations,
not an atomic filesystem/database snapshot. Any observed drift requires review. No automatic
restoration may overwrite newer owner, MFA or calendar changes.

The runner uses offline Docker, ephemeral private namespaces, fixed software images and bounded
memory-backed scratch filesystems. Original production disk guards were retained. Preliminary
fixture runs exposed an existing-directory assumption, insufficient local disk headroom, provider
class initialization, and read-only backup mount alias binding. These were corrected locally;
failed preliminary runs are excluded from final qualification. Unused local builder cache was
pruned; no server infrastructure was changed.

## Next protected milestone

Use the [exact rollout and rollback sequence](../pilot/calendar-activation-readiness.md#exact-installation-and-rollback-sequence-after-separate-execution-authorization).
It requires fresh pre-change capture/isolated recovery, the two-file0.4.1 update, explicit guarded
15-table migration, then a fresh27-table capture/restore before expanding pilot data. Bind all
private commands to current release manifests and the exact target immediately before execution.
Retain30minute job limits, disk reserves, strict source scope and sanitized output.

The initial one-use Farm Owner bootstrap is a separate explicit security decision granting all20
farm permissions. Employee enrollments/grants and mandatory enforcement remain held. Inquiry
stays disabled, emergency inactive, public booking absent and production untouched.
