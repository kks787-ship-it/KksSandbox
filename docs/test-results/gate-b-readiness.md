# Gate B readiness — current sandbox preflight

**November 2, 2026, America/Los_Angeles. Issue #11; read-only assessment only.** The owner's
“Sprint #15” readiness assignment is not implementation of onboarding Issue #15. Gate B remains
unapproved. Starting documentation head `1fe8947aa5c106470b26666220dbdb7dab1e9eb2` and tested
implementation `2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1` are preserved.

**Decision:** the exact release is ready for scoped owner review, not installation. No static
version/storage mismatch was found. One options-table digest drift during inspection requires
review and a fresh frozen baseline. Real operator identity, individual staff setup, integration
dependency confirmation, recovery custody and a fresh change-window backup/restore remain gates.
Protected MFA/calendar functionality and Beth/Kris acceptance have not been tested.

## Current target and evidence

Only target inspected: `https://sandbox.cornerstonefarmomak.com`, root
`/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com`, DB `wp_mdf4x`, prefix
`TXGEh_`, literal `development` environment. Production files/configuration/database were not
queried. Direct SQL used short read-only transactions, SELECTs and private own-account grant
metadata; no WordPress bootstrap, WP-CLI, cron invocation, login, authenticated API call,
application/script staging, backup, restore, plugin operation or account mutation was performed.
Inspection scripts and syntax-check source input ran from stdin, without installing files.

| Observed item | Finding / compatibility implication |
| --- | --- |
| WordPress | 7.1.4 from installed version file; single-site layout, no multisite flag observed |
| PHP | CLI and FPM binaries both 8.3.36; mysqli/mysqlnd, JSON, hash, OpenSSL, sodium, mbstring present |
| MariaDB | 10.6.23; all12 existing WordPress tables InnoDB, including users/usermeta/options required by ownership locking |
| Theme | Active template/stylesheet Kadence; installed1.5.2 |
| Plesk | Installed core package18.0.81.6; no control-panel setting changed |
| HTTP stack | Nginx1.30.6, Apache2.4.52; nginx/apache2/plesk-php83-fpm services active/running |
| FPM routing | Apache handler points to the sandbox socket. Pool uses `prefix=/var/www/vhosts/system/$pool` with `listen=php-fpm.sock`; expansion matches that existing socket. The initial literal-path-only check was insufficient; relative-prefix resolution was verified |
| HTTP Basic Authentication | Declared in sandbox nginx and Apache configuration; unauthenticated **HEAD to existing Kadence static CSS returned401** with Basic challenge. No credential was submitted and no dynamic page was requested |
| Plugins | csf-core0.3.0 active; csf-emergency0.1.0 and Akismet5.7.2 installed/inactive. No scheduling, csf-mfa or Two-Factor directory |
| Database | Zero scheduling tables; proposed schema3 adds15, retaining the12 existing native tables |
| Migration permissions | Private own-grant metadata confirms configured DB account has CREATE/INDEX and DML on this target; no trial DDL or privilege change performed |
| Authentication state | One account, one Administrator role row, no Subscriber role rows, no duplicate capability rows, no farm-capability/MFA/provider metadata. Human ownership remains unknown |
| Inquiry / emergency | Inquiry option absent; installed core's disabled default verified by source hash. Emergency inactive. No setting changed |
| Caching / configuration | No MU plugins, cache/DB drop-ins or .user.ini files; no WP_CACHE flag, no cache-enable directive in inspected sandbox vhosts. OPcache enabled, timestamp validation on, revalidation2seconds in selected PHP configuration; no selected auto-prepend/append override |
| Timezone | WordPress remains global UTC (empty timezone string, offset0). Proposed facility timezone is separate; no site-global change |

All **31 PHP source files** in the proposed45-file distribution (19 custom,12 vendor) passed
syntax checks on the installed **PHP8.3.36** binary. Source came from the exact implementation
pin and actual verified ZIP, was hash-checked in memory and supplied to `php -l` via stdin;
no plugin code or WordPress bootstrap was executed and no plugin file was installed.
This extends syntax evidence to the actual PHP version, not protected application acceptance.

Static configuration/binary inspection is not execution of the new plugins on FPM. Actual
cookie/nonce/MFA, reverse-proxy, authenticated API, private caching and mobile compatibility
remain for an approved pilot. OPcache availability does not establish an active WordPress
object/page cache. Do not flush shared services or change Plesk to force a test.

## Identity and alternate-authentication risk

No WordPress account ID, username, private login/recovery email, password, factor, token or identity
document was selected or returned. Only account/role/metadata **counts** were queried. One
Administrator does not identify Kris, Beth or the technical operator. Owner verification must
occur privately before any account, factor, role or ownership step. Neither Beth nor Kris has
been mapped to an existing account by this assessment.

There are **zero `_application_passwords` metadata rows**, no installed authentication/MU
plugin, and no matching application-password/XML-RPC authentication hooks in active csf-core.
Bounded sandbox access-log samples contained14 REST requests and no XML-RPC request; the
largest sampled file covered approximately08:23–08:37 PST. No identities, IPs, complete URLs,
headers or raw log entries were returned. This limited window cannot prove there is no dormant
mobile app, publishing client, monitor, deployment agent, Plesk WordPress Toolkit login or
external integration. Owner/operator must inventory these privately and approve retirement or
an independently reviewed replacement before `M-Enforce`.

