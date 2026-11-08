<?php
/** Fixture-only versioned email adapter; no real delivery.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Pure versioned job transitions for an isolated sink; never changes live envelopes. */
final class CSF_Scheduling_Email_Test_Adapter {
	const MAX_ATTEMPTS = 3;
	/** Require an isolated local fixture before adapter use.
	 *
	 * @throws RuntimeException Outside the isolated local fixture.
	 */
	public static function fixture() {
		if ( ABSPATH !== '/test/site/' || ! function_exists( 'wp_get_environment_type' ) || 'local' !== wp_get_environment_type() ) {
			throw new RuntimeException( 'email_fixture_only' ); }
	}
	/** Reject an invalid adapter transition.
	 *
	 * @param bool   $ok Required condition.
	 * @param string $error Fixed adapter error code.
	 * @throws RuntimeException If the transition condition fails.
	 */
	private static function require( $ok, $error = 'email_invalid' ) {
		if ( ! $ok ) {
			throw new RuntimeException( esc_html( $error ) ); } }
	/** Copy only a recipient's item subset; no names, contacts, reasons or free text.
	 *
	 * @param array $event Durable notice envelope.
	 * @param int   $recipient Original recipient user ID.
	 */
	public static function job( $event, $recipient ) {
		self::fixture();
		self::require( 'booking_notice' === ( $event['record_type'] ?? null ) && 'held_no_delivery_authorization' === ( $event['email_state'] ?? null ) && is_int( $recipient ) && $recipient > 0 && in_array( $recipient, $event['recipient_ids'] ?? array(), true ) );
		self::require( CSF_Scheduling_Audit::uuid( $event['source_command'] ?? null ) );
		$changes = array();
		foreach ( $event['changes'] ?? array() as $change ) {
			if ( ! in_array( $change['uuid'] ?? null, $event['recipient_items'][ $recipient ] ?? array(), true ) ) {
				continue; }
			self::require( CSF_Scheduling_Audit::uuid( $change['uuid'] ) && in_array( $change['kind'], array( 'NEW', 'RESCHEDULED', 'CANCELED' ), true ) );
			$departure = in_array( $change['uuid'], $event['recipient_departures'][ $recipient ] ?? array(), true );
			$changes[] = array(
				'uuid'   => $change['uuid'],
				'kind'   => $departure ? 'REMOVED_FROM_ASSIGNMENT' : $change['kind'],
				'urgent' => $departure ? ! empty( $change['before_urgent'] ) : ! empty( $change['urgent'] ),
			);
		}
		self::require( count( $changes ) > 0 && count( $changes ) <= 20 );
		return array(
			'key'         => $event['source_command'] . ':' . $recipient . ':email-sink-v1',
			'revision'    => 0,
			'state'       => 'queued',
			'attempts'    => 0,
			'retry_at'    => 0,
			'claim'       => null,
			'lease_until' => null,
			'message'     => array(
				'recipient_id' => $recipient,
				'subject'      => 'Digital Ranch booking update',
				'text'         => 'Sign in with MFA to review your permitted lesson details.',
				'changes'      => $changes,
			),
			'result'      => null,
		);
	}
	/** Check the expected job revision and supplied clock.
	 *
	 * @param array $job Versioned synthetic job.
	 * @param int   $expected Expected record revision.
	 * @param int   $now Current Unix timestamp.
	 */
	private static function version( $job, $expected, $now ) {
		self::fixture();
		self::require( is_int( $expected ) && $expected === $job['revision'], 'email_stale' );
		self::require( is_int( $now ) && $now >= 0 );
	}
	/** Caller must persist this transition atomically before any attempt.
	 *
	 * @param array  $job Versioned synthetic job.
	 * @param int    $expected Expected record revision.
	 * @param string $token Opaque claim token.
	 * @param int    $now Current Unix timestamp.
	 */
	public static function claim( $job, $expected, $token, $now ) {
		self::version( $job, $expected, $now );
		self::require( is_string( $token ) && 1 === preg_match( '/^[a-f0-9]{32}$/D', $token ) );
		// A lost worker may already have submitted: never automatically reclaim it.
		if ( 'claimed' === $job['state'] && $job['lease_until'] <= $now ) {
			$job['state']  = 'uncertain';
			$job['result'] = 'expired_claim';
			++$job['revision'];
			return $job; }
		self::require( in_array( $job['state'], array( 'queued', 'retry' ), true ) && $job['retry_at'] <= $now && $job['attempts'] < self::MAX_ATTEMPTS, 'email_not_claimable' );
		$job['state']       = 'claimed';
		$job['claim']       = $token;
		$job['lease_until'] = $now + 60;
		++$job['attempts'];
		++$job['revision'];
		return $job;
	}
	/** An uncertain result is terminal pending manual evidence review, never auto-retried.
	 *
	 * @param array  $job Versioned synthetic job.
	 * @param int    $expected Expected record revision.
	 * @param string $token Opaque claim token.
	 * @param string $outcome Explicit synthetic transport outcome.
	 * @param int    $now Current Unix timestamp.
	 */
	public static function finish( $job, $expected, $token, $outcome, $now ) {
		self::version( $job, $expected, $now );
		self::require( 'claimed' === $job['state'] && is_string( $token ) && hash_equals( $job['claim'], $token ), 'email_claim_lost' );
		if ( $now >= $job['lease_until'] ) {
			$outcome = 'uncertain'; }
		if ( 'accepted' === $outcome ) {
			$job['state'] = 'sink_accepted'; } elseif ( 'rejected_transient' === $outcome && $job['attempts'] < self::MAX_ATTEMPTS ) {
			$job['state']    = 'retry';
			$job['retry_at'] = $now + 60 * ( 2 ** ( $job['attempts'] - 1 ) ); } elseif ( in_array( $outcome, array( 'rejected_permanent', 'rejected_transient' ), true ) ) {
				$job['state'] = 'failed'; } else {
				$job['state'] = 'uncertain';
				$outcome      = 'uncertain'; }
				$job['result'] = $outcome;
				++$job['revision'];
				return $job;
	}
	/** Only the final in-memory sink is accepted. This cannot invoke wp_mail/HTTP/SMTP.
	 *
	 * @param array                     $claimed Persisted claimed job.
	 * @param string                    $token Opaque claim token.
	 * @param int                       $now Current Unix timestamp.
	 * @param CSF_Scheduling_Email_Sink $sink Final in-memory test sink.
	 */
	public static function attempt( $claimed, $token, $now, CSF_Scheduling_Email_Sink $sink ) {
		self::fixture();
		self::require( 'claimed' === $claimed['state'] && is_string( $token ) && hash_equals( $claimed['claim'], $token ) && $now < $claimed['lease_until'], 'email_claim_lost' );
		try {
			$result = $sink->submit( $claimed['message'] + array( 'idempotency_key' => $claimed['key'] ) );
		} catch ( Throwable $e ) {
			$result = 'uncertain'; }
		return self::finish( $claimed, $claimed['revision'], $token, $result, $now );
	}
}
