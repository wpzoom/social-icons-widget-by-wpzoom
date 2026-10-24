/* global wpzSocialIconsBlock */

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	AlignmentControl,
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	ExternalLink,
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
	ToggleGroupControl as StableToggleGroupControl,
	ToggleGroupControlOption as StableToggleGroupControlOption,
	__experimentalToggleGroupControl,
	__experimentalToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import blockIcon from '../blockIcon';
import { googleUrl, isLocalDomain, normalizeDomain } from '../utils/domain';

// Stable since WordPress 6.6; experimental before that.
const ToggleGroupControl = StableToggleGroupControl || __experimentalToggleGroupControl;
const ToggleGroupControlOption = StableToggleGroupControlOption || __experimentalToggleGroupControlOption;

const data = ( window.wpzSocialIconsBlock && wpzSocialIconsBlock.preferredSource ) || {};

/**
 * A warning when Google can't list the domain the button points to.
 *
 * @param {string}  domain     Domain the button points to.
 * @param {boolean} isOverride Whether the domain was typed in the block.
 * @return {?string} Message, or null.
 */
function domainWarning( domain, isOverride ) {
	if ( ! domain ) {
		return __( 'Enter your domain, like example.com.', 'social-icons-widget-by-wpzoom' );
	}
	if ( isLocalDomain( domain ) ) {
		return isOverride
			? sprintf(
				/* translators: %s: domain */
				__( '%s doesn’t look like a public domain. Google only lists public sites.', 'social-icons-widget-by-wpzoom' ),
				domain
			)
			: sprintf(
				/* translators: %s: domain */
				__( '%s is a local or development address, so Google can’t list it. The button will point to your real domain once the site is live there.', 'social-icons-widget-by-wpzoom' ),
				domain
			);
	}
	if ( ! isOverride && data.isSubfolder ) {
		return sprintf(
			/* translators: 1: site address, 2: domain */
			__( 'Your site is in a subfolder (%1$s). Google lists whole domains and subdomains only, so the button points to %2$s. If your publication is listed under another domain, enter it below.', 'social-icons-widget-by-wpzoom' ),
			data.homeUrl,
			domain
		);
	}
	return null;
}

export default function Edit( { attributes, setAttributes } ) {
	const { label, theme, size, textAlign, domain, openInNewTab } = attributes;

	const typedDomain = normalizeDomain( domain );
	const targetDomain = typedDomain || data.siteDomain || '';
	const warning = domainWarning( targetDomain, !! typedDomain );

	const blockProps = useBlockProps( {
		className: classnames(
			'wpzoom-preferred-source',
			`is-theme-${ theme }`,
			`is-size-${ size }`,
			{ [ `has-text-align-${ textAlign }` ]: textAlign }
		),
	} );

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					value={ textAlign }
					onChange={ ( value ) => setAttributes( { textAlign: value || '' } ) }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Button', 'social-icons-widget-by-wpzoom' ) }>
					<TextControl
						label={ __( 'Button text', 'social-icons-widget-by-wpzoom' ) }
						value={ label }
						placeholder={ data.defaultLabel }
						onChange={ ( value ) => setAttributes( { label: value } ) }
						help={ __( 'Leave empty to use the default text, translated to your site language.', 'social-icons-widget-by-wpzoom' ) }
					/>
					<ToggleGroupControl
						label={ __( 'Theme', 'social-icons-widget-by-wpzoom' ) }
						value={ theme }
						isBlock
						onChange={ ( value ) => setAttributes( { theme: value } ) }
						help={
							'auto' === theme
								? __( 'Follows each visitor’s light or dark mode setting.', 'social-icons-widget-by-wpzoom' )
								: undefined
						}
					>
						<ToggleGroupControlOption value="light" label={ __( 'Light', 'social-icons-widget-by-wpzoom' ) } />
						<ToggleGroupControlOption value="dark" label={ __( 'Dark', 'social-icons-widget-by-wpzoom' ) } />
						<ToggleGroupControlOption value="auto" label={ __( 'Auto', 'social-icons-widget-by-wpzoom' ) } />
					</ToggleGroupControl>
					<ToggleGroupControl
						label={ __( 'Size', 'social-icons-widget-by-wpzoom' ) }
						value={ size }
						isBlock
						onChange={ ( value ) => setAttributes( { size: value } ) }
					>
						<ToggleGroupControlOption value="small" label={ __( 'Small', 'social-icons-widget-by-wpzoom' ) } />
						<ToggleGroupControlOption value="medium" label={ __( 'Medium', 'social-icons-widget-by-wpzoom' ) } />
						<ToggleGroupControlOption value="large" label={ __( 'Large', 'social-icons-widget-by-wpzoom' ) } />
					</ToggleGroupControl>
					<ToggleControl
						label={ __( 'Open in new tab', 'social-icons-widget-by-wpzoom' ) }
						checked={ openInNewTab }
						onChange={ ( value ) => setAttributes( { openInNewTab: value } ) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Your site on Google', 'social-icons-widget-by-wpzoom' ) }
					className="wpzoom-preferred-source-panel"
				>
					{ warning && (
						<Notice status="warning" isDismissible={ false }>
							{ warning }
						</Notice>
					) }
					<TextControl
						label={ __( 'Domain', 'social-icons-widget-by-wpzoom' ) }
						value={ domain }
						placeholder={ data.siteDomain }
						onChange={ ( value ) => setAttributes( { domain: value } ) }
						help={ __( 'Leave empty to use your site address. A domain or subdomain only, like news.example.com.', 'social-icons-widget-by-wpzoom' ) }
					/>
					{ targetDomain && (
						<p>
							<ExternalLink href={ googleUrl( targetDomain ) }>
								{ __( 'Check your site on Google', 'social-icons-widget-by-wpzoom' ) }
							</ExternalLink>
						</p>
					) }
					<p className="wpzoom-preferred-source-panel__help">
						{ __( 'Google only offers sites it lists. If your site isn’t found on that page, the button can’t add it yet.', 'social-icons-widget-by-wpzoom' ) }
					</p>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<a
					className="wpzoom-preferred-source__button"
					href={ targetDomain ? googleUrl( targetDomain ) : undefined }
					onClick={ ( event ) => event.preventDefault() }
				>
					<span className="wpzoom-preferred-source__logo" aria-hidden="true">
						{ blockIcon }
					</span>
					<span className="wpzoom-preferred-source__label">
						{ label.trim() || data.defaultLabel }
					</span>
				</a>
			</div>
		</>
	);
}
