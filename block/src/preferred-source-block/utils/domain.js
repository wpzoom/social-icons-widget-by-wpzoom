/**
 * Domain helpers, mirroring WPZOOM_Preferred_Source in PHP.
 */

const GOOGLE_URL = 'https://www.google.com/preferences/source';

const LOCAL_TLDS = [ 'local', 'localhost', 'test', 'example', 'invalid', 'internal', 'lan', 'home' ];

/**
 * Reduce a URL or host to the domain Google expects: no scheme, path, port or
 * leading "www.". Other subdomains are kept, as Google lists them separately.
 *
 * @param {string} value URL or host.
 * @return {string} Lowercase domain, or '' when there is none.
 */
export function normalizeDomain( value ) {
	let host = String( value || '' ).trim().toLowerCase();
	if ( ! host ) {
		return '';
	}
	host = host.replace( /^[a-z][a-z0-9+.-]*:\/\//, '' ); // scheme
	host = host.split( /[/?#]/ )[ 0 ]; // path, query, hash
	host = host.replace( /^[^@]*@/, '' ); // credentials
	host = host.replace( /:\d*$/, '' ); // port
	host = host.replace( /\.+$/, '' );
	return host.replace( /^www\./, '' );
}

/**
 * Whether a domain is a local or development address Google can't list.
 *
 * @param {string} domain Domain.
 * @return {boolean} True for local addresses.
 */
export function isLocalDomain( domain ) {
	if ( ! domain || domain.indexOf( '.' ) === -1 || /^[\d.]+$/.test( domain ) || domain.indexOf( ':' ) !== -1 ) {
		return true;
	}
	return LOCAL_TLDS.indexOf( domain.split( '.' ).pop() ) !== -1;
}

/**
 * Google's source preferences URL for a domain.
 *
 * @param {string} domain Domain.
 * @return {string} URL.
 */
export function googleUrl( domain ) {
	return `${ GOOGLE_URL }?q=${ encodeURIComponent( domain ) }`;
}
