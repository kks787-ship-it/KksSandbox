# Security and operational boundaries

The current emergency system is an MVP, not an operational alert or dispatch service.
See [ranked findings](10-takeover-audit.md) and [emergency acceptance checklist](04-emergency-plugin.md).

## Trust boundaries

Public marketing data and unlisted emergency information must remain separate from future
private portal data. Unlisted URLs are publicly readable. Only owner-approved emergency
fields belong there. Uploads/media URLs are public unless a separate access-control design
protects them; an unpublished post does not make an attachment confidential.

Emergency writes require explicit capabilities and WordPress nonces at HTTP entry points.
Internal PHP APIs are trusted callers, not an authorization boundary. MFA is currently a
warning, not enforcement. Require and verify it operationally before emergency go-live.
Use least-privilege accounts, reviewed public contact numbers and HTTPS on the real server.

## Open release gates

- Replace read-then-delete confirmations with atomic consumption; test parallel requests,
  multiple coordinators, persistent object caches, retries and stale reviews on MySQL/MariaDB.
- Make state/audit/notification consistency explicit: test failed state writes and failed
  audit insertion, recovery and outbox idempotency. No exactly-once delivery claim is justified.
- Reserve emergency identifiers across trash and permanent deletion, including collision and
  concurrent allocation tests. Retain old tags and never reassign issued codes.
- Resolve optional individual-horse directory behavior against the owner's no-directory rule.
- Verify Plesk/nginx/CDN caching and logged-out banner freshness; WordPress purge hooks alone
  do not prove upstream cache invalidation.
- Build consent, opt-out, delivery failure reporting, retry bounds, deduplication and manual
  fallback before adding notification providers. Never automatically dispatch volunteers.

No secrets or personal records belong in source, fixtures, logs or shared audit reports.
Backups contain sensitive data: restrict permissions, store encrypted off-server and test
restoration to an isolated development subscription. Production changes require explicit approval.

## Current sandbox hardening evidence

Per the owner's October 31, 2026 Gateway correction, WP_ENVIRONMENT_TYPE=development,
DISALLOW_FILE_EDIT=true and WP_DEBUG_DISPLAY=false are applied/verified; syntax, ownership
and live wp-config mode 0600 passed. Do not reapply them. Original configuration recovery
artifact: `/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak`; preserve it
privately. It is not a full backup or restore verification. Full backup/recovery, actual
Kadence acceptance and the emergency release gates above remain outstanding. See
[the current report](test-results/sandbox-preflight.md) for provenance and scope.
