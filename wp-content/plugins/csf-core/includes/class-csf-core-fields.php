<?php
/**
 * Small, shared meta box helper for structured content (horses, events).
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves a declared list of fields as post meta prefixed with "_csf_".
 *
 * Field types: text, textarea, date, url, select, checkbox.
 */
class CSF_Core_Fields {

	/**
	 * Read a field value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @return string
	 */
	public static function get( $post_id, $key ) {
		return (string) get_post_meta( $post_id, '_csf_' . $key, true );
	}

	/**
	 * Render fields as a form table.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $fields  Field definitions keyed by field key.
	 * @param string $nonce   Nonce action/name.
	 */
	public static function render( $post_id, array $fields, $nonce ) {
		wp_nonce_field( $nonce, $nonce );
		echo '<table class="form-table csf-fields" role="presentation">';
		foreach ( $fields as $key => $field ) {
			$id    = 'csf-' . $key;
			$name  = 'csf_fields[' . $key . ']';
			$value = self::get( $post_id, $key );
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			switch ( $field['type'] ) {
				case 'textarea':
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea>';
					break;
				case 'select':
					echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( $field['options'] as $opt => $label ) {
						echo '<option value="' . esc_attr( $opt ) . '"' . selected( $value, (string) $opt, false ) . '>' . esc_html( $label ) . '</option>';
					}
					echo '</select>';
					break;
				case 'checkbox':
					echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', $value, false ) . '> ' . esc_html( $field['checkbox_label'] ?? '' ) . '</label>';
					break;
				default:
					$type = in_array( $field['type'], array( 'date', 'url' ), true ) ? $field['type'] : 'text';
					echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
			}
			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Save fields from the current request.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $fields  Field definitions.
	 * @param string $nonce   Nonce action/name.
	 * @param string $cap     Capability required for this post.
	 * @return bool Whether fields were saved.
	 */
	public static function save( $post_id, array $fields, $nonce, $cap = 'edit_post' ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}
		if ( ! isset( $_POST[ $nonce ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $nonce ] ) ), $nonce ) ) {
			return false;
		}
		if ( ! current_user_can( $cap, $post_id ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$input = isset( $_POST['csf_fields'] ) && is_array( $_POST['csf_fields'] ) ? wp_unslash( $_POST['csf_fields'] ) : array();

		foreach ( $fields as $key => $field ) {
			update_post_meta( $post_id, '_csf_' . $key, self::sanitize( $input[ $key ] ?? '', $field ) );
		}
		return true;
	}

	/**
	 * Sanitize one value according to its field type.
	 *
	 * @param mixed $raw   Raw value.
	 * @param array $field Field definition.
	 * @return string
	 */
	public static function sanitize( $raw, array $field ) {
		$raw = is_scalar( $raw ) ? (string) $raw : '';
		switch ( $field['type'] ) {
			case 'textarea':
				return sanitize_textarea_field( $raw );
			case 'url':
				return esc_url_raw( $raw );
			case 'date':
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
			case 'select':
				return array_key_exists( $raw, $field['options'] ) ? $raw : (string) array_key_first( $field['options'] );
			case 'checkbox':
				return '' === $raw ? '0' : '1';
			default:
				return sanitize_text_field( $raw );
		}
	}

	/**
	 * Render a definition list of non-empty values.
	 *
	 * @param array<string,string> $rows Label => value.
	 * @param string               $extra_class Extra class.
	 * @return string
	 */
	public static function definition_list( array $rows, $extra_class = '' ) {
		$rows = array_filter( $rows, static fn( $v ) => '' !== trim( (string) $v ) );
		if ( ! $rows ) {
			return '';
		}
		$html = '<dl class="csf-details ' . esc_attr( $extra_class ) . '">';
		foreach ( $rows as $label => $value ) {
			$html .= '<div class="csf-details__row"><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
		}
		return $html . '</dl>';
	}

	/**
	 * Format a Y-m-d date for display.
	 *
	 * @param string $date Date.
	 * @return string
	 */
	public static function format_date( $date ) {
		$ts = $date ? strtotime( $date . ' 12:00:00' ) : false;
		return $ts ? wp_date( get_option( 'date_format' ), $ts ) : '';
	}
}
