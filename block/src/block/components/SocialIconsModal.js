/* global wpzSocialIconsBlock */

import ModalColorPicker from './ModalColorPicker';
import SocialIcon from './SocialIcon';
import Helper from '../utils/helper';
import React from 'react';
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import {
	Button,
	Modal,
	TextControl,
	SelectControl,
} from '@wordpress/components';

const { iconKitsCategories } = wpzSocialIconsBlock;

class SocialIconsModal extends Component {
	state = {
		modalShow: this.props.show,
		modalUrl: this.props.url,
		modalLabel: this.props.label,
		modalIcon: this.props.icon,
		modalIconKit: this.props.iconKit,
		modalColor: this.props.color,
		modalHoverColor: this.props.hoverColor,
		modalSearch: '',
	};

	constructor( props ) {
		super( props );
		this.myRef = React.createRef();
	}

	/**
	 * State for choosing `icon` from `iconKit`. Keeps what the user customised:
	 * the label only follows the icon while it is still the automatic one, and
	 * colors follow the icon's brand color until a color is picked in this
	 * dialog.
	 *
	 * @param {Object} state   Current state.
	 * @param {string} iconKit Kit of the chosen icon.
	 * @param {string} icon    Chosen icon.
	 */
	static selectIconState( state, iconKit, icon ) {
		const next = { modalIcon: icon, modalIconKit: iconKit };

		const autoLabel = Helper.humanizeIconLabel( state.modalIcon || '' );
		if ( ! state.modalLabel || state.modalLabel === autoLabel ) {
			next.modalLabel = Helper.humanizeIconLabel( icon );
		}

		const newBrand = Helper.brandColor( iconKit, icon );
		if ( newBrand ) {
			if ( ! state.colorTouched ) {
				next.modalColor = newBrand;
			}
			if ( ! state.hoverColorTouched ) {
				next.modalHoverColor = newBrand;
			}
		}
		return next;
	}

	/*
	 * The URL is kept exactly as typed (it used to be rewritten to
	 * "https://…" on the first keystroke) and normalised on save. A recognised
	 * address selects its icon from any kit — Socicons first.
	 */
	urlTextControlHandler = ( value ) => {
		this.setState( ( state ) => {
			const next = { modalUrl: value };
			const match = Helper.detectIcon( value );
			if ( match && ( match.icon !== state.modalIcon || match.iconKit !== state.modalIconKit ) ) {
				Object.assign( next, SocialIconsModal.selectIconState( state, match.iconKit, match.icon ) );
			}
			return next;
		} );
	};

	labelTextControlHandler = ( value ) => {
		this.setState( { modalLabel: value } );
	};

	searchTextControlHandler = ( value ) => {
		this.setState( { modalSearch: value } );
	};

	saveColorPickerHandler = ( arg ) => {
		this.setState( { modalColor: arg.color, colorTouched: true } );
	};

	saveHoverColorPickerHandler = ( arg ) => {
		this.setState( { modalHoverColor: arg.color, hoverColorTouched: true } );
	};

	onSave = () => {
		this.props.save( {
			...this.state,
			modalUrl: Helper.normalizeUrl( this.state.modalUrl ),
		} );
	};

	static getDerivedStateFromProps( props, state ) {
		if ( props.show !== state.modalShow ) {
			return {
				modalShow: props.show,
				modalUrl: props.url,
				modalLabel: props.label,
				modalIcon: props.icon,
				modalIconKit: props.iconKit,
				modalColor: props.color,
				modalHoverColor: props.hoverColor,
				modalSearch: '',
				colorTouched: false,
				hoverColorTouched: false,
			};
		}

		return null;
	}

	onClickIconHandler = ( icon, iconKit ) => {
		this.setState( ( state ) => SocialIconsModal.selectIconState( state, iconKit, icon ) );
	};

	componentDidMount() {
		if ( this.state.modalShow ) {
			this.scrollToSelected();
		}
	}

