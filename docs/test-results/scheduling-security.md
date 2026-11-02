# Issue #14 — enforced administration MFA and independent ownership approval

Prepared November 1; final validation and release pin completed November 2, 2026 (America/Los_Angeles). **Local synthetic development only. Gate B remains held.** No Gateway calls, farm account changes, deployment, protected migration, production, inquiry, emergency or website-content changes occurred.

This report supersedes Issue #13's unresolved local artifact/enforcement/bootstrap blockers; its historical source and test reports remain intact. The implementation builds on `2c08321d98566bc50ccb6d62712abf798cc16d0d`, preserving documentation head `ac15352f33369461908086f91e7676e020603285` and tracker `1b9a418`. It does not replace an approved installation pin.

Six subsequent documentation commits through **`e10373f4acd9220ba4b5a9ae9b64fd86c8a121ec`**
were integrated without changing their new onboarding/payment architecture, roadmap, owner
register or product requirements. This release implements only Issue14, not those future modules.

Exact implementation source: **[`2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`](https://github.com/kks787-ship-it/KksSandbox/commit/2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1)**.
Root Git tree **`e0fed480efa9a35a28e639a5209680824f967d91`**. Subsequent documentation-only
pinning does not move this code or grant any Gate B approval.

## Candidate and official artifact

Scheduling **0.4.0**, separate MFA adapter **0.2.0**, WordPress Two-Factor **0.17.0**. Scheduling stays **schema v3 / 15 tables**: no physical schema migration is introduced. Proposed custom distribution: **20 scheduling files + 4 adapter files**; vendor distribution: **21 files**, total **45**. Tests, private invitations, documentation, development dependencies and fixture probes are excluded.

The actual official release ZIP was downloaded, not reconstructed: [WordPress/two-factor 0.17.0 two-factor.zip](https://github.com/WordPress/two-factor/releases/download/0.17.0/two-factor.zip), **91,837 bytes**, SHA-256 **`59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`**. `tests/scheduling/verify-mfa-release.py` verifies the entire ZIP digest, bounded members, safe paths, no duplicate/encrypted/symlink entries, exact 21-file inventory and every existing provider hash before extracting into the ignored disposable fixture. The new security tests use those exact distribution bytes. No vendor source or provider manifest was changed.

Exact per-file hashes and Git blobs are in [the custom manifest](scheduling-security-files.sha256)
and [combined installation inventory](scheduling-security-install-inventory.json). The unchanged
[actual-prefix proposed DDL](../pilot/scheduling-v3-TXGEh-proposed.ddl.txt) has SHA256
`45ca1b585f5f8bccfdff8b350b2942c65115a57ecf5c648c073fb22f21803dab`: settings, resources,
appointments, allocations, commands, audit, riders, horses, availability, restrictions,
details, history, slots, participants, authorization. This is reviewed source, never
permission to run raw DDL. Protected prefix/state must be independently checked before any
guarded migration. This release changes no DDL or existing serialized authorization shape.

Native option footprint is `active_plugins` on separately approved activation, existing
`two_factor_enabled_providers`, and adapter credential/configuration epoch `csf_mfa_epoch`.
User meta footprint is `_two_factor_enabled_providers`, `_two_factor_provider`, `_two_factor_totp_key`,
`_two_factor_totp_last_successful_login`, `_two_factor_backup_codes`, `_two_factor_nonce`,
`_two_factor_last_login_failure`, `_two_factor_failed_login_attempts`, `_two_factor_password_was_reset`,
`_csf_mfa_epoch`, and `session_tokens` (proof key `csf-mfa-proof-v1`). Ownership also changes only
approved identities' native `<prefix>capabilities` farm mirrors, preserving their WordPress roles.
No new WordPress role/options-based owner, provider table or notification service is introduced.
Backup/custody must protect these MFA secrets and WordPress salts; reports must not publish values.

Exact scheduling tree **`2caf847c1453821fbe633657c250917198bc79ae`**; adapter tree
**`e2357a00c59c986daef5533183f963c12a8e9ac8`**. The finite ceremony's custom release digest,
independently computed in Python and PHP, is
**`71977819b893ce9d6a7ceab727af57666d2fe94ba6cc99003e00ef23658fd6c9`**.

## Separate WordPress administration policy

`CSF_MFA_ENABLED=true` enables the existing adapter. Separately approved **`CSF_MFA_ENFORCE_ADMIN=true`** enables mandatory privileged-web MFA. Neither setting is applied to a farm by this release. Enabling enforcement changes the proof fingerprint, so existing sessions need a fresh actual challenge. Without valid current-session proof, privileged capability checks, admin controllers/AJAX and authenticated private REST are denied even for Administrators. Core roles and native capability changes do not supply proof.

Enrollment grace permits only a bare own-profile GET and the provider's nonce-protected own-user TOTP/backup-code enrollment REST calls while unenrolled. Profile POST, other identities, role mutation and general privileged actions remain denied. Enrollment alone never unlocks access: complete a real TOTP or backup-code challenge. Logout, token revocation, changed credentials, adapter disablement, provider integrity failure and proof expiry invalidate protected access. Enrollment grace itself has no temporary admin powers.

Cookie/nonce-authenticated native WordPress REST can use the current browser MFA proof; **Digital Ranch REST remains denied** by its separate verifier. Public read REST remains available under native permissions. When enforcement is selected, WordPress application passwords are unavailable globally and authenticated XML-RPC is denied. This intentionally affects integrations, all authenticated users and editors as well as Administrators: review the exact plugin/API inventory before approving enforcement. Plesk HTTP Basic Authentication is an upstream protection, not a WordPress factor; its Authorization header does not become a farm MFA proof. Trusted local WP-CLI recovery retains native WordPress capabilities but **cannot authenticate a Digital Ranch session or grant ownership**.

This protects the tested native WordPress paths. It does not claim a universal guarantee against unreviewed plugins with their own unauthenticated mutation endpoints, trusted PHP/SQL/root administrators, or a compromised host. Protected-stack/API compatibility remains a separate acceptance requirement.

## One-use ownership ceremony

The retired CLI bootstrap/transfer never grants ownership, even with the old flag or `--user`. No new shell MFA exception, reusable bearer token, URL approval, public endpoint or manual SQL ownership procedure is provided.

1. Before any protected execution, an owner separately approves the exact numeric operator and owner identities, verification evidence, source, target and action. An existing WordPress Administrator is not presumed to be Kris, Beth or the farm owner. Proposed owner needs basic WordPress `read`, not WordPress administration.
2. A separately authorized trusted operator prepares a finite private JSON invitation outside the web root, non-symlink, mode 0600, maximum 8 KiB. `CSF_SCHEDULING_IDENTITY_PATH` selects that file; it is not an ownership or MFA bypass. The file references a privately retained independent identity-verification record by SHA-256. Verification of the person's real identity and legal authority is a human/out-of-band prerequisite, not something a WordPress checkbox can prove.
3. The invitation binds exact home/site URL, allowed nonproduction environment, facility UUID, custom release content digest, schema 3, starting revision, policy/registry hashes, distinct operator/owner numeric IDs, previous master, bootstrap/transfer action, issue/expiry (maximum **600 seconds**), identity-evidence hash, and separate exact command UUIDs. The entire invitation is shown to its participants. No identity data is accepted through GET.
4. The operator submits **Start operator review** from their own browser. The proposed owner independently submits **Approve as proposed owner** from their own browser. Transfer additionally requires **Approve as current owner** by the outgoing master. Each must have a real approved MFA challenge no older than **300 seconds**, not just an enrolled account. The operator then submits **Confirm ownership change**.
5. Every step is signed and immutable in the existing command ledger and audit. Action, complete parameters, step UUID, actor, exact session reference and approval time are HMAC-bound. References contain session hashes/signatures, never raw cookies, passwords, seeds or recovery codes; they are not accepted as login credentials.
6. Consumption revalidates all live proofs, credentials, native roles, owner baseline access and invitation under the existing InnoDB singleton transaction lock. Native users/usermeta/options must also be InnoDB and relevant rows are locked. Registry, native farm-capability mirrors, outgoing-session revocation, history, revision, command and audit commit together. Existing calendar conflict/welfare/permission code remains authoritative.
7. Same UUID retries return the original result, not another grant. An uncertain response must be resolved with **Check result** using the original UUID; do not invent a new command. Any intervening policy/group/calendar revision, source change, expired/revoked proof or changed identity invalidates the pending ceremony. Use a brief change freeze and restart review with a new invitation only after resolving the original result.

Native account deletion/removal and role changes of the current master are blocked pending a verified ownership transfer. There is no second-master checkbox and no emergency replacement identity inferred from an email or username. Privileged physical access remains an infrastructure trust boundary.

Private invitation **shape only**, deliberately invalid and not executable. Preserve exact key
order; populate only after O-Master/O-Transfer approves each real value and independent evidence.
For bootstrap omit `identity_master_approved`; for transfer include it between owner approval
and consumption. Every UUID must be distinct. No actual staff invitation is committed.

```json
{
  "site": "OWNER-APPROVED-EXACT-HOME-AND-SITEURL",
  "environment": "development",
  "facility": "VERIFIED-FACILITY-UUID",
  "release": "APPROVED-CUSTOM-CONTENT-DIGEST",
  "schema": 3,
  "revision": null,
  "policy_hash": "CURRENT-POLICY-SHA256",
  "registry_hash": "CURRENT-CANONICAL-REGISTRY-SHA256",
  "operator": null,
  "owner": null,
  "previous_master": null,
  "action": "bootstrap",
  "issued": null,
  "expires": null,
  "verification_record_hash": "PRIVATE-INDEPENDENT-VERIFICATION-RECORD-SHA256",
  "commands": {
    "identity_requested": "EXACT-NEW-UUID",
    "identity_owner_approved": "SEPARATE-EXACT-NEW-UUID",
    "identity_consumed": "SEPARATE-EXACT-NEW-UUID"
  }
}
```

`release` is `CSF_Scheduling_Identity::release_hash()`: SHA256 of WordPress JSON encoding
`[scheduling version, adapter version, sorted relative custom-file SHA256 map]`. It binds
exact bytes rather than a circular embedded commit identifier. The operator must compare this
against the pinned release inventory before creating an invitation. `policy_hash` hashes exact
stored policy JSON; `registry_hash` uses the existing recursively canonical Store JSON digest.
These are read-only calculations, never a grant or evidence of a person completing MFA.

## Enrollment, recovery and updates

Before approving enforcement, verify each actual administrator and designated owner has an individually controlled account, an approved authenticator, securely retained single-use recovery codes and documented private recovery custody. Perform fresh browser challenge/logout/relogin and a recovery-code challenge; verify privileged access is denied before the challenge and after revocation. Never paste factors, codes, invitation contents or identity documents into chat, Git or a public ticket. No notifications are sent by this change.

If the authenticator is lost but backup codes remain, use one code through the normal provider challenge; rotate credentials/codes and revalidate. If all factors are lost, keep enforcement enabled, revoke that identity's sessions, and stop for separately approved physical operator recovery of the **same numeric identity** after independent owner verification. Use the pinned provider's supported credential reset/enrollment APIs through a privately audited trusted shell, not an ownership SQL edit or static web bypass. Record who verified/recovered whom and why in the private recovery/change record; have the user independently enroll and complete fresh MFA. The registry owner must remain unchanged. A deleted/replaced identity or unavailable host requires a separately reviewed recovery/restore; this release does not silently elect a new master. Rehearse operator access and lockout recovery before enforcement.

Automatic updates are disabled for these three security-sensitive plugins only when enforcement is selected. This is a **manual security maintenance obligation**: monitor upstream advisories/releases, assign an accountable operator and review promptly; download each actual candidate ZIP, verify provenance/digest/all members, stage and rerun both-engine/browser/recovery suites, update the exact manifest and versions, obtain new file/update approval and back up before replacement. An unexpected vendor change fails closed; do not add a hash wildcard or switch off enforcement to accept it. No unsupported automatic-update promise is made.

## Validation and limits

These are **new final reruns**, not inherited Issue13 results. All four database/browser runners
and the static runner exited0. [Machine-readable evidence](scheduling-security-evidence.json)
records runtime versions, exact counts and final log SHA256s. No temporary fixture services remain.

| Check | MariaDB10.6.28 | MySQL8.0.46 |
| --- | --- | --- |
| Foundation / calendar / staff usability / granular permissions | 88 /131 /66 /81 passed | 88 /131 /66 /81 passed |
| Real-provider prerequisites | 8 passed | 8 passed |
| Existing real-provider browser suite | 50 passed | 50 passed |
| New enforced-admin/independent-ownership browser suite | 78 passed | 78 passed |
| Six booking races, two permission/revocation races, migration race | Passed | Passed |
| Populated v2→v3 preservation, downgrade denial, v3/v1/baseline logical restores | Passed | Passed |
| Ownership race/idempotency, seven SQL/COMMIT rollback injections, lost acknowledgment | Passed | Passed |

Totals: **732 scheduling +16 MFA prerequisites =748 assertions**, **354 browser checks**
(59 calendar +39 permissions on MariaDB, 50 existing +78 new security on **each** engine).
All **88 custom PHP files** passed syntax and repository WPCS/PHPCompatibility checks. Deployment/
backup guards22, mocked Plesk identity checks6 and sandbox-preflight unit tests14 passed. Browser
axe checks at **390/768/1280**, captured JavaScript exceptions and reported transport diagnostics
were all clear. The matrix covers actual TOTP/backup challenges, recovery-code contention on
four PHP workers, unenrollment/logout/expiry, credential/proof-write races, API boundaries and
the native ownership forms. The new suite additionally tests >5-minute proof rejection,
invalid/private-file scope, approval HMAC tampering, stale revision, current-master native
deletion/role-change denial and same-identity factor recovery. Normal farm grants remain scoped.

Reproduction after verifying the officialZIP into the ignored fixture:

```bash
python3 tests/scheduling/verify-mfa-release.py /path/to/verified/two-factor.zip
CSF_TEST_SECURITY=1 bash tests/scheduling/mfa-run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 CSF_TEST_SECURITY=1 bash tests/scheduling/mfa-run.sh
CSF_TEST_BROWSER=1 bash tests/scheduling/run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
```

These commands are for the **existing isolated local tooling only**, never the farm. The
scheduling regressions use WordPress6.8.3; the actual-official-provider security suites use
WordPress7.1.4; both run PHP8.1.34. Theme is a local stand-in, not Kadence. Chromium154.0.8037.57,
Playwright1.62.1, axe4.10.3. The lifecycle mutex serializes Docker fixtures so network-interface
churn does not invalidate Chromium runs; no farm network or credentials are used. Local fixtures
use synthetic data and suppress mail. Guarded test-only domain seeding constructs old regression
baselines; real ownership acceptance uses independent actual browser factors. Neither fixture
seeding nor HTTP probes ship. Unrelated emergency activation/HTTP tests were deliberately not
run because this task explicitly keeps emergency inactive; static checks and regression
assertions verify its source/status and the disabled inquiry boundary remain unchanged.

Gate A's reported functional recovery remains valuable historical evidence, **not a fresh backup for a new change**. The actual Plesk/Kadence/PHP stack, real identities/MFA custody, protected HTTP authentication/proxy behavior, staff acceptance, off-server recovery and farm policy decisions remain unverified by these local runs. No operational acceptance is claimed for Beth or Kris. See the [single owner decision package](../pilot/gate-b-owner-review.md) and [canonical deployment runbook](../05-backup-rollback-deploy.md) for separate unapproved installation/recovery checkpoints.
