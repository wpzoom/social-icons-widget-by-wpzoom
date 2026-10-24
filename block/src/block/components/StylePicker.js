/**
 * External dependencies
 */
import classnames from 'classnames';
import { omit } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { getSaveElement } from '@wordpress/blocks';
import { Button, PanelBody } from '@wordpress/components';
import TokenList from '@wordpress/token-list';

/**
 * Internal dependencies
 */
import Helper from '../utils/helper';
import { getStyleSwitchAttributes } from '../utils/style-variations';
import { fillIconSpans, useIconKits } from '../utils/svg-icons';

/**
 * `className` with its block style swapped for `styleName`.
 *
 * @param {string} className Current class names.
 * @param {string} styleName Style to apply.
 * @return {string} Class names.
 */
export function withStyleClass( className, styleName ) {
	const list = new TokenList( className || '' );
	Array.from( list.values() )
		.filter( ( token ) => token.indexOf( 'is-style-' ) === 0 )
		.forEach( ( token ) => list.remove( token ) );
	list.add( `is-style-${ styleName }` );
	return list.value;
}

/**
 * Visual block-style picker: each tile renders this block's own icons in that
 * style, using the block's save output, so you see the result before choosing.
 * Replaces WordPress's text-only style buttons, whose labels were truncated to
 * the same few words and whose hover preview shrank the icons to a few pixels.
 *
 * @param {Object}   props
 * @param {string}   props.name       Block name.
 * @param {Object}   props.attributes Block attributes.
 * @param {Function} props.onSelect   Called with a style name.
 */
export default function StylePicker( { name, attributes, onSelect } ) {
	const styles = useSelect(
		( select ) => select( 'core/blocks' ).getBlockStyles( name ),
		[ name ]
	);

	// The previews are the block's save output, whose icon spans are empty:
	// draw the icons into them as SVG once their kits have loaded.
	const gridRef = useRef();
	useIconKits( ( attributes.selectedIcons || [] ).map( ( item ) => item.iconKit ) );
	useEffect( () => {
		fillIconSpans( gridRef.current );
	} );

	if ( ! styles || ! styles.length ) {
		return null;
	}

	const active =
		Helper.getActiveStyle( styles, attributes.className ) ||
		styles.find( ( style ) => style.isDefault ) ||
		styles[ 0 ];

	return (
		<PanelBody
			title={ __( 'Style', 'social-icons-widget-by-wpzoom' ) }
			className="wpzoom-style-picker"
		>
			<div className="wpzoom-style-picker__grid" ref={ gridRef }>
				{ styles.map( ( style ) => {
					const withLabels = /label/.test( style.name );
					const next = getStyleSwitchAttributes( attributes, style.name ) || {};
					const icons = ( next.selectedIcons || attributes.selectedIcons || [] )
						.slice( 0, withLabels ? 2 : 3 )
						.map( ( icon ) => ( { ...icon, isActive: false, showPopover: false } ) );
					const previewAttributes = {
						...attributes,
						...omit( next, [ 'defaultIcon', 'selectedIcons' ] ),
						selectedIcons: icons,
						className: withStyleClass( attributes.className, style.name ),
						iconsAlignment: 'center',
						openLinkInNewTab: false,
					};
					const isActive = active && active.name === style.name;

					return (
						<div
							key={ style.name }
							className={ classnames( 'wpzoom-style-picker__item', {
								'is-active': isActive,
								'is-wide': withLabels,
							} ) }
						>
							{ /* The preview is the block's real markup (links included),
							     so it is inert and hidden from assistive tech; the button
							     laid over it is what you click or tab to. */ }
							<div className="wpzoom-style-picker__preview" aria-hidden="true" inert="">
								{ getSaveElement( name, previewAttributes ) }
							</div>
							<Button
								className="wpzoom-style-picker__button"
								onClick={ () => onSelect( style.name ) }
								aria-pressed={ isActive }
							>
								<span className="wpzoom-style-picker__label">
									{ style.label || style.name }
								</span>
							</Button>
						</div>
					);
				} ) }
			</div>
		</PanelBody>
	);
}
