<?php
/**
 * Draws the Social Icons block's and widget's icons as inline SVG on the
 * front end, instead of loading icon fonts.
 *
 * Saved content doesn't change: the block still saves
 * <span class="social-icon socicon socicon-x"></span>, and the SVG is put
 * inside that span when the page is rendered. The SVGs come from the icon
 * library (includes/icon-library/), extracted from the same fonts, so icons
 * look exactly as before.
 *
 * Instead of six icon stylesheets plus their font files, the page gets one
 * small stylesheet with the boxes those stylesheets gave the icons. An icon
 * the library doesn't have still gets its kit's original stylesheet.
 *
 * Turn it off with: add_filter( 'wpzoom_social_icons_use_svg', '__return_false' );
 *
 * The block editor draws the same SVGs, from the icon-kit REST route below.
 *
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPZOOM_Social_Icons_SVG
 */
class WPZOOM_Social_Icons_SVG {

	/**
	 * Stylesheet handle for the SVG icons.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'wpzoom-social-icons-svg';

	/**
	 * Kit => its font stylesheet (handle, file), the fallback for an icon the
	 * library lacks.
	 *
	 * @var array
	 */
	private static $font_styles = array(
		'socicon'     => array( 'wpzoom-social-icons-socicon', 'wpzoom-socicon.css' ),
		'genericon'   => array( 'wpzoom-social-icons-genericons', 'genericons.css' ),
		'academicons' => array( 'wpzoom-social-icons-academicons', 'academicons.min.css' ),
		'fab'         => array( 'wpzoom-social-icons-font-awesome-5', 'font-awesome-5.min.css' ),
		'far'         => array( 'wpzoom-social-icons-font-awesome-5', 'font-awesome-5.min.css' ),
		'fas'         => array( 'wpzoom-social-icons-font-awesome-5', 'font-awesome-5.min.css' ),
		'fa'          => array( 'wpzoom-social-icons-font-awesome-3', 'font-awesome-3.min.css' ),
		'dashicons'   => array( 'dashicons', '' ),
	);

