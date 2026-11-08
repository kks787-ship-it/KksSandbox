<?php
/** Strict same-origin sandbox checkout and independent private management endpoints.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** Public checkout and private management have independent default-off gates. */
final class CSF_Billing_HTTP {
	/** Register only bounded POST routes; never expose balances by certificate number. */
	public static function register() {
		$actions = array();
		if ( CSF_Billing_Config::enabled() ) {
			$actions = array( 'session', 'prepare', 'create', 'capture', 'receipt' );
		}
		if ( CSF_Billing_Config::enabled() || CSF_Billing_Config::management_enabled() ) {
			$actions = array_merge( $actions, array( 'list', 'detail', 'redeem', 'correct', 'recover', 'cash' ) );
		}
		foreach ( $actions as $action ) {
			register_rest_route(
				'csf-billing/v1',
				'/' . $action,
				array(
					'methods'             => 'POST',
					'permission_callback' => array( __CLASS__, 'permit' ),
					'callback'            => array( __CLASS__, 'dispatch' ),
				)
			);
		}
		if ( CSF_Billing_Config::enabled() && defined( 'CSF_BILLING_WEBHOOK_QUALIFIED' ) && true === CSF_BILLING_WEBHOOK_QUALIFIED ) {
			register_rest_route(
				'csf-billing/v1',
				'/webhook',
				array(
					'methods'             => 'POST',
					'permission_callback' => array( __CLASS__, 'webhook_permit' ),
					'callback'            => array( __CLASS__, 'webhook' ),
				)
			);
		}
	}
	/** Include permission errors in the no-cache policy.
	 *
	 * @param WP_HTTP_Response $response Current response.
	 * @param WP_REST_Server   $server REST server.
	 * @param WP_REST_Request  $request Current request.
	 * @return WP_HTTP_Response
	 */
	public static function no_cache( $response, $server, $request ) {
		unset( $server );
		if ( str_starts_with( $request->get_route(), '/csf-billing/v1/' ) ) {
			$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
			$response->header( 'Referrer-Policy', 'no-referrer' );
		}
		return $response;
	}

