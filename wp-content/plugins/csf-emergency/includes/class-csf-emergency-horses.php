<?php
/**
 * Horse emergency identification profiles.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * A private post type holding only information approved for public emergency use.
 * Private records (full medical history, ownership, billing) do not belong here.
 */
class CSF_Emergency_Horses {

	const POST_TYPE = 'csf_horse_emergency';
	const NONCE     = 'csf_horse_meta';

	/**
	 * Field definitions shared by the editor and the public page.
	 *
	 * @return array<string,array{label:string,type:string,help?:string}>
	 */
	public static function fields() {
		return array(
			'registered_name' => array(
				'label' => __( 'Registered name', 'csf-emergency' ),
				'type'  => 'text',
			),
			'breed'           => array(
				'label' => __( 'Breed', 'csf-emergency' ),
				'type'  => 'text',
			),
			'sex'             => array(
				'label' => __( 'Sex', 'csf-emergency' ),
				'type'  => 'sex',
			),
			'color'           => array(
				'label' => __( 'Color', 'csf-emergency' ),
				'type'  => 'text',
			),
			'height'          => array(
				'label' => __( 'Height', 'csf-emergency' ),
				'type'  => 'text',
				'help'  => __( 'e.g. 16.1 hh', 'csf-emergency' ),
			),
			'markings'        => array(
				'label' => __( 'Identifying markings', 'csf-emergency' ),
				'type'  => 'textarea',
			),
			'microchip'       => array(
				'label' => __( 'Microchip number', 'csf-emergency' ),
				'type'  => 'text',
				'help'  => __( 'Hidden publicly unless "Show microchip number" is ticked; otherwise the page only says the horse is microchipped.', 'csf-emergency' ),
			),
			'registration'    => array(
				'label' => __( 'Registration (breed registry / number)', 'csf-emergency' ),
				'type'  => 'text',
			),
			'handling'        => array(
				'label' => __( 'Safe handling instructions', 'csf-emergency' ),
				'type'  => 'textarea',
			),
			'trailer'         => array(
				'label' => __( 'Trailer loading preferences', 'csf-emergency' ),
				'type'  => 'textarea',
			),
			'medical'         => array(
				'label' => __( 'Essential emergency medical considerations', 'csf-emergency' ),
				'type'  => 'textarea',
				'help'  => __( 'Only what a stranger needs to keep this horse safe today (e.g. allergies, "do not give bute"). No private veterinary records.', 'csf-emergency' ),
			),
			'feeding'         => array(
				'label' => __( 'Temporary feeding instructions', 'csf-emergency' ),
				'type'  => 'textarea',
			),
		);
	}

