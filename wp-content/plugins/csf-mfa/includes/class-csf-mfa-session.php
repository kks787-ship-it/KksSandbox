<?php
/**
 * Challenge-backed session evidence; never enrollment-as-authentication.
 *
 * @package CSF_MFA
 */

defined( 'ABSPATH' ) || exit;
// Uncached private security records must be read from the primary database.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/** Narrow integration with the pinned Two-Factor release and WordPress session API. */
final class CSF_MFA_Session {

	const VERSION = '0.2.0';
	const KEY     = 'csf-mfa-proof-v1';
	const METHODS = array( 'Two_Factor_Totp', 'Two_Factor_Backup_Codes' );
	const MAX_AGE = 28800;
	/** Cookie issued during this request, never persisted here.
	 *
	 * @var array
	 */
	private static $issued = array();
	/** Primary state captured before provider processing under a per-user lock.
	 *
	 * @var array
	 */
	private static $challenge = array();
	/** Alternate authentication happened during this request.
	 *
	 * @var bool
	 */
	private static $api = false;
	/** MySQL advisory lock owned until request shutdown.
	 *
	 * @var string|null
	 */
	private static $lock = null;

	/** Register evidence hooks. Activation never configures a provider or grants access. */
	public static function hooks() {
		add_filter( 'csf_scheduling_mfa_verified', array( __CLASS__, 'verified' ), PHP_INT_MAX, 2 );
		add_filter( 'csf_scheduling_mfa_enrolled', array( __CLASS__, 'enrolled' ), PHP_INT_MAX, 2 );
		add_filter( 'two_factor_providers', array( __CLASS__, 'providers' ), PHP_INT_MAX );
		add_action( 'set_logged_in_cookie', array( __CLASS__, 'cookie' ), PHP_INT_MAX, 6 );
		add_action( 'two_factor_user_authenticated', array( __CLASS__, 'authenticated' ), PHP_INT_MAX, 2 );
		add_action( 'two_factor_user_revalidated', array( __CLASS__, 'revalidated' ), PHP_INT_MAX, 2 );
		add_action( 'login_form_validate_2fa', array( __CLASS__, 'begin' ), 0 );
		add_action( 'login_form_revalidate_2fa', array( __CLASS__, 'begin' ), 0 );
		add_action( 'application_password_did_authenticate', array( __CLASS__, 'api_login' ) );
		foreach ( array( 'added_user_meta', 'updated_user_meta', 'deleted_user_meta' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'credentials_changed' ), 10, 4 );
		}
		add_action( 'updated_option', array( __CLASS__, 'configuration_changed' ), 10, 3 );
		add_action( 'added_option', array( __CLASS__, 'configuration_changed' ), 10, 2 );
		add_action( 'deleted_option', array( __CLASS__, 'configuration_changed' ), 10 );
		add_filter( 'get_user_metadata', array( __CLASS__, 'challenge_metadata' ), PHP_INT_MAX, 4 );
		add_action( 'shutdown', array( __CLASS__, 'unlock' ), PHP_INT_MAX );
	}

	/** Permanently invalidate older proofs even if a credential configuration is later restored.
	 *
	 * @param mixed  $meta_id Meta row ID.
	 * @param int    $id User.
	 * @param string $key Meta key.
	 * @param mixed  $value Value, never logged.
	 */
	public static function credentials_changed( $meta_id, $id, $key, $value ) {
		unset( $meta_id, $value );
		if ( defined( 'CSF_MFA_ENABLED' ) && true === CSF_MFA_ENABLED && in_array( $key, array( '_two_factor_totp_key', '_two_factor_enabled_providers', '_two_factor_provider', '_two_factor_backup_codes' ), true ) ) {
			global $wpdb;
			$prior = $wpdb->suppress_errors( true );
			try {
				update_user_meta( $id, '_csf_mfa_epoch', wp_generate_uuid4() );
			} finally {
				$wpdb->suppress_errors( $prior );
			}
		}
	}

	/** Provider removal/reinstallation and settings changes cannot revive an old proof.
	 *
	 * @param string $key Option.
	 * @param mixed  $before Previous value.
	 * @param mixed  $after New value.
	 */
	public static function configuration_changed( $key, $before = null, $after = null ) {
		unset( $before, $after );
		if ( defined( 'CSF_MFA_ENABLED' ) && true === CSF_MFA_ENABLED && in_array( $key, array( 'active_plugins', 'two_factor_enabled_providers' ), true ) ) {
			update_option( 'csf_mfa_epoch', wp_generate_uuid4(), false );
		}
	}

	/** No trusted WordPress plugin may fabricate another plugin's actions; that is outside this boundary.
	 *
	 * @return bool
	 */
	public static function ready() {
		if ( ! defined( 'CSF_MFA_ENABLED' ) || true !== CSF_MFA_ENABLED || ! defined( 'TWO_FACTOR_VERSION' ) || '0.17.0' !== TWO_FACTOR_VERSION || ! class_exists( 'Two_Factor_Core' ) || is_multisite() ) {
			return false;
		}
		$active = self::option( 'active_plugins' );
		if ( ! is_array( $active ) || ! in_array( 'two-factor/two-factor.php', $active, true ) || ! in_array( 'csf-mfa/csf-mfa.php', $active, true ) ) {
			return false;
		}
		$manifest = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/provider-files.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Pinned local release inventory, never a remote request.
		if ( ! is_array( $manifest ) || empty( $manifest['files'] ) ) {
			return false;
		}
		$paths = array();
		$root  = WP_PLUGIN_DIR . '/two-factor/';
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
			if ( $file->isLink() ) {
				return false; }
			if ( $file->isFile() ) {
				$paths[] = substr( $file->getPathname(), strlen( $root ) ); }
		}
		$expected = array_keys( $manifest['files'] );
		sort( $paths );
		sort( $expected );
		if ( $paths !== $expected ) {
			return false; }
		foreach ( $manifest['files'] as $path => $sha ) {
			$file = WP_PLUGIN_DIR . '/two-factor/' . $path;
			if ( ! is_file( $file ) || ! hash_equals( $sha, (string) hash_file( 'sha256', $file ) ) ) {
				return false;
			}
		}
		return true;
	}

	/** Raw option, bypassing persistent object caches.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function option( $key ) {
		global $wpdb;
		$prior = $wpdb->suppress_errors( true );
		try {
			$rows = $wpdb->get_col( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", $key ) );
			return 1 === count( $rows ) ? maybe_unserialize( $rows[0] ) : null;
		} finally {
			$wpdb->suppress_errors( $prior );
		}
	}

	/** Raw user meta, requiring a unique key. Sensitive SQL failures never reach logs.
	 *
	 * @param int    $id User ID.
	 * @param string $key Meta key.
	 * @return mixed
	 */
	public static function meta( $id, $key ) {
		global $wpdb;
		$prior = $wpdb->suppress_errors( true );
		try {
			$rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s", $id, $key ) );
			return 1 === count( $rows ) ? maybe_unserialize( $rows[0] ) : null;
		} finally {
			$wpdb->suppress_errors( $prior );
		}
	}

	/** Enrollment is only a prerequisite for assignment, never a proof or scheduling grant.
	 *
	 * @param bool $unused Ignored upstream verdict.
	 * @param int  $id Existing identity.
	 * @return bool
	 */
	public static function enrolled( $unused, $id ) {
		unset( $unused );
		if ( ! self::ready() || ! is_int( $id ) || $id < 1 ) {
			return false;
		}
		$methods = self::meta( $id, '_two_factor_enabled_providers' );
		$key     = self::meta( $id, '_two_factor_totp_key' );
		$site    = self::option( 'two_factor_enabled_providers' );
		return is_array( $methods ) && in_array( 'Two_Factor_Totp', $methods, true ) && ! array_diff( $methods, self::METHODS ) && is_string( $key ) && (bool) preg_match( '/^[A-Z2-7]{16,128}$/D', $key ) && ( null === $site || ( is_array( $site ) && in_array( 'Two_Factor_Totp', $site, true ) ) );
	}

	/** Restrict provider choices site-wide only when the separately approved private switch is enabled.
	 *
	 * @param array $providers Provider paths.
	 * @return array
	 */
	public static function providers( $providers ) {
		if ( ! defined( 'CSF_MFA_ENABLED' ) || true !== CSF_MFA_ENABLED ) {
			return $providers;
		}
		return array_intersect_key( $providers, array_flip( self::METHODS ) );
	}

	/** Serialize provider consumption; lock failure prevents a challenge from continuing. */
	public static function begin() {
		if ( ! defined( 'CSF_MFA_ENABLED' ) || true !== CSF_MFA_ENABLED ) {
			return;
		}
		// Provider validates nonce and challenge. This untrusted ID selects only a bounded lock.
		$id = 'login_form_revalidate_2fa' === current_action() ? get_current_user_id() : absint( $_REQUEST['wp-auth-id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! $id || ! self::ready() ) {
			wp_die( 'MFA integration unavailable. No Digital Ranch access is authorized.', '', array( 'response' => 403 ) );
		}
		global $wpdb;
		$key = 'csf-mfa-' . hash( 'sha256', DB_NAME . ':' . $wpdb->prefix . ':' . $id );
		$key = substr( $key, 0, 64 );
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $key ) ) ) {
			wp_die( 'Authentication is busy. Retry the sign-in.', '', array( 'response' => 409 ) );
		}
		self::$lock = $key;
		clean_user_cache( $id );
		self::$challenge = array(
			'id'     => $id,
			'totp'   => self::meta( $id, '_two_factor_totp_last_successful_login' ),
			'backup' => self::meta( $id, '_two_factor_backup_codes' ),
			'core'   => self::core_fingerprint( $id ),
		);
	}

	/** Release only our connection's local challenge lock. */
	public static function unlock() {
		if ( null !== self::$lock ) {
			global $wpdb;
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::$lock ) );
			self::$lock = null;
		}
		self::$challenge = array();
	}

	/** Alternate credentials never authorize a protected request. */
	public static function api_login() {
		self::$api = true;
	}

	/** Provider reads under our challenge lock cannot consume stale cached credentials/sessions.
	 *
	 * @param mixed  $value Prior filter result.
	 * @param int    $id User.
	 * @param string $key Meta key.
	 * @param bool   $single Single value requested.
	 * @return mixed
	 */
	public static function challenge_metadata( $value, $id, $key, $single ) {
		unset( $single );
		if ( null !== self::$lock && ( self::$challenge['id'] ?? null ) === $id && ( str_starts_with( $key, '_two_factor_' ) || 'session_tokens' === $key ) ) {
			return array( self::meta( $id, $key ) ?? '' );
		}
		return $value;
	}

	/** Capture the newly issued token, without treating issuance as MFA success.
	 *
	 * @param string $cookie Cookie.
	 * @param int    $expire Cookie expiry.
	 * @param int    $expiration Session expiration.
	 * @param int    $id User.
	 * @param string $scheme Scheme.
	 * @param string $token Session token.
	 */
	public static function cookie( $cookie, $expire, $expiration, $id, $scheme, $token ) {
		unset( $cookie, $expire, $scheme );
		self::$issued = array(
			'id'         => $id,
			'token'      => $token,
			'expiration' => $expiration,
		);
	}

	/** Handle documented post-challenge success only.
	 *
	 * @param WP_User             $user User.
	 * @param Two_Factor_Provider $provider Provider.
	 */
	public static function authenticated( $user, $provider ) {
		$i            = self::$issued;
		self::$issued = array();
		if ( ( $i['id'] ?? null ) === $user->ID ) {
			self::issue( $user->ID, $i['token'], $provider );
		}
	}

	/** Handle documented step-up success for this exact existing session.
	 *
	 * @param WP_User             $user User.
	 * @param Two_Factor_Provider $provider Provider.
	 */
	public static function revalidated( $user, $provider ) {
		if ( get_current_user_id() === $user->ID && (int) wp_validate_auth_cookie( '', 'logged_in' ) === $user->ID ) {
			self::issue( $user->ID, wp_get_session_token(), $provider );
		}
	}

	/** Stable credential configuration, excluding legitimate recovery-code consumption.
	 *
	 * @param int $id User.
	 * @return string
	 */
	private static function core_fingerprint( $id ) {
		global $wpdb;
		$pass = $wpdb->get_var( $wpdb->prepare( "SELECT user_pass FROM {$wpdb->users} WHERE ID=%d", $id ) );
		return hash_hmac( 'sha256', wp_json_encode( array( self::VERSION, defined( 'CSF_MFA_ENFORCE_ADMIN' ) && true === CSF_MFA_ENFORCE_ADMIN, $id, $pass, self::meta( $id, '_two_factor_totp_key' ), self::meta( $id, '_two_factor_enabled_providers' ), self::meta( $id, '_two_factor_provider' ), self::option( 'csf_mfa_epoch' ), self::option( 'two_factor_enabled_providers' ), home_url(), site_url() ) ), wp_salt( 'auth' ) );
	}

	/** Bind a proof to the validated configuration even if it changes during proof storage.
	 *
	 * @param int         $id User.
	 * @param string|null $core Pre-challenge credential fingerprint when issuing.
	 * @return string
	 */
	private static function fingerprint( $id, $core = null ) {
		return hash_hmac( 'sha256', wp_json_encode( array( $core ?? self::core_fingerprint( $id ), self::meta( $id, '_two_factor_backup_codes' ), self::meta( $id, '_csf_mfa_epoch' ) ) ), wp_salt( 'auth' ) );
	}

	/** Authoritative exact token lookup, bypassing WordPress object caches.
	 *
	 * @param int    $id User.
	 * @param string $token Token.
	 * @return array
	 */
	private static function session( $id, $token ) {
		$sessions = self::meta( $id, 'session_tokens' );
		return is_array( $sessions ) && is_string( $token ) && strlen( $token ) >= 32 ? ( $sessions[ hash( 'sha256', $token ) ] ?? array() ) : array();
	}

	/** Mint proof only after real provider consumption and durable primary-DB verification.
	 *
	 * @param int                 $id User.
	 * @param string              $token Token.
	 * @param Two_Factor_Provider $provider Provider.
	 */
	private static function issue( $id, $token, $provider ) {
		if ( ! self::enrolled( false, $id ) || self::$api || null === self::$lock || ( self::$challenge['id'] ?? null ) !== $id || 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || ! is_object( $provider ) || ! in_array( get_class( $provider ), self::METHODS, true ) ) {
			return;
		}
		$method = get_class( $provider );
		if ( ! hash_equals( self::$challenge['core'], self::core_fingerprint( $id ) ) ) {
			return;
		}
		if ( ! in_array( $method, self::meta( $id, '_two_factor_enabled_providers' ), true ) ) {
			return;
		}
		if ( 'Two_Factor_Totp' === $method ) {
			$last = self::meta( $id, '_two_factor_totp_last_successful_login' );
			if ( ! is_numeric( $last ) || (int) $last <= (int) self::$challenge['totp'] || abs( time() - (int) $last ) > 150 ) {
				return;
			}
		} else {
			$before = self::$challenge['backup'];
			$after  = self::meta( $id, '_two_factor_backup_codes' );
			if ( ! is_array( $before ) || ! is_array( $after ) || count( $after ) !== count( $before ) - 1 || array_diff( $after, $before ) ) {
				return;
			}
		}
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key='session_tokens'", $id ), ARRAY_A );
		if ( 1 !== count( $rows ) ) {
			return;
		}
		$sessions = maybe_unserialize( $rows[0]['meta_value'] );
		$session  = is_array( $sessions ) ? ( $sessions[ hash( 'sha256', $token ) ] ?? array() ) : array();
		if ( empty( $session['expiration'] ) || $session['expiration'] <= time() ) {
			return;
		}
		$proof                = array(
			'user'        => $id,
			'token'       => hash( 'sha256', $token ),
			'method'      => $method,
			'issued'      => time(),
			'expires'     => min( time() + self::MAX_AGE, (int) $session['expiration'] ),
			'fingerprint' => self::fingerprint( $id, self::$challenge['core'] ),
		);
		$proof['signature']   = hash_hmac( 'sha256', wp_json_encode( $proof ), wp_salt( 'auth' ) );
		$session[ self::KEY ] = $proof;
		$prior                = $wpdb->suppress_errors( true );
		try {
			$sessions[ hash( 'sha256', $token ) ] = $session;
			// A concurrent revocation/change makes this exact row comparison fail; never recreate a token.
			$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->usermeta}` SET meta_value=%s WHERE umeta_id=%d AND user_id=%d AND meta_key='session_tokens' AND BINARY meta_value=BINARY %s", maybe_serialize( $sessions ), (int) $rows[0]['umeta_id'], $id, $rows[0]['meta_value'] ) );
			wp_cache_delete( $id, 'user_meta' );
		} finally {
			$wpdb->suppress_errors( $prior );
		}
	}

	/** Exact current web session; no CLI, REST/XML-RPC, enrollment, role or incoming verdict bypass.
	 *
	 * @param bool $unused Untrusted previous verdict.
	 * @param int  $id Identity requesting protected access.
	 * @return bool
	 */
	public static function verified( $unused, $id ) {
		unset( $unused );
		return ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && self::web_verified( $id );
	}

	/** Current cookie proof for WordPress web administration, including nonce-authenticated REST.
	 *
	 * @param int $id Current identity.
	 * @return bool
	 */
	public static function web_verified( $id ) {
		if ( self::$api || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || get_current_user_id() !== $id || ! self::enrolled( false, $id ) || (int) wp_validate_auth_cookie( '', 'logged_in' ) !== $id ) {
			return false;
		}
		$token   = wp_get_session_token();
		$session = self::session( $id, $token );
		return self::proof_valid( $id, $session, hash( 'sha256', $token ) );
	}

	/** Validate an exact persisted proof without authorizing a request.
	 *
	 * @param int    $id User.
	 * @param array  $session Primary record.
	 * @param string $token_hash Session key.
	 * @return bool
	 */
	private static function proof_valid( $id, $session, $token_hash ) {
		$proof = $session[ self::KEY ] ?? null;
		if ( ! is_array( $proof ) || array_keys( $proof ) !== array( 'user', 'token', 'method', 'issued', 'expires', 'fingerprint', 'signature' ) || $proof['user'] !== $id || ! in_array( $proof['method'], self::METHODS, true ) || ! is_int( $proof['issued'] ) || ! is_int( $proof['expires'] ) || $proof['issued'] > time() || $proof['expires'] <= time() || $proof['expires'] - $proof['issued'] > self::MAX_AGE || (int) ( $session['expiration'] ?? 0 ) <= time() || ! hash_equals( $token_hash, (string) $proof['token'] ) || ! hash_equals( self::fingerprint( $id ), (string) $proof['fingerprint'] ) ) {
			return false;
		}
		$signature = $proof['signature'];
		unset( $proof['signature'] );
		return is_string( $signature ) && hash_equals( hash_hmac( 'sha256', wp_json_encode( $proof ), wp_salt( 'auth' ) ), $signature );
	}
	/** Non-bearer reference captured only from the approving user's own current browser.
	 *
	 * @return array|null
	 */
	public static function attestation() {
		$id = get_current_user_id();
		if ( ! self::verified( false, $id ) ) {
			return null; }
		$session = self::session( $id, wp_get_session_token() );
		return array(
			'user'      => $id,
			'session'   => hash( 'sha256', wp_get_session_token() ),
			'signature' => $session[ self::KEY ]['signature'],
		);
	}

	/** Revalidate a prior, durable web approval; never a CLI/session authorization verdict.
	 *
	 * @param array $ref Exact persisted approval reference.
	 * @param int   $max_age Maximum challenge age for the sensitive operation.
	 * @return bool
	 */
	public static function attested( $ref, $max_age = self::MAX_AGE ) {
		if ( ! is_array( $ref ) || array_keys( $ref ) !== array( 'user', 'session', 'signature' ) || ! is_int( $ref['user'] ) || ! is_string( $ref['session'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $ref['session'] ) || ! is_string( $ref['signature'] ) || ! self::enrolled( false, $ref['user'] ) ) {
			return false; }
		$all     = self::meta( $ref['user'], 'session_tokens' );
		$session = is_array( $all ) ? ( $all[ $ref['session'] ] ?? array() ) : array();
		return self::proof_valid( $ref['user'], $session, $ref['session'] ) && is_int( $max_age ) && $max_age > 0 && time() - $session[ self::KEY ]['issued'] <= $max_age && hash_equals( $session[ self::KEY ]['signature'], $ref['signature'] );
	}
}