Selected enforcement disables **all WordPress application-password authentication and
authenticated XML-RPC**, restricts password-only privileged web/API access and pins the three
security plugins to supervised manual updates. An API nonce, Plesk Basic Auth, Toolkit SSO or
CLI impersonation is not evidence of a completed factor. Public read REST remains under native
permissions; the Digital Ranch REST verifier remains closed. No external integration was tested,
disabled or contacted. Security notification behavior also needs an explicit owner decision;
disabled inquiries do not disable native WordPress/provider security mail.

## Drift and baseline boundary

Comparison from **08:35:17 to08:57:48 PST** found identical website inventory/digests for
all4,479 files, config and core/emergency source; unchanged active plugins, selected site
settings and identity aggregates; and identical fingerprints for **11 of12 tables**. The
options table remained163 rows but its whole-table digest changed. Values/identities were not
dumped and the changed option key was not identified from the available aggregate snapshots.
The inspection's SQL sessions were read-only; no dynamic WordPress request or write was issued.
Do not attribute the drift to a particular actor, cron/transient or owner edit without evidence,
and **do not restore an older options table to remove it**. This assessment is not an unchanged
or frozen DB baseline. Owner/operator must review concurrent activity, resolve unexpected drift
and capture a fresh baseline during the separately approved change window before execution.

## Exact proposed change

[Machine-readable installation diff](../pilot/gate-b-installation-proposed.json): **45 additions,
0 replacements, 0 deletions**, limited to20 scheduling,4 adapter and21 vendor files. All three
destination directories are absent. Every custom file was compared to the immutable Git pin;
the actual local official ZIP and all21 extracted hashes were reverified against freshly read
official GitHub release metadata. ZIP91,837bytes, SHA256
`59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`.

Scheduling tree `2caf847c1453821fbe633657c250917198bc79ae`; adapter tree
`e2357a00c59c986daef5533183f963c12a8e9ac8`. No source changed. Full15-table proposed DDL remains
SHA256 `45ca1b585f5f8bccfdff8b350b2942c65115a57ecf5c648c073fb22f21803dab`.
Native activation/options/MFA metadata, temporary migration flag and private invitation are
separately enumerated in the diff/runbook; copying files grants none of those operations.

Private ownership invitations must be outside the webroot,0600, owned/readable by the sandbox
PHP principal, in a nonpublic0700 directory. Existing private-parent ownership/traversal and
declared pool open_basedir permit that proposal by mode/configuration inspection. No actual
invitation exists or was created. ACL/effective FPM read, file custody and nonpublic access must
be verified during the separately authorized ceremony; never use a root-only unreadable file
or relax permissions to make it work.

## Gate A recovery evidence and remaining backup gate

Existing Gate A bundle and isolated recovery directory are present,0700; config recovery and
archives remain0600. Current read-only verification passed **6/6 standard and12/12 recovery
checksums**, all three recorded archive digests, readable gzip and safe tar members. The retained
functional record says12 integrity-checked tables,4,479 restored files, cloned login/dashboard/
representative-page success, zero accounts created, services stopped. Current process inspection
found no drill service. **All4,479 current website files still match Gate A's file baseline.**

The three recorded Gate A jobs remain terminal succeeded. This assignment only read existing
artifacts/evidence; it did not rerun restoration or certify the whole Plesk stack/off-server
custody. A prior backup's age being under24hours would not cover new account/MFA changes or
intervening owner edits. Before the approved change window obtain **separate R-Backup and
R-Restore authorization**, capture a fresh exact-target DB/wp-content/config/salts baseline,
verify private custody/hashes and rehearse isolated functional recovery. Do not reuse an old
whole-site restore across later website/account changes.

## Owner decisions and validation

The single decision entry point is [the updated owner worksheet](../pilot/gate-b-owner-review.md).
The [canonical staged runbook](../05-backup-rollback-deploy.md) supplies ordered execution and
rollback. It separates identity, provider choice, backup/recovery, accounts, security files,
notifications, enrollment, enforcement, scheduling files/schema, ownership, individual privileges,
fictional policy/records and human acceptance. Every execution row remains **Hold**.

Checks for this documentation assignment:31 PHP8.3 source-only syntax checks; source/ZIP/45-entry
inventory and DDL/policy digests; JSON/Markdown-link/whitespace validation;22 local deployment/
backup guard scenarios,6 mocked Plesk identity checks and14 preflight unit tests. The implementation's
748 assertions/354 browser checks/88 PHP files are **historical Sprint14 results**, not rerun
here and not protected acceptance. Detailed current evidence and terminal Gateway job IDs are
in [the sanitized readiness evidence](gate-b-readiness-evidence.json).

The safest next execution step is a narrowly approved fresh backup and isolated recovery gate
after private operator confirmation, followed by separately scoped recoverable security/account
setup. No scheduling install or database write follows automatically. Stop at every missing
approval, dependency or baseline mismatch. No onboarding/contracts/payments work is included.
