import { Component } from '@wordpress/element';
import classnames from 'classnames';
import Helper from '../utils/helper';
import IconGlyph from './IconGlyph';

/**
 * One icon in the "Select Icon" grid. Behaves as a toggle button: focusable,
 * activated with Enter or Space, and named for screen readers and on hover —
 * look-alike icons from different kits are otherwise hard to tell apart.
 */
class SocialIcon extends Component {
	state = {
		isHover: false,
	};

	onMouseEnterCallback = () => {
		this.setState( { isHover: true } );
	};

	onMouseLeaveCallback = () => {
		this.setState( { isHover: false } );
	};

	onKeyDown = ( e ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			this.props.click( this.props.icon );
		}
	};

	render() {
		const name = Helper.humanizeIconLabel( this.props.icon );
		return (
			<IconGlyph
				ref={ this.props.setRef }
				iconKit={ this.props.iconKit }
				icon={ this.props.icon }
				role="button"
				tabIndex={ 0 }
				title={ this.props.kitLabel ? `${ name } — ${ this.props.kitLabel }` : name }
				aria-label={ name }
				aria-pressed={ !! this.props.isSelected }
				onClick={ () => this.props.click( this.props.icon ) }
				onKeyDown={ this.onKeyDown }
				className={ classnames( { selected: this.props.isSelected } ) }
				style={ {
					backgroundColor: this.state.isHover ? this.props.hoverColor : this.props.color,
				} }
				onMouseEnter={ this.onMouseEnterCallback }
				onMouseLeave={ this.onMouseLeaveCallback }
			/>
		);
	}
}

export default SocialIcon;
