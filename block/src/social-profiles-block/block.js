/**
 * BLOCK: wpzoom-blocks/social-profiles
 *
 * Social profiles as a list (free), cards or buttons (PRO): each with the
 * network's icon, the handle taken from its link and an optional follower
 * count. Drawn on the page by WPZOOM_Social_Profiles::render() in PHP, so
 * nothing is saved in the post but the attributes.
 */

import './editor.scss';
import './style.scss';

/**
 * Internal dependencies
 */
import blockIcon from './blockIcon';
import Edit from './components/Edit';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'wpzoom-blocks/social-profiles', {
	apiVersion: 3,
	title: __( 'Social Profiles', 'social-icons-widget-by-wpzoom' ),
	description: __(
		'Your social profiles as a list with handles and follower counts. Paste your links and the icons come with them.',
		'social-icons-widget-by-wpzoom'
	),
	icon: blockIcon,
	category: 'wpzoom-blocks',
	keywords: [
		__( 'Follow', 'social-icons-widget-by-wpzoom' ),
		__( 'Social links', 'social-icons-widget-by-wpzoom' ),
		__( 'Followers', 'social-icons-widget-by-wpzoom' ),
		__( 'Link in bio', 'social-icons-widget-by-wpzoom' ),
	],
	// Kept in step with the attributes registered in PHP (init.php).
	attributes: {
		items: {
			type: 'array',
			default: [],
		},
		layout: {
			type: 'string',
			default: 'list',
		},
		display: {
			type: 'string',
			default: 'handle',
		},
		colorMode: {
			type: 'string',
			default: 'brand',
		},
		color: {
			type: 'string',
			default: '',
		},
		columns: {
			type: 'number',
			default: 2,
		},
		showCounts: {
			type: 'boolean',
			default: true,
		},
		showCta: {
			type: 'boolean',
			default: true,
		},
		ctaText: {
			type: 'string',
			default: '',
		},
		buttonText: {
			type: 'string',
			default: '',
		},
		openInNewTab: {
			type: 'boolean',
			default: true,
		},
		nofollow: {
			type: 'boolean',
			default: false,
		},
		// Styles tab: empty means the layout's own look.
		radius: { type: 'number' },
		borderWidth: { type: 'number' },
		borderColor: { type: 'string', default: '' },
		background: { type: 'string', default: '' },
		textColor: { type: 'string', default: '' },
		padding: { type: 'number' },
		gap: { type: 'number' },
		iconSize: { type: 'number' },
		iconRadius: { type: 'number' },
	},
	supports: {
		html: false,
		align: [ 'wide', 'full' ],
		spacing: {
			margin: true,
		},
		typography: {
			fontSize: true,
			lineHeight: true,
		},
	},
	example: {
		attributes: {
			items: [
				{ url: 'https://instagram.com/wpzoom', iconKit: 'socicon', icon: 'instagram', network: 'Instagram', color: '#E4405F', count: '18.2K' },
				{ url: 'https://youtube.com/@wpzoom', iconKit: 'socicon', icon: 'youtube', network: 'YouTube', color: '#FF0000', count: '4.1K' },
				{ url: 'https://x.com/wpzoom', iconKit: 'socicon', icon: 'x', network: 'X', color: '#000000', count: '9.8K' },
			],
		},
	},
	edit: Edit,
	save: () => null,
} );
