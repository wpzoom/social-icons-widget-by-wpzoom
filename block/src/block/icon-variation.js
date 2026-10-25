/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { starEmpty } from '@wordpress/icons';

/**
 * "Icon by WPZOOM": the Social Icons block set up for a single icon with no
 * link and no background, for icons that aren't social (a phone, a feature, a
 * decoration). It is the same block; add a URL and the icon becomes a link.
 *
 * wasStyled keeps Edit from applying the default style's colors and spacing
 * over these on insert. The default icon is drawn even where Font Awesome is
 * off in the picker, since the editor fetches any set's artwork it needs.
 *
 * Shared with the PRO plugin through bin/sync-from-free.sh.
 */
export default {
	name: 'icon',
	title: __( 'Icon by WPZOOM', 'social-icons-widget-by-wpzoom' ),
	description: __(
		'A single icon from more than 2,400, with or without a link.',
		'social-icons-widget-by-wpzoom'
	),
	icon: starEmpty,
	keywords: [
		__( 'icon', 'social-icons-widget-by-wpzoom' ),
		__( 'svg', 'social-icons-widget-by-wpzoom' ),
		__( 'Font Awesome', 'social-icons-widget-by-wpzoom' ),
		__( 'Dashicons', 'social-icons-widget-by-wpzoom' ),
	],
	scope: [ 'inserter' ],
	attributes: {
		className: 'is-style-without-canvas',
		wasStyled: true,
		canvasType: 'without-canvas',
		showIconsLabel: false,
		iconsFontSize: 32,
		iconsPaddingHorizontal: 0,
		iconsPaddingVertical: 0,
		iconsMarginHorizontal: 0,
		iconsMarginVertical: 0,
		selectedIcons: [
			{
				url: '',
				icon: 'check-circle',
				iconKit: 'fas',
				color: '#1e1e1e',
				hoverColor: '#1e1e1e',
				label: 'Check Circle',
				showPopover: false,
				isActive: false,
				customSvg: null,
			},
		],
	},
	// A one-icon block shows as "Icon by WPZOOM" in the editor's sidebar.
	isActive: ( { selectedIcons } ) =>
		Array.isArray( selectedIcons ) && selectedIcons.length === 1,
};
