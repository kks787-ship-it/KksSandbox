# Disposable gift-certificate qualification

Never point these tests at the farm, a public site, an existing owner database, or real PayPal.
Only `csf_gift_fixture` and the initially empty `csf_gift_restore` databases on loopback port
13379 are permitted. Fixture setup grants synthetic users financial rights and enrolls their
TOTP; that is not an operational provisioning workflow. Do not deploy any file in this folder.

## Standalone contracts

Run `php tests/billing/domain.php`, `service.php`, `schema.php`, and `session.php` separately.
The latter three include the 52 domain assertions; do not add cumulative counts together.
These need PHP 8.1+ with OpenSSL and use explicit fake WordPress/provider/store functions.
They cannot prove transactional SQL, browser or real provider behavior.

## Native fixture used for the recorded run

The environment has no Docker, system PHP/database service or usable AF_UNIX sockets. Official
Debian packages listed in `runtime-artifacts.json` were fetched using signed Debian trixie apt
metadata and extracted with `dpkg-deb -x` into `/tmp/csf-billing-deb/root`, without installation,
root services or maintainer scripts. Retain/verify all recorded SHA256 values. This supplemental
runtime must not be substituted into the protected farm recovery qualification.

The following setup is operator-driven, not yet a portable one-command CI bootstrap:

1. Private root `/tmp/csf-billing-fixture` (0700), `site`, `data`, `run` directories. PHP wrapper
   `/tmp/csf-billing-fixture/php` invokes extracted `usr/bin/php8.4` with the extracted library
   path and php.ini. Modules: mysqlnd, mysqli, mbstring, dom, xml, xmlreader, xmlwriter, curl,
   iconv, phar, ctype, tokenizer, fileinfo, simplexml. Keep display_errors off, logs private.
2. Bootstrap the extracted MariaDB datadir, then run unprivileged, TCP **127.0.0.1:13379**,
   `--socket=` and no external listener. Create only the two named empty databases and user
   `csffixture`@127.0.0.1, password `synthetic-local-only`, with rights scoped to these DBs.
   No farm credentials. The fixtures use this explicitly synthetic credential.
3. Extract official WordPress 7.1.4 into `site` (archive SHA1
   `b175879c4fb2d35300f9abe1153da9b543a35a2b`). Use existing pinned WP-CLI 2.12.0 at
   `.wp-local/wp-cli.phar` (SHA256
   `ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c`). Configure database
   `csf_gift_fixture`, prefix `gift_fixture_`, loopback port13379; random fresh salts. Install
   with URL `https://127.0.0.1:18443`, title `Synthetic Gift Fixture`, synthetic admin
   `fixture_admin` / `synthetic-local-admin-only`, email `fixture@example.invalid`, skip email.
4. Symlink the repository `csf-billing`, `csf-mfa`, and official Two-Factor0.17.0 plugin into
   site plugins. The provider must be the verified official ZIP (SHA256
   `59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`), all 21 files checked,
   at `.wp-local/mfa-official/two-factor`. Symlink this folder's `fixture.php` into mu-plugins.
   Its strict path/database/local guard confines all mocks/fault injection to this fixture.
5. Include private `/tmp/csf-billing-fixture/config.php` from wp-config after DB_NAME. Assert
   DB_NAME is `csf_gift_fixture`; local environment; DISABLE_WP_CRON and DISALLOW_FILE_EDIT;
   CSF_MFA_ENABLED, CSF_BILLING_FIXTURE_MOCK, CSF_BILLING_SANDBOX_ENABLED and
   CSF_BILLING_WEBHOOK_QUALIFIED true. Generate a new persistent base64 32-byte AES key
   as CSF_BILLING_DATA_KEY (0600 file, never Git). Never rotate it during restore.
   Use synthetic client ID/secret, merchant `TESTMERCHANT123`, webhook `SYNTHETICWEBHOOK123`.
   Catalog product: ID `11111111-1111-4111-8111-111111111111`, revision1, label
   `SYNTHETIC test package`, 2 rides, 60 minutes, amount_minor12345, USD, one_time, approved=true.
   Terms: version synthetic-1, text `SYNTHETIC terms for disposable tests only. Not Beth’s
   agreement or a real offer.`, its exact SHA256, approved/legal_reviewed=true. These flags
   are synthetic test data, never evidence of real owner or legal approval.
6. Activate only billing, MFA and official provider. Run `wp eval-file tests/billing/setup.php`
   once on this new site. It applies proposed schema2, creates synthetic subscriber gift_operator,
   financial grants, official TOTP/backup enrollment, synthetic page and operator.json. It
   intentionally fails on repeat; never force-reset an existing fixture. Save a private backup
   before intentionally replacing any fixture data.
7. Run PHP HTTP servers on **127.0.0.1:18080** and **:18081** against site, with a front-controller
   router returning existing files or site/index.php. Set HTTPS=on only in this synthetic
   loopback fixture config. Use a self-signed IP-SAN certificate/key at root cert.pem/key.pem;
   Node TLS proxy `tls.cjs` listens on **127.0.0.1:18443**, forwards to18080, or18081 for
   X-Fixture-Lane:B. No external ingress. Browser test trusts the fixture only; Python trusts
   this exact certificate. The two-worker header proves actual separate PHP workers.
8. Run tests in the **same process/network namespace** as the servers. This environment's
   separate exec calls cannot see each other's loopback listeners; a persistent shell is needed.
   Run `python3 tests/billing/http-integration.py`, then
   `CSF_TEST_CHROMIUM=/tmp/csf-billing-deb/root/usr/lib/chromium/chromium-headless-shell node tests/billing/browser.cjs`.
   Use npm ci from the existing lockfile. Headless-shell and chromium-common must match exactly.
   Official provider replay protection can require the next naturally occurring TOTP period
   between fresh logins; do not disable replay checks or reset enrollment.
