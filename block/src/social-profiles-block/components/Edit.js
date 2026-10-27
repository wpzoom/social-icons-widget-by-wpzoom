/* global wpzSocialIconsBlock */

/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	ExternalLink,
	Icon,
	PanelBody,
	Placeholder,
	Popover,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { chevronDown, chevronUp, link, plus, trash } from '@wordpress/icons';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Helper from '../../block/utils/helper';
import PopoverSearch from '../../block/components/PopoverSearch';
import IconLibrary from '../../block/components/IconLibrary';
import ModalColorPicker from '../../block/components/ModalColorPicker';
import Glyph from './Glyph';
import Profile from './Profile';
import blockIcon from '../blockIcon';
import { defaultCountLabel, networkName, profileFromUrl, profileText, styleVars } from '../utils/profiles';

const config = typeof wpzSocialIconsBlock !== 'undefined' ? wpzSocialIconsBlock : {};
const IS_PRO = !! config.isPro;

// The quick picks on an empty block: the networks most sites link to.
const POPULAR = [ 'facebook', 'instagram', 'x', 'youtube', 'tiktok', 'linkedin', 'pinterest', 'threads', 'bluesky', 'whatsapp' ];

const LAYOUTS = [
	{ value: 'list', label: __( 'List', 'social-icons-widget-by-wpzoom' ), pro: false },
	{ value: 'cards', label: __( 'Cards', 'social-icons-widget-by-wpzoom' ), pro: true },
	{ value: 'buttons', label: __( 'Buttons', 'social-icons-widget-by-wpzoom' ), pro: true },
];

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const { items, layout, display, colorMode, color, columns, showCounts, showCta, ctaText, buttonText, openInNewTab, nofollow } = attributes;
	const [ openIndex, setOpenIndex ] = useState( null );
	const [ libraryIndex, setLibraryIndex ] = useState( null );
	const [ picked, setPicked ] = useState( [] );

	// Free shows the List layout; Cards and Buttons are PRO.
	const shownLayout = IS_PRO || layout === 'list' ? layout : 'list';

	const blockProps = useBlockProps( {
		className: classnames( `is-layout-${ shownLayout }`, `is-color-${ colorMode }`, {
			[ `has-${ columns }-columns` ]: shownLayout === 'cards',
		} ),
		style: {
			...styleVars( attributes ),
			...( colorMode === 'mono' && color ? { '--wpz-sp-color': color } : {} ),
		},
	} );

	const updateItem = ( index, patch ) =>
		setAttributes( { items: items.map( ( item, i ) => ( i === index ? { ...item, ...patch } : item ) ) } );

	const removeItem = ( index ) => {
		setAttributes( { items: items.filter( ( item, i ) => i !== index ) } );
		setOpenIndex( null );
	};

	const moveItem = ( index, step ) => {
		const target = index + step;
		if ( target < 0 || target >= items.length ) {
			return;
		}
		const next = [ ...items ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { items: next } );
		setOpenIndex( target );
	};

	const addItem = () => {
		setAttributes( { items: [ ...items, { url: '', iconKit: '', icon: '', network: '', color: '' } ] } );
		setOpenIndex( items.length );
	};

	// The networks picked on the empty block, in the order shown; each then
	// gets its link from its own settings, starting with the first.
	const addPicked = () => {
		const icons = POPULAR.filter( ( icon ) => picked.indexOf( icon ) > -1 );
		if ( ! icons.length ) {
			return;
		}
		setAttributes( {
			items: icons.map( ( icon ) => ( {
				url: '',
				iconKit: 'socicon',
				icon,
				network: networkName( icon ),
				color: Helper.brandColor( 'socicon', icon ) || '',
			} ) ),
		} );
		setPicked( [] );
		setOpenIndex( 0 );
	};

	const togglePicked = ( icon ) =>
		setPicked( picked.indexOf( icon ) > -1 ? picked.filter( ( name ) => name !== icon ) : [ ...picked, icon ] );

	// While a link is typed: its icon, name and color right away; the link
	// itself is saved on Enter or when leaving the field.
	const onType = ( index, typed ) => {
		const match = Helper.detectIcon( typed );
		if ( match && match.icon !== items[ index ].icon ) {
			updateItem( index, {
				iconKit: match.iconKit,
				icon: match.icon,
				network: networkName( match.icon ),
				color: match.color || items[ index ].color,
			} );
		}
	};

	const selectLibraryIcon = ( iconKit, icon ) => {
		const brand = Helper.brandColor( iconKit, icon );
		updateItem( libraryIndex, {
			iconKit,
			icon,
			network: networkName( icon ),
			...( brand ? { color: brand } : {} ),
		} );
		setOpenIndex( libraryIndex );
		setLibraryIndex( null );
	};

	const renderPopover = ( item, index ) => {
		const isBlank = ! item.icon;
		// What the profile shows while Text is empty (the field's placeholder).
		const auto = profileText( { ...item, text: '' }, display );
		return (
			<Popover
				className="wpzoom-social-icons-popover"
				position="bottom center"
				shift
				// The URL field focuses itself (PopoverSearch).
				focusOnMount={ false }
				onClose={ () => setOpenIndex( null ) }
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
								style={ item.color ? { backgroundColor: item.color } : undefined }
								aria-hidden="true"
							>
								<Glyph iconKit={ item.iconKit } icon={ item.icon } />
							</span>
						) }
						<span className="wpzoom-icon-popover__name">
							{ isBlank ? __( 'New profile', 'social-icons-widget-by-wpzoom' ) : item.network || networkName( item.icon ) }
						</span>
						<Button variant="secondary" className="wpzoom-icon-popover__change" onClick={ () => setLibraryIndex( index ) }>
							{ __( 'Change icon', 'social-icons-widget-by-wpzoom' ) }
						</Button>
					</div>

					<div className="wpzoom-icon-popover__section">
						<PopoverSearch
							key={ index }
							value={ item.url }
							save={ ( url ) => updateItem( index, profileFromUrl( Helper.normalizeUrl( url ), item ) ) }
							onType={ ( typed ) => onType( index, typed ) }
							button={ __( 'Apply link and detect icon', 'social-icons-widget-by-wpzoom' ) }
							icon={ item.url && ! isBlank ? <Glyph iconKit={ item.iconKit } icon={ item.icon } /> : null }
							color={ item.color }
						/>
						<p className="wpzoom-icon-popover__help">
							{ __( 'Paste your profile link: the icon and handle come from it.', 'social-icons-widget-by-wpzoom' ) }
						</p>
					</div>

					<div className="wpzoom-icon-popover__section wpzoom-sp-popover__fields">
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Text', 'social-icons-widget-by-wpzoom' ) }
							value={ item.text || '' }
							placeholder={ auto }
							onChange={ ( text ) => updateItem( index, { text } ) }
						/>
						<div className="wpzoom-sp-popover__row">
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'Count', 'social-icons-widget-by-wpzoom' ) }
								value={ item.count || '' }
								placeholder="12.4K"
								onChange={ ( count ) => updateItem( index, { count } ) }
							/>
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'Count label', 'social-icons-widget-by-wpzoom' ) }
								value={ item.countLabel || '' }
								placeholder={ defaultCountLabel( item.icon ) }
								onChange={ ( countLabel ) => updateItem( index, { countLabel } ) }
							/>
						</div>
					</div>

					{ colorMode === 'brand' && (
						<div className="wpzoom-icon-popover__section wpzoom-icon-popover__colors">
							<span className="wpzoom-icon-popover__label">{ __( 'Color', 'social-icons-widget-by-wpzoom' ) }</span>
							<ModalColorPicker
								title={ __( 'Color', 'social-icons-widget-by-wpzoom' ) }
								label={ __( 'Brand', 'social-icons-widget-by-wpzoom' ) }
								color={ item.color }
								save={ ( arg ) => updateItem( index, { color: arg.color } ) }
							/>
						</div>
					) }

					<div className="wpzoom-icon-popover__footer">
						<div>
							<Button
								icon={ chevronUp }
								label={ __( 'Move up', 'social-icons-widget-by-wpzoom' ) }
								disabled={ index === 0 }
								onClick={ () => moveItem( index, -1 ) }
							/>
							<Button
								icon={ chevronDown }
								label={ __( 'Move down', 'social-icons-widget-by-wpzoom' ) }
								disabled={ index === items.length - 1 }
								onClick={ () => moveItem( index, 1 ) }
							/>
						</div>
						<Button variant="tertiary" isDestructive icon={ trash } onClick={ () => removeItem( index ) }>
							{ __( 'Remove', 'social-icons-widget-by-wpzoom' ) }
						</Button>
					</div>
				</div>
			</Popover>
		);
	};

	const inspector = (
		<InspectorControls>
			<PanelBody title={ __( 'Layout', 'social-icons-widget-by-wpzoom' ) }>
				<div className="wpzoom-sp-layouts">
					{ LAYOUTS.map( ( option ) => {
						const locked = option.pro && ! IS_PRO;
						return (
							<Button
								key={ option.value }
								className={ classnames( 'wpzoom-sp-layouts__option', `is-${ option.value }` ) }
								isPressed={ shownLayout === option.value }
								disabled={ locked }
								onClick={ () => setAttributes( { layout: option.value } ) }
							>
								<span className="wpzoom-sp-layouts__preview" aria-hidden="true">
									<span /><span /><span />
								</span>
								<span className="wpzoom-sp-layouts__label">
									{ option.label }
									{ locked && <span className="wpzoom-sp-layouts__pro">{ __( 'Pro', 'social-icons-widget-by-wpzoom' ) }</span> }
								</span>
							</Button>
						);
					} ) }
				</div>
				{ ! IS_PRO && config.upgradeUrl && (
					<p className="wpzoom-sp-layouts__upsell">
						<ExternalLink href={ config.upgradeUrl }>
							{ __( 'Cards and Buttons layouts come with WPZOOM Connect Pro', 'social-icons-widget-by-wpzoom' ) }
						</ExternalLink>
					</p>
				) }
				{ shownLayout === 'cards' && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Columns', 'social-icons-widget-by-wpzoom' ) }
						value={ columns }
						min={ 1 }
						max={ 4 }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
					/>
				) }
			</PanelBody>

			<PanelBody title={ __( 'Text', 'social-icons-widget-by-wpzoom' ) }>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Show', 'social-icons-widget-by-wpzoom' ) }
					value={ display }
					options={ [
						{ value: 'handle', label: __( 'Handle (@yourname)', 'social-icons-widget-by-wpzoom' ) },
						{ value: 'url', label: __( 'Short link (x.com/yourname)', 'social-icons-widget-by-wpzoom' ) },
						{ value: 'network', label: __( 'Network name (Instagram)', 'social-icons-widget-by-wpzoom' ) },
					] }
					help={ __( 'A profile’s own text, set in its settings, always wins.', 'social-icons-widget-by-wpzoom' ) }
					onChange={ ( value ) => setAttributes( { display: value } ) }
				/>
				{ shownLayout !== 'buttons' && (
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show follower counts', 'social-icons-widget-by-wpzoom' ) }
						help={ __( 'Type each count in the profile’s settings, e.g. 12.4K.', 'social-icons-widget-by-wpzoom' ) }
						checked={ showCounts }
						onChange={ ( value ) => setAttributes( { showCounts: value } ) }
					/>
				) }
				{ shownLayout === 'list' && (
					<>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show “Follow” on each row', 'social-icons-widget-by-wpzoom' ) }
							checked={ showCta }
							onChange={ ( value ) => setAttributes( { showCta: value } ) }
						/>
						{ showCta && (
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'Follow text', 'social-icons-widget-by-wpzoom' ) }
								value={ ctaText }
								placeholder={ __( 'Follow', 'social-icons-widget-by-wpzoom' ) }
								onChange={ ( value ) => setAttributes( { ctaText: value } ) }
							/>
						) }
					</>
				) }
				{ shownLayout === 'buttons' && (
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Button text', 'social-icons-widget-by-wpzoom' ) }
						value={ buttonText }
						placeholder={ __( 'Follow on %s', 'social-icons-widget-by-wpzoom' ) }
						help={ __( '%s is replaced with the network name.', 'social-icons-widget-by-wpzoom' ) }
						onChange={ ( value ) => setAttributes( { buttonText: value } ) }
					/>
				) }
			</PanelBody>

			<PanelBody title={ __( 'Color', 'social-icons-widget-by-wpzoom' ) }>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Icon colors', 'social-icons-widget-by-wpzoom' ) }
					value={ colorMode }
					options={ [
						{ value: 'brand', label: __( 'Each network’s brand color', 'social-icons-widget-by-wpzoom' ) },
						{ value: 'mono', label: __( 'One color for all', 'social-icons-widget-by-wpzoom' ) },
					] }
					onChange={ ( value ) => setAttributes( { colorMode: value } ) }
				/>
			</PanelBody>
			{ colorMode === 'mono' && (
				<PanelColorSettings
					title={ __( 'One color', 'social-icons-widget-by-wpzoom' ) }
					initialOpen
					colorSettings={ [
						{
							value: color,
							onChange: ( value ) => setAttributes( { color: value || '' } ),
							label: __( 'Color', 'social-icons-widget-by-wpzoom' ),
						},
					] }
				/>
			) }

			<PanelBody title={ __( 'Links', 'social-icons-widget-by-wpzoom' ) } initialOpen={ false }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Open in a new tab', 'social-icons-widget-by-wpzoom' ) }
					checked={ openInNewTab }
					onChange={ ( value ) => setAttributes( { openInNewTab: value } ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Add rel="nofollow"', 'social-icons-widget-by-wpzoom' ) }
					checked={ nofollow }
					onChange={ ( value ) => setAttributes( { nofollow: value } ) }
				/>
			</PanelBody>
		</InspectorControls>
	);

	// A range that can be cleared back to the layout's own value.
	const range = ( key, label, min, max, fallback ) => (
		<RangeControl
			__nextHasNoMarginBottom
			label={ label }
			value={ attributes[ key ] }
			min={ min }
			max={ max }
			allowReset
			resetFallbackValue={ undefined }
			initialPosition={ fallback }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
		/>
	);

	const styles = (
		<InspectorControls group="styles">
			<PanelBody title={ __( 'Profiles', 'social-icons-widget-by-wpzoom' ) }>
				{ range( 'radius', __( 'Corner radius', 'social-icons-widget-by-wpzoom' ), 0, 40, shownLayout === 'buttons' ? 40 : 14 ) }
				{ range( 'borderWidth', __( 'Border width', 'social-icons-widget-by-wpzoom' ), 0, 6, shownLayout === 'list' ? 1 : 0 ) }
				{ range( 'padding', __( 'Padding', 'social-icons-widget-by-wpzoom' ), 4, 32, 12 ) }
				{ range( 'gap', __( 'Space between', 'social-icons-widget-by-wpzoom' ), 0, 40, 10 ) }
			</PanelBody>
			<PanelColorSettings
				title={ __( 'Profile colors', 'social-icons-widget-by-wpzoom' ) }
				initialOpen={ false }
				colorSettings={ [
					{
						value: attributes.borderColor,
						onChange: ( value ) => setAttributes( { borderColor: value || '' } ),
						label: __( 'Border', 'social-icons-widget-by-wpzoom' ),
					},
					...( shownLayout === 'list'
						? [
							{
								value: attributes.background,
								onChange: ( value ) => setAttributes( { background: value || '' } ),
								label: __( 'Background', 'social-icons-widget-by-wpzoom' ),
							},
							{
								value: attributes.textColor,
								onChange: ( value ) => setAttributes( { textColor: value || '' } ),
								label: __( 'Text', 'social-icons-widget-by-wpzoom' ),
							},
						  ]
						: [] ),
				] }
			/>
			{ shownLayout !== 'buttons' && (
				<PanelBody title={ __( 'Icon', 'social-icons-widget-by-wpzoom' ) } initialOpen={ false }>
					{ range( 'iconSize', __( 'Size', 'social-icons-widget-by-wpzoom' ), 24, 72, 40 ) }
					{ range( 'iconRadius', __( 'Corner radius', 'social-icons-widget-by-wpzoom' ), 0, 36, 10 ) }
				</PanelBody>
			) }
		</InspectorControls>
	);

	if ( ! items.length ) {
		return (
			<div { ...blockProps }>
				{ inspector }
				{ styles }
				<Placeholder
					icon={ blockIcon }
					label={ __( 'Social Profiles', 'social-icons-widget-by-wpzoom' ) }
					instructions={ __( 'Pick the networks you’re on. You’ll add each link next.', 'social-icons-widget-by-wpzoom' ) }
					className="wpzoom-sp-placeholder"
				>
					<div className="wpzoom-sp-picker" role="group" aria-label={ __( 'Networks', 'social-icons-widget-by-wpzoom' ) }>
						{ POPULAR.map( ( icon ) => {
							const isPicked = picked.indexOf( icon ) > -1;
							return (
								<Button
									key={ icon }
									className="wpzoom-sp-picker__network"
									isPressed={ isPicked }
									onClick={ () => togglePicked( icon ) }
									style={ { '--wpz-sp-brand': Helper.brandColor( 'socicon', icon ) || '#444140' } }
								>
									<span className="wpzoom-sp-picker__icon" aria-hidden="true">
										<Glyph iconKit="socicon" icon={ icon } />
									</span>
									<span>{ networkName( icon ) }</span>
								</Button>
							);
						} ) }
					</div>
					<div className="wpzoom-sp-placeholder__actions">
						<Button variant="primary" onClick={ addPicked } disabled={ ! picked.length }>
							{ picked.length
								? sprintf(
									/* translators: %d: number of networks picked */
									_n( 'Add %d profile', 'Add %d profiles', picked.length, 'social-icons-widget-by-wpzoom' ),
									picked.length
								)
								: __( 'Add profiles', 'social-icons-widget-by-wpzoom' ) }
						</Button>
						<Button variant="tertiary" onClick={ addItem }>
							{ __( 'Another network', 'social-icons-widget-by-wpzoom' ) }
						</Button>
					</div>
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ inspector }
			{ styles }
			<ul className="wpz-sp__list">
				{ items.map( ( item, index ) => (
					<Profile
						key={ index }
						item={ item }
						attributes={ { ...attributes, layout: shownLayout } }
						isOpen={ isSelected && openIndex === index }
						onClick={ () => setOpenIndex( openIndex === index ? null : index ) }
					>
						{ isSelected && openIndex === index && renderPopover( item, index ) }
					</Profile>
				) ) }
			</ul>
			{ isSelected && (
				<Button className="wpzoom-sp-add" icon={ plus } variant="secondary" onClick={ addItem }>
					{ __( 'Add profile', 'social-icons-widget-by-wpzoom' ) }
				</Button>
			) }
			{ libraryIndex !== null && items[ libraryIndex ] && (
				<IconLibrary
					icon={ items[ libraryIndex ].icon }
					iconKit={ items[ libraryIndex ].iconKit }
					renderGlyph={ ( kit, name ) => <Glyph iconKit={ kit } icon={ name } /> }
					onSelect={ selectLibraryIcon }
					onClose={ () => setLibraryIndex( null ) }
				/>
			) }
		</div>
	);
}
