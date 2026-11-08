# Family visits: isolated reservation planner increment

November 8, 2026. Local development only; not published or deployed.

**Later integration:** the 0.4.12-dev command/UI candidate is described in
[scheduling-family-visits.md](scheduling-family-visits.md). The sections below record
the earlier isolated-planner increment; their integration TODOs are historical, not
a claim that the current candidate is still an unconnected helper.

## Implemented and tested

`CSF_Scheduling_Visit_Plan::calculate()` accepts normalized, explicitly linked
lesson IDs, UTC teaching instants, and resolved instructor/arena/location resources.
It returns each individual teaching interval, each resource reservation, exact
adjacent sibling IDs and proposed resource conflicts. It never writes, authorizes,
sends a notification or claims availability against the database.

The pilot preparation/wrap-up defaults are 15/15 minutes, exposed as explicit
parameters for later reviewed policy configuration. Identical contiguous timestamps
alone never establish a family relationship. Sharing requires a non-null equal visit
UUID, the same complete resource footprint, instructor and arena/location context,
and explicit compatibility on every resource. Missing context or a gap retains
independent buffers. Mandatory resource minima survive sharing; any resulting
collision is reported instead of waived. Horse occupancy/recovery remains outside
this helper and must still pass the existing authoritative gate.

The two 30-minute sibling blocks produce separately itemized 09:45–10:30 and
10:30–11:15 resource reservations, giving the approved 09:45–11:15 envelope.
120+120+60 produces five teaching hours plus 30 outer minutes. Omitting a canceled
lesson recalculates the survivor's buffers without changing its teaching time;
omitting a middle lesson splits the chain at the gap. These are calculations,
**not a completed cancellation command**.

Actual isolated executions using the temporary WordPress Playground PHP WebAssembly
runtime passed **103 checks on PHP 8.1.34 and 103 on PHP 8.3.36**. Coverage includes
both owner examples, single/first/middle/last survivors, unrelated clients,
resource changes, incompatible turnover, mandatory pre/post travel, overlapping
teaching, one-minute gaps, half-open boundaries, repeated DST hours, configurable
asymmetric defaults, malformed inputs, ambiguous branching, and varied chains of
2–20 lessons. Tests execute the real planner and UUID validator, without a database
mock. Added to the disposable fixture runner. No new runtime was installed globally.

## Integration still required; do not enable as a booking feature

The planner is deliberately not loaded by the plugin entry point or exposed to
requests. Existing appointments and their reservation semantics are unchanged.
The following work is still outstanding, not blocked by a publishing decision:

1. Add the versioned visit linkage/command contract under the Store singleton lock,
   with stable group and command IDs, per-item authorization and exact replay.
   Resolve input from authoritative policy/resources; never accept client-produced
   reservation intervals or compatibility flags as authority.
2. Stage a complete group projection including edits/cancellations. Validate every
   item and every newly exposed outer buffer against existing occupied resources,
   facility/resource hours, closures, rider conflicts, travel and horse welfare.
   Commit all itemized records, reservations and history together, or none. Preserve
   other teaching times and cancellation history; never drop reservations outside
   a transaction or exclude unrelated bookings from collision checks.
3. Require reviewed updates when membership/holds change. Expired holds must not
   leave a survivor with an unprotected newly exposed outer buffer. A group preview
   cannot substitute for locked revalidation at write time.
4. Integrate the compact family-session editor and an authoritative read model for
   preview, calendar and homepage. Only describe buffers as reserved after commit.
5. Add explicit segment override permission, audit and protected indicators without
   granting it to any account or weakening mandatory constraints.
6. Run real MariaDB/MySQL rollback, exact replay, stale revision, simultaneous group
   booking/edit/cancel, hold expiry, resource-hours/closure races and browser/access
   acceptance. The current environment has no native database/WordPress fixture;
   these tests were **not run** and pure planner checks do not establish them.

No schema change, source activation, data migration, real family records, email,
staff grants, MFA changes, payments, emergency/public booking or live operation
occurred in this increment. The prior schema-4 horse candidate still independently
requires an updated reviewed recovery contract and rehearsal before deployment.
