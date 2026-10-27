/**
 * Text helpers for the Social Profiles block. Mirrored in PHP by
 * WPZOOM_Social_Profiles (includes/classes/class-wpzoom-social-profiles.php):
 * the block is drawn there on the page, here in the editor.
 */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Helper from '../../block/utils/helper';

// Names that capitalisation alone gets wrong.
const NETWORK_NAMES = {
	x: 'X',
	youtube: 'YouTube',
	tiktok: 'TikTok',
	linkedin: 'LinkedIn',
	github: 'GitHub',
	gitlab: 'GitLab',
	whatsapp: 'WhatsApp',
	soundcloud: 'SoundCloud',
	wordpress: 'WordPress',
	deviantart: 'DeviantArt',
	mail: __( 'Email', 'social-icons-widget-by-wpzoom' ),
	mobile: __( 'Phone', 'social-icons-widget-by-wpzoom' ),
};

/**
 * The display name of a network, from its icon name.
 *
 * @param {string} icon Icon name, e.g. "youtube".
 * @return {string} Name, e.g. "YouTube".
 */
export function networkName( icon ) {
	if ( ! icon ) {
		return '';
	}
	return NETWORK_NAMES[ icon ] || Helper.humanizeIconLabel( icon );
}

/**
 * What a count is called on a network: subscribers on YouTube, members on
 * Discord, followers almost everywhere else.
 *
 * @param {string} icon Icon name.
 * @return {string} Label.
 */
export function defaultCountLabel( icon ) {
	switch ( icon ) {
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
 * A URL taken apart, or null when it can't be parsed.
 *
 * @param {string} url URL, with or without https://.
 * @return {?Object} { host, segments, hasQuery }.
 */
function parse( url ) {
	const value = String( url || '' ).trim();
	if ( ! value ) {
		return null;
	}
	try {
		const parsed = new URL( /^[a-z][a-z0-9+.-]*:/i.test( value ) ? value : `https://${ value }` );
		return {
			host: parsed.hostname.replace( /^www\./, '' ),
			segments: parsed.pathname
				.split( '/' )
				.filter( Boolean )
				.map( ( segment ) => {
					try {
						return decodeURIComponent( segment );
					} catch ( e ) {
						return segment;
					}
				} ),
			hasQuery: parsed.search.length > 1,
		};
	} catch ( e ) {
		return null;
	}
}

/**
 * The address without its clutter: "https://www.x.com/ciorici/" → "x.com/ciorici".
 *
 * @param {string} url URL.
 * @return {string} Short link.
 */
export function shortUrl( url ) {
	const value = String( url || '' ).trim();
	if ( /^(mailto|tel|sms):/i.test( value ) ) {
		return value.replace( /^[a-z]+:/i, '' ).replace( /\?.*$/, '' );
	}
	const parts = parse( value );
	if ( ! parts ) {
		return value;
	}
	return [ parts.host, ...parts.segments ].join( '/' );
}

/**
 * The profile's handle: "x.com/ciorici" → "@ciorici", "youtube.com/@wpzoom"
 * → "@wpzoom", "reddit.com/r/WordPress" → "r/WordPress". '' for addresses
 * with no clear handle (several path parts, a query), such as a Facebook
 * group: the profile then shows the network's name.
 *
 * @param {string} url URL.
 * @return {string} Handle, or ''.
 */
export function handle( url ) {
	const value = String( url || '' ).trim();
	if ( /^(mailto|tel|sms):/i.test( value ) ) {
		return shortUrl( value );
	}
	const parts = parse( value );
	if ( ! parts ) {
		return value;
	}
	const [ first, second ] = parts.segments;
	if ( ! first ) {
		return parts.host;
	}
	if ( first.charAt( 0 ) === '@' ) {
		return first;
	}
	if ( parts.hasQuery ) {
		return '';
	}
	if ( parts.segments.length === 1 ) {
		return `@${ first }`;
	}
	if ( parts.segments.length === 2 && [ 'r', 'u', 'user' ].indexOf( first ) > -1 && /reddit\./.test( parts.host ) ) {
		return `${ first === 'user' ? 'u' : first }/${ second }`;
	}
	return '';
}

/**
 * The text a profile shows, per the block's Display setting; its own text
 * wins when it has one.
 *
 * @param {Object} item    Profile.
 * @param {string} display "handle", "url" or "network".
 * @return {string} Text.
 */
export function profileText( item, display ) {
	if ( item.text ) {
		return item.text;
	}
	const name = item.network || networkName( item.icon );
	if ( display === 'network' || ! item.url ) {
		return name;
	}
	return display === 'url' ? shortUrl( item.url ) : handle( item.url ) || name;
}

/**
 * The words on a profile's button in the Buttons layout.
 *
 * @param {Object} item       Profile.
 * @param {string} buttonText The block's button text, "%s" for the network.
 * @return {string} Text.
 */
export function buttonLabel( item, buttonText ) {
	const name = item.network || networkName( item.icon );
	/* translators: %s: network name, e.g. Instagram */
	const pattern = buttonText || __( 'Follow on %s', 'social-icons-widget-by-wpzoom' );
	return pattern.indexOf( '%s' ) > -1 ? sprintf( pattern, name ) : pattern;
}

/**
 * A profile for a URL: its icon, network name and brand color when the URL
 * is recognised.
 *
 * @param {string} url  URL as typed.
 * @param {Object} base Profile to start from.
 * @return {Object} Profile.
 */
export function profileFromUrl( url, base = {} ) {
	const match = Helper.detectIcon( url );
	const next = { ...base, url: String( url || '' ).trim() };
	if ( match ) {
		next.iconKit = match.iconKit;
		next.icon = match.icon;
		next.network = networkName( match.icon );
		next.color = match.color || base.color || '';
	}
	return next;
}

/**
 * The Styles tab settings, as CSS variables for the block's wrapper (and, in
 * PHP, WPZOOM_Social_Profiles::style_vars()). Settings left empty set nothing,
 * so the layout's own look applies.
 */
const STYLE_VARS = {
	radius: [ '--wpz-sp-radius', 'px' ],
	borderWidth: [ '--wpz-sp-border-width', 'px' ],
	borderColor: [ '--wpz-sp-border-color', '' ],
	background: [ '--wpz-sp-bg', '' ],
	textColor: [ '--wpz-sp-text', '' ],
	padding: [ '--wpz-sp-pad', 'px' ],
	gap: [ '--wpz-sp-gap', 'px' ],
	iconSize: [ '--wpz-sp-icon-size', 'px' ],
	iconRadius: [ '--wpz-sp-icon-radius', 'px' ],
};

/**
 * @param {Object} attributes Block attributes.
 * @return {Object} CSS variables.
 */
export function styleVars( attributes ) {
	const vars = {};
	Object.keys( STYLE_VARS ).forEach( ( key ) => {
		const value = attributes[ key ];
		if ( value !== undefined && value !== null && value !== '' ) {
			vars[ STYLE_VARS[ key ][ 0 ] ] = `${ value }${ STYLE_VARS[ key ][ 1 ] }`;
		}
	} );
	return vars;
}
