/* global wpzSocialIconsBlock */

import URI from 'urijs';
import { find, findKey, forEach, isEmpty, isObject } from 'lodash';

const { icons, iconKitsCategories } = wpzSocialIconsBlock;
const svgConfig = wpzSocialIconsBlock.svgIcons || {};

/**
 * Kits in lookup order: the order of the "Select Icon Kit" dropdown, which is
 * Socicons first, then Dashicons, Genericons, Academicons and the Font Awesome
 * sets — limited to the kits enabled in Settings when kit sync is on.
 */
const KIT_ORDER = Object.values( iconKitsCategories || {} ).map( ( kit ) => kit.value );

/**
 * Normalise a search term: case-insensitive, spaces match hyphens.
 *
 * @param {string} term Search term.
 * @return {string} Normalised term.
 */
const normaliseSearch = ( term ) =>
	String( term || '' ).trim().toLowerCase().replace( /\s+/g, '-' );

const iconName = ( item ) => ( isObject( item ) ? item.icon : item ); // Items are objects or plain names.

import TokenList from '@wordpress/token-list';

class Helper {
	static filterIcons( searchIcon ) {
		const collector = {};

		if ( searchIcon === '' ) {
			return icons;
		}

		// Special case for WordPress - add both dashicons and genericon variants
		if (searchIcon.toLowerCase() === 'wordpress') {
			const wordpressCollector = {};
			
			// Look for wordpress icons in different icon sets
			forEach( icons, ( iconsArray, key ) => {
				wordpressCollector[ key ] = iconsArray.filter( ( item ) => {
					if ( isObject( item ) ) {
						return item.icon === 'wordpress' || item.icon === 'wordpress-alt';
					}
					return item === 'wordpress' || item === 'wordpress-alt';
				} );
			} );
			
			return wordpressCollector;
		}

		const term = normaliseSearch( searchIcon );
		forEach( icons, ( iconsArray, key ) => {
			collector[ key ] = iconsArray.filter(
				( item ) => String( iconName( item ) ).toLowerCase().indexOf( term ) > -1
			);
		} );

		return collector;
	}

	/**
	 * Kits to look in, in priority order (see KIT_ORDER).
	 *
	 * @return {string[]} Kit keys.
	 */
	static kitOrder() {
		return KIT_ORDER.filter( ( kit ) => Array.isArray( icons[ kit ] ) );
	}

	/**
	 * Human label of a kit, e.g. "Font Awesome Brands".
	 *
	 * @param {string} kit Kit key.
	 * @return {string} Label.
	 */
	static kitLabel( kit ) {
		const found = Object.values( iconKitsCategories || {} ).find( ( k ) => k.value === kit );
		return found ? found.label : kit;
	}

	/**
	 * The registry's brand color for an icon, or null. Kits without colors
	 * (Genericons, Dashicons) borrow the same icon's color from another kit.
	 *
	 * @param {string} iconKit Kit key.
	 * @param {string} icon    Icon name.
	 * @return {?string} Hex color.
	 */
	static brandColor( iconKit, icon ) {
		const colorIn = ( kit ) => {
			const item = ( icons[ kit ] || [] ).find( ( o ) => iconName( o ) === icon );
			return item && isObject( item ) && item.color ? item.color : null;
		};
		const own = colorIn( iconKit );
		if ( own ) {
			return own;
		}
		for ( const kit of Object.keys( icons ) ) {
			const borrowed = colorIn( kit );
			if ( borrowed ) {
				return borrowed;
			}
		}
		return null;
	}

	/**
	 * Add https:// to a bare domain ("reddit.com"); keep mailto:, tel:, etc.
	 *
	 * @param {string} url URL as typed.
	 * @return {string} URL with a scheme.
	 */
	static normalizeUrl( url ) {
		const value = String( url || '' ).trim();
		if ( ! value ) {
			return value;
		}
		return isEmpty( new URI( value ).protocol() ) ? `https://${ value }` : value;
	}

