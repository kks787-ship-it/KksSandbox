# Any rider eligibility — November 9, 2026

Adds an explicit per-horse Any rider checkbox. It includes future rider profiles without individual horse/rider linking. Existing records default to restricted lists; no horse settings are bulk changed. Selection remains independent of rider clearance, service eligibility, archive state, availability, conflicts and welfare limits. The option is authorized by suitability_manage and stored by the existing revision-checked audited horse command. No physical schema migration.

Validation on private synthetic PHP 8.3 fixtures: 23 domain checks, 4 form rendering assertions, 5 authorization checks, 46 existing mapping checks and 33 lifecycle checks passed. All scheduler PHP files pass syntax checks. No live booking or browser acceptance claimed; full database/browser suites and PHPCS were not rerun in this environment.

Candidate based on installed 339c7e1. Protected sandbox deployment requires exact pre-change source comparison, current verified backup/functional restoration, pre/post file/configuration and row-hash checks. The complete backup captured about 22 minutes before preflight remains verified and unchanged at that preflight; rollback retains the previous plugin directory. Old code treats Any rider horses as restricted to their retained rider list, so a file rollback is conservative but temporarily removes this convenience. Never restore a database over later owner edits.

## Installed and independently verified

Scheduling 0.4.19-dev, source 921c19f6590b993c62c611d70e35636012a3916b, is installed on the protected sandbox. Four PHP files were replaced by an atomic plugin-directory exchange; previous source remains privately available for file-only rollback. Independent complete-root hash verification, file ownership/modes, protected configuration hashes, schema and canonical row comparison passed; zero database rows changed. The initial direct snapshot equality check included the capture timestamp and reported drift; canonical schema/row comparison resolved that diagnostic false positive. No horse record, real booking, account or notification was changed. Browser acceptance remains unrun.