	/**
	 * Sex options.
	 *
	 * @return array<string,string>
	 */
	public static function sex_options() {
		return array(
			''         => __( '— Select —', 'csf-emergency' ),
			'mare'     => __( 'Mare', 'csf-emergency' ),
			'gelding'  => __( 'Gelding', 'csf-emergency' ),
			'stallion' => __( 'Stallion', 'csf-emergency' ),
			'filly'    => __( 'Filly', 'csf-emergency' ),
			'colt'     => __( 'Colt', 'csf-emergency' ),
		);
	}

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		// Token assignment must run for every save path (admin, import, WP-CLI), not just the editor.
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		if ( is_admin() ) {
			add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_boxes' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
			add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
			add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		}
	}

	/**
	 * Register the post type. Not public, not queryable, not searchable, not in REST.
	 * The only public view is the /h/{token} route rendered by CSF_Emergency_Public.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Horse Emergency IDs', 'csf-emergency' ),
					'singular_name' => __( 'Horse Emergency ID', 'csf-emergency' ),
					'add_new_item'  => __( 'Add Horse Emergency ID', 'csf-emergency' ),
					'edit_item'     => __( 'Edit Horse Emergency ID', 'csf-emergency' ),
					'menu_name'     => __( 'Horse IDs', 'csf-emergency' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'csf-emergency',
				'show_in_rest'        => false,
				'show_in_nav_menus'   => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'thumbnail' ),
				'capabilities'        => CSF_Emergency_Capabilities::horse_caps(),
				'map_meta_cap'        => false,
			)
		);
	}

	/**
	 * Read a profile field.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key without prefix.
	 * @return string
	 */
	public static function get( $post_id, $key ) {
		return (string) get_post_meta( $post_id, '_csf_' . $key, true );
	}

	/**
	 * Whether this profile is approved and active for public display.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_public( $post_id ) {
		return 'publish' === get_post_status( $post_id ) && '1' === self::get( $post_id, 'public_approved' );
	}

	/**
	 * Public URL for a profile, or '' if it has no token yet.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function public_url( $post_id ) {
		$token = get_post_meta( $post_id, CSF_Emergency_Tokens::META_KEY, true );
		return $token ? home_url( '/h/' . $token ) : '';
	}

	/**
	 * Attachment IDs for photos: featured image first, then the gallery.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function photo_ids( $post_id ) {
		$ids = array_filter( array_map( 'absint', explode( ',', self::get( $post_id, 'photos' ) ) ) );
		$fid = (int) get_post_thumbnail_id( $post_id );
		if ( $fid ) {
			array_unshift( $ids, $fid );
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Admin assets for the editor and tag screens.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( $screen && self::POST_TYPE === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'csf-emergency-admin', CSF_EMERGENCY_URL . 'assets/admin.js', array( 'jquery' ), CSF_EMERGENCY_VERSION, true );
		}
		wp_enqueue_style( 'csf-emergency-admin', CSF_EMERGENCY_URL . 'assets/admin.css', array(), CSF_EMERGENCY_VERSION );
	}

	/**
	 * Meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box( 'csf-horse-public', __( 'Public emergency information', 'csf-emergency' ), array( __CLASS__, 'render_fields_box' ), self::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'csf-horse-status', __( 'Status & publishing', 'csf-emergency' ), array( __CLASS__, 'render_status_box' ), self::POST_TYPE, 'side', 'high' );
		add_meta_box( 'csf-horse-photos', __( 'Additional photographs', 'csf-emergency' ), array( __CLASS__, 'render_photos_box' ), self::POST_TYPE, 'side', 'default' );
	}

	/**
	 * Public fields box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_fields_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );
		echo '<div class="csf-public-warning"><strong>' . esc_html__( 'Everything in this box is PUBLIC', 'csf-emergency' ) . '</strong> — ' . esc_html__( 'anyone who scans this horse\'s tag can read it once the profile is approved. Do not enter owner details, home addresses, prices, or private medical records.', 'csf-emergency' ) . '</div>';
		echo '<table class="form-table csf-fields">';
		foreach ( self::fields() as $key => $field ) {
			$value = self::get( $post->ID, $key );
			$id    = 'csf-field-' . $key;
			echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			if ( 'textarea' === $field['type'] ) {
				echo '<textarea id="' . esc_attr( $id ) . '" name="csf[' . esc_attr( $key ) . ']" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea>';
			} elseif ( 'sex' === $field['type'] ) {
				echo '<select id="' . esc_attr( $id ) . '" name="csf[' . esc_attr( $key ) . ']">';
				foreach ( self::sex_options() as $opt => $label ) {
					echo '<option value="' . esc_attr( $opt ) . '"' . selected( $value, $opt, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="csf[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="regular-text">';
			}
			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '<tr><th>' . esc_html__( 'Microchip visibility', 'csf-emergency' ) . '</th><td><label><input type="checkbox" name="csf[show_microchip]" value="1"' . checked( '1', self::get( $post->ID, 'show_microchip' ), false ) . '> ' . esc_html__( 'Show microchip number publicly', 'csf-emergency' ) . '</label></td></tr>';
		echo '</table>';
	}

	/**
	 * Status & publishing box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_status_box( $post ) {
		$token = get_post_meta( $post->ID, CSF_Emergency_Tokens::META_KEY, true );
		$url   = self::public_url( $post->ID );

		echo '<p><label><input type="checkbox" name="csf[public_approved]" value="1"' . checked( '1', self::get( $post->ID, 'public_approved' ), false ) . '> <strong>' . esc_html__( 'Approved for public emergency display', 'csf-emergency' ) . '</strong></label></p>';
		echo '<p class="description">' . esc_html__( 'Until approved (and published), the tag URL shows only the farm contact page.', 'csf-emergency' ) . '</p>';

		echo '<p><label for="csf-evac-status"><strong>' . esc_html__( 'Evacuation status', 'csf-emergency' ) . '</strong></label><br><select id="csf-evac-status" name="csf[evac_status]">';
		$current = self::get( $post->ID, 'evac_status' ) ? self::get( $post->ID, 'evac_status' ) : 'at_farm';
		foreach ( CSF_Emergency_Statuses::horse() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';

		echo '<p><label for="csf-evac-note">' . esc_html__( 'Public status note (no locations)', 'csf-emergency' ) . '</label><input type="text" id="csf-evac-note" name="csf[evac_note]" value="' . esc_attr( self::get( $post->ID, 'evac_note' ) ) . '" class="widefat" maxlength="160"></p>';

		echo '<p><label for="csf-last-verified"><strong>' . esc_html__( 'Information last verified', 'csf-emergency' ) . '</strong></label><input type="date" id="csf-last-verified" name="csf[last_verified]" value="' . esc_attr( self::get( $post->ID, 'last_verified' ) ) . '" class="widefat"></p>';

		if ( $token ) {
			echo '<hr><p><strong>' . esc_html__( 'Permanent tag URL', 'csf-emergency' ) . '</strong><br><code>' . esc_html( $url ) . '</code></p>';
			$tag_url = add_query_arg(
				array(
					'page'  => 'csf-emergency-tag',
					'horse' => $post->ID,
				),
				admin_url( 'admin.php' )
			);
			echo '<p><a class="button" href="' . esc_url( $tag_url ) . '">' . esc_html__( 'Print QR tag', 'csf-emergency' ) . '</a> <a class="button" target="_blank" rel="noopener" href="' . esc_url( $url ) . '">' . esc_html__( 'View', 'csf-emergency' ) . '</a></p>';
		} else {
			echo '<hr><p class="description">' . esc_html__( 'A permanent tag URL is created when this profile is first saved.', 'csf-emergency' ) . '</p>';
		}
	}

	/**
	 * Gallery box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_photos_box( $post ) {
		$ids = self::get( $post->ID, 'photos' );
		echo '<p class="description">' . esc_html__( 'The featured image is the primary ID photo. Add current photos showing both sides and markings. Photos become public once approved.', 'csf-emergency' ) . '</p>';
		echo '<div id="csf-photo-preview" class="csf-photo-preview">';
		foreach ( array_filter( array_map( 'absint', explode( ',', $ids ) ) ) as $id ) {
			echo wp_get_attachment_image( $id, 'thumbnail' );
		}
		echo '</div><input type="hidden" id="csf-photos" name="csf[photos]" value="' . esc_attr( $ids ) . '">';
		echo '<p><button type="button" class="button" id="csf-photos-select">' . esc_html__( 'Choose photos', 'csf-emergency' ) . '</button> <button type="button" class="button-link" id="csf-photos-clear">' . esc_html__( 'Clear', 'csf-emergency' ) . '</button></p>';
	}

	/**
	 * Save meta and assign a permanent token.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}

		// The token is permanent: assign once, never regenerate (printed tags depend on it).
		if ( ! get_post_meta( $post_id, CSF_Emergency_Tokens::META_KEY, true ) ) {
			add_post_meta( $post_id, CSF_Emergency_Tokens::META_KEY, CSF_Emergency_Tokens::generate_unique(), true );
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_HORSES ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$input = isset( $_POST['csf'] ) && is_array( $_POST['csf'] ) ? wp_unslash( $_POST['csf'] ) : array();

		foreach ( self::fields() as $key => $field ) {
			$raw = $input[ $key ] ?? '';
			if ( 'textarea' === $field['type'] ) {
				$value = sanitize_textarea_field( $raw );
			} elseif ( 'sex' === $field['type'] ) {
				$value = array_key_exists( $raw, self::sex_options() ) ? $raw : '';
			} else {
				$value = sanitize_text_field( $raw );
			}
			update_post_meta( $post_id, '_csf_' . $key, $value );
		}

		update_post_meta( $post_id, '_csf_show_microchip', empty( $input['show_microchip'] ) ? '0' : '1' );
		update_post_meta( $post_id, '_csf_public_approved', empty( $input['public_approved'] ) ? '0' : '1' );
		update_post_meta( $post_id, '_csf_evac_note', sanitize_text_field( $input['evac_note'] ?? '' ) );
		update_post_meta( $post_id, '_csf_photos', implode( ',', array_filter( array_map( 'absint', explode( ',', (string) ( $input['photos'] ?? '' ) ) ) ) ) );

		$date = sanitize_text_field( $input['last_verified'] ?? '' );
		update_post_meta( $post_id, '_csf_last_verified', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '' );

		$old_status = self::get( $post_id, 'evac_status' );
		$new_status = sanitize_key( $input['evac_status'] ?? 'at_farm' );
		if ( ! array_key_exists( $new_status, CSF_Emergency_Statuses::horse() ) ) {
			$new_status = 'unknown';
		}
		if ( $old_status !== $new_status ) {
			update_post_meta( $post_id, '_csf_evac_status', $new_status );
			update_post_meta( $post_id, '_csf_evac_updated', time() );
			CSF_Emergency_Event_Log::record(
				array(
					'event_type' => 'horse_status',
					'object_id'  => $post_id,
					'old_status' => $old_status,
					'new_status' => $new_status,
					'message'    => get_the_title( $post_id ),
				)
			);
		}
	}

	/**
	 * List table columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );
		$columns['csf_public']   = __( 'Public', 'csf-emergency' );
		$columns['csf_status']   = __( 'Evacuation status', 'csf-emergency' );
		$columns['csf_verified'] = __( 'Last verified', 'csf-emergency' );
		$columns['csf_tag']      = __( 'Tag', 'csf-emergency' );
		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column( $column, $post_id ) {
		switch ( $column ) {
			case 'csf_public':
				echo self::is_public( $post_id ) ? esc_html__( 'Yes', 'csf-emergency' ) : '<strong>' . esc_html__( 'No', 'csf-emergency' ) . '</strong>';
				break;
			case 'csf_status':
				$statuses = CSF_Emergency_Statuses::horse();
				$key      = self::get( $post_id, 'evac_status' );
				echo esc_html( $statuses[ $key ] ?? $statuses['at_farm'] );
				break;
			case 'csf_verified':
				echo esc_html( self::get( $post_id, 'last_verified' ) ? self::get( $post_id, 'last_verified' ) : '—' );
				break;
			case 'csf_tag':
				$token = get_post_meta( $post_id, CSF_Emergency_Tokens::META_KEY, true );
				echo $token ? '<code>/h/' . esc_html( $token ) . '</code>' : '—';
				break;
		}
	}

	/**
	 * Aggregate counts of approved horses by evacuation status.
	 *
	 * @return array{total:int,counts:array<string,int>,horses:array<int>}
	 */
	public static function summary() {
		$ids    = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- a boutique farm herd; bounded.
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_key'       => '_csf_public_approved', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$counts = array();
		foreach ( $ids as $id ) {
			$key            = self::get( $id, 'evac_status' ) ? self::get( $id, 'evac_status' ) : 'at_farm';
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
		}
		return array(
			'total'  => count( $ids ),
			'counts' => $counts,
			'horses' => $ids,
		);
	}
}