	/**
	 * Whether an icon belongs to a network that has shut down or been renamed
	 * (includes/retired-icons.php). Existing icons keep working; the picker
	 * hides these and URL detection skips them.
	 *
	 * @param {string} kit  Kit name.
	 * @param {string} icon Icon name.
	 * @return {boolean} True when retired.
	 */
	static isRetired( kit, icon ) {
		const list = ( svgConfig.retired || {} )[ kit ];
		return Array.isArray( list ) && list.indexOf( icon ) > -1;
	}

	/**
	 * Whether an icon can be drawn: icons marked svg_only have no font glyph,
	 * so they need SVG (on unless a site turned it off).
	 *
	 * @param {Object|string} item Icon data item.
	 * @return {boolean} True when the icon can be shown.
	 */
	static canDraw( item ) {
		return ! ( isObject( item ) && item.svg_only ) || !! svgConfig.enabled;
	}

	/**
	 * The icon for a URL: the platform name from the address, looked up kit by
	 * kit in priority order — Socicons first, falling back to the other enabled
	 * kits — and the first exact match wins. Retired networks are skipped.
	 *
	 * @param {string} url Typed or saved URL; a bare domain is fine.
	 * @return {?{iconKit: string, icon: string, color: ?string, label: string}} Match, or null.
	 */
	static detectIcon( url ) {
		const value = String( url || '' ).trim();
		if ( ! value ) {
			return null;
		}
		let name;
		try {
			name = Helper.filterUrlScheme( Helper.normalizeUrl( value ) );
		} catch ( e ) {
			return null; // Half-typed URLs can be unparsable.
		}
		if ( ! name ) {
			return null;
		}
		for ( const kit of Helper.kitOrder() ) {
			const item = icons[ kit ].find(
				( o ) => iconName( o ) === name && Helper.canDraw( o ) && ! Helper.isRetired( kit, name )
			);
			if ( item ) {
				return {
					iconKit: kit,
					icon: name,
					color: Helper.brandColor( kit, name ),
					label: Helper.humanizeIconLabel( name ),
				};
			}
		}
		return null;
	}

	/**
	 * Whether an icon is still blank: just added, with no icon chosen yet.
	 *
	 * @param {Object} item Icon from selectedIcons.
	 * @return {boolean} True when blank.
	 */
	static isBlankIcon( item ) {
		return ! item.icon && ! ( item.iconKit === 'svg' && item.customSvg );
	}

