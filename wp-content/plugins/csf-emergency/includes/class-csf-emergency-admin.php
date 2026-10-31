<?php
/**
 * Farm Emergency dashboard: status change workflow, event log, and QR tag printing.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Status changes are a three-step flow: choose → review → confirm. The confirm form carries a
 * single-use key, so a double click, a browser resubmit, or a back-button replay cannot
 * apply (or later notify) the same change twice.
 */
class CSF_Emergency_Admin {

	const SLUG          = 'csf-emergency';
	const ACTION        = 'csf_emergency_change_status';
	const MESSAGE_LIMIT = 280;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_confirm' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 100 );
	}

	/**
	 * Menus.
	 */
	public static function menu() {
		$cap = CSF_Emergency_Capabilities::MANAGE_EMERGENCY;
		add_menu_page( __( 'Farm Emergency', 'csf-emergency' ), __( 'Farm Emergency', 'csf-emergency' ), $cap, self::SLUG, array( __CLASS__, 'render_dashboard' ), 'dashicons-warning', 3 );
		add_submenu_page( self::SLUG, __( 'Farm Status', 'csf-emergency' ), __( 'Farm Status', 'csf-emergency' ), $cap, self::SLUG, array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( self::SLUG, __( 'Event Log', 'csf-emergency' ), __( 'Event Log', 'csf-emergency' ), $cap, 'csf-emergency-log', array( __CLASS__, 'render_log' ) );
		add_submenu_page( self::SLUG, __( 'Emergency Settings', 'csf-emergency' ), __( 'Settings', 'csf-emergency' ), $cap, CSF_Emergency_Settings::PAGE, array( 'CSF_Emergency_Settings', 'render_page' ) );
		add_submenu_page( '', __( 'Print Horse Tag', 'csf-emergency' ), '', CSF_Emergency_Capabilities::MANAGE_HORSES, 'csf-emergency-tag', array( __CLASS__, 'render_tag' ) );
	}

	/**
	 * Show the active status in the admin bar so nobody forgets an emergency is live.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 */
	public static function admin_bar( $bar ) {
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_EMERGENCY ) || ! CSF_Emergency_Farm_State::is_active() ) {
			return;
		}
		$status = CSF_Emergency_Statuses::get_farm( CSF_Emergency_Farm_State::get()['status'] );
		$bar->add_node(
			array(
				'id'    => 'csf-emergency-status',
				/* translators: %s: status label */
				'title' => esc_html( sprintf( __( 'Farm status: %s', 'csf-emergency' ), $status['label'] ) ),
				'href'  => admin_url( 'admin.php?page=' . self::SLUG ),
			)
		);
	}

	/**
	 * Dashboard router: the review step is a POST back to this page.
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_EMERGENCY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage farm emergencies.', 'csf-emergency' ) );
		}

		echo '<div class="wrap csf-wrap"><h1>' . esc_html__( 'Farm Emergency Status', 'csf-emergency' ) . '</h1>';
		self::render_notices();
		self::render_mfa_warning();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in render_review().
		if ( isset( $_POST['csf_step'] ) && 'review' === $_POST['csf_step'] ) {
			self::render_review();
		} else {
			self::render_current();
			self::render_form();
		}
		echo '</div>';
	}

	/**
	 * Notices from the confirm redirect.
	 */
	private static function render_notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		if ( isset( $_GET['csf_done'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Farm status updated. The public banner, /emergency page, and horse profiles now show the new status.', 'csf-emergency' ) . '</p></div>';
		}
		if ( isset( $_GET['csf_error'] ) ) {
			$errors = array(
				'expired'   => __( 'This confirmation was already used or has expired. Nothing was changed. Check the current status below before trying again.', 'csf-emergency' ),
				'duplicate' => __( 'This exact update was already published in the last few minutes. Nothing was changed and nothing was sent again.', 'csf-emergency' ),
				'unchecked' => __( 'Tick the confirmation box to apply the change.', 'csf-emergency' ),
				'invalid'   => __( 'The status change could not be applied.', 'csf-emergency' ),
			);
			$key    = sanitize_key( wp_unslash( $_GET['csf_error'] ) );
			echo '<div class="notice notice-error"><p>' . esc_html( $errors[ $key ] ?? $errors['invalid'] ) . '</p></div>';
		}
		// phpcs:enable
	}

	/**
	 * Warn coordinators who are not protected by a second factor.
	 */
	private static function render_mfa_warning() {
		$mfa = CSF_Emergency_Capabilities::user_has_mfa( get_current_user_id() );
		if ( null === $mfa ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'No supported two-factor authentication plugin is active. Install and configure the "Two Factor" plugin for every account with emergency access.', 'csf-emergency' ) . '</p></div>';
		} elseif ( ! $mfa ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Your account does not have two-factor authentication enabled. Enable it in your profile before an emergency.', 'csf-emergency' ) . '</p></div>';
		}
	}

	/**
	 * Current status card.
	 */
	private static function render_current() {
		$state  = CSF_Emergency_Farm_State::get();
		$status = CSF_Emergency_Statuses::get_farm( $state['status'] );
		$user   = $state['updated_by'] ? get_userdata( $state['updated_by'] ) : null;

		echo '<div class="csf-current csf-sev-' . esc_attr( $status['severity'] ) . '">';
		echo '<p><strong>' . esc_html__( 'Current status:', 'csf-emergency' ) . '</strong> ' . esc_html( $status['label'] ) . '</p>';
		if ( $state['message'] ) {
			echo '<p><strong>' . esc_html__( 'Public update:', 'csf-emergency' ) . '</strong> ' . esc_html( $state['message'] ) . '</p>';
		}
		if ( $state['updated_at'] ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: 1: date/time, 2: user display name */
					__( 'Set %1$s by %2$s', 'csf-emergency' ),
					CSF_Emergency_Public::format_time( $state['updated_at'] ),
					$user ? $user->display_name : __( 'unknown user', 'csf-emergency' )
				)
			) . '</p>';
		}
		echo '<p><a href="' . esc_url( home_url( '/emergency' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View public /emergency page', 'csf-emergency' ) . '</a></p>';
		echo '</div>';

		$settings = CSF_Emergency_Settings::get();
		if ( ! $settings['farm_phone'] ) {
			echo '<div class="notice notice-error inline"><p>' . wp_kses_post(
				sprintf(
					/* translators: %s: settings URL */
					__( 'No emergency phone number is configured, so emergency pages have no CALL FARM button. <a href="%s">Add contact numbers</a>.', 'csf-emergency' ),
					esc_url( admin_url( 'admin.php?page=' . CSF_Emergency_Settings::PAGE ) )
				)
			) . '</p></div>';
		}
	}

	/**
	 * Step 1: choose status and write the public update.
	 *
	 * @param array $values Prefill values.
	 */
	private static function render_form( array $values = array() ) {
		$state  = CSF_Emergency_Farm_State::get();
		$values = wp_parse_args(
			$values,
			array(
				'status'      => $state['status'],
				'message'     => '',
				'public_info' => $state['public_info'],
				'next_update' => '',
			)
		);

		echo '<h2>' . esc_html__( 'Change farm status', 'csf-emergency' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">';
		wp_nonce_field( 'csf_review', 'csf_review_nonce' );
		echo '<input type="hidden" name="csf_step" value="review">';

		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Status', 'csf-emergency' ) . '</legend><div class="csf-status-options">';
		foreach ( CSF_Emergency_Statuses::farm() as $key => $status ) {
			printf(
				'<label><input type="radio" name="csf_status" value="%1$s" %2$s required> <span>%3$s</span><span class="description">%4$s</span></label>',
				esc_attr( $key ),
				checked( $values['status'], $key, false ),
				esc_html( $status['label'] ),
				esc_html( $status['description'] )
			);
		}
		echo '</div></fieldset>';

		echo '<p><label for="csf-message"><strong>' . esc_html__( 'Short public update', 'csf-emergency' ) . '</strong></label><br>';
		echo '<textarea id="csf-message" name="csf_message" rows="3" class="large-text" maxlength="' . esc_attr( self::MESSAGE_LIMIT ) . '">' . esc_textarea( $values['message'] ) . '</textarea>';
		echo '<span class="description">' . esc_html__( 'Shown in the sitewide banner. Do not include evacuation destinations or private details.', 'csf-emergency' ) . '</span></p>';

		echo '<p><label for="csf-public-info"><strong>' . esc_html__( 'Approved public evacuation information (optional)', 'csf-emergency' ) . '</strong></label><br>';
		echo '<textarea id="csf-public-info" name="csf_public_info" rows="4" class="large-text">' . esc_textarea( $values['public_info'] ) . '</textarea>';
		echo '<span class="description">' . esc_html__( 'Shown on /emergency. Only information approved for the public.', 'csf-emergency' ) . '</span></p>';

		echo '<p><label for="csf-next-update"><strong>' . esc_html__( 'Next update expected (optional)', 'csf-emergency' ) . '</strong></label><br>';
		echo '<input type="text" id="csf-next-update" name="csf_next_update" class="regular-text" value="' . esc_attr( $values['next_update'] ) . '" placeholder="' . esc_attr__( 'e.g. 6:00 PM', 'csf-emergency' ) . '"></p>';

		submit_button( __( 'Review change', 'csf-emergency' ), 'primary csf-big-button' );
		echo '</form>';
	}

	/**
	 * Step 2: review what will happen, then confirm.
	 */
	private static function render_review() {
		check_admin_referer( 'csf_review', 'csf_review_nonce' );

		$input = self::read_input( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		if ( is_wp_error( $input ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $input->get_error_message() ) . '</p></div>';
			self::render_form();
			return;
		}

		$current = CSF_Emergency_Farm_State::get();
		$from    = CSF_Emergency_Statuses::get_farm( $current['status'] );
		$to      = CSF_Emergency_Statuses::get_farm( $input['status'] );
		$key     = wp_generate_password( 32, false );

		// Single-use confirmation key, bound to this user and this exact change.
		set_transient( 'csf_confirm_' . get_current_user_id(), array( 'key' => $key ) + $input, 15 * MINUTE_IN_SECONDS );

		echo '<h2>' . esc_html__( 'Review before publishing', 'csf-emergency' ) . '</h2><div class="csf-review"><dl>';
		echo '<dt>' . esc_html__( 'Status change', 'csf-emergency' ) . '</dt><dd>' . esc_html( $from['label'] . ' → ' . $to['label'] ) . '</dd>';
		echo '<dt>' . esc_html__( 'Public update', 'csf-emergency' ) . '</dt><dd>' . ( $input['message'] ? esc_html( $input['message'] ) : '<em>' . esc_html__( '(none)', 'csf-emergency' ) . '</em>' ) . '</dd>';
		if ( $input['public_info'] ) {
			echo '<dt>' . esc_html__( 'Public evacuation information', 'csf-emergency' ) . '</dt><dd>' . nl2br( esc_html( $input['public_info'] ) ) . '</dd>';
		}

		echo '<dt>' . esc_html__( 'Sitewide banner preview', 'csf-emergency' ) . '</dt><dd>';
		if ( 'normal' === $input['status'] ) {
			esc_html_e( 'The banner will be removed.', 'csf-emergency' );
		} else {
			echo '<div class="csf-banner csf-sev-' . esc_attr( $to['severity'] ) . '" style="padding:.75rem 1rem;color:#fff"><strong>' . esc_html( $to['headline'] ) . '</strong>';
			if ( $input['message'] ) {
				echo '<br>' . esc_html( $input['message'] );
			}
			echo '</div>';
		}
		echo '</dd>';

		echo '<dt>' . esc_html__( 'Notifications', 'csf-emergency' ) . '</dt><dd>';
		/**
		 * Filters the notification summary shown before confirming. A notification module
		 * should return a human-readable list of channels and recipient counts.
		 *
		 * @param string $summary Default summary.
		 * @param array  $input   Proposed change.
		 */
		echo esc_html( apply_filters( 'csf_emergency_notification_summary', __( 'None. The notification system is not installed yet — no email, SMS, or push alerts will be sent. Contact people directly.', 'csf-emergency' ), $input ) );
		echo '</dd>';
		if ( CSF_Emergency_Farm_State::is_duplicate( $input['status'], $input['message'] ) ) {
			echo '<dt>' . esc_html__( 'Warning', 'csf-emergency' ) . '</dt><dd><strong>' . esc_html__( 'This matches the update published in the last few minutes and will be rejected as a duplicate.', 'csf-emergency' ) . '</strong></dd>';
		}
		echo '</dl>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		echo '<input type="hidden" name="csf_confirm_key" value="' . esc_attr( $key ) . '">';
		echo '<p><label><input type="checkbox" name="csf_confirmed" value="1" required> ' . esc_html__( 'I have reviewed this update and approve publishing it.', 'csf-emergency' ) . '</label></p>';
		$class = in_array( $to['severity'], array( 'critical', 'warning' ), true ) ? 'primary csf-big-button csf-danger' : 'primary csf-big-button';
		submit_button( __( 'Confirm and publish', 'csf-emergency' ), $class, 'submit', false );
		echo ' <a class="button button-large" href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Cancel', 'csf-emergency' ) . '</a>';
		echo '</form></div>';
	}

	/**
	 * Validate and sanitize the change request.
	 *
	 * @param array $source Raw request array.
	 * @return array|WP_Error
	 */
	private static function read_input( array $source ) {
		$source = wp_unslash( $source );
		$status = sanitize_key( $source['csf_status'] ?? '' );
		if ( ! array_key_exists( $status, CSF_Emergency_Statuses::farm() ) ) {
			return new WP_Error( 'csf_status', __( 'Choose a status.', 'csf-emergency' ) );
		}
		$message = sanitize_textarea_field( $source['csf_message'] ?? '' );
		if ( function_exists( 'mb_substr' ) ) {
			$message = mb_substr( $message, 0, self::MESSAGE_LIMIT );
		}
		return array(
			'status'      => $status,
			'message'     => $message,
			'public_info' => 'normal' === $status ? '' : sanitize_textarea_field( $source['csf_public_info'] ?? '' ),
			'next_update' => sanitize_text_field( $source['csf_next_update'] ?? '' ),
		);
	}

	/**
	 * Step 3: apply the reviewed change. Uses the stored, reviewed values — never fresh POST
	 * values — so what was previewed is exactly what is published.
	 */
	public static function handle_confirm() {
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_EMERGENCY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage farm emergencies.', 'csf-emergency' ), 403 );
		}
		check_admin_referer( self::ACTION );

		$back = admin_url( 'admin.php?page=' . self::SLUG );

		if ( empty( $_POST['csf_confirmed'] ) ) {
			wp_safe_redirect( add_query_arg( 'csf_error', 'unchecked', $back ) );
			exit;
		}

		$transient = 'csf_confirm_' . get_current_user_id();
		$pending   = get_transient( $transient );
		$key       = isset( $_POST['csf_confirm_key'] ) ? sanitize_text_field( wp_unslash( $_POST['csf_confirm_key'] ) ) : '';

		if ( ! is_array( $pending ) || ! hash_equals( (string) $pending['key'], $key ) ) {
			wp_safe_redirect( add_query_arg( 'csf_error', 'expired', $back ) );
			exit;
		}

		// Consume before acting to reject sequential replays; concurrent claims still need atomic persistence.
		delete_transient( $transient );

		$result = CSF_Emergency_Farm_State::change( $pending['status'], $pending['message'], $pending['public_info'], $pending['next_update'] );
		if ( is_wp_error( $result ) ) {
			$code = 'csf_duplicate' === $result->get_error_code() ? 'duplicate' : 'invalid';
			wp_safe_redirect( add_query_arg( 'csf_error', $code, $back ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'csf_done', '1', $back ) );
		exit;
	}

	/**
	 * Event log.
	 */
	public static function render_log() {
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_EMERGENCY ) ) {
			wp_die( esc_html__( 'You do not have permission to view the emergency log.', 'csf-emergency' ) );
		}
		$farm  = CSF_Emergency_Statuses::farm();
		$horse = CSF_Emergency_Statuses::horse();
		$rows  = CSF_Emergency_Event_Log::recent( 200 );

		echo '<div class="wrap"><h1>' . esc_html__( 'Emergency Event Log', 'csf-emergency' ) . '</h1>';
		echo '<p>' . esc_html__( 'Most recent 200 events. Times are shown in the site timezone.', 'csf-emergency' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Time', 'csf-emergency' ) . '</th><th>' . esc_html__( 'User', 'csf-emergency' ) . '</th><th>' . esc_html__( 'Event', 'csf-emergency' ) . '</th><th>' . esc_html__( 'Change', 'csf-emergency' ) . '</th><th>' . esc_html__( 'Message', 'csf-emergency' ) . '</th><th>' . esc_html__( 'Event ID', 'csf-emergency' ) . '</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No events recorded yet.', 'csf-emergency' ) . '</td></tr>';
		}
		foreach ( $rows as $row ) {
			$user   = $row->user_id ? get_userdata( (int) $row->user_id ) : null;
			$labels = 'farm_status' === $row->event_type ? wp_list_pluck( $farm, 'label' ) : $horse;
			$old    = $labels[ $row->old_status ] ?? $row->old_status;
			$new    = $labels[ $row->new_status ] ?? $row->new_status;
			echo '<tr>';
			echo '<td>' . esc_html( CSF_Emergency_Public::format_time( strtotime( $row->created_at . ' UTC' ) ) ) . '</td>';
			echo '<td>' . esc_html( $user ? $user->display_name : '—' ) . '</td>';
			echo '<td>' . esc_html( 'farm_status' === $row->event_type ? __( 'Farm status', 'csf-emergency' ) : __( 'Horse status', 'csf-emergency' ) ) . '</td>';
			echo '<td>' . esc_html( ( $old ? $old : '—' ) . ' → ' . $new ) . '</td>';
			echo '<td>' . esc_html( $row->message ) . '</td>';
			echo '<td><code>' . esc_html( substr( $row->event_uuid, 0, 8 ) ) . '</code></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Printable QR tag for one horse.
	 */
	public static function render_tag() {
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_HORSES ) ) {
			wp_die( esc_html__( 'You do not have permission to print horse tags.', 'csf-emergency' ) );
		}
		$post_id = isset( $_GET['horse'] ) ? absint( $_GET['horse'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view.
		$url     = $post_id ? CSF_Emergency_Horses::public_url( $post_id ) : '';
		if ( ! $url || get_post_type( $post_id ) !== CSF_Emergency_Horses::POST_TYPE ) {
			wp_die( esc_html__( 'Horse profile not found or not yet saved.', 'csf-emergency' ) );
		}

		$settings = CSF_Emergency_Settings::get();
		$name     = get_the_title( $post_id );
		$short    = preg_replace( '#^https?://(www\.)?#', '', $url );

		wp_enqueue_script( 'csf-qrcode', CSF_EMERGENCY_URL . 'assets/vendor/qrcode.js', array(), '1.4.4', true );
		wp_enqueue_script( 'csf-print-tag', CSF_EMERGENCY_URL . 'assets/print-tag.js', array( 'csf-qrcode' ), CSF_EMERGENCY_VERSION, true );

		echo '<div class="wrap"><h1 class="csf-no-print">' . esc_html__( 'Print Horse Tag', 'csf-emergency' ) . '</h1>';
		echo '<div class="csf-no-print">';
		if ( ! CSF_Emergency_Horses::is_public( $post_id ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'This profile is not approved or not published. The tag will work, but scanning it will show only the farm contact page until the profile is approved.', 'csf-emergency' ) . '</p></div>';
		}
		if ( ! $settings['farm_phone'] ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'No farm emergency phone is configured. Add one in Settings before printing — the printed number is the fallback when the QR code cannot be scanned.', 'csf-emergency' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Print at 100% scale. Before attaching, scan the printed tag with at least one iPhone and one Android phone, in daylight and low light.', 'csf-emergency' ) . '</p>';
		echo '<p><button type="button" class="button button-primary" onclick="window.print()">' . esc_html__( 'Print', 'csf-emergency' ) . '</button> ';
		echo '<a class="button" href="' . esc_url( $url . '/print' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Print full emergency profile', 'csf-emergency' ) . '</a></p></div>';

		echo '<div class="csf-tag-sheet"><div class="csf-tag">';
		/* translators: %s: horse name */
		echo '<div class="csf-tag__qr" data-csf-qr="' . esc_attr( $url ) . '" data-csf-alt="' . esc_attr( sprintf( __( 'QR code for %s', 'csf-emergency' ), $name ) ) . '"></div>';
		echo '<p class="csf-tag__name">' . esc_html( $name ) . '</p>';
		if ( $settings['farm_phone'] ) {
			echo '<p class="csf-tag__phone">' . esc_html( $settings['farm_phone'] ) . '</p>';
		}
		echo '<p class="csf-tag__url">' . esc_html( $short ) . '</p>';
		echo '<p class="csf-tag__farm">' . esc_html( $settings['farm_name'] ) . '</p>';
		echo '</div></div></div>';
	}
}
