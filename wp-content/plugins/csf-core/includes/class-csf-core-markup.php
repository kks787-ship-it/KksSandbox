<?php
/**
 * Block markup builder used by patterns and page content.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Produces serialized core block markup. Keeping every block's HTML in one place means the
 * patterns and pages stay consistent and valid in the block editor (verified by the
 * automated editor-validity test). Methods taking $html expect already-escaped HTML.
 */
class CSF_Core_Markup {

	/**
	 * Container width for section content.
	 */
	const CONTENT_SIZE = '1160px';

	/**
	 * Serialize a block with inner HTML.
	 *
	 * @param string $name  Block name without "core/".
	 * @param array  $attrs Comment attributes.
	 * @param string $html  Inner HTML.
	 * @return string
	 */
	public static function block( $name, array $attrs, $html ) {
		return '<!-- wp:' . $name . self::attrs( $attrs ) . " -->\n" . $html . "\n<!-- /wp:" . $name . " -->\n";
	}

	/**
	 * Serialize a void (dynamic) block.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @return string
	 */
	public static function dynamic( $name, array $attrs = array() ) {
		return '<!-- wp:' . $name . self::attrs( $attrs ) . " /-->\n";
	}

	/**
	 * Encode comment attributes.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private static function attrs( array $attrs ) {
		return $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
	}

	/**
	 * Class attribute from a list.
	 *
	 * @param string[] $classes Classes.
	 * @return string
	 */
	private static function class_attr( array $classes ) {
		$classes = array_filter( $classes );
		return $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';
	}

	/**
	 * Escape plain text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function t( $text ) {
		return esc_html( $text );
	}

	/**
	 * Site URL for a page path.
	 *
	 * @param string $path Path such as "lessons/youth-riders".
	 * @return string
	 */
	public static function url( $path = '' ) {
		return esc_url( home_url( $path ? '/' . trim( $path, '/' ) . '/' : '/' ) );
	}

	/**
	 * Paragraph.
	 *
	 * @param string $html      Escaped inline HTML.
	 * @param string $class_name Optional class (e.g. is-style-csf-lead).
	 * @return string
	 */
	public static function p( $html, $class_name = '' ) {
		$attrs = $class_name ? array( 'className' => $class_name ) : array();
		return self::block( 'paragraph', $attrs, '<p' . self::class_attr( array( $class_name ) ) . '>' . $html . '</p>' );
	}

	/**
	 * Eyebrow paragraph.
	 *
	 * @param string $text Plain text.
	 * @return string
	 */
	public static function eyebrow( $text ) {
		return self::p( self::t( $text ), 'is-style-csf-eyebrow' );
	}

	/**
	 * Lead paragraph.
	 *
	 * @param string $html Escaped HTML.
	 * @return string
	 */
	public static function lead( $html ) {
		return self::p( $html, 'is-style-csf-lead' );
	}

	/**
	 * Heading.
	 *
	 * @param string $text      Plain text.
	 * @param int    $level     Level 1-6.
	 * @param string $class_name Optional class.
	 * @return string
	 */
	public static function h( $text, $level = 2, $class_name = '' ) {
		$attrs = array();
		if ( 2 !== $level ) {
			$attrs['level'] = $level;
		}
		if ( $class_name ) {
			$attrs['className'] = $class_name;
		}
		return self::block( 'heading', $attrs, '<h' . $level . self::class_attr( array( 'wp-block-heading', $class_name ) ) . '>' . self::t( $text ) . '</h' . $level . '>' );
	}

