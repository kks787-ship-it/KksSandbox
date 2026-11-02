# Corner Stone Farm pilot policy proposals

These files are review inputs for Sprint #11, **not applied configuration**. Use the canonical [deployment checklist](../05-backup-rollback-deploy.md#sprint-11--phase-1-evidence-and-approval-checklist-november-2-2026) and [preflight report](../test-results/scheduling-pilot-preflight.md). Operators perform approved initial configuration; Beth/Kris use native staff forms.

| Proposal | Purpose and approval status |
| --- | --- |
| [Operating draft](csf-operating-policy-unconfirmed.json) | Intentionally incomplete; all services disabled, operating hours/workload/durations/capacities unknown. It must remain unready for bookings. |
| [Synthetic test policy](csf-synthetic-policy-proposed.json) | Fully specified fictional fixture proposed for owner approval; does not describe approved real operating or horse-care policies. |

Proposed independent timezone is America/Los_Angeles for Omak; WordPress global UTC stays unchanged. Existing documented business requirements retain training enrollment cap5 and preferred off-site Wednesday (numeric3). Sunday closure is carried into the test proposal. Real opening hours, per-service capacity/duration, horse recovery/workload, travel buffers and individual suitability/availability need owner decisions. Monday Academy timing is already documented, but unknown season/capacity is not invented; the operating draft keeps Academy unresolved. Synthetic Academy is disabled; group rotations are manual, not recurrence implementation.

The **fictional test proposal**, subject to explicit approval: Monday–Saturday09:00–18:00, Sunday closed; lesson/group/haul_in/off_site/horse_training enabled, each60minutes; group capacity4, others1; daily horse workload300minutes/consecutive120/recovery15; travelbuffer15. These numerical values are test inputs, not veterinary recommendations. Private lessons allow school/client horses; haul-ins/off-site require client horses; groups/training use school horses. Required resources are explicit in the JSON. All synthetic resources still need finite availability, suitability and welfare records; policy validation does not make them automatically available.

After approval, use labels prefixed **Pilot**: four fictional riders, two fictional school horses, one fictional client horse, two instructors, two arenas and one off-site location. Do not copy real people/horse records or imply instructor resources are WordPress staff accounts. Select finite upcoming local dates with the owner; test DST gap/fold on disposable fixtures, and safely verify local display/offsets on the protected pilot. Preserve records/audits for review. Confirmed scheduling state never proves rider clearance or a signed agreement.

Required before installation/provisioning: owner-approved operator and distinct Beth/Kris account IDs, actual MFA evidence and chosen permission scope. A missing account or MFA mechanism is a separate security/account-management decision; no inferred identities, automatic user creation or extra plugin installation is authorized. Required before policy application: approve this fictional test policy or provide replacements through the controlled operator workflow. Actual business policies remain a later operational gate.

[Installation diff](scheduling-install-diff.json) lists every added file/hash. [DDL](scheduling-v2-TXGEh-proposed.ddl.txt) is source-derived review only, not executable deployment instructions. [Evidence](preflight-evidence.json) omits account identities and credentials.
