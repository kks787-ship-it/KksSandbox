# Project decisions

## Continue the existing implementation

Takeover baseline: c44240e9994c5c693360bef21d9aa2757de79c8e, independently matched to the
remote Claude branch. Continue on codex/takeover-audit. No rebuild: the plugin boundaries,
WordPress-native content, repeatable setup and small dependency footprint are useful.
Correct specific defects and add targeted tests. Evidence: [takeover audit](10-takeover-audit.md).

## Keep canonical documentation

Keep numbered architecture, roadmap, deployment and installation documents as canonical.
New business context, security guidance, decision log and root AGENTS.md link to those;
no parallel architecture/roadmap/deployment document set or redundant CLAUDE.md is needed.

## Fail closed on development target detection

Deployment and backup accept only successfully detected local, development or staging targets.
Unknown output, WP-CLI errors and production are rejected before rsync/export. This prevents
a broken WordPress bootstrap from silently removing the production guard. A verified target
is still not deployment approval.

## Report failed emergency persistence

If saving emergency state fails, return an error before logging success, firing extension
hooks or purging caches. This narrow correction does not provide a transaction or exactly-once
notification semantics. Atomic confirmation consumption and state/log/outbox consistency
remain release gates for operational emergency use.

## Use current evidence for readiness

Historical 472 browser checks remain a prior report, not current validation. Local PHP/SQLite
success does not prove MySQL/MariaDB, actual Kadence, Plesk, real mobile networks, MFA or physical
tag readiness. Record blocked and unrun checks explicitly.
