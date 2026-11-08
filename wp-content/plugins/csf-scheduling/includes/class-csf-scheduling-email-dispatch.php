<?php
/** Durable dispatch qualification; explicitly loaded only by disposable fixtures.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Uses the facility lock and ledger; has no hooks, scheduler or network transport. */
final class CSF_Scheduling_Email_Dispatch {
	/** Require the exact disposable database and authenticated operational actor. */
	public static function authorize() {
		return ABSPATH === '/test/site/' && defined( 'DB_NAME' ) && DB_NAME === 'csf_fixture' && wp_get_environment_type() === 'local' && CSF_Scheduling_Access::can( 'team_assign' ) && CSF_Scheduling_Access::can( 'calendar_view' );
	}

	/** Stable event/recipient/channel identity independent of worker retries.
	 *
	 * @param string $event Event UUID.
	 * @param int    $recipient Original recipient.
	 */
	public static function uuid( $event, $recipient ) {
		$hex = hash( 'sha256', $event . ':' . $recipient . ':email-sink-v1' );
		return substr( $hex, 0, 8 ) . '-' . substr( $hex, 8, 4 ) . '-4' . substr( $hex, 13, 3 ) . '-a' . substr( $hex, 17, 3 ) . '-' . substr( $hex, 20, 12 );
	}

	/** All original items must still be permitted; never silently widen or alter a retry.
	 *
	 * @param array $event Immutable event.
	 * @param int   $recipient Original recipient.
	 * @param array $state Current permission registry.
	 * @param array $appointments Current appointment details indexed by UUID.
	 * @return bool
	 */
	public static function permitted( $event, $recipient, $state, $appointments ) {
		$rights = CSF_Scheduling_Permissions::effective( $state, $recipient );
		if ( ( empty( $rights['calendar_all'] ) && empty( $rights['calendar_assigned'] ) ) || ! in_array( $recipient, $event['recipient_ids'] ?? array(), true ) ) {
			return false;
		}
		$items = $event['recipient_items'][ $recipient ] ?? array();
		if ( ! $items ) {
			return false;
		}
		$oversight = ! empty( $rights['calendar_all'] ) && ( ! empty( $rights['appointment_edit'] ) || ! empty( $rights['appointment_confirm'] ) || ! empty( $rights['appointment_cancel'] ) );
		$changes   = array_column( $event['changes'], null, 'uuid' );
		foreach ( $items as $uuid ) {
			if ( ! isset( $changes[ $uuid ], $appointments[ $uuid ] ) ) {
				return false;
			}
			if ( $oversight ) {
				continue;
			}
			$departure = in_array( $uuid, $event['recipient_departures'][ $recipient ] ?? array(), true );
			$resources = $departure ? ( $changes[ $uuid ]['previous_instructors'] ?? array() ) : ( $appointments[ $uuid ]['instructors'] ?? array() );
			if ( empty( $rights['calendar_assigned'] ) || ! array_intersect( $state['members'][ $recipient ]['resources'], $resources ) ) {
				return false;
			}
		}
		return true;
	}

	/** Check enrollment and current records under the same lock as permission changes.
	 *
	 * @param array $event Immutable event.
	 * @param int   $recipient Original recipient.
	 */
	private static function current( $event, $recipient ) {
		$appointments = array();
		foreach ( $event['recipient_items'][ $recipient ] ?? array() as $uuid ) {
			$row = CSF_Scheduling_Calendar::find( 'details', $uuid );
			if ( $row && CSF_Scheduling_Calendar::find( 'appointments', $uuid ) ) {
				$details               = json_decode( $row['body_json'], true );
				$appointments[ $uuid ] = array( 'instructors' => array() );
				foreach ( $details['resources'] ?? array() as $resource_uuid ) {
					$resource = CSF_Scheduling_Calendar::find( 'resources', $resource_uuid );
					if ( $resource && 'instructor' === $resource['kind'] ) {
						$appointments[ $uuid ]['instructors'][] = $resource_uuid;
					}
				}
			}
		}
		$state = CSF_Scheduling_Permissions::state();
		return $state && CSF_Scheduling_Access::mirror_matches( $recipient, CSF_Scheduling_Permissions::effective( $state, $recipient ) ) && CSF_Scheduling_Permissions::enrolled( $recipient ) && self::permitted( $event, $recipient, $state, $appointments );
	}

