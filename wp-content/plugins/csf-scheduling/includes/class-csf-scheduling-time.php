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
	/** Valid instants for a wall time, including both DST folds.
	 *
	 * @param string $local Farm-local datetime.
	 * @param string $zone IANA zone.
	 * @return array
	 * @throws RuntimeException Invalid wall time.
	 */
	public static function choices( $local, $zone ) {
		if ( ! is_string( $local ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $local ) || ! is_string( $zone ) || ! in_array( $zone, timezone_identifiers_list(), true ) ) {
			throw new RuntimeException( 'invalid_time' ); }
		$naive = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $local, new DateTimeZone( 'UTC' ) );
		if ( ! $naive || $naive->format( 'Y-m-d\TH:i' ) !== $local ) {
			throw new RuntimeException( 'invalid_time' ); }
		$tz      = new DateTimeZone( $zone );
		$offsets = array();
		foreach ( $tz->getTransitions( $naive->getTimestamp() - 172800, $naive->getTimestamp() + 172800 ) as $transition ) {
			$offsets[] = $transition['offset']; }
		$choices = array();
		foreach ( array_unique( $offsets ) as $offset ) {
			$d = $naive->modify( ( 0 - $offset ) . ' seconds' )->setTimezone( $tz );
			if ( $d->format( 'Y-m-d\TH:i' ) === $local ) {
				$choices[] = $d->format( 'Y-m-d\TH:iP' ); }
		}
		usort( $choices, static fn( $a, $b ) => strtotime( $a ) <=> strtotime( $b ) );
		return $choices;
	}

	/** Resolve only a server-validated candidate, never a guessed browser offset.
	 *
	 * @param array|string $input Local/choice pair or original validated ISO for replay.
	 * @param string       $zone Facility zone.
	 * @return string
	 * @throws RuntimeException Gap or unchosen fold.
	 */
	public static function resolve( $input, $zone ) {
		if ( is_string( $input ) ) {
			return self::parse( $input, $zone )->format( 'Y-m-d\TH:iP' ); }
		if ( ! is_array( $input ) || array_diff( array_keys( $input ), array( 'local', 'choice' ) ) ) {
			throw new RuntimeException( 'invalid_time' ); }
		$choices = self::choices( $input['local'] ?? null, $zone );
		if ( 1 === count( $choices ) ) {
			return $choices[0]; }
		if ( 2 === count( $choices ) && in_array( $input['choice'] ?? null, $choices, true ) ) {
			return $input['choice']; }
		throw new RuntimeException( $choices ? 'ambiguous_time' : 'nonexistent_time' );
	}
}