	/**
	 * REST namespace for the editor's icon data.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'wpzoom-social-icons/v1';

	/**
	 * Hook everything up.
	 */
	public static function init() {
		add_filter( 'render_block_wpzoom-blocks/social-icons', array( __CLASS__, 'render_block' ), 10, 2 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
	}

	/**
	 * Whether icons are drawn as SVG (front end and block editor), unless
	 * turned off with the filter.
	 *
	 * @return bool
	 */
	public static function use_svg() {
		/**
		 * Filters whether the Social Icons block and widget draw their icons as
		 * inline SVG instead of loading icon fonts.
		 *
		 * @param bool $use_svg Default true.
		 */
		return (bool) apply_filters( 'wpzoom_social_icons_use_svg', true );
	}

	/**
	 * Whether icons are drawn as SVG on this front-end request.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return ! is_admin() && self::use_svg();
	}

	/**
	 * Icons of retired networks, per kit (see includes/retired-icons.php).
	 *
	 * @return array Kit => list of icon names.
	 */
	public static function retired_icons() {
		static $retired = null;
		if ( null === $retired ) {
			$retired = include WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'includes/retired-icons.php';
		}
		return $retired;
	}

	/**
	 * Data the block editor needs to draw icons as SVG.
	 *
	 * @return array
	 */
	public static function editor_data() {
		// Changes whenever the library is regenerated, so cached kits refresh.
		$version = WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION;
		foreach ( glob( WPZOOM_Social_Icons_Library::dir() . '*.php' ) as $file ) {
			$version .= '-' . filemtime( $file );
		}

		return array(
			'enabled'  => self::use_svg(),
			'restPath' => '/' . self::REST_NAMESPACE . '/icon-kit/',
			'version'  => substr( md5( $version ), 0, 10 ),
			'retired'  => self::retired_icons(),
		);
	}

	/**
	 * GET /wpzoom-social-icons/v1/icon-kit/<kit>: a kit's icons as inline SVG,
	 * for the block editor. Public (the same icons any page shows) and cacheable:
	 * the editor adds a version that changes with the library.
	 */
	public static function register_rest_route() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/icon-kit/(?P<kit>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_icon_kit' ),
			)
		);
	}

	/**
	 * REST callback: name => inline SVG for every icon of a kit.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_icon_kit( $request ) {
		$kit = $request['kit'];
		if ( ! isset( self::$font_styles[ $kit ] ) ) {
			return new WP_Error( 'wpzoom_unknown_icon_kit', __( 'Unknown icon kit.', 'social-icons-widget-by-wpzoom' ), array( 'status' => 404 ) );
		}

		$icons = array();
		foreach ( array_keys( WPZOOM_Social_Icons_Library::kit( $kit ) ) as $name ) {
			$icons[ $name ] = WPZOOM_Social_Icons_Library::inline_svg( $kit, $name );
		}

		$response = new WP_REST_Response( (object) $icons );
		$response->header( 'Cache-Control', 'public, max-age=31536000, immutable' );
		return $response;
	}

	/**
	 * Enqueue the stylesheet the SVG icons need (it replaces the icon fonts'
	 * stylesheets on the front end).
	 */
	public static function enqueue_style() {
		wp_enqueue_style(
			self::STYLE_HANDLE,
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-svg.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-social-icons-svg.css' )
		);
	}

	/**
	 * Enqueue a kit's font stylesheet, for an icon the library lacks.
	 *
	 * @param string $kit Kit name.
	 */
	public static function enqueue_font_style( $kit ) {
		if ( ! isset( self::$font_styles[ $kit ] ) ) {
			return;
		}
		list( $handle, $file ) = self::$font_styles[ $kit ];
		if ( '' === $file ) {
			wp_enqueue_style( $handle );
			return;
		}
		wp_enqueue_style(
			$handle,
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/' . $file,
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/' . $file )
		);
	}

	/**
	 * Kit and icon name from an icon's class list.
	 *
	 * @param string $classes Class attribute, e.g. 'social-icon fab fa-facebook'.
	 * @return array|null array( kit, name ), or null if it isn't an icon class list.
	 */
	public static function parse_classes( $classes ) {
		$tokens = preg_split( '/\s+/', trim( $classes ) );
		foreach ( $tokens as $token ) {
			if ( ! isset( self::$font_styles[ $token ] ) ) {
				continue;
			}
			// Font Awesome names its icons fa-*, whatever the kit.
			$prefix = in_array( $token, array( 'fab', 'far', 'fas', 'fa' ), true ) ? 'fa-' : $token . '-';
			foreach ( $tokens as $candidate ) {
				if ( 0 === strpos( $candidate, $prefix ) && strlen( $candidate ) > strlen( $prefix ) ) {
					return array( $token, substr( $candidate, strlen( $prefix ) ) );
				}
			}
		}
		return null;
	}

	/**
	 * An icon's inline SVG, enqueueing what it needs. When the library lacks
	 * the icon, its kit's font stylesheet is enqueued instead.
	 *
	 * @param string $kit  Kit name.
	 * @param string $name Icon name.
	 * @return string SVG markup, or '' if the font has to draw this icon.
	 */
	public static function icon( $kit, $name ) {
		$svg = WPZOOM_Social_Icons_Library::inline_svg( $kit, $name );
		if ( '' === $svg ) {
			self::enqueue_font_style( $kit );
			return '';
		}
		self::enqueue_style();
		return $svg;
	}

	/**
	 * Fill every empty icon span in some HTML with the icon's SVG.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function fill_spans( $html ) {
		if ( false === strpos( $html, 'social-icon' ) ) {
			return $html;
		}
		return preg_replace_callback(
			'#<span\s+class="([^"]*\bsocial-icon\b[^"]*)"([^>]*)>\s*</span>#',
			function ( $m ) {
				$icon = WPZOOM_Social_Icons_SVG::parse_classes( $m[1] );
				if ( ! $icon ) {
					return $m[0];
				}
				$svg = WPZOOM_Social_Icons_SVG::icon( $icon[0], $icon[1] );
				if ( '' === $svg ) {
					return $m[0];
				}
				return '<span class="' . $m[1] . ' wpzoom-svg"' . $m[2] . '>' . $svg . '</span>';
			},
			$html
		);
	}

	/**
	 * Social Icons block: icon spans get their SVG.
	 *
	 * @param string $content Rendered block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function render_block( $content, $block = array() ) {
		if ( ! self::enabled() ) {
			return $content;
		}
		return self::fill_spans( self::from_pro( $content, $block ) );
	}

	/**
	 * Blocks saved by WPZOOM Connect Pro, on a site back on the free plugin:
	 * PRO writes each icon as `social-icon socicon socicon-<name>` (whatever
	 * its kit) with its own SVG inside, which free's styles don't size. Each
	 * such span is turned back into the empty span free saves, with the kit
	 * from the block's attributes (icons are in the same order), and is then
	 * drawn like any other. The editor migrates these blocks the same way
	 * (block/src/block/pro-deprecations.js); this covers pages not re-saved.
	 *
	 * @param string $content Rendered block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	private static function from_pro( $content, $block ) {
		$icons = isset( $block['attrs']['selectedIcons'] ) && is_array( $block['attrs']['selectedIcons'] ) ? array_values( $block['attrs']['selectedIcons'] ) : array();
		if ( ! $icons || false === strpos( $content, '<svg' ) ) {
			return $content;
		}

		$index = 0;
		return preg_replace_callback(
			// Icon spans only: the class list starts with "social-icon" as a
			// whole word (not "social-icon-link"). SVGs hold no spans.
			'#<span\s+class="(social-icon(?:\s[^"]*)?)"([^>]*)>(.*?)</span>#s',
			function ( $m ) use ( $icons, &$index ) {
				$icon = isset( $icons[ $index ] ) ? $icons[ $index ] : null;
				++$index;
				if (
					! is_array( $icon ) || empty( $icon['icon'] ) || empty( $icon['iconKit'] ) || 'svg' === $icon['iconKit']
					|| false === strpos( $m[3], '<svg' )
					|| ! preg_match( '/(^|\s)socicon-' . preg_quote( $icon['icon'], '/' ) . '(\s|$)/', $m[1] )
				) {
					return $m[0];
				}
				$kit  = sanitize_html_class( $icon['iconKit'] );
				$name = sanitize_html_class( $icon['icon'] );
				$fa   = in_array( $kit, array( 'fab', 'far', 'fas' ), true );
				return '<span class="social-icon ' . $kit . ' ' . ( $fa ? 'fa-' : $kit . '-' ) . $name . '"' . $m[2] . '></span>';
			},
			$content
		);
	}
}

WPZOOM_Social_Icons_SVG::init();
