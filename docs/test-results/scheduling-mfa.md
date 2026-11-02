# Issue #13 — maintained MFA provider and verified current-session integration

Local development, November 1, 2026 (America/Los_Angeles). **Gate B remains held.** No Gateway,
protected-site/server command, real account, master bootstrap, scheduling deployment/migration,
live grant, policy/fixture record, production, inquiry or emergency change occurred.

## Recommendation and source evidence

Recommend **WordPress Two-Factor 0.17.0 plus the separate Digital Ranch MFA adapter 0.1.0**
for owner selection and further acceptance, not installation authorization. Scheduling becomes
**0.3.1**, retaining schema 3/15 tables and the Issue #12 transaction/permission architecture.
The adapter is a separate three-file plugin. Scheduling stays a 19-file release.

| Decision consideration | WordPress Two-Factor | Full Wordfence |
| --- | --- | --- |
| Maintenance evidence | Official GitHub release 0.17.0 published September 28, 2026; repository activity November 1; exact release commit below | Maintained alternative identified by owner/vendor links; current official binary/version and supported hooks not independently retrieved here |
| Operational scope | TOTP, backup codes, profile/settings/login and provider REST endpoints; no WAF/scanner | Broader firewall/scanner/security suite; server/proxy, scanning, network and licensing implications require a separate assessment |
| Native mandatory enforcement | No comprehensive built-in role-based enrollment enforcement; site provider selection is not mandatory MFA | Vendor enforcement features need exact-version verification; no compatibility claim based on marketing |
| Current-session evidence | Documented authenticated/revalidated actions with successful provider; adapter tested against exact source | No adapter implemented or undocumented vendor internals guessed |
| Selection | Recommended for minimal operational complexity, with explicit remaining blockers | Not selected or approved; no invented release hash/file/table inventory |

**Do not select standalone Wordfence Login Security.** Its scheduled discontinuation is documented
in the owner's Issue #13 and [vendor help](https://www.wordfence.com/help/login-security-plugin/).
The old `wp-plugins/wordfence` GitHub mirror is stale (2015) and was rejected as current evidence.
The shell proxy was unreachable; it was not bypassed. Connected GitHub read-only tools supplied
official Two-Factor release/source metadata. No environment configuration was changed.

