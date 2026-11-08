<?php
/** Local fixture-only email adapter. No real transport, hooks, contacts or scheduler.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Deliberately final: a network sender cannot be substituted into the test adapter. */
final class CSF_Scheduling_Email_Sink {
	/** Remaining synthetic outcomes.
	 *
	 * @var array
	 */
	private $outcomes;
	/** Previously accepted messages by idempotency key.
	 *
	 * @var array
	 */
	private $accepted = array();
	/** Observed synthetic submissions.
	 *
	 * @var array
	 */
	public $messages = array();
	/** Prepare synthetic outcomes for the in-memory sink.
	 *
	 * @param array $outcomes Ordered synthetic submission results.
	 */
	public function __construct( $outcomes = array( 'accepted' ) ) {
		$this->outcomes = $outcomes; }
	/** Accept or reject a synthetic message with idempotent deduplication.
	 *
	 * @param array $message Minimal synthetic message envelope.
	 * @throws RuntimeException If the idempotency key is invalid or conflicts.
	 */
	public function submit( $message ) {
		CSF_Scheduling_Email_Test_Adapter::fixture();
		$key = $message['idempotency_key'] ?? null;
		if ( ! is_string( $key ) ) {
			throw new RuntimeException( 'email_invalid' ); }
		if ( isset( $this->accepted[ $key ] ) ) {
			if ( $this->accepted[ $key ] !== $message ) {
				throw new RuntimeException( 'email_key_conflict' );
			} return 'accepted'; }
		$outcome          = array_shift( $this->outcomes ) ?? 'accepted';
		$this->messages[] = $message;
		if ( 'throw' === $outcome ) {
			throw new RuntimeException( 'synthetic_timeout' ); }
		if ( 'accepted' === $outcome ) {
			$this->accepted[ $key ] = $message; }
		return $outcome;
	}
}

require_once __DIR__ . '/class-csf-scheduling-email-test-adapter.php';
