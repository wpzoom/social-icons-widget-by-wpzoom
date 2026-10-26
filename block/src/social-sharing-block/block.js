/**
 * BLOCK: wpzoom-social-sharing-block
 *
 * Registering a social sharing block with Gutenberg.
 */
import { assign } from 'lodash';

//  Import CSS.
import './editor.scss';
import './style.scss';

/**
 * Internal dependencies
 */
import blockIcon from './blockIcon';
import Edit from './components/Edit';
import Save from './components/Save';
import savedMarkupDeprecation from './saved-markup-deprecation';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

const attributes = {
	align: {
		type: 'string',
		default: 'none',
	},
	showLabels: {
		type: 'boolean',
		default: true,
	},
	iconColor: {
		type: 'string',
		default: '#ffffff',
	},
	iconHoverColor: {
		type: 'string',
		default: '#ffffff',
	},
	labelColor: {
		type: 'string',
		default: 'inherit',
	},
	labelHoverColor: {
		type: 'string',
		default: '#ffffff',
	},
	iconSize: {
		type: 'number',
		default: 20,
	},
	labelSize: {
		type: 'number',
		default: 16,
	},
	paddingVertical: {
		type: 'number',
		default: 5,
	},
	paddingHorizontal: {
		type: 'number',
		default: 15,
	},
	marginVertical: {
		type: 'number',
		default: 5,
	},
	marginHorizontal: {
		type: 'number',
		default: 5,
	},
	borderRadius: {
		type: 'number',
		default: 50,
	},
	backgroundStyle: {
		type: 'string',
		default: 'brand',
	},
	hasBorder: {
		type: 'boolean',
		default: false,
	},
	borderWidth: {
		type: 'number',
		default: 1,
	},
	borderColor: {
		type: 'string',
		default: '',
	},
	oneToneColor: {
		type: 'string',
		default: '#000000',
	},
	xUsername: {
		type: 'string',
		default: '',
	},
	platforms: {
		type: 'array',
		default: [
			{
				id: 'facebook',
				name: 'Facebook',
				enabled: true,
				color: '#0866FF'
			},
			{
				id: 'x',
				name: 'X',
				enabled: true,
				color: '#000000'
			},
			{
				id: 'threads',
				name: 'Threads',
				enabled: true,
				color: '#000000'
			},
			{
				id: 'linkedin',
				name: 'LinkedIn',
				enabled: true,
				color: '#0966c2'
			},
			{
				id: 'pinterest',
				name: 'Pinterest',
				enabled: false,
				color: '#E60023'
			},
			{
				id: 'reddit',
				name: 'Reddit',
				enabled: true,
				color: '#FF4500'
			},
			{
				id: 'telegram',
				name: 'Telegram',
				enabled: false,
				color: '#0088cc'
			},
			{
				id: 'whatsapp',
				name: 'WhatsApp',
				enabled: true,
				color: '#25D366'
			},
			{
				id: 'bluesky',
				name: 'Bluesky',
				enabled: false,
				color: '#1DA1F2'
			},
			{
				id: 'email',
				name: 'Email',
				enabled: true,
				color: '#333333'
			},
			{
				id: 'copy-link',
				name: 'Copy Link',
				enabled: true,
				color: '#333333'
			},
			{
				id: 'print',
				name: 'Print',
				enabled: false,
				color: '#333333'
			}
		],
	},
};

/**
 * Register: WPZOOM Social Sharing Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param {string} name     Block name.
 * @param {Object} settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType( 'wpzoom-blocks/social-sharing', {
	$schema:      "https://json.schemastore.org/block.json",
	apiVersion: 3,
	title: __( 'Social Sharing Buttons', 'social-icons-widget-by-wpzoom' ), // Block title.
	description: __(
		'Add social sharing buttons to allow visitors to share your content.',
		'social-icons-widget-by-wpzoom'
	),
	icon: {
		foreground: '#274474',
		src: blockIcon,
	},
	category: 'wpzoom-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'Social Sharing', 'social-icons-widget-by-wpzoom' ),
		__( 'Share', 'social-icons-widget-by-wpzoom' ),
		__( 'Buttons', 'social-icons-widget-by-wpzoom' ),
		__( 'Social Media', 'social-icons-widget-by-wpzoom' ),
	],
	attributes,
	styles: [
		{
			name: 'default',
			label: __( 'Default (Circle)', 'social-icons-widget-by-wpzoom' ),
			isDefault: true,
		},
		{
			name: 'filled',
			label: __( 'Square / Filled', 'social-icons-widget-by-wpzoom' ),
		},
		{
			name: 'rounded',
			label: __( 'Rounded / Filled', 'social-icons-widget-by-wpzoom' ),
		},
		{
			name: 'outlined-pill',
			label: __( 'Outlined / Pill', 'social-icons-widget-by-wpzoom' ),
		},
		{
			name: 'outlined-square',
			label: __( 'Outlined / Square', 'social-icons-widget-by-wpzoom' ),
		},
		{
			name: 'minimal',
			label: __( 'Minimal', 'social-icons-widget-by-wpzoom' ),
		},
		{
			name: 'one-tone',
			label: __( 'One Tone', 'social-icons-widget-by-wpzoom' ),
		},
	],
	supports: {
		align: true,
		html: false,
		spacing: {
			margin: true,
			padding: true,
		},
		typography: {
			fontSize: true,
			lineHeight: true,
		},
	},
	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 * @param {Object} props Props.
	 * @return {Mixed} JSX Component.
	 */
	edit: Edit,

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 * @param {Object} props Props.
	 * @return {Mixed} JSX Frontend HTML.
	 */
	save: Save,

	deprecated: [ savedMarkupDeprecation( attributes ) ],
} ); 