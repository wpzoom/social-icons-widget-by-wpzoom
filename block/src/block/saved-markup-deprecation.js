/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';

const SELECTOR = '.wp-block-wpzoom-blocks-social-icons';

/**
 * An inline style string as a React style object, in the same order, so it
 * serializes back to the same string.
 *
 * @param {string} style Inline style.
 * @return {Object} Style object.
 */
const styleObject = ( style ) => {
	const object = {};
	String( style || '' )
		.split( ';' )
		.forEach( ( declaration ) => {
			const colon = declaration.indexOf( ':' );
			if ( colon < 1 ) {
				return;
			}
			const property = declaration.slice( 0, colon ).trim();
			const key = property.indexOf( '--' ) === 0 ? property : property.replace( /-([a-z])/g, ( m, letter ) => letter.toUpperCase() );
			object[ key ] = declaration.slice( colon + 1 ).trim();
		} );
	return object;
};

/**
 * The last resort: accepts the block's markup exactly as it was saved.
 *
 * Free and PRO save different markup (free: empty icon spans the page fills
 * in; PRO: each icon's SVG), and either one's markup changes over time. The
 * other deprecations rebuild known older versions; whatever they miss would
 * show "unexpected or invalid content". Here the wrapper's class and style and
 * its inside are read back from the post and saved as they were, so the block
 * validates; migrate() drops them and the next save uses this plugin's own
 * format. Until then the page keeps the markup it has.
 *
 * @param {Object} attributes The block's attributes.
 * @return {Object} Deprecation.
 */
export default function savedMarkupDeprecation( attributes ) {
	return {
		attributes: {
			...attributes,
			savedClass: { type: 'string', source: 'attribute', selector: SELECTOR, attribute: 'class' },
			savedStyle: { type: 'string', source: 'attribute', selector: SELECTOR, attribute: 'style' },
			savedHtml: { type: 'string', source: 'html', selector: SELECTOR },
		},
		// A real wrapper element: WordPress adds the block's class names to it
		// (a bare RawHTML would get wrapped in another div), and the inside
		// goes through as it was.
		save( { attributes: { savedClass, savedStyle, savedHtml } } ) {
			return (
				<div className={ savedClass } style={ savedStyle ? styleObject( savedStyle ) : undefined }>
					<RawHTML>{ savedHtml || '' }</RawHTML>
				</div>
			);
		},
		migrate: ( { savedClass, savedStyle, savedHtml, ...rest } ) => rest,
	};
}
