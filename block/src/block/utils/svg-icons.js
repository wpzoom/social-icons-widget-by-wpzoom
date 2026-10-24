/* global wpzSocialIconsBlock */

/**
 * Icons as SVG in the block editor.
 *
 * A kit's icons come from the icon-kit REST route as name => inline SVG, the
 * same markup the front end shows. Each kit is fetched once, when an icon of
 * it is first shown, and the browser caches it (the URL carries a version that
 * changes with the icon library).
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const config = ( window.wpzSocialIconsBlock && wpzSocialIconsBlock.svgIcons ) || {};

const kits = {}; // kit => { data: object|null, promise }
const listeners = new Set();

const KIT_TOKENS = [ 'socicon', 'dashicons', 'genericon', 'academicons', 'fab', 'far', 'fas', 'fa' ];

/**
 * Whether icons are drawn as SVG (off when a site turns it off with the
 * wpzoom_social_icons_use_svg filter).
 *
 * @return {boolean} True when SVG is used.
 */
export const svgEnabled = () => !! config.enabled;

/**
 * Fetch a kit's icons (once).
 *
 * @param {string} kit Kit name.
 * @return {Promise<Object|null>} Name => SVG markup.
 */
export function loadKit( kit ) {
	if ( ! svgEnabled() || ! kit || KIT_TOKENS.indexOf( kit ) === -1 ) {
		return Promise.resolve( null );
	}
	if ( ! kits[ kit ] ) {
		const done = ( data ) => {
			kits[ kit ].data = data || {};
			listeners.forEach( ( listener ) => listener() );
			return kits[ kit ].data;
		};
		kits[ kit ] = { data: null };
		kits[ kit ].promise = apiFetch( { path: `${ config.restPath }${ kit }?v=${ config.version }` } )
			.then( done )
			.catch( () => done( {} ) );
	}
	return kits[ kit ].promise;
}

/**
 * An icon's SVG markup, if its kit has loaded.
 *
 * @param {string} kit  Kit name.
 * @param {string} icon Icon name.
 * @return {?string} SVG markup, or null.
 */
export function getSvg( kit, icon ) {
	const entry = kits[ kit ];
	return entry && entry.data && entry.data[ icon ] ? entry.data[ icon ] : null;
}

/**
 * Load these kits and re-render the component when any kit arrives.
 *
 * @param {string[]} kitList Kit names.
 */
export function useIconKits( kitList ) {
	const [ , setVersion ] = useState( 0 );
	useEffect( () => {
		const listener = () => setVersion( ( v ) => v + 1 );
		listeners.add( listener );
		return () => listeners.delete( listener );
	}, [] );
	const key = kitList.filter( Boolean ).join( ',' );
	useEffect( () => {
		kitList.forEach( loadKit );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ key ] );
}

/**
 * Kit and icon name from an icon span's class list, e.g. 'social-icon fab fa-x'.
 *
 * @param {string} className Class attribute.
 * @return {?Array} [ kit, icon ], or null.
 */
export function parseIconClasses( className ) {
	const tokens = String( className || '' ).split( /\s+/ );
	for ( const token of tokens ) {
		if ( KIT_TOKENS.indexOf( token ) === -1 ) {
			continue;
		}
		const prefix = [ 'fab', 'far', 'fas', 'fa' ].indexOf( token ) > -1 ? 'fa-' : token + '-';
		const name = tokens.find( ( t ) => t.indexOf( prefix ) === 0 && t.length > prefix.length );
		if ( name ) {
			return [ token, name.slice( prefix.length ) ];
		}
	}
	return null;
}

/**
 * Fill the empty icon spans inside a DOM node with their SVG (for markup
 * rendered from the block's save output, like the style previews).
 *
 * @param {?Element} root Container.
 * @return {string[]} Kits seen, so the caller can load them.
 */
export function fillIconSpans( root ) {
	const seen = [];
	if ( ! root || ! svgEnabled() ) {
		return seen;
	}
	root.querySelectorAll( 'span.social-icon' ).forEach( ( el ) => {
		if ( el.querySelector( ':scope > .wpzoom-svg-icon' ) ) {
			// React rewrites className on re-render; the SVG stays.
			el.classList.add( 'wpzoom-svg' );
			return;
		}
		if ( el.childElementCount ) {
			return;
		}
		const parsed = parseIconClasses( el.className );
		if ( ! parsed ) {
			return;
		}
		seen.push( parsed[ 0 ] );
		const svg = getSvg( parsed[ 0 ], parsed[ 1 ] );
		if ( svg ) {
			el.innerHTML = svg;
			el.classList.add( 'wpzoom-svg' );
		}
	} );
	return seen;
}
