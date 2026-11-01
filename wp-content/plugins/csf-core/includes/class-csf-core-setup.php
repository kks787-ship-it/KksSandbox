<?php
/**
 * Repeatable site setup: pages, hierarchy, front page, menus, and footer.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Idempotent. Safe to run any number of times:
 * - Missing pages are created from their page layouts.
 * - Existing pages are only updated if nobody has edited them since setup created them
 *   (tracked by a content hash). Edited pages are never overwritten unless forced.
 * - Menus are only populated when they are first created.
 *
 * Run from Tools → Corner Stone Farm Setup, or `wp csf setup`.
 */
class CSF_Core_Setup {

	const VERSION      = 1;
	const PAGE         = 'csf-site-setup';
	const FOOTER_TITLE = 'Site Footer';
	const MENU_PRIMARY = 'Primary Navigation';
	const MENU_FOOTER  = 'Footer Navigation';

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
			add_action( 'admin_post_csf_site_setup', array( __CLASS__, 'handle' ) );
		}
	}

	/**
	 * Page tree, parents before children. "path" is the full URL path.
	 *
	 * @return array<int,array{slug:string,title:string,parent:string,path:string}>
	 */
	public static function pages() {
		$tree  = array(
			array( 'home', __( 'Home', 'csf-core' ), '' ),
			array( 'lessons', __( 'Lessons', 'csf-core' ), '' ),
			array( 'youth-riders', __( 'Youth Riders', 'csf-core' ), 'lessons' ),
			array( 'adult-amateurs', __( 'Adult Amateurs', 'csf-core' ), 'lessons' ),
			array( 'dressage-foundations', __( 'Dressage Foundations', 'csf-core' ), 'lessons' ),
			array( 'off-site-lessons', __( 'Off-Site Lessons', 'csf-core' ), 'lessons' ),
			array( 'after-school-program', __( 'After-School Program', 'csf-core' ), 'lessons' ),
			array( 'horse-training', __( 'Horse Training', 'csf-core' ), '' ),
			array( 'fei-young-horse-development', __( 'FEI Young Horse Development', 'csf-core' ), 'horse-training' ),
			array( 'camps-clinics', __( 'Camps & Clinics', 'csf-core' ), '' ),
			array( 'horse-sales', __( 'Horse Sales', 'csf-core' ), '' ),
			array( 'leasing-mentorship', __( 'Leasing & Mentorship', 'csf-core' ), 'horse-sales' ),
			array( 'about', __( 'About', 'csf-core' ), '' ),
			array( 'meet-beth', __( 'Meet Beth', 'csf-core' ), 'about' ),
			array( 'meet-the-team', __( 'Meet the Team', 'csf-core' ), 'about' ),
			array( 'meet-the-horses', __( 'Meet the Horses', 'csf-core' ), 'about' ),
			array( 'facilities', __( 'Facilities', 'csf-core' ), 'about' ),
			array( 'resources', __( 'Resources', 'csf-core' ), '' ),
			array( 'faq', __( 'FAQ', 'csf-core' ), 'resources' ),
			array( 'contact', __( 'Contact', 'csf-core' ), '' ),
			array( 'booking', __( 'Contact Beth to Schedule', 'csf-core' ), '' ),
		);
		$pages = array();
		foreach ( $tree as $row ) {
			$pages[] = array(
				'slug'   => $row[0],
				'title'  => $row[1],
				'parent' => $row[2],
				'path'   => $row[2] ? $row[2] . '/' . $row[0] : $row[0],
			);
		}
		return $pages;
	}

	/**
	 * Primary menu: [page slug, children slugs]. "booking" becomes the highlighted CTA item.
	 *
	 * @return array<string,string[]>
	 */
	public static function primary_menu() {
		return array(
			'home'           => array(),
			'lessons'        => array( 'youth-riders', 'adult-amateurs', 'dressage-foundations', 'off-site-lessons', 'after-school-program' ),
			'horse-training' => array( 'fei-young-horse-development' ),
			'camps-clinics'  => array(),
			'horse-sales'    => array( 'leasing-mentorship' ),
			'about'          => array( 'meet-beth', 'meet-the-team', 'meet-the-horses', 'facilities' ),
			'resources'      => array( 'faq' ),
			'contact'        => array(),
			'booking'        => array(),
		);
	}

	/**
	 * Find a setup page by its slug.
	 *
	 * @param string $slug Slug.
	 * @return WP_Post|null
	 */
	public static function find_page( $slug ) {
		foreach ( self::pages() as $page ) {
			if ( $page['slug'] === $slug ) {
				$post = get_page_by_path( $page['path'], OBJECT, 'page' );
				return $post instanceof WP_Post ? $post : null;
			}
		}
		return null;
	}

	/**
	 * Whether a page's content has been edited since setup wrote it.
	 *
	 * @param WP_Post $post Page.
	 * @return bool
	 */
	public static function is_edited( WP_Post $post ) {
		$hash = get_post_meta( $post->ID, '_csf_setup_hash', true );
		return ! $hash || md5( $post->post_content ) !== $hash;
	}

	/**
	 * Run setup.
	 *
	 * @param array $args { force: bool, dry_run: bool, status: publish|draft, set_permalinks: bool }.
	 * @return string[] Log lines.
	 */
	public static function run( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'force'          => false,
				'dry_run'        => false,
				'status'         => 'publish',
				'set_permalinks' => false,
			)
		);
		$log  = array();
		$dry  = (bool) $args['dry_run'];

		if ( ! $dry ) {
			$term_errors = array_merge( CSF_Core_Library::seed_categories(), CSF_Core_Horses::seed_terms(), CSF_Core_Events::seed_terms() );
			foreach ( $term_errors as $error ) {
				$log[] = 'ERROR    Taxonomy setup: ' . $error->get_error_message();
			}
		}

		// Pages.
		$ids = array();
		foreach ( self::pages() as $page ) {
			$content  = CSF_Core_Patterns::page_content( $page['slug'] );
			$existing = get_page_by_path( $page['path'], OBJECT, 'page' );

			if ( $existing instanceof WP_Post ) {
				$ids[ $page['slug'] ] = $existing->ID;
				$edited               = self::is_edited( $existing );
				if ( $edited && ! $args['force'] ) {
					$log[] = sprintf( 'kept     /%s/ (edited in WordPress — not overwritten)', $page['path'] );
					if ( 'contact' === $page['slug'] && ! has_block( 'csf/inquiry-form', $existing ) ) {
						$log[] = 'WARNING  /contact/ has no inquiry-form block. Review the edited page and add the Farm Inquiry Form block manually in Gutenberg; setup has preserved its content. The form stays disabled until delivery is verified and enabled separately.';
					}
					continue;
				}
				if ( ! $edited && md5( $content ) === get_post_meta( $existing->ID, '_csf_setup_source', true ) ) {
					$log[] = sprintf( 'ok       /%s/', $page['path'] );
					continue;
				}
				$log[] = sprintf( 'update   /%s/%s', $page['path'], $args['force'] ? ' (forced)' : '' );
				if ( ! $dry ) {
					$updated = wp_update_post(
						array(
							'ID'           => $existing->ID,
							'post_content' => $content,
						),
						true
					);
					if ( is_wp_error( $updated ) || ! $updated ) {
						$log[] = 'ERROR    Could not update /' . $page['path'] . '/: ' . ( is_wp_error( $updated ) ? $updated->get_error_message() : 'No post was saved.' );
						continue;
					}
					self::save_meta(
						$existing->ID,
						array(
							'_csf_setup_hash'   => md5( get_post_field( 'post_content', $existing->ID, 'raw' ) ),
							'_csf_setup_source' => md5( $content ),
						),
						$log
					);
				}
				continue;
			}

			$log[] = sprintf( 'create   /%s/', $page['path'] );
			if ( $dry ) {
				continue;
			}
			if ( $page['parent'] && empty( $ids[ $page['parent'] ] ) ) {
				$log[] = 'ERROR    Skipped /' . $page['path'] . '/ because its parent page is unavailable.';
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => $args['status'],
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_parent'  => $page['parent'] ? ( $ids[ $page['parent'] ] ?? 0 ) : 0,
					'post_content' => $content,
					'menu_order'   => count( $ids ),
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				$log[] = 'ERROR    Could not create /' . $page['path'] . '/: ' . ( is_wp_error( $id ) ? $id->get_error_message() : 'No post was saved.' );
				continue;
			}
			$ids[ $page['slug'] ] = $id;
			self::save_meta(
				$id,
				array(
					'_csf_setup_hash'            => md5( get_post_field( 'post_content', $id, 'raw' ) ),
					'_csf_setup_source'          => md5( $content ),
					'_csf_setup_version'         => self::VERSION,
					// Kadence layouts include their own H1 and full-width sections.
					'_kad_post_title'            => 'hide',
					'_kad_post_layout'           => 'fullwidth',
					'_kad_post_content_style'    => 'unboxed',
					'_kad_post_vertical_padding' => 'hide',
				),
				$log
			);
		}

		// Front page.
		if ( ! empty( $ids['home'] ) && ( (int) get_option( 'page_on_front' ) !== $ids['home'] || 'page' !== get_option( 'show_on_front' ) ) ) {
			$log[] = 'set      front page = Home';
			if ( ! $dry ) {
				self::save_option( 'show_on_front', 'page', $log );
				self::save_option( 'page_on_front', $ids['home'], $log );
			}
		}

		// Footer as a synced pattern, so it is edited in one place.
		$log = array_merge( $log, self::ensure_footer( $dry ) );

		// Menus.
		if ( ! $dry ) {
			$log = array_merge( $log, self::ensure_menus( $ids, (bool) $args['force'] ) );
		}

		// Permalinks.
		$structure = (string) get_option( 'permalink_structure' );
		if ( '/%postname%/' !== $structure ) {
			if ( $args['set_permalinks'] && ! $dry ) {
				self::save_option( 'permalink_structure', '/%postname%/', $log );
				$log[] = 'set      permalinks = /%postname%/';
			} else {
				$log[] = sprintf( 'WARNING  permalink structure is "%s"; recommended "/%%postname%%/" (re-run with --set-permalinks or change in Settings → Permalinks).', $structure ? $structure : 'plain' );
			}
		}

		if ( ! $dry ) {
			flush_rewrite_rules( false );
			global $wp_rewrite;
			if ( did_action( 'wp_loaded' ) && get_option( 'rewrite_rules' ) !== $wp_rewrite->rules ) {
				$log[] = 'ERROR    Rewrite rules could not be saved.';
			}
			if ( ! self::has_errors( $log ) ) {
				self::save_option(
					'csf_core_setup',
					array(
						'version' => self::VERSION,
						'time'    => time(),
					),
					$log,
					false
				);
			}
		}
		return $log;
	}

	/**
	 * Whether setup encountered a failed WordPress operation.
	 *
	 * @param string[] $log Setup output.
	 * @return bool
	 */
	public static function has_errors( array $log ) {
		foreach ( $log as $line ) {
			if ( preg_match( '/^\s*ERROR(?:\s|:)/', $line ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Save an option without treating an unchanged value as a failure.
	 *
	 * @param string    $name     Option name.
	 * @param mixed     $value    Expected value.
	 * @param string[]  $log      Output updated on failure.
	 * @param bool|null $autoload Autoload preference.
	 * @return bool
	 */
	private static function save_option( $name, $value, array &$log, $autoload = null ) {
		$current = get_option( $name );
		if ( $current === $value || ( is_scalar( $current ) && is_scalar( $value ) && (string) $current === (string) $value ) ) {
			return true;
		}
		if ( ! update_option( $name, $value, $autoload ) ) {
			$log[] = 'ERROR    Could not save option ' . $name . '.';
			return false;
		}
		return true;
	}

	/**
	 * Save setup/layout metadata and report failed writes.
	 *
	 * @param int      $post_id Post ID.
	 * @param array    $values  Metadata values.
	 * @param string[] $log     Output updated on failure.
	 */
	private static function save_meta( $post_id, array $values, array &$log ) {
		foreach ( $values as $key => $value ) {
			$value = (string) $value;
			if ( get_post_meta( $post_id, $key, true ) !== $value && false === update_post_meta( $post_id, $key, $value ) ) {
				$log[] = 'ERROR    Could not save ' . $key . ' for post #' . $post_id . '.';
			}
		}
	}

	/**
	 * Create the footer synced pattern if missing.
	 *
	 * @param bool $dry Dry run.
	 * @return string[]
	 */
	private static function ensure_footer( $dry ) {
		$id = (int) get_option( 'csf_core_footer_block' );
		if ( $id && 'wp_block' === get_post_type( $id ) ) {
			return array( 'ok       footer synced pattern #' . $id );
		}
		if ( $dry ) {
			return array( 'create   footer synced pattern' );
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_title'   => self::FOOTER_TITLE,
				'post_content' => CSF_Core_Patterns::section( 'site-footer' ),
			),
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return array( 'ERROR    Could not create the footer pattern: ' . ( is_wp_error( $id ) ? $id->get_error_message() : 'No post was saved.' ) );
		}
		$log = array();
		self::save_option( 'csf_core_footer_block', $id, $log, false );
		$log[] = 'create   footer synced pattern #' . $id . ' (place it in the Kadence footer — see docs)';
		return $log;
	}

	/**
	 * Create and assign menus.
	 *
	 * @param array<string,int> $ids   Page IDs by slug.
	 * @param bool              $force Rebuild existing menus.
	 * @return string[]
	 */
	private static function ensure_menus( array $ids, $force ) {
		$log       = array();
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$available = get_registered_nav_menus();

		$primary = wp_get_nav_menu_object( self::MENU_PRIMARY );
		if ( ! $primary || $force ) {
			$menu_id = $primary ? $primary->term_id : wp_create_nav_menu( self::MENU_PRIMARY );
			if ( is_wp_error( $menu_id ) || ! $menu_id ) {
				$log[] = 'ERROR    Could not create the primary menu: ' . ( is_wp_error( $menu_id ) ? $menu_id->get_error_message() : 'No menu was saved.' );
				return $log;
			}
			if ( $primary ) {
				foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
					if ( ! wp_delete_post( $item->ID, true ) ) {
						$log[] = 'ERROR    Could not remove primary menu item #' . $item->ID . '; menu rebuild stopped.';
						return $log;
					}
				}
			}
			$order = 0;
			foreach ( self::primary_menu() as $slug => $children ) {
				if ( empty( $ids[ $slug ] ) ) {
					continue;
				}
				$parent_item = self::add_menu_page( $menu_id, $ids[ $slug ], 0, ++$order, 'booking' === $slug ? 'csf-menu-cta' : '' );
				if ( is_wp_error( $parent_item ) || ! $parent_item ) {
					$log[] = 'ERROR    Could not save primary menu item for ' . $slug . '.';
					continue;
				}
				foreach ( $children as $child ) {
					if ( ! empty( $ids[ $child ] ) ) {
						$child_item = self::add_menu_page( $menu_id, $ids[ $child ], $parent_item, ++$order );
						if ( is_wp_error( $child_item ) || ! $child_item ) {
							$log[] = 'ERROR    Could not save primary submenu item for ' . $child . '.';
						}
					}
				}
			}
			$log[]      = ( $primary ? 'rebuild  ' : 'create   ' ) . 'menu "' . self::MENU_PRIMARY . '"';
			$primary_id = $menu_id;
		} else {
			$primary_id = $primary->term_id;
			$log[]      = 'ok       menu "' . self::MENU_PRIMARY . '" (existing menu not changed)';
		}

		$footer = wp_get_nav_menu_object( self::MENU_FOOTER );
		if ( ! $footer ) {
			$footer_id = wp_create_nav_menu( self::MENU_FOOTER );
			if ( is_wp_error( $footer_id ) || ! $footer_id ) {
				$log[] = 'ERROR    Could not create the footer menu: ' . ( is_wp_error( $footer_id ) ? $footer_id->get_error_message() : 'No menu was saved.' );
				return $log;
			}
			$order = 0;
			foreach ( array( 'contact', 'faq', 'booking' ) as $slug ) {
				if ( ! empty( $ids[ $slug ] ) ) {
					$item = self::add_menu_page( $footer_id, $ids[ $slug ], 0, ++$order );
					if ( is_wp_error( $item ) || ! $item ) {
						$log[] = 'ERROR    Could not save footer menu item for ' . $slug . '.';
					}
				}
			}
			$emergency_item = wp_update_nav_menu_item(
				$footer_id,
				0,
				array(
					'menu-item-title'    => __( 'Farm Emergency Information', 'csf-core' ),
					'menu-item-url'      => home_url( '/emergency/' ),
					'menu-item-type'     => 'custom',
					'menu-item-status'   => 'publish',
					'menu-item-position' => ++$order,
				)
			);
			if ( is_wp_error( $emergency_item ) || ! $emergency_item ) {
				$log[] = 'ERROR    Could not save the footer emergency link.';
			}
			$log[] = 'create   menu "' . self::MENU_FOOTER . '"';
		} else {
			$footer_id = $footer->term_id;
		}

		// Assign to whichever of the common locations the active theme registers (Kadence: primary, mobile, footer).
		$changed = false;
		foreach ( array(
			'primary' => $primary_id,
			'mobile'  => $primary_id,
			'footer'  => $footer_id,
		) as $location => $menu ) {
			if ( isset( $available[ $location ] ) && empty( $locations[ $location ] ) ) {
				$locations[ $location ] = $menu;
				$changed                = true;
				$log[]                  = 'assign   menu to location "' . $location . '"';
			}
		}
		if ( $changed ) {
			set_theme_mod( 'nav_menu_locations', $locations );
			if ( get_theme_mod( 'nav_menu_locations', array() ) !== $locations ) {
				$log[] = 'ERROR    Menu locations could not be saved.';
			}
		}
		if ( ! $available ) {
			$log[] = 'note     active theme registers no menu locations; assign menus in Appearance → Menus after activating Kadence.';
		}
		return $log;
	}

	/**
	 * Add a page to a menu.
	 *
	 * @param int    $menu_id Menu.
	 * @param int    $page_id Page.
	 * @param int    $parent_item Parent menu item.
	 * @param int    $order   Position.
	 * @param string $classes CSS classes.
	 * @return int|WP_Error Menu item ID or save error.
	 */
	private static function add_menu_page( $menu_id, $page_id, $parent_item, $order, $classes = '' ) {
		return wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $page_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent_item,
				'menu-item-position'  => $order,
				'menu-item-classes'   => $classes,
			)
		);
	}

	/**
	 * Count "Owner to confirm" notes in a page.
	 *
	 * @param WP_Post $post Page.
	 * @return int
	 */
	public static function owner_notes( WP_Post $post ) {
		return (int) ( substr_count( $post->post_content, 'csf-owner-note' ) / 2 ); // Class appears in the comment and the HTML.
	}

	/**
	 * Admin menu.
	 */
	public static function menu() {
		add_management_page( __( 'Corner Stone Farm Setup', 'csf-core' ), __( 'Corner Stone Farm Setup', 'csf-core' ), 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	/**
	 * Run setup from the admin page.
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to run the site setup.', 'csf-core' ), 403 );
		}
		check_admin_referer( 'csf_site_setup' );
		$log = self::run( array( 'dry_run' => ! empty( $_POST['csf_dry_run'] ) ) );
		set_transient( 'csf_setup_log_' . get_current_user_id(), $log, 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE . '&ran=1' ) );
		exit;
	}

	/**
	 * Admin page: status table, run button, manual Kadence steps.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Corner Stone Farm Site Setup', 'csf-core' ) . '</h1>';

		$log = get_transient( 'csf_setup_log_' . get_current_user_id() );
		if ( $log && isset( $_GET['ran'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			delete_transient( 'csf_setup_log_' . get_current_user_id() );
			$failed  = self::has_errors( $log );
			$class   = $failed ? 'notice-error' : 'notice-success';
			$message = $failed ? __( 'Setup is incomplete. Some changes may have been saved; review the errors before trying again.', 'csf-core' ) : __( 'Setup finished.', 'csf-core' );
			echo '<div class="notice ' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p><pre style="white-space:pre-wrap">' . esc_html( implode( "\n", $log ) ) . '</pre></div>';
		}

		echo '<p>' . esc_html__( 'Creates any missing pages from the Corner Stone Farm page layouts, sets the front page, creates menus and the footer. Pages you have edited are never overwritten. Safe to run more than once.', 'csf-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'csf_site_setup' );
		echo '<input type="hidden" name="action" value="csf_site_setup">';
		echo '<p><label><input type="checkbox" name="csf_dry_run" value="1"> ' . esc_html__( 'Dry run (show what would change, change nothing)', 'csf-core' ) . '</label></p>';
		submit_button( __( 'Run site setup', 'csf-core' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'Pages', 'csf-core' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Page', 'csf-core' ) . '</th><th>' . esc_html__( 'Status', 'csf-core' ) . '</th><th>' . esc_html__( 'Edited since setup', 'csf-core' ) . '</th><th>' . esc_html__( 'Owner notes left', 'csf-core' ) . '</th></tr></thead><tbody>';
		foreach ( self::pages() as $page ) {
			$post = get_page_by_path( $page['path'], OBJECT, 'page' );
			echo '<tr><td><code>/' . esc_html( $page['path'] ) . '/</code></td>';
			if ( $post instanceof WP_Post ) {
				echo '<td><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_post_status_object( $post->post_status )->label ) . '</a></td>';
				echo '<td>' . esc_html( self::is_edited( $post ) ? __( 'Yes', 'csf-core' ) : __( 'No', 'csf-core' ) ) . '</td>';
				echo '<td>' . esc_html( (string) self::owner_notes( $post ) ) . '</td>';
			} else {
				echo '<td colspan="3">' . esc_html__( 'Not created', 'csf-core' ) . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Manual steps in Kadence', 'csf-core' ) . '</h2><p>' . esc_html__( 'See docs/07-website-installation.md in the project repository for the Kadence Customizer settings (palette, header, footer).', 'csf-core' ) . '</p></div>';
	}
}
