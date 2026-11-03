# Calendar security — one private verification and scoped decision

This procedure completes calendar prerequisites; it does not add users, redesign permissions,
enable email alerts or deploy the relationship prototype. Use the existing account labels:
Technical Master, Operations, Head Trainer. Never infer human control from a successful username
match, HTTP protection, Plesk access or two distinct WordPress IDs. Do not repeat account matching.

## Private verification ceremony

1. The owner identifies the human controller and authorized recovery custodian for each existing
   account in an owner-controlled secure administrative record. Record a non-secret evidence
   reference and date; never copy usernames, addresses, passwords or recovery material to GitHub.
2. Each controller uses their own private browser to sign in through native WordPress, opens
   their own profile and confirms it is the intended account. The owner or approved verifier
   witnesses the result privately. Do not share cookies, screen-record secrets, reset a password,
   edit recovery addresses or grant access to complete this step. An inability to sign in is a
   recovery blocker, not permission for Codex to repair credentials.
3. Confirm who actually controls the existing recovery destination and how the custodian can
   be reached through an independent channel. Record custody confirmation, not the destination.
   Do not send a test/reset email without separate outbound authorization. This verifies custody
   only; it is not evidence of successful email delivery or a completed account recovery.
4. Identify whether Technical Master and Operations are controlled by different humans. Prefer
   different controllers. If one person controls both, record that explicitly and stop the initial
   ownership ceremony for review; do not represent two browser sessions as independent humans.
5. The private record states for each label: controller confirmed, recovery custodian confirmed,
   witnessed native login reference, intended authenticator device custodian and recovery-code
   storage custodian. Record the maintenance/recovery contact and essential Toolkit/API workflows.
   Unknown fields remain pending. Owner attestation and witnessed technical evidence are separate.

No factor secrets, recovery codes or passwords are requested by Codex. Use an existing secure
owner record or Gateway's secure handoff for references; never paste the completed record into
chat or an issue. A reference alone is not independent verification.

Private handoff template (plain text; use only account labels, not usernames):

```text
Technical Master: controller reference __; recovery custodian reference __;
  witnessed native login date/verifier reference __; factor/device custodian __;
  recovery-code custody reference __.
Operations: controller reference __; recovery custodian reference __;
  witnessed native login date/verifier reference __; factor/device custodian __;
  recovery-code custody reference __.
Head Trainer: controller reference __; recovery custodian reference __;
  witnessed native login date/verifier reference __; factor/device custodian __;
  recovery-code custody reference __.
Technical operator and initial farm owner: different humans / same human / undecided.
Maintenance/recovery custodian reference: __.
Essential Toolkit/API workflows: __ / none known / not yet reviewed.
```

Leave unenrolled factors and unperformed login/recovery tests explicitly pending. Submission
collects owner statements for review; it performs no server operation and grants no authority.

## One consolidated owner authorization — select scopes individually

| Scope | Exact proposal | Owner choice |
| --- | --- | --- |
| A — provider and adapter | Install the reviewed Two-Factor 0.17.0 distribution (21 files) and csf-mfa 0.2.0 (4 files); verify every file against the pinned inventory. Activate those two plugins and enable `CSF_MFA_ENABLED` only. Keep mandatory-admin enforcement off. | Approve / Revise / Hold |
| B — individual enrollment and recovery rehearsal | The three verified controllers enroll separate TOTP authenticators and generate single-use recovery codes themselves. Test fresh native login/logout, one backup-code login and rejection of its reuse; securely retain remaining codes. No seed/code collection by Codex or changes to recovery destinations. | Approve / Revise / Hold |
| C — maintenance and recovery | Name a human custodian for advisory review, tested updates and lost-device recovery; assign secure/offline code custody per person. No automatic provider update or emergency MFA bypass. | Private custodian references required |
| D — mandatory administrator MFA | Separate checkpoint after actual recovery evidence and essential Toolkit/API compatibility; enable `CSF_MFA_ENFORCE_ADMIN` only if approved. It globally disables application-password authentication/creation and authenticated XML-RPC. | Hold until evidence; then Approve / Revise / Hold |
| E — farm authority | Separate effective-access review and MFA-bound one-use ownership ceremony: Technical Master operates, Operations becomes initial farm owner, Head Trainer receives the reviewed group/overrides. Preserve native roles. | Hold until verified controllers and MFA; then Approve / Revise / Hold |
| F — provider automatic security consequences | Review Two-Factor's built-in failed-factor password-reset behavior and user/admin security emails, plus its email fallback if all stored methods become unavailable. Approving factor enrollment alone does not approve these external or credential effects. | Hold; resolve before provider activation |

