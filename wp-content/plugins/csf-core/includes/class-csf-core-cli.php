<?php
/**
 * WP-CLI commands.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Corner Stone Farm site commands.
 */
class CSF_Core_CLI {

	/**
	 * Create or refresh the site's pages, front page, menus, and footer.
	 *
	 * Idempotent. Pages edited in WordPress are never overwritten unless --force is given.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would change without changing anything.
	 *
	 * [--force]
	 * : Overwrite pages edited since setup, and rebuild the primary menu.
	 *
	 * [--status=<status>]
	 * : Status for newly created pages.
	 * ---
	 * default: publish
	 * options:
	 *   - publish
	 *   - draft
	 * ---
	 *
	 * [--set-permalinks]
	 * : Set the permalink structure to /%postname%/ if it differs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp csf setup --dry-run
	 *     wp csf setup --set-permalinks
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Options.
	 */
	public function setup( $args, $assoc_args ) {
		$log = CSF_Core_Setup::run(
			array(
				'dry_run'        => WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false ),
				'force'          => WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false ),
				'status'         => $assoc_args['status'] ?? 'publish',
				'set_permalinks' => WP_CLI\Utils\get_flag_value( $assoc_args, 'set-permalinks', false ),
			)
		);
		foreach ( $log as $line ) {
			WP_CLI::log( $line );
		}
		if ( CSF_Core_Setup::has_errors( $log ) ) {
			WP_CLI::error( 'Site setup is incomplete. Some changes may have been saved; review the errors before trying again.' );
		}
		WP_CLI::success( WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false ) ? 'Site setup dry run complete.' : 'Site setup complete.' );
	}

	/**
	 * Report page status, edits, and outstanding owner notes.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Options.
	 */
	public function status( $args, $assoc_args ) {
		$rows  = array();
		$notes = 0;
		foreach ( CSF_Core_Setup::pages() as $page ) {
			$post   = get_page_by_path( $page['path'], OBJECT, 'page' );
			$count  = $post ? CSF_Core_Setup::owner_notes( $post ) : 0;
			$notes += $count;
			$rows[] = array(
				'path'        => '/' . $page['path'] . '/',
				'status'      => $post ? $post->post_status : 'missing',
				'edited'      => $post && CSF_Core_Setup::is_edited( $post ) ? 'yes' : 'no',
				'owner_notes' => $count,
			);
		}
		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, array( 'path', 'status', 'edited', 'owner_notes' ) );
		if ( 'table' === ( $assoc_args['format'] ?? 'table' ) ) {
			WP_CLI::log( sprintf( 'Owner notes outstanding: %d (must be 0 before launch).', $notes ) );
			$booking = CSF_Core_Booking::get();
			WP_CLI::log( 'Booking URL: ' . $booking['url'] . ( $booking['verified'] ? ' (verified)' : ' (NOT verified)' ) );
			WP_CLI::log( 'Active theme: ' . wp_get_theme()->get( 'Name' ) . ( get_template() === 'kadence' ? '' : ' (expected Kadence)' ) );
		}
	}
}
