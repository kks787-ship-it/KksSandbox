# Sandbox restoration evidence — recorded Gateway result

This records the owner's supplied Gateway report dated **October 31, 2026**. It is evidence
of the checks reported below, not a new restore, operator signature or assertion that the
final backup gate passed. Do not use an unreviewed copy of this report as a shortcut around
artifact checks or execution approval.

## Bound source and target

- Source site: https://sandbox.cornerstonefarmomak.com.
- Source root: `/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`.
- Source database: `wp_mdf4x` (options `TXGEh_options`).
- Completed full-backup directory:
  `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/`.
- Database export capture: **October 31, 2026, 10:08:58 AM PDT** (17:08:58 UTC).
- Full file archive capture: **October 31, 2026, 10:09:42 AM PDT** (17:09:42 UTC).
  Includes current hardened wp-config and complete sandbox files.
- Existing scratch database: **csf_sandbox_restore_20261031**; **12/12 tables**, all InnoDB.
- Existing scratch filesystem: private and not publicly served; exact path was not forwarded.
  Recover that path from the completed Gateway record; do not recreate the restoration.
- Existing private evidence file:
  `/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt`.
  Reuse/review this file rather than scheduling a new evidence-writing or restore task.
- Production DB `wp_iacb7` and existing unassigned DB `wp_mdoaq` excluded; no production changes.

## Reported completed checks

| Check | Result |
|---|---|
| Full sandbox filesystem/config and DB backup | Complete |
| Archive and checksum verification | Passed |
| Isolated scratch database restore | Completed in `csf_sandbox_restore_20261031` |
| InnoDB table verification | **12 tables** verified |
| Representative record counts | Verified; exact per-table counts not forwarded |
| Restored regular files | **4,336** |
| Restored file checksum mismatches | **0** |
| Functional WordPress login on restoration | **NOT RUN** |
| Functional page rendering on restoration | **NOT RUN** |

The completed result is **structural and file-integrity recovery verification**. Functional
application recovery remains untested. Retain that limitation explicitly in operator evidence
and installation review. This report neither clears activation/site-setup approval nor labels
the existing restoration as wholly unperformed.

## Existing private operator record — reuse and review

Gateway supplied the existing evidence path above. Before passing that file to the verifier,
review its contents and bind it to the actual backup bundle used for rollback. Keep its
functional-test-not-run qualification. Capture timestamps and scratch DB name are now known;
do not request or recreate them again. Raw SQL/archive filenames, actual SHA-256 values,
the private scratch filesystem path, detailed per-table counts and operator identity were
not forwarded here; recover those from the existing evidence/Gateway records if needed.
Do not invent values or create a replacement restoration merely to fill a documentation gap.
Never fabricate values, mark functional tests passed, expose database credentials or modify
the source backup. Preserve the original separate pre-hardening wp-config backup at
`/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak` as configuration-only evidence.

## What the script verifies

scripts/verify-dev-backup.sh checks target path/URL, six fixed checksum entries, compressed
archive integrity/nonempty SQL, archive age at most 24 hours, backup outside the site and a
nonempty evidence file. It does **not** interpret the evidence content or prove login/rendering.
The reported DB capture reaches 24 hours on **November 1, 2026 at 17:08:58 UTC /
9:08:58 AM PST**. Daylight saving ends overnight; do not treat 10:08:58 AM local time the
next day as only 24 elapsed hours. Actual bundle metadata/age must still be checked at use.

This is a truthful record of the supplied execution evidence; the private file has not been
read or recreated here, and the remote script-specific artifact gate has not been executed
by this task. See [the canonical gate procedure](../05-backup-rollback-deploy.md#backups-and-verified-backup-gate-before-any-apply).
