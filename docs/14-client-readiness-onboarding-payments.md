# Digital Ranch — client-ready onboarding, digital agreements, billing and checkout

**Owner requirement recorded November 2026.**
**Status: PRODUCT BLUEPRINT ONLY.** Household intake, signed agreements, client payment/billing, client portal and client checkout are **not implemented or deployed**. Staff scheduling v0.3.1 and Farm Master permissions are locally coded/tested, but still not installed on the protected sandbox. No public self-booking or payment processor integration is authorized. Parent scheduling/security Gate B #11 and security development #14 remain separate.

**Related issues:**
- [#15 — Household/horse onboarding and versioned e-signatures](https://github.com/kks787-ship-it/KksSandbox/issues/15)
- [#16 — Billing/payments, pay-at-booking and boarding autopay](https://github.com/kks787-ship-it/KksSandbox/issues/16)
- [#17 — Controlled client scheduling + waiver/payment readiness](https://github.com/kks787-ship-it/KksSandbox/issues/17)
- [#11 — Protected staff-calendar pilot, Gate B held](https://github.com/kks787-ship-it/KksSandbox/issues/11)
- [#14 — Staff/admin MFA and Master bootstrap security development](https://github.com/kks787-ship-it/KksSandbox/issues/14)

## 1. Business outcomes and no-repeat-paperwork promise

**For riders/families:** One family account. Complete basic contact information once, add multiple adults/riders and guardian/emergency relationships, sign only applicable liability/instruction/camp/lease documents, accept optional media permissions separately, pay or redeem lesson credit, then arrive ready to ride. Returning clients update only changed fields or newly required documents. Never stop a lesson to chase a signature or payment.

**For a horse owner/boarder:** An approved intake link allows horse ownership/authorized agent, board/training services and care plan, vaccinations/health documents as actually required by farm policy, veterinary/emergency authorization, feed instructions, signed board/training agreements and approved first payment or autopay setup **before trailer arrival**. Arrival board shows signed/financial/care/welfare/routing gates. Signed contracts should not be promised or treated as executed until every required signer and countersigner completes the exact approved version.

**For Beth:** A short attendance screen says Ready / NOT CLEARED, with concise action items. She should not have to ask repeatedly for paperwork, chase clients for monthly fees, or troubleshoot payment webhooks.

**For Kris:** One account-level operations dashboard for contracts, invoices, outstanding approvals, signed cancellations, charge failures and activity logs; manual overrides only with documented reason and proper permission.

**Previous failure this prevents:** A family with several riders plus a horse in training/board formerly required multiple lengthy, overlapping intake forms and signature packets. Unsigned agreements could leave the barn without enforceable contract terms. The new workflow cannot mark a service-arrival state ready on the basis of an email invitation, verbal promise, appointment confirmation, or unsigned document.

## 2. Nonnegotiable source-of-truth rules

| Domain | Canonical truth | What must never imply it |
| --- | --- | --- |
| Appointment | Scheduling's transactionally confirmed reservation/resources | An online payment redirect, payment intent or mere request |
| Person/guardian/horse owner | Verified household/onboarding relationships, owner-authority evidence | Matching names/emails, a self-declared minor, or card payer |
| Executed contract | Signed exact immutable template version, all required authorized signers and audit evidence | A checked 'verified' flag, viewed draft, photo consent or paid invoice |
| Rider attendance clearance | Owner-approved legal evidence + rider/horse suitability/welfare + required age/guardian/emergency policies | Appointment confirmed or payment paid alone |
| Payment | Authoritative verified processor event/API and corresponding internal auditable ledger | Browser query parameters, payment link sent, pending ACH or checkout open |
| Care and emergency | Human horse-welfare duties and authorized emergency decisions | Invoice delinquency, missing digital form or an offline device |

There must be no competing client identity, appointment, financial balance or waiver-signature copy in multiple modules. Expose only stable references and authorized readiness status between modules. Signing a release does not substitute for a trainer's suitability decision or make a horse sound.

## 3. Complete family intake UX

1. New adult contact starts an authorized invitation/registration and explicitly consents to electronic records. No public signup/inquiry switch is activated until separately approved.
2. Collect minimal family basics once: adult(s), contact, authorized guardians/payers, emergency contacts; add 1+ riders, optional 1+ horses and approved ownership/agency relationship. Support separated/divorced household guardian signers and payer not identical to guardian.
3. Choose requested service(s) and riders. Server builds a requirements matrix by service, rider age, horse ownership, signer roles and agreement version. Show a **short checklist** with progress and estimated steps, support safe Save & Resume on a phone.
4. Present full legally approved agreement versions in readable order. Do not bundle optional marketing photo consent with mandatory risk release. Where legal/owner-reviewed, allow one adult to execute a multi-rider document; where rider-specific or distinct guardian/horse-owner signatures are required, collect them individually with verified signer identity.
5. Client reviews per-document fee/contract/cancellation terms, selects authorized method of paying, and completes hosted processor checkout, or an approved prepaid lesson/program credit. Payment and agreement outcomes are independent and reconciliation may be asynchronous.
6. Portal resolves Ready / Action needed (e.g. Parent must sign / Owner authorization missing / Payment pending / Horse vaccine record pending). Clients see only their own records, receipts, upcoming appointments and executed PDFs. Staff dashboard sees readiness without private medical/payment credentials.
7. Returning family resumes the same household/riders/horses, with no retyping and only incremental signatures/consents when policies change, agreements expire or a new service needs new documents.

Do not force clients through a two-hour paper-equivalent online application. Use service-specific progressive disclosure and collect only data necessary for a particular program. The planned short first-time lesson intake target is a UX objective, not a guaranteed completion time.

## 4. Boarder and training horse pre-arrival flow

1. Inquiry/intake requested → Kris/Beth accepts the horse/service/arrival date. Never auto-accept all horses; boutique training max 5 horses.
2. Capture horse name/identification, owner and any authorized agent, relevant care/feed/emergency-veterinary instructions; upload **only** vetted facility-required documents into restricted storage, never public media. Support 1+ horses and separate horse/owner signatures.
3. Generate and execute board/care agreement; add training agreement or signed service addendum when applicable. Horse-owner authorization, rates and service start/termination notice are explicit and versioned.
4. Show initial board/training invoice, permitted deposit/first-period payment, optional consented recurring payment. A saved payment method does not imply authorization for arbitrary future charges.
5. Staff arrival checklist: ownership authorized, legal agreements executed, appropriate health/care prerequisites verified, first agreed invoice/deposit fulfilled (or an explicit owner-approved payment exception), stall/paddock prepared, welfare resource available. Mark **Ready for Arrival** only when all approved gates pass.
6. If a horse arrives unexpectedly/in emergency or an animal requires immediate care, log the exception and provide necessary care; legal/finance staff resolve paperwork and payment later. Never lock out a horse from essential welfare solely over unpaid invoices.

Include a time-stamped 30-day (or otherwise approved) notice workflow: online termination/notice request, date received, contract-defined notice length, final invoice/proration, pending care and signed acknowledgment. The notice period is **an owner/legal decision, not inferred from legacy PDFs**. Preserve complete executed prior versions and notices even after moving out.

## 5. Agreement and signature catalog (legal review mandatory)

| Service / person | Candidate required records | Signing authority |
| --- | --- | --- |
| Adult lesson/riding/haul-in | Equestrian assumption-of-risk and liability release, instruction terms, safety/helmet policies, optional equipment/participant consent | Adult participant and other legally required signers |
| Minor rider | Minor-specific guardian permission plus service/rider release and emergency-contact information | Legally authorized parent/guardian; avoid assuming parent may waive minor's claims |
| Student in working-student / mentorship | Participant/volunteer or employment-related agreement as applicable, risk/safety terms and guardian consent where applicable | Legally appropriate signer(s); separate labor-law review |
| Boarding | Board/full care and facility rules, emergency vet/care authorization, service rates and termination/notice rules, horse ownership/agency evidence | Owner(s), authorized agent(s) and farm as applicable |
| Boarding + training | Boarding plus training addendum/service plan, trainer authority and horse-specific intake; charges/changes and sale-prep when applicable | Horse owner/authorized contracting party, farm and other required signers |
| Camp/clinic/Academy | Family/program enrollment, waiver/guardian consents, program/refund/smoke safety policies | Each authorized signer |
| Leases and sales | Lease/half-lease terms, seller/consignor written authority, commission/fees, bill of sale and disclosures | True horse owner/authorized signers, counterparties |
| Marketing/media | Separate optional photo/video use, identifiable family/rider coverage, withdrawal and retention handling | Appropriate adult/guardian; not bundled with mandatory release |

Historical CSF rider and training/boarding forms are **inputs to counsel review**, not approved templates. Do not hard-code stale rates, cancellation/notice terms or a generic waiver for every service. For Washington, review the Uniform Electronic Transactions Act **RCW Chapter 1.80** (electronic-record consent and signatures) and equine activity definitions and exceptions **RCW 4.24.530/.540**. Electronic form alone does not prove a clause valid or release enforceable, particularly with minors.

Required evidence model: complete immutable template and plain language disclosure, version and content hash; all required signer IDs and authority; affirmative electronic-consent record, displayed document and each assent/signature event; date/time, signed PDF hash and nonpublic retrieval, revocation/expiry, audit/correction log, retention/legal hold. The signing provider/engine must be evaluated and approved separately, including final signed artifact not just webhook success.

## 6. Billing and payment design

**Recommended UX:** payment at the time a booking is approved/confirmed, without collecting before an uncertain lesson reservation. Offer a card/payment method for authorized future charges and consented subscriptions. A bank transfer or ACH can be pending; do not show Paid until settled by provider evidence.

**Services:** private/group lessons, lesson packages/credits, Horse Academy tuition, camps/clinics, annual/quarterly beginner programs, boarding, training, sales prep/consignment, lease, haul-in, travel, approved care extras. The known Nov 2026 private lesson rate is **$60/hr**; all prices/rates may be changed by owner and are not silently fixed by this document.

**Boarding:** charges recurring by approved contract/plan and cycle, with first invoice, deposits, prorations, extras, credits, late/notice policies only when approved. Client portal has invoice history and current balance; optional authorized auto-pay and secure provider hosted card/ACH setup. Record cash/check/manual settlements with audit/reconciliation; do not automatically penalize a horse or deny care for unpaid charges.

**Lessons:** configurable per-service charge policy (pay in full, approved deposit, or consume validated prepaid credit). Distinguish pending request / reserved hold / appointment confirmed / payment authorized / settled / refunded / canceled / disputed. No double spend on package credits. For staff-confirmed appointments, a secure payment/completion link can finalize an approved hold; instant self-booking is a later **separately approved** mode only for published safe availability.

**Cancellation:** Owner-requested 48-hour cancellation policy to be embedded in versioned lesson terms after approval. Fire-season smoke, evacuation, horse illness/welfare and weather rescheduling create authorized exception/credit workflows. No automatic no-show fee, refund, forfeiture or makeup charge unless signed terms and explicit configured policy support it; preserve actual notice time/actor.

**Processor:** None selected for Digital Ranch. Compare existing prior PayPal camp workflow to Stripe Checkout/Billing and Square. Favor hosted checkout and a single provider gateway behind a portable internal ledger. Do not store PAN/CVV/payment details on CSF WordPress. Require separate merchant identity for nonprofit activity or separate accounting/processor setup consistent with actual entities.

**Safety:** processor webhooks need signature verification, idempotent event IDs, exact payer/invoice/appointment/amount/currency reconciliation, event replay and out-of-order guards, delayed bank settlements, disputes/chargebacks, refunds and merchant account routing. A returned success URL does not prove paid. **Do not bypass protected site's Basic Auth or publish a webhook endpoint without a separately reviewed public endpoint/security plan.** Payment endpoints/keys/autobill/email remain off until owner approval.

## 7. Booking and readiness modes

### Phase A: Existing staff-confirmed model (safe initial path)

- Customer sends request through currently authorized channel (Contact Beth while public booking remains disabled).
- Beth/Kris reviews rider and horse suitability and available resources; creates held/confirmed appointment through the existing scheduling engine.
- Private onboarding invitation and signed-document workflow resolves eligibility. Approved payment link / credit redemption takes place on a verified held/approved reservation.
- Staff sees Ready when legal/financial/suitability checks permit. If not complete, appointment may exist but PRE-RIDE BLOCK remains prominent; do not suggest automatic clearance.

### Phase B: Client request and online checkout (separately approved)

- Client selects a service and requests limited offered windows; no resource slot confirmation until staff acceptance.
- Staff approves; system creates an expiring hold and sends secure checkout/action link. Pay/sign then confirm. Unpaid/expired requests are visible to Kris, not silently booked.
- Client can change/cancel according to signed policies; refunds/credits use Finance's audited state machine.

### Phase C: Narrow instant self-booking (only if Beth/Kris opt in)

- Show only approved service/person/horse resources and protected openings, constrained by Sunday closed, Wednesday off-site/travel grouping, five training horses, arena conflicts, school-horse suitability/rest and trainer burnout limits.
- Server uses existing transactional scheduling collision engine. No second calendar or public list of client names/horse welfare notes.
- Ready requires all gates; booking, payment and release are separate verified events. A client cannot pay their way past an unsafe horse or unsigned waiver.

For all modes, no Acuity integration, and **public "Contact Beth to Schedule" CTA stays until owner signs off on the new flow**.

## 8. Platform and permissions

Proposed independently deployable components:
- **csf-onboarding**: households, adult/guardian identities, rider/horse ownership, required agreements, signer authority and legally executed documents.
- **csf-billing**: service catalog, price/contract-versioned invoice items, account ledger, credits, payments, provider adapter and reconciliation.
- **csf-portal**: minimum authorized client/family view into appointments, contracts, balances, receipts, readiness; client authentication, guardian and privacy model.
- **csf-scheduling** (existing local source): sole resource and appointment reservation truth, with existing conflict/availability and pre-ride gates; new adapter reads aggregate readiness.
- **csf-mfa** (separate staff/admin module): staff/operator session security; **does not automatically grant access to client-family accounts**. Design appropriate client identity/guardian authentication separately.

New Digital Ranch privileges are independent from WordPress Administrator and existing scheduling permissions, e.g. onboarding-review, contract-template-approve, signature-evidence-verify, finance-invoice, finance-refund, payment-settlement, household-read-scoped. Beth may read readiness, not raw banking or full minor records; Kris has explicitly approved owner/finance oversight with dual review for high-risk financial/legal corrections. The CSF Riding Program nonprofit must be accounted for separately from commercial barn obligations.

### Client readiness aggregator (read-only computation)

- **Ready to Ride** = required current executed waiver/consents and guardian relationship resolved + payer's approved lesson charge/credit condition satisfied + owner-reviewed rider/service suitability and horse welfare/availability at lesson time.
- **Ready for Horse Arrival** = boarding/training service approved + ownership/care/health information complete + every required service agreement executed + owner-approved initial finance requirement satisfied or formally waived + actual paddock/stall/resources ready.
- **Blocked** displays a reason and authorized next action; do not show a false green success merely because a calendar reservation or payment exists.
- Delayed/reversed payment or an expired/withdrawn agreement re-evaluates applicable readiness and flags staff; preserve history, don't silently cancel a confirmed appointment or remove a horse from care.

Use transactional idempotency, private auditable revisions, direct authorization checks, secure PDFs outside public document root, limited health/minor records, GDPR/Washington privacy/legal review, backups/restore and replay-safe provider/webhook mechanics. All cross-module read models should be privacy-scoped and non-public. Include data export and supported manual fallback if online signing/payment unavailable, with actual verification before participation.

## 9. Acceptance examples

**Family lesson:** One parent + 3 riding children → household form once → correct guardian-specific signatures + release per legal requirement → optional separate photo choices → owner-approved 1-hour private booking/payment or credit → readiness for each rider. The parent can complete it without a printed form. A missing child's signature blocks only that child's attendance; does not remove other riders' legal identities.

**Boarding + training:** One parent/contracting owner with 1 horse, 2 riders → board + training package, horse intake/vet authorizations, waiver for each rider, required signatures, deposit/initial invoice, monthly autopay consent → authorized Ready for Arrival. The notice/change-in-contract workflow is signed and enforceable only after legal review; future month price changes don't retroactively rewrite history.

**Existing customer:** Book another lesson → no duplicate intake, only new program terms/expired waiver and payment/credit step. Email or QR code is not a bypass.

**Failures:** unsigned release, wrong guardian/agent, duplicate click, card declines, webhook replay, ACH pending/reversed, chargeback, late cancellation/fire closure, horse welfare hold, two clients choosing same horse/instructor, revised agreement after calendar confirm, server offline/partial restore. Never double charge, overbook, mark unsigned riders as cleared or erase historical audit.

**Human acceptance:** Beth tests lesson-day check-in; Kris tests a full family/horse plus charge/notice workflow. Actual time taken, steps reused, help required, privacy and failed payments are recorded.

## 10. Sequencing and approval gates

1. **Current separate security prerequisite:** Finish admin MFA/Master bootstrap and the controlled staff-calendar protected pilot on Issue #14/#11, with explicit owner authorization.
2. **Work in parallel without deployment:** UX/canonical schema/signature evidence design and legal document review for #15; provider/ledger/checkout architecture and business policy choices for #16; controlled client booking UX for #17.
3. Build and locally test household model, agreement template/signature system and client portal auth. Verify signer authority and owner/counsel-reviewed templates before protected signing.
4. Build and locally test billing ledger/provider hosted checkout, payment idempotency and reconciliation; choose/authorize merchant account and processor only after owner review.
5. Integrate a read-only readiness adapter into scheduling and a private family portal; ship first as **staff-approved booking and paid intake**, not unrestricted public booking.
6. Conduct separately approved protected synthetic pilot with fictional households, rider/horse documents and **test-mode** payment provider, no real cards/funds/client data.
7. Owner/attorney/privacy/financial review and human Beth/Kris acceptance; only then consider actual signed documents, real money, public booking and production.

**No GitHub issue/document, code test or planning conversation is deployment permission.** No existing protected client identity, contracts, payment provider, real invoices, MFA, scheduling plugin or production data should be changed until its separate approval.

## 11. Gift certificates: November 8 sandbox-only development increment

Kris has requested a public gift-certificate page, PayPal checkout, required acceptance of
Beth's terms, and private unique certificate numbers and purchased/redeemed ride accounting.
This selects PayPal for this gift-certificate development scope only. It does not authorize
production deployment, live charges, other billing products, new grants, or public booking.
The separate `csf-billing` plugin is under development on `codex/gift-certificates`; it does
not modify scheduling. See `docs/test-results/gift-certificates.md` for exact completed work
and the substantial unfinished integration/qualification steps. The shortcode currently
renders a closed state; it is not an operational checkout or management feature.

Prices remain provisional: two one-hour lessons $110; four one-hour lessons $210; twelve
monthly two-hour sessions $795 upfront or four quarterly payments of $225. None are encoded
as approved products. Confirm package definitions and currency as well as prices. Quarterly
billing, credit vesting, installments, subscriptions and debt collection are not implemented.

Historical gift-certificate draft terms included a named package, non-refundable and
non-transferable wording, waiver/guardian signatures before riding, appointment/availability,
safety rescheduling and barn rules. These are historical draft evidence, not published policy.
Expiration was blank. Annual commitment/payment structure, notice/makeup policy and emergency
exceptions need a final owner-approved text; some prior elaborations were assistant drafted.
Do not infer that shorter packages have the annual makeup rules. A twelve-month program
commitment must not silently become an expiration of purchased credit.

Legal review is required before approved terms are supplied. Washington RCW 19.240.020 generally
prohibits expiration/fees except specified exceptions and includes a cash-on-demand rule for
certain remaining balances below $5. Thus absolute “no cash” and automatic forfeiture/expiry
must not be copied into checkout. Applicability to the final service package and interaction
with federal rules require review; this document is not a legal determination.
Sources: https://apps.leg.wa.gov/rcw/default.aspx?cite=19.240.020,
https://apps.leg.wa.gov/rcw/default.aspx?cite=19.240.030,
https://www.consumerfinance.gov/rules-policy/regulations/1005/20/.

The protected sandbox retains HTTP Basic Auth. Do not open a webhook exception, expose its
private records, or substitute a mock webhook for real PayPal verification. Real provider
callbacks need a separately reviewed public test ingress/relay or isolated test target. The
current module registers no payment or webhook HTTP routes. Production hosting of any public
financial endpoint is a separate security/recovery/deployment decision.

### November 8 sandbox-only gift implementation update

The gift scope now has a local 0.2.0-dev/schema-2 candidate: public sandbox checkout/session
controls, private management and audited corrections, provider uncertainty recovery and
conservative refund/dispute holds. Real disposable database, browser, official MFA and
functional restore tests have run; actual PayPal transport remains mocked. This supersedes
foundation-only implementation status, not the broader onboarding blueprint or its holds.
See [current evidence and remaining gates](test-results/gift-certificates.md). No gift module
has been published or deployed; catalog, exact terms and owner decisions remain provisional.

### PayPal sandbox credential and merchant-verification handoff (prepared, not executed)

No account ownership or merchant readiness is inferred from historical PayPal usage. Kris or
the authorized merchant controller should use PayPal Developer Dashboard **Apps & Credentials**
in **Sandbox**, select/create the gift-testing REST app and its Business sandbox seller, and
use a separate Personal sandbox buyer. Never provide the live app secret or PayPal password
in chat, Git, an issue, screenshots or test logs. [Official setup](https://developer.paypal.com/api/get-started/),
[sandbox account roles](https://developer.paypal.com/sandbox-testing/accounts).

Before any credential handoff, identify the approved isolated test host, responsible operator,
merchant controller and app; confirm that no real catalog or live adapter is enabled. The
server needs the sandbox client ID/secret and expected seller merchant ID; the seller ID is
an independent reviewed binding, not a value accepted from a browser payment response. An
OAuth success authenticates the app but does not alone establish farm ownership or the intended
payee. Verify the seller account-to-app relationship in the owner's dashboard, then verify the
expected payee merchant ID in server-fetched sandbox order/capture results. Retain only a
redacted pass/fail/account-binding review record, never tokens or complete payment payloads.

Use a scoped secret handoff: Gateway's existing secure stored-value link can receive a named
sandbox credential without exposing its content to chat, then an authorized exact-target job
can reference that stored value. Alternatively, the owner can provision the approved isolated
runner's secret manager directly. Neither method has been invoked or provisioned by this work;
no protected CI secrets or farm config were changed. Provision a separate random billing data
key with private backup custody; credentials and encrypted certificate key are different assets.
Keep key material outside the document root and repository, with restricted ownership/mode.
Do not replace the data key after certificates exist.

A separate approved **HTTPS port443** test listener is required for genuine signed webhooks;
register it on the same sandbox app and securely record its webhook ID. Do not remove protected
sandbox Basic Auth or forward owner traffic into the local fixture. Verify completed, pending,
denied, refunded, reversed and dispute events and their replay/reordering with that app.
PayPal's simulator does not support the postback signature-verification method used here, so
simulator events cannot qualify this adapter. [Official webhook requirements](https://developer.paypal.com/api/rest/webhooks/rest/).

Owner action needed first: confirm the merchant controller and approved isolated test target,
then use the secure handoff after its exact scope is prepared. Credential availability does
not approve public sales, final terms, deployment, real delivery or production transactions.


### Verified sandbox connection procedure and remaining commercial blocker

Official PayPal documentation rechecked November 8, 2026: use Developer Dashboard Apps &
Credentials in Sandbox, bind the REST app to its Business sandbox seller, and use a separate
Personal sandbox buyer. OAuth exchanges the sandbox app credentials at
`https://api-m.sandbox.paypal.com/v1/oauth2/token`; OAuth success alone does not prove the
intended merchant binding. [Setup](https://developer.paypal.com/api/get-started/),
[authentication](https://developer.paypal.com/api/rest/authentication).

No PayPal connector or merchant credentials were found/used in this task. A Gateway secure
stored-value writer is available, but no handoff or write has been invoked. Owner action is to
identify the controller and authorize an isolated non-farm HTTPS test target and its secret
store. Then an operator can arrange the scoped secret-manager handoff without receiving values
in chat. Do not add protected CI secrets, remove farm Basic Auth or register a public listener
under the existing publication-only approval. Genuine app-associated sandbox events are needed:
the current postback verification adapter cannot use simulator events as signature evidence.
Register the separately approved HTTPS443 endpoint on that app, retain the webhook ID securely,
and qualify merchant/amount/currency bindings, event retries/reordering, uncertainty and freezes.
[Official webhook guide](https://developer.paypal.com/api/rest/webhooks/rest/).

Washington RCW19.240.020 generally prohibits paid gift-certificate expiration and fees (subject
to narrow statutory exceptions). Following a partial purchase, remaining value must be available
as cash or certificate credit at the issuer's option; a remaining balance below $5 is redeemable
in cash on demand. No blanket no-cash, expiration or forfeiture term is approved here.
[Statutory text](https://app.leg.wa.gov/RCW/default.aspx?cite=19.240.020).

**Implementation gap:** schema2 tracks rides and original order amount, but not an authoritative
remaining monetary-value ledger or cash-redemption workflow. Sales must remain disabled. A
reviewed next schema must preserve original paid value/currency, separately journal ride/value
consumption, corrections and cash redemption atomically with idempotency, audit, permissions
and MFA, and handle uncertain payouts without duplicate payment or erasing credit. Proposed
cash-request/review/recorded-payment states are design only, not payment authorization. Define
reviewed package-value allocation/rounding and evidence of actual payout before implementation;
never infer remaining value from current catalog prices. Expand recovery schema/ledger tests
alongside that work. Annual scheduling commitments do not themselves expire paid certificate
value; no-show forfeiture, nontransferability and withdrawal terms require tailored legal review.


### Monetary workflow implementation follow-up (provisional)

The 0.3.0-dev/schema3 candidate now implements paid-value accounting alongside rides and an
MFA-protected full-balance cash reservation/uncertainty/attested-payment recording workflow.
It never performs payouts/refunds. See gift-certificates test results for exact proportional
rounding proposals and limitations. Valuation defaults unapproved; no final commercial terms
or interpretation of no-show/annual withdrawal rights is encoded. Owner/legal approval of
allocation and operating procedure, real sandbox verification and all deployment/recovery gates
remain mandatory. Existing schema2 data requires a reviewed reconciliation migration; none runs
on activation. The earlier ride-only gap above describes the previous candidate, not this code.

### Printable fulfillment increment and new draft requirements (November 9)

Owner requests buyer-selected themed numbered certificates bearing authorized Beth signature
artwork, buyer certificate/receipt/terms/scheduling email, administration sale notification,
and certificate-number payment in customer scheduling. The two newly supplied in-house PDFs
are drafts, not blanket policy approval. Booking must reserve a lesson, not consume it.

Implemented locally: three compiled versioned color themes, checkout design selection and buyer
email, immutable presentation/instruction snapshots, authenticated encryption of buyer contact
and signature artwork, and two distinct held delivery intents committed with verified issuance.
The authorized receipt renders a sandbox-marked printable HTML certificate in a script-disabled
frame. Browser print/save-as-PDF works; no production PDF artifact service exists yet. Missing
authorized artwork is labeled pending, never replaced by a generated or typed signature.
Private `CSF_BILLING_SIGNATURE_JSON` accepts authorized PNG bytes, SHA256 and an authorization
reference; no upload endpoint or remote asset fetch exists. `CSF_BILLING_SCHEDULING_INSTRUCTIONS`
is operator-supplied plain text, frozen per purchase, empty by default. Both need review before
configuration. Old purchases without presentation snapshots are not silently reconstructed.

A local-only, explicit synthetic sink bundles buyer certificate/receipt/accepted terms and
instructions, and a distinct minimal administration receipt. It rejects non-.invalid buyer
addresses, requires billing management/MFA authorization, encrypts payloads and deduplicates
records. It cannot send email and never returns delivered=true. Production delivery intents
remain held. Recipient configuration, contact verification/correction, renderer qualification,
durable dispatch claims, outcome reconciliation and actual provider callbacks remain to build.
Do not treat sink tests or an SMTP accepted response as confirmed customer delivery.

**Booking implementation boundary:** existing billing and scheduling stores each open their own
transaction; invoking them sequentially cannot provide all-or-nothing booking. Do not call the
completed-ride `redeem` command during booking. The next reviewed schema/domain increment needs
separate available/reserved/used ride and monetary balances; a reservation bound to certificate,
booking UUID/revision, purchase valuation and idempotent command; scheduling-lock then billing-lock
order on the same DB connection; one outer transaction for availability check, booking, reservation,
command receipts and audits; and no network call within it. Failed/stale bookings must roll back
every participant. Reservation must block cash/other bookings from spending its value. Completion
moves reserved to used exactly once; release/cancellation/no-show/edit/expiry rules stay held
until approved. Public number entry needs independent session/CSRF/rate limits, generic responses,
no codes in URLs/logs/browser storage, service eligibility and identity/waiver policy. Current
private scheduling authorization is not permission to open public booking or bypass MFA.

Reprinting must refer to the same certificate/balance. Compromise replacement needs audited,
atomic number rotation, invalidation of old bearer codes, preserved balance/reservations and
delivery linkage; no second issuance/payment. This rotation is not implemented in this increment.
Final prices/terms, valuation, annual versus gift-value rules, cancellation/no-show/release policy,
buyer eligibility/waivers, signature artwork authorization, scheduling wording, administration
recipients/from address, mail provider and PayPal sandbox qualification remain owner/operator
inputs. Do not infer forfeiture, expiration, refund deadlines or installment authorization from
the PDFs. Existing legal, sales, real-email, grants, MFA and deployment holds are unchanged.

Latest editorial-source correction: In-house terms PDF `libfile_861a7ee2bb608191a746479c8d73777f`
supersedes the previous terms draft, but is not blanket owner approval. Checkout/email PDF
`libfile_2ce39a9d5b2081918a16b66d10afa6bc` is stale. Do not copy either into approved checkout
configuration. Two annual late-cancellation makeups are proposed; timely/farm cancellations
should not consume that allowance, and courtesy stacking is unresolved. Cash-on-demand wording,
14-versus-30-day refund timing and new promises remain disputed; quarterly appendix stays held.
Delivery outcomes must distinguish pending, failure and uncertainty; payment timeouts cannot
assert no charge before reconciliation. Never place the certificate bearer number in email
subjects. Partial refunds require reconciled remaining paid credit, not automatic closure or
zeroing. Current provider refund handling freezes for review; it does not implement partial
refund allocation or close remaining credit. These are constraints for the next increment,
not newly approved business rules. Sales and real delivery remain disabled.

### Continued local fulfillment and reservation candidate

The next candidate adds a compiled one-page PDF renderer and encrypted persisted artifacts
inside the billing ledger. Browser download is an authorized no-store POST with digest checks;
no public uploads or bearer URLs are created. Reprints reuse the same artifact for that number
generation. The renderer accepts bounded ASCII template text; unsupported text/overflow stays
pending rather than silently changing it. Authorized PNG signatures require GD for PDF raster
embedding; missing GD fails closed. Missing artwork is explicitly unsigned/pending and every
artifact remains SANDBOX TEST, not valid for rides. Full exact UTF8 terms stay in the delivery
bundle rather than being transliterated into the PDF. Production typography/accessibility and
non-ASCII font support remain qualification work.

A separate durable dispatch worker implements claim, synthetic acceptance, reconciliation,
uncertain and superseded states. It is explicitly local-only and has no real mail adapter.
Buyer bundles reference the persisted PDF and exact terms/receipt/instructions; administration
gets a minimal sale record. Subjects omit bearer numbers. Stale/frozen number generations cannot
be newly accepted. An uncertain attempt with no authoritative sink receipt is not retried.
No delivered=true outcome exists. Actual provider delivery and uncertain-outcome reconciliation
remain unqualified; do not substitute SMTP acceptance for receipt by a buyer.

Private compromised-number rotation requires management/MFA, expected revision and explicit
attestation. It retains the same certificate, paid balance, ride history and reservations,
preserves a retired-number tombstone, audits old/new digests and invalidates stale dispatch.
No actual refund, second sale, replenished credit or automatic external resend occurs.

A default-off private booking participant now joins the scheduler's transaction after its
facility lock and takes the billing lock on the same connection. Failed scheduler changes roll
back reservations too. Explicit reviewed/versioned product-to-service and release-initiator
policy is required; none is supplied by default. The first implementation supports one rider/ride
per confirmed appointment, with exact package duration. It reserves original monetary/ride
ordinals, excludes them from completed-ride/cash spending, completes them once after the lesson
ends, and supports audited correction back to reservation. Cancellation releases only under
the reservation's approved policy snapshot. No no-show forfeiture, timer-based expiration,
quarterly, courtesy stacking or late-cancellation rule is inferred. Uncoordinated edits of
bound appointments fail closed; the detail marker also prevents edits if billing is absent.
Completion is recorded as billing_credit_state=used; existing scheduler appointment state stays
confirmed (the scheduler has no separate completed state).

Private POST commands and existing permissions/MFA are retained. Customer-facing certificate
entry/identity/waiver authorization and multi-rider/family credit allocation are not enabled or
qualified. The private participant must not be described as a completed public booking feature.
A browser booking workflow and full scheduler-path integration/race coverage remain required;
the added SQL probe isolates the transaction participant using a synthetic outer transaction.
