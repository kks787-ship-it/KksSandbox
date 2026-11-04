# Scope F — minimum provider decision for the calendar

## Owner decision recorded

**F2 design is approved. Do not ask for that design choice again.** Preserve TOTP, single-use
backup codes, the existing global provider restriction and native reset/security-mail protections.
This is not authorization to install or activate MFA, enroll real factors, reset credentials,
send messages, grant permissions or activate scheduling. Private controller/recovery verification
and security-mail recipient/sender/transport verification must precede the scoped A/B/C decision.
D enforcement and E farm grants remain later checkpoints.

Source and isolated-fixture findings only. No real credentials, password reset, mail delivery,
provider installation or authentication setting was changed. Scheduling/adapter pin `2fd02a2`
is preserved; this comparison introduces no new production plugin or policy implementation.

## What the pinned combination already does

The previous security card described the vendor's fallback in isolation. With
`CSF_MFA_ENABLED=true`, the existing `CSF_MFA_Session::providers()` callback filters the registry
to TOTP and backup codes **globally, for all WordPress identities**. If every stored provider
is no longer registered, the vendor's default email fallback is unavailable and its supported path returns
`no_available_2fa_methods`. It does not silently authenticate with a password. Disabling the
adapter restores the vendor's broader provider list; that is not a recovery procedure or bypass.

A lost phone is different from an unregistered provider: enrolled one-use backup codes remain
the normal recovery factor. Losing both factors/codes requires separately authorized custodian
intervention; this work does not qualify an automated factor-replacement flow.

The enabled adapter does **not** suppress the vendor's compromised-password reset or security
notifications. These remain real consequences needing explicit scope approval before activation.

| Behavior | F1 — vendor-native baseline | F2 — existing pinned adapter restriction |
| --- | --- | --- |
| Normal factors | Vendor supports TOTP, backup codes and email among its available methods | Only TOTP and backup codes registered while adapter enabled |
| All stored provider types removed/unregistered | Vendor can offer available email fallback | Fails closed; independent recovery intervention required |
| Repeated invalid factor attempts | Native backoff remains; default reset threshold 30 failed attempts | Same native backoff and reset threshold; no threshold override |
| Reset effects | Changes affected user's password, clears login nonce/failure counters, attempts security mail | Same effects; adapter does not disable them |
| Recipients | Affected user's existing email plus site administrator, unless same address or vendor filter changes notification policy | Same existing recipient selection; no new addresses or recipient policy inferred |
| False-positive lockout | Repeated legitimate code mistakes can still trigger reset and recovery | Same reset risk; provider loss additionally requires custodian intervention instead of email fallback |
| Upstream modifications | None | None; existing vendor-supported registry filter in the pinned adapter |
| Release consequence | Retaining email fallback while enabling the current adapter would require a separately reviewed source change; this is not the unchanged calendar candidate | Already implemented at the current pin; no additional deployable file is needed |

**Recommendation: F2, preserving the current tested candidate.** It prevents email fallback,
retains the vendor's brute-force safeguards and avoids another policy plugin. It does not mean
"no automatic resets or mail." If the owner requires either of those to be absent, activation
stays held: no safe replacement has been qualified by this work. Do not raise the reset limit,
silently suppress mail after changing a password, enable email as a farm factor, or edit vendor
bytes to bypass this decision.

## Private prerequisites and allowed test boundary

Use the existing private controller/recovery worksheet as the single private decision record.
Do not repeat account matching or copy identities/addresses/custody references to this document.

- Witness each controller's own native WordPress login; record verifier/date and secure evidence
  reference, not a password or browser cookie. Separate WordPress IDs do not prove separate humans.
- Confirm each account's recovery custodian and control of its existing recovery destination.
  Record individual authenticator/device custody, secure/offline one-use code custody, maintenance
  responsibility and whether technical operator/initial farm owner are independent humans.
- Privately verify the affected-user and site-admin recipient settings and existing sender/transport.
  No delivery is established by a source check or a captured mock message. Approve a finite native
  security-mail transport test to those verified recipients separately; no lesson alerts or bulk mail.
- Rehearse failed-factor threshold/reset and fallback behavior only on synthetic local identities.
  Do not deliberately trigger the 30-failure reset on the three real accounts. Any necessary real
  password reset or factor-replacement action needs its own exact consent and recovery procedure.

## Single A/B/C card — complete only after private confirmations and F choice

