# Corner Stone Farm development

Current owner-adopted Supervised Autonomy Policy (November2): see docs/DECISIONS.md.
Bounded read-only development-sandbox diagnostics/repeats and local engineering/commits are
prospectively authorized; record reasons/scope/outcomes. Earlier per-attempt diagnostic holds
are historical and do not block ordinary troubleshooting after adoption. No production,
destructive action, account/password/privilege/MFA/security change, installation or migration
is granted. Stop for unexpected security findings/consequential changes. Recovery/deployment/
business/security approvals remain separate; preserve source2fd02a2.


Latest Nov2 identity correction: read the Accounts & Access section in docs/01-architecture.md,
owner register and current Gate B worksheet. HTTP Basic Auth and Plesk control-panel access do
not identify the Plesk-created WordPress Administrator or prove farm ownership/MFA. Its human
controller/UID remain privately unverified. Recommend retaining technical admin unchanged and
separately approving Kris's Subscriber business identity and Beth's staff identity; do not infer
accounts from login names. Preserve current distinct-UID browser bootstrap and source2fd02a2.
Separate accounts are not proof of separate humans. Accounts & Access is design only; no server,
account, MFA, grant, schema or installation action is authorized by the correction.

Issue #14 current local candidate: scheduling0.4.0 / csf-mfa0.2.0, same schema3/15 tables.
Read docs/test-results/scheduling-security.md and the current Gate B package override before
security work. Official Two-Factor0.17.0 ZIP bytes/all21 files are now verified; the Issue13
source-build limitations below remain historical evidence. CSF_MFA_ENFORCE_ADMIN is a separate
default-off approval decision, never an implicit deployment setting. Ownership needs a finite
private invitation and independently MFA-authenticated operator/owner browser approvals (plus
outgoing master for transfer), not the retired CLI flag. Never re-enable that historical CLI
ownership path or copy fixture seeding/probes to a farm. GateB identities, MFA files/enforcement,
source/schema, bootstrap and every pilot write remain independently held.

Continue the existing plugins; do not rebuild without evidence and a documented decision.
Use the existing checkout in this isolated cloud task; do not create a worktree unless requested.
Work on a separate development branch; preserve Claude's branch and existing user changes.

Read README.md, docs/CSF-BUSINESS-CONTEXT.md, docs/01-architecture.md,
docs/02-roadmap.md, docs/10-takeover-audit.md, docs/SECURITY.md, and docs/DECISIONS.md.
Canonical deployment instructions: docs/05-backup-rollback-deploy.md and
 docs/07-website-installation.md. Extend these documents instead of creating duplicate
ARCHITECTURE, ROADMAP, or DEPLOYMENT files.

Keep business logic in plugins, presentation in Kadence/Gutenberg, and emergency data
separate from private portal records. No WordPress core edits or proprietary page builders.
Use the original owner-supplied logo and authentic photography. Never fabricate business,
legal, medical, horse, pricing, testimonial, or emergency information. Track unresolved
publication decisions in docs/03-owner-confirmation-register.md.

Never change production files, databases, DNS, or Plesk configuration without explicit
approval. Prepare a concrete, reviewable development release and rollback plan before
requesting deployment approval. Back up before development deployment. Emergency features
remain non-operational until owner acceptance, phone/physical tag tests, cache verification,
MFA, and drills. Queued notifications are not delivered notifications.

Use composer install and npm ci with existing lockfiles. Validate PHP with composer lint
and php vendor/bin/phpcs. Run bash tests/scripts/development-guards.sh, bash tests/scripts/plesk-preflight.sh and, only on the
disposable local WordPress site, wp eval-file tests/integration/takeover.php and
python3 tests/integration/emergency-http.py. The HTTP checks require localhost:8890.
Tests use synthetic data and restore state; never point them at the Plesk or production site.
Run browser tests when Chromium is available; distinguish historical results from reruns.
The full browser runner rewrites docs/test-results/phase-2.md; preserve that historical
report unless intentionally refreshing it, and do not use --force setup on owner-edited data.

