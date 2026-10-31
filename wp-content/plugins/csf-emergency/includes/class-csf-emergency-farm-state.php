<?php
/**
 * Current farm emergency state.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and changes the farm status. All changes go through change(), which logs the
 * event, fires the extension hook exactly once per event, and purges page caches.
 */
class CSF_Emergency_Farm_State {

	const OPTION = 'csf_emergency_state';

	/**
	 * Seconds during which an identical status + message is treated as a duplicate.
	 */
	const DUPLICATE_WINDOW = 300;

	/**
	 * Current state.
	 *
	 * @return array{status:string,message:string,public_info:string,next_update:string,updated_at:int,updated_by:int,event_uuid:string}
	 */
	public static function get() {
		$state = wp_parse_args(
			(array) get_option( self::OPTION, array() ),
			array(
				'status'      => 'normal',
				'message'     => '',
				'public_info' => '',
				'next_update' => '',
				'updated_at'  => 0,
				'updated_by'  => 0,
				'event_uuid'  => '',
			)
		);
		if ( ! array_key_exists( $state['status'], CSF_Emergency_Statuses::farm() ) ) {
			$state['status'] = 'normal';
		}
		return $state;
	}

	/**
	 * Whether a non-normal status is active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return 'normal' !== self::get()['status'];
	}

	/**
	 * Whether the proposed change duplicates the current state within the duplicate window.
	 *
	 * @param string $status  Proposed status.
	 * @param string $message Proposed message.
	 * @return bool
	 */
	public static function is_duplicate( $status, $message ) {
		$current = self::get();
		return $current['status'] === $status
			&& trim( $current['message'] ) === trim( $message )
			&& ( time() - (int) $current['updated_at'] ) < self::DUPLICATE_WINDOW;
	}

	/**
	 * Apply a status change.
	 *
	 * @param string $status      New status key.
	 * @param string $message     Short public update.
	 * @param string $public_info Approved public evacuation information.
	 * @param string $next_update Expected time of next update (free text).
	 * @return string|WP_Error Event UUID on success.
	 */
	public static function change( $status, $message, $public_info = '', $next_update = '' ) {
		if ( ! array_key_exists( $status, CSF_Emergency_Statuses::farm() ) ) {
			return new WP_Error( 'csf_bad_status', __( 'Unknown status.', 'csf-emergency' ) );
		}
		if ( self::is_duplicate( $status, $message ) ) {
			return new WP_Error( 'csf_duplicate', __( 'This exact update was already published in the last few minutes. No change was made and nothing was sent again.', 'csf-emergency' ) );
		}

		$previous = self::get();
		$uuid     = wp_generate_uuid4();
		$state    = array(
			'status'      => $status,
			'message'     => $message,
			'public_info' => $public_info,
			'next_update' => $next_update,
			'updated_at'  => time(),
			'updated_by'  => get_current_user_id(),
			'event_uuid'  => $uuid,
		);

		if ( ! update_option( self::OPTION, $state, true ) ) {
			return new WP_Error( 'csf_state_write_failed', __( 'The farm status could not be saved. Check the current status before trying again.', 'csf-emergency' ) );
		}

		CSF_Emergency_Event_Log::record(
			array(
				'event_uuid' => $uuid,
				'event_type' => 'farm_status',
				'old_status' => $previous['status'],
				'new_status' => $status,
				'message'    => $message,
				'details'    => array(
					'public_info' => $public_info,
					'next_update' => $next_update,
				),
			)
		);

		/**
		 * Fires once per confirmed farm status change.
		 *
		 * Notification modules hook here. They must use $uuid as their idempotency key so
		 * that a retried request can never send the same alert twice.
		 *
		 * @param string $uuid     Event UUID.
		 * @param array  $state    New state.
		 * @param array  $previous Previous state.
		 */
		do_action( 'csf_emergency_status_changed', $uuid, $state, $previous );

		self::purge_page_caches();

		return $uuid;
	}

	/**
	 * Ask common page-cache plugins to purge so the banner appears immediately.
	 */
	public static function purge_page_caches() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		do_action( 'litespeed_purge_all' );
		do_action( 'csf_emergency_purge_caches' );
	}
}