	/**
	 * Group.
	 *
	 * @param string $inner Inner blocks.
	 * @param array  $opts  { tag, align, className, layout }.
	 * @return string
	 */
	public static function group( $inner, array $opts = array() ) {
		$tag   = $opts['tag'] ?? 'div';
		$attrs = array();
		if ( 'div' !== $tag ) {
			$attrs['tagName'] = $tag;
		}
		if ( ! empty( $opts['align'] ) ) {
			$attrs['align'] = $opts['align'];
		}
		if ( ! empty( $opts['className'] ) ) {
			$attrs['className'] = $opts['className'];
		}
		$attrs['layout'] = $opts['layout'] ?? array( 'type' => 'constrained' );

		$classes = array( 'wp-block-group', ! empty( $opts['align'] ) ? 'align' . $opts['align'] : '', $opts['className'] ?? '' );
		return self::block( 'group', $attrs, '<' . $tag . self::class_attr( $classes ) . ">\n" . $inner . '</' . $tag . '>' );
	}

	/**
	 * Full-width page section.
	 *
	 * @param string $inner Inner blocks.
	 * @param string $style linen|white|tan|dark|brown.
	 * @param string $extra Extra classes.
	 * @return string
	 */
	public static function section( $inner, $style = 'linen', $extra = '' ) {
		return self::group(
			$inner,
			array(
				'tag'       => 'section',
				'align'     => 'full',
				'className' => trim( 'is-style-csf-section-' . $style . ' ' . $extra ),
				'layout'    => array(
					'type'        => 'constrained',
					'contentSize' => self::CONTENT_SIZE,
				),
			)
		);
	}

	/**
	 * Centred section intro: eyebrow, heading, optional lead.
	 *
	 * @param string $eyebrow Eyebrow text ('' for none).
	 * @param string $heading Heading text.
	 * @param string $lead    Lead HTML ('' for none).
	 * @return string
	 */
	public static function intro( $eyebrow, $heading, $lead = '' ) {
		$inner = ( $eyebrow ? self::eyebrow( $eyebrow ) : '' ) . self::h( $heading ) . ( $lead ? self::lead( $lead ) : '' );
		return self::group( $inner, array( 'className' => 'csf-section-intro' ) );
	}

	/**
	 * Inner-page header band with the page's H1.
	 *
	 * @param string $eyebrow Eyebrow.
	 * @param string $title   H1 text.
	 * @param string $lead    Lead HTML.
	 * @param string $style   Section style.
	 * @return string
	 */
	public static function page_header( $eyebrow, $title, $lead, $style = 'tan' ) {
		return self::section( self::eyebrow( $eyebrow ) . self::h( $title, 1 ) . ( $lead ? self::lead( $lead ) : '' ), $style, 'csf-page-header' );
	}

	/**
	 * Columns.
	 *
	 * @param array  $columns   List of inner markup strings, or [inner, width] pairs.
	 * @param string $valign    Vertical alignment ('' | center | top).
	 * @param string $class_name Extra class.
	 * @return string
	 */
	public static function columns( array $columns, $valign = '', $class_name = '' ) {
		$attrs = array();
		if ( $valign ) {
			$attrs['verticalAlignment'] = $valign;
		}
		if ( $class_name ) {
			$attrs['className'] = $class_name;
		}
		$html = '';
		foreach ( $columns as $column ) {
			$inner     = is_array( $column ) ? $column[0] : $column;
			$width     = is_array( $column ) ? $column[1] : '';
			$col_attrs = array();
			if ( $valign ) {
				$col_attrs['verticalAlignment'] = $valign;
			}
			if ( $width ) {
				$col_attrs['width'] = $width;
			}
			$style = $width ? ' style="flex-basis:' . esc_attr( $width ) . '"' : '';
			$html .= self::block( 'column', $col_attrs, '<div' . self::class_attr( array( 'wp-block-column', $valign ? 'is-vertically-aligned-' . $valign : '' ) ) . $style . ">\n" . $inner . '</div>' );
		}
		$classes = array( 'wp-block-columns', $valign ? 'are-vertically-aligned-' . $valign : '', $class_name );
		return self::block( 'columns', $attrs, '<div' . self::class_attr( $classes ) . ">\n" . $html . '</div>' );
	}