	static filterUrlScheme( url ) {
		const schemas = {
			mailto: 'mail',
			viber: 'viber',
			skype: 'skype',
			tg: 'tg',
			tel: 'mobile',
			sms: 'comments',
			fax: 'fax',
			news: 'newspaper-o',
			feed: 'rss',
		};

		const domains = {
			'feedburner.google.com': 'rss',
			'ok.ru': 'odnoklassniki',
			'yt.com': 'youtube',
			'fb.com': 'facebook',
			't.me': 'telegram',
            'm.me': 'messenger',
			'wa.me': 'whatsapp',
			'zen.yandex.com': 'zen-yandex',
			'zen.yandex.ru': 'zen-yandex',
            'bsky.app': 'bluesky',
            'wordpress.org': 'wordpress',
            'wordpress.com': 'wordpress',
			'wp.org': 'wordpress',
			'youtu.be': 'youtube',
			'fb.me': 'facebook',
			'instagr.am': 'instagram',
			'pin.it': 'pinterest',
			'lnkd.in': 'linkedin',
			'threads.net': 'threads',
			'twitter.com': 'x',
			'linktr.ee': 'linktree',
			'buymeacoffee.com': 'buy-me-a-coffee',
			'cash.app': 'cash-app',
			'producthunt.com': 'product-hunt',
			'dev.to': 'dev-to',
			'music.apple.com': 'apple-music',
			'podcasts.apple.com': 'apple-podcasts',
			'music.youtube.com': 'youtube-music',
			'play.google.com': 'google-play',
			'epicgames.com': 'epic-games',
			'store.epicgames.com': 'epic-games',
			'warpcast.com': 'farcaster',
			'farcaster.xyz': 'farcaster',
			'bere.al': 'bereal',
			'open.kakao.com': 'kakaotalk',
			'pf.kakao.com': 'kakaotalk',
			'gofund.me': 'gofundme',
		};

		// Common domain name mappings
		const commonDomains = {
			'wordpress': 'wordpress',
			'twitter': 'x', // Twitter is X; the bird is still in the picker.
			'x': 'x',
			'facebook': 'facebook',
			'instagram': 'instagram',
			'linkedin': 'linkedin',
			'youtube': 'youtube',
			'pinterest': 'pinterest',
			'github': 'github',
			'spotify': 'spotify',
			'tiktok': 'tiktok',
			'itch': 'itch-io',
		};

		const uri = new URI( url );

		// First check if the full hostname is in our domains list
		const fullHostname = uri.hostname();
		if (domains[fullHostname]) {
			return domains[fullHostname];
		}

		// Then try to extract the domain name without TLD
		let domainParts = fullHostname ? fullHostname.split('.') : [];
		let domain = '';
		
		if (domainParts.length >= 2) {
			// Check for subdomains like shop.example.com
			// First try the subdomain
			domain = domainParts[0];
			let mainDomain = domainParts[domainParts.length - 2]; // Get the main domain name
			
			// Check if the subdomain or main domain is in our common domains list
			if (commonDomains[domain]) {
				return commonDomains[domain];
			}
			if (commonDomains[mainDomain]) {
				return commonDomains[mainDomain];
			}
		}
		
		// Fall back to the first part of the domain or the scheme
		domain = uri.domain() !== undefined
			? uri.domain().split('.').shift()
			: uri.scheme();

		const schemaHasIcon = findKey( schemas, ( val, key ) => {
			return key === uri.scheme();
		} );

		domain = schemaHasIcon !== undefined ? schemas[ schemaHasIcon ] : domain;

		const domainHasIcon = findKey( domains, ( val, key ) => {
			return key === uri.hostname();
		} );

		return domainHasIcon !== undefined ? domains[ domainHasIcon ] : domain;
	}

	static hyphensToSpaces( s ) {
		return s.replace( /-/g, ' ' );
	}

	static capitalize( s ) {
		if ( typeof s !== 'string' ) {
			return '';
		}
		return s.charAt( 0 ).toUpperCase() + s.slice( 1 );
	}

	static humanizeIconLabel( s ) {
		return this.hyphensToSpaces( this.capitalize( s ) );
	}

	static getBlockStyle( className ) {
		const regex = /is-style-(\S*)/g;
		const m = regex.exec( className );
		return m !== null ? m[ 1 ] : null;
	}

	static getIconClassList( iconKit, icon ) {
		const classes = { 'social-icon': true };
		classes[ iconKit ] = true;

		if ( [ 'fab', 'fas', 'far' ].includes( iconKit ) ) {
			classes[ 'fa-' + icon ] = true;
		} else {
			classes[ iconKit + '-' + icon ] = true;
		}

		return classes;
	}

	static addPercentagePipe( value ) {
		return `${ value }%`;
	}

	static addPercentageHalfPipe( value ) {
		return `${ 0.5 * value }%/${ value }%`;
	}

	static addPixelsPipe( value ) {
		return `${ value }px`;
	}

	static arrayMoveMutate( array, from, to ) {
		array.splice( to < 0 ? array.length + to : to, 0, array.splice( from, 1 )[ 0 ] );
	}

	static arrayMove( array, from, to ) {
		array = array.slice();
		this.arrayMoveMutate( array, from, to );
		return array;
	}

	static getActiveStyle( styles, className ) {
		for ( const style of new TokenList( className ).values() ) {
			if ( style.indexOf( 'is-style-' ) === -1 ) {
				continue;
			}

			const potentialStyleName = style.substring( 9 );
			const activeStyle = find( styles, { name: potentialStyleName } );
			if ( activeStyle ) {
				return activeStyle;
			}
		}

		return find( styles, [ 'isDefault', true ] );
	}
}

export default Helper;
