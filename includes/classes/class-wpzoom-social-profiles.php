<?php
/**
 * Social Profiles block: drawn on the page here, from its attributes.
 *
 * The editor draws the same markup in
 * block/src/social-profiles-block/components/Profile.js, and the text
 * helpers below mirror block/src/social-profiles-block/utils/profiles.js.
 *
 * @package WPZOOM_Social_Icons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the wpzoom-blocks/social-profiles block.
 */
class WPZOOM_Social_Profiles {

	/**
	 * The block's attributes, as registered with register_block_type().
	 *
	 * @return array
	 */
	public static function attributes() {
		return array(
			'items'        => array(
				'type'    => 'array',
				'default' => array(),
			),
			'layout'       => array(
				'type'    => 'string',
				'default' => 'list',
			),
			'display'      => array(
				'type'    => 'string',
				'default' => 'handle',
			),
			'colorMode'    => array(
				'type'    => 'string',
				'default' => 'brand',
			),
			'color'        => array(
				'type'    => 'string',
				'default' => '',
			),
			'columns'      => array(
				'type'    => 'number',
				'default' => 2,
			),
			'showCounts'   => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showCta'      => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'ctaText'      => array(
				'type'    => 'string',
				'default' => '',
			),
			'buttonText'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'openInNewTab' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'nofollow'     => array(
				'type'    => 'boolean',
				'default' => false,
			),
			// Styles tab: empty means the layout's own look.
			'radius'       => array( 'type' => 'number' ),
			'borderWidth'  => array( 'type' => 'number' ),
			'borderColor'  => array(
				'type'    => 'string',
				'default' => '',
			),
			'background'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'textColor'    => array(
				'type'    => 'string',
				'default' => '',
			),
			'padding'      => array( 'type' => 'number' ),
			'gap'          => array( 'type' => 'number' ),
			'iconSize'     => array( 'type' => 'number' ),
			'iconRadius'   => array( 'type' => 'number' ),
		);
	}

	/**
	 * The Styles tab settings as CSS variables (as styleVars() in JS).
	 *
	 * @param array $a Block attributes.
	 * @return string Inline style.
	 */
	private static function style_vars( $a ) {
		$map   = array(
			'radius'      => array( '--wpz-sp-radius', 'px' ),
			'borderWidth' => array( '--wpz-sp-border-width', 'px' ),
			'borderColor' => array( '--wpz-sp-border-color', '' ),
			'background'  => array( '--wpz-sp-bg', '' ),
			'textColor'   => array( '--wpz-sp-text', '' ),
			'padding'     => array( '--wpz-sp-pad', 'px' ),
			'gap'         => array( '--wpz-sp-gap', 'px' ),
			'iconSize'    => array( '--wpz-sp-icon-size', 'px' ),
			'iconRadius'  => array( '--wpz-sp-icon-radius', 'px' ),
		);
		$style = '';
		foreach ( $map as $key => $var ) {
			if ( ! isset( $a[ $key ] ) || '' === $a[ $key ] || null === $a[ $key ] ) {
				continue;
			}
			$value = 'px' === $var[1] ? ( is_numeric( $a[ $key ] ) ? (float) $a[ $key ] . 'px' : '' ) : self::color( $a[ $key ] );
			if ( '' !== $value ) {
				$style .= $var[0] . ':' . $value . ';';
			}
		}
		return $style;
	}

	/**
	 * The block on the page.
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML.
	 */
	public static function render( $attributes ) {
		$defaults = array();
		foreach ( self::attributes() as $key => $attribute ) {
			// The Styles tab settings have no default: empty means the layout's own look.
			if ( array_key_exists( 'default', $attribute ) ) {
				$defaults[ $key ] = $attribute['default'];
			}
		}
		$a     = wp_parse_args( $attributes, $defaults );
		$items = array_filter(
			is_array( $a['items'] ) ? $a['items'] : array(),
			function ( $item ) {
				// A profile still being set up (no icon yet) stays in the editor.
				return is_array( $item ) && ! empty( $item['icon'] );
			}
		);
		if ( ! $items ) {
			return '';
		}

		// Free shows the List layout; Cards and Buttons are PRO.
		$layout = in_array( $a['layout'], array( 'list', 'cards', 'buttons' ), true ) ? $a['layout'] : 'list';
		if ( 'list' !== $layout && ! WPZOOM_Connect_Overview::is_pro() ) {
			$layout = 'list';
		}
		$a['layout'] = $layout;

		$classes = array( 'is-layout-' . $layout, 'is-color-' . ( 'mono' === $a['colorMode'] ? 'mono' : 'brand' ) );
		if ( 'cards' === $layout ) {
			$classes[] = 'has-' . max( 1, min( 4, (int) $a['columns'] ) ) . '-columns';
		}
		$style = self::style_vars( $a ) . ( 'mono' === $a['colorMode'] && $a['color'] ? '--wpz-sp-color:' . self::color( $a['color'] ) . ';' : '' );

		$wrapper = get_block_wrapper_attributes(
			array_filter(
				array(
					'class' => implode( ' ', $classes ),
					'style' => $style,
				)
			)
		);

		$html = '';
		foreach ( $items as $item ) {
			$html .= self::render_item( $item, $a );
		}

		return sprintf( '<div %s><ul class="wpz-sp__list">%s</ul></div>', $wrapper, $html );
	}

	/**
	 * One profile.
	 *
	 * @param array $item Profile.
	 * @param array $a    Block attributes (with defaults).
	 * @return string HTML.
	 */
	private static function render_item( $item, $a ) {
		$item = wp_parse_args(
			$item,
			array(
				'url'        => '',
				'iconKit'    => 'socicon',
				'icon'       => '',
				'network'    => '',
				'text'       => '',
				'count'      => '',
				'countLabel' => '',
				'color'      => '',
			)
		);

		$text  = self::profile_text( $item, $a['display'] );
		$count = $a['showCounts'] ? trim( (string) $item['count'] ) : '';
		$label = '' !== $item['countLabel'] ? $item['countLabel'] : self::default_count_label( $item['icon'] );
		$icon  = class_exists( 'WPZOOM_Social_Icons_Library' ) ? WPZOOM_Social_Icons_Library::inline_svg( $item['iconKit'], $item['icon'] ) : '';

		$inner = '<span class="wpz-sp__icon" aria-hidden="true">' . $icon . '</span>';

		if ( 'buttons' === $a['layout'] ) {
			$inner .= '<span class="wpz-sp__name">' . esc_html( self::button_label( $item, $a['buttonText'] ) ) . '</span>';
		} else {
			$inner .= '<span class="wpz-sp__text"><span class="wpz-sp__name">' . esc_html( $text ) . '</span>';
			if ( '' !== $count ) {
				$inner .= '<span class="wpz-sp__meta"><strong>' . esc_html( $count ) . '</strong> ' . esc_html( $label ) . '</span>';
			}
			$inner .= '</span>';
		}

		// "Follow" only where there's a link to follow.
		if ( 'list' === $a['layout'] && $a['showCta'] && '' !== trim( (string) $item['url'] ) ) {
			$inner .= '<span class="wpz-sp__cta">' . esc_html( '' !== $a['ctaText'] ? $a['ctaText'] : __( 'Follow', 'social-icons-widget-by-wpzoom' ) ) . '<span aria-hidden="true">→</span></span>';
		}

		$url = trim( (string) $item['url'] );
		if ( '' === $url ) {
			$link = '<span class="wpz-sp__link">' . $inner . '</span>';
		} else {
			$rel = array();
			if ( $a['openInNewTab'] ) {
				$rel[] = 'noopener';
			}
			if ( $a['nofollow'] ) {
				$rel[] = 'nofollow';
			}
			$link = sprintf(
				'<a class="wpz-sp__link" href="%s"%s%s%s>%s</a>',
				esc_url( $url ),
				$a['openInNewTab'] ? ' target="_blank"' : '',
				$rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '',
				'buttons' === $a['layout'] ? '' : ' aria-label="' . esc_attr( self::network_name( $item ) . ': ' . $text ) . '"',
				$inner
			);
		}

		$style = 'brand' === $a['colorMode'] && $item['color'] ? ' style="--wpz-sp-brand:' . esc_attr( self::color( $item['color'] ) ) . '"' : '';

		return '<li class="wpz-sp__item"' . $style . '>' . $link . '</li>';
	}

	/**
	 * A color value safe for an inline style, or ''.
	 *
	 * @param string $color Color.
	 * @return string
	 */
	private static function color( $color ) {
		$color = trim( (string) $color );
		return preg_match( '/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|hsla?\([\d\s.,%deg]+\)|[a-z]+)$/i', $color ) ? $color : '';
	}

	/**
	 * The network's display name, as stored when its icon was chosen.
	 *
	 * @param array $item Profile.
	 * @return string
	 */
	private static function network_name( $item ) {
		if ( '' !== $item['network'] ) {
			return $item['network'];
		}
		return ucfirst( str_replace( '-', ' ', $item['icon'] ) );
	}

	/**
	 * What a count is called on a network (as defaultCountLabel() in JS).
	 *
	 * @param string $icon Icon name.
	 * @return string
	 */
	public static function default_count_label( $icon ) {
		switch ( $icon ) {
			case 'youtube':
			case 'substack':
			case 'mail':
				return __( 'subscribers', 'social-icons-widget-by-wpzoom' );
			case 'discord':
			case 'telegram':
			case 'reddit':
			case 'slack':
				return __( 'members', 'social-icons-widget-by-wpzoom' );
			case 'spotify':
				return __( 'listeners', 'social-icons-widget-by-wpzoom' );
			case 'patreon':
				return __( 'patrons', 'social-icons-widget-by-wpzoom' );
			default:
				return __( 'followers', 'social-icons-widget-by-wpzoom' );
		}
	}

	/**
	 * The text a profile shows (as profileText() in JS).
	 *
	 * @param array  $item    Profile.
	 * @param string $display "handle", "url" or "network".
	 * @return string
	 */
	private static function profile_text( $item, $display ) {
		if ( '' !== trim( (string) $item['text'] ) ) {
			return $item['text'];
		}
		if ( 'network' === $display || '' === trim( (string) $item['url'] ) ) {
			return self::network_name( $item );
		}
		if ( 'url' === $display ) {
			return self::short_url( $item['url'] );
		}
		$handle = self::handle( $item['url'] );
		return '' !== $handle ? $handle : self::network_name( $item );
	}

	/**
	 * The words on a profile's button (as buttonLabel() in JS).
	 *
	 * @param array  $item        Profile.
	 * @param string $button_text The block's button text, "%s" for the network.
	 * @return string
	 */
	private static function button_label( $item, $button_text ) {
		/* translators: %s: network name, e.g. Instagram */
		$pattern = '' !== $button_text ? $button_text : __( 'Follow on %s', 'social-icons-widget-by-wpzoom' );
		return false !== strpos( $pattern, '%s' ) ? str_replace( '%s', self::network_name( $item ), $pattern ) : $pattern;
	}

	/**
	 * A URL taken apart, or null (as parse() in JS).
	 *
	 * @param string $url URL.
	 * @return array|null { host, segments, has_query }.
	 */
	private static function parse( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return null;
		}
		if ( ! preg_match( '/^[a-z][a-z0-9+.-]*:/i', $url ) ) {
			$url = 'https://' . $url;
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return null;
		}
		$segments = array_values(
			array_filter(
				array_map( 'rawurldecode', explode( '/', isset( $parts['path'] ) ? $parts['path'] : '' ) ),
				'strlen'
			)
		);
		return array(
			'host'      => preg_replace( '/^www\./', '', strtolower( $parts['host'] ) ),
			'segments'  => $segments,
			'has_query' => ! empty( $parts['query'] ),
		);
	}

	/**
	 * "https://www.x.com/ciorici/" → "x.com/ciorici" (as shortUrl() in JS).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function short_url( $url ) {
		$url = trim( (string) $url );
		if ( preg_match( '/^(mailto|tel|sms):/i', $url ) ) {
			return preg_replace( '/\?.*$/', '', preg_replace( '/^[a-z]+:/i', '', $url ) );
		}
		$parts = self::parse( $url );
		if ( ! $parts ) {
			return $url;
		}
		return implode( '/', array_merge( array( $parts['host'] ), $parts['segments'] ) );
	}

	/**
	 * "x.com/ciorici" → "@ciorici", "youtube.com/@wpzoom" → "@wpzoom",
	 * "reddit.com/r/WordPress" → "r/WordPress"; '' when there's no clear
	 * handle, e.g. a Facebook group (as handle() in JS).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function handle( $url ) {
		$url = trim( (string) $url );
		if ( preg_match( '/^(mailto|tel|sms):/i', $url ) ) {
			return self::short_url( $url );
		}
		$parts = self::parse( $url );
		if ( ! $parts ) {
			return $url;
		}
		$segments = $parts['segments'];
		if ( ! $segments ) {
			return $parts['host'];
		}
		if ( '@' === substr( $segments[0], 0, 1 ) ) {
			return $segments[0];
		}
		if ( $parts['has_query'] ) {
			return '';
		}
		if ( 1 === count( $segments ) ) {
			return '@' . $segments[0];
		}
		if ( 2 === count( $segments ) && in_array( $segments[0], array( 'r', 'u', 'user' ), true ) && false !== strpos( $parts['host'], 'reddit.' ) ) {
			return ( 'user' === $segments[0] ? 'u' : $segments[0] ) . '/' . $segments[1];
		}
		return '';
	}
}