- [Official release](https://github.com/WordPress/two-factor/releases/tag/0.17.0), source commit
  **12c033f7b7ac8a1b561e1b32aa944a0e6856fff5**, GPL-2.0-or-later.
- Release asset `two-factor.zip`, 91,837 bytes. GitHub publishes SHA-256
  **59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473**.
  **Published digest only: ZIP bytes were not downloaded or independently verified here.**
- Tests use a reconstructed source distribution: 20 exact official release-source blobs plus
  `includes/qrcode-generator/qrcode.js` from qrcode-generator 2.0.4, MIT, Git blob
  **df13f829bf41f36b82f0ed85751ed3b4c39cfeb8**, commit
  **83b7e8fe3fddd3b0368dbafd6ce56995bd25e3c8**. This build dependency is required by upstream's
  release instructions. All source blobs/bytes and SHA-256s were verified; no provider PHP changed.
- The adapter's [provider inventory](../../wp-content/plugins/csf-mfa/provider-files.json)
  pins all 21 local distribution files. It deliberately denies unreviewed versions/changed files.
  Deterministic local USTAR source-build SHA-256: **b91ab43be616c04478e95f0239ff96640c981193a4337bbf1f10122bafc6971d** (sorted `two-factor/` paths, mode0644, uid/gid/mtime0). This source build is **not represented as the downloaded vendor ZIP**. A future installer must
  explicitly select and verify one reviewed artifact, never silently substitute distributions.
- Original scheduling code **c04a24387e3d26f7cb0a7a13683a3981675a086b**, documentation head
  **d09ad92701dd6707281984eeb106f1d27370c497**, historical 0.2.1 pin and owner tracking changes
  through **488c517de2ba01f72b8abf0faefc69993ed34dd5** remain in provenance.

## Security contract

`CSF_MFA_ENABLED` must be explicitly true in separately approved private configuration. Otherwise
the adapter never authorizes access. Activation creates no scheduling schema, identities, grants
or provider enrollment. Provider choice is limited to TOTP/recovery codes when enabled; email,
dummy and unsupported extensions cannot satisfy Digital Ranch verification, even under WP_DEBUG.
This provider restriction affects the entire single WordPress site and requires M-Enforce review.
Multisite is deliberately denied.

The provider's `is_current_user_session_two_factor()` helper is insufficient: initial enrollment
can populate a timestamp without a successful challenge/provider. The adapter listens to the
documented `two_factor_user_authenticated` and `two_factor_user_revalidated` actions, captures the
new cookie token or validates the existing revalidation cookie, and records its own signed proof
only after durable challenge consumption is verified. The proof also anchors the credential configuration captured before the challenge; a factor rotation during successful-login callbacks cannot mint a proof for the replacement credentials. A per-user MariaDB/MySQL advisory lock
serializes challenge processing; lock failure stops the request. Vendor nonce and validation
continue to run. No incoming field or enrollment flag can mint a proof.

Proof binds numeric user ID, SHA-256 of the exact WordPress token, approved provider, issuance,
expiry, site identity and a secret-keyed credential/configuration fingerprint. HMAC uses the
WordPress authentication salt. Maximum proof life is **eight hours**, capped by WordPress session
expiry, irrespective of Remember Me. Eight hours is a proposed security policy requiring approval,
not an assertion of actual farm policy. Native provider revalidation refreshes an existing session.
No trusted-device bypass is implemented. A stolen complete valid browser session remains a bearer
credential; MFA does not protect a compromised browser, server or arbitrary trusted plugin code.

Each protected request requires a valid current signed WordPress cookie for that same identity,
an unexpired primary-database session/proof, matching source pin and current enrollment/fingerprint.
Raw uncached reads avoid a stale object-cache authorization. Under the per-user challenge lock, provider credential/session metadata reads also bypass stale caches. Proof storage uses an exact primary-row compare-and-swap, so revocation between reading the session and storing its proof cannot recreate the token. Password changes, session deletion,
factor changes, backup-code regeneration/consumption, provider configuration/removal and expiry
invalidate proofs. Epochs prevent restoring enrollment/active-plugin settings from reviving older
proofs. Backup consumption conservatively invalidates that user's other proofs, not their farm
membership. Sensitive writes suppress raw SQL errors and restore the caller's logging state.

CLI `--user`, application-password authentication, REST (including valid cookies/nonces), XML-RPC,
SSO/current-user assignment without a valid challenge-backed cookie and password-only cookies
cannot authorize Digital Ranch. Existing native admin/AJAX nonces, record scope, membership,
transactional lock and granular rights remain necessary. No new public production endpoint exists.
Provider REST enrollment endpoints retain the provider's existing permissions and revalidation.

Scheduling now separates **enrollment eligibility** (`csf_scheduling_mfa_enrolled`, default false)
from **current-session authentication** (`csf_scheduling_mfa_verified`, default false). An MFA-verified
owner can review an independently enrolled employee without borrowing that employee's session.
Assignment also requires the existing explicit identity-verification field and audited review.
An enrolled employee still has no protected access until their own current session verifies.
The UI labels stored rights "Configured now (MFA required)" and displays current-session evidence separately. It does not claim to verify another person's browser from an owner viewing their record.

### Deliberately unresolved security gates

1. **Master bootstrap/transfer:** the historical guarded CLI ceremony demands MFA for operator
   and target. A browser-only verifier correctly refuses it. No browser token is exported to CLI
   or treated as reusable authority. Tests assert this denial. A separately reviewed, one-use,
   exact-target/actor/revision/command-bound identity handoff or alternate safe ceremony must be
   implemented/tested before O-Master can execute. No temporary global MFA bypass is acceptable.
2. **WordPress administration enforcement:** this adapter gates Digital Ranch. Two-Factor does
   not by itself enforce enrollment for every WordPress admin/alternate admin login. Inventory
   actual operator/auth plugins and approve/test mandatory administration protection independently
   before installation readiness. Do not claim password-only WordPress administration is solved.
3. Exact official ZIP byte verification or explicit source-build selection, protected PHP 8.3.36 /
   MariaDB 10.6.23 / Kadence / Plesk / HTTPS / Basic Auth / proxy/cache compatibility and real
   device enrollment remain outstanding. Local compatibility is evidence only for its stated stack.
4. Provider or adapter updates change the security contract. Stage/pin/retest before enabling;
   automatic provider updates cannot silently preserve Digital Ranch access through a hash mismatch.

## Expected installation effects and recovery

Reviewed file scope is 19 scheduling files, 3 adapter files and 21 source-distribution provider
files in their three separate plugin directories. Official ZIP installation still needs an exact
unpacked inventory comparison. No WordPress/Kadence/core/emergency/script/page/menu/photo changes.
No .htaccess/WAF/firewall/NTP/Plesk changes. Provider declares WordPress >=7.0, PHP >=7.4; adapter
requires PHP >=8.1. Vendor requirements are not protected-stack acceptance.

No new adapter/provider tables. Scheduling remains the separately guarded v3 migration (15 tables),
never an activation side effect. Provider uses user meta for enabled/primary methods, TOTP secret
and last-used tick, hashed backup codes, login nonce, throttling/failures/password-reset notice;
WordPress `session_tokens` contains provider information and adapter proof. Adapter adds per-user
`_csf_mfa_epoch` and non-autoload site option `csf_mfa_epoch`; provider may register an uninstall
callback in `uninstall_plugins`, and `two_factor_enabled_providers` stores site choices. All affected
options/usermeta must be backed up and privately compared after an approved disposable/pilot run.
TOTP secrets are stored by upstream in WordPress user meta, not encrypted by this adapter; backups,
DB/administrator access and vault custody require appropriate protection. Never export those values.

Provider can send low-backup-code warnings and compromised-password-reset messages. Email OTP is
excluded, but these notifications still require delivery/privacy/recipient approval and testing.
All local mail was intercepted. No message was sent and inquiry settings remain disabled.
Pinned upstream defaults reviewed for M-Enforce: six-digit SHA-1 TOTP, 30-second periods with a four-tick (±120-second) acceptance window and latest-used-tick replay protection; ten eight-digit numeric backup codes stored as password hashes; one-second base/exponential challenge backoff capped at 15 minutes, with password reset after 30 failed challenges. These defaults need explicit security review, not farm-policy inference. Provider throttles failed challenges and can reset passwords after repeated failures; recovery
and alternate-login tests must be supervised, never brute-force against actual staff.

After approval, verify each actual numeric identity privately; supervised TOTP enrollment, private
code confirmation, backup-code generation/custody in an approved vault plus protected offline copy,
fresh-browser login, individual permission checks and logout/recovery tests precede operational use.
No QR, seed, password, token or recovery value belongs in Git/chat/screenshots. No staff contact
address is assumed to be a login identity. A verified Farm Owner / Master need not be a WP admin.

For a lost device, use one recovery code, rotate TOTP and backup codes, revoke sessions and verify a
fresh challenge before restoring use. If all methods are lost, suspend that person's pilot access
through an authorized independent actor, verify identity out of band and obtain scoped recovery
approval. If the sole master/operator is locked out, stop for separately approved restricted
recovery; the unresolved bootstrap ceremony is a blocker, not permission to disable MFA globally.

Fresh target-bound DB/wp-content/configuration backup, hashes, encrypted recovery custody and a
private isolated functional restore must cover WordPress accounts/meta, provider secrets/settings,
salts, session data, plugin files and scheduling records. Gate A only covers its old captured state.
For containment deactivate only scheduling/adapter under approved scope: default-deny must remain.
Do not uninstall the provider or delete secrets/audit/tables. A matched restore needs separate
approval and reconciliation of later website/account/scheduling changes. Restoring old sessions or
secrets requires revocation/rotation review before access resumes. Do not downgrade v3 DB to v2 code.

## Validation and reproduction

Final clean runs (earlier incomplete diagnostic runs excluded):

| Validation | MariaDB10.6.28 | MySQL8.0.46 |
| --- | ---: | ---: |
| Existing scheduling assertions |366|366|
| MFA prerequisite/negative assertions |8|8|
| Actual-provider Chromium checks |50|50|
| Two simultaneous recovery attempts, four PHP workers |Exactly one verified session|Exactly one verified session|
| Injected failed TOTP/backup/session writes |Denied|Denied|
| Revocation during proof storage |No token resurrection|No token resurrection|
| Factor rotation during successful-login callback |No proof for replacement factor|No proof for replacement factor|
| Disabled adapter / CLI bootstrap default-deny |Passed|Passed|
| Existing booking/permission/migration races and populated upgrade/restore matrix |Passed|Passed|

**732 scheduling database assertions +16 MFA prerequisites**; **100 actual-provider browser
checks +98 existing calendar/team checks =198 browser checks**. MFA tests ran WordPress7.1.4 /
PHP8.1.34 on both SQL engines. Existing regressions used WordPress6.8.3/PHP8.1.34. Chromium
154.0.8037.57, Playwright1.62.1, axe4.10.3; 390/768/1280px owner views passed axe with zero
violations. Final MFA runs captured zero script exceptions or transport diagnostics. These are
local stand-in-theme fixtures, not actual Kadence/Plesk/device or owner acceptance.

All **86 custom-plugin PHP files** passed syntax and project WPCS/PHPCompatibilityWP. Backup/
deployment guards passed22 scenarios, Plesk identity mocks6, sandbox-preflight units14. Shell,
Node, JSON, exact provider/custom file hashes and whitespace checks passed. Core/emergency source
and installation/deployment scripts are unchanged; their unrelated HTTP runtime suites were not
rerun or emergency activated. Existing scheduling suites assert inquiry/emergency fixture state.
Authentication tests assert that all15 owned scheduling tables remain unchanged; expected
WordPress authentication metadata changes occur only inside the disposable fixture.

[Machine-readable evidence and final log digests](scheduling-mfa-evidence.json),
[22 custom-file manifest](scheduling-mfa-files.sha256) and
[exact 43-file proposed inventory](scheduling-mfa-install-inventory.json) accompany this report.
Scheduling plugin tree **03884538be197ea1c97c5d65aef65122cd783f69**;
adapter plugin tree **d50ee60a71bccd55217374af1f40c3d8c173d43d**. The immutable implementation
commit is recorded in the release-pin follow-up. No fresh protected target comparison occurred.

Development corrections included enrollment-versus-session assignment/projection, atomic proof
persistence and binding to the pre-challenge credential configuration. Test helpers were corrected
to submit one native challenge, reuse the actual consumed code for replay, decode the native logout
link, and inject failures after the password phase. Diagnostic runs also encountered managed-network
changes while local fixtures started/stopped and dependent WordPress script failures. Final runners
serialize fixture lifecycles and wait for web/network startup. No error assertions or security
checks were disabled, no uncertain login POST was retried, and incomplete runs are excluded.

```sh
python3 tests/scheduling/prepare-mfa-provider.py
bash tests/scheduling/mfa-run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/mfa-run.sh
CSF_TEST_BROWSER=1 bash tests/scheduling/run.sh
CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/scheduling/run.sh
```

Only disposable `/test/site/`, DB `csf_fixture`, internal Docker networks and synthetic accounts
are used. Browser relay binds executor loopback only. Provider source lives under ignored
`.wp-local/`; no fixture/fake verifier is in either production plugin. Local WP 7.1.4 is copied
without its existing config/content and installed into a new MariaDB/MySQL fixture. Scheduling
regressions continue to use their existing WP 6.8.3 fixture. Stand-in theme is not protected Kadence.

Owner review entry point: [Gate B decision package](../pilot/gate-b-owner-review.md).
Provider choice, provider/adapter files, mandatory security settings, notifications, each account /
enrollment/recovery, new scheduling source, schema migration, safe identity ceremony, individual
grants, fictional policies, fixture records and protected acceptance require separate decisions.
No blanket Gate B authorization is requested or implied by local test completion.
