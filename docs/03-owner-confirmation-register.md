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

## Client intake, legal agreements, scheduling checkout and payments — product approval vs. execution

**Owner's November 2026 requirement is approved as a PRODUCT GOAL, not as legal terms, payment terms or deployment permission.** A family must be able to complete correct intake and releases before the first lesson, ideally paying when booking. A boarding/training owner must complete horse intake, executed agreements and initial financial arrangements before horse arrival. One household needs multiple riders, horse owners and payers. Beth should not collect signatures/payments at lesson time; Kris should manage exceptions. [Single combined specification](14-client-readiness-onboarding-payments.md), [onboarding #15](https://github.com/kks787-ship-it/KksSandbox/issues/15), [billing #16](https://github.com/kks787-ship-it/KksSandbox/issues/16) and [client booking #17](https://github.com/kks787-ship-it/KksSandbox/issues/17).

| Owner decision still required | Current position (do not publish/execute without approval) |
| --- | --- |
| Executable legal waiver/hold-harmless, adult vs. minor guardian/signer authority | Washington equine counsel review required; prior PDFs are only candidates. Separate optional photo/video permission. |
| Exact agreement set and signature levels by service | Parent/guardian, horse-owner and payer may differ; avoid duplicate household intake, but separate necessary signers. |
| Board/training contract notice and first-payment/deposit terms | Amounts, rates, grace periods, termination notice, prorations and default consequences unconfirmed; old historical agreements may be stale. |
| Lesson/camp cancellation and makeup policy | 48-hour cancellation is an owner-requested policy direction; fee/refund/credit terms, weather/fire/smoke/horse welfare exceptions still need a finalized signed policy. |
| Current service catalog and prices | Private lesson known as $60/hour in Nov 2026 planning, but every new online price, package/camp/Academy/billing rate requires confirmation. |
| Lesson booking payment logic | Owner wants payment at scheduling; approve whether deposit/full prepay, staff-approved hold then checkout, card on file, package redemption or later instant booking for each service. No public self-booking approval yet. |
| Payment provider / merchant account | Unselected for Digital Ranch. Compare Stripe, Square and previous camp PayPal usage against hosted checkout, recurring board auto-pay and fees; separate nonprofit accounting and merchant authority. |
| Autopay and stored payment-method consent | Optional explicit written client authorization, payment timing, variable fee/extras/proration handling, retries and opt-out; no card numbers on farm server. |
| Client portal identity, adult/guardian invitations, consent and privacy | Authorize account/email delivery setup, restricted PDF storage, optional secure verification, document retention, access and legal hold. Public inquiry remains disabled. |
| Staff/readiness exception powers | Define which verified staff may approve a safe emergency horse arrival, payment exception, duplicate signer correction or document review. Don't bypass legal signature or horse welfare by a generic override. |
| Tax, insurance, legal entity and nonprofit boundaries | Review merchant entity, CSF Riding Program scholarship/nonprofit separation, invoicing/tax treatment, privacy compliance and insurance requirements. |
| Public booking/payment launch timing | Remains **NOT AUTHORIZED**; Acuity canceled and Contact Beth to Schedule remains unchanged. Gate B and admin MFA continue independently. |

**This register does not start collection of cards, signer data, new WordPress accounts, real invoices or executed releases.** Every actual provider, template, portal, signature and payment launch requires its own protected deployment, legal/financial and owner approval.

## Corrected account layers and owner decisions — November 2, 2026

Source: [owner clarification on Issue11](https://github.com/kks787-ship-it/KksSandbox/issues/11#issuecomment-6584204812).
The reported Plesk screenshot concerns the **WordPress Administrator created at installation**;
the owner's separate protected-site login is **HTTP Basic Authentication**, not that WordPress
administrator. Neither literal login, private recovery email nor screenshot is reproduced here.
The screenshot does not verify the human controller, numeric WordPress ID, factor enrollment or
an ownership approval. Plesk control-panel access is also separate from native WordPress roles.

**Superseded:** recent planning statements identifying the sole WordPress Administrator as
Kris's business login, treating that identity mapping as resolved, or preferring reuse of that
same UID for Farm Master. Those statements remain in historical commits; they are not current
instructions. Do not create or link an account from a Basic Auth name or inferred email match.

**Recommended for owner review:** retain the existing Plesk-created WordPress technical
Administrator unchanged for maintenance; separately approve an individually controlled native
Subscriber account for Kris's Digital Ranch business role and an independent Beth staff account.
Kris becomes protected Farm Master only after the existing separately approved bootstrap. This
is intentional maintenance/business separation, not permission to create duplicate or throwaway
accounts. No extra administrator or Beth promotion is proposed. Human controllers and exact
private account mappings are still unverified. Existing accounts may be used only after their
actual identities/purposes and approved reuse are privately established.

| Decision | Current position / owner choice still required |
| --- | --- |
| Existing technical WordPress administrator | Preserve native role and Plesk relationship; privately verify controller, UID, maintenance authority and recovery. No farm membership inferred |
| Kris's business identity | Approve/revise separate Subscriber business account and private login/recovery details; no values have been chosen, published or provisioned |
| Beth's identity and scope | Independently verified Subscriber plus reviewed Head Trainer/individual choices, no native WordPress admin |
| Human independence | Different UIDs and MFA sessions establish account separation, not two people. Decide whether operator and owner must be independently controlled by different people and privately verify that requirement |
| Current bootstrap | Keep distinct operator/owner IDs, own fresh MFA, finite exact-action invitation, atomic/idempotent audit. Same-UID redesign is not recommended for this pilot and would require new security review/tests/source pin |
| Accounts & Access UI | Approve/revise the proposed inventory, separate native/farm permissions, enrollment versus own-session proof, recovery-readiness status and restricted technical audience; **not implemented** |
| Plesk management / SSO | Dependency to protect, precise mechanism unverified. Test maintenance/login/MFA/recovery only in a separately approved recoverable window; do not weaken MFA to preserve a password-only path |
| MFA, custody and notifications | Separately approve exact provider, individual enrollment, recovery custody, privileged enforcement and security notifications; HTTP protection is not the second factor |
| Actual execution | All account, backup/restore, provider/security, source/schema, ownership, employee grants and pilot changes remain held independently |

The existing Scheduling0.4.0 validator's distinct-UID constraint fits the recommended separate
technical/business account model. It does **not** authorize the existing admin as operator by
inference, prove two independent humans or grant any actual farm ownership. No source change
is required by the recommended model. See [canonical account/UI/security design](01-architecture.md#accounts--access--corrected-identity-model-november-2-2026)
and [single Gate B worksheet](pilot/gate-b-owner-review.md).

**Options digest context retained:** the prior163-row options digest difference remains real;
reported backend/Plesk activity is a plausible ordinary explanation but the exact key/writer is
unknown. No incident is established, no broad forensic investigation or database repair is
implied, and no older options table should be restored to force a match. Check critical settings
and obtain a fresh consistent change-window baseline before any approved write. This correction
performs no server reinspection or change and does not resolve the historical drift's attribution.