	/**
	 * Buttons.
	 *
	 * @param array  $buttons List of [label, url, style] where style is csf-primary|csf-outline|csf-light.
	 * @param string $justify '' or center.
	 * @return string
	 */
	public static function buttons( array $buttons, $justify = '' ) {
		$html = '';
		foreach ( $buttons as $button ) {
			$class = 'is-style-' . ( $button[2] ?? 'csf-primary' );
			$html .= self::block( 'button', array( 'className' => $class ), '<div class="wp-block-button ' . esc_attr( $class ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $button[1] ) . '">' . self::t( $button[0] ) . '</a></div>' );
		}
		$attrs = $justify ? array(
			'layout' => array(
				'type'           => 'flex',
				'justifyContent' => $justify,
			),
		) : array();
		return self::block( 'buttons', $attrs, "<div class=\"wp-block-buttons\">\n" . $html . '</div>' );
	}

	/**
	 * Booking button (dynamic; uses Settings → Booking).
	 *
	 * @param string $variant primary|outline|light.
	 * @param string $label   Optional label.
	 * @param string $align   left|center.
	 * @return string
	 */
	public static function book( $variant = 'primary', $label = '', $align = '' ) {
		$attrs = array();
		if ( 'primary' !== $variant ) {
			$attrs['variant'] = $variant;
		}
		if ( $label ) {
			$attrs['label'] = $label;
		}
		if ( $align && 'left' !== $align ) {
			$attrs['align'] = $align;
		}
		return self::dynamic( 'csf/book-button', $attrs );
	}

	/**
	 * Booking button plus a secondary text link, side by side.
	 *
	 * @param string $link_label Secondary link label.
	 * @param string $link_url   Secondary link URL.
	 * @param string $variant    Booking button variant.
	 * @param bool   $centered   Centre the row.
	 * @return string
	 */
	public static function book_row( $link_label, $link_url, $variant = 'primary', $centered = false ) {
		return self::group(
			self::book( $variant ) . self::buttons( array( array( $link_label, $link_url, 'csf-outline' ) ) ),
			array(
				'className' => 'csf-actions-row' . ( $centered ? ' is-centered' : '' ),
				'layout'    => array( 'type' => 'default' ),
			)
		);
	}

	/**
	 * Image from a URL.
	 *
	 * @param string $src       URL.
	 * @param string $alt       Alt text.
	 * @param string $class_name Classes.
	 * @return string
	 */
	public static function image( $src, $alt, $class_name = 'is-style-csf-soft' ) {
		return self::block(
			'image',
			array(
				'sizeSlug'  => 'large',
				'className' => $class_name,
			),
			'<figure' . self::class_attr( array( 'wp-block-image', 'size-large', $class_name ) ) . '><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"/></figure>'
		);
	}

	/**
	 * Labelled photo placeholder.
	 *
	 * @param string $slug Placeholder slug (assets/placeholders/manifest.json).
	 * @return string
	 */
	public static function placeholder( $slug ) {
		return self::image( self::placeholder_url( $slug ), self::placeholder_alt( $slug ), 'is-style-csf-soft csf-placeholder' );
	}

	/**
	 * Placeholder URL.
	 *
	 * @param string $slug Slug.
	 * @return string
	 */
	public static function placeholder_url( $slug ) {
		return CSF_CORE_URL . 'assets/placeholders/' . sanitize_file_name( $slug ) . '.svg';
	}

