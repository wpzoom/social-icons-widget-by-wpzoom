/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Helper from '../utils/helper';
import { getSvg, useIconKits } from '../utils/svg-icons';

/**
 * An icon span as the block saves it (`social-icon socicon socicon-x`), with
 * the icon drawn inside as SVG once its kit has loaded. Other props go to the
 * span.
 */
const IconGlyph = forwardRef( ( { iconKit, icon, className, ...props }, ref ) => {
	useIconKits( [ iconKit ] );
	const svg = getSvg( iconKit, icon );

	return (
		<span
			ref={ ref }
			{ ...props }
			className={ classnames( Helper.getIconClassList( iconKit, icon ), className, {
				'wpzoom-svg': !! svg,
			} ) }
			dangerouslySetInnerHTML={ svg ? { __html: svg } : undefined }
		/>
	);
} );

export default IconGlyph;
