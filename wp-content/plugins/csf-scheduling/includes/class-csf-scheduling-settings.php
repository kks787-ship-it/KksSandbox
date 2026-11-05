<?php
/** Owner-reviewed facility settings using the existing transactional policy command.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** No enrollment, role, migration or sample-record side effects. */
class CSF_Scheduling_Settings {
	/** Uncommitted form values.
	 *
	 * @var array|null
	 */
	private static $draft = null;
	/** Exact reviewed command.
	 *
	 * @var array|null
	 */
	private static $review = null;
	/** Fixed validation or command outcome.
	 *
	 * @var string|null
	 */
	private static $notice = null;

	/** Strict native form integer; an empty field deliberately stays unconfigured.
	 *
	 * @param mixed $value Posted number.
	 * @return int|null
	 * @throws RuntimeException Malformed number.
	 */
	private static function integer( $value ) {
		if ( '' === $value ) {
			return null;
		}
		if ( ! is_string( $value ) || ! preg_match( '/^(0|[1-9][0-9]{0,5})$/D', $value ) ) {
			throw new RuntimeException( 'Invalid number.' );
		}
		return (int) $value;
	}

	/** Checkboxes never accept PHP truthiness or arrays.
	 *
	 * @param array  $row Posted fields.
	 * @param string $key Checkbox name.
	 * @return bool
	 * @throws RuntimeException Malformed checkbox.
	 */
	private static function checked( $row, $key ) {
		if ( ! isset( $row[ $key ] ) ) {
			return false;
		}
		if ( '1' !== $row[ $key ] ) {
			throw new RuntimeException( 'Invalid checkbox.' );
		}
		return true;
	}

