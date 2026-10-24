/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import { TextControl, Button, Icon } from '@wordpress/components';

/**
 * @param {Object}   props
 * @param {string}   props.value Current URL.
 * @param {Function} props.save  Called with the URL when Detect Icon is used.
 * @param {*}        props.icon  The matched icon to show in the field instead of
 *                               the globe, or null to show the globe.
 * @param {string}   props.color Brand color behind the matched icon.
 */
export default function PopoverSearch( {
	value,
	save,
	icon = null,
	color = '',
} ) {
	const [ search, setSearch ] = useState( value );

	const onKeyDownHandler = ( e ) => {
		e.stopPropagation();
		if ( e.key === 'Enter' ) {
			save( search );
		}
	};

	const onClickHandler = ( e ) => {
		e.stopPropagation();
		save( search );
	};

	return (
		<Fragment>
			<div className="url-input-wrapper">
				{ icon ? (
					<span
						className="url-input-icon url-input-icon--matched"
						style={ color ? { backgroundColor: color } : undefined }
						aria-hidden="true"
					>
						{ icon }
					</span>
				) : (
					<Icon icon="admin-site" className="url-input-icon" />
				) }
				<TextControl
					className="url-input"
					type="text"
					value={ search }
					onChange={ setSearch }
					onKeyDown={ onKeyDownHandler }
					onFocus={ ( e ) => e.target.select() }
					placeholder={ __('https://example.com', 'social-icons-widget-by-wpzoom') }
				/>
			</div>
			<Button
				icon="update"
				isPrimary
				onClick={ onClickHandler }
				className="url-apply-button"
			>
				{ __( 'Detect Icon', 'social-icons-widget-by-wpzoom' ) }
			</Button>
		</Fragment>
	);
}
