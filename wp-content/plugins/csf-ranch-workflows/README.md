# Digital Ranch Workflows — LOCAL PROTOTYPE, NOT A RELEASE

Version0.0.1-dev is a separate experimental domain candidate for Issues18–20. Do not install,
activate or migrate this prototype on a farm. The current scheduling candidate remains2fd02a2,
Scheduling0.4.0/schema3/15tables. Its20 installed sandbox files remain inactive. This package
registers no routes, hooks, jobs, UI, schema installer, authorization adapter or mail/push driver.

Implemented and tested locally:

- Farm-controlled zero/one/multiple account links per horse, field-specific projections/writes,
  default denial, no self-assignment or protected/clinical/billing/welfare fields. Links are access
  relationships, never legal ownership. Membership/link removal denies subsequent reads/replays.
- Authorized educational profiles/notes independent of primary status. Sensitive sections are
  excluded; author and primary recipient are distinct. Records use facility-scoped references.
- Immediate primary self-reassignment with pending review, one effective primary, approval,
  revision-safe rejection and conflict review when a later change or ineligible former instructor
  prevents safe restoration. Audit and manager notification intents share the transaction.
- Channel-independent minimal outbox, event/recipient deduplication, claim leases, attempt history,
  current permission/contact/opt-in checks, bounded known-failure retry and uncertain-outcome hold.
  Expired leases are not blindly resent. Note intents snapshot the primary at commit and are
  suppressed if no longer primary/eligible at delivery: **prototype proposal, not live policy**.
- Appointment participant contract reads a trusted source through the transaction connection;
  no client committed flag or replacement booking/conflict engine. Drafts/failed transactions/
  exact replay do not duplicate notification events. Unassigned lessons route to operations review.

## Hard production boundaries

Six experimental table definitions are applied **only by disposable fixture code**, not a
WordPress activation hook or shipping migration. The fixture source/sink/access implementations
exist only under tests/workflows. No real account names, contact addresses or factors are inputs.

There is deliberately no production Access implementation. Before release it must consume the
existing authoritative identity/MFA/membership registry, join its transaction/lock discipline,
recheck current scopes and make revocation authoritative. Passing a trusted interface in tests
is not proof of production authorization or actor independence. The separate prototype's facility
lock does not replace the existing scheduling singleton/registry locks.

There is deliberately no production scheduler adapter. The synthetic adapter demonstrates
same-connection rollback mechanics. The prototype owns its own transaction and must not be
called inside an existing scheduling transaction. Actual integration needs a reviewed pre-commit
participant on the scheduler's authoritative transaction and canonical horse/rider identities;
never copy this into a post-commit hook and claim atomicity or duplicate the scheduling engine.

No production mail/push driver or switch exists. Only a transport explicitly declaring itself a
sink may run. A future real transport needs separately approved sender/provider/recipient consent,
verified delivery, idempotency/reconciliation and data minimization. Unknown acceptance/expired
lease remains uncertain for manual reconciliation; a reconciler/operational UI is **not built**.

Before release: canonical identity adapters, versioned migrations/backfill/rollback, management
UI, field catalog/retention/privacy review, real access-lock integration, resource/appointment
bridge, reconciliation tools and staff acceptance. No future finance/clinical/legal/emergency
rights follow from these interfaces or a scheduling role. No source pin is advanced by this work.

## Reproduce

`bash tests/workflows/run.sh` and `CSF_TEST_DB_IMAGE=mysql:8.0 bash tests/workflows/run.sh` use
existing local PHP/database images, an internal Docker network, synthetic data and sink only.
The runner applies PHP coding/compatibility/lint checks, creates only a disposable fixture schema,
executes real transaction/failure/race scenarios, and removes its own containers/network.
It never uses Gateway, real WordPress configuration, public ports, customer data or real delivery.

Current business directions stay configurable: Sunday client closure, Wednesday off-site
preference (not exclusive), seasonal/rostered Monday Academy14:30–17:00 and at most five horses
in full training. This module does not invent hours, roster/season, welfare workloads, staff
shifts or travel values, and does not modify existing welfare/clearance/conflict protection.
