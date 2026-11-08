# Independent lesson buffers — local 0.4.15-dev candidate

November 8, 2026. No publication, deployment or existing-data backfill.

New or explicitly resaved independent lessons now calculate and persist 15-minute
preparation and wrap-up resource reservations, separate from teaching time. Larger
mandatory travel minima prevail. The same windows are used by the locked conflict,
facility-hours, resource-hours and closure checks and the actual allocations writer.
Unrelated consecutive lessons cannot merge buffers. Requested records retain a
planned window but create no allocations; their UI explicitly says not reserved.
Expired holds and cancellations are also not presented as currently reserved.

The private preview reports the actual calculated windows before save. Calendar and
weekly dashboard use the saved snapshot. Metadata is named `resource_reservations`
for both independent and family lessons; it is never accepted from a client wire
body. Historical records without a snapshot retain their existing travel-only
semantics until an explicit reviewed save. There is no automatic rewriting of the
owner's appointments. Normal resaves may therefore reveal a new buffer conflict
and must not silently shift the lesson to make it fit.

The initial 15/15 defaults remain planner parameters. A reviewed policy/configuration
UI and authorized buffer exceptions are still pending in this increment.

**Actually run:** 54 command/parser checks on both PHP 8.1.34 and 8.3.36 now cover
actual saved independent windows, unchanged teaching time, unrelated adjacent
rejection, exact half-open boundary acceptance, requested non-reservation, and a
larger mandatory travel envelope. The existing 16 notice tests pass on both versions
after the shared metadata name change. These use synthetic boundary doubles, not
real database isolation. Diff checks pass.

**Authored but unrun:** disposable database cases assert saved 15+15 and unchanged
all-table state after unrelated-neighbor rejection. Existing horse-recovery cases
now use separate instructor/arena resources so optional arrival buffers cannot
mask the intended horse-welfare assertion. The full SQL, browser and concurrency
suite still requires the unavailable disposable WordPress database fixture. No
protected browser acceptance or live scheduling change is claimed.