Cloud tooling, if retained: export PATH=/workspace/tooling/bin:$PATH and
PLAYWRIGHT_BROWSERS_PATH=/workspace/tooling/browsers. See saved environment startup instructions.
No credentials or personal data in code, reports, commands, or commits. Use descriptive
commits, review git diff, and keep test results and deployment blockers explicit.

Current sandbox target is https://sandbox.cornerstonefarmomak.com, absolute root
/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com, configured/connected
DB wp_mdf4x, options TXGEh_options. Production root is the sibling httpdocs directory and DB
wp_iacb7; both production and unassigned DB wp_mdoaq are excluded from changes.

On October 31, 2026, the owner reports Gateway backed up the original sandbox wp-config to
/root/csf-config-backups/sandbox-wp-config-before-20261031.php.bak, then applied and verified
WP_ENVIRONMENT_TYPE=development, DISALLOW_FILE_EDIT=true, WP_DEBUG_DISPLAY=false, PHP syntax,
ownership and live file mode 0600. These changes are COMPLETE: do not reapply or request
hardening approval again. Preserve the backup privately as configuration-only recovery evidence;
it is not a full website/database backup or restore test.

Gateway completed preparation on October 31, 2026: full backup at
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/, archive/checksum
verification, isolated scratch DB restoration (12 InnoDB tables plus representative counts),
and filesystem restoration (4,336 regular files, zero mismatches). Functional WordPress
login/page rendering on the restoration was NOT tested. Do not describe the structural/file
restore as unperformed or claim functional recovery passed.

Private source is already staged/pinned to d360dbfd5a0441fa142a3b963a4a42c5145d9738; plugin
trees match reviewed 52401dcd9a90ca87ad074c1078ad6633df5be77e. Gateway reports exact-target
Plesk preflight and deployment dry run passed, proposing only two new CSF plugin directories.
Do not repeat full preparation, recreate scratch resources, restage source or move the pin
because documentation advanced. Retrieve raw identifiers/output from existing Gateway records.

Gateway supplied DB export capture October 31, 2026, 10:08:58 AM PDT (17:08:58 UTC) and
full file archive capture 10:09:42 AM PDT (17:09:42 UTC). Existing scratch DB is
csf_sandbox_restore_20261031; private restored files are not publicly served. Existing evidence:
/var/www/vhosts/cornerstonefarmomak.com/private/csf-sandbox-backup-20261031/restore-evidence.txt.
Reuse it; do not recreate the evidence/restore or request these known identifiers again.

Latest owner correction: csf-core 0.2.0 is installed/active on the sandbox, all 21 pages
are published, do_blocks passed with one H1 each, primary/mobile/footer menus are assigned,
and Home is static front page. Permalinks are /%postname%/, 162 rewrite rules, standard
sandbox .htaccess installed and Apache syntax passed. Do not repeat installation/setup or
permalink work. Site Footer synced block 27 exists but is NOT placed in Kadence Footer Builder.
Emergency remains inactive. The historical d360dbf staging/dry run is not a test of new code.

Newer remote roadmap commits 52b882e/457fffb report real-Kadence Issue #1: shared intro text
reaches the viewport edge while headings are inset, observed on Meet Beth and Camps/Clinics.
This supersedes the earlier no-confirmed-defects clarification. A shared CSS candidate is
now reproduced/corrected on a disposable local downloaded Kadence 1.5.2 fixture; the actual
protected sandbox remains untested. Do not claim its acceptance passed or close Issue #1. Start authenticated 390/768/1280px review on the six priority pages, then audit
all 21 pages, other text sections and Gutenberg; preserve full-bleed backgrounds and content.
HTTP 401 without Basic Authentication is expected, not a broken route. Do not redesign or
change Kadence meta speculatively. Original logo/photos, header/palette/typography/footer
configuration, 23 owner notes and approved public contact channels remain outstanding.