	/**
	 * Placeholder alt text.
	 *
	 * @param string $slug Slug.
	 * @return string
	 */
	public static function placeholder_alt( $slug ) {
		static $manifest = null;
		if ( null === $manifest ) {
			$manifest = json_decode( (string) file_get_contents( CSF_CORE_DIR . 'assets/placeholders/manifest.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- bundled file.
		}
		/* translators: %s: description of the photograph needed */
		return sprintf( __( 'Photo placeholder: %s', 'csf-core' ), $manifest[ $slug ]['description'] ?? $slug );
	}

	/**
	 * Hero cover with background image.
	 *
	 * @param string $src   Background URL.
	 * @param string $alt   Alt text.
	 * @param string $inner Inner blocks.
	 * @return string
	 */
	public static function hero( $src, $alt, $inner ) {
		$attrs = array(
			'url'                => $src,
			'alt'                => $alt,
			'dimRatio'           => 70,
			'overlayColor'       => 'csf-barn-black',
			'isUserOverlayColor' => true,
			'minHeight'          => 82,
			'minHeightUnit'      => 'vh',
			'align'              => 'full',
			'className'          => 'csf-hero',
			'layout'             => array(
				'type'        => 'constrained',
				'contentSize' => self::CONTENT_SIZE,
			),
		);
		$html  = '<div class="wp-block-cover alignfull csf-hero" style="min-height:82vh">';
		$html .= '<img class="wp-block-cover__image-background" alt="' . esc_attr( $alt ) . '" src="' . esc_url( $src ) . '" data-object-fit="cover"/>';
		$html .= '<span aria-hidden="true" class="wp-block-cover__background has-csf-barn-black-background-color has-background-dim-70 has-background-dim"></span>';
		$html .= "<div class=\"wp-block-cover__inner-container\">\n" . $inner . '</div></div>';
		return self::block( 'cover', $attrs, $html );
	}

	/**
	 * List.
	 *
	 * @param string[] $items     Escaped HTML items.
	 * @param string   $class_name Class.
	 * @return string
	 */
	public static function ul( array $items, $class_name = 'is-style-csf-checklist' ) {
		$html = '';
		foreach ( $items as $item ) {
			$html .= self::block( 'list-item', array(), '<li>' . $item . '</li>' );
		}
		$attrs = $class_name ? array( 'className' => $class_name ) : array();
		return self::block( 'list', $attrs, '<ul' . self::class_attr( array( 'wp-block-list', $class_name ) ) . ">\n" . $html . '</ul>' );
	}

	/**
	 * Details (FAQ item).
	 *
	 * @param string $summary Plain text question.
	 * @param string $inner   Inner blocks.
	 * @return string
	 */
	public static function details( $summary, $inner ) {
		return self::block( 'details', array(), '<details class="wp-block-details"><summary>' . self::t( $summary ) . "</summary>\n" . $inner . '</details>' );
	}

	/**
	 * Ornamental separator.
	 *
	 * @return string
	 */
	public static function ornament() {
		return self::block( 'separator', array( 'className' => 'is-style-csf-ornament' ), '<hr class="wp-block-separator has-alpha-channel-opacity is-style-csf-ornament"/>' );
	}

	/**
	 * Owner review note. Visible while the site is under review; must be resolved before launch.
	 *
	 * @param string $html Escaped HTML describing what needs confirming.
	 * @return string
	 */
	public static function note( $html ) {
		return self::group( self::p( '<strong>' . esc_html__( 'Owner to confirm:', 'csf-core' ) . '</strong> ' . $html ), array( 'className' => 'csf-owner-note' ) );
	}

	/**
	 * Card (Group with Card style).
	 *
	 * @param string $inner Inner blocks.
	 * @return string
	 */
	public static function card( $inner ) {
		return self::group( $inner, array( 'className' => 'is-style-csf-card' ) );
	}

	/**
	 * Card linking to a page: placeholder image, heading, text, link.
	 *
	 * @param string $placeholder Placeholder slug ('' for none).
	 * @param string $title       Title.
	 * @param string $text        Escaped HTML.
	 * @param string $path        Page path.
	 * @param string $link_label  Link label.
	 * @return string
	 */
	public static function link_card( $placeholder, $title, $text, $path, $link_label ) {
		$inner  = $placeholder ? self::placeholder( $placeholder ) : '';
		$inner .= self::h( $title, 3 );
		$inner .= self::p( $text );
		$inner .= self::p( '<a href="' . self::url( $path ) . '">' . self::t( $link_label ) . '</a>', 'csf-card-link' );
		return self::card( $inner );
	}
}
