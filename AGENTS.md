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

Current sandbox target is owner-confirmed as https://sandbox.cornerstonefarmomak.com,
Plesk-relative root sandbox.cornerstonefarmomak.com, assigned DB wp_mdf4x. Production DB
wp_iacb7 and unassigned wp_mdoaq are excluded. Do not guess the absolute root. Current scope
is read-only preflight/dry run only; use scripts/sandbox-preflight.py to avoid WordPress
bootstrap writes. See docs/test-results/sandbox-preflight.md for unverified checks/access
blockers. Test guards with PYTHONDONTWRITEBYTECODE=1 python3 tests/scripts/test_sandbox_preflight.py.
