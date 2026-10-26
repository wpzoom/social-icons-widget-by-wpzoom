/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Button, Modal, SearchControl } from '@wordpress/components';
import { chevronRight } from '@wordpress/icons';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Helper from '../utils/helper';

/**
 * The icon picker, laid out like the block editor's own icon library: search
 * and the icon kits on the left, the icons with their names on the right.
 * Choosing an icon applies it and closes the dialog; the link, colors and
 * label are set in the icon's popover.
 *
 * @param {Object}   props
 * @param {string}   props.icon        The icon chosen now (may be empty).
 * @param {string}   props.iconKit     Its kit (may be empty).
 * @param {Function} props.renderGlyph ( iconKit, icon ) => element.
 * @param {Function} props.onSelect    ( iconKit, icon ) => void.
 * @param {Function} props.onClose     Closes the dialog.
 */
export default function IconLibrary( { icon, iconKit, renderGlyph, onSelect, onClose } ) {
	const kits = Helper.kitOrder();
	const [ search, setSearch ] = useState( '' );
	const [ kit, setKit ] = useState( kits.indexOf( iconKit ) > -1 ? iconKit : kits[ 0 ] );
	const selectedRef = useRef();
	const contentRef = useRef();

	const searching = search.trim() !== '';

	// No networks that have shut down (the icon already chosen stays) and no
	// icons that can't be drawn.
	const visible = ( kitName, items ) =>
		( items || [] ).filter(
			( item ) =>
				Helper.canDraw( item ) &&
				( ! Helper.isRetired( kitName, item.icon ) || ( icon === item.icon && iconKit === kitName ) )
		);

	const all = useMemo( () => Helper.filterIcons( '' ), [] );
	const groups = useMemo( () => {
		if ( ! searching ) {
			return [ { kit, items: visible( kit, all[ kit ] ) } ];
		}
		const found = Helper.filterIcons( search );
		return kits
			.map( ( kitName ) => ( { kit: kitName, items: visible( kitName, found[ kitName ] ) } ) )
			.filter( ( group ) => group.items.length );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ search, kit ] );

	// Bring the chosen icon into view when the dialog opens or the kit changes;
	// other kits, and search results, start at the top.
	useEffect( () => {
		if ( ! searching && selectedRef.current ) {
			selectedRef.current.scrollIntoView( { block: 'center' } );
		} else if ( contentRef.current ) {
			contentRef.current.scrollTop = 0;
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ kit, searching ] );

	const renderItem = ( kitName, item ) => {
		const isSelected = icon === item.icon && iconKit === kitName;
		const name = Helper.humanizeIconLabel( item.icon );
		return (
			<button
				type="button"
				key={ `${ kitName }-${ item.icon }` }
				ref={ isSelected ? selectedRef : undefined }
				className={ classnames( 'wpzoom-icon-library__item', { 'is-selected': isSelected } ) }
				aria-pressed={ isSelected }
				title={ searching ? `${ name } — ${ Helper.kitLabel( kitName ) }` : name }
				onClick={ () => onSelect( kitName, item.icon ) }
			>
				<span className="wpzoom-icon-library__glyph" aria-hidden="true">
					{ renderGlyph( kitName, item.icon ) }
				</span>
				<span className="wpzoom-icon-library__name">{ name }</span>
			</button>
		);
	};

	return (
		<Modal
			className="wpzoom-icon-library"
			title={ __( 'Icon library', 'social-icons-widget-by-wpzoom' ) }
			onRequestClose={ onClose }
		>
			<div className="wpzoom-icon-library__layout">
				<div className="wpzoom-icon-library__sidebar">
					<SearchControl
						__nextHasNoMarginBottom
						value={ search }
						onChange={ setSearch }
						placeholder={ __( 'Search all icons', 'social-icons-widget-by-wpzoom' ) }
					/>
					<div className="wpzoom-icon-library__kits" role="tablist" aria-orientation="vertical">
						{ kits.map( ( kitName ) => (
							<Button
								key={ kitName }
								role="tab"
								aria-selected={ ! searching && kitName === kit }
								className={ classnames( 'wpzoom-icon-library__kit', {
									'is-active': ! searching && kitName === kit,
								} ) }
								onClick={ () => {
									setSearch( '' );
									setKit( kitName );
								} }
							>
								<span>{ Helper.kitLabel( kitName ) }</span>
								<span className="wpzoom-icon-library__count">
									{ visible( kitName, all[ kitName ] ).length }
								</span>
								{ ! searching && kitName === kit && <span className="wpzoom-icon-library__chevron">{ chevronRight }</span> }
							</Button>
						) ) }
					</div>
				</div>

				<div className="wpzoom-icon-library__content" ref={ contentRef }>
					{ groups.length ? (
						groups.map( ( group ) => (
							<div key={ group.kit } className="wpzoom-icon-library__group">
								{ searching && (
									<h3 className="wpzoom-icon-library__group-title">
										{ Helper.kitLabel( group.kit ) }
										<span className="wpzoom-icon-library__count">{ group.items.length }</span>
									</h3>
								) }
								<div className={ classnames( 'wpzoom-icon-library__grid', `${ group.kit }-wrapper` ) }>
									{ group.items.map( ( item ) => renderItem( group.kit, item ) ) }
								</div>
							</div>
						) )
					) : (
						<p className="wpzoom-icon-library__empty">
							{ sprintf(
								/* translators: %s: search term */
								__( 'No icons match “%s”.', 'social-icons-widget-by-wpzoom' ),
								search.trim()
							) }
						</p>
					) }
				</div>
			</div>
		</Modal>
	);
}