	/** Parse ordinary fields; the domain validator remains authoritative.
	 *
	 * @param mixed $form Posted settings.
	 * @return array
	 * @throws RuntimeException Malformed native fields.
	 */
	public static function parse( $form ) {
		if ( ! is_array( $form ) || ! isset( $form['hours'], $form['services'], $form['academy'] ) || ! is_array( $form['hours'] ) || ! is_array( $form['services'] ) || ! is_array( $form['academy'] ) || count( $form['services'] ) > 31 || array_keys( $form['hours'] ) !== range( 0, 6 ) ) {
			throw new RuntimeException( 'Invalid settings fields.' );
		}
		if ( isset( $form['timezone'] ) && ! is_string( $form['timezone'] ) ) {
			throw new RuntimeException( 'Invalid timezone.' );
		}
		$p                  = CSF_Scheduling_Policy::defaults();
		$p['timezone']      = '' === ( $form['timezone'] ?? '' ) ? null : $form['timezone'];
		$p['service_hours'] = array();
		foreach ( $form['hours'] as $day => $rows ) {
			if ( ! is_array( $rows ) || count( $rows ) > 24 ) {
				throw new RuntimeException( 'Invalid hours.' );
			}
			$p['service_hours'][ $day ] = array();
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['start'], $row['end'] ) || ! is_string( $row['start'] ) || ! is_string( $row['end'] ) ) {
					throw new RuntimeException( 'Invalid hours.' );
				}
				if ( '' !== $row['start'] || '' !== $row['end'] ) {
					$p['service_hours'][ $day ][] = array( $row['start'], $row['end'] );
				}
			}
		}
		foreach ( array( 'daily_minutes', 'consecutive_minutes', 'break_minutes' ) as $key ) {
			$p['workload'][ $key ] = self::integer( $form[ $key ] ?? '' );
		}
		foreach ( array( 'training_horse_cap', 'preferred_offsite_day', 'travel_buffer_minutes' ) as $key ) {
			$p[ $key ] = self::integer( $form[ $key ] ?? '' );
		}
		foreach ( $form['services'] as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['name'] ) || ! is_string( $row['name'] ) || strlen( $row['name'] ) > 60 ) {
				throw new RuntimeException( 'Invalid service.' );
			}
			if ( '' === trim( $row['name'] ) ) {
				continue;
			}
			$key = $row['key'] ?? strtolower( str_replace( ' ', '_', trim( $row['name'] ) ) );
			if ( ! is_string( $key ) || isset( $p['services'][ $key ] ) || ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/D', $key ) ) {
				throw new RuntimeException( 'Use a unique service name with letters and spaces.' );
			}
			if ( ! isset( $row['horse_source'] ) || ! is_string( $row['horse_source'] ) || ( isset( $row['resources'] ) && ( ! is_array( $row['resources'] ) || ! array_is_list( $row['resources'] ) || count( array_filter( $row['resources'], 'is_string' ) ) !== count( $row['resources'] ) ) ) ) {
				throw new RuntimeException( 'Invalid service choices.' );
			}
			$p['services'][ $key ] = array(
				'enabled'            => self::checked( $row, 'enabled' ),
				'capacity'           => self::integer( $row['capacity'] ?? '' ),
				'duration_minutes'   => self::integer( $row['duration_minutes'] ?? '' ),
				'horse_source'       => $row['horse_source'] ?? '',
				'requires_travel'    => self::checked( $row, 'requires_travel' ),
				'required_resources' => $row['resources'] ?? array(),
			);
		}
		$p['academy'] = array( 'enabled' => self::checked( $form['academy'], 'enabled' ) );
		if ( $p['academy']['enabled'] ) {
			foreach ( array( 'weekday', 'capacity' ) as $key ) {
				$p['academy'][ $key ] = self::integer( $form['academy'][ $key ] ?? '' );
			}
			foreach ( array( 'start', 'end', 'season_start', 'season_end' ) as $key ) {
				if ( isset( $form['academy'][ $key ] ) && ! is_string( $form['academy'][ $key ] ) ) {
					throw new RuntimeException( 'Invalid Academy time.' );
				}
				$p['academy'][ $key ] = $form['academy'][ $key ] ?? '';
			}
		}
		return $p;
	}

	/** Synthetic example only; loading it never saves or adds records. */
	public static function pilot() {
		$p                          = CSF_Scheduling_Policy::defaults();
		$p['timezone']              = 'America/Los_Angeles';
		$p['service_hours']         = array( array(), array( array( '08:00', '18:00' ) ), array( array( '08:00', '18:00' ) ), array( array( '08:00', '18:00' ) ), array( array( '08:00', '18:00' ) ), array( array( '08:00', '18:00' ) ), array( array( '08:00', '18:00' ) ) );
		$p['workload']              = array(
			'daily_minutes'       => 120,
			'consecutive_minutes' => 30,
			'break_minutes'       => 15,
		);
		$p['training_horse_cap']    = 3;
		$p['travel_buffer_minutes'] = 60;
		$p['preferred_offsite_day'] = 3;
		$p['academy']               = array(
			'enabled'      => true,
			'weekday'      => 1,
			'start'        => '09:00',
			'end'          => '10:00',
			'season_start' => '2027-01-25',
			'season_end'   => '2027-01-30',
			'capacity'     => 4,
		);
		foreach ( array(
			'pilot_private' => array( 1, 30, 'school', false, array( 'instructor', 'arena', 'horse' ) ),
			'pilot_group'   => array( 4, 60, 'school', false, array( 'instructor', 'arena', 'horse' ) ),
			'pilot_offsite' => array( 1, 60, 'client', true, array( 'instructor', 'location' ) ),
		) as $key => $spec ) {
			$p['services'][ $key ] = array(
				'enabled'            => true,
				'capacity'           => $spec[0],
				'duration_minutes'   => $spec[1],
				'horse_source'       => $spec[2],
				'requires_travel'    => $spec[3],
				'required_resources' => $spec[4],
			);
		}
		return $p;
	}

	/** Session-bound tamper check for the exact reviewed payload/revision/UUID.
	 *
	 * @param array $review Exact reviewed command.
	 * @return string
	 */
	private static function signature( $review ) {
		return hash_hmac( 'sha256', wp_json_encode( CSF_Scheduling_Store::canonical( $review ) ) . ':' . get_current_user_id(), wp_salt( 'nonce' ) );
	}

	/** Nonce and existing policy permission precede any parsing or writes.
	 *
	 * @throws RuntimeException Caught for malformed or stale form input.
	 */
	public static function post() {
		if ( ! isset( $_POST['csf_facility_settings'] ) ) {
			return;
		}
		if ( ! CSF_Scheduling_Access::can( 'policy_manage' ) ) {
			wp_die( 'Facility policy permission and verified MFA required.', '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'csf_facility_settings' );
		try {
			$action  = is_string( $_POST['csf_facility_settings'] ) ? sanitize_key( wp_unslash( $_POST['csf_facility_settings'] ) ) : '';
			$current = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
			if ( 'pilot' === $action ) {
				self::$draft  = self::pilot();
				self::$notice = 'Fictional example loaded for review only. Nothing has been saved. These are not actual farm policies.';
				return;
			}
			if ( 'review' === $action ) {
				$revision = isset( $_POST['facility_revision'] ) && is_string( $_POST['facility_revision'] ) ? sanitize_text_field( wp_unslash( $_POST['facility_revision'] ) ) : '';
				if ( ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $revision ) || (int) CSF_Scheduling_Schema::settings()['revision'] !== (int) $revision ) {
					throw new RuntimeException( 'Settings changed. Reload before reviewing again.' );
				}
				self::$draft = self::parse( isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : null ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed fields and domain validation.
				if ( array_diff( array_keys( $current['services'] ), array_keys( self::$draft['services'] ) ) ) {
					throw new RuntimeException( 'Existing services cannot be removed here. Disable a service to retain its history.' );
				}
				$clean = CSF_Scheduling_Policy::validate( self::$draft );
				if ( is_wp_error( $clean ) ) {
					self::$notice = $clean->get_error_message();
					return;
				}
				self::$review = array(
					'policy'   => $clean,
					'revision' => (int) $revision,
					'command'  => wp_generate_uuid4(),
				);
				return;
			}
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Bound signed document, decoded and validated below.
			if ( 'apply' !== $action || ! isset( $_POST['review'], $_POST['signature'], $_POST['reviewed'] ) || '1' !== $_POST['reviewed'] || ! is_string( $_POST['review'] ) || strlen( wp_unslash( $_POST['review'] ) ) > 65536 || ! is_string( $_POST['signature'] ) ) {
				throw new RuntimeException( 'Review the settings before saving.' );
			}
			$r = json_decode( wp_unslash( $_POST['review'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Signed exact review and strict domain validation.
			if ( ! is_array( $r ) || array_keys( $r ) !== array( 'policy', 'revision', 'command' ) || ! is_int( $r['revision'] ) || ! CSF_Scheduling_Audit::uuid( $r['command'] ) || ! hash_equals( self::signature( $r ), wp_unslash( $_POST['signature'] ) ) ) {
				throw new RuntimeException( 'This review changed. Review the settings again.' );
			}
			$result       = CSF_Scheduling_Store::configure( $r['policy'], $r['revision'], $r['command'] );
			self::$notice = 'Facility settings committed and audited.';
			if ( is_wp_error( $result ) ) {
				$reason      = $result->get_error_data()['reason'] ?? $result->get_error_code();
				self::$draft = $r['policy'];
				if ( 'stale_revision' === $reason ) {
					self::$notice = 'Settings changed in another tab. Your draft is retained. Review it against the latest settings before saving.';
				} else {
					self::$notice = 'Settings not confirmed. Keep this exact review and retry it after checking your session or storage. Do not start another change if the result is uncertain.';
					self::$review = $r;
				}
			}
		} catch ( RuntimeException $e ) {
			self::$notice = $e->getMessage();
		}
	}

	/** Accessible native field with a visible label.
	 *
	 * @param string $name Field name.
	 * @param string $label Visible label.
	 * @param mixed  $value Scalar field value.
	 * @param string $type Native input type.
	 * @param string $extra Static attributes.
	 */
	private static function field( $name, $label, $value, $type = 'text', $extra = '' ) {
		echo '<label>' . esc_html( $label ) . ' <input name="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( null === $value ? '' : $value ) . '" ' . $extra . '></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Extra attributes are static call-site literals.
	}

	/** Native named select.
	 *
	 * @param string $name Field name.
	 * @param string $label Visible label.
	 * @param mixed  $value Selected scalar.
	 * @param array  $choices Reviewed choices.
	 */
	private static function select( $name, $label, $value, $choices ) {
		echo '<label>' . esc_html( $label ) . ' <select name="' . esc_attr( $name ) . '">';
		foreach ( $choices as $key => $text ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( (string) $value, (string) $key, false ) . '>' . esc_html( $text ) . '</option>';
		}
		echo '</select></label>';
	}

	/** Readable review; never display an editable structured document.
	 *
	 * @param array $p Valid policy.
	 * @return array
	 */
	public static function summary( $p ) {
		$days = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
		$out  = array( 'Timezone' => $p['timezone'] ?? 'Not configured' );
		foreach ( array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ) as $day => $label ) {
			$out[ $label ] = null === $p['service_hours'] ? 'Not configured' : ( $p['service_hours'][ $day ] ? implode( ', ', array_map( static fn( $r ) => $r[0] . '–' . $r[1], $p['service_hours'][ $day ] ) ) : 'Closed' );
		}
		foreach ( $p['workload'] as $key => $value ) {
			$out[ 'Horse ' . str_replace( '_', ' ', $key ) ] = null === $value ? 'Not configured' : (string) $value;
		}
		$out['Maximum full-training horses'] = null === $p['training_horse_cap'] ? 'Not configured' : (string) $p['training_horse_cap'];
		$out['Preferred off-site day']       = null === $p['preferred_offsite_day'] ? 'Not configured' : $days[ $p['preferred_offsite_day'] ];
		$out['Travel buffer minutes']        = null === $p['travel_buffer_minutes'] ? 'Not configured' : (string) $p['travel_buffer_minutes'];
		$out['Horse Academy']                = empty( $p['academy']['enabled'] ) ? ( null === $p['academy'] ? 'Not configured' : 'Disabled' ) : $days[ $p['academy']['weekday'] ] . ', ' . $p['academy']['start'] . '–' . $p['academy']['end'] . ', ' . $p['academy']['season_start'] . ' to ' . $p['academy']['season_end'] . ', capacity ' . $p['academy']['capacity'];
		foreach ( $p['services'] as $key => $s ) {
			$out[ 'Service: ' . str_replace( '_', ' ', $key ) ] = ( $s['enabled'] ? 'Enabled' : 'Disabled' ) . '; capacity ' . ( $s['capacity'] ?? 'unset' ) . '; ' . ( $s['duration_minutes'] ?? 'unset' ) . ' minutes; ' . $s['horse_source'] . ' horses; resources ' . ( null === $s['required_resources'] ? 'not configured' : ( $s['required_resources'] ? implode( ', ', $s['required_resources'] ) : 'none (explicit)' ) ) . '; travel ' . ( $s['requires_travel'] ? 'yes' : 'no' );
		}
		return $out;
	}

	/** Owner settings appear before appointment forms; loading a sample never applies it. */
	public static function render() {
		if ( ! CSF_Scheduling_Access::can( 'policy_manage' ) ) {
			return;
		}
		$current = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
		$p       = self::$draft ?? $current;
		$days    = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
		echo '<section id="csf-facility-settings"><h2>Facility settings</h2><p>These settings control scheduling for this facility. Empty hours mean closed. Blank limits stay unconfigured. Initial pilot examples are fictional, not approved farm operating policies.</p>';
		if ( self::$notice ) {
			echo '<p role="status">' . esc_html( self::$notice ) . '</p>';
		}
		if ( self::$review ) {
			$r = self::$review;
			echo '<h3>Review facility changes</h3><div class="csf-policy-table"><table class="widefat striped"><thead><tr><th>Setting</th><th>Current</th><th>Proposed</th></tr></thead><tbody>';
			$old = self::summary( $current );
			foreach ( self::summary( $r['policy'] ) as $label => $text ) {
				echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $old[ $label ] ?? 'Not configured' ) . '</td><td>' . esc_html( $text ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
			$missing = CSF_Scheduling_Policy::missing( $r['policy'] );
			if ( $missing ) {
				echo '<p role="alert">Scheduling will remain blocked until all required settings are complete.</p>';
			}
			echo '<form method="post" class="csf-policy-review">';
			wp_nonce_field( 'csf_facility_settings' );
			echo '<input type="hidden" name="csf_facility_settings" value="apply"><input type="hidden" name="review" value="' . esc_attr( wp_json_encode( $r ) ) . '"><input type="hidden" name="signature" value="' . esc_attr( self::signature( $r ) ) . '"><label><input type="checkbox" name="reviewed" value="1" required> I reviewed these exact facility settings.</label><button type="submit" class="button button-primary">Apply reviewed settings</button><p>For an uncertain result, retry this exact form rather than starting another change.</p></form>';
		}
		echo '<details class="csf-policy-editor"' . ( null !== self::$draft || CSF_Scheduling_Policy::missing( $current ) ? ' open' : '' ) . '><summary>Edit facility settings</summary><form method="post" class="csf-policy-example">';
		wp_nonce_field( 'csf_facility_settings' );
		echo '<button class="button" name="csf_facility_settings" value="pilot">Load fictional pilot example (not saved)</button></form><form method="post" class="csf-policy-form">';
		wp_nonce_field( 'csf_facility_settings' );
		echo '<input type="hidden" name="csf_facility_settings" value="review"><input type="hidden" name="facility_revision" value="' . esc_attr( CSF_Scheduling_Schema::settings()['revision'] ) . '">';
		echo '<button type="submit" class="button button-primary">Review facility settings</button>';
		self::select( 'settings[timezone]', 'Facility timezone', $p['timezone'], array( '' => 'Choose timezone' ) + array_combine( timezone_identifiers_list(), timezone_identifiers_list() ) );
		echo '<fieldset><legend>Weekly opening hours</legend><p>Leave both times blank for a closed day. Use the additional row for a split day.</p>';
		foreach ( $days as $day => $label ) {
			$rows   = $p['service_hours'][ $day ] ?? array();
			$rows[] = array( '', '' );
			echo '<fieldset><legend>' . esc_html( $label ) . '</legend>';
			foreach ( $rows as $i => $range ) {
				self::field( 'settings[hours][' . $day . '][' . $i . '][start]', 'Open', $range[0], 'time' );
				self::field( 'settings[hours][' . $day . '][' . $i . '][end]', 'Close', $range[1], 'time' );
			}
			echo '</fieldset>';
		}
		echo '</fieldset><fieldset><legend>Horse workload and travel</legend>';
		foreach ( array(
			'daily_minutes'       => 'Maximum horse minutes per day',
			'consecutive_minutes' => 'Maximum continuous horse minutes',
			'break_minutes'       => 'Minimum horse recovery minutes',
		) as $key => $label ) {
			self::field( 'settings[' . $key . ']', $label, $p['workload'][ $key ], 'number', 'min="0" max="1440"' );
		}
		self::field( 'settings[training_horse_cap]', 'Maximum full-training horses', $p['training_horse_cap'], 'number', 'min="0" max="10000"' );
		self::field( 'settings[travel_buffer_minutes]', 'Travel buffer minutes', $p['travel_buffer_minutes'], 'number', 'min="0" max="1440"' );
		self::select( 'settings[preferred_offsite_day]', 'Preferred off-site day', $p['preferred_offsite_day'], array( '' => 'Not configured' ) + $days );
		echo '</fieldset><fieldset><legend>Horse Academy</legend>';
		self::field( 'settings[academy][enabled]', 'Enable Horse Academy', '1', 'checkbox', checked( ! empty( $p['academy']['enabled'] ), true, false ) );
		self::select( 'settings[academy][weekday]', 'Academy day', $p['academy']['weekday'] ?? '', array( '' => 'Choose day' ) + $days );
		foreach ( array(
			'start'        => array( 'Academy start', 'time' ),
			'end'          => array( 'Academy end', 'time' ),
			'season_start' => array( 'Season starts', 'date' ),
			'season_end'   => array( 'Season ends', 'date' ),
			'capacity'     => array( 'Academy capacity', 'number' ),
		) as $key => $spec ) {
			self::field( 'settings[academy][' . $key . ']', $spec[0], $p['academy'][ $key ] ?? '', $spec[1] );
		}
		echo '</fieldset><fieldset><legend>Lesson and service types</legend><p>Disable an existing service to preserve its identity. A new service name uses letters and spaces.</p>';
		$services     = $p['services'];
		$services[''] = array(
			'enabled'            => false,
			'capacity'           => null,
			'duration_minutes'   => null,
			'horse_source'       => 'school',
			'requires_travel'    => false,
			'required_resources' => array(),
		);
		$i            = 0;
		foreach ( $services as $key => $s ) {
			$name = 'settings[services][' . ( $i++ ) . ']';
			echo '<fieldset><legend>' . esc_html( '' === $key ? 'Add a service' : ucwords( str_replace( '_', ' ', $key ) ) ) . '</legend>';
			if ( '' !== $key ) {
				echo '<input type="hidden" name="' . esc_attr( $name . '[key]' ) . '" value="' . esc_attr( $key ) . '">';
			}
			self::field( $name . '[name]', 'Service name', ucwords( str_replace( '_', ' ', $key ) ), 'text', '' !== $key ? 'readonly' : '' );
			self::field( $name . '[enabled]', 'Enable service', '1', 'checkbox', checked( $s['enabled'], true, false ) );
			self::field( $name . '[capacity]', 'Rider capacity', $s['capacity'], 'number', 'min="1" max="100"' );
			self::field( $name . '[duration_minutes]', 'Duration in minutes', $s['duration_minutes'], 'number', 'min="1" max="1440"' );
			self::select(
				$name . '[horse_source]',
				'Horse source',
				$s['horse_source'],
				array(
					'school' => 'School horses',
					'client' => 'Client-owned horses',
					'either' => 'Either',
					'none'   => 'No horse',
				)
			);
			self::field( $name . '[requires_travel]', 'Include travel buffer', '1', 'checkbox', checked( $s['requires_travel'], true, false ) );
			foreach ( array( 'instructor', 'horse', 'arena', 'location' ) as $kind ) {
				self::field( $name . '[resources][]', 'Require ' . $kind, $kind, 'checkbox', checked( in_array( $kind, $s['required_resources'] ?? array(), true ), true, false ) );
			}
			echo '</fieldset>';
		}
		echo '</fieldset><button type="submit" class="button button-primary">Review changes</button></form></details></section>';
	}
}
