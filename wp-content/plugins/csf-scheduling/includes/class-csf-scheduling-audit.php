<?php
/** Append-only application audit; no notes, names or policy bodies in events.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

// Authoritative transactional data must bypass WordPress object caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

/** Transaction participants. The caller must roll back on failure. */
class CSF_Scheduling_Audit {
	/** UTC wall clock with microseconds.
	 *
	 * @return string
	 */
	public static function now() {
		return ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d H:i:s.u' );
	}

	/** UUID validator.
	 *
	 * @param mixed $uuid UUID.
	 * @return bool
	 */
	public static function uuid( $uuid ) {
		return is_string( $uuid ) && (bool) preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $uuid );
	}

	/** Insert only, in the caller's transaction.
	 *
	 * @param string $command Command UUID.
	 * @param string $action Fixed action code.
	 * @param string $entity Entity UUID.
	 * @param int    $old Old revision.
	 * @param int    $next_revision New revision.
	 * @param string $zone Optional validated facility zone.
	 * @return string|WP_Error
	 */
	public static function append( $command, $action, $entity, $old, $next_revision, $zone = null ) {
		global $wpdb;
		if ( ! self::uuid( $command ) || ! self::uuid( $entity ) || ! in_array( $action, array_merge( array( 'schema_installed', 'policy_replaced', 'staff_provisioned', 'resource_created' ), CSF_Scheduling_Calendar::COMMANDS, CSF_Scheduling_Permissions::COMMANDS, CSF_Scheduling_Identity::COMMANDS ), true ) || ( null !== $zone && ! in_array( $zone, timezone_identifiers_list(), true ) ) ) {
			return new WP_Error( 'audit_invalid', 'Invalid audit metadata.' );
		}
		$uuid  = wp_generate_uuid4();
		$utc   = self::now();
		$local = $zone ? ( new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( $zone ) )->format( 'Y-m-d H:i:s.u' ) : null;
		$ok    = $wpdb->insert(
			CSF_Scheduling_Schema::table( 'audit' ),
			array(
				'uuid'              => $uuid,
				'command_uuid'      => $command,
				'actor_id'          => get_current_user_id(),
				'action'            => $action,
				'entity_uuid'       => $entity,
				'old_revision'      => $old,
				'new_revision'      => $next_revision,
				'occurred_at_utc'   => $utc,
				'occurred_at_local' => $local,
				'timezone'          => $zone,
			)
		);
		return 1 === $ok ? $uuid : new WP_Error( 'audit_failed', 'Audit insert failed.' );
	}
}
