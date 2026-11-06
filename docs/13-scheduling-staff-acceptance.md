# Staff calendar acceptance — Beth and Kris

## Security prerequisite for the current local candidate

For scheduling0.4.0 / csf-mfa0.2.0, the [current GateB decision package](pilot/gate-b-owner-review.md)
supersedes earlier provisioning/CLI directions below. No protected pilot is approved. Before
staff acceptance, an independently authorized operator must verify separate real identities,
MFA/recovery custody, the exact release/schema and granular grants. The owner's finite bootstrap
requires independent browser MFA approvals; no legacy CLI flag or WordPress Administrator role
substitutes for ownership. Users complete a fresh factor challenge, not just enrollment.

In the later authorized pilot, record that password-only access cannot use native administration
or the private calendar, enrolled-but-unchallenged access stays locked, the real owner can open
Team & Permissions without WordPress administrator rights, employee denials take immediate
effect and logout/revocation removes access. Verify private recovery-code handling without
recording codes/seeds/passwords in acceptance notes. Beth and Kris must each personally test
the calendar tasks below; automation is not their acceptance. Local security results are in
[the Issue14 report](test-results/scheduling-security.md).

**Sprint #9.1 / Issue #10 · candidate 0.2.1 · synthetic records only.**
This is a walkthrough for a disposable local fixture or a **separately approved** protected
sandbox pilot. It does not authorize installation, real appointments or real client data.
Automated execution is recorded in [the validation report](test-results/scheduling-staff.md).
Beth and Kris have **not** completed these tasks or accepted an operational release yet.

## Before the walkthrough

The pilot operator must provide approved staff accounts/MFA, a configured facility timezone,
service hours, enabled services/capacities/durations, horse sources, required resources,
workload/break limits and travel buffers. Daily staff tasks use forms; initial policy setup
and owner-verified account provisioning remain controlled operator work. Do not ask Beth to
edit a configuration file while running lessons.

Use unmistakably fictional names: **Pilot Instructor**, **Pilot Arena**, **Pilot Maple**,
**Pilot Cedar**, **Pilot Riders A–D**, and **Pilot Client Horse**. Do not substitute real
children, guardians, horses, medical information, agreements or contact details.
The automated fixture uses America/Los_Angeles and January 25–30, 2027, with explicit test
policies. These are test values, not approved Corner Stone Farm operating policies. The pilot
operator must choose a comparable synthetic working week within the reviewed pilot settings.

Each task is complete only when Beth or Kris can do it without editing structured data or
being told a UTC offset. Record assistance needed, confusing labels and time taken. Do not
mark a task accepted merely because an automated check passed.

## Tasks

