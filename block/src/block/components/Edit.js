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
import { Icon, Button, Popover, Modal, TextareaControl } from '@wordpress/components';
import { AlignmentControl, BlockControls, useBlockProps } from '@wordpress/block-editor';
// SVG icons for controls drawn inside the editor canvas (an iframe that only has
// the Dashicons font when that icon kit is enabled); Dashicon names there can
// render as empty boxes.
import { plus } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Helper from '../utils/helper';
import SocialIconsModal from './SocialIconsModal';
import Inspector from './Inspector';
import PopoverSearch from './PopoverSearch';
import SortableArrows from './SortableArrows';
import ModalColorPicker from './ModalColorPicker';

const STYLE_VARIATIONS = {
	'with-label-canvas-rounded': {
		canvasType: 'with-label-canvas',
		showIconsLabel: true,
		iconsColor: null,
		iconsLabelColor: '#fff',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#fff',
		iconsFontSize: 18,
		iconsLabelFontSize: 15,
		iconsPaddingHorizontal: 5,
		iconsPaddingVertical: 5,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 50,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: null,
			hoverColor: null,
		},
	},
	'with-canvas-rounded': {
		canvasType: 'with-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 5,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'with-canvas-round': {
		canvasType: 'with-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 50,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'with-canvas-squared': {
		canvasType: 'with-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsBorderRadius: 0,
		iconsHasBorder: true,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'without-canvas': {
		canvasType: 'without-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: false,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'without-canvas-with-border': {
		canvasType: 'without-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: 'inherit',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#f1f1f1',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 0,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: null,
			hoverColor: null,
		},
	},
	'without-canvas-with-label': {
		canvasType: 'without-canvas',
		showIconsLabel: true,
		iconsColor: null,
		iconsLabelColor: 'inherit',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#f1f1f1',
		iconsFontSize: 40,
		iconsLabelFontSize: 15,
		iconsPaddingHorizontal: 10,
		iconsPaddingVertical: 10,
		iconsMarginHorizontal: 0,
		iconsMarginVertical: 0,
		iconsHasBorder: false,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: null,
			hoverColor: null,
		},
	},
};