	/** Persist one bounded transition inside Store's transaction.
	 *
	 * @param array $body Strict command.
	 */
	public static function apply( $body ) {
		CSF_Scheduling_Calendar::require( self::authorize() && CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		$body += array( 'transport' => 'memory_sink' );
		CSF_Scheduling_Calendar::keys( $body, array( 'event', 'recipient', 'expected', 'action', 'token', 'outcome', 'transport' ) );
		CSF_Scheduling_Calendar::require( in_array( $body['transport'], array( 'memory_sink', 'native_mail_fixture' ), true ) );
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $body['event'] ) && is_int( $body['recipient'] ) && $body['recipient'] > 0 && is_int( $body['expected'] ) );
		CSF_Scheduling_Calendar::require( in_array( $body['action'], array( 'queue', 'claim', 'begin', 'finish', 'expire' ), true ) && is_string( $body['token'] ) && preg_match( '/^[a-f0-9]{32}$/D', $body['token'] ) && in_array( $body['outcome'], array( '', 'accepted', 'rejected_transient', 'rejected_permanent', 'uncertain' ), true ) );
		$event_row = CSF_Scheduling_Calendar::find( 'details', $body['event'] );
		$event     = $event_row ? json_decode( $event_row['body_json'], true ) : array();
		CSF_Scheduling_Calendar::require( 'booking_notice' === ( $event['record_type'] ?? null ) && $body['event'] === $event['source_command'], 'not_found' );
		$uuid = self::uuid( $body['event'], $body['recipient'] );
		$row  = CSF_Scheduling_Calendar::find( 'details', $uuid );
		CSF_Scheduling_Calendar::require( ( $row ? (int) $row['revision'] : 0 ) === $body['expected'], 'stale_appointment' );
		$doc = $row ? json_decode( $row['body_json'], true ) : null;
		CSF_Scheduling_Calendar::require( ! $doc || ( 'email_sink_job' === ( $doc['record_type'] ?? null ) && $doc['event'] === $body['event'] && $doc['recipient'] === $body['recipient'] ) );
		$job = $doc['job'] ?? null;
		$now = time();
		if ( 'queue' === $body['action'] ) {
			CSF_Scheduling_Calendar::require( ! $row, 'invalid_transition' );
			$job              = CSF_Scheduling_Email_Test_Adapter::job( $event, $body['recipient'] );
			$job['transport'] = $body['transport'];
		} else {
			CSF_Scheduling_Calendar::require( null !== $job, 'not_found' );
		}
		CSF_Scheduling_Calendar::require( ( $job['transport'] ?? 'memory_sink' ) === $body['transport'], 'invalid_transition' );
		if ( in_array( $body['action'], array( 'queue', 'claim', 'begin' ), true ) && ! in_array( $job['state'], array( 'queued', 'retry', 'claimed' ), true ) ) {
			CSF_Scheduling_Calendar::require( false, 'invalid_transition' );
		}
		if ( in_array( $body['action'], array( 'queue', 'claim', 'begin' ), true ) && ! self::current( $event, $body['recipient'] ) ) {
			$job['state']  = 'suppressed';
			$job['result'] = 'recipient_no_longer_permitted';
			++$job['revision'];
		} elseif ( 'claim' === $body['action'] ) {
			$job = CSF_Scheduling_Email_Test_Adapter::claim( $job, $job['revision'], $body['token'], $now );
		} elseif ( 'begin' === $body['action'] ) {
			CSF_Scheduling_Calendar::require( 'claimed' === $job['state'] && hash_equals( $job['claim'], $body['token'] ) && $now < $job['lease_until'], 'invalid_transition' );
			$job['state'] = 'submitting';
			++$job['revision'];
		} elseif ( 'finish' === $body['action'] ) {
			CSF_Scheduling_Calendar::require( 'submitting' === $job['state'], 'invalid_transition' );
			$job['state'] = 'claimed';
			$job          = CSF_Scheduling_Email_Test_Adapter::finish( $job, $job['revision'], $body['token'], $body['outcome'], $now );
		} elseif ( 'expire' === $body['action'] ) {
			CSF_Scheduling_Calendar::require( in_array( $job['state'], array( 'claimed', 'submitting' ), true ) && $now >= $job['lease_until'], 'invalid_transition' );
			$job['state']  = 'uncertain';
			$job['result'] = 'expired_claim';
			++$job['revision'];
		}
		$doc      = array(
			'record_type' => 'email_sink_job',
			'event'       => $body['event'],
			'recipient'   => $body['recipient'],
			'job'         => $job,
		);
		$expected = CSF_Scheduling_Calendar::document( 'details', $uuid, $body['event'], $doc, $body['expected'] );
		return array(
			'uuid'     => $uuid,
			'expected' => $expected,
			'job'      => $job,
		);
	}

	/** Commit a fresh begin command before invoking only the final in-memory sink.
	 *
	 * A failed/unknown begin never submits. A failed finish leaves submitting and
	 * expiry makes it uncertain. Re-entering with the old claim cannot resend.
	 *
	 * @param array                     $body Claimed job identifiers and expected row revision.
	 * @param CSF_Scheduling_Email_Sink $sink Final synthetic sink, no network capability.
	 */
	public static function run( $body, CSF_Scheduling_Email_Sink $sink ) {
		CSF_Scheduling_Calendar::require( ( $body['transport'] ?? 'memory_sink' ) === 'memory_sink', 'invalid_transition' );
		return self::work( $body, $sink, null );
	}

	/** Exercise the same durable worker through guarded wp_mail, never a real mailer.
	 *
	 * @param array  $body Native fixture claim identifiers.
	 * @param string $address Synthetic example.invalid address.
	 */
	public static function run_native( $body, $address ) {
		CSF_Scheduling_Calendar::require( self::authorize() && ( $body['transport'] ?? null ) === 'native_mail_fixture', 'forbidden' );
		require_once __DIR__ . '/class-csf-scheduling-email-wp-mail.php';
		return self::work( $body, null, $address );
	}

	/** Shared durable pre-submit and completion boundary; no arbitrary transport callback.
	 *
	 * @param array                          $body Claim identifiers.
	 * @param CSF_Scheduling_Email_Sink|null $sink Final memory sink or guarded native fixture.
	 * @param string|null                    $address Synthetic native fixture address.
	 */
	private static function work( $body, $sink, $address ) {
		CSF_Scheduling_Calendar::require( self::authorize(), 'forbidden' );
		$body['action']  = 'begin';
		$body['outcome'] = '';
		$result          = self::command( $body );
		if ( is_wp_error( $result ) || 'submitting' !== $result['job']['state'] ) {
			return $result;
		}
		if ( time() >= $result['job']['lease_until'] ) {
			return new WP_Error( 'email_claim_expired', 'Submission held; resolve the expired durable claim.' );
		}
		try {
			if ( null === $sink ) {
				$claimed          = $result['job'];
				$claimed['state'] = 'claimed';
				$receipt          = CSF_Scheduling_Email_WP_Mail::attempt( $claimed, $body['token'], time(), $address );
				$outcome          = $receipt['result'];
			} else {
				$outcome = $sink->submit( $result['job']['message'] + array( 'idempotency_key' => $result['job']['key'] ) );
			}
		} catch ( Throwable $error ) {
			$outcome = 'uncertain';
		}
		$body['expected'] = $result['expected'];
		$body['action']   = 'finish';
		$body['outcome']  = in_array( $outcome, array( 'accepted', 'rejected_transient', 'rejected_permanent' ), true ) ? $outcome : 'uncertain';
		return self::command( $body );
	}

	/** Fresh UUID is essential: a replayed begin result is never send authority.
	 *
	 * @param array $body Transition command.
	 */
	private static function command( $body ) {
		return CSF_Scheduling_Store::calendar( 'email_sink_transition', $body, (int) CSF_Scheduling_Schema::settings()['revision'], wp_generate_uuid4() );
	}
}
require_once __DIR__ . '/class-csf-scheduling-email-sink.php';