| Decision | Scope to select |
| --- | --- |
| F | F2 design already approved. Preserve the current pin and native protections. Sender, verified recipient references and finite delivery-test scope still need private verification/consent before activation. F1 remains a source comparison, not a new choice or permission to weaken the pinned adapter. |
| C | Confirm private controller/custodian assignments and witnessed-login evidence; designate maintenance/update and incident-recovery responsibility. Unknown human independence stays unresolved. No privilege grant follows from a custody reference. |
| A | Conditional on F/C: install the pinned 21-file Two-Factor0.17.0 distribution plus 4-file adapter0.2.0; activate those two and enable `CSF_MFA_ENABLED`. Keep mandatory-admin enforcement off. Verify the exact existing scheduling20 files without activating scheduling. |
| B | Conditional on F/C/A and each controller's consent: individual TOTP/backup enrollment; fresh native challenge/login/logout and one-use-code recovery rehearsal. Controllers retain secrets; no code collection, shared seed, duplicate account or changed recovery destination. |

Before A/B, verify current target-bound recovery protection and take a new pre-authentication
backup; retain a reviewed rollback to the protected pre-change state. Stop for unexpected
lockout, mail/credential effects outside approved scope, changed identities, missing recovery
or source mismatch. Do not auto-restore over newer owner changes or disable MFA to pass a test.
No A/B execution is authorized by this prepared card or an in-principle recommendation.

### Minimum execution request after private verification

Complete these fields in the existing private worksheet, not in a public issue. Do not create
another account register or collect factor secrets. Pending fields keep the request conditional.

| Required item | Completion evidence / exact scope |
| --- | --- |
| Controller and C custody | Witnessed native login references for each existing identity; verified recovery custodian/destination control; operator/owner human-independence determination; individual device/code custody and maintenance/recovery responsibility |
| Security-mail readiness | Privately reviewed existing affected-user/site-admin recipients and sender; actual web/PHP mail route identified, not inferred from CLI PHP, plugin presence or an option's existence |
| Finite outbound test | Separately approve the exact verified sender/recipients, transport and bounded message count/purpose. No deliberately induced real-account reset, password-reset link or factor code in a transport test; no unapproved retries or forwarding. No real delivery is implied by local interception. |
| A installation | Approved pinned provider21/adapter4 files and activation/configuration scope above; no mandatory enforcement, role change, scheduler activation or schema apply |
| B enrollment | Each controller consents; personally enrolls TOTP/backup codes and completes fresh challenge, logout and single-use-code rehearsal without changing credentials/recovery destinations |
| Window and rollback | Fresh target-bound pre-authentication backup, verified recovery readiness, exact approved window and rollback reference; no restoration over later owner changes |

Approve A and B individually and record C assignments; outbound-test permission is independently
scoped. If readiness is incomplete, finish only the safe read-only/local work and report the
missing evidence. A saved reference or syntactically plausible email is not ownership, custody,
sender authentication, transport compatibility or delivery proof.

### One controlled native security-mail transport test — prepared, not authorized

This is the single proposed test. Do not repeat completed configuration inspection or account
matching. Private bindings are not verified, so it is **not execution-ready**. Do not guess
recipients from public contact details or select an SSH/Plesk identity as sender.

| Binding / limit | Exact proposed scope |
| --- | --- |
| Target | Designated protected sandbox, verified by the existing exact-target guard; no production or shared mail-service changes |
| Sender | Existing effective native WordPress security-mail sender after private review of active mail hooks and actual web/PHP transport; no From override or new credentials |
| Recipients | One consenting affected-account controller's verified existing mailbox, plus verified existing site-admin mailbox if distinct and separately consented. Deduplicate privately; maximum TWO messages, each with one To recipient. No CC, BCC or recipient/forwarding changes. |
| Subject | `Digital Ranch sandbox security-mail transport test` |
| Body | Exact plain-text body below; a unique non-secret per-message reference bound to the single approved test |
| Submission limit | One approved application attempt, maximum TWO unique initial submissions, one per consented distinct mailbox; 120-second submission-process limit; no automatic application resubmission or alternate route |
| Native delivery | Existing mail-service queue, retry and expiration behavior is permitted only within the verified/consented native policy; service-owned delivery attempts may outlive the submission command and may produce duplicates or delivery-status notices. No service or queue setting changes. |
| Queue ownership | Existing mail-service operator retains queue ownership; named pilot custodian observes only this test. No queue flush, hold, purge, forced retry, shared-log export or infrastructure change. |
| Observation | Check after submission, about15 minutes and1 hour, then reconcile by submission time plus the verified sender queue/bounce horizon plus1 hour. Exact horizon/timestamps and responsible operator are private approval bindings, not an invented default. No unattended poller is created. |
| Interface | Reviewed native WordPress `wp_mail` route used by security notifications; no password-reset function, lost-password endpoint, factor challenge or failed-attempt threshold |
| Exclusions | No links, login/reset tokens, factors, recovery codes, attachments, customer records, lesson alerts, marketing, inquiry or emergency changes |
| Evidence | Private per-recipient submission state and receiver confirmation; no addresses, headers, identifiers, private infrastructure or mailbox screenshots in GitHub |

