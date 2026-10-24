/**
 * Inspector Controls
 */

import Helper from '../utils/helper';
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	PanelRow,
	FormToggle,
	ColorPalette,
	ColorIndicator,
	RangeControl,
} from '@wordpress/components';
import StylePicker, { withStyleClass } from './StylePicker';

/**
 * Create an Inspector Controls wrapper Component
 */
export default class Inspector extends Component {
	constructor( props ) {
		super( ...arguments );
	}

	state = {
		selectedIcons: JSON.parse(
			JSON.stringify( this.props.attributes.selectedIcons )
		),
	};

	static getDerivedStateFromProps( props, state ) {
		if (
			state.selectedIcons.length !== props.attributes.selectedIcons.length
		) {
			return {
				selectedIcons: JSON.parse(
					JSON.stringify( props.attributes.selectedIcons )
				),
			};
		}

		return null;
	}

	setAlignment = ( alignment ) => {
		this.props.setAttributes( { iconsAlignment: alignment } );
	};

	getBlockStyle( classname ) {
		const blockStyle = Helper.getBlockStyle( classname );
		return null === blockStyle ? 'with-canvas-round' : blockStyle;
	}

	render() {
		const colors = [
			{
				name: __( 'Turquoise', 'social-icons-widget-by-wpzoom' ),
				color: '#4ECDC4',
			},
			{
				name: __( 'Charcoal', 'social-icons-widget-by-wpzoom' ),
				color: '#2E3131',
			},
			{ name: __( 'White', 'social-icons-widget-by-wpzoom' ), color: '#fff' },
			{
				name: __( 'Dodger blue', 'social-icons-widget-by-wpzoom' ),
				color: '#22A7F0',
			},
			{ name: __( 'Red', 'social-icons-widget-by-wpzoom' ), color: '#D91E18' },
			{
				name: __( 'Orange', 'social-icons-widget-by-wpzoom' ),
				color: '#F89406',
			},
		];

		const { setAttributes } = this.props;

		const isLeftAlignment = 'left' === this.props.attributes.iconsAlignment;
		const isCenterAlignment =
			'center' === this.props.attributes.iconsAlignment;
		const isRightAlignment =
			'right' === this.props.attributes.iconsAlignment;

		return (
            <>
			<InspectorControls group="settings">
				<PanelBody
					title={ __(
						'Labels & Links',
						'social-icons-widget-by-wpzoom'
					) }
				>
					<PanelRow>
						<label htmlFor="show-icon-labels">
							{ __(
								'Show icon labels',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
						<FormToggle
							id="show-icon-labels"
							label={ __(
								'Show icon labels',
								'social-icons-widget-by-wpzoom'
							) }
							checked={ this.props.attributes.showIconsLabel }
							onChange={ () => {
								setAttributes( {
									showIconsLabel: ! this.props.attributes
										.showIconsLabel,
								} );
							} }
						/>
					</PanelRow>
					<PanelRow>
						<label htmlFor="open-link-in-new-tab">
							{ __(
								'Open links in a new tab',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
						<FormToggle
							id="open-link-in-new-tab"
							label={ __(
								'Open links in a new tab',
								'social-icons-widget-by-wpzoom'
							) }
							checked={ this.props.attributes.openLinkInNewTab }
							onChange={ () => {
								setAttributes( {
									openLinkInNewTab: ! this.props.attributes
										.openLinkInNewTab,
									noopener: ! this.props.attributes
										.noopener,

								} );
							} }
						/>
					</PanelRow>
						<Fragment>
							<PanelRow>
								<label htmlFor="add-nofollow-to-links">
									{ __(
										'Add rel="nofollow" to links',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
								<FormToggle
									id="add-nofollow-to-links"
									label={ __(
										'Add rel="nofollow" to links',
										'social-icons-widget-by-wpzoom'
									) }
									checked={ this.props.attributes.nofollow }
									onChange={ () => {
										setAttributes( {
											nofollow: ! this.props.attributes
												.nofollow,
										} );
									} }
								/>
							</PanelRow>
							<PanelRow>
								<label htmlFor="add-noreferrer-to-links">
									{ __(
										'Add rel="noreferrer" to links',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
								<FormToggle
									id="add-noreferrer-to-links"
									label={ __(
										'Add rel="noreferrer" to links',
										'social-icons-widget-by-wpzoom'
									) }
									checked={ this.props.attributes.noreferrer }
									onChange={ () => {
										setAttributes( {
											noreferrer: ! this.props.attributes
												.noreferrer,
										} );
									} }
								/>
							</PanelRow>
							<PanelRow>
								<label htmlFor="add-noopener-to-links">
									{ __(
										'Add rel="noopener" to links',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
								<FormToggle
									id="add-noopener-to-links"
									label={ __(
										'Add rel="noopener" to links',
										'social-icons-widget-by-wpzoom'
									) }
									checked={ this.props.attributes.noopener }
									onChange={ () => {
										setAttributes( {
											noopener: ! this.props.attributes
												.noopener,
										} );
									} }
								/>
							</PanelRow>
							<PanelRow>
								<label htmlFor="add-noopener-to-links">
									{ __(
										'Add rel="me" to links',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
								<FormToggle
									id="add-noopener-to-links"
									label={ __(
										'Add rel="me" to links',
										'social-icons-widget-by-wpzoom'
									) }
									checked={ this.props.attributes.relme }
									onChange={ () => {
										setAttributes( {
											relme: ! this.props.attributes
												.relme,
										} );
									} }
								/>
							</PanelRow>
						</Fragment>
				</PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
				<StylePicker
					name={ this.props.name }
					attributes={ this.props.attributes }
					onSelect={ ( styleName ) =>
						setAttributes( {
							className: withStyleClass( this.props.attributes.className, styleName ),
						} )
					}
				/>
				<PanelBody
					title={ __(
						'Icon Styling Settings',
						'social-icons-widget-by-wpzoom'
					) }
				>
					{ this.props.attributes.iconsHasBorder ? (
						<Fragment>
							<PanelRow>
								<label htmlFor="icons-border-radius">
									{ __(
										'Icons Border Radius:',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
							</PanelRow>
							<PanelRow>
								<RangeControl
									id={ 'icons-border-radius' }
									min={ 0 }
									max={ 55 }
									value={
										this.props.attributes.iconsBorderRadius
									}
									onChange={ ( newFontSize ) => {
										setAttributes( {
											iconsBorderRadius: newFontSize,
										} );
									} }
								/>
							</PanelRow>
						</Fragment>
					) : null }
					<PanelRow>
						<span id="wpzoom-icons-alignment-label">
							{ __( 'Icons Alignment:', 'social-icons-widget-by-wpzoom' ) }
						</span>
					</PanelRow>
					<PanelRow>
						<div
							className="components-button-group"
							role="group"
							aria-labelledby="wpzoom-icons-alignment-label"
						>
							<Button
								onClick={ () => this.setAlignment( 'left' ) }
								isPrimary={ isLeftAlignment }
								isSecondary={ ! isLeftAlignment }
							>
								{ __( 'Left', 'social-icons-widget-by-wpzoom' ) }
							</Button>
							<Button
								onClick={ () => this.setAlignment( 'center' ) }
								isPrimary={ isCenterAlignment }
								isSecondary={ ! isCenterAlignment }
							>
								{ __( 'Center', 'social-icons-widget-by-wpzoom' ) }
							</Button>
							<Button
								onClick={ () => this.setAlignment( 'right' ) }
								isPrimary={ isRightAlignment }
								isSecondary={ ! isRightAlignment }
							>
								{ __( 'Right', 'social-icons-widget-by-wpzoom' ) }
							</Button>
						</div>
					</PanelRow>
					<PanelRow>
						<label htmlFor="icons-font-size">
							{ __( 'Icons Font Size:', 'social-icons-widget-by-wpzoom' ) }
						</label>
					</PanelRow>
					<PanelRow>
						<RangeControl
							id="icons-font-size"
							min={ 0 }
							max={ 200 }
							value={ this.props.attributes.iconsFontSize }
							onChange={ ( newFontSize ) => {
								setAttributes( { iconsFontSize: newFontSize } );
							} }
						/>
					</PanelRow>
					{ this.props.attributes.showIconsLabel ? (
						<Fragment>
							<PanelRow>
								<label htmlFor="icons-label-font-size">
									{ __(
										'Icons Label Font Size:',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
							</PanelRow>
							<PanelRow>
								<RangeControl
									id="icons-label-font-size"
									min={ 0 }
									max={ 200 }
									value={
										this.props.attributes.iconsLabelFontSize
									}
									onChange={ ( newFontSize ) => {
										setAttributes( {
											iconsLabelFontSize: newFontSize,
										} );
									} }
								/>
							</PanelRow>
						</Fragment>
					) : null }

					<PanelRow>
						<label htmlFor="icons-padding">
							{ __(
								'Icons Padding Horizontal:',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
					</PanelRow>
					<PanelRow>
						<RangeControl
							id="icons-padding-horizontal"
							beforeIcon={ 'image-flip-horizontal' }
							value={ this.props.attributes.iconsPaddingHorizontal }
							onChange={ ( padding ) =>
								setAttributes( {
									iconsPaddingHorizontal: padding,
								} )
							}
							min={ 0 }
							max={ 200 }
						/>
					</PanelRow>
					<PanelRow>
						<label htmlFor="icons-padding">
							{ __(
								'Icons Padding Vertical:',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
					</PanelRow>
					<PanelRow>
						<RangeControl
							id="icons-padding-vertical"
							beforeIcon={ 'image-flip-vertical' }
							value={ this.props.attributes.iconsPaddingVertical }
							onChange={ ( padding ) =>
								setAttributes( { iconsPaddingVertical: padding } )
							}
							min={ 0 }
							max={ 200 }
						/>
					</PanelRow>
					<PanelRow>
						<label htmlFor="icons-margin-horizontal">
							{ __(
								'Icons Margin Horizontal:',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
					</PanelRow>
					<PanelRow>
						<RangeControl
							id="icons-margin-horizontal"
							beforeIcon={ 'image-flip-horizontal' }
							value={ this.props.attributes.iconsMarginHorizontal }
							onChange={ ( margin ) =>
								setAttributes( { iconsMarginHorizontal: margin } )
							}
							min={ 0 }
							max={ 200 }
						/>
					</PanelRow>
					<PanelRow>
						<label htmlFor="icons-margin-vertical">
							{ __(
								'Icons Margin Vertical:',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
					</PanelRow>
					<PanelRow>
						<RangeControl
							id="icons-margin-vertical"
							beforeIcon={ 'image-flip-vertical' }
							value={ this.props.attributes.iconsMarginVertical }
							onChange={ ( margin ) =>
								setAttributes( { iconsMarginVertical: margin } )
							}
							min={ 0 }
							max={ 200 }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'Icon Color Settings',
						'social-icons-widget-by-wpzoom'
					) }
				>
					<PanelRow>
						<label htmlFor="icon-color">
							{ __(
								'Set color for all icons',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
						<ColorIndicator
							colorValue={ this.props.attributes.iconsColor }
						/>
					</PanelRow>
					<PanelRow>
						<ColorPalette
							id={ 'icon-color' }
							colors={ colors }
							value={ this.props.attributes.iconsColor }
							onChange={ ( color ) => {
								const clonedSelectedIcons = JSON.parse(
									JSON.stringify(
										this.props.attributes.selectedIcons
									)
								);

								clonedSelectedIcons.map( ( item, key ) => {
									item.color =
										undefined === color
											? this.state.selectedIcons[ key ]
												.color
											: color;
									return item;
								} );

								setAttributes( {
									iconsColor: color,
									selectedIcons: clonedSelectedIcons,
								} );
							} }
						/>
					</PanelRow>
					<PanelRow>
						<label htmlFor="icon-hover-color">
							{ __(
								'Set hover color for all icons',
								'social-icons-widget-by-wpzoom'
							) }
						</label>
						<ColorIndicator
							colorValue={ this.props.attributes.iconsHoverColor }
						/>
					</PanelRow>
					<PanelRow>
						<ColorPalette
							id={ 'icon-hover-color' }
							colors={ colors }
							value={ this.props.attributes.iconsHoverColor }
							onChange={ ( color ) => {
								const clonedSelectedIcons = [
									...this.props.attributes.selectedIcons,
								];

								clonedSelectedIcons.map( ( item, key ) => {
									item.hoverColor =
										undefined === color
											? this.state.selectedIcons[ key ]
												.hoverColor
											: color;
									return item;
								} );

								setAttributes( {
									iconsHoverColor: color,
									selectedIcons: clonedSelectedIcons,
								} );
							} }
						/>
					</PanelRow>
					{ this.props.attributes.showIconsLabel ? (
						<Fragment>
							<PanelRow>
								<label htmlFor="icon-label-color">
									{ __(
										'Set color for all label icons',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
								<ColorIndicator
									colorValue={
										this.props.attributes.iconsLabelColor
									}
								/>
							</PanelRow>
							<PanelRow>
								<ColorPalette
									id={ 'icon-label-color' }
									colors={ colors }
									value={
										this.props.attributes.iconsLabelColor
									}
									onChange={ ( color ) => {
										setAttributes( {
											iconsLabelColor: color,
										} );
									} }
								/>
							</PanelRow>
							<PanelRow>
								<label htmlFor="icon-hover-label-color">
									{ __(
										'Set hover color for all label icons',
										'social-icons-widget-by-wpzoom'
									) }
								</label>
								<ColorIndicator
									colorValue={
										this.props.attributes
											.iconsLabelHoverColor
									}
								/>
							</PanelRow>
							<PanelRow>
								<ColorPalette
									id={ 'icon-hover-label-color' }
									colors={ colors }
									value={
										this.props.attributes
											.iconsLabelHoverColor
									}
									onChange={ ( color ) => {
										setAttributes( {
											iconsLabelHoverColor: color,
										} );
									} }
								/>
							</PanelRow>
						</Fragment>
					) : null }
				</PanelBody>
			</InspectorControls>
            </>
		);
	}
}
