/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { Icon, link } from '@wordpress/icons';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Glyph from './Glyph';
import { buttonLabel, defaultCountLabel, profileText } from '../utils/profiles';

/**
 * One profile as the page shows it (WPZOOM_Social_Profiles::render_item() in
 * PHP draws the same markup), as a button that opens its settings. Children
 * (its settings popover) go inside the item.
 */
const Profile = forwardRef( ( { item, attributes, isOpen, onClick, children }, ref ) => {
	const { layout, display, showCounts, showCta, ctaText, buttonText } = attributes;
	const isBlank = ! item.icon;
	const text = isBlank ? __( 'New profile', 'social-icons-widget-by-wpzoom' ) : profileText( item, display );
	const count = showCounts && item.count ? item.count : '';

	return (
		<li
			className={ classnames( 'wpz-sp__item', { 'is-blank': isBlank, 'is-open': isOpen } ) }
			style={ item.color ? { '--wpz-sp-brand': item.color } : undefined }
		>
			<div
				ref={ ref }
				className="wpz-sp__link"
				role="button"
				tabIndex={ 0 }
				aria-expanded={ isOpen }
				onClick={ onClick }
				onKeyDown={ ( e ) => {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						e.preventDefault();
						onClick();
					}
				} }
			>
				<span className="wpz-sp__icon" aria-hidden="true">
					{ isBlank ? <Icon icon={ link } /> : <Glyph iconKit={ item.iconKit } icon={ item.icon } /> }
				</span>
				{ layout === 'buttons' ? (
					<span className="wpz-sp__name">{ isBlank ? text : buttonLabel( item, buttonText ) }</span>
				) : (
					<span className="wpz-sp__text">
						<span className="wpz-sp__name">{ text }</span>
						{ ! isBlank && ! item.url && (
							// Editor only: the page shows the name, unlinked.
							<span className="wpz-sp__meta wpz-sp__nolink">{ __( 'Add your link', 'social-icons-widget-by-wpzoom' ) }</span>
						) }
						{ count && (
							<span className="wpz-sp__meta">
								<strong>{ count }</strong> { item.countLabel || defaultCountLabel( item.icon ) }
							</span>
						) }
					</span>
				) }
				{ layout === 'list' && showCta && ! isBlank && item.url && (
					<span className="wpz-sp__cta">
						{ ctaText || __( 'Follow', 'social-icons-widget-by-wpzoom' ) }
						<span aria-hidden="true">→</span>
					</span>
				) }
			</div>
			{ children }
		</li>
	);
} );

export default Profile;
