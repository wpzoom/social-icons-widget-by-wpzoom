/* eslint-disable react/jsx-no-target-blank */

/**
 * External dependencies
 */
import { get, isEmpty, omit, find } from 'lodash';
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef, Fragment } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Icon, Button, Popover, Modal, TextareaControl, TextControl } from '@wordpress/components';
import { AlignmentControl, BlockControls, useBlockProps } from '@wordpress/block-editor';
// SVG icons for controls drawn inside the editor canvas (an iframe that only has
// the Dashicons font when that icon kit is enabled); Dashicon names there can
// render as empty boxes.
import { plus, check, info, code, trash, link } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Helper from '../utils/helper';
import IconLibrary from './IconLibrary';
import Inspector from './Inspector';
import PopoverSearch from './PopoverSearch';
import SortableArrows from './SortableArrows';
import ModalColorPicker from './ModalColorPicker';
import IconGlyph from './IconGlyph';
import { getStyleVariations, getStyleSwitchAttributes } from '../utils/style-variations';

export default function Edit( props ) {
	const { attributes, setAttributes, isSelected, name } = props;

	const [ isCustomSvgModalOpen, setIsCustomSvgModalOpen ] = useState( false );
	const [ customSvgCode, setCustomSvgCode ] = useState( '' );
	const [ activeIconKey, setActiveIconKey ] = useState( null );

	const blockStyles = useSelect(
		( select ) => select( 'core/blocks' ).getBlockStyles( name ),
		[ name ]
	);

	const blockProps = useBlockProps();

	const activeStyle = Helper.getActiveStyle( blockStyles, blockProps.className );
	const activeStyleName = ( activeStyle && activeStyle.name ) || '';

	// componentDidMount: apply initial style variation once
	useEffect( () => {
		if ( attributes.wasStyled === true ) {
			return;
		}
		const styleVariation = getStyleVariations( activeStyleName, activeStyleName );
		if ( ! isEmpty( styleVariation ) ) {
			styleVariation.wasStyled = true;
			setAttributes( omit( styleVariation, [ 'selectedIcons' ] ) );

			const clonedSelectedIcons = JSON.parse(
				JSON.stringify( attributes.selectedIcons )
			);

			if ( ! isEmpty( styleVariation.selectedIcons ) ) {
				clonedSelectedIcons.map( ( item ) => {
					const current = find( styleVariation.selectedIcons, [
						'icon',
						item.icon,
					] );
					item.color = isEmpty( current )
						? styleVariation.defaultIcon.color
						: current.color;
					item.hoverColor = isEmpty( current )
						? styleVariation.defaultIcon.hoverColor
						: current.hoverColor;
					return item;
				} );
			}

			if ( ! isEmpty( styleVariation.iconsColor ) ) {
				clonedSelectedIcons.map( ( item ) => {
					item.color = styleVariation.iconsColor;
					return item;
				} );
			}
			if ( ! isEmpty( styleVariation.iconsHoverColor ) ) {
				clonedSelectedIcons.map( ( item ) => {
					item.hoverColor = styleVariation.iconsHoverColor;
					return item;
				} );
			}

			setAttributes( { selectedIcons: clonedSelectedIcons } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	/**
	 * An icon as drawn in the popover's URL field. Same classes as the canvas:
	 * the icon fonts only draw glyphs for `.social-icon.<kit>-<icon>`, so
	 * `social-icon` stays and its tile styling is reset in editor.scss.
	 *
	 * @param {string} iconKit   Kit.
	 * @param {string} icon      Icon name.
	 * @param {string} customSvg Custom SVG markup, for the "svg" kit.
	 */
	const renderUrlFieldGlyph = ( iconKit, icon, customSvg = null ) =>
		iconKit === 'svg' && customSvg ? (
			<span
				className="url-input-icon-glyph"
				dangerouslySetInnerHTML={ { __html: customSvg } }
			/>
		) : (
			<IconGlyph className="url-input-icon-glyph" iconKit={ iconKit } icon={ icon } />
		);

	// Always points at the latest attributes so async callbacks (e.g. timers)
	// never operate on a stale closure snapshot.
	const latestAttributesRef = useRef( attributes );
	latestAttributesRef.current = attributes;

	// componentDidUpdate: respond to block style panel changes
	const prevClassNameRef = useRef( blockProps.className );
	useEffect( () => {
		const prevClassName = prevClassNameRef.current;
		prevClassNameRef.current = blockProps.className;

		if (
			Helper.getBlockStyle( prevClassName ) ===
			Helper.getBlockStyle( blockProps.className )
		) {
			return;
		}

		// Same attributes the style picker previews (utils/style-variations.js).
		const next = getStyleSwitchAttributes( attributes, activeStyleName );
		if ( next ) {
			setAttributes( omit( next, [ 'selectedIcons' ] ) );
			setAttributes( { selectedIcons: next.selectedIcons } );
		}
	}, [ blockProps.className ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const closeModal = () => setAttributes( { showModal: false } );

	const getIconsAlignmentStyle = ( alignment ) => {
		const styles = {
			left: 'flex-start',
			right: 'flex-end',
			center: 'center',
		};
		return styles[ alignment ];
	};

	const setAlignment = ( alignment ) =>
		setAttributes( { iconsAlignment: alignment } );

	/**
	 * Applies the icon chosen in the icon library to the active icon: its
	 * brand color comes with it, and the label follows it unless it was
	 * changed by hand.
	 *
	 * @param {string} iconKit Kit.
	 * @param {string} icon    Icon name.
	 */
	const selectLibraryIcon = ( iconKit, icon ) => {
		const index = attributes.activeIconIndex;
		const current = attributes.selectedIcons[ index ];
		const autoLabel = current.icon ? Helper.humanizeIconLabel( current.icon ) : '';
		// The new icon's brand color. Without one, a color that only came with
		// the previous icon goes back to neutral; a color picked by hand stays.
		const oldBrand = current.icon ? Helper.brandColor( current.iconKit, current.icon ) : null;
		const brand =
			Helper.brandColor( iconKit, icon ) ||
			( oldBrand && current.color === oldBrand ? '#444140' : null );
		setAttributes( {
			selectedIcons: attributes.selectedIcons.map( ( item, i ) =>
				i === index
					? {
						...item,
						iconKit,
						icon,
						label: ! item.label || item.label === autoLabel ? Helper.humanizeIconLabel( icon ) : item.label,
						...( brand ? { color: brand, hoverColor: brand } : {} ),
						// A hand-picked icon is the icon for this URL now.
						iconDetected: undefined,
						showPopover: true,
						isActive: true,
					  }
					: item
			),
			showModal: false,
		} );
	};

	const insertIcon = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		if ( e.detail === 0 ) {
			return;
		}

		// Blank until a link is typed or an icon chosen (Helper.isBlankIcon), in
		// a neutral color: the icon's brand color comes with the icon. (It used to
		// take the style's default-icon color, Facebook blue.)
		const defaultIcon = {
			url: '',
			icon: '',
			iconKit: '',
			color: '#444140',
			hoverColor: '#444140',
			label: '',
			showPopover: true,
			isActive: true,
			customSvg: null,
		};


		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone.map( ( item ) => ( item.isActive = false ) );
		const key = selectedIconsClone.push( defaultIcon );
		setAttributes( { selectedIcons: selectedIconsClone, activeIconIndex: key - 1 } );
	};

	// eslint-disable-next-line no-unused-vars
	const onClickIconHandler = ( e, key, iconObject ) => {
		e.preventDefault();
		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone.map( ( item ) => ( item.isActive = false ) );
		selectedIconsClone[ key ].showPopover = true;
		selectedIconsClone[ key ].isActive = true;
		setAttributes( { activeIconIndex: key, selectedIcons: selectedIconsClone } );
	};

	const popoverCloseHandler = ( key ) => {
		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone[ key ].showPopover = false;
		setAttributes( { selectedIcons: selectedIconsClone } );
	};


	const popoverDeleteIconHandler = ( e, key ) => {
		e.stopPropagation();
		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone.splice( key, 1 );
		setAttributes( { selectedIcons: selectedIconsClone, activeIconIndex: 0 } );
	};

	const popoverEditSettingsHandler = ( e, key ) => {
		e.stopPropagation();
		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone[ key ].showPopover = false;
		setAttributes( { showModal: true, selectedIcons: selectedIconsClone } );
	};

	/**
	 * While a URL is typed in the popover, switches the icon to the one it
	 * belongs to (the URL itself is saved on Enter or when leaving the field).
	 *
	 * @param {number} key   Icon index.
	 * @param {string} typed URL as typed so far.
	 */
	const popoverTypeHandler = ( key, typed ) => {
		const match = Helper.detectIcon( typed );
		const icons = latestAttributesRef.current.selectedIcons;
		const current = icons[ key ];
		if ( ! match || ! current || ( current.iconKit === match.iconKit && current.icon === match.icon ) ) {
			return;
		}
		setAttributes( {
			selectedIcons: icons.map( ( item, i ) =>
				i === key
					? {
						...item,
						iconKit: match.iconKit,
						icon: match.icon,
						label: match.label,
						...( match.color ? { color: match.color, hoverColor: match.color } : {} ),
					  }
					: item
			),
		} );
	};

	/**
	 * Saves an icon's URL from the popover.
	 *
	 * @param {number}  key    Icon index.
	 * @param {string}  newUrl URL as typed.
	 * @param {boolean} detect Also pick the icon the URL belongs to. Off for a
	 *                         single icon, where the icon was chosen first and
	 *                         the link is optional.
	 */
	const popoverSearchHandler = ( key, newUrl, detect = true ) => {
		newUrl = Helper.normalizeUrl( newUrl );

		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);

		// Socicons first, then the other enabled kits; first exact match wins.
		const match = detect ? Helper.detectIcon( newUrl ) : null;
		const iconDetected = !! match;
		if ( match ) {
			selectedIconsClone[ key ].iconKit = match.iconKit;
			selectedIconsClone[ key ].icon = match.icon;
			selectedIconsClone[ key ].label = match.label;
			if ( match.color ) {
				selectedIconsClone[ key ].color = match.color;
				selectedIconsClone[ key ].hoverColor = match.color;
			}
		}

		selectedIconsClone[ key ].url = newUrl;
		selectedIconsClone[ key ].showPopover = true;
		selectedIconsClone[ key ].iconDetected = iconDetected;
		selectedIconsClone[ key ].justUpdated = true;

		setAttributes( { selectedIcons: selectedIconsClone } );

		// Hide the status message after a short delay. Read the *current*
		// attributes via the ref: the `attributes` variable in this closure is a
		// snapshot from before setAttributes() ran, and writing it back would
		// revert the icon that was just detected.
		setTimeout( () => {
			const currentIcons = latestAttributesRef.current.selectedIcons;
			if ( ! currentIcons || ! currentIcons[ key ] || ! currentIcons[ key ].justUpdated ) {
				return;
			}
			const resetIconsClone = JSON.parse( JSON.stringify( currentIcons ) );
			resetIconsClone[ key ].justUpdated = false;
			setAttributes( { selectedIcons: resetIconsClone } );
		}, 2000 );
	};

	const moveLeftHandler = ( e, key ) => {
		let selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone = Helper.arrayMove( selectedIconsClone, key, key - 1 );
		setAttributes( { selectedIcons: selectedIconsClone, activeIconIndex: key - 1 } );
	};

	const moveRightHandler = ( e, key ) => {
		let selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone = Helper.arrayMove( selectedIconsClone, key, key + 1 );
		setAttributes( { selectedIcons: selectedIconsClone, activeIconIndex: key + 1 } );
	};

	const getRelAttr = () => {
		let relAttr = [];
		if ( attributes.nofollow ) relAttr.push( 'nofollow' );
		if ( attributes.noreferrer ) relAttr.push( 'noreferrer' );
		if ( attributes.noopener ) relAttr.push( 'noopener' );
		if ( attributes.relme ) relAttr.push( 'me' );
		if ( attributes.openLinkInNewTab ) relAttr = [ 'noopener' ];
		return relAttr;
	};

	const getTarget = () =>
		attributes.openLinkInNewTab ? '_blank' : undefined;

	const openCustomSvgModal = ( key ) => {
		setIsCustomSvgModalOpen( true );
		setActiveIconKey( key );
		setCustomSvgCode( '' );
	};

	const closeCustomSvgModal = () => {
		setIsCustomSvgModalOpen( false );
		setCustomSvgCode( '' );
		setActiveIconKey( null );
	};

	const applySvgIcon = () => {
		if ( ! customSvgCode || customSvgCode.trim() === '' ) return;
		if ( activeIconKey === null ) return;

		const sanitizedSvg = customSvgCode
			.replace( /javascript:/gi, '' )
			.replace( /on\w+=/gi, '' )
			.replace( /data:/gi, '' );

		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone[ activeIconKey ].iconKit = 'svg';
		selectedIconsClone[ activeIconKey ].icon = 'custom-svg';
		selectedIconsClone[ activeIconKey ].customSvg = sanitizedSvg;
		selectedIconsClone[ activeIconKey ].showPopover = false;

		if (
			! selectedIconsClone[ activeIconKey ].label ||
			selectedIconsClone[ activeIconKey ].label === ''
		) {
			selectedIconsClone[ activeIconKey ].label = __(
				'Custom Icon',
				'social-icons-widget-by-wpzoom'
			);
		}

		setAttributes( { selectedIcons: selectedIconsClone } );
		closeCustomSvgModal();
	};

	// --- Render ---

	let className = blockProps.className;

	if ( Helper.getBlockStyle( className ) === null ) {
		className = classnames( className, 'is-style-with-canvas-round' );
	}
	if ( attributes.showIconsLabel ) {
		className = classnames( className, 'show-icon-labels-style' );
	}

	const relAttr = getRelAttr();
	const target = getTarget();

	// One icon is "Icon by WPZOOM" (icon-variation.js): its popover leads
	// with the icon and keeps the link optional.
	const singleIcon = attributes.selectedIcons.length === 1;

	const IconsList = attributes.selectedIcons.map( ( list, key ) => {
		const showIconsLabel = attributes.showIconsLabel ? (
			<span className={ classnames( 'icon-label' ) }>{ list.label }</span>
		) : (
			''
		);

		const isBlank = Helper.isBlankIcon( list );

		let iconContent;
		if ( isBlank ) {
			iconContent = (
				<span className="social-icon social-icon-placeholder">
					<Icon icon={ link } />
				</span>
			);
		} else if ( list.iconKit === 'svg' && list.customSvg ) {
			iconContent = (
				<span
					className={ classnames( 'social-icon', 'social-icon-svg' ) }
					dangerouslySetInnerHTML={ { __html: list.customSvg } }
				/>
			);
		} else {
			iconContent = <IconGlyph iconKit={ list.iconKit } icon={ list.icon } />;
		}

		return (
			<Fragment key={ key }>
				<a
					onClick={ ( e ) => onClickIconHandler( e, key, list ) }
					href={ list.url }
					className={ classnames( 'social-icon-link', {
						selected: list.isActive,
						'is-placeholder': isBlank,
					} ) }
					style={ {
						'--wpz-social-icons-block-item-color': list.color,
						'--wpz-social-icons-block-item-color-hover': list.hoverColor,
					} }
				>
					{ iconContent }
					{ showIconsLabel }
					{ list.showPopover && isSelected && (
						<Popover
							className={ classnames( 'wpzoom-social-icons-popover' ) }
							key={ key }
							position="bottom center"
							// Keep it inside the viewport: next to the screen edge it
							// used to open partly off-screen (WordPress 6.1+).
							shift
							// The URL field focuses itself (PopoverSearch).
							focusOnMount={ false }
							onClose={ () => popoverCloseHandler( key ) }
						>
							<div className="wpzoom-icon-popover">
								<div className="wpzoom-icon-popover__head">
									{ isBlank ? (
										<span className="wpzoom-icon-badge is-large is-blank" aria-hidden="true">
											<Icon icon={ link } size={ 20 } />
										</span>
									) : (
										<span
											className="wpzoom-icon-badge is-large"
											style={ list.color ? { backgroundColor: list.color } : undefined }
											aria-hidden="true"
										>
											{ renderUrlFieldGlyph( list.iconKit, list.icon, list.customSvg ) }
										</span>
									) }
									<span className="wpzoom-icon-popover__name">
										{ isBlank
											? __( 'New icon', 'social-icons-widget-by-wpzoom' )
											: list.label || __( 'Icon', 'social-icons-widget-by-wpzoom' ) }
									</span>
									<Button
										variant="secondary"
										className="wpzoom-icon-popover__change"
										onClick={ ( e ) => popoverEditSettingsHandler( e, key ) }
									>
										{ __( 'Change icon', 'social-icons-widget-by-wpzoom' ) }
									</Button>
								</div>

								<div className="wpzoom-icon-popover__section">
									{ singleIcon ? (
										// One icon (Icon by WPZOOM): it was chosen first, so the
										// link is optional and doesn't change it.
										<PopoverSearch
											key={ key }
											value={ list.url }
											save={ ( url ) => popoverSearchHandler( key, url, false ) }
											button={ __( 'Save link', 'social-icons-widget-by-wpzoom' ) }
										/>
									) : (
										<PopoverSearch
											key={ key }
											value={ list.url }
											save={ ( url ) => popoverSearchHandler( key, url ) }
											button={ __( 'Apply link and detect icon', 'social-icons-widget-by-wpzoom' ) }
											// The icon in the URL field: the one this URL is
											// paired with, once there is a URL and the last
											// detection did not fail. Globe otherwise.
											icon={
												list.url && list.iconDetected !== false && ! isBlank
													? renderUrlFieldGlyph( list.iconKit, list.icon, list.customSvg )
													: null
											}
											// The icon switches while the link is typed.
											onType={ ( typed ) => popoverTypeHandler( key, typed ) }
											// Live preview in the field, until then.
											preview={ ( typed ) => {
												const match = Helper.detectIcon( typed );
												return match
													? {
														node: renderUrlFieldGlyph( match.iconKit, match.icon ),
														color: match.color || list.color,
													  }
													: null;
											} }
											color={ list.color }
										/>
									) }
									{ ! singleIcon && list.justUpdated ? (
										<p
											className={ classnames( 'wpzoom-icon-popover__status', {
												'is-success': list.iconDetected,
											} ) }
										>
											<Icon icon={ list.iconDetected ? check : info } size={ 16 } />
											{ list.iconDetected
												? __( 'Icon detected and applied.', 'social-icons-widget-by-wpzoom' )
												: __( 'No matching icon. Use Change icon to pick one.', 'social-icons-widget-by-wpzoom' ) }
										</p>
									) : (
										<p className="wpzoom-icon-popover__help">
											{ singleIcon
												? __( 'Optional. Add a link to make the icon clickable.', 'social-icons-widget-by-wpzoom' )
												: isBlank
													? __( 'Paste a link and its icon is picked for you, or use Change icon to pick one.', 'social-icons-widget-by-wpzoom' )
													: __( 'Paste a link and the icon is picked for you. Leave it empty for an icon without a link.', 'social-icons-widget-by-wpzoom' ) }
										</p>
									) }
								</div>

								{ ! isBlank && (
									<div className="wpzoom-icon-popover__section wpzoom-icon-popover__label-field">
										<TextControl
											__nextHasNoMarginBottom
											label={ __( 'Label', 'social-icons-widget-by-wpzoom' ) }
											help={
												attributes.showIconsLabel
													? undefined
													: __( 'Shown on hover and to screen readers.', 'social-icons-widget-by-wpzoom' )
											}
											value={ list.label || '' }
											onChange={ ( label ) =>
												setAttributes( {
													selectedIcons: attributes.selectedIcons.map( ( item, i ) =>
														i === key ? { ...item, label } : item
													),
												} )
											}
										/>
									</div>
								) }

								<div className="wpzoom-icon-popover__section wpzoom-icon-popover__colors">
									<span className="wpzoom-icon-popover__label">
										{ __( 'Color', 'social-icons-widget-by-wpzoom' ) }
									</span>
									<ModalColorPicker
										title={ __( 'Icon Color', 'social-icons-widget-by-wpzoom' ) }
										label={ __( 'Normal', 'social-icons-widget-by-wpzoom' ) }
										save={ ( arg ) =>
											// Copy, never mutate: attributes must be
											// replaced for undo and change detection.
											setAttributes( {
												selectedIcons: attributes.selectedIcons.map( ( item, i ) =>
													i === key ? { ...item, color: arg.color } : item
												),
											} )
										}
										color={ list.color }
									/>
									<ModalColorPicker
										title={ __( 'Hover Color', 'social-icons-widget-by-wpzoom' ) }
										label={ __( 'Hover', 'social-icons-widget-by-wpzoom' ) }
										save={ ( arg ) =>
											setAttributes( {
												selectedIcons: attributes.selectedIcons.map( ( item, i ) =>
													i === key ? { ...item, hoverColor: arg.color } : item
												),
											} )
										}
										color={ list.hoverColor }
									/>
								</div>

								<div className="wpzoom-icon-popover__footer">
									<Button
										variant="tertiary"
										icon={ code }
										onClick={ () => openCustomSvgModal( key ) }
									>
										{ __( 'Custom SVG', 'social-icons-widget-by-wpzoom' ) }
									</Button>
									{ attributes.selectedIcons.length > 1 && (
										<Button
											variant="tertiary"
											isDestructive
											icon={ trash }
											onClick={ ( e ) => popoverDeleteIconHandler( e, key ) }
										>
											{ __( 'Remove', 'social-icons-widget-by-wpzoom' ) }
										</Button>
									) }
								</div>
							</div>
						</Popover>
					) }
				</a>
				<SortableArrows
					left={ moveLeftHandler }
					right={ moveRightHandler }
					length={ attributes.selectedIcons.length }
					isActive={ list.isActive && isSelected }
					itemKey={ key }
				/>
			</Fragment>
		);
	} );

	return (
		<Fragment>
			<Inspector { ...props } />
			<BlockControls group="block">
				<AlignmentControl
					value={ attributes.iconsAlignment }
					onChange={ ( iconsAlignment ) => setAlignment( iconsAlignment ) }
				/>
			</BlockControls>
			<div
				{ ...blockProps }
				className={ className }
				style={ {
					...blockProps.style,
					'--wpz-social-icons-block-item-font-size': Helper.addPixelsPipe(
						attributes.iconsFontSize
					),
					'--wpz-social-icons-block-item-padding-horizontal': Helper.addPixelsPipe(
						attributes.iconsPaddingHorizontal
					),
					'--wpz-social-icons-block-item-padding-vertical': Helper.addPixelsPipe(
						attributes.iconsPaddingVertical
					),
					'--wpz-social-icons-block-item-margin-horizontal': Helper.addPixelsPipe(
						attributes.iconsMarginHorizontal
					),
					'--wpz-social-icons-block-item-margin-vertical': Helper.addPixelsPipe(
						attributes.iconsMarginVertical
					),
					'--wpz-social-icons-block-item-border-radius': Helper.addPixelsPipe(
						attributes.iconsBorderRadius
					),
					'--wpz-social-icons-block-label-font-size': Helper.addPixelsPipe(
						attributes.iconsLabelFontSize
					),
					'--wpz-social-icons-block-label-color': attributes.iconsLabelColor,
					'--wpz-social-icons-block-label-color-hover':
						attributes.iconsLabelHoverColor,
					'--wpz-social-icons-alignment': getIconsAlignmentStyle(
						attributes.iconsAlignment
					),
				} }
			>
				{ IconsList }
				{ isSelected && (
					<Button
						type="button"
						onClick={ insertIcon }
						style={ { padding: attributes.iconsPadding } }
						className="insert-icon"
						label={ __( 'Add icon', 'social-icons-widget-by-wpzoom' ) }
					>
						<Icon icon={ plus } size={ 24 } />
					</Button>
				) }
				{ attributes.showModal && attributes.selectedIcons[ attributes.activeIconIndex ] && (
					<IconLibrary
						icon={ attributes.selectedIcons[ attributes.activeIconIndex ].icon }
						iconKit={ attributes.selectedIcons[ attributes.activeIconIndex ].iconKit }
						renderGlyph={ ( kit, name ) => <IconGlyph iconKit={ kit } icon={ name } /> }
						onSelect={ selectLibraryIcon }
						onClose={ closeModal }
					/>
				) }

				{ isCustomSvgModalOpen && (
					<Modal
						title={ __(
							'Insert Custom SVG Icon',
							'social-icons-widget-by-wpzoom'
						) }
						onRequestClose={ closeCustomSvgModal }
						className="wpzoom-custom-svg-modal"
					>
						<div className="wpzoom-custom-svg-modal-content">
							<p className="wpzoom-custom-svg-modal-description">
								{ __(
									"Paste your SVG code below. Make sure it's clean and valid SVG code for security reasons.",
									'social-icons-widget-by-wpzoom'
								) }
							</p>
							<TextareaControl
								label={ __(
									'SVG Code',
									'social-icons-widget-by-wpzoom'
								) }
								help={ __(
									'Paste SVG code here. For security reasons, scripts and event handlers will be removed.',
									'social-icons-widget-by-wpzoom'
								) }
								value={ customSvgCode }
								onChange={ ( value ) =>
									setCustomSvgCode( value )
								}
								rows={ 10 }
								className="wpzoom-custom-svg-textarea"
							/>
							{ customSvgCode && customSvgCode.trim() !== '' && (
								<div className="wpzoom-custom-svg-preview">
									<p className="wpzoom-custom-svg-preview-title">
										{ __(
											'Preview:',
											'social-icons-widget-by-wpzoom'
										) }
									</p>
									<div
										className="wpzoom-custom-svg-preview-box"
										dangerouslySetInnerHTML={ {
											__html: customSvgCode,
										} }
									/>
								</div>
							) }
							<div className="wpzoom-custom-svg-modal-buttons">
								<Button
									isPrimary
									onClick={ applySvgIcon }
									disabled={
										! customSvgCode ||
										customSvgCode.trim() === ''
									}
								>
									{ __(
										'Apply SVG Icon',
										'social-icons-widget-by-wpzoom'
									) }
								</Button>
								<Button
									isSecondary
									onClick={ closeCustomSvgModal }
								>
									{ __(
										'Cancel',
										'social-icons-widget-by-wpzoom'
									) }
								</Button>
							</div>
						</div>
					</Modal>
				) }
			</div>
		</Fragment>
	);
}
