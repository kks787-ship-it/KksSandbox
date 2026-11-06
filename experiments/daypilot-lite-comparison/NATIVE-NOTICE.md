# Native renderer provenance and license

The comparison uses the unmodified Digital Ranch planner PHP, time helper and planner JavaScript/CSS from `kks787-ship-it/KksSandbox`, scheduling implementation `c2783b5e3f6eba046b97e5dc517a198517532377`. Planner/time/JavaScript are unchanged from the earlier 0.4.5 candidate. The 0.4.6 staff stylesheet also contains catalog rules unused by this harness.

Source: https://github.com/kks787-ship-it/KksSandbox/tree/c2783b5e3f6eba046b97e5dc517a198517532377/wp-content/plugins/csf-scheduling

The original scheduling plugin declares **GPL-2.0-or-later**. This separate experimental wrapper/distribution chooses **GPL-3.0-or-later**, retaining native source notices and the Apache-2.0 DayPilot LICENSE/NOTICE. It does not change the farm plugin's license. Review the combined distribution and license obligations before any adoption; Apache-2.0 must not simply be relabeled GPL-2.0-only.

The harness replaces WordPress presentation helpers, access checks and document reads with explicit CLI-only synthetic stubs. It does not load WordPress, a database or a farm configuration. The browser shell sets a system font and positions desktop iframe dialogs; it is not a screenshot or benchmark of the actual WordPress/Kadence installation.

The recorded native source hashes are in the experiment evidence directory; the download includes generated native assets for offline viewing.
