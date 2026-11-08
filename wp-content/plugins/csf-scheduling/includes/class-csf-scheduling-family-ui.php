<?php
/** Native itemized family review. No raw JSON editor or independent writer.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
/** Coordinate this private scheduling operation through existing access and transaction boundaries. */
class CSF_Scheduling_Family_UI {
	/** Native draft retained after validation failure.
	 *
	 * @var array|null
	 */
	private static $draft = null;
	/** Exact signed proposal awaiting confirmation.
	 *
	 * @var array|null
	 */
	private static $review = null;
	/** Current request outcome text.
	 *
	 * @var string
	 */
	private static $notice = '';
	/** Whether this request received commit acknowledgement.
	 *
	 * @var bool
	 */
	private static $committed = false;
	/** Read a bounded string form field.
	 *
	 * @param array  $a Normalized lesson or submitted field values.
	 * @param string $key Field key.
	 */
	private static function value( $a, $key ) {
		$v = $a[ $key ] ?? '';
		CSF_Scheduling_Calendar::require( is_string( $v ) && strlen( $v ) <= 400 );
		return $v;
	}
	/** Bind the exact reviewed payload to this actor and session.
	 *
	 * @param array $r Exact reviewed proposal.
	 */
	private static function signature( $r ) {
		return hash_hmac( 'sha256', wp_json_encode( CSF_Scheduling_Store::canonical( $r ) ) . ':' . get_current_user_id() . ':' . wp_get_session_token(), wp_salt( 'nonce' ) );
	}
	/** Parse compact routine fields; service duration is still server-derived.
	 *
	 * @param array $input Submitted native fields.
	 * @param array $p Facility policy.
	 */
	public static function parse( $input, $p ) {
		CSF_Scheduling_Calendar::require( is_array( $input['lessons'] ?? null ) && array_is_list( $input['lessons'] ) && count( $input['lessons'] ) > 0 && count( $input['lessons'] ) <= 20 );
		$expected = self::value( $input, 'visit_expected' );
		CSF_Scheduling_Calendar::require( preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $expected ) && '1' === self::value( $input, 'same_family' ) );
		$state = ( '' !== self::value( $input, 'visit_state' ) ) ? self::value( $input, 'visit_state' ) : 'confirmed';
		CSF_Scheduling_Calendar::require( in_array( $state, array( 'requested', 'held', 'confirmed' ), true ), 'visit_state' );
		$hold = null;
		if ( 'held' === $state ) {
			$local  = self::value( $input, 'visit_expiry' );
			$offset = self::value( $input, 'expiry_choice' );
			CSF_Scheduling_Calendar::require( '' === $offset || preg_match( '/^[+-][0-9]{2}:[0-9]{2}$/D', $offset ), 'invalid_time' );
			$hold = CSF_Scheduling_Time::resolve(
				array(
					'local'  => $local,
					'choice' => '' === $offset ? null : $local . $offset,
				),
				$p['timezone']
			);
		}
		$body = array(
			'uuid'        => self::value( $input, 'visit_uuid' ),
			'expected'    => (int) $expected,
			'same_family' => true,
			'items'       => array(),
		);
		foreach ( $input['lessons'] as $r ) {
			CSF_Scheduling_Calendar::require( is_array( $r ) );
			foreach ( array( 'title', 'service_type', 'rider', 'horse', 'start', 'choice', 'reason' ) as $field ) {
				self::value( $r, $field ); }
			CSF_Scheduling_Calendar::require( is_array( $r['resources'] ?? array() ) && array_is_list( $r['resources'] ?? array() ) );
			foreach ( $r['resources'] ?? array() as $id ) {
				CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $id ) ); }
			$rev = self::value( $r, 'expected' );
			CSF_Scheduling_Calendar::require( preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $rev ) );
			$b      = array(
				'uuid'     => self::value( $r, 'uuid' ),
				'expected' => (int) $rev,
			);
			$cancel = self::value( $r, 'cancel' );
			CSF_Scheduling_Calendar::require( in_array( $cancel, array( '', '1' ), true ) );
			if ( '1' === $cancel ) {
				$now             = ( new DateTimeImmutable( 'now', new DateTimeZone( $p['timezone'] ) ) )->format( 'Y-m-d\TH:iP' );
				$b              += array(
					'initiator' => 'staff',
					'reason'    => self::value( $r, 'reason' ),
					'notice'    => $now,
					'effective' => $now,
				);
				$body['items'][] = array(
					'kind' => 'appointment_canceled',
					'body' => $b,
				);
				continue;
			}
			$resources = $r['resources'] ?? array();
			CSF_Scheduling_Calendar::require( is_array( $resources ) && array_is_list( $resources ) && count( $resources ) <= 100 );
			foreach ( $resources as $id ) {
				CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $id ) );
			}
			$local  = self::value( $r, 'start' );
			$offset = self::value( $r, 'choice' );
			CSF_Scheduling_Calendar::require( '' === $offset || preg_match( '/^[+-][0-9]{2}:[0-9]{2}$/D', $offset ), 'invalid_time' );
			$b += array(
				'title'        => self::value( $r, 'title' ),
				'service_type' => self::value( $r, 'service_type' ),
				'state'        => $state,
				'start'        => array(
					'local'  => $local,
					'choice' => '' === $offset ? null : $local . $offset,
				),
				'end'          => null,
				'hold_expires' => $hold,
				'resources'    => $resources,
				'riders'       => array(),
				'rides'        => array(),
				'_routine'     => true,
				'single_rider' => self::value( $r, 'rider' ),
				'single_horse' => ( '' !== self::value( $r, 'horse' ) ) ? self::value( $r, 'horse' ) : null,
			);
			if ( '1' === self::value( $r, 'advanced' ) ) {
				$service = $p['services'][ $b['service_type'] ] ?? null;
				CSF_Scheduling_Calendar::require( $service && $service['enabled'], 'lesson_service' );
				$b['start'] = CSF_Scheduling_Time::resolve( $b['start'], $p['timezone'] );
				$start      = CSF_Scheduling_Time::parse( $b['start'], $p['timezone'] );
				CSF_Scheduling_Calendar::require( 0 === (int) $start->format( 'i' ) % 15, 'lesson_grid' );
				$b['end']    = $start->setTimestamp( $start->getTimestamp() + 60 * $service['duration_minutes'] )->format( 'Y-m-d\TH:iP' );
				$b['riders'] = $r['participants'] ?? array();
				CSF_Scheduling_Calendar::require( is_array( $b['riders'] ) && array_is_list( $b['riders'] ) && count( $b['riders'] ) <= 100 );
				foreach ( $b['riders'] as $id ) {
					CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $id ) ); }
				$turns = $r['turns'] ?? array();
				CSF_Scheduling_Calendar::require( is_array( $turns ) && array_is_list( $turns ) && count( $turns ) <= 20 );
				foreach ( $turns as $turn ) {
					CSF_Scheduling_Calendar::require( is_array( $turn ) );
					$ride = array(
						'horse' => self::value( $turn, 'horse' ),
						'rider' => ( '' !== self::value( $turn, 'rider' ) ) ? self::value( $turn, 'rider' ) : null,
					);
					CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $ride['horse'] ) && ( null === $ride['rider'] || CSF_Scheduling_Audit::uuid( $ride['rider'] ) ) );
					foreach ( array( 'start', 'end' ) as $edge ) {
						$local  = self::value( $turn, $edge );
						$offset = self::value( $turn, $edge . '_choice' );
						CSF_Scheduling_Calendar::require( '' === $offset || preg_match( '/^[+-][0-9]{2}:[0-9]{2}$/D', $offset ), 'invalid_time' );
						$ride[ $edge ] = CSF_Scheduling_Time::resolve(
							array(
								'local'  => $local,
								'choice' => '' === $offset ? null : $local . $offset,
							),
							$p['timezone']
						); }
					$b['rides'][] = $ride;
				}
				unset( $b['_routine'], $b['single_rider'], $b['single_horse'] );
			} else {
				$b = CSF_Scheduling_Booking::derive( $b, $p ); }
			$body['items'][] = array(
				'kind' => 'appointment_saved',
				'body' => $b,
			);
		}
		return $body;
	}
	/** Process the nonce-protected submitted command. */
	public static function post() {
		if ( ! isset( $_POST['csf_family_action'] ) ) {
			return; }
		if ( ! CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
			wp_die( 'Booking permission and verified MFA required.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'csf_family_review' );
		try {
			$input  = wp_unslash( $_POST ); // Exact bounded fields; signed immutable review on apply.
			$action = self::value( $input, 'csf_family_action' );
			if ( preg_match( '/^remove:([0-9]{1,2})$/D', $action, $m ) ) {
				$input['remove_index'] = $m[1];
				$action                = 'remove'; }
			if ( preg_match( '/^(turn|dropturn):([0-9]{1,2})(?::([0-9]{1,2}))?$/D', $action, $m ) ) {
				$input['turn_lesson'] = $m[2];
				$input['turn_index']  = $m[3] ?? '';
				$action               = $m[1]; }
			if ( in_array( $action, array( 'turn', 'dropturn', 'add', 'review', 'attach', 'remove' ), true ) ) {
				CSF_Scheduling_Calendar::require( strlen( wp_json_encode( $input ) ) <= 65536 );
				$s   = CSF_Scheduling_Schema::settings();
				$p   = json_decode( $s['policy_json'], true );
				$rev = self::value( $input, 'family_revision' );
				CSF_Scheduling_Calendar::require( preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $rev ) && (int) $rev === (int) $s['revision'], 'stale_revision' );
				self::$draft = $input;
				CSF_Scheduling_Calendar::require( is_array( $input['lessons'] ?? null ) && array_is_list( $input['lessons'] ) && count( $input['lessons'] ) <= 20 );
				if ( in_array( $action, array( 'turn', 'dropturn' ), true ) ) {
					$i = (int) $input['turn_lesson'];
					CSF_Scheduling_Calendar::require( isset( $input['lessons'][ $i ] ) );
					$turns = $input['lessons'][ $i ]['turns'] ?? array();
					CSF_Scheduling_Calendar::require( is_array( $turns ) && array_is_list( $turns ) && count( $turns ) <= 20 );
					if ( 'turn' === $action ) {
						CSF_Scheduling_Calendar::require( count( $turns ) < 20 );
						$turns[] = array(
							'horse'        => '',
							'rider'        => '',
							'start'        => $input['lessons'][ $i ]['start'] ?? '',
							'end'          => '',
							'start_choice' => '',
							'end_choice'   => '',
						); } else {
						CSF_Scheduling_Calendar::require( ctype_digit( $input['turn_index'] ) && isset( $turns[ (int) $input['turn_index'] ] ) );
						array_splice( $turns, (int) $input['turn_index'], 1 ); }
						self::$draft['lessons'][ $i ]['advanced'] = '1';
						self::$draft['lessons'][ $i ]['turns']    = $turns;
						self::$notice                             = 'Unsaved horse turns updated. Review all riders, intervals and welfare checks before save.';
						return;
				}
				if ( 'remove' === $action ) {
					$index = self::value( $input, 'remove_index' );
					CSF_Scheduling_Calendar::require( ctype_digit( $index ) && isset( $input['lessons'][ (int) $index ] ) && '0' === self::value( $input['lessons'][ (int) $index ], 'expected' ) && count( $input['lessons'] ) > 1 );
					array_splice( self::$draft['lessons'], (int) $index, 1 );
					self::$notice = 'Unsaved lesson removed from the draft.';
					return;
				}
				if ( 'attach' === $action ) {
					$id = self::value( $input, 'attach_uuid' );
					CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $id ) && count( $input['lessons'] ) < 20 );
					$a = CSF_Scheduling_Calendar::find( 'appointments', $id );
					CSF_Scheduling_Calendar::require( $a && 'canceled' !== $a['state'] && CSF_Scheduling_Permissions::appointment_visible( $a ), 'forbidden' );
					$b = json_decode( CSF_Scheduling_Calendar::find( 'details', $id )['body_json'], true );
					CSF_Scheduling_Calendar::require( empty( $b['visit_uuid'] ), 'invalid_transition' );
					$b['expected']            = (int) $a['revision'];
					self::$draft['lessons'][] = self::draft_row( $b, $p );
					self::$notice             = 'Existing lesson added to this unsaved draft. Review its full details and the same-family attestation before saving.';
					return;
				}
				$body = self::parse( $input, $p );
				if ( 'add' === $action ) {
					CSF_Scheduling_Calendar::require( count( $body['items'] ) < 20 );
					$last = end( $body['items'] );
					CSF_Scheduling_Calendar::require( 'appointment_saved' === $last['kind'] );
					$copy             = end( $input['lessons'] );
					$copy['uuid']     = wp_generate_uuid4();
					$copy['expected'] = '0';
					$copy['cancel']   = '';
					$copy['reason']   = '';
					$end              = CSF_Scheduling_Time::parse( $last['body']['end'], $p['timezone'] );
					$copy['start']    = $end->format( 'Y-m-d\TH:i' );
					$copy['choice']   = $end->format( 'P' );
					if ( '1' === ( $copy['advanced'] ?? '' ) ) {
						$delta = $end->getTimestamp() - CSF_Scheduling_Time::parse( $last['body']['start'], $p['timezone'] )->getTimestamp();
						foreach ( $last['body']['rides'] as $j => $ride ) {
							foreach ( array( 'start', 'end' ) as $edge ) {
								$t                                        = CSF_Scheduling_Time::parse( $ride[ $edge ], $p['timezone'] );
								$t                                        = $t->setTimestamp( $t->getTimestamp() + $delta );
								$copy['turns'][ $j ][ $edge ]             = $t->format( 'Y-m-d\TH:i' );
								$copy['turns'][ $j ][ $edge . '_choice' ] = $t->format( 'P' ); }
						}
					}
					self::$draft['lessons'][] = $copy;
					self::$notice             = 'Unsaved lesson copied into the next slot. Review its rider, horse and service before saving.';
					return;
				}
				$r      = array(
					'body'     => $body,
					'revision' => (int) $rev,
					'command'  => wp_generate_uuid4(),
					'draft'    => $input,
				);
				$result = CSF_Scheduling_Store::visit_preview( $body, $r['revision'], $r['command'] );
				if ( is_wp_error( $result ) ) {
					self::$notice = CSF_Scheduling_UI::message( $result );
					return; }
				$r['plan']    = $result['plan'];
				self::$review = $r;
				self::$notice = 'All lessons checked together. Nothing is reserved until Save family visit.';
				return;
			}
			CSF_Scheduling_Calendar::require( in_array( $action, array( 'apply', 'revise' ), true ) );
			$raw = $input['family_review'] ?? null;
			CSF_Scheduling_Calendar::require( is_string( $raw ) && strlen( $raw ) <= 65536 );
			$r = json_decode( $raw, true );
			CSF_Scheduling_Calendar::keys( $r, array( 'body', 'revision', 'command', 'plan', 'draft' ) );
			CSF_Scheduling_Calendar::require( hash_equals( self::signature( $r ), self::value( $input, 'family_signature' ) ) );
			if ( 'revise' === $action ) {
				self::$draft  = $r['draft'];
				self::$notice = 'Draft reopened. Review all lessons again before saving.';
				return;
			}
			$result       = CSF_Scheduling_Store::calendar( 'family_visit_saved', $r['body'], $r['revision'], $r['command'] );
			self::$review = $r;
			if ( is_wp_error( $result ) ) {
				self::$notice = CSF_Scheduling_UI::message( $result ) . ' No successful save is confirmed. Retry this exact review only if unchanged; otherwise reopen the visit and review current records.';
				return; }
			self::$committed = true;
			self::$notice    = 'Family visit saved atomically. Each lesson and its state were recorded with history. Requested visits reserve nothing; holds expire at their common expiry. No email was sent.';
		} catch ( Throwable $e ) {
			self::$notice = CSF_Scheduling_UI::message( new WP_Error( $e->getMessage(), 'Family review rejected.' ) ); }
	}
	/** Convert a saved lesson to editable native form fields.
	 *
	 * @param array $b Normalized lesson or submitted field values.
	 * @param array $p Facility policy.
	 */
	private static function draft_row( $b, $p ) {
		$time  = CSF_Scheduling_Time::parse( $b['start'], $p['timezone'] );
		$turns = array();
		foreach ( $b['rides'] as $ride ) {
			$turn = array(
				'horse' => $ride['horse'],
				'rider' => $ride['rider'] ?? '',
			);
			foreach ( array( 'start', 'end' ) as $edge ) {
				$t                         = CSF_Scheduling_Time::parse( $ride[ $edge ], $p['timezone'] );
				$turn[ $edge ]             = $t->format( 'Y-m-d\TH:i' );
				$turn[ $edge . '_choice' ] = $t->format( 'P' );
			} $turns[] = $turn; }
		return array(
			'advanced'     => CSF_Scheduling_Booking::simple( $b ) ? '' : '1',
			'participants' => $b['riders'],
			'turns'        => $turns,
			'uuid'         => $b['uuid'],
			'expected'     => (string) $b['expected'],
			'title'        => $b['title'],
			'service_type' => $b['service_type'],
			'rider'        => $b['riders'][0] ?? '',
			'horse'        => $b['rides'][0]['horse'] ?? '',
			'start'        => $time->format( 'Y-m-d\TH:i' ),
			'choice'       => $time->format( 'P' ),
			'resources'    => $b['resources'],
		);
	}
	/** Read-only handoff from an independent lesson. Both rows remain unsaved until review.
	 *
	 * @param string $uuid Stable record UUID.
	 * @param array  $p Facility policy.
	 */
	public static function from_lesson( $uuid, $p ) {
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $uuid ) && CSF_Scheduling_Access::can( 'appointment_edit' ), 'forbidden' );
		$a = CSF_Scheduling_Calendar::find( 'appointments', $uuid );
		CSF_Scheduling_Calendar::require( $a && 'canceled' !== $a['state'] && CSF_Scheduling_Permissions::appointment_visible( $a ), 'forbidden' );
		$b = json_decode( CSF_Scheduling_Calendar::find( 'details', $uuid )['body_json'], true );
		CSF_Scheduling_Calendar::require( empty( $b['visit_uuid'] ), 'visit_required' );
		$b['expected']    = (int) $a['revision'];
		$original         = self::draft_row( $b, $p );
		$copy             = $original;
		$copy['uuid']     = wp_generate_uuid4();
		$copy['expected'] = '0';
		$start            = CSF_Scheduling_Time::parse( $b['start'], $p['timezone'] );
		$end              = CSF_Scheduling_Time::parse( $b['end'], $p['timezone'] );
		$copy['start']    = $end->format( 'Y-m-d\TH:i' );
		$copy['choice']   = $end->format( 'P' );
		$delta            = $end->getTimestamp() - $start->getTimestamp();
		foreach ( $b['rides'] as $j => $ride ) {
			foreach ( array( 'start', 'end' ) as $edge ) {
				$time                                     = CSF_Scheduling_Time::parse( $ride[ $edge ], $p['timezone'] );
				$time                                     = $time->setTimestamp( $time->getTimestamp() + $delta );
				$copy['turns'][ $j ][ $edge ]             = $time->format( 'Y-m-d\TH:i' );
				$copy['turns'][ $j ][ $edge . '_choice' ] = $time->format( 'P' ); }
		}
		$expiry = 'held' === $a['state'] ? CSF_Scheduling_Time::parse( $b['hold_expires'], $p['timezone'] ) : null;
		return array(
			'visit_uuid'     => wp_generate_uuid4(),
			'visit_expected' => '0',
			'visit_state'    => $a['state'],
			'visit_expiry'   => $expiry ? $expiry->format( 'Y-m-d\TH:i' ) : '',
			'expiry_choice'  => $expiry ? $expiry->format( 'P' ) : '',
			'lessons'        => array( $original, $copy ),
		);
	}

	/** Render an escaped native input field.
	 *
	 * @param string $name Input name.
	 * @param string $label Visible field label.
	 * @param mixed  $value Field value validated or escaped by the caller and renderer.
	 * @param string $type Native input type.
	 */
	private static function field( $name, $label, $value, $type = 'text' ) {
		echo '<label>' . esc_html( $label ) . ' <input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"></label> ';
	}
	/** Render an escaped select field and its current choice.
	 *
	 * @param string $name Input name.
	 * @param string $label Visible field label.
	 * @param array  $options Allowed option values and labels.
	 * @param mixed  $value Field value validated or escaped by the caller and renderer.
	 */
	private static function select( $name, $label, $options, $value ) {
		echo '<label>' . esc_html( $label ) . ' <select name="' . esc_attr( $name ) . '"><option value="">Choose</option>';
		foreach ( $options as $id => $text ) {
			echo '<option value="' . esc_attr( $id ) . '" ' . selected( $id, $value, false ) . '>' . esc_html( $text ) . '</option>'; }
		echo '</select></label> ';
	}
	/** Compact repeated native fields with one combined immutable final review.
	 *
	 * @param array $ctx Current scoped presentation context.
	 * @param array $p Facility policy.
	 * @param array $snapshot Reviewed facility snapshot.
	 */
	public static function render( $ctx, $p, $snapshot ) {
		if ( ! CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
			wp_die( 'Booking permission required.', '', array( 'response' => 403 ) ); }
		echo '<h2>Family visit</h2><p>Every active lesson shares one state and hold expiry. Requested visits reserve nothing; all held reservations expire together. Each rider keeps an individual lesson. Compatible consecutive lessons share arrival and departure buffers; horse recovery and travel remain mandatory. Saving recomputes standard buffers; prior exception approvals stay in history and any new exception needs a separate review.</p><p role="status">' . esc_html( self::$notice ) . '</p>';
		if ( self::$review ) {
			$r = self::$review;
			echo '<h3>Review every lesson</h3><ul>';
			foreach ( $r['body']['items'] as $item ) {
				$b       = $item['body'];
				$summary = $b;
				if ( 'appointment_canceled' === $item['kind'] ) {
					$stored  = CSF_Scheduling_Calendar::find( 'details', $b['uuid'] );
					$summary = $stored ? json_decode( $stored['body_json'], true ) : $b;
				}
				$labels = array();
				foreach ( $summary['riders'] ?? array() as $id ) {
					$labels[] = $ctx['riders'][ $id ] ?? 'Assigned rider'; }
				foreach ( $summary['rides'] ?? array() as $ride ) {
					$labels[] = $ctx['horses'][ $ride['horse'] ] ?? 'Assigned horse'; }
				foreach ( $summary['resources'] ?? array() as $id ) {
					$labels[] = $ctx['resources'][ $id ] ?? 'Assigned resource'; }
				$service = $p['services'][ $summary['service_type'] ?? '' ] ?? array();
				echo '<li>' . esc_html( ( 'appointment_canceled' === $item['kind'] ? 'CANCEL: ' . $b['reason'] . ' — ' : '' ) . ( $summary['title'] ?? 'Lesson' ) . ' — ' . ( $service['label'] ?? $summary['service_type'] ?? '' ) . ' — ' . ( $summary['state'] ?? '' ) . ' — ' . ( $summary['hold_expires'] ?? '' ) . ' — ' . ( $summary['start'] ?? '' ) . ' to ' . ( $summary['end'] ?? '' ) . ' — ' . implode( ', ', $labels ) ) . '</li>';
			}
			$times    = array();
			$teaching = 0;
			foreach ( $r['plan']['lessons'] as $lesson ) {
				foreach ( $lesson['reservations'] as $window ) {
					$times[] = $window['start'];
					$times[] = $window['end']; }
			}
			foreach ( $r['body']['items'] as $item ) {
				if ( 'appointment_saved' === $item['kind'] ) {
					$teaching += ( strtotime( $item['body']['end'] ) - strtotime( $item['body']['start'] ) ) / 60; }
			}
			if ( $times ) {
				$format = static fn( $t ) => ( new DateTimeImmutable( '@' . $t ) )->setTimezone( new DateTimeZone( $p['timezone'] ) )->format( 'Y-m-d H:i P' );
				echo '<li><strong>' . esc_html( 'Visit span: ' . $format( min( $times ) ) . ' – ' . $format( max( $times ) ) . '; ' . $teaching . ' itemized teaching minutes. Span may include gaps; exact resource reservations are listed below.' ) . '</strong></li>'; }
			echo '</ul><h3>Planned resource windows (requested visits do not reserve)' . ( self::$committed ? '' : ' after save' ) . '</h3><ul>';
			foreach ( $r['plan']['lessons'] as $lesson ) {
				foreach ( $lesson['reservations'] as $w ) {
					$format = static fn( $time ) => ( new DateTimeImmutable( '@' . $time ) )->setTimezone( new DateTimeZone( $p['timezone'] ) )->format( 'Y-m-d H:i P' );
					echo '<li>' . esc_html( ( $ctx['resources'][ $w['resource_uuid'] ] ?? 'Assigned resource' ) . ': ' . $format( $w['start'] ) . ' to ' . $format( $w['end'] ) . ' — preparation ' . $w['pre_minutes'] . ' min, wrap-up ' . $w['post_minutes'] . ' min' ) . '</li>';
				}
			}
			echo '</ul>';
			if ( ! self::$committed ) {
				echo '<form method="post">';
				wp_nonce_field( 'csf_family_review' );
				self::field( 'family_review', '', wp_json_encode( $r ), 'hidden' );
				self::field( 'family_signature', '', self::signature( $r ), 'hidden' );
				echo '<button class="button button-primary" name="csf_family_action" value="apply">Save family visit</button> <button class="button" name="csf_family_action" value="revise">Back to draft</button></form>';
			} else {
				echo '<a class="button" href="' . esc_url( CSF_Scheduling_UX::url( 'family', array( 'visit' => $r['body']['uuid'] ) ) ) . '">Open visit / book another lesson</a>'; }
			return;
		}
		$draft = self::$draft;
		if ( ! $draft ) {
			$draft = array(
				'visit_state'    => CSF_Scheduling_Access::can( 'appointment_confirm' ) ? 'confirmed' : 'requested',
				'visit_expiry'   => '',
				'expiry_choice'  => '',
				'visit_uuid'     => wp_generate_uuid4(),
				'visit_expected' => '0',
				'lessons'        => array(
					array(
						'uuid'         => wp_generate_uuid4(),
						'expected'     => '0',
						'title'        => 'Family lesson',
						'service_type' => '',
						'rider'        => '',
						'horse'        => '',
						'start'        => '',
						'choice'       => '',
						'resources'    => array(),
					),
				),
			);
			$id    = filter_input( INPUT_GET, 'visit', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? ''; // Read-only, scoped below.
			if ( $id ) {
				try {
					$visit                   = CSF_Scheduling_Family_Visit::read( $id );
					$draft['visit_uuid']     = $id;
					$draft['visit_expected'] = (string) $visit['expected'];
					$draft['lessons']        = array();
					foreach ( $visit['items'] as $item ) {
						$b                    = $item['body'];
						$draft['lessons'][]   = self::draft_row( $b, $p );
						$draft['visit_state'] = $b['state'];
						if ( 'held' === $b['state'] ) {
							$expiry                 = CSF_Scheduling_Time::parse( $b['hold_expires'], $p['timezone'] );
							$draft['visit_expiry']  = $expiry->format( 'Y-m-d\TH:i' );
							$draft['expiry_choice'] = $expiry->format( 'P' ); }
					}
					if ( ! $draft['lessons'] ) {
						$draft['lessons'][] = array(
							'uuid'         => wp_generate_uuid4(),
							'expected'     => '0',
							'title'        => 'Family lesson',
							'service_type' => '',
							'rider'        => '',
							'horse'        => '',
							'start'        => '',
							'choice'       => '',
							'resources'    => array(),
						); }
				} catch ( Throwable $e ) {
					echo '<p>Visit unavailable to this session or requires a more detailed editor. No records were changed.</p>';
					return; }
			}
		}
		$from_lesson = filter_input( INPUT_GET, 'from_lesson', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! self::$draft && ! filter_input( INPUT_GET, 'visit', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) && null !== $from_lesson ) {
			try {
				CSF_Scheduling_Calendar::require( is_string( $from_lesson ) );
				$draft = self::from_lesson( $from_lesson, $p );
				echo '<p>Existing lesson and proposed next lesson loaded. Adjust the next rider, horse or service, confirm the same family, then review. Nothing has been saved or moved.</p>'; } catch ( Throwable $e ) {
				echo '<p>The lesson is unavailable to this session. No changes were made.</p>';
				return; }
		}
		$services = array();
		foreach ( $p['services'] as $key => $service ) {
			if ( $service['enabled'] ) {
				$services[ $key ] = ( $service['label'] ?? $key ) . ' — ' . $service['duration_minutes'] . ' min'; }
		}
		echo '<form method="post">';
		wp_nonce_field( 'csf_family_review' );
		self::field( 'visit_uuid', '', $draft['visit_uuid'], 'hidden' );
		self::field( 'visit_expected', '', $draft['visit_expected'], 'hidden' );
		self::field( 'family_revision', '', $snapshot['revision'], 'hidden' );
		self::select(
			'visit_state',
			'State for every active lesson',
			array(
				'requested' => 'Requested — no reservation',
				'held'      => 'Held — one expiry for all lessons',
				'confirmed' => 'Confirmed',
			),
			$draft['visit_state'] ?? 'confirmed'
		);
		echo '<div data-csf-family-expiry>';
		self::field( 'visit_expiry', 'Common hold expiry (' . $p['timezone'] . ')', $draft['visit_expiry'] ?? '', 'datetime-local' );
		self::field( 'expiry_choice', 'Expiry UTC offset if repeated local time', $draft['expiry_choice'] ?? '' );
		echo '</div>';
		foreach ( $draft['lessons'] as $i => $r ) {
			$prefix = 'lessons[' . $i . ']';
			echo '<fieldset><legend>Lesson ' . esc_html( $i + 1 ) . '</legend>';
			foreach ( array( 'uuid', 'expected' ) as $key ) {
				self::field( $prefix . '[' . $key . ']', '', $r[ $key ], 'hidden' ); }
			self::field( $prefix . '[title]', 'Title', $r['title'] );
			self::select( $prefix . '[service_type]', 'Lesson type', $services, $r['service_type'] );
			self::select( $prefix . '[rider]', 'Rider', $ctx['riders'], $r['rider'] );
			self::select( $prefix . '[horse]', 'Horse', $ctx['active_horses'], $r['horse'] );
			self::field( $prefix . '[start]', 'Start (' . $p['timezone'] . ')', $r['start'], 'datetime-local' );
			self::field( $prefix . '[choice]', 'UTC offset for repeated local time, if needed', $r['choice'] );
			echo '<div>Instructor / arena / location: ';
			foreach ( $ctx['resource_rows'] as $resource ) {
				if ( ! $resource['active'] || 'horse' === $resource['kind'] ) {
					continue;
				} echo '<label><input type="checkbox" name="' . esc_attr( $prefix . '[resources][]' ) . '" value="' . esc_attr( $resource['uuid'] ) . '" ' . checked( in_array( $resource['uuid'], $r['resources'] ?? array(), true ), true, false ) . '>' . esc_html( $resource['label'] ) . '</label> '; }
			echo '</div>';
			echo '<details' . ( '1' === ( $r['advanced'] ?? '' ) ? ' open' : '' ) . '><summary>Multiple riders or horse turns</summary><label><input type="checkbox" name="' . esc_attr( $prefix . '[advanced]' ) . '" value="1" ' . checked( $r['advanced'] ?? '', '1', false ) . '>Use these riders and turns instead of the single rider/horse above</label><div>Riders: ';
			foreach ( $ctx['riders'] as $uuid => $label ) {
				echo '<label><input type="checkbox" name="' . esc_attr( $prefix . '[participants][]' ) . '" value="' . esc_attr( $uuid ) . '" ' . checked( in_array( $uuid, $r['participants'] ?? array(), true ), true, false ) . '>' . esc_html( $label ) . '</label> '; }
			echo '</div>';
			foreach ( $r['turns'] ?? array() as $j => $turn ) {
				$tp = $prefix . '[turns][' . $j . ']';
				echo '<fieldset><legend>Horse turn ' . esc_html( $j + 1 ) . '</legend>';
				self::select( $tp . '[horse]', 'Horse', $ctx['active_horses'], $turn['horse'] );
				self::select( $tp . '[rider]', 'Rider (blank only for unmounted work)', $ctx['riders'], $turn['rider'] );
				foreach ( array( 'start', 'end' ) as $edge ) {
					self::field( $tp . '[' . $edge . ']', ucfirst( $edge ), $turn[ $edge ], 'datetime-local' );
					self::field( $tp . '[' . $edge . '_choice]', ucfirst( $edge ) . ' UTC offset if repeated', $turn[ $edge . '_choice' ] ); }
				echo '<button class="button" name="csf_family_action" value="dropturn:' . esc_attr( $i ) . ':' . esc_attr( $j ) . '" formnovalidate>Remove this draft turn</button></fieldset>';
			}
			echo '<button class="button" name="csf_family_action" value="turn:' . esc_attr( $i ) . '" formnovalidate>Add horse turn</button></details>';
			if ( (int) $r['expected'] > 0 ) {
				echo '<label><input type="checkbox" name="' . esc_attr( $prefix . '[cancel]' ) . '" value="1" ' . checked( $r['cancel'] ?? '', '1', false ) . '>Cancel this lesson</label> ';
				self::field( $prefix . '[reason]', 'Cancellation reason', $r['reason'] ?? '' ); }
			if ( '0' === (string) $r['expected'] && count( $draft['lessons'] ) > 1 ) {
				echo '<button class="button" name="csf_family_action" value="remove:' . esc_attr( $i ) . '" formnovalidate>Remove unsaved lesson</button>'; }
			echo '</fieldset>';
		}
		self::field( 'attach_uuid', 'Existing independent lesson ID to add for review', '' );
		echo '<button class="button" name="csf_family_action" value="attach" formnovalidate>Load existing lesson</button>';
		echo '<label><input type="checkbox" name="same_family" value="1" required ' . checked( $draft['same_family'] ?? '', '1', false ) . '>I verified these lessons belong to the same family visit.</label><p><button class="button" name="csf_family_action" value="add">Book another lesson for this family visit</button> <button class="button button-primary" name="csf_family_action" value="review">Review all lessons and buffers</button></p></form>';
	}
}
