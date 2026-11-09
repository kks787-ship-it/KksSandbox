<?php
/** Fixed-host PayPal sandbox transport.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** No live host, arbitrary URL, redirect following, or credential persistence. */
final class CSF_Billing_PayPal {
	/** Exact sandbox API origin. */
	private const HOST = 'https://api-m.sandbox.paypal.com';
	/** Fetch fresh provider state; browser redirects never constitute payment proof.
	 *
	 * @param string $id Exact object identifier.
	 */
	public function order( $id ) {
		return $this->request( 'GET', '/v2/checkout/orders/' . CSF_Billing_Domain::provider_id( $id ) );
	}
	/** Fetch independent capture state.
	 *
	 * @param string $id Exact object identifier.
	 */
	public function capture_details( $id ) {
		return $this->request( 'GET', '/v2/payments/captures/' . CSF_Billing_Domain::provider_id( $id ) );
	}
	/** Use a durably recorded request UUID; callers must reconcile uncertain outcomes.
	 *
	 * @param string $id Exact object identifier.
	 * @param string $request_id Durably stored provider request UUID.
	 */
	public function capture( $id, $request_id ) {
		CSF_Billing_Domain::uuid( $request_id );
		return $this->request( 'POST', '/v2/checkout/orders/' . CSF_Billing_Domain::provider_id( $id ) . '/capture', new stdClass(), $request_id );
	}
	/** Build immutable server-priced order; no browser amount/merchant/return URL.
	 *
	 * @param array  $local Immutable local purchase bindings.
	 * @param string $request_id Durably stored provider request UUID.
	 */
	public function create( $local, $request_id ) {
		CSF_Billing_Domain::uuid( $request_id );
		CSF_Billing_Domain::uuid( $local['id'] );
		CSF_Billing_Domain::need( 'USD' === $local['currency'] );
		return $this->request(
			'POST',
			'/v2/checkout/orders',
			array(
				'intent'         => 'CAPTURE',
				'payment_source' => array(
					'paypal' => array(
						'experience_context' => array(
							'return_url'          => CSF_Billing_Config::return_url(),
							'cancel_url'          => CSF_Billing_Config::return_url(),
							'shipping_preference' => 'NO_SHIPPING',
							'user_action'         => 'PAY_NOW',
						),
					),
				),
				'purchase_units' => array(
					array(
						'reference_id' => $local['id'],
						'custom_id'    => $local['id'],
						'invoice_id'   => 'CSF-' . $local['id'],
						'payee'        => array( 'merchant_id' => CSF_Billing_Config::secret( 'CSF_PAYPAL_SANDBOX_MERCHANT_ID' ) ),
						'amount'       => array(
							'currency_code' => 'USD',
							'value'         => CSF_Billing_Domain::amount( $local['amount_minor'] ),
						),
					),
				),
			),
			$request_id
		);
	}
	/** Verify an exact decoded event with PayPal, without fetching a caller's URL.
	 *
	 * @param mixed $body Bounded request payload.
	 * @param array $headers Webhook signature headers.
	 */
	public function verify( $body, $headers ) {
		CSF_Billing_Domain::need( is_string( $body ) && strlen( $body ) <= 65536, 'webhook_invalid' );
		$event = json_decode( $body, true, 32, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::need( is_array( $event ) && is_string( $event['id'] ?? null ) && preg_match( '/^[A-Za-z0-9-]{8,64}$/D', $event['id'] ), 'webhook_invalid' );
		$data = array(
			'webhook_id'    => CSF_Billing_Config::secret( 'CSF_PAYPAL_SANDBOX_WEBHOOK_ID' ),
			'webhook_event' => $event,
		);
		foreach ( array( 'auth_algo', 'cert_url', 'transmission_id', 'transmission_sig', 'transmission_time' ) as $name ) {
			$value = $headers[ $name ] ?? null;
			CSF_Billing_Domain::need( is_string( $value ) && strlen( $value ) > 0 && strlen( $value ) <= 2048 && ! preg_match( '/[\r\n]/', $value ), 'webhook_invalid' );
			$data[ $name ] = $value;
		}
		CSF_Billing_Domain::need( 'SHA256withRSA' === $data['auth_algo'] && preg_match( '~^https://api\.sandbox\.paypal\.com/v1/notifications/certs/[A-Za-z0-9-]+$~D', $data['cert_url'] ), 'webhook_invalid' );
		$result = $this->request( 'POST', '/v1/notifications/verify-webhook-signature', $data );
		CSF_Billing_Domain::need( 'SUCCESS' === ( $result['verification_status'] ?? null ), 'webhook_unverified' );
		return $event;
	}
	/** Obtain short-lived OAuth token per request; never store it in WordPress.
	 *
	 * @param string $method Compiled HTTP method.
	 * @param string $path Compiled API path.
	 * @param mixed  $body Bounded request payload.
	 * @param string $request_id Durably stored provider request UUID.
	 */
	private function request( $method, $path, $body = null, $request_id = null ) {
		CSF_Billing_Domain::need( CSF_Billing_Config::enabled(), 'sandbox_disabled' );
		$client = CSF_Billing_Config::secret( 'CSF_PAYPAL_SANDBOX_CLIENT_ID' );
		$secret = CSF_Billing_Config::secret( 'CSF_PAYPAL_SANDBOX_CLIENT_SECRET' );
		$token  = $this->send(
			'/v1/oauth2/token',
			array(
				'method'  => 'POST',
				'headers' => array(
					// Binary key/envelope or OAuth Basic authentication encoding; never evaluated as code.
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Authorization' => 'Basic ' . base64_encode( $client . ':' . $secret ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => 'grant_type=client_credentials',
			)
		);
		CSF_Billing_Domain::need( is_string( $token['access_token'] ?? null ) && strlen( $token['access_token'] ) > 0 && strlen( $token['access_token'] ) <= 8192 && ! preg_match( '/[\r\n]/', $token['access_token'] ) && 'Bearer' === ( $token['token_type'] ?? null ), 'provider_unavailable' );
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token['access_token'],
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
		);
		if ( null !== $request_id ) {
			$args['headers']['PayPal-Request-Id'] = $request_id;
		}
		if ( null !== $body ) {
			$args['body'] = CSF_Billing_Domain::json( $body );
		}
		return $this->send( $path, $args );
	}
	/** TLS verification stays enabled and redirect/error bodies never reach callers.
	 *
	 * @param string $path Compiled API path.
	 * @param array  $args Server-built HTTP arguments.
	 */
	private function send( $path, $args ) {
		$args    += array(
			'timeout'             => 20,
			'redirection'         => 0,
			'sslverify'           => true,
			'limit_response_size' => 131072,
		);
		$response = wp_remote_request( self::HOST . $path, $args );
		CSF_Billing_Domain::need( ! is_wp_error( $response ), 'provider_outcome_uncertain' );
		$status = wp_remote_retrieve_response_code( $response );
		CSF_Billing_Domain::need( $status >= 200 && $status < 300, 'provider_outcome_uncertain' );
		$result = json_decode( wp_remote_retrieve_body( $response ), true, 32, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::need( is_array( $result ), 'provider_outcome_uncertain' );
		return $result;
	}
}
