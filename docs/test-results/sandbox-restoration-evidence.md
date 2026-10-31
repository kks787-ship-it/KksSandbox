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
- Restore destination: a new isolated scratch database and restored filesystem, per Gateway.
  Exact existing scratch DB/path names were not forwarded; record them from the completed
  Gateway operation before operator acceptance. Do not create a replacement drill to obtain names.
- Production DB `wp_iacb7` and existing unassigned DB `wp_mdoaq` excluded; no production changes.

## Reported completed checks

| Check | Result |
|---|---|
| Full sandbox filesystem/config and DB backup | Complete |
| Archive and checksum verification | Passed |
| Isolated scratch database restore | Completed |
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

## Final private operator record — populate from existing records, not guesses

Before passing an evidence file to the deployment verifier, the operator should retain:

- The completed backup job identifier/precise capture timestamp and exact dump/archive paths.
- Actual source artifact SHA-256 values and the successful verification output.
- Exact **existing** scratch DB and filesystem path, restore timestamps, table checks/counts
  and file-verification output.
- Operator identity/review time and this unchanged functional-test qualification.
- Binding to the current source root, URL and backup artifacts to be used for rollback.

Those raw identifiers/hashes were not supplied here. Their absence from this chat is a
record-completion task, not evidence that the Gateway checks failed or never happened.
Never fabricate values, mark functional tests passed, expose database credentials or modify
the source backup. Preserve the original separate pre-hardening wp-config backup at
`/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak` as configuration-only evidence.

## What the script verifies

scripts/verify-dev-backup.sh checks target path/URL, six fixed checksum entries, compressed
archive integrity/nonempty SQL, archive age at most 24 hours, backup outside the site and a
nonempty evidence file. It does **not** interpret the evidence content or prove login/rendering.
This document is a truthful prepared evidence summary; the actual remote artifact gate has
not been executed in this task. See [the canonical gate procedure](../05-backup-rollback-deploy.md#backups-and-verified-backup-gate-before-any-apply).
