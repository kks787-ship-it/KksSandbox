# Schema-4 recovery preparation — not functional qualification

November 8, 2026. Local-only source preparation; no farm access, backup capture,
installation, migration, restore execution or private command binding in this work.

The canonical metadata SELECT generator now distinguishes complete/damaged v4 from
v3 and partial migration, preserving the same exact 12 native + 15 scheduling
table allowlist. Unknown/malformed versions and extra tables remain unqualified.
Capture must explicitly expect v4; pre-scheduling or damaged state cannot satisfy
that expectation. The generator digest pin and deterministic public inspection,
capture and restore payloads/checksums were regenerated from reviewed local source.

Restore runs the existing relationship checks and actual restored application's
schema-readiness probe for both v3 and v4. It cannot qualify by table count alone.
A v4 data snapshot paired with v3 source fails application readiness. Integrity,
namespace isolation, functional pages/login, cleanup and source-binding checks
remain required. No existing private command, approval or backup freshness is
inherited by these new payload bytes. The historical inactive-staging builder
remains pinned to its historical plugin and intentionally refuses this candidate;
only its shared-source dependency checksum list was refreshed.

**Actually run:** all 283 Python synthetic recovery tests pass, including three new
v4 cases for complete/damaged sets, malformed/future versions, schema-digest drift
and rejection of a preinstall capture against a v4 expectation. Public payload
checksum verification and PHP/JS/shell syntax checks pass. These are local contract,
failure-path and packaging tests, not an actual isolated functional restore.

The guarded offline recovery fixture now expects schema 4 for the current plugin
and includes archived-horse, family membership, resource-window/override and held
notice metadata. Its original source-row/file preservation, login/logout, cleanup,
damaged/partial state and replay protections remain. The fixture was NOT RUN:
Docker/MariaDB/native PHP and its pinned runtime images are unavailable here. Its
metadata is synthetic recovery coverage, not full booking workflow acceptance.

Before deployment: qualify the actual candidate on disposable WordPress/MySQL or
MariaDB; run real races and protected HTTP/browser/MFA checks; run the offline
functional restore fixture; review the exact new payload/source inventory; then
obtain a fresh complete target-bound backup (<24 hours), usable isolated functional
recovery evidence, transparent preflight and separate independent post-write
readback under the existing owner policy. Source-only rollback to 0.4.9 or an older
v4 development candidate is unsafe for current JSON/booking semantics. Restore a
coherent reviewed source/data pair without overwriting newer owner data.

Publication is still held after the earlier rejected branch push. No repeat push,
CI upload, remote fixture deployment or farm operation was attempted. The public
candidate inventory is review evidence only, not an approved installation manifest.
