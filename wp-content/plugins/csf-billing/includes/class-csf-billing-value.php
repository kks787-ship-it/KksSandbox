<?php
/** Monetary invariants independent of payment transport.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** All amounts are integer minor units; policy approval is separate from implementation. */
final class CSF_Billing_Value {
	/** Read explicitly configured, immutable valuation policy. */
	public static function policy() {
		$policy = defined( 'CSF_BILLING_VALUATION_JSON' ) ? json_decode( CSF_BILLING_VALUATION_JSON, true, 8, JSON_THROW_ON_ERROR ) : array(
			'method'   => 'proportional_floor_v1',
			'version'  => 'pending',
			'approved' => false,
		);
		self::validate_policy( $policy );
		return $policy;
	}
	/** Validate supported allocation, without treating configuration as legal advice.
	 *
	 * @param array $policy Policy snapshot.
	 */
	public static function validate_policy( $policy ) {
		CSF_Billing_Domain::need( is_array( $policy ) && count( $policy ) === 3 && in_array( $policy['method'] ?? null, array( 'proportional_floor_v1', 'front_remainder_v1' ), true ) && is_bool( $policy['approved'] ?? null ) && is_string( $policy['version'] ?? null ) && preg_match( '/^[A-Za-z0-9._-]{1,64}$/D', $policy['version'] ), 'valuation_unapproved' );
	}
	/** Allocate every paid cent exactly once among immutable ride ordinals.
	 *
	 * @param int   $paid Original paid cents.
	 * @param int   $rides Purchased rides.
	 * @param array $policy Reviewed snapshot.
	 */
	public static function allocation( $paid, $rides, $policy ) {
		self::validate_policy( $policy );
		CSF_Billing_Domain::need( is_int( $paid ) && is_int( $rides ) && $rides > 0 && $rides <= 100 && $paid >= $rides && $paid <= 100000000, 'valuation_invalid' );
		$units = array();
		for ( $i = 0;$i < $rides;$i++ ) {
			$units[] = 'proportional_floor_v1' === $policy['method'] ? intdiv( $paid * ( $i + 1 ), $rides ) - intdiv( $paid * $i, $rides ) : intdiv( $paid, $rides ) + ( $i < $paid % $rides ? 1 : 0 );}
		return $units;
	}
	/** Initialize without enabling redemption under an unapproved policy.
	 *
	 * @param int   $paid Original paid cents.
	 * @param int   $rides Purchased rides.
	 * @param array $policy Immutable policy.
	 */
	public static function initial( $paid, $rides, $policy ) {
		return array(
			'version'         => 1,
			'currency'        => 'USD',
			'paid_minor'      => $paid,
			'allocation'      => self::allocation( $paid, $rides, $policy ),
			'policy'          => $policy,
			'used_slots'      => array(),
			'cash_paid_minor' => 0,
			'pending'         => null,
		);
	}
	/** Validate the complete value document and its ride balance.
	 *
	 * @param array $row Certificate row.
	 */
	public static function read( $row ) {
		CSF_Billing_Domain::need( is_array( $row ) && is_string( $row['value_json'] ?? null ), 'value_reconciliation_required' );
		$v = json_decode( $row['value_json'], true, 16, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::need( is_array( $v ) && 8 === count( $v ) && 1 === ( $v['version'] ?? null ) && 'USD' === ( $v['currency'] ?? null ), 'value_invalid' );
		CSF_Billing_Domain::need( self::allocation( $v['paid_minor'], (int) $row['purchased'], $v['policy'] ) === $v['allocation'] && is_array( $v['used_slots'] ) && array_is_list( $v['used_slots'] ) && count( $v['used_slots'] ) === (int) $row['redeemed'], 'value_invalid' );
		CSF_Billing_Domain::need( count( array_unique( $v['used_slots'], SORT_REGULAR ) ) === count( $v['used_slots'] ), 'value_invalid' );
		foreach ( $v['used_slots'] as $slot ) {
			CSF_Billing_Domain::need( is_int( $slot ) && $slot >= 0 && $slot < count( $v['allocation'] ), 'value_invalid' );}
		CSF_Billing_Domain::need( is_int( $v['cash_paid_minor'] ) && $v['cash_paid_minor'] >= 0 && self::remaining( $v ) >= 0, 'value_invalid' );
		if ( null !== $v['pending'] ) {
			CSF_Billing_Domain::uuid( $v['pending']['id'] ?? '' );
			CSF_Billing_Domain::need( 3 === count( $v['pending'] ) && in_array( $v['pending']['state'] ?? null, array( 'reserved', 'uncertain' ), true ) && is_int( $v['pending']['amount_minor'] ?? null ) && self::remaining( $v ) === $v['pending']['amount_minor'] && $v['pending']['amount_minor'] > 0, 'value_invalid' );}
		return $v;
	}
	/** Compute remaining paid value including a reservation, never excluding it as spent.
	 *
	 * @param array $v Validated value document.
	 */
	public static function remaining( $v ) {
		$spent = 0;
		foreach ( $v['used_slots'] as $slot ) {
			$spent += $v['allocation'][ $slot ];}
		return $v['paid_minor'] - $spent - $v['cash_paid_minor'];
	}
	/** Consume unused original ordinals; corrections restore exactly their original cents.
	 *
	 * @param array $row Certificate row.
	 * @param int   $rides Number of rides.
	 * @param array $reserved Unavailable original ordinals.
	 */
	public static function debit( $row, $rides, $reserved = array() ) {
		$v = self::read( $row );
		CSF_Billing_Domain::need( true === $v['policy']['approved'] && null === $v['pending'], 'valuation_or_cash_hold' );
		$slots = array_slice( array_values( array_diff( range( 0, (int) $row['purchased'] - 1 ), array_merge( $v['used_slots'], $reserved ) ) ), 0, $rides );
		CSF_Billing_Domain::need( count( $slots ) === $rides, 'insufficient_rides' );
		$minor = 0;
		foreach ( $slots as $slot ) {
			$minor += $v['allocation'][ $slot ];}
		CSF_Billing_Domain::need( $minor <= self::remaining( $v ), 'insufficient_value' );
		$v['used_slots'] = array_merge( $v['used_slots'], $slots );
		sort( $v['used_slots'] );
		return array(
			'value'       => $v,
			'slots'       => $slots,
			'value_minor' => $minor,
		);
	}
	/** Reverse only the exact original debit allocation.
	 *
	 * @param array $row Certificate row.
	 * @param array $debit Original immutable receipt.
	 */
	public static function reverse( $row, $debit ) {
		$v     = self::read( $row );
		$slots = $debit['slots'] ?? null;
		CSF_Billing_Domain::need( null === $v['pending'] && is_array( $slots ) && count( $slots ) === $debit['rides'] && ! array_diff( $slots, $v['used_slots'] ), 'value_reconciliation_required' );
		$minor = 0;
		foreach ( $slots as $slot ) {
			$minor += $v['allocation'][ $slot ];
		}CSF_Billing_Domain::need( ( $debit['value_minor'] ?? null ) === $minor, 'value_reconciliation_required' );
		$v['used_slots'] = array_values( array_diff( $v['used_slots'], $slots ) );
		return $v;
	}
}