```text
Digital Ranch sandbox security-mail transport check.
Reference: <approved unique message reference>
Please confirm receipt to the agreed operator through your usual private channel.
```

The reference grants no access. Record exact sender, consented recipients, transport, reference
and window in the EXISTING private worksheet. Review actual active hooks for extra recipients,
debug output, queuing and other outbound effects before authorizing an executable payload. Do
not bootstrap WordPress merely to discover those effects without a reviewed scope and current
recovery protection. CLI PHP or host-MTA-only evidence is not proof of the web/PHP mail route.

After private bindings are verified, finalize/hash the target-bound payload and obtain ONE
consent for that test and its native queue behavior. This prepared request grants no sending
authority. The120-second limit bounds application submission only, **not subsequent delivery**.
Do not replace transport or alter shared infrastructure. Privately verify retry/expiration
settings, envelope sender/return path, delivery-status-notice recipients and any downstream relay
policy; do not pretend that a local queue horizon bounds a remote provider's queue.

Before the first call, commit a durable per-test/per-recipient submission claim. The locally
tested [submission gate](../../scripts/pilot/security_mail_ledger.py) permits at most two initial
claims and blocks replay even after a crash, failed/unknown result or concurrent workers. It has
NO sender/queue/filesystem operation. It is not installed and is not a complete dispatcher.
Before real use, bind exact approved sender/recipients/transport to opaque references, deduplicate
actual mailboxes, validate private durable-storage/runtime availability and preserve claim/results
across job restarts. Never start a new test reference merely to evade an unresolved claim.

Use unique approved message references and, where supported by the reviewed native route,
Message-IDs for correlation. These do **not** guarantee inbox deduplication or exactly-once SMTP
delivery. Record repeated native attempts separately from new application submissions. A lost
acknowledgment can produce a duplicate delivery; compare the same reference privately and escalate
rather than creating a replacement message.

Record evidence as separate events; a later event must not erase earlier uncertainty:

| Event | What it proves |
| --- | --- |
| `submission_accepted` | The application's mail API accepted the call, not inbox receipt |
| `queued` | The verified native queue contains this message, with private correlation evidence |
| `attempted` | A recorded delivery attempt for this message, not a new application submission |
| `provider_accepted` | A downstream provider accepted transfer; no claim that the receiver saw it |
| `receiver_confirmed` | The consenting receiver confirmed the correct reference in inbox/spam through the agreed private channel |
| `unknown` | Evidence is missing, ambiguous or outside the observation boundary; no inferred delivery or expiration |

The named operator must be able to reconcile only this test's IDs without exposing other mail.
If queue evidence cannot be isolated, the horizon cannot be verified, or no custodian can perform
the agreed final check, hold dispatch and report that concern. Read-only active-session checks
and the custodian's agreed final check are distinct from a background automation or promised
future Codex execution. No new scheduler is installed by this proposal.

Close early only after both intended receivers confirm and duplicate/anomaly review is clear.
Otherwise reconcile by the agreed final deadline: PASS only for all required confirmed receipts;
PARTIAL for some; FAIL for definitive rejection/bounce; UNKNOWN for pending/ambiguous outcomes.
A queue expiry setting is not proof that this particular message expired; require evidence.
Delivery after the observation window remains possible, especially after onward provider
acceptance; the owner must acknowledge that limitation before consenting. Do not purge or force
retry to manufacture a terminal result. Native bounces/status notices are reviewed native-service
effects, not authorization for additional application messages or a password reset.

Stop further application submissions for target/source/sender/recipient/route mismatch, missing
consent/recovery, unexpected additional messages or credential effects, timeout or ambiguity.
Inspect existing private job/queue/receiver evidence before proposing another test. An application
stop does not recall a queued message. Backups cannot retract email. No real failed-factor or
password-reset path is exercised; those vendor paths remain synthetic-fixture tests only.

**D remains later:** actual operator recovery and essential Toolkit/SSO/API compatibility must
pass before separate mandatory-admin enforcement. Its application-password/XML-RPC restrictions
are not approved by A/B. **E remains later:** independently verified MFA-bound ownership ceremony
and exact effective Kris/Beth permission review before farm grants. No native-role promotion.

After those gates, guarded schema-v3/15-table activation and finite fictional appointments follow
the recovery-qualified sandbox policy and [staff acceptance walkthrough](../13-scheduling-staff-acceptance.md).
Only Beth and Kris accept actual workflows. Public booking, inquiry, emergency, production,
client data, lesson alerts and expanded relationship modules stay outside this work.
