<?php
/** Explicit-offset local time handling.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Rejects DST gaps and requires callers to disambiguate repeated times. */
class CSF_Scheduling_Time {
	/** Parse an ISO local datetime with explicit numeric UTC offset.
	 *
	 * @param string $text ISO time.
	 * @param string $zone IANA zone.
	 * @return DateTimeImmutable
	 * @throws RuntimeException Invalid local time or offset.
	 */
	public static function parse( $text, $zone ) {
		if ( ! is_string( $text ) || ! is_string( $zone ) || ! in_array( $zone, timezone_identifiers_list(), true ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}[+-]\d{2}:\d{2}$/D', $text ) ) {
			throw new RuntimeException( 'invalid_time' );
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:iP', $text );
		if ( ! $date || $date->format( 'Y-m-d\TH:iP' ) !== $text || $date->setTimezone( new DateTimeZone( $zone ) )->format( 'Y-m-d\TH:iP' ) !== $text ) {
			throw new RuntimeException( 'invalid_time' );
		}
		return $date->setTimezone( new DateTimeZone( $zone ) );
	}

	/** UTC database value.
	 *
	 * @param DateTimeImmutable $date Instant.
	 * @return string
	 */
	public static function utc( $date ) {
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s.u' );
	}
}