9. Stop test clients; run `python3 tests/billing/recovery.py`. It refuses a nonempty restore DB,
   captures fresh private files/SQL, checks exact file/DDL/row equality, then applies explicit
   isolation overlays. Run `tests/billing/recovery-browser.cjs` with the same browser environment
   variable; it starts/stops separate loopback18082/18444 servers, verifies closed page, absent
   checkout route, restored official password/TOTP login and outbound blocks. Existing source
   sessions/enrollment are never copied into a fabricated active browser proof.
10. Shut down all fixture servers/database; retain private evidence/backups. Do not commit key,
    SQL, cookies, provider options, certificate numbers or screenshots containing those values.

Evidence JSON/logs/screenshots are under the private root. Real external PayPal APIs remain
mocked by the MU fixture. The mock deliberately allows synthetic approval and injected failure
files; the isolation guard is mandatory. Tests are destructive only to explicitly synthetic
fixture throttle state, grants and expired session proofs. They do not reset MFA enrollment.

## Gaps

No real merchant/signature qualification, exact pinned browser, grant-revocation race,
connection-loss-at-COMMIT recovery, or protected-target recovery claim is made. The supported
native matrix and same-capture/injected-COMMIT-failure tests are now completed below. Port this guarded fixture to approved disposable CI before extending
those claims. Current farm recovery pins/allowlists must continue rejecting billing schema2.

## Supported native matrix continuation

`native-matrix.py` uses extracted official Ubuntu jammy PHP8.1/MariaDB10.6/MySQL8.0 and
noble PHP8.3 packages; exact artifacts/results are in `native-matrix-evidence.json`. Verify
Ubuntu keyring SHA256 then signed apt metadata before downloading; extract without maintainer
scripts. Never install packages globally. Keep MySQL and MariaDB in separate binary/datadir
roots (their executable names overlap). Private prepared roots: `/tmp/csf-gift-matrix/jammy`,
`noble`, `mysql`, `mariadb-data`, `mysql-data`; PHP wrappers `php8.1`/`php8.3` load their matching
ABI modules. Wrapper library paths must resolve the extracted dependencies, never alter the
system linker or global environment.

Prepare fresh MariaDB datadir with mariadb-install-db --no-defaults and fresh MySQL datadir
with mysqld --no-defaults --initialize-insecure, both unprivileged under the scratch root.
The runner starts each on loopback13379, socket empty; MySQL has mysqlx=OFF and
secure-file-priv=NULL. It creates only the known synthetic account/database and imports the
previous verified synthetic backup after checking its evidence hash and an empty table list.
Set `CSF_MATRIX_DATABASES=mariadb,mysql` (default) or one name. Run from repository root.
It starts two PHP workers and TLS proxy in the same namespace, executes database.php and
http-integration.py on both PHP versions, saves per-cell private logs and terminates children.
The HTTP client accepts only the compiled fixture/native wrapper paths, never arbitrary PHP.

To rerun only the SQL failure probes on existing initialized fixture databases, set
`CSF_MATRIX_PROBES_ONLY=1`. No import, HTTP flow, financial record reset or TOTP reset occurs;
only unique scratch-prefix billing tables are created/dropped. Source SQL/HTTP evidence is
preserved. These probes inject a SQL failure *instead of* COMMIT; they do not simulate a lost
network acknowledgement after an actual successful commit.

The runner relies on the explicitly prepared local fixture; it is not yet a general CI
bootstrap or the protected exact-pin recovery runner. Native Ubuntu backported package
versions satisfy the PHP/database version matrix, but do not substitute for pinned Docker
image identities or protected-target qualification.

## Proposed billing recovery qualification

`CSF_BILLING_RECOVERY_BROWSER=1 python3 tests/billing/recovery-run.py` runs the supplemental
native recovery candidate using the prepared jammy runtime, native PHP8.3 wrapper, synthetic
site and prior hash-bound private capture. Every invocation initializes a **new** datadir
under `/tmp/csf-gift-matrix/recovery-qualification-*`; it never resets prior source/restore
databases. It seeds unresolved payment states through the real service/mock provider, then
runs recovery.py, recovery-contract-test.py, recovery-key.php and optional restored browser
login. Snapshot/key material remains in private capture directories. All payment routes stay
disabled in the restored site. Candidate reports are not deployment approvals.

`recovery-missing-key.php` also runs standalone on PHP8.1/8.3. `recovery_contract.py` is a
proposed offline invariant layer only. It does not replace qualified target binding, physical
schema/custody controls or exact-pin runtime isolation. Review
[the proposal](../../docs/pilot/billing-recovery-proposal.md) before any further integration.


## Draft CI and combined exact-runtime rehearsal

The authorized draft PR runs `.github/workflows/billing-disposable.yml`. It checks contracts on
PHP8.1 and PHP8.3, syntax/coding standards, and deployment guards. `ci-prepare.sh` obtains verified
official dependencies and the original pinned interpreter/images. The unchanged qualified
scheduling recovery suite must pass before `combined-recovery.sh` runs its separate synthetic
33-table candidate. No farm data, protected secrets, live provider calls or uploaded artifacts
are used. Docker and the original disposable namespace prerequisites are required.

`combined_recovery.py` verifies exact table/file restoration, billing invariants, key/tamper
checks, real official TOTP login and closed checkout over isolated HTTP, and preservation of
newer source data. Each absent billing table is rejected and the original qualified table
contract must still reject the expanded schema. Clone credentials cannot access the source DB.
This is candidate qualification, not a replacement for the qualified contract or target-bound
recovery gates. See PR27 for final exact-head CI results and the recovery proposal for gaps.