| Task | Staff actions | Expected result / acceptance |
|---|---|---|
| Find a day or week | Open Digital Ranch. Pick a local date and Day or Week, then Show calendar. Try instructor, arena and horse filters. | Correct local dates and named records, readable from a phone; filters do not expose private names/IDs in URLs. |
| Set up resources | In Farm setup, add an instructor, arena and the two school-horse availability resources. Add explicit available intervals for each. | No intervals clearly means unavailable. Changing a resource name keeps its identity/history. No permissions are granted by adding an instructor resource. |
| Add riders and horses | Add Pilot Riders A–D with evidence flags unchecked. Add Maple/Cedar as school horses, select their availability resources and explicitly approve appropriate services and riders. | Unknown suitability remains blocked. Client-owned horses need no school resource. Evidence boxes are not electronic signatures. |
| Book a private lesson | New appointment: title, configured lesson service, confirmed, local start/end, instructor and arena, Rider A, Maple in the horse pool. Add a turn matching the appointment. Check schedule, then Create appointment. | Preview reports actual reserved horse minutes and makes no reservation. Save checks again. One confirmed appointment appears, with a PRE-RIDE BLOCK for missing evidence. |
| Four riders / two horses | Create a configured group service, e.g. 13:00–14:00. Select A–D and both horses. Enter A/Maple and B/Cedar 13:00–13:20, then C/Maple and D/Cedar 13:35–13:55. | Four readable named turns, 15-minute recovery gaps, 40 ridden/reserved minutes per horse when no travel buffer applies. Capacity/workload policy is checked, not guessed. |
| Add a recovery interval | Add a turn, set its end, enter recovery minutes, then add the next turn. Review/edit its proposed local times and rider/horse. Remove an unwanted row with keyboard controls. | Recovery shifts the next proposed start; nothing is saved automatically. All actual constraints still pass server preview. |
| Preserve a rotation | Open an existing group, change only its title, preview and save. | Riders, horse assignments and each partial turn time remain unchanged. |
| Client-owned haul-in | Add Pilot Client Horse, choose Client-owned, approve its configured client-horse service and rider. Make a corresponding appointment with the resources that service requires. | No fictitious school-horse assignment. Haul-in/off-site/travel behavior follows the actual configured service; no route optimization is implied. |
| Catch a collision | Try to confirm a second appointment over an occupied instructor/arena, horse or rider. | Before Save is enabled, preview identifies the selected conflicting resource/person and gives a correction path. It does not disclose another family’s appointment. |
| Protect a sore horse | Add a Horse welfare concern block for Maple that overlaps an existing booking. | Existing appointment remains confirmed but displays WELFARE BLOCK / staff review. It is not silently canceled. New unsafe bookings fail. No medical notes are requested. |
| Release a block | After a synthetic staff review, open the active protective block and enter a release reason. | Restriction is released with retained history; original block is not erased. |
| Move an appointment | Open an appointment, change its start and use “Move end and all turns to match the new start.” Review every proposed turn, preview and save. | End and turns move by the same local-clock difference. Gaps/folds and new conflicts are checked. No implicit move happens merely on changing a title/date. |
| Cancel | Open Cancel appointment. Record initiating party, reason, actual notice and effective time. | Canceled card and readable actor/time/reason history remain. Allocations release. No fee, refund, message or makeup credit is created. |
| Check missing rider evidence | Leave release/guardian/contact evidence unchecked for a fictional minor. Confirm its calendar appointment. | Confirmation and PRE-RIDE BLOCK are visibly distinct. Staff must not begin a service on that evidence; automatic attendance/signature enforcement is not built. |
| Clock changes | Enter a nonexistent spring-forward local time. Then try a repeated fall-back time. | Gap is rejected. Repeated time offers first/second occurrence from the server. Ordinary times need no manual offset. A closed facility day still cannot be booked. |
| Handle simultaneous work | Beth and Kris preview two overlapping synthetic groups, then attempt saves. | At most one reservation commits. The other receives a stale-review/conflict message and keeps a draft. Re-preview after reviewing current records. |
| Recover uncertainty | If a save reports an uncertain result, use Retry exact command rather than starting a new appointment. | Original command/result is recovered once; no duplicate appointment. A changed record still needs a fresh edit and cannot be overwritten. |
| Test reduced access | Use an explicitly provisioned view-only test account. Try private tools without a nonce or while logged out. | View-only sees no write/audit controls. Unauthorized writes/time/preview requests fail. Public visitors cannot access scheduling records. |

The synthetic “unsigned rider” task represents missing manually verified evidence, not a
real signed/unsigned document. Household onboarding, legally reviewed versioned agreements
and signature/service-start enforcement remain the later Sprint #10 workflow.

## Screenshots from automated local execution

These show only fictional records on stock WordPress 6.8.3, not the protected Kadence site.
They are evidence of rendered controls and geometry, not human acceptance or physical devices.

| Phone — 390px | Tablet — 768px | Desktop — 1280px |
|---|---|---|
| [Phone rotation editor](screenshots/scheduling-staff/staff-390.png) | [Tablet rotation editor](screenshots/scheduling-staff/staff-768.png) | [Desktop rotation editor](screenshots/scheduling-staff/staff-1280.png) |