	/** Authenticate request context before any ledger access.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return bool|WP_Error
	 */
	public static function permit( $request ) {
		try {
			$action  = basename( $request->get_route() );
			$private = in_array( $action, array( 'list', 'detail', 'redeem', 'correct', 'recover', 'cash' ), true );
			CSF_Billing_Domain::need( CSF_Billing_Config::enabled() || ( $private && CSF_Billing_Config::management_enabled() ), 'sandbox_disabled' );
			CSF_Billing_Session::origin( $request );
			if ( in_array( $action, array( 'list', 'detail', 'redeem', 'correct', 'recover', 'cash' ), true ) ) {
				CSF_Billing_Domain::need( wp_verify_nonce( $request->get_header( 'x-wp-nonce' ), 'wp_rest' ), 'request_forbidden' );
				CSF_Billing_Access::require_cap( 'redeem' === $action ? 'csf_billing_redeem' : ( in_array( $action, array( 'correct', 'recover', 'cash' ), true ) ? 'csf_billing_manage' : 'csf_billing_audit' ) );
			} elseif ( 'session' !== $action ) {
				CSF_Billing_Domain::need( hash_equals( CSF_Billing_Session::csrf( CSF_Billing_Session::current() ), $request->get_header( 'x-csf-csrf' ) ), 'request_forbidden' );
			}
			return true;
		} catch ( Throwable $error ) {
			return new WP_Error( 'billing_forbidden', __( 'This request needs a valid session and verified access.', 'csf-billing' ), array( 'status' => 403 ) );
		}
	}
	/** Always send sensitive responses uncached, including failures.
	 *
	 * @param array $data Safe response fields.
	 * @param int   $status HTTP status.
	 * @return WP_REST_Response
	 */
	private static function response( $data, $status = 200 ) {
		return new WP_REST_Response(
			$data,
			$status,
			array(
				'Cache-Control'          => 'private, no-store, max-age=0',
				'Pragma'                 => 'no-cache',
				'Referrer-Policy'        => 'no-referrer',
				'X-Content-Type-Options' => 'nosniff',
			)
		);
	}
	/** Minimized public and private request dispatcher.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response
	 */
	public static function dispatch( $request ) {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			$allowed = self::permit( $request );
			CSF_Billing_Domain::need( true === $allowed, 'request_forbidden' );
			$action = basename( $request->get_route() );
			$body   = $request->get_json_params();
			CSF_Billing_Domain::need( is_array( $body ), 'invalid_request' );
			$store = new CSF_Billing_Store();
			$store->throttle( $action, in_array( $action, array( 'prepare', 'create' ), true ) ? 5 : 30 );
			$service = new CSF_Billing_Service( $store );
			if ( 'session' === $action ) {
				try {
					$cookie = CSF_Billing_Session::current();
				} catch ( CSF_Billing_Failure $error ) {
					$cookie = CSF_Billing_Session::issue(); }
				CSF_Billing_Domain::need( ! headers_sent(), 'session_unavailable' );
				setcookie(
					CSF_Billing_Session::COOKIE,
					$cookie,
					array(
						'expires'  => (int) explode( '.', $cookie )[1],
						'path'     => '/',
						'secure'   => true,
						'httponly' => true,
						'samesite' => 'Lax',
					)
				);
				$catalog  = CSF_Billing_Config::catalog();
				$terms    = CSF_Billing_Domain::terms( $catalog['terms'] ?? null );
				$products = array_map( array( 'CSF_Billing_Domain', 'product' ), $catalog['products'] ?? array() );
				CSF_Billing_Domain::need( count( $products ) > 0 && count( $products ) <= 20, 'catalog_unapproved' );
				return self::response(
					array(
						'csrf'     => CSF_Billing_Session::csrf( $cookie ),
						'products' => $products,
						'terms'    => $terms,
						'sandbox'  => true,
					)
				);
			}
			if ( 'list' === $action ) {
				return self::response(
					array(
						'orders'  => $store->listing(),
						'reviews' => $store->reviews(),
					)
				); }
			if ( 'cash' === $action ) {
				return self::response( $service->cash( $body['command'] ?? '', $body['certificate_id'] ?? '', $body['revision'] ?? null, $body['transition'] ?? '', $body['request'] ?? '', $body['amount_minor'] ?? null, $body['evidence'] ?? '', $body['attested'] ?? false ) );
			}
			if ( 'redeem' === $action ) {
				return self::response( $service->redeem( $body['command'] ?? '', $body['certificate_id'] ?? '', $body['rides'] ?? null, $body['revision'] ?? null, $body['ride_reference'] ?? '' ) );
			}
			if ( 'recover' === $action ) {
				return self::response( $service->recover_created( $body['command'] ?? '', $body['order_id'] ?? '', $body['provider_order_id'] ?? '', $body['revision'] ?? null ) );
			}
			if ( 'correct' === $action ) {
				return self::response( $service->correct( $body['command'] ?? '', $body['certificate_id'] ?? '', $body['redemption_command'] ?? '', $body['revision'] ?? null, $body['reason'] ?? '' ) );
			}
			$session = 'detail' === $action ? null : CSF_Billing_Session::digest( CSF_Billing_Session::current() );
			if ( 'prepare' === $action ) {
				$catalog  = CSF_Billing_Config::catalog();
				$products = array_values(
					array_filter(
						$catalog['products'] ?? array(),
						static function ( $p ) use ( $body ) {
							return ( $p['id'] ?? null ) === ( $body['product_id'] ?? null ) && ( $p['revision'] ?? null ) === ( $body['product_revision'] ?? null );
						}
					)
				);
				CSF_Billing_Domain::need( 1 === count( $products ), 'catalog_changed' );
				return self::response( $service->prepare( $body['command'] ?? '', $session, $products[0], $catalog['terms'], $body ) );
			}
			$id = CSF_Billing_Domain::uuid( $body['order_id'] ?? '' );
			if ( 'create' === $action ) {
				$result = $service->checkout( $body['command'] ?? '', $id, $session );
				return self::response(
					array(
						'order_id'     => $id,
						'approval_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=' . rawurlencode( CSF_Billing_Domain::provider_id( $result['provider_order_id'] ) ),
					)
				);
			}
			if ( 'capture' === $action ) {
				$service->capture( $body['command'] ?? '', $id, $session ); }
			CSF_Billing_Domain::need( in_array( $action, array( 'capture', 'receipt', 'detail' ), true ), 'invalid_request' );
			$result                       = $store->receipt( $id, $session );
			$result['certificate_number'] = $result['number_sealed'] ? CSF_Billing_Config::open( $result['number_sealed'] ) : null;
			unset( $result['number_sealed'] );
			if ( 'detail' === $action ) {
				$result['history'] = $store->history( $id, $result['certificate_id'] );
				foreach ( array( 'redeem', 'manage' ) as $right ) {
					try {
						CSF_Billing_Access::require_cap( 'csf_billing_' . $right );
						$result[ 'can_' . $right ] = true; } catch ( CSF_Billing_Failure $error ) {
						$result[ 'can_' . $right ] = false; }
				}
			}
			return self::response( $result );
		} catch ( Throwable $error ) {
			$code = $error instanceof CSF_Billing_Failure ? $error->getMessage() : 'billing_unavailable';
			return self::response(
				array(
					'code'    => $code,
					'message' => __( 'The request could not be completed. Keep your purchase reference and check its status before starting another payment.', 'csf-billing' ),
				),
				'rate_limited' === $code ? 429 : 409
			);
		} finally {
			$wpdb->suppress_errors( $previous ); }
	}
	/** Separate verified-provider entry point; never relax site-level Basic Auth.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return bool|WP_Error
	 */
	public static function webhook_permit( $request ) {
		return CSF_Billing_Config::enabled() && defined( 'CSF_BILLING_WEBHOOK_QUALIFIED' ) && true === CSF_BILLING_WEBHOOK_QUALIFIED && is_ssl() && strlen( $request->get_body() ) <= 65536 ? true : new WP_Error( 'billing_unavailable', 'Unavailable.', array( 'status' => 403 ) );
	}
	/** Provider verification and lifecycle application; no payment data in response.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response
	 */
	public static function webhook( $request ) {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			CSF_Billing_Domain::need( true === self::webhook_permit( $request ), 'request_forbidden' );
			$store = new CSF_Billing_Store();
			$store->throttle( 'webhook', 120 );
			$headers = array();
			foreach ( array( 'auth_algo', 'cert_url', 'transmission_id', 'transmission_sig', 'transmission_time' ) as $name ) {
				$headers[ $name ] = $request->get_header( 'paypal-' . str_replace( '_', '-', $name ) ); }
			( new CSF_Billing_Service( $store ) )->webhook( $request->get_body(), $headers );
			return self::response( array( 'received' => true ) );
		} catch ( Throwable $error ) {
			return self::response( array( 'received' => false ), 503 ); } finally {
			$wpdb->suppress_errors( $previous ); }
	}
}
