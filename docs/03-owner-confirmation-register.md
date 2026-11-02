# 03 — Owner Confirmation Register

Nothing in this list may be published as fact until an owner confirms it. Each item records who
confirmed it and when. The website must not invent testimonials, qualifications, prices,
policies, horse records, or emergency information.

| # | Item | Current value / source | Status | Confirmed by / date |
|---|---|---|---|---|
| 1 | Business name spelling | “Corner Stone Farm” (spec) | Confirm | |
| 2 | Public business phone | Beth Siemon — 509-557-8467; call only. SMS approval not supplied. | **Approved; saved in sandbox Farm Details; links verified in protected browser at all three widths** | Owner, current validation follow-up |
| 3 | Emergency phone(s) answered 24/7 during an emergency | — | **Needed** | |
| 4 | Text number for found-horse reports | — | **Needed** | |
| 5 | Public email address | Beth.Siemon@cornerstonefarmomak.com | **Approved; saved in sandbox Farm Details; links verified in protected browser at all three widths** | Owner, current validation follow-up |
| 6 | Public street address or “Omak, WA” only | — | **Needed** | |
| 7 | Scheduling request channel | **Acuity canceled.** Public CTA: **Contact Beth to Schedule** → `/contact/`. Show only approved call/message options; no public booking availability or enabled inquiry form. | **Workflow confirmed; code/content migration pending [Issue #3](https://github.com/kks787-ship-it/KksSandbox/issues/3)** | Owner, Oct 31 2026 |
| 8 | Lesson, training, camp, lease prices | — | **Needed** (or decide “inquire for pricing”) | |
| 9 | Beth Siemon credentials (USDF Bronze & Silver Medals, Pony Club B, FEI young horse experience) | Spec | Confirm exact wording | |
| 10 | Kris Siemon bio and role description | Spec | Confirm wording | |
| 11 | Other team members | — | **Needed** | |
| 12 | Maximum 5 full-training horses — publish publicly? | Spec | Confirm | |
| 13 | Sunday closed to clients — publish publicly? | Spec | Confirm | |
| 14 | Off-site lessons on Wednesdays; service area | Spec (area unknown) | **Needed** | |
| 15 | Camp and clinic dates, ages, capacity | — | **Needed** | |
| 16 | Policies: cancellation, weather, helmet, minors, payment | — | **Needed** | |
| 17 | Agreement/waiver templates (legally reviewed) | — | **Needed** | |
| 18 | Testimonials (real, with permission to publish) | — | **Needed** | |
| 19 | Logo source files (vector preferred) | Original-looking farm artwork located in legacy production uploads: `2025/03/Corner-Stone-Farm-01-1.png` (white transparent, 256×135) and `2025/03/Facebook-Cover-Image-1.png` (black-on-white, 1702×630). Owner-provided branded image is also in project context. **Unrelated 2024 “Tucker” logos are excluded.** | **Candidates located; confirm approved version and header/footer placement. Vector master still requested.** | Read-only archive audit, Nov 1 2026 |
| 20 | Authentic photographs and photo permissions (esp. minors) | Legacy production archive contains candidate historic equestrian photos; sandbox media library currently empty. **19 distinct image roles / 30 active placements.** Many candidate originals are only 206×206 social thumbnails. | **Owner must select images and verify actual farm context, rights, and minors’ permissions before import/publication.** | Read-only archive audit, Nov 1 2026 |
| 21 | Social media profile URLs | — | **Needed** | |
| 22 | Official emergency information source for the area (e.g. county emergency management) | — | **Needed** | |
| 23 | Horses for emergency profiles and the public fields approved for each | — | **Needed** | |
| 24 | Sale/lease horses and approval to publish prices | — | **Needed** | |
| 25 | Brand palette approval against original logo | Preliminary palette in spec | Confirm | |
| 26 | Business entity name for legal pages / privacy policy | — | **Needed** | |


## Public scheduling contact approval

The owner explicitly supplied Beth Siemon, **509-557-8467** and
**Beth.Siemon@cornerstonefarmomak.com** in this validation follow-up when asked which
channels could be published. This is a direct public-channel approval, independent of the
private inquiry recipient. Preserve the supplied spelling; use `tel:5095578467` for calling
and the supplied email for `mailto:`. No SMS, emergency/24-hour contact status, street address,
response-time commitment or inquiry-form enablement is inferred. The owner separately approved the settings/content step, and the values are now saved in
sandbox Farm Details with unrelated fields preserved. Call/email links were verified through
server-side rendering without sending a live message. Protected browser checks now confirm those links at all three widths; a separate tablet
header accessibility defect and unfinished branding/footer still prevent full site acceptance.


## Website completion inventory — November 1, 2026

The protected sandbox contains **23 unresolved Owner-to-Confirm notes** across 20 of its 21 generated program pages, **19 unique photo designs in 30 image placements**, and **zero uploaded Media Library attachments**. The legacy production archive was inspected read-only; the verified farm logo candidates above were not imported or published. See [canonical asset inventory](08-content-and-asset-inventory.md#actual-protected-sandbox-finishing-audit--november-1-2026), [photo recovery issue #5](https://github.com/kks787-ship-it/KksSandbox/issues/5) and [content decisions issue #6](https://github.com/kks787-ship-it/KksSandbox/issues/6).

No prior program fee, operating-hour proposal, client waiver, age minimum, return policy, family photograph, or permission is converted into approved public content merely because it was discussed in an earlier planning session. Confirm each current value before removing its associated note.


## Issue #12 — owner/master and individual staff access (all protected changes held)

The local Team & Permissions build supersedes equal broad Beth/Kris grants. The owner must
privately verify numeric master/operator/staff identities and actual strong login/MFA before
any separately authorized bootstrap or access. No existing Administrator ownership is inferred.
Review/edit preset defaults and each person's explicit grants/denials/resource scope, including
welfare hold versus release, clearance versus legal signatures, farm policy and team security.
Beth's Head Trainer and Kris's master walkthrough are examples, not assigned real permissions.

Approve the actual security provider and effective-session adapter separately; default scheduling
MFA status is denied, and the local fake adapter is never a deployment artifact. Review the new
candidate 19-file source pin, schema 3/15 tables, fresh backup/recovery and acceptance/rollback
proposal before Gate B. The historical 0.2.1 pin remains evidence, not permission for old grants.
Actual facility/pilot policies, protected-stack/device acceptance and later real use remain
separate decisions. See the current owner package and permission release report.