Approval of A does not approve B, D or E. Naming a custodian is not a privilege grant. No native
security email, real lesson alert, password reset, test message or external notification is
authorized by this card. If an essential flow requires one, stop that flow and identify its exact
recipient, transport and purpose for a scoped decision. No shared-host/Plesk security change.

Provider source review identifies a concrete dependency for A: `process_provider()` can call
`reset_compromised_password()` and `send_password_reset_emails()` after the provider's failed-login
policy triggers (default threshold: 30 failed attempts, subject to vendor filters). The vendor also has an email fallback when previously stored methods are no
longer registered. The adapter rejects email as Digital Ranch MFA evidence, but that does not
prevent the provider from sending mail. Therefore A activation is blocked until F is explicitly
resolved: either approve the exact native security consequences and transport/recipients, or
review and locally test a narrowly scoped alternative before activation. Do not silently disable
vendor protection, change vendor bytes, claim that backups can unsend mail, or treat lesson-email
authorization as covering native security mail. These are source findings, not observed live events.
See the [pinned vendor authentication path](https://github.com/WordPress/two-factor/blob/0.17.0/class-two-factor-core.php#L2150)
and [provider-loss fallback](https://github.com/WordPress/two-factor/blob/0.17.0/class-two-factor-core.php#L744).

## Exact candidate and rollback boundaries

- Implementation pin: `2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1`; Scheduling 0.4.0 remains unchanged.
- Official Two-Factor ZIP SHA-256:
  `59d7c75f57966b93cc59657a0c14d58f247ba6f350b081c8602b3cacdc51e473`.
  Use the [verified release procedure](../test-results/scheduling-security.md) and
  [45-file inventory](../test-results/scheduling-security-install-inventory.json).
- A adds only the 25 provider/adapter files to the reviewed scheduling candidate. It creates
  no scheduling tables. Activation changes native plugin metadata; adapter enablement changes
  its approved configuration. B changes the consenting users' provider enrollment, code hashes,
  session tokens and MFA proof epochs; backups therefore contain sensitive authentication data.
- Before any authentication/schema write, verify recovery readiness and capture a fresh
  target-bound pre-change backup, including configuration and user metadata. Restrict custody.
  A prior successful isolated restore establishes a procedure, not today's backup freshness.
- Record exact before/after files, configuration, options and approved user metadata privately.
  Rollback uses that approved pre-change state; do not reset real credentials, disable MFA or
  overwrite newer owner data automatically. A failed recovery/lockout stops security execution
  for owner review. Never apply fixture seeding or CLI ownership shortcuts to the farm.

## Compatibility and acceptance checkpoints

Read-only Toolkit inventory/help can identify available interfaces but cannot prove SSO works.
After scoped security authorization, the operator performs actual native and essential Toolkit
login tests, verifying that password-only/SSO access cannot obtain protected Digital Ranch
rights without a fresh approved factor. If a Toolkit flow fails, use verified native login for
diagnosis and report the incompatibility; do not add an MFA exemption or change shared Plesk.
Inventory application-password/XML-RPC dependencies before D; absence of saved passwords alone
does not prove no integration depends on those paths.

Validate actual factor challenge, logout, consumed-code rejection, expired/revoked sessions,
lost-device custody and protected access after changes. Local synthetic tests are prerequisite
evidence, never a substitute for each controller's real enrollment and recovery.

In parallel, keep the existing schema-v3/15-table dry-run, migration/data-preservation,
permission-group/override and fictional appointment tests ready. Once security gates pass,
routine scheduling activation and guarded schema work follow the adopted recovery-qualified
sandbox policy. Preview effective rights before the separately approved grants. Run the
[staff walkthrough](../13-scheduling-staff-acceptance.md) on the protected site at phone,
tablet and desktop widths. Only Beth and Kris can accept their actual workflows.

Production, public booking, inquiry activation, emergency management and real client records
remain excluded. Email alerts and Issues18–20 remain subsequent milestones.