	// Bring the selected icon into view when the modal opens or the kit
	// changes — not after every update, which made the list jump while typing.
	componentDidUpdate( prevProps, prevState ) {
		const opened = ! prevState.modalShow && this.state.modalShow;
		const kitChanged = prevState.modalIconKit !== this.state.modalIconKit;
		if ( this.state.modalShow && ( opened || kitChanged ) && ! this.isSearching() ) {
			this.scrollToSelected();
		}
	}

	scrollToSelected = () => {
		window.requestAnimationFrame( () => {
			const node = this.myRef.current;
			if ( node && node.scrollIntoView ) {
				node.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
			}
		} );
	};

	/**
	 * Icons to offer: no networks that have shut down (the icon already chosen
	 * stays, so an existing block still shows it) and no icons that can't be
	 * drawn.
	 *
	 * @param {string} iconKit Kit.
	 * @param {Array}  items   Icon data items.
	 * @return {Array} Visible items.
	 */
	visibleItems( iconKit, items ) {
		return items.filter(
			( item ) =>
				Helper.canDraw( item ) &&
				( ! Helper.isRetired( iconKit, item.icon ) ||
					( this.state.modalIcon === item.icon && this.state.modalIconKit === iconKit ) )
		);
	}

	isSearching() {
		return ( this.state.modalSearch || '' ).trim() !== '';
	}

	renderIcons( iconKit, items, withKitName ) {
		return items.map( ( element ) => {
			const isSelected =
				this.state.modalIcon === element.icon && this.state.modalIconKit === iconKit;
			return (
				<SocialIcon
					key={ `${ iconKit }-${ element.icon }` }
					setRef={ isSelected ? this.myRef : null }
					color={ this.state.modalColor }
					hoverColor={ this.state.modalHoverColor }
					icon={ element.icon }
					iconKit={ iconKit }
					kitLabel={ withKitName ? Helper.kitLabel( iconKit ) : null }
					click={ () => this.onClickIconHandler( element.icon, iconKit ) }
					isSelected={ isSelected }
				/>
			);
		} );
	}

