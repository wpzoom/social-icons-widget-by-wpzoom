/**
 * BLOCK: wpzoom-social-icons-block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */
import { assign } from 'lodash';

//  Import CSS.
import './editor.scss';
import './style.scss';

/**
 * Internal dependencies
 */
import blockIcon from './blockIcon';
import previewImage from './previewImage';
import Edit from './components/Edit';
import Save, { createSave } from './components/Save';
import iconVariation from './icon-variation';
import proDeprecations from './pro-deprecations';
import unsetLabelHover from './unset-label-hover';
import TransformToBlock from './legacy-widget-transform/transform-to-block';
import widgetAttributesTransform from './legacy-widget-transform/widget-attributes-transform';

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { registerBlockType, createBlock } from '@wordpress/blocks';
import { Fragment } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';

const parentContainer = document.getElementById(
	'customize-theme-controls'
);

/**
 * Filters registered block attributes, extending attributes to include `selectedIcons` & `showModal`.
 *
 * @param {Object} attributes Original block attributes.
 * @return {Object} Filtered block attributes.
 */
function addAttributes( attributes ) {
	if ( ! ( undefined === attributes.selectedIcons ) ) {
		const selectedIconsClone = [ ...attributes.selectedIcons ];
		selectedIconsClone.map( ( item ) => {
			item.isActive = false;
			return item;
		} );
		attributes.selectedIcons = selectedIconsClone;
		attributes.showModal = false;
	}

	return attributes;
}

function addBlockClassNameSupport( settings, name ) {
	if ( name !== 'core/heading' || name !== 'core/paragraph' ) {
		return settings;
	}

	return assign( {}, settings, {
		supports: assign( {}, settings.supports, {
			className: true,
		} ),
	} );
}

/**
 * Override the default edit UI of legacy widget to replace with grouped inner blocks.
 *
 * @param {Function} BlockEdit Original component.
 * @return {Function} Wrapped component.
 */
const withGroupedBlocks = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { attributes, name: legacyBlockName } = props;
		const { id, idBase, instance, __internalWidgetId: widgetId } = attributes;
		const widgetTypeId = id ?? idBase;

		if (
			legacyBlockName === 'core/legacy-widget' &&
			widgetTypeId === 'zoom-social-icons-widget'
		) {
			const { widgetType, hasResolvedWidgetType } = useSelect(
				( select ) => {
					return {
						widgetType: select( 'core' ).getWidgetType( widgetTypeId ),
						hasResolvedWidgetType: select(
							'core'
						).hasFinishedResolution( 'getWidgetType', [ widgetTypeId ] ),
					};
				},
				[ id, idBase ]
			);
			const blockAttributes = widgetAttributesTransform( instance.raw );

			return (
				<Fragment>
					<BlockEdit { ...props } />
					{ widgetType && hasResolvedWidgetType && (
						<TransformToBlock
							{ ...props }
							attributes={ blockAttributes }
							widgetId={ widgetId }
						/>
					) }
				</Fragment>
			);
		}

		return (
			<Fragment>
				<BlockEdit { ...props } />
			</Fragment>
		);
	},
	'withGroupedBlock'
);

addFilter(
	'blocks.registerBlockType',
	'wpzoom-blocks/social-icons/class-names/heading-paragraph-block',
	addBlockClassNameSupport
);

addFilter(
	'blocks.getBlockAttributes',
	'wpzoom-blocks/social-icons',
	addAttributes
);

/**
 * Don't display convert notice in Customizer.
 */
// if ( ! parentContainer ) {
// 	addFilter(
// 		'editor.BlockEdit',
// 		'wpzoom-blocks/social-icons/wrap-group-blocks',
// 		withGroupedBlocks
// 	);
// }

/**
 * Block attributes, shared with the deprecated versions.
 */
