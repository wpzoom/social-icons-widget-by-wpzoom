import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Icon, Button, ButtonGroup } from '@wordpress/components';
import { chevronLeft, chevronRight } from '@wordpress/icons';

/*
 * Inline SVG chevrons, not Dashicon names. The block uses API version 3, so the
 * editor canvas is an iframe, and a Dashicon name there
 * relies on the Dashicons font being loaded into that iframe, which only happens
 * when the Dashicons icon kit is enabled — otherwise they rendered as empty boxes.
 */
const SortableArrows = ( props ) => {
	return (
		props.isActive && (
			<ButtonGroup className={ classnames( 'sortable-arrows' ) }>
				<Button
					className={ classnames( 'arrow-btn' ) }
					isSmall
					label={ __( 'Move left', 'social-icons-widget-by-wpzoom' ) }
					disabled={ props.itemKey === 0 }
					onClick={ ( e ) => props.left( e, props.itemKey ) }
				>
					<Icon
						icon={ chevronLeft }
						size={ 18 }
						className={ classnames( 'arrow-icon' ) }
					/>
				</Button>
				<Button
					className={ classnames( 'arrow-btn' ) }
					isSmall
					label={ __( 'Move right', 'social-icons-widget-by-wpzoom' ) }
					disabled={ props.itemKey === props.length - 1 }
					onClick={ ( e ) => props.right( e, props.itemKey ) }
				>
					<Icon
						icon={ chevronRight }
						size={ 18 }
						className={ classnames( 'arrow-icon' ) }
					/>
				</Button>
			</ButtonGroup>
		)
	);
};

export default SortableArrows;
