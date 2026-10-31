# 01 — Platform Architecture

## Principles

1. **Kadence + Gutenberg for presentation; plugins for behaviour.** Business logic never lives
   in the theme. Switching or breaking the theme must not break bookings, emergency pages,
   or data.
2. **Separate plugins by risk and audience.** Public marketing, emergency operations, and
   private client data have different security, uptime, and privacy needs.
3. **Public, unlisted, and private are different things.**
   - *Public*: marketing pages, articles — indexed.
   - *Unlisted*: emergency horse pages — reachable by anyone with the URL, not indexed, not
     listed. **Never used for confidential data.**
   - *Private*: client, horse-owner, and staff records — authenticated *and* authorized per
     record.
4. **WordPress-native first.** Custom post types, capabilities, the Settings API, and core
   sitemaps before third-party frameworks. No page builders.
5. **Explicit capabilities.** Every sensitive feature has its own capability, granted
   deliberately — never implied by “Editor” or similar.

## Components

```
┌─────────────────────────────────────────────────────────────────────┐
│ Kadence theme (+ child theme only if needed)  — presentation only   │
├─────────────────────────────────────────────────────────────────────┤
│ csf-core         Knowledge Library, booking buttons, shared helpers │
│ csf-emergency    Horse IDs, farm status, banner, /emergency, log    │
│ (future)                                                            │
│ csf-notify       Alert subscriptions & delivery (email/push/SMS)    │
│ csf-assist       Evacuation help requests, responders, dispatch     │
│ csf-onboarding   Households, riders, agreements, e-signatures       │
│ csf-portal       Client / horse-owner / staff portals               │
└─────────────────────────────────────────────────────────────────────┘
```

Each future plugin depends only on documented hooks from the plugins beneath it:

| Hook | Provided by | Purpose |
|---|---|---|
| `csf_emergency_status_changed( $uuid, $state, $previous )` | csf-emergency | Fires once per confirmed farm status change. Notification modules use `$uuid` as the idempotency key. |
| `csf_emergency_notification_summary` (filter) | csf-emergency | Lets a notification module describe recipients on the review screen before confirmation. |
| `csf_emergency_purge_caches` | csf-emergency | Lets hosting-specific cache purges run after a status change. |

## Emergency plugin design decisions

| Decision | Reason |
|---|---|
| Standalone templates for `/h/…` and `/emergency` (no `wp_head()`) | They keep working if the theme, a builder, or an optimisation plugin breaks, and they load in one request on a weak rural mobile connection. |
| 8-character tokens from a 31-character alphabet without 0/O/1/l/i | Cryptographically random, non-sequential, ~8.5 × 10¹¹ combinations, and readable aloud from a worn tag. |
| Tokens are permanent and never regenerated | Printed tags must keep working for the horse’s lifetime. |
| Unknown, unapproved, and retired tags return the same page (with farm phone) | Does not reveal which codes exist, but a finder still reaches the farm. |
| Profiles are hidden until “Approved for public emergency display” is ticked | Information is published deliberately, never by saving a draft. |
| Microchip number masked by default | Shows that a chip exists without publishing the number. |
| `/emergency` shows aggregate horse counts by default | The spec asks for horse status summaries *and* no public directory. A per-horse list is an opt-in setting, shown only during an active emergency. |
| Report Found Horse uses `sms:` with a pre-filled message | Works on any phone with no server round-trip, which matters when the site is under load or coverage is poor. |
| Three-step status change with a single-use confirmation key | Prevents double clicks, browser resubmits, and back-button replays from applying (and later notifying) twice. Identical status + message within 5 minutes is also rejected. |
| Dedicated audit table rather than post meta | An append-only log that survives option changes and can be reviewed after an incident. |
| Data is kept on deactivate/uninstall | Emergency records must not vanish because a plugin was toggled. |

## Data separation for future portals

- Horse **emergency** profiles (public-approved fields only) are a different post type from
  future horse **records** (training journals, veterinary, billing). The portal will link a
  horse record to its emergency profile by ID; it will never expose portal fields through
  `/h/…`.
- Household, rider, agreement, and signature data will live in dedicated tables owned by
  `csf-onboarding`, not in user meta or post meta, with per-record authorization checks
  (“can this user view *this* rider?”), not just role checks.
- Signed agreements: stored outside the web root or in a non-public uploads directory,
  served through an authorization-checked endpoint, with the agreement version and a hash
  of the signed content recorded.

## Planned roles (to be confirmed)

| Role | Purpose |
|---|---|
| Administrator | Site owner (Kris). |
| Emergency Coordinator (`csf_emergency_coordinator`, implemented) | Change farm status, maintain horse IDs, view log. |
| Trainer (future) | Training notes, lesson rosters, horse journals. |
| Staff / Working Student (future) | Assigned chores and schedules only. |
| Client (future) | Own household, riders, agreements, lesson notes. |
| Horse Owner (future) | Own horses’ records only. |

## Security baseline

- MFA for every account with emergency or administrative access. The dashboard warns when the
  WordPress.org **Two Factor** plugin is missing or not enabled for the current user.
- `DISALLOW_FILE_EDIT`, least-privilege roles, automatic minor core updates.
- All input sanitized, all output escaped, nonces and capability checks on every write
  (enforced via WordPress Coding Standards in `phpcs.xml.dist`).
- No credentials in the repository. API keys for future notification providers go in
  `wp-config.php` constants or environment variables on the server.

## Takeover review

The design is retained. See [the takeover audit](10-takeover-audit.md) and
[security release gates](SECURITY.md). Confirmation transients and status comparisons are
not atomic: the existing “once per event” hook is not an exactly-once guarantee under
concurrency or persistence failure. Notification consumers need durable idempotency and
recoverable event processing before operational use.
