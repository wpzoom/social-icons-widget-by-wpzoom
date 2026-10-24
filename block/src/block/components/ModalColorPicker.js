import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, ColorIndicator, ColorPicker, Dropdown } from '@wordpress/components';

/**
 * Color swatch that opens a picker.
 *
 * Controlled: it always shows the `color` it is given, so it follows changes
 * made elsewhere (e.g. Detect Icon applying a brand color) — it used to copy
 * the color into its own state once and then show a stale swatch. Built on
 * Dropdown, so the swatch is a real button (keyboard friendly), clicking it
 * again toggles the picker, and clicking anywhere else closes it — the old
 * hand-rolled popover stayed open inside the "Select Icon" dialog.
 */
class ModalColorPicker extends Component {
	onChange = ( color ) => {
		// Legacy picker API passes { hex }, the current one a string.
		const hex = typeof color === 'string' ? color : color && color.hex;
		if ( hex ) {
			this.props.save( { color: hex } );
		}
	};

	render() {
		const { color, title, className } = this.props;

		return (
			<Dropdown
				className="wpzoom-color-indicator-wrap"
				popoverProps={ { position: 'middle right' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						className="wpzoom-color-indicator-button"
						label={ title || __( 'Change color', 'social-icons-widget-by-wpzoom' ) }
						showTooltip={ false }
						aria-haspopup="true"
						aria-expanded={ isOpen }
						onClick={ onToggle }
					>
						<ColorIndicator className={ classnames( className ) } colorValue={ color } />
					</Button>
				) }
				renderContent={ () => (
					<div className="popover-content">
						<ColorPicker
							className="wpzoom-color-picker"
							disableAlpha
							color={ color }
							onChangeComplete={ this.onChange }
						/>
					</div>
				) }
			/>
		);
	}
}

export default ModalColorPicker;
