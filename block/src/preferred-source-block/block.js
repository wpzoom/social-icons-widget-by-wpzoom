/**
 * BLOCK: wpzoom-blocks/preferred-source
 *
 * "Add to Preferred Sources" button for Google Search. Rendered on the server
 * by WPZOOM_Preferred_Source::render_block().
 */

import './editor.scss';

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

registerBlockType( 'wpzoom-blocks/preferred-source', {
	apiVersion: 3,
	title: __( 'Google Preferred Source', 'social-icons-widget-by-wpzoom' ),
	description: __(
		'Button that lets readers add your site as a preferred source on Google, so they see more of it in Top Stories and AI results.',
		'social-icons-widget-by-wpzoom'
	),
	icon: blockIcon,
	category: 'wpzoom-blocks',
	keywords: [
		__( 'Google', 'social-icons-widget-by-wpzoom' ),
		__( 'Preferred Sources', 'social-icons-widget-by-wpzoom' ),
		__( 'Top Stories', 'social-icons-widget-by-wpzoom' ),
		__( 'Follow', 'social-icons-widget-by-wpzoom' ),
	],
	attributes: {
		label: {
			type: 'string',
			default: '',
		},
		theme: {
			type: 'string',
			default: 'light',
		},
		size: {
			type: 'string',
			default: 'medium',
		},
		textAlign: {
			type: 'string',
			default: '',
		},
		domain: {
			type: 'string',
			default: '',
		},
		openInNewTab: {
			type: 'boolean',
			default: true,
		},
	},
	supports: {
		html: false,
		spacing: {
			margin: true,
		},
	},
	example: {
		attributes: {
			textAlign: 'center',
		},
	},
	edit: Edit,
	save: () => null,
} );
