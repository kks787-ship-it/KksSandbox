<?php
/**
 * Uninstall handler.
 *
 * Emergency horse records, farm status, and the event log are deliberately NOT deleted
 * on uninstall. Removing them requires a manual, documented decision by the farm
 * (see docs/04-emergency-plugin.md). Only the custom role is removed here.
 *
 * @package CSF_Emergency
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

remove_role( 'csf_emergency_coordinator' );
