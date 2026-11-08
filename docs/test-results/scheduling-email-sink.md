# Email sink adapter — local qualification only

November 8, 2026. Real booking email/push remains held. No addresses, delivery
consent, sender, WordPress mail hooks, cron, queue polling or provider credentials
were enabled. The new file is deliberately not loaded by the plugin bootstrap.

`CSF_Scheduling_Email_Test_Adapter` accepts a copy of a held booking notice and
selects only the recipient's recorded child subset. Messages exclude free-text
reasons, names, contacts and medical details. A stable event/recipient/version key
supports sink deduplication. Versioned claim/finish transitions reject stale
revisions and wrong claim tokens, cap attempts at three, back off only after an
explicit transient rejection, and mark lost claims, timeouts and unknown/late
results uncertain instead of automatically retrying them. Acceptance is called
`sink_accepted`, never delivered. The original durable notice stays held.

The only supported adapter is the final in-memory sink. Every entry requires the
exact disposable `/test/site/` local environment. There is no transport interface
through which a production sender can be injected. These guards supplement the
absence of network/mail code; they are not real delivery authorization.

**Actually run:** 31 isolated checks on each PHP 8.1.34 and 8.3.36, covering recipient
subsets, no sensitive free text, unchanged held event, idempotent acceptance,
changed-payload/key rejection, claim ownership, stale revisions, retry limits,
backoff, lost/late/ambiguous results, permanent rejection and nonfixture denial.
The sink tests are wired into the disposable container suite.

**Not implemented or claimed:** a durable database worker/CAS claim repository,
provider delivery, recipient-address resolution/current-scope dispatch checks,
manual uncertain-delivery reconciliation or a live retry service. Claim transitions
must be atomically persisted before a future external submission; these pure tests
do not prove multi-process exactly-once delivery. Real provider idempotency and
uncertainty need separate qualification and scoped outbound consent. The existing
transactional in-app notices and acknowledgements remain the operational candidate
notification path, subject to the unrun WordPress/database acceptance gates.
