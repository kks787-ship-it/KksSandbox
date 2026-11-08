<?php
/** Pure reservation planning. This class does not authorize, reserve or save.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Calculate itemized resource windows before the transactional booking gate. */
class CSF_Scheduling_Visit_Plan {
	/** Plan explicitly linked lessons with independent mandatory resource buffers.
	 *
	 * Callers must resolve resource identities, compatibility and mandatory minima
	 * from authoritative records. A submitted plan must never be trusted on write.
	 * Times are UTC epoch seconds, after strict facility-time parsing. Only active
	 * proposed lessons belong here; a cancellation is recalculated by omission.
	 * This deliberately neither evaluates nor changes horse turns/recovery.
	 *
	 * @param array $lessons Normalized proposed lessons, not stored appointments.
	 * @param int   $before Configured preparation minutes (pilot baseline 15).
	 * @param int   $after Configured wrap-up minutes (pilot baseline 15).
	 * @return array Itemized windows in input order plus resource conflicts.
	 */
	public static function calculate( $lessons, $before = 15, $after = 15 ) {
		self::need( is_array( $lessons ) && array_is_list( $lessons ) && count( $lessons ) <= 100 );
		self::minutes( $before );
		self::minutes( $after );
		$seen = array();
		foreach ( $lessons as &$lesson ) {
			self::keys( $lesson, array( 'uuid', 'visit_uuid', 'start', 'end', 'resources' ) );
			self::need( CSF_Scheduling_Audit::uuid( $lesson['uuid'] ) && ! isset( $seen[ $lesson['uuid'] ] ) );
			$seen[ $lesson['uuid'] ] = true;
			self::need( null === $lesson['visit_uuid'] || CSF_Scheduling_Audit::uuid( $lesson['visit_uuid'] ) );
			self::need( is_int( $lesson['start'] ) && is_int( $lesson['end'] ) && $lesson['start'] >= 0 && $lesson['end'] <= 253402214400 && $lesson['end'] > $lesson['start'] && $lesson['end'] - $lesson['start'] <= 86400 );
			self::need( is_array( $lesson['resources'] ) && array_is_list( $lesson['resources'] ) && count( $lesson['resources'] ) <= 100 );
			$resources = array();
			foreach ( $lesson['resources'] as $resource ) {
				self::keys( $resource, array( 'uuid', 'kind', 'mandatory_before', 'mandatory_after', 'shareable' ) );
				self::need( CSF_Scheduling_Audit::uuid( $resource['uuid'] ) && ! isset( $resources[ $resource['uuid'] ] ) && in_array( $resource['kind'], array( 'instructor', 'arena', 'location' ), true ) && is_bool( $resource['shareable'] ) );
				self::minutes( $resource['mandatory_before'] );
				self::minutes( $resource['mandatory_after'] );
				$resources[ $resource['uuid'] ] = $resource;
			}
			ksort( $resources );
			$lesson['resources'] = $resources;
		}
		unset( $lesson );
		// Resource identity/kind must mean the same thing throughout one plan.
		$kinds = array();
		foreach ( $lessons as $lesson ) {
			foreach ( $lesson['resources'] as $uuid => $resource ) {
				self::need( ! isset( $kinds[ $uuid ] ) || $kinds[ $uuid ] === $resource['kind'] );
				$kinds[ $uuid ] = $resource['kind'];
			}
		}
		$plans = array();
		foreach ( $lessons as $lesson ) {
			$left = null;
			$right = null;
			foreach ( $lessons as $other ) {
				if ( ! self::compatible( $lesson, $other ) ) {
					continue;
				}
				if ( $other['end'] === $lesson['start'] ) {
					// Ambiguous branching must not silently share a buffer.
					self::need( null === $left );
					$left = $other['uuid'];
				}
				if ( $other['start'] === $lesson['end'] ) {
					self::need( null === $right );
					$right = $other['uuid'];
				}
			}
			$windows = array();
			foreach ( $lesson['resources'] as $uuid => $resource ) {
				$pre = max( null === $left ? $before : 0, $resource['mandatory_before'] );
				$post = max( null === $right ? $after : 0, $resource['mandatory_after'] );
				$windows[] = array(
					'resource_uuid' => $uuid,
					'start' => $lesson['start'] - $pre * 60,
					'end' => $lesson['end'] + $post * 60,
					'pre_minutes' => $pre,
					'post_minutes' => $post,
					'shared_before' => $left,
					'shared_after' => $right,
				);
			}
			$plans[] = array( 'uuid' => $lesson['uuid'], 'visit_uuid' => $lesson['visit_uuid'], 'teaching_start' => $lesson['start'], 'teaching_end' => $lesson['end'], 'reservations' => $windows );
		}
		$conflicts = array();
		foreach ( $plans as $i => $a ) {
			foreach ( array_slice( $plans, $i + 1 ) as $b ) {
				$by_resource = array_column( $b['reservations'], null, 'resource_uuid' );
				foreach ( $a['reservations'] as $x ) {
					$y = $by_resource[ $x['resource_uuid'] ] ?? null;
					if ( $y && $x['start'] < $y['end'] && $y['start'] < $x['end'] ) {
						$conflicts[] = array( 'first' => $a['uuid'], 'second' => $b['uuid'], 'resource_uuid' => $x['resource_uuid'] );
					}
				}
			}
		}
		return array( 'lessons' => $plans, 'conflicts' => $conflicts );
	}

	/** Same explicit visit and complete compatible staff/location footprint. */
	private static function compatible( $a, $b ) {
		if ( $a['uuid'] === $b['uuid'] || null === $a['visit_uuid'] || $a['visit_uuid'] !== $b['visit_uuid'] || array_keys( $a['resources'] ) !== array_keys( $b['resources'] ) ) {
			return false;
		}
		$kinds = array_column( $a['resources'], 'kind' );
		if ( ! in_array( 'instructor', $kinds, true ) || ( ! in_array( 'arena', $kinds, true ) && ! in_array( 'location', $kinds, true ) ) ) {
			return false;
		}
		foreach ( $a['resources'] as $uuid => $resource ) {
			if ( ! $resource['shareable'] || ! $b['resources'][ $uuid ]['shareable'] ) {
				return false;
			}
		}
		return true;
	}

	/** Validate exact normalized shapes, without coercion. */
	private static function keys( $value, $keys ) {
		self::need( is_array( $value ) && ! array_diff( array_keys( $value ), $keys ) && ! array_diff( $keys, array_keys( $value ) ) );
	}

	/** Bounded explicit minutes. */
	private static function minutes( $value ) {
		self::need( is_int( $value ) && $value >= 0 && $value <= 1440 );
	}

	/** Reject malformed or ambiguous inputs. */
	private static function need( $ok ) {
		if ( ! $ok ) {
			throw new InvalidArgumentException( 'visit_plan_invalid' );
		}
	}
}
