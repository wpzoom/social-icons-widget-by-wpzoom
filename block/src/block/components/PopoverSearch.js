/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useEffect, useMemo, useState } from '@wordpress/element';
import { TextControl, Button, Icon } from '@wordpress/components';

/**
 * URL field with icon detection.
 *
 * @param {Object}   props
 * @param {string}   props.value   Current (saved) URL.
 * @param {Function} props.save    Called with the URL when Detect Icon is used.
 * @param {*}        props.icon    The applied icon to show in the field instead of
 *                                 the globe, or null to show the globe.
 * @param {string}   props.color   Brand color behind the applied icon.
 * @param {Function} props.preview Optional: ( typedUrl ) => { node, color } | null.
 *                                 While the field holds an unsaved URL, its match is
 *                                 shown live, so the icon appears as you type.
 */
export default function PopoverSearch( {
	value,
	save,
	icon = null,
	color = '',
	preview = null,
} ) {
	const [ search, setSearch ] = useState( value );

	// Follow URL changes made elsewhere (e.g. the "Choose Icon" modal).
	useEffect( () => {
		setSearch( value );
	}, [ value ] );

	const isEditing = ( search || '' ).trim() !== ( value || '' ).trim();
	const live = useMemo(
		() => ( isEditing && preview ? preview( search ) : null ),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ search, value ]
	);

	// While typing: the live match (or the globe). Otherwise: the applied icon.
	const shown = isEditing ? live : icon && { node: icon, color };

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
				{ shown ? (
					<span
						className="url-input-icon url-input-icon--matched"
						style={ shown.color ? { backgroundColor: shown.color } : undefined }
						aria-hidden="true"
					>
						{ shown.node }
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
					placeholder={ __( 'https://example.com', 'social-icons-widget-by-wpzoom' ) }
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
