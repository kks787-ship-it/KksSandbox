<?php
/**
 * Public routes: /h/{token}, /h/{token}/print, and /emergency.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes render standalone, lightweight templates that do not depend on the active theme,
 * page builders, or third-party scripts, so they keep working if the theme breaks and load
 * quickly on a weak rural mobile connection.
 */
class CSF_Emergency_Public {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'route' ), 0 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'no_canonical_redirect' ) );
	}

	/**
	 * Rewrite rules. Added at the top so a page with the slug "emergency" cannot shadow them.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^h/([a-z0-9]{1,32})/print/?$', 'index.php?csf_h=$matches[1]&csf_print=1', 'top' );
		add_rewrite_rule( '^h/([a-z0-9]{1,32})/?$', 'index.php?csf_h=$matches[1]', 'top' );
		add_rewrite_rule( '^emergency/?$', 'index.php?csf_emergency=1', 'top' );
	}

	/**
	 * Allow our query vars.
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'csf_h';
		$vars[] = 'csf_print';
		$vars[] = 'csf_emergency';
		return $vars;
	}

	/**
	 * Stop WordPress guessing a "better" URL for our routes.
	 *
	 * @param string|false $redirect Redirect URL.
	 * @return string|false
	 */
	public static function no_canonical_redirect( $redirect ) {
		if ( get_query_var( 'csf_h' ) || get_query_var( 'csf_emergency' ) ) {
			return false;
		}
		return $redirect;
	}

	/**
	 * Dispatch.
	 */
	public static function route() {
		$token = get_query_var( 'csf_h' );
		if ( $token ) {
			self::render_horse( strtolower( (string) $token ), (bool) get_query_var( 'csf_print' ) );
		}
		if ( get_query_var( 'csf_emergency' ) ) {
			self::render_template( 'emergency-landing', array(), __( 'Emergency Information', 'csf-emergency' ), 200 );
		}
	}

	/**
	 * Render a horse profile, or the inactive-tag page. Unknown and unapproved tokens get an
	 * identical response so the page does not reveal which tokens exist.
	 *
	 * @param string $token Token from the URL.
	 * @param bool   $printable Print layout.
	 */
	private static function render_horse( $token, $printable ) {
		$post_id = CSF_Emergency_Tokens::find_post_id( $token );

		if ( ! $post_id || ! CSF_Emergency_Horses::is_public( $post_id ) ) {
			self::render_template( 'horse-inactive', array( 'token' => $token ), __( 'Corner Stone Farm Horse Tag', 'csf-emergency' ), 404 );
		}

		self::render_template(
			$printable ? 'horse-print' : 'horse-profile',
			array(
				'post_id' => $post_id,
				'token'   => $token,
			),
			get_the_title( $post_id ),
			200
		);
	}

	/**
	 * Output a standalone template and exit.
	 *
	 * @param string $template Template name (without .php).
	 * @param array  $vars     Variables available to the template as $csf.
	 * @param string $title    Document title.
	 * @param int    $status   HTTP status.
	 */
	public static function render_template( $template, array $vars, $title, $status ) {
		global $wp_query;
		if ( 404 !== $status ) {
			$wp_query->is_404 = false;
		}
		status_header( $status );
		CSF_Emergency_Privacy::send_headers();
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		$csf = array_merge(
			$vars,
			array(
				'title'    => $title,
				'settings' => CSF_Emergency_Settings::get(),
				'state'    => CSF_Emergency_Farm_State::get(),
			)
		);

		include CSF_EMERGENCY_DIR . 'templates/partials/header.php';
		include CSF_EMERGENCY_DIR . 'templates/' . $template . '.php';
		include CSF_EMERGENCY_DIR . 'templates/partials/footer.php';
		exit;
	}

	/**
	 * Format a UTC timestamp in the site timezone.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	public static function format_time( $timestamp ) {
		return $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' T', (int) $timestamp ) : '';
	}

	/**
	 * Format a Y-m-d date.
	 *
	 * @param string $date Date.
	 * @return string
	 */
	public static function format_date( $date ) {
		$ts = $date ? strtotime( $date . ' 12:00:00' ) : false;
		return $ts ? wp_date( get_option( 'date_format' ), $ts ) : '';
	}
}
