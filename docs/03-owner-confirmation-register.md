# 03 — Owner Confirmation Register

Nothing in this list may be published as fact until an owner confirms it. Each item records who
confirmed it and when. The website must not invent testimonials, qualifications, prices,
policies, horse records, or emergency information.

| # | Item | Current value / source | Status | Confirmed by / date |
|---|---|---|---|---|
| 1 | Business name spelling | “Corner Stone Farm” (spec) | Confirm | |
| 2 | Public business phone | Beth Siemon — 509-557-8467; call only. SMS approval not supplied. | **Approved; saved in sandbox Farm Details; public render verified server-side** | Owner, current validation follow-up |
| 3 | Emergency phone(s) answered 24/7 during an emergency | — | **Needed** | |
| 4 | Text number for found-horse reports | — | **Needed** | |
| 5 | Public email address | Beth.Siemon@cornerstonefarmomak.com | **Approved; saved in sandbox Farm Details; public render verified server-side** | Owner, current validation follow-up |
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
| 19 | Logo source files (vector preferred) | — | **Needed** | |
| 20 | Authentic photographs and photo permissions (esp. minors) | — | **Needed** | |
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
server-side rendering without sending a live message. Protected browser acceptance remains
pending secure Basic Auth access; see the scheduling release report.