	render() {
		if ( ! this.state.modalShow ) {
			return null;
		}

		let iconElements;
		if ( this.isSearching() ) {
			// Search every enabled kit, in priority order.
			const filtered = Helper.filterIcons( this.state.modalSearch );
			const groups = Helper.kitOrder()
				.map( ( kit ) => ( { kit, items: this.visibleItems( kit, filtered[ kit ] || [] ) } ) )
				.filter( ( group ) => group.items.length );

			iconElements = groups.length ? (
				<div className="icon-search-results">
					{ groups.map( ( { kit, items } ) => (
						<div key={ kit } className="icon-search-group">
							<div className="icon-search-group__title">
								{ Helper.kitLabel( kit ) }
								<span className="icon-search-group__count">{ items.length }</span>
							</div>
							<div className={ classnames( 'icon-kit', `${ kit }-wrapper` ) }>
								{ this.renderIcons( kit, items, true ) }
							</div>
						</div>
					) ) }
				</div>
			) : (
				<p className="icon-search-empty">
					{ Helper.kitOrder().some( ( kit ) =>
						( filtered[ kit ] || [] ).some( ( item ) => Helper.isRetired( kit, item.icon ) )
					)
						? sprintf(
							/* translators: %s: search term */
							__( '“%s” is a network that has shut down, so it is no longer offered. Icons already on your site keep working.', 'social-icons-widget-by-wpzoom' ),
							this.state.modalSearch.trim()
						)
						: sprintf(
							/* translators: %s: search term */
							__( 'No icons match “%s” in any icon kit.', 'social-icons-widget-by-wpzoom' ),
							this.state.modalSearch.trim()
						) }
				</p>
			);
		} else {
			const items = this.visibleItems( this.state.modalIconKit, Helper.filterIcons( '' )[ this.state.modalIconKit ] || [] );
			iconElements = (
				<div className={ classnames( 'option-item', 'icon-kit', `${ this.state.modalIconKit }-wrapper` ) }>
					{ this.renderIcons( this.state.modalIconKit, items, false ) }
				</div>
			);
		}

		return (
			<Modal
				className={ classnames(
					'wpzoom-social-icons-modal',
					this.props.className
				) }
				style={ {
					'--wpz-social-icons-block-modal-item-border-radius':
						Helper.addPixelsPipe( this.props.iconsBorderRadius ),
				} }
				title={ __( 'Select Icon', 'social-icons-widget-by-wpzoom' ) }
				shouldCloseOnClickOutside={ false }
				onRequestClose={ () => this.props.onClose( this.state ) }
			>
				<div className={ 'modal-content' }>
					<div className={ 'option-item' }>
						<div className="label">{ __( 'URL', 'social-icons-widget-by-wpzoom' ) }</div>
						<div className="option-wrapper">
							<TextControl
								value={ this.state.modalUrl }
								onChange={ this.urlTextControlHandler }
								placeholder={ __( 'https://example.com', 'social-icons-widget-by-wpzoom' ) }
							/>
						</div>
					</div>
					<div className="option-item">
						<div className="label">
							{ __( 'Label', 'social-icons-widget-by-wpzoom' ) }
						</div>
						<div className="option-wrapper">
							<TextControl
								value={ this.state.modalLabel }
								onChange={ this.labelTextControlHandler }
							/>
						</div>
					</div>
					<div className="option-item">
						<div className="label">
							{ __( 'Pick icon color', 'social-icons-widget-by-wpzoom' ) }
						</div>
						<div className="option-wrapper">
							<ModalColorPicker
								title={ __( 'Icon color', 'social-icons-widget-by-wpzoom' ) }
								save={ this.saveColorPickerHandler }
								color={ this.state.modalColor }
							/>
						</div>
					</div>
					<div className="option-item">
						<div className="label">
							{ __( 'Pick hover color', 'social-icons-widget-by-wpzoom' ) }
						</div>
						<div className="option-wrapper">
							<ModalColorPicker
								title={ __( 'Hover color', 'social-icons-widget-by-wpzoom' ) }
								save={ this.saveHoverColorPickerHandler }
								color={ this.state.modalHoverColor }
							/>
						</div>
					</div>
					<div className="option-item">
						<div className="label">
							{ __( 'Select Icon Kit', 'social-icons-widget-by-wpzoom' ) }
						</div>
						<div className="option-wrapper">
							<SelectControl
								value={ this.state.modalIconKit }
								onChange={ ( currentIconKit ) => {
									this.setState( { modalIconKit: currentIconKit } );
								} }
								options={ Object.values( iconKitsCategories ) }
								help={
									this.isSearching()
										? __( 'Searching all icon kits.', 'social-icons-widget-by-wpzoom' )
										: undefined
								}
							/>
						</div>
					</div>
					<div className={ 'option-item icon-kits-wrapper' }>{ iconElements }</div>
				</div>
				<div className="modal-controls">
					<div className="modal-search">
						<TextControl
							placeholder={ __(
								'Search all icon kits',
								'social-icons-widget-by-wpzoom'
							) }
							value={ this.state.modalSearch }
							onChange={ this.searchTextControlHandler }
							type="search"
						/>
					</div>
					<div className="modal-buttons">
						{ this.props.showDeleteBtn && (
							<Button
								className={ 'button-link-delete is-button' }
								onClick={ () => this.props.delete() }
							>
								{ __( 'Delete Icon', 'social-icons-widget-by-wpzoom' ) }
							</Button>
						) }
						<Button isPrimary onClick={ this.onSave }>
							{ __( 'Save', 'social-icons-widget-by-wpzoom' ) }
						</Button>
					</div>
				</div>
			</Modal>
		);
	}
}

export default SocialIconsModal;