Current local candidate: csf-core 0.3.0; source has not been deployed. Inquiry recipient is
owner-confirmed beth.siemon@cornerstonefarmomak.com; delivery service is unknown. Owner asks
that the form remain disabled until delivery is verified. Never send a live test email or
enable the form without the relevant authorization. No new plugin is needed for theme setup.

No new code deployment, emergency activation, site setup, permalink or production changes
are authorized. A future core-only update uses --only-core, a reviewed exact source SHA,
current target-bound backup/evidence gate and separate approval. The old complete backup
predates the installed site build: preserve it and its structural evidence, but do not claim
it captures the current 21-page state. Functional restored-site login/rendering remains unrun.
This cloud session has no callable Gateway tool; owner-reported remote results are not
commands executed here. See docs/07-website-installation.md and docs/test-results/website-finishing.md.

See docs/test-results/sandbox-preflight.md, docs/test-results/sandbox-restoration-evidence.md
and docs/05-backup-rollback-deploy.md. Preserve deployment guards. No reapplication of the
completed constants or redo of completed preparation. Test sandbox guards with
PYTHONDONTWRITEBYTECODE=1 python3 tests/scripts/test_sandbox_preflight.py.

Remote branch also records completed fresh gate bundle csf-dev-20261031-184634-36879, verified
before approved file copy/activation/setup. This is complete, not another pending preparation
task. Preserve job provenance in docs/05-backup-rollback-deploy.md. That bundle predates the
now-published build; a future approved update needs a current backup, not a repeated full
infrastructure restoration. Do not claim either historical bundle captures later owner edits.

Latest user scope: finish local layout fixes and prepare review; private Basic Auth file and
sandbox network access are NOT provisioned/verified. Gateway SSH access does not transfer.
The local test theme came from the public CDN mirror, not the VPS; preserve that distinction.
Safe non-forced Contact upgrade is tested from the actual reviewed 0.2.0 template: unedited
page gains one block, edited/no-hash page is kept and warns for manual placement. No force,
automatic enablement or live Contact mutation. See docs/test-results/kadence-layout.md and
updated Contact integration in doc 07; any live content operation still needs approval.

Issue #2 confirmation correction is part of the same undeployed 0.3.0 candidate: only an
accepted POST issues a five-minute site-bound signed receipt. Public flags/challenges cannot
acknowledge submissions, and a valid receipt can be claimed once. No PII in receipt URLs or
stored expiry markers; never log raw proofs. Keep form disabled. New local integrity/HTTP
checks do not establish Plesk/MariaDB acceptance or delivered mail. Before any later approved
apply, Gateway needs current target-bound backup and a core-only comparison of the new SHA.


Current scheduling work follows Issues #3/#4 and the latest decision register: the provider
is canceled, all public scheduling CTAs use Contact Beth to Schedule → /contact/, inquiries
stay disabled and emergency inactive. Source/migration tests and runtime blockers are in
docs/test-results/scheduling-contact.md. Internal calendar architecture is in doc 01; do not
mistake its proposed schema and acceptance matrix for an implemented staff scheduling MVP.


Validation follow-up: existing private Gateway fixture validation supersedes the earlier
cloud-only runtime blockers; see the current section in docs/test-results/scheduling-contact.md.
The corrected structured-content browser assertions passed 19/19. The owner approved public
call/email values in doc 03; settings publication remains a separate live-content operation,
and SMS/emergency use is not inferred. The calibrated candidate preview proposed all 21 page
updates without writing them. The doc 01 architecture includes Issue #4 business-rule review
refinements; keep it design-only until unresolved capacities/policies and roles are reviewed.


Latest owner approval and completed release: steps 2 and 3 explicitly approved the 6a46521
core-only deployment and separate 21-page/menu/public-contact update. These are COMPLETE;
do not repeat them. Fresh private backup csf-dev-20261101-065451-968013 and Gateway job
provenance are in docs/test-results/scheduling-contact.md. All 21 saved pages now match the
new source; items 48/51 and public call/email updated; no force/setup apply/permalink change.
Protected real-Kadence review has now run at 390/768/1280px: all 63 scheduling checks pass;
42 mobile/desktop views pass overall and 21 tablet views share one unnamed header-home-link
axe violation. Demo site title/default footer and missing CSF footer placement remain. The
secure value is saved in Gateway; temporary server credential file removed. Never put
credentials in chat or Git or disable protection. Inquiry stays off, emergency inactive/unchanged,
production excluded. The calendar architecture remains design-only.

