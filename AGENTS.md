# Corner Stone Farm development

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

Next: prepare/verify the fresh target-bound gate bundle in the script's required format using
existing records/evidence. The reported DB capture reaches 24 elapsed hours November 1, 2026,
9:08:58 AM PST (17:08:58 UTC; DST ends overnight). Actual script artifact metadata/layout
still needs checking. Retain the structural-pass/functional-test-not-run distinction. Only
refresh a limited gate backup if actual staleness/incompatibility warrants it and is authorized.
Do not touch timestamps to fake freshness. Files-only installation, activation, site setup,
permalink changes and production deployment remain unauthorized; see the staged plan in
docs/07-website-installation.md. The latest preflight pass is Gateway-reported; its exact
command was not supplied, so do not invent a particular helper's result or a permalink change.
Gateway evidence is owner-supplied, not proof this Codex session executed remote commands.

See docs/test-results/sandbox-preflight.md, docs/test-results/sandbox-restoration-evidence.md
and docs/05-backup-rollback-deploy.md. Preserve deployment guards. No reapplication of the
completed constants or redo of completed preparation. Test sandbox guards with
PYTHONDONTWRITEBYTECODE=1 python3 tests/scripts/test_sandbox_preflight.py.