## Human acceptance record — intentionally uncompleted

- [ ] Beth completes private/group/rotation and welfare tasks on her actual phone.
- [ ] Kris completes roster/availability, stale edits, cancellation and audit tasks.
- [ ] Both can distinguish reservation status, expired holds, welfare review and rider clearance.
- [ ] Neither needs structured-data editing or a manual UTC offset for the tasks above.
- [ ] Keyboard/focus and ordinary mobile touch targets work for the actual devices.
- [ ] Owner-approved policy, suitability, welfare and cancellation interpretations are correct.
- [ ] Protected-stack access, caching, private tool authorization and MFA are verified.
- [ ] Failures/blockers and required changes are recorded before any real-booking approval.

Record reviewer, device/browser, date, task, outcome, help needed and issue reference in a
private pilot record. Do not put client records, credentials or medical evidence in GitHub.
No checkboxes above have been signed off by Beth/Kris in this development sprint.

## Issue #12 — synthetic owner and staff permission walkthrough

Local candidate 0.3.0 changes the access prerequisites above. Do not provision a real person with
the retired four-capability command. Use separately approved existing numeric accounts, tested MFA
and guarded owner bootstrap; no account is inferred to be Kris/Beth. In disposable tests, use
fictional owner/trainer identities. On a future approved pilot, Kris/Beth perform their own tasks.

1. Verified owner opens **Digital Ranch → Team & Permissions**. Select the trainer account and
   load **Head Trainer**. Review group defaults, individual overrides and effective results.
   This is an example, not approval of Beth's actual privileges.
2. Toggle a confirmation checkbox with keyboard/touch. Observe the explicit deny/grant selector
   and effective result. Return to Inherit and verify it follows the group's default.
3. Preview: confirm exact numeric identity/group, newly granted/revoked rights and sensitive
   administration choices. Saving requires a fresh reviewed revision and explicit confirmation.
4. Trainer opens the calendar and books/confirms synthetic work and applies a horse safety hold.
   Farm policy, team security, rider-evidence verification and hold release must fail unless
   independently granted. Calendar confirmation must not clear a PRE-RIDE BLOCK.
5. Owner copies a preset to **Assistant Trainer**, previews affected employees, and saves. Assign
   it to selected existing verified employees through bulk review. Individual overrides remain.
6. Owner edits a group default; review every affected member and confirm that an individual deny
   remains denied. Trainer cannot forge group/checkbox requests to raise their own rights.
7. Keep the trainer browser signed in while the owner disables farm access. The next protected
   request/save must fail and the old session must be revoked. Re-enable only after review.
8. Attempt to deactivate/unverify/deny the protected owner's team controls. Preview must refuse,
   with registry/audit unchanged. Do not actually exercise administrative recovery on a protected
   site without separate identity/recovery approval.
9. Review private permission history by person and UTC dates, including actor and affected members.
   Record device/browser, clarity, assistance needed, expected/observed result and issue references.

Ordinary employees need no WordPress Administrator rights. No invitations, emails, MFA enrollment,
account creation, public booking, emergency or operational acceptance is implied by these tasks.

## Calendar Upgrade — first routine-booking increment (0.4.3)

Routine single-rider booking now uses a date and 15-minute start choices, with finish and horse turn calculated from the configured service. Use **Show later starts** for later parts of the day. **Advanced rotation** retains the explicit group/short-turn workflow. A longer service never waives horse welfare limits, and confirmation does not clear a rider's participation requirements.

Follow the [routine-booking validation and protected acceptance record](test-results/scheduling-routine-booking.md) for the exact candidate and outstanding protected-site acceptance. Do not expect family visit grouping, merged 15+15 buffers, horse deletion/archive changes, booking alerts or the dashboard in this first increment. No real booking email is enabled.
