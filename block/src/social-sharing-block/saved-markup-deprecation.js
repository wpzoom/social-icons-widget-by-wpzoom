/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Accepts the block's markup as it was saved, whatever wrote it.
 *
 * The block is dynamic: the page is drawn by the PHP render callback, and the
 * saved HTML is never shown. Free and PRO save different icons and buttons, so
 * a block saved by one is "unexpected or invalid content" in the other. Here
 * the inside of the block is read back from the post (savedHtml, sourced from
 * the HTML) and saved as it was, so the block validates; migrate() drops
 * savedHtml and the next save uses this plugin's own format.
 *
 * @param {Object} attributes The block's attributes.
 * @return {Object} Deprecation.
 */
export default function savedMarkupDeprecation( attributes ) {
	return {
		attributes: {
			...attributes,
			savedHtml: {
				type: 'string',
				source: 'html',
				selector: '.wp-block-wpzoom-blocks-social-sharing',
			},
		},
		save( { attributes: { align, savedHtml } } ) {
			const blockProps = useBlockProps.save( {
				className: classnames( {
					[ `align-${ align }` ]: align,
					'wpzoom-social-sharing-block': true,
				} ),
			} );

			return (
				<div { ...blockProps } style={ { textAlign: align } }>
					<RawHTML>{ savedHtml }</RawHTML>
				</div>
			);
		},
		migrate: ( { savedHtml, ...rest } ) => rest,
	};
}