function getStyleVariations( styleType, activeStyleName ) {
	return get( STYLE_VARIATIONS, styleType, false )
		? get( STYLE_VARIATIONS, styleType, false )
		: get( STYLE_VARIATIONS, activeStyleName );
}

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
			<span
				className={ classnames(
					'url-input-icon-glyph',
					Helper.getIconClassList( iconKit, icon )
				) }
			/>
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

		const styleVariation = getStyleVariations( activeStyleName, activeStyleName );
		if ( ! isEmpty( styleVariation ) ) {
			setAttributes( omit( styleVariation, [ 'selectedIcons' ] ) );

			const clonedSelectedIcons = JSON.parse(
				JSON.stringify( attributes.selectedIcons )
			);

			if ( ! isEmpty( styleVariation.selectedIcons ) ) {
				clonedSelectedIcons.map( ( item ) => {
					if ( isEmpty( item.color ) ) {
						item.color = styleVariation.defaultIcon.color;
					}
					if ( isEmpty( item.hoverColor ) ) {
						item.hoverColor = styleVariation.defaultIcon.hoverColor;
					}
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

	const saveModalHandler = ( iconObject ) => {
		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		const currentIcon = selectedIconsClone[ attributes.activeIconIndex ];
		selectedIconsClone[ attributes.activeIconIndex ] = {
			...currentIcon,
			url: iconObject.modalUrl,
			label: iconObject.modalLabel,
			icon: iconObject.modalIcon,
			iconKit: iconObject.modalIconKit,
			color: iconObject.modalColor,
			hoverColor: iconObject.modalHoverColor,
			// A hand-picked icon is the icon for this URL now, so the URL field
			// should show it even if an earlier Detect Icon found no match.
			iconDetected: undefined,
		};
		setAttributes( { selectedIcons: selectedIconsClone, showModal: false } );
	};

	const insertIcon = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		if ( e.detail === 0 ) {
			return;
		}

		const styleVariation = getStyleVariations(
			Helper.getBlockStyle( blockProps.className ),
			activeStyleName
		);

		const defaultIcon = {
			url: '',
			icon: 'wordpress',
			iconKit: 'socicon',
			color: '#444140',
			hoverColor: '#444140',
			label: 'WordPress',
			showPopover: true,
			isActive: true,
			customSvg: null,
		};

		if ( styleVariation && ! isEmpty( styleVariation.defaultIcon.color ) ) {
			defaultIcon.color = styleVariation.defaultIcon.color;
		}
		if ( styleVariation && ! isEmpty( styleVariation.defaultIcon.hoverColor ) ) {
			defaultIcon.hoverColor = styleVariation.defaultIcon.hoverColor;
		}

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

	const deleteIconHandler = () => {
		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);
		selectedIconsClone.splice( attributes.activeIconIndex, 1 );
		setAttributes( {
			selectedIcons: selectedIconsClone,
			showModal: false,
			activeIconIndex: 0,
		} );
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

	const popoverSearchHandler = ( key, newUrl ) => {
		newUrl = Helper.normalizeUrl( newUrl );

		const selectedIconsClone = JSON.parse(
			JSON.stringify( attributes.selectedIcons )
		);

		// Socicons first, then the other enabled kits; first exact match wins.
		const match = Helper.detectIcon( newUrl );
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

	const IconsList = attributes.selectedIcons.map( ( list, key ) => {
		const showIconsLabel = attributes.showIconsLabel ? (
			<span className={ classnames( 'icon-label' ) }>{ list.label }</span>
		) : (
			''
		);

		let iconContent;
		if ( list.iconKit === 'svg' && list.customSvg ) {
			iconContent = (
				<span
					className={ classnames( 'social-icon', 'social-icon-svg' ) }
					dangerouslySetInnerHTML={ { __html: list.customSvg } }
				/>
			);
		} else {
			iconContent = (
				<span
					className={ classnames(
						Helper.getIconClassList( list.iconKit, list.icon )
					) }
				/>
			);
		}

		return (
			<Fragment key={ key }>
				<a
					onClick={ ( e ) => onClickIconHandler( e, key, list ) }
					href={ list.url }
					className={ classnames( 'social-icon-link', {
						selected: list.isActive,
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
							onClose={ () => popoverCloseHandler( key ) }
						>
							<div className={ classnames( 'popover-content' ) }>
								<div className="popover-header">
									<span className="popover-title">
										{ __(
											'Social Icon Settings',
											'social-icons-widget-by-wpzoom'
										) }
									</span>
								</div>

								<div className={ classnames( 'popover-url-wrapper' ) }>
									<div className="popover-section-title">
										<Icon icon="admin-links" />
										{ __( 'URL & ICON', 'social-icons-widget-by-wpzoom' ) }
									</div>
									<div className="popover-description">
										{ __(
											'Enter a website URL to automatically detect its icon',
											'social-icons-widget-by-wpzoom'
										) }
									</div>
									<div className="popover-url-input-container">
										<PopoverSearch
											key={ key }
											value={ list.url }
											save={ ( url ) =>
												popoverSearchHandler( key, url )
											}
											// The icon in the URL field: the one this URL is
											// paired with, once there is a URL and the last
											// Detect Icon did not fail. Globe otherwise. Drawn
											// with the same classes as the canvas: the icon
											// fonts only draw glyphs for `.social-icon.<kit>-<icon>`,
											// so `social-icon` stays and its tile styling is
											// reset in editor.scss.
											icon={
												list.url && list.iconDetected !== false
													? renderUrlFieldGlyph( list.iconKit, list.icon, list.customSvg )
													: null
											}
											// Live preview while typing: the icon Detect
											// Icon would apply, before it is applied.
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
									</div>
									{ list.justUpdated && (
										<div
											className={ `icon-status-message ${ list.iconDetected ? 'success' : 'notice' }` }
										>
											<Icon
												icon={
													list.iconDetected
														? 'yes-alt'
														: 'info-outline'
												}
											/>
											{ list.iconDetected
												? __(
														'Icon detected and applied!',
														'social-icons-widget-by-wpzoom'
												  )
												: __(
														'No matching icon found. Choose manually below.',
														'social-icons-widget-by-wpzoom'
												  ) }
										</div>
									) }
									<div className="popover-alternate-options">
										<span>
											{ __( 'Or', 'social-icons-widget-by-wpzoom' ) }
										</span>
										<Button
											isPrimary
											onClick={ ( e ) =>
												popoverEditSettingsHandler( e, key )
											}
											className="popover-edit-details-button"
										>
											<Icon icon="edit" />
											{ __(
												'Choose Icon & Edit Details',
												'social-icons-widget-by-wpzoom'
											) }
										</Button>

										<div className="popover-section-divider">
											<span>
												{ __( 'Or', 'social-icons-widget-by-wpzoom' ) }
											</span>
										</div>

										<Button
											className="popover-custom-svg-button"
											onClick={ () => openCustomSvgModal( key ) }
										>
											<Icon icon="editor-code" />
											{ __(
												'Insert Custom SVG Icon',
												'social-icons-widget-by-wpzoom'
											) }
										</Button>
									</div>
								</div>

								<div className="popover-colors-section">
									<div className="popover-section-title">
										<Icon icon="art" />
										{ __( 'COLORS', 'social-icons-widget-by-wpzoom' ) }
									</div>
									<div className="color-pickers-container">
										<div
											className="color-picker-option"
											data-tooltip={ __(
												'Change color',
												'social-icons-widget-by-wpzoom'
											) }
										>
											<span className="color-label">
												{ __(
													'Normal:',
													'social-icons-widget-by-wpzoom'
												) }
											</span>
											<ModalColorPicker
												title={ __(
													'Icon Color',
													'social-icons-widget-by-wpzoom'
												) }
												className={ classnames(
													'popover-color-picker'
												) }
												save={ ( arg ) =>
													// Copy, never mutate: attributes must be
													// replaced for undo and change detection.
													setAttributes( {
														selectedIcons: attributes.selectedIcons.map(
															( item, i ) =>
																i === key
																	? { ...item, color: arg.color }
																	: item
														),
													} )
												}
												color={ list.color }
											/>
										</div>
										<div
											className="color-picker-option"
											data-tooltip={ __(
												'Change color',
												'social-icons-widget-by-wpzoom'
											) }
										>
											<span className="color-label">
												{ __(
													'Hover:',
													'social-icons-widget-by-wpzoom'
												) }
											</span>
											<ModalColorPicker
												title={ __(
													'Hover Color',
													'social-icons-widget-by-wpzoom'
												) }
												className={ classnames(
													'popover-color-picker'
												) }
												save={ ( arg ) =>
													// Copy, never mutate: attributes must be
													// replaced for undo and change detection.
													setAttributes( {
														selectedIcons: attributes.selectedIcons.map(
															( item, i ) =>
																i === key
																	? { ...item, hoverColor: arg.color }
																	: item
														),
													} )
												}
												color={ list.hoverColor }
											/>
										</div>
									</div>
								</div>

								{ attributes.selectedIcons.length > 1 && (
									<div className="popover-footer">
										<Button
											isDestructive
											onClick={ ( e ) =>
												popoverDeleteIconHandler( e, key )
											}
											className="delete-icon-button"
										>
											<Icon icon="trash" />
											{ __(
												'Delete Icon',
												'social-icons-widget-by-wpzoom'
											) }
										</Button>
									</div>
								) }
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
				{ attributes.selectedIcons[ attributes.activeIconIndex ] && (
					<SocialIconsModal
						className={ classnames(
							Helper.getBlockStyle( className )
						) }
						showIconsLabel={ attributes.showIconsLabel }
						iconsBorderRadius={ attributes.iconsBorderRadius }
						show={ attributes.showModal }
						url={
							attributes.selectedIcons[ attributes.activeIconIndex ]
								.url
						}
						label={
							attributes.selectedIcons[ attributes.activeIconIndex ]
								.label
						}
						icon={
							attributes.selectedIcons[ attributes.activeIconIndex ]
								.icon
						}
						iconKit={
							attributes.selectedIcons[ attributes.activeIconIndex ]
								.iconKit
						}
						color={
							attributes.selectedIcons[ attributes.activeIconIndex ]
								.color
						}
						hoverColor={
							attributes.selectedIcons[ attributes.activeIconIndex ]
								.hoverColor
						}
						save={ saveModalHandler }
						delete={ deleteIconHandler }
						showDeleteBtn={ attributes.selectedIcons.length > 1 }
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
