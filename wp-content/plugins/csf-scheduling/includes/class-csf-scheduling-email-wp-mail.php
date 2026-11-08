<?php
/** Sink-only qualification bridge for the native WordPress mail API.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Unloaded by the plugin. No production transport, hooks, cron or delivery switch. */
final class CSF_Scheduling_Email_WP_Mail {
	/** Build minimal arguments from an already recipient-scoped synthetic job.
	 *
	 * @param array  $job Versioned fixture job.
	 * @param string $address Synthetic invalid-domain recipient.
	 * @return array
	 * @throws RuntimeException If the fixture payload or recipient is invalid.
	 */
	public static function arguments( $job, $address ) {
		CSF_Scheduling_Email_Test_Adapter::fixture();
		if ( ! is_string( $address ) || ! is_email( $address ) || ! preg_match( '/@example\.invalid$/D', $address ) || preg_match( '/[\r\n<>;,]/', $address ) ) {
			throw new RuntimeException( 'email_fixture_recipient' );
		}
		$kinds  = array();
		$lines  = array();
		$urgent = false;
		foreach ( $job['message']['changes'] ?? array() as $change ) {
			if ( ! in_array( $change['kind'] ?? null, array( 'NEW', 'RESCHEDULED', 'CANCELED', 'REMOVED_FROM_ASSIGNMENT' ), true ) ) {
				throw new RuntimeException( 'email_invalid' );
			}
			$kinds[] = $change['kind'];
			$urgent  = $urgent || ! empty( $change['urgent'] );
			$lines[] = str_replace( '_', ' ', $change['kind'] ) . ' lesson';
		}
		if ( ! $lines || count( $lines ) > 20 || ! is_string( $job['key'] ?? null ) || ! preg_match( '/^[a-f0-9-]{36}:[1-9][0-9]*:email-sink-v1$/D', $job['key'] ) ) {
			throw new RuntimeException( 'email_invalid' );
		}
		return array(
			'to'          => array( $address ),
			'subject'     => 'Digital Ranch: ' . ( $urgent ? 'URGENT CHANGE' : implode( ' / ', array_unique( $kinds ) ) ),
			'message'     => implode( "\n", $lines ) . "\nSign in with MFA to review your permitted lesson details.\n" . admin_url( 'admin.php?page=csf-scheduling' ),
			'headers'     => array( 'X-Digital-Ranch-Fixture: ' . hash( 'sha256', $job['key'] ), 'From: Digital Ranch Fixture <fixture@example.invalid>' ),
			'attachments' => array(),
		);
	}

	/** Exercise wp_mail only when the final no-network fixture mailer is installed.
	 *
	 * @param array  $job Persisted claimed fixture job.
	 * @param string $token Claim token.
	 * @param int    $now Fixture clock.
	 * @param string $address Synthetic invalid-domain recipient.
	 * @return array
	 * @throws RuntimeException If the fixture, claim or mail hooks are not qualified.
	 */
	public static function attempt( $job, $token, $now, $address ) {
		CSF_Scheduling_Email_Test_Adapter::fixture();
		global $phpmailer;
		if ( ! class_exists( 'CSF_Scheduling_Email_Fixture_Mailer', false ) || ! is_object( $phpmailer ) || get_class( $phpmailer ) !== 'CSF_Scheduling_Email_Fixture_Mailer' || ! ( new ReflectionClass( $phpmailer ) )->isFinal() ) {
			throw new RuntimeException( 'email_fixture_mailer_required' );
		}
		if ( 'claimed' !== $job['state'] || ! is_string( $token ) || ! hash_equals( $job['claim'], $token ) || $now >= $job['lease_until'] ) {
			throw new RuntimeException( 'email_claim_lost' );
		}
		foreach ( array( 'wp_mail', 'pre_wp_mail', 'wp_mail_from', 'wp_mail_from_name', 'phpmailer_init', 'wp_mail_succeeded', 'wp_mail_failed', 'wp_mail_content_type', 'wp_mail_charset' ) as $hook ) {
			$object = $GLOBALS['wp_filter'][ $hook ] ?? null;
			foreach ( $object ? $object->callbacks : array() as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( 'wp_mail' !== $hook || 'wp_staticize_emoji_for_email' !== $callback['function'] ) {
						throw new RuntimeException( 'email_unqualified_hook' );
					}
				}
			}
		}
		$args = self::arguments( $job, $address );
		try {
			$accepted = wp_mail( $args['to'], $args['subject'], $args['message'], $args['headers'], $args['attachments'] );
			// False/exception is not proof that no submission occurred. Never blind-retry.
			$outcome = true === $accepted ? 'accepted' : 'uncertain';
		} catch ( Throwable $e ) {
			$outcome = 'uncertain';
		}
		return CSF_Scheduling_Email_Test_Adapter::finish( $job, $job['revision'], $token, $outcome, $now );
	}
}
