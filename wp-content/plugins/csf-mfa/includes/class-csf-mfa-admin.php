<?php
/** Privileged WordPress access needs a current second-factor session.
 *
 * @package CSF_MFA
 */

defined( 'ABSPATH' ) || exit;
/** Separate, explicitly enabled WordPress administration policy. */
final class CSF_MFA_Admin {
	/** Exact self-service REST user after native authentication.
	 *
	 * @var int
	 */
	private static $enrollment_user = 0;
	/** Register policy without enabling it. */
	public static function hooks() {
		add_filter( 'map_meta_cap', array( __CLASS__, 'caps' ), PHP_INT_MAX, 4 );
		add_action( 'admin_init', array( __CLASS__, 'gate' ), -100 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'rest' ), 100, 3 );
		add_filter( 'wp_is_application_passwords_available_for_user', array( __CLASS__, 'application_passwords' ), PHP_INT_MAX, 2 );
		add_filter( 'wp_is_application_passwords_available', array( __CLASS__, 'application_passwords' ), PHP_INT_MAX );
		add_filter( 'authenticate', array( __CLASS__, 'authenticate' ), PHP_INT_MAX );
		add_filter( 'auto_update_plugin', array( __CLASS__, 'updates' ), PHP_INT_MAX, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}
	/** Owner-reviewed switch, independent of installation.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return defined( 'CSF_MFA_ENFORCE_ADMIN' ) && true === CSF_MFA_ENFORCE_ADMIN;
	}
	/** Trusted shell recovery is not a web/session verifier.
	 *
	 * @return bool
	 */
	private static function shell() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}
	/** Current request may use only its own approved web proof.
	 *
	 * @return bool
	 */
	private static function verified() {
		try {
			return CSF_MFA_Session::web_verified( get_current_user_id() );
		} catch ( Throwable $error ) {
			return false;
		}
	}
	/** Exact self-service provider REST enrollment, never general user editing.
	 *
	 * @return bool
	 */
	private static function enrollment() {
		return get_current_user_id() > 0 && get_current_user_id() === self::$enrollment_user;
	}
	/** Limit an unenrolled/password-only user to self enrollment and read, regardless of role.
	 *
	 * @param array  $caps Mapped capabilities.
	 * @param string $cap Requested capability.
	 * @param int    $id User checked.
	 * @param array  $args Object IDs.
	 * @return array
	 */
	public static function caps( $caps, $cap, $id, $args ) {
		if ( ! self::enabled() || self::shell() || 'read' === $cap ) {
			return $caps;
		}
		if ( get_current_user_id() === $id && self::verified() ) {
			return $caps;
		}
		global $pagenow;
		$self = get_current_user_id() === $id && (int) ( $args[0] ?? 0 ) === $id && 'edit_user' === $cap;
		if ( $self && ( self::enrollment() || ( 'profile.php' === $pagenow && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && empty( $_GET ) ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification -- Read-only own-profile gate; no GET actions allowed.
			return array( 'read' );
		}
		return array( 'do_not_allow' );
	}
	/** Block admin controllers before mutations; only the own profile GET is enrollment grace. */
	public static function gate() {
		if ( ! self::enabled() || self::shell() || ! get_current_user_id() || self::verified() ) {
			return;
		}
		global $pagenow;
		if ( 'profile.php' === $pagenow && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && empty( $_GET ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification -- No action or other-user parameters accepted.
			return;
		}
		wp_die( 'A fresh approved second factor is required for WordPress administration. Enroll through your own profile or revalidate your session. No privileged change is authorized.', '', array( 'response' => 403 ) );
	}
	/** Anonymous/public read REST remains available. Private/authenticated API access needs MFA.
	 *
	 * @param mixed           $result Previous response.
	 * @param WP_REST_Server  $server Server.
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function rest( $result, $server, $request ) {
		unset( $server );
		if ( ! self::enabled() || ! get_current_user_id() || self::verified() ) {
			return $result;
		}
		$route = $request->get_route();
		$self  = get_current_user_id() === (int) $request->get_param( 'user_id' );
		if ( $self && 'POST' === $request->get_method() && in_array( $route, array( '/two-factor/1.0/totp', '/two-factor/1.0/generate-backup-codes' ), true ) && ! CSF_MFA_Session::enrolled( false, get_current_user_id() ) ) {
			self::$enrollment_user = get_current_user_id();
			return $result;
		}
		$public = 'GET' === $request->get_method() && (bool) preg_match( '#^/wp/v2/(posts|pages|types|taxonomies|categories|tags)(/[0-9]+)?$#D', $route ) && in_array( $request->get_param( 'context' ), array( null, 'view', 'embed' ), true ) && in_array( $request->get_param( 'status' ), array( null, 'publish' ), true );
		return $public ? $result : new WP_Error( 'csf_admin_mfa_required', 'Approved current-session MFA is required.', array( 'status' => 403 ) );
	}
	/** Application passwords are not an MFA channel under this explicitly selected policy.
	 *
	 * @param bool    $available Previous verdict.
	 * @param WP_User $user User.
	 * @return bool
	 */
	public static function application_passwords( $available, $user = null ) {
		unset( $user );
		return self::enabled() ? false : $available;
	}
	/** Reject authenticated XML-RPC; ordinary browser/HTTP Basic Auth is not intercepted.
	 *
	 * @param mixed $user Authentication result.
	 * @return mixed
	 */
	public static function authenticate( $user ) {
		return self::enabled() && defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST && $user instanceof WP_User ? new WP_Error( 'csf_admin_mfa_required', 'Authenticated XML-RPC is unavailable under the approved MFA policy.' ) : $user;
	}
	/** Security updates must be staged and repinned, never silently bypass integrity.
	 *
	 * @param bool|null $update Previous preference.
	 * @param object    $item Plugin update item.
	 * @return bool|null
	 */
	public static function updates( $update, $item ) {
		return self::enabled() && in_array( $item->plugin ?? '', array( 'two-factor/two-factor.php', 'csf-mfa/csf-mfa.php', 'csf-scheduling/csf-scheduling.php' ), true ) ? false : $update;
	}
	/** Visible next step without exposing another identity, secrets or a global bypass. */
	public static function notice() {
		if ( self::enabled() && get_current_user_id() && ! self::verified() ) {
			echo '<div class="notice notice-warning"><p>Digital Ranch security: privileged access is locked until your own approved MFA challenge succeeds. Provider integrity failure requires the separately approved operator recovery process. <a href="' . esc_url( add_query_arg( 'action', 'revalidate_2fa', wp_login_url() ) ) . '">Revalidate your second factor</a>.</p></div>';
		}
	}
}
