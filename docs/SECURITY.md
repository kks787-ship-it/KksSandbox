# Security and operational boundaries

## Owner-approved recovery-first protocol — conditional activation pending

The [latest policy](DECISIONS.md#november-2-2026--owner-approved-recovery-first-autonomous-development-policy)
allows autonomous sandbox-local writes only with the required preceding24hour verified backup,
usable isolated functional recovery, pre-change backups for major/destructive/auth changes,
rollback and documented scope. Owner ratification of the supplied protocol is now recorded;
current new capture is verified but current-workflow isolated functional restore verification remains pending. Existing
read-only supervision continues. Policy registration does not authorize a restore, install,
migration or security/account action in the current capture-reporting task. Owner exceptions
remain separate even after activation; stop for exposure/security/recovery uncertainty, and
never auto-restore over newer owner changes. No production/shared infrastructure or outside costs.


## Owner-adopted supervised autonomy — November 2, 2026

[Standing policy](DECISIONS.md#november-2-2026--owner-adopted-supervised-autonomy-policy)
authorizes bounded read-only development-sandbox diagnostics and routine safe repeats/local
fixes without another per-attempt approval. Record reason/scope/outcome; never treat a failed
job as PASS. No retroactive expansion of historical approvals. Stop for unexpected security
findings or potentially consequential changes; production, destructive/security/account/MFA,
installation/migration and recovery/deployment approval boundaries remain intact. Nothing in
this policy grants capture/transfer/restore or GateB installation. Sensitive log/config material
must be reduced to sanitized observations before returning or publishing it.


## November 2 identity correction

HTTP Basic Auth is a web-server access gate, not a WordPress identity, farm role or MFA proof.
Plesk/Toolkit management identity, its installed technical WordPress administrator, Kris's
proposed separate business identity and Beth's staff identity must be mapped/verified separately.
No username/email matching, Plesk screenshot or control-panel access establishes a person's WP
UID, current factor proof or farm ownership. Native WordPress Administrator does not grant a
Plesk account, and WordPress MFA does not secure the hosting control plane.

The technical account's controller/numeric ID remain private and unverified. Recommend keeping
it for maintenance and separately approving Kris's Subscriber/Master business account; preserve
the existing distinct-UID browser approval protocol. Separate accounts are not proof of separate
humans; privately establish the owner's required independence, recovery and integration policy.
Do not relax source checks, promote Beth, create a throwaway admin or copy factor proofs to solve
an inferred identity problem. [Accounts & Access](01-architecture.md#accounts--access--corrected-identity-model-november-2-2026)
is a local design, not a deployed UI or new grant path. Exact identities, screenshots and secrets
stay out of Git/chat; current-session authentication is shown only for the requesting identity.

## Current Issue #14 override — no protected execution

The [local security release](test-results/scheduling-security.md) supersedes earlier unresolved
officialZIP/enforcement/CLI-handoff design statements below. Scheduling0.4.0/csf-mfa0.2.0 use
the verified official Two-Factor0.17.0 distribution. Mandatory WordPress administration MFA
is an independent default-off policy. Its enrollment grace cannot mutate general profiles or
privileges; enrollment does not authenticate a session. Selected enforcement also disables
WordPress application passwords/authenticated XML-RPC; review integration impact separately.

Ownership requires independently reviewed real identities plus separate actual browser MFA
approvals against a private finite exact-action invitation. No CLI ownership exception or
bearer handoff token exists. Protect the current master from native deletion/role changes;
lost factors require same-numeric-identity recovery under a separately approved private
physical procedure. Trusted host/SQL access remains a security boundary, not something this
WordPress plugin can make untrusted. A new owner cannot be inferred from admin role/email.
Maintain manual vendor monitoring/staged repinning; a hash mismatch fails closed. No farm
provider, account, role, schema, policy, inquiry, emergency or production change is authorized.

## Digital Ranch MFA — local candidate only

The separate csf-mfa adapter requires a successful approved challenge bound to the current
signed WordPress cookie and exact live primary-database session. It never equates provider
enrollment, a timestamp, WP Administrator, CLI --user or an application password with MFA.
Scheduling membership, overrides, scope, nonces, transaction checks and audit remain independent.
See [Issue #13 security contract and release limitations](test-results/scheduling-mfa.md).
Provider pin/credential/session/expiry failure denies access. The historical CLI master identity
ceremony remains blocked with this web-only adapter; never export tokens or enable a fake
verification filter to get past it. WordPress administration-wide mandatory MFA is a separate
unresolved enforcement gate. Gate B is held and no actual provider/accounts/server changed.

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
privately. It is separate from the completed full backup at the private csf-sandbox-backup-20261031 directory. Gateway verified structural restoration of 12 InnoDB tables and 4,336 regular files with zero mismatches. Functional restored-site login/rendering, actual CSF/Kadence acceptance and the emergency release gates above remain untested/outstanding. See
[the current report](test-results/sandbox-preflight.md) for provenance and scope.

## Optional public inquiries — local 0.3.0 candidate only

The owner requests the form remain disabled until delivery is verified. Installing code does
not set recipient/enablement or send mail. WordPress Settings API capability/nonce protection
covers administrative settings. Public submissions require nonce, signed 3-second/2-hour
challenge, honeypot and reply permission, bounded scalar fields, a fixed valid recipient and
validated email-only Reply-To. No visitor-controlled recipient, subject or From header,
attachments or auto-replies. Expiring one-use/rate claims use no-autoload options with identical
duplicate values, limiting standard WordPress DB writes atomically; actual MariaDB/client
behavior still needs verification. Clock-hour limits allow boundary bursts and are not DDoS
protection. Do not trust forwarded IP headers or call a public guest nonce an anti-bot secret.

No inquiry body or raw IP is retained by core. Salted identifiers/expiry values need WP-Cron
cleanup; independent mail-provider/plugin logs may retain content and require privacy review.
Exclude form/submission routes from full-page caching. Real provider/inbox and authenticated
form acceptance, including error/replay behavior, are required before separately approved
enablement. All local tests intercept mail; none establish delivery to Beth. Form delivery
failure consumes the token and does not trigger an automatic retry or a success notice.

### Inquiry confirmation integrity (Issue #2)

A public URL flag is not proof of submission. Only a successful validated POST's accepted mail
transport issues the new five-minute HMAC receipt, purpose-separated from the public challenge
and bound to the site's Contact URL. Shape, signature and age checks precede an atomic identical-
value claim; replay or claim failure produces no acknowledgment. Stored receipt markers contain
only a digest key and expiry, never personal inquiry data. Cleanup reuses the existing bounded
expiry job. No-store/private and no-referrer response headers protect receipt views; verify
actual cache exclusions on Plesk before enablement. Accepted-for-sending does not mean delivered.
The form stays disabled and no new provider, activation or deployment is authorized here.
