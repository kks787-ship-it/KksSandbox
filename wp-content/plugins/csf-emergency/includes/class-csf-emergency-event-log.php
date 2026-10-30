<?php
/**
 * Append-only emergency event log.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores every status change and horse status change in a dedicated table so the log
 * survives option changes and can be audited after an incident.
 */
class CSF_Emergency_Event_Log {

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'csf_emergency_log';
	}

	/**
	 * Create or upgrade the table.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_uuid char(36) NOT NULL,
				created_at datetime NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				event_type varchar(40) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				old_status varchar(40) NOT NULL DEFAULT '',
				new_status varchar(40) NOT NULL DEFAULT '',
				message text NOT NULL,
				details longtext NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_uuid (event_uuid),
				KEY created_at (created_at),
				KEY event_type (event_type)
			) {$charset};"
		);

		update_option( 'csf_emergency_db_version', CSF_EMERGENCY_DB_VERSION, false );
	}

	/**
	 * Run install() if the stored schema version is behind.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'csf_emergency_db_version' ) !== CSF_EMERGENCY_DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Record an event.
	 *
	 * @param array $event {
	 *     Event data.
	 *     @type string $event_type Required. Event type, e.g. farm_status or horse_status.
	 *     @type int    $object_id  Related post ID, if any.
	 *     @type string $old_status Previous status key.
	 *     @type string $new_status New status key.
	 *     @type string $message    Public message or summary.
	 *     @type array  $details    Additional structured data.
	 *     @type string $event_uuid Optional; generated when absent.
	 * }
	 * @return string|false The event UUID, or false on failure.
	 */
	public static function record( array $event ) {
		global $wpdb;

		$uuid = $event['event_uuid'] ?? wp_generate_uuid4();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom audit table.
		$ok = $wpdb->insert(
			self::table(),
			array(
				'event_uuid' => $uuid,
				'created_at' => current_time( 'mysql', true ),
				'user_id'    => get_current_user_id(),
				'event_type' => sanitize_key( $event['event_type'] ),
				'object_id'  => (int) ( $event['object_id'] ?? 0 ),
				'old_status' => sanitize_key( $event['old_status'] ?? '' ),
				'new_status' => sanitize_key( $event['new_status'] ?? '' ),
				'message'    => (string) ( $event['message'] ?? '' ),
				'details'    => wp_json_encode( $event['details'] ?? array() ),
			),
			array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		return $ok ? $uuid : false;
	}

	/**
	 * Most recent events.
	 *
	 * @param int $limit  Max rows.
	 * @param int $offset Offset.
	 * @return array<object>
	 */
	public static function recent( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset ) );
	}
}