Issue #8 foundation is now implemented locally in csf-scheduling 0.1.0. See doc 01's v1 contract
and docs/test-results/scheduling-foundation.md. No sandbox installation or migration is approved.
Activation has no scheduling writes/grants. Use tests/scheduling/run.sh on disposable internal
Docker fixtures only (MariaDB/MySQL, synthetic data). It never uses the protected fixture/config.
The foundation deliberately has no appointment writer/confirmation/UI. Issue #9 requires a new
versioned schema increment and the audited resource/eligibility service before a staff calendar.
Existing core/emergency source and deployment/installation scripts stay untouched.

Sprint #9 now extends the foundation locally: csf-scheduling 0.2.0 / schema v2 adds the private
staff calendar and audited transactional appointment commands. Read doc 01's implemented v2
contract and docs/test-results/scheduling-calendar.md before changes. Preserve the original
foundation dispatcher, actor-bound command replay and singleton lock. The test runner now
covers populated-v1 upgrade/restore and real two-worker booking races, with optional
CSF_TEST_BROWSER=1 for the synthetic loopback browser fixture. No protected install or live
migration is approved. Inquiry disabled, emergency inactive, website/deployment scripts untouched.

Sprint #9.1 / Issue #10 now adds local 0.2.1 native staff fields, local-time choices and private
nonce/allowlist-protected preview tools. Schema remains v2/14 tables. Preview uses the same
Store transaction and rolls back; it never reserves. Preserve exact request replay, current
policy/time validation, roster/turn identities and clear confirmation-versus-evidence warnings.
See docs/13-scheduling-staff-acceptance.md and docs/test-results/scheduling-staff.md. Initial
policy/provisioning stays controlled operator work. The pinned sandbox pilot is a proposal in
the canonical deployment runbook, not permission to install/migrate/provision. No production,
public self-booking, inquiry enablement, emergency activation or website setup changes.

Issue #12 local candidate is now scheduling 0.3.0/schema 3: Team & Permissions, protected verified
master identity, editable groups and individual grant/deny/inherit rights. Read the implemented
contract in doc 01 and docs/test-results/scheduling-permissions.md before changing authorization.
Use the one transactional authorization registry and existing singleton lock/ledger/audit; do not
restore blanket four-capability provisioning. The old 0.2.1 pin/15-file manifest is historical,
not permission to install it or substitute the new 19-file candidate. Gate B remains held.
Independent MFA integration defaults false; no provider/bypass is bundled. The synthetic adapter
in tests/scheduling/auth-fixture.php is strictly disposable-fixture-only and must never be copied
to a farm. Master bootstrap/transfer needs its separate guarded numeric-ID/MFA/URL approval;
routine staff/group changes use the native reviewed dashboard. No actual accounts, real grants,
protected migration, provider installation, inquiry enablement, emergency or production changes.

Issue #13 local candidate: separate csf-mfa0.1.0 for exact Two-Factor0.17.0 source, scheduling0.3.1
/schema3 unchanged physically. Read docs/test-results/scheduling-mfa.md before security edits.
MFA enrollment does not authenticate a session; both hooks default false. Do not add fake verifiers,
CLI exemptions, cookie export or API bypasses. The existing CLI master identity ceremony remains
blocked pending a separately reviewed browser-to-identity handoff. Mandatory WP-admin enforcement,
provider artifact verification/selection, actual identities and protected compatibility are held.
Use tests/scheduling/mfa-run.sh only on disposable local synthetic fixtures; fake/test provider
probes never ship. Preserve owner source/tracker commits and all GateB independent approvals.
