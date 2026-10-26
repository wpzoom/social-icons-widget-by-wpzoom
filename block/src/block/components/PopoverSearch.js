/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Button, Icon } from '@wordpress/components';
import { globe, keyboardReturn } from '@wordpress/icons';

/**
 * URL field with icon detection, in the style of the block editor's link
 * field: the icon on the left, the field, and a submit button inside it.
 *
 * @param {Object}   props
 * @param {string}   props.value   Current (saved) URL.
 * @param {Function} props.save    Called with the URL on Enter or the submit button.
 * @param {*}        props.icon    The applied icon to show in the field instead of
 *                                 the globe, or null to show the globe.
 * @param {string}   props.color   Brand color behind the applied icon.
 * @param {Function} props.preview Optional: ( typedUrl ) => { node, color } | null.
 *                                 While the field holds an unsaved URL, its match is
 *                                 shown live, so the icon appears as you type.
 * @param {string}   props.button  Submit button label (its tooltip).
 * @param {Function} props.onType  Optional: called with the typed URL a moment
 *                                 after typing stops, to apply its icon right away.
 */
export default function PopoverSearch( {
	value,
	save,
	icon = null,
	color = '',
	preview = null,
	button = __( 'Apply', 'social-icons-widget-by-wpzoom' ),
	onType = null,
} ) {
	const [ search, setSearch ] = useState( value );
	const inputRef = useRef();

	// The field is what a click on an icon is for, so it takes the focus when
	// the popover opens (the popover itself doesn't move focus).
	useEffect( () => {
		if ( inputRef.current ) {
			inputRef.current.focus();
		}
	}, [] );

	// Follow URL changes made elsewhere (e.g. the "Choose Icon" modal).
	useEffect( () => {
		setSearch( value );
	}, [ value ] );

	const isEditing = ( search || '' ).trim() !== ( value || '' ).trim();

	// As in the classic widget, the icon follows the URL while it's typed.
	useEffect( () => {
		if ( ! onType || ! isEditing ) {
			return;
		}
		const timer = setTimeout( () => onType( search ), 250 );
		return () => clearTimeout( timer );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ search ] );
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
			e.preventDefault();
			save( search );
		}
	};

	const onClickHandler = ( e ) => {
		e.stopPropagation();
		save( search );
	};

	return (
		<div className="wpzoom-url-field">
			{ shown ? (
				<span
					className="wpzoom-icon-badge"
					style={ shown.color ? { backgroundColor: shown.color } : undefined }
					aria-hidden="true"
				>
					{ shown.node }
				</span>
			) : (
				<Icon icon={ globe } size={ 20 } className="wpzoom-url-field__globe" />
			) }
			<input
				ref={ inputRef }
				className="wpzoom-url-field__input"
				type="text"
				value={ search || '' }
				onChange={ ( e ) => setSearch( e.target.value ) }
				onKeyDown={ onKeyDownHandler }
				onFocus={ ( e ) => e.target.select() }
				// Leaving the field keeps what was typed, so the link always
				// matches the icon that was applied while typing.
				onBlur={ () => isEditing && save( search ) }
				aria-label={ __( 'URL', 'social-icons-widget-by-wpzoom' ) }
				// Focused when the popover opens; without this the browser drops
				// its saved form entries over the popover.
				autoComplete="off"
				spellCheck={ false }
				placeholder={ __( 'Paste a link', 'social-icons-widget-by-wpzoom' ) }
			/>
			<Button
				className="wpzoom-url-field__submit"
				icon={ keyboardReturn }
				label={ button }
				onClick={ onClickHandler }
			/>
		</div>
	);
}
