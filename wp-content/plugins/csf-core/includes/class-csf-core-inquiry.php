<?php
/**
 * General inquiries: deliberately disabled until the owner verifies mail delivery.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Uses the site's configured wp_mail transport, not visitor-controlled recipients/headers.
 * No attachments, automatic replies, submission records, or message logging.
 */
class CSF_Core_Inquiry {

	const OPTION      = 'csf_core_inquiry';
	const ACTION      = 'csf_inquiry';
	const PREFIX      = 'csf_inquiry_limit_';
	const RECEIPT_TTL = 5 * MINUTE_IN_SECONDS;

	/** Register the optional form and its maintenance/settings hooks. */
	public static function init() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'csf_inquiry_cleanup', array( __CLASS__, 'cleanup' ) );
		add_action( 'template_redirect', array( __CLASS__, 'confirmation_headers' ) );
	}

	/**
	 * Explicit opt-in; installing an update never starts accepting inquiries.
	 *
	 * @return array
	 */
	public static function settings() {
		return wp_parse_args(
			(array) get_option( self::OPTION, array() ),
			array(
				'recipient'         => '',
				'enabled'           => 0,
				'delivery_verified' => 0,
			)
		);
	}

	/**
	 * Validate settings from the capability- and nonce-protected WordPress Settings API.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input     = is_array( $input ) ? $input : array();
		$recipient = is_string( $input['recipient'] ?? null ) ? trim( $input['recipient'] ) : '';
		$old       = self::settings();
		$verified  = ! empty( $input['delivery_verified'] ) && $recipient === $old['recipient'];
		if ( ! is_email( $recipient ) || preg_match( '/[\r\n]/', $recipient ) ) {
			$recipient = '';
			$verified  = false;
			add_settings_error( self::OPTION, 'recipient', __( 'Enter one valid inquiry recipient. The form remains disabled.', 'csf-core' ) );
		}
		return array(
			'recipient'         => $recipient,
			'delivery_verified' => (int) $verified,
			'enabled'           => (int) ( $verified && ! empty( $input['enabled'] ) ),
		);
	}

	/** Register the option. */
	public static function register_setting() {
		register_setting(
			self::OPTION,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/** Add settings under the existing plugin, without another form plugin dependency. */
	public static function menu() {
		add_options_page( __( 'Farm Inquiries', 'csf-core' ), __( 'Farm Inquiries', 'csf-core' ), 'manage_options', 'csf-inquiries', array( __CLASS__, 'render_settings' ) );
	}

	/** Render owner settings; a new recipient always needs a new delivery check. */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::settings();
		?>
		<div class="wrap"><h1><?php esc_html_e( 'Farm Inquiries', 'csf-core' ); ?></h1>
		<p><?php esc_html_e( 'Save the recipient first. Configure your mail service and verify an actual message arrives in that inbox before confirming delivery and enabling the form. Changing the recipient clears verification.', 'csf-core' ); ?></p>
		<?php settings_errors( self::OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( self::OPTION ); ?>
			<p><label for="csf-inquiry-recipient"><?php esc_html_e( 'Inquiry recipient', 'csf-core' ); ?></label><br>
			<input id="csf-inquiry-recipient" type="email" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[recipient]" value="<?php echo esc_attr( $s['recipient'] ); ?>" required></p>
			<p><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[delivery_verified]" value="1" <?php checked( 1, (int) $s['delivery_verified'] ); ?>> <?php esc_html_e( 'Delivery to this saved recipient has been verified in the receiving inbox.', 'csf-core' ); ?></label></p>
			<p><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( 1, (int) $s['enabled'] ); ?>> <?php esc_html_e( 'Enable the inquiry form.', 'csf-core' ); ?></label></p>
			<?php submit_button(); ?>
		</form></div>
		<?php
	}

	/**
	 * Whether submissions may be accepted.
	 *
	 * @return bool
	 */
	private static function enabled() {
		$s = self::settings();
		return ! empty( $s['enabled'] ) && ! empty( $s['delivery_verified'] ) && is_string( $s['recipient'] ) && is_email( $s['recipient'] ) && ! preg_match( '/[\r\n]/', $s['recipient'] );
	}

	/**
	 * A signed, short-lived form challenge. Public nonces alone are not spam protection.
	 *
	 * @return string
	 */
	public static function challenge() {
		$value = time() . ':' . wp_generate_uuid4();
		return $value . ':' . hash_hmac( 'sha256', $value, wp_salt( 'nonce' ) );
	}

	/**
	 * Keep receipt responses out of shared caches and outgoing referrers.
	 * Application headers supplement the required page/CDN cache exclusions.
	 */
	public static function confirmation_headers() {
		if ( is_page( 'contact' ) && isset( $_GET['csf_inquiry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- cache policy only, no data read or write.
			nocache_headers();
			header( 'Cache-Control: no-store, private, max-age=0' );
			header( 'Referrer-Policy: no-referrer' );
		}
	}

	/**
	 * Separate confirmation proofs from the publicly issued anti-spam challenge, and
	 * bind them to this site's Contact URL even when a clone shares WordPress salts.
	 *
	 * @param string $value Timestamp and random UUID.
	 * @return string
	 */
	private static function receipt_signature( $value ) {
		return hash_hmac( 'sha256', 'csf-inquiry-confirmation-v1|' . home_url( '/contact/' ) . '|' . $value, wp_salt( 'nonce' ) );
	}

	/**
	 * Issue only after a successful POST's transport acceptance; never from the form renderer.
	 *
	 * @return string
	 */
	private static function success_receipt() {
		$value = time() . ':' . wp_generate_uuid4();
		return $value . ':' . self::receipt_signature( $value );
	}

	/**
	 * Acknowledge a valid receipt once. Only a digest and expiry are retained; no PII.
	 * Competing/replayed claims use identical values, so only one add_option can succeed.
	 *
	 * @param mixed $input Untrusted URL parameter.
	 * @return bool
	 */
	private static function consume_receipt( $input ) {
		if ( ! is_string( $input ) || strlen( $input ) > 128 ) {
			return false;
		}
		$receipt = wp_unslash( $input );
		$parts   = explode( ':', $receipt );
		if ( 3 !== count( $parts ) || ! ctype_digit( $parts[0] ) || ! wp_is_uuid( $parts[1], 4 ) || strlen( $parts[2] ) !== 64 ) {
			return false;
		}
		$issued = (int) $parts[0];
		if ( $issued > time() || time() - $issued >= self::RECEIPT_TTL || ! hash_equals( self::receipt_signature( $parts[0] . ':' . $parts[1] ), $parts[2] ) ) {
			return false;
		}
		return add_option( self::PREFIX . 'receipt_' . hash( 'sha256', $receipt ), (string) ( $issued + self::RECEIPT_TTL ), '', false );
	}

	/**
	 * Render a native, keyboard-accessible form. No external CAPTCHA script or tracking.
	 *
	 * @return string
	 */
	public static function render() {
		if ( ! self::enabled() ) {
			return current_user_can( 'manage_options' ) ? '<p class="csf-admin-hint">' . esc_html__( 'Inquiry form disabled. Verify delivery and enable it in Settings → Farm Inquiries.', 'csf-core' ) . '</p>' : '';
		}
		$id   = wp_unique_id( 'csf-inquiry-' );
		$html = '<div class="csf-inquiry" id="csf-inquiry">';
		if ( self::consume_receipt( $_GET['csf_inquiry'] ?? null ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- dedicated signed, expiring receipt validation; no submission data is read.
			$html .= '<p role="status">' . esc_html__( 'Your inquiry was accepted for sending. If you need to follow up, please use the contact details on this page.', 'csf-core' ) . '</p>';
		}
		$html .= '<h2>' . esc_html__( 'Send an inquiry', 'csf-core' ) . '</h2>';
		$html .= '<p id="' . esc_attr( $id . '-help' ) . '">' . esc_html__( 'For general questions only. Please do not include children’s names, medical records, payment details, or other sensitive information. For a life-threatening emergency, call 911.', 'csf-core' ) . '</p>';
		$html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" aria-describedby="' . esc_attr( $id . '-help' ) . '">';
		$html .= '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		$html .= wp_nonce_field( self::ACTION, '_csf_nonce', false, false );
		$html .= '<input type="hidden" name="challenge" value="' . esc_attr( self::challenge() ) . '">';
		$html .= '<p class="csf-inquiry__trap" aria-hidden="true"><label for="' . esc_attr( $id . '-website' ) . '">' . esc_html__( 'Leave this field empty', 'csf-core' ) . '</label><input type="text" id="' . esc_attr( $id . '-website' ) . '" name="website" tabindex="-1" autocomplete="off"></p>';
		foreach ( array(
			'name'  => array( __( 'Your name', 'csf-core' ), 'text', 'name', 120 ),
			'email' => array( __( 'Your email', 'csf-core' ), 'email', 'email', 254 ),
		) as $key => $field ) {
			$html .= '<p><label for="' . esc_attr( $id . '-' . $key ) . '">' . esc_html( $field[0] ) . ' (' . esc_html__( 'required', 'csf-core' ) . ')</label><input id="' . esc_attr( $id . '-' . $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field[1] ) . '" autocomplete="' . esc_attr( $field[2] ) . '" maxlength="' . (int) $field[3] . '" required></p>';
		}
		$html .= '<p><label for="' . esc_attr( $id . '-message' ) . '">' . esc_html__( 'Your question (required)', 'csf-core' ) . '</label><textarea id="' . esc_attr( $id . '-message' ) . '" name="message" rows="6" maxlength="4000" required></textarea></p>';
		$html .= '<p><label><input type="checkbox" name="consent" value="1" required> ' . esc_html__( 'I agree that Corner Stone Farm may reply to this inquiry by email.', 'csf-core' ) . '</label></p>';
		$html .= '<button type="submit" class="csf-book-button csf-book-button--primary">' . esc_html__( 'Send inquiry', 'csf-core' ) . '</button></form></div>';
		return $html;
	}

	/**
	 * Validate, limit and submit. The transport's true return means accepted, not delivered.
	 *
	 * @param array  $input       Unslashed POST fields.
	 * @param string $remote_addr Web server REMOTE_ADDR; never trust forwarded headers here.
	 * @return true|WP_Error
	 */
	public static function submit( array $input, $remote_addr ) {
		if ( ! self::enabled() ) {
			return new WP_Error( 'unavailable', __( 'The inquiry form is unavailable. Please use the contact details on the Contact page.', 'csf-core' ) );
		}
		foreach ( array( '_csf_nonce', 'challenge', 'website', 'name', 'email', 'message', 'consent' ) as $field ) {
			if ( ! isset( $input[ $field ] ) || ! is_string( $input[ $field ] ) ) {
				return new WP_Error( 'invalid', __( 'Please complete all required inquiry fields.', 'csf-core' ) );
			}
		}
		$parts = explode( ':', $input['challenge'] );
		if ( ! wp_verify_nonce( $input['_csf_nonce'], self::ACTION ) || 3 !== count( $parts ) || ! ctype_digit( $parts[0] ) || ! wp_is_uuid( $parts[1], 4 ) || ! hash_equals( hash_hmac( 'sha256', $parts[0] . ':' . $parts[1], wp_salt( 'nonce' ) ), $parts[2] ) || time() - (int) $parts[0] < 3 || time() - (int) $parts[0] > 2 * HOUR_IN_SECONDS || '' !== $input['website'] ) {
			return new WP_Error( 'invalid', __( 'The form could not be verified. Please return to Contact, refresh the page, and try again.', 'csf-core' ) );
		}
		$name    = sanitize_text_field( $input['name'] );
		$email   = trim( $input['email'] );
		$message = sanitize_textarea_field( $input['message'] );
		if ( '' === $name || strlen( $input['name'] ) > 480 || mb_strlen( $name ) > 120 || ! is_email( $email ) || strlen( $email ) > 254 || preg_match( '/[\r\n]/', $email ) || '' === $message || strlen( $input['message'] ) > 16000 || mb_strlen( $message ) > 4000 || '1' !== $input['consent'] ) {
			return new WP_Error( 'invalid', __( 'Check your name, email, message (up to 4,000 characters), and permission to reply.', 'csf-core' ) );
		}
		$token_key = self::PREFIX . wp_hash( 'token:' . $input['challenge'] );
		if ( ! filter_var( $remote_addr, FILTER_VALIDATE_IP ) || ! self::claim( 'ip:' . $remote_addr, 3 ) || ! self::claim( 'site', 20 ) || ! add_option( $token_key, (string) ( (int) $parts[0] + 2 * HOUR_IN_SECONDS ), '', false ) ) {
			return new WP_Error( 'limited', __( 'This form has reached its sending limit or has already been submitted. Please use the contact details on the Contact page.', 'csf-core' ) );
		}
		if ( ! wp_next_scheduled( 'csf_inquiry_cleanup' ) ) {
			wp_schedule_single_event( time() + DAY_IN_SECONDS, 'csf_inquiry_cleanup' );
		}
		$body = "General website inquiry\n\nName: " . $name . "\nEmail: " . $email . "\n\n" . $message . "\n\nThe sender agreed to an email reply. This form is not an emergency channel.";
		$sent = wp_mail( self::settings()['recipient'], __( 'Corner Stone Farm website inquiry', 'csf-core' ), $body, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email ) );
		return $sent ? true : new WP_Error( 'mail_failed', __( 'Your inquiry could not be submitted. Please use the contact details on the Contact page.', 'csf-core' ) );
	}

	/**
	 * Fixed-window database claims: concurrent requests cannot reuse an occupied slot.
	 * The identical value on every competing add_option() makes its duplicate upsert a no-op.
	 * Only salted hashes, bucket expiry and no-autoload flags are stored, never raw IPs.
	 *
	 * @param string $scope  Rate scope or signed form token.
	 * @param int    $limit  Slots per window.
	 * @param int    $window Window length in seconds.
	 * @return bool
	 */
	private static function claim( $scope, $limit, $window = HOUR_IN_SECONDS ) {
		$bucket = (int) floor( time() / $window );
		for ( $slot = 0; $slot < $limit; ++$slot ) {
			$key = self::PREFIX . wp_hash( $scope . ':' . $window . ':' . $bucket . ':' . $slot );
			if ( add_option( $key, (string) ( ( $bucket + 1 ) * $window ), '', false ) ) {
				return true;
			}
		}
		return false;
	}

	/** Delete expired rate keys only, in bounded batches; no submission content is retained. */
	public static function cleanup() {
		global $wpdb;
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d LIMIT 1000", $wpdb->esc_like( self::PREFIX ) . '%', time() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- bounded maintenance on private no-autoload options; delete_option handles cache invalidation.
		foreach ( $keys as $key ) {
			delete_option( $key );
		}
		if ( count( $keys ) === 1000 && ! wp_next_scheduled( 'csf_inquiry_cleanup' ) ) {
			wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'csf_inquiry_cleanup' );
		}
	}

	/** POST endpoint; errors never redirect with personal data or transport diagnostics. */
	public static function handle() {
		if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || absint( $_SERVER['CONTENT_LENGTH'] ?? 0 ) > 20000 ) {
			wp_die( esc_html__( 'Invalid inquiry request.', 'csf-core' ), '', array( 'response' => 400 ) );
		}
		$result = self::submit( wp_unslash( $_POST ), $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- submit validates nonce and every field before use.
		nocache_headers();
		if ( is_wp_error( $result ) ) {
			$status = 'limited' === $result->get_error_code() ? 429 : ( 'invalid' === $result->get_error_code() ? 400 : 503 );
			wp_die(
				esc_html( $result->get_error_message() ),
				esc_html__( 'Inquiry not submitted', 'csf-core' ),
				array(
					'response'  => absint( $status ),
					'back_link' => true,
				)
			);
		}
		$confirmation_url = add_query_arg( 'csf_inquiry', self::success_receipt(), home_url( '/contact/' ) );
		wp_safe_redirect( $confirmation_url . '#csf-inquiry', 303 );
		exit;
	}
}