const attributes = {
	wasStyled: {
		type: 'boolean',
		default: false,
	},
	canvasType: {
		type: 'string',
		default: 'with-canvas',
	},
	showIconsLabel: {
		type: 'boolean',
		default: false,
	},
	showModal: {
		type: 'boolean',
		default: false,
	},
	openLinkInNewTab: {
		type: 'boolean',
		default: false,
	},
	nofollow: {
		type: 'boolean',
		default: false,
	},
	noreferrer: {
		type: 'boolean',
		default: false,
	},
	noopener: {
		type: 'boolean',
		default: false,
	},
	relme: {
		type: 'boolean',
		default: false,
	},
	iconsAlignment: {
		type: 'string',
		default: 'left',
	},
	iconsColor: {
		type: 'string',
		default: '#f1f1f1',
	},
	iconsLabelColor: {
		type: 'string',
		default: 'inherit',
	},
	iconsHoverColor: {
		type: 'string',
		default: '#f1f1f1',
	},
	iconsLabelHoverColor: {
		type: 'string',
		default: '#f1f1f1',
	},
	iconsFontSize: {
		type: 'number',
		default: 20,
	},
	iconsLabelFontSize: {
		type: 'number',
		default: 20,
	},
	iconsPaddingVertical: {
		type: 'number',
		default: 10,
	},
	iconsPaddingHorizontal: {
		type: 'number',
		default: 10,
	},
	iconsMarginVertical: {
		type: 'number',
		default: 5,
	},
	iconsMarginHorizontal: {
		type: 'number',
		default: 5,
	},
	iconsBorderRadius: {
		type: 'number',
		default: 0,
	},
	iconsBackgroundStyle: {
		type: 'string',
		default: 'round',
	},
	iconsHasBorder: {
		type: 'boolean',
		default: false,
	},
	activeIconIndex: {
		type: 'integer',
		default: 0,
	},
	defaultIcon: {
		type: 'object',
		default: {
			icon: 'facebook',
			color: '#f89406',
			hoverColor: '#f89406',
		},
	},
	selectedIcons: {
		type: 'array',
		default: [
			{
				url: 'https://facebook.com',
				icon: 'facebook',
				iconKit: 'socicon',
				color: '#0866FF',
				hoverColor: '#0866FF',
				label: 'Facebook',
				showPopover: false,
				isActive: false,
				customSvg: null,
			},
			{
				url: 'https://x.com',
				icon: 'x',
				iconKit: 'socicon',
				color: '#000000',
				hoverColor: '#000000',
				label: 'X',
				showPopover: false,
				isActive: false,
				customSvg: null,
			},
			{
				url: 'https://instagram.com',
				icon: 'instagram',
				iconKit: 'socicon',
				color: '#E4405F',
				hoverColor: '#E4405F',
				label: 'Instagram',
				showPopover: false,
				isActive: false,
				customSvg: null,
			},
		],
	},
};

/**
 * The deprecations, then each version again for markup saved without a label
 * hover color (see unset-label-hover.js).
 *
 * @param {Object} current     The current version: { attributes, save }.
 * @param {Array}  deprecations Older versions.
 * @return {Array} Deprecations.
 */
const withUnsetLabelHover = ( current, deprecations ) => [ ...deprecations, ...unsetLabelHover( [ current, ...deprecations ] ) ];

/**
 * Register: WPZOOM Social Icons Block.
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
registerBlockType( 'wpzoom-blocks/social-icons', {
	$schema:      "https://json.schemastore.org/block.json",
	apiVersion: 3,
	title: __( 'Social Icons by WPZOOM', 'social-icons-widget-by-wpzoom' ), // Block title.
	description: __(
		'Display icons with links to social media platforms.',
		'social-icons-widget-by-wpzoom'
	),
	icon: {
		foreground: '#274474',
		src: blockIcon,
	},
	example: {
		attributes: {
			cover: previewImage,
			author: 'WPZOOM',
		},
	},
	category: 'wpzoom-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'Social Icons', 'social-icons-widget-by-wpzoom' ),
		__( 'icon', 'social-icons-widget-by-wpzoom' ),
		__( 'svg', 'social-icons-widget-by-wpzoom' ),
        __( 'share', 'social-icons-widget-by-wpzoom' ),
        __( 'sharing', 'social-icons-widget-by-wpzoom' ),
		__( 'Fontawesome', 'social-icons-widget-by-wpzoom' ),
	],
	attributes,
	styles: [
		{
			name: 'with-canvas-round',
			label: __(
				'Filled circle',
				'social-icons-widget-by-wpzoom'
			),
			isDefault: true,
		},
		{
			name: 'with-canvas-rounded',
			label: __(
				'Filled rounded',
				'social-icons-widget-by-wpzoom'
			),
		},
		{
			name: 'with-canvas-squared',
			label: __(
				'Filled square',
				'social-icons-widget-by-wpzoom'
			),
		},
		{
			name: 'without-canvas',
			label: __( 'Icon only', 'social-icons-widget-by-wpzoom' ),
		},
		{
			name: 'without-canvas-with-border',
			label: __(
				'Outlined',
				'social-icons-widget-by-wpzoom'
			),
		},
		{
			name: 'with-label-canvas-rounded',
			label: __(
				'Filled with labels',
				'social-icons-widget-by-wpzoom'
			),
		},
		{
			name: 'without-canvas-with-label',
			label: __(
				'Icon with labels',
				'social-icons-widget-by-wpzoom'
			),
		},
	],
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget' ],
				transform: ( { instance } ) => {
					return createBlock(
						'wpzoom-blocks/social-icons',
						widgetAttributesTransform( instance.raw )
					);
				},
			},
		],
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

	variations: [ iconVariation ],

	deprecated: withUnsetLabelHover( { attributes, save: Save }, [
		/**
		 * Before icons could be left without a link: an icon with an empty URL
		 * was saved as a link (`<a href="">`). Same attributes; re-saved as a
		 * plain icon.
		 */
		{
			attributes,
			save: createSave( { linkless: false } ),
		},
		...proDeprecations( attributes ),
	] ),
} );
