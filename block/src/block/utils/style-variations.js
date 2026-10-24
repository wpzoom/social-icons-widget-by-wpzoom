/**
 * Per-style attribute presets for the Social Icons block, and the attributes a
 * style switch applies. Shared by the block (when its style changes) and the
 * visual style picker (to preview each style) so a preview always shows exactly
 * what choosing that style will do.
 */
import { get, isEmpty, omit } from 'lodash';

export const STYLE_VARIATIONS = {
	'with-label-canvas-rounded': {
		canvasType: 'with-label-canvas',
		showIconsLabel: true,
		iconsColor: null,
		iconsLabelColor: '#fff',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#fff',
		iconsFontSize: 18,
		iconsLabelFontSize: 15,
		iconsPaddingHorizontal: 5,
		iconsPaddingVertical: 5,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 50,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: null,
			hoverColor: null,
		},
	},
	'with-canvas-rounded': {
		canvasType: 'with-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 5,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'with-canvas-round': {
		canvasType: 'with-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 50,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'with-canvas-squared': {
		canvasType: 'with-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsBorderRadius: 0,
		iconsHasBorder: true,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'without-canvas': {
		canvasType: 'without-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: '#2e3131',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#2e3131',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: false,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: '#0866FF',
			hoverColor: '#0866FF',
		},
		selectedIcons: [
			{ icon: 'facebook', color: '#0866FF', hoverColor: '#0866FF' },
			{ icon: 'x', color: '#000000', hoverColor: '#000000' },
			{ icon: 'instagram', color: '#E4405F', hoverColor: '#E4405F' },
		],
	},
	'without-canvas-with-border': {
		canvasType: 'without-canvas',
		showIconsLabel: false,
		iconsColor: null,
		iconsLabelColor: 'inherit',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#f1f1f1',
		iconsFontSize: 18,
		iconsLabelFontSize: 16,
		iconsPaddingHorizontal: 6,
		iconsPaddingVertical: 6,
		iconsMarginHorizontal: 5,
		iconsMarginVertical: 5,
		iconsHasBorder: true,
		iconsBorderRadius: 0,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: null,
			hoverColor: null,
		},
	},
	'without-canvas-with-label': {
		canvasType: 'without-canvas',
		showIconsLabel: true,
		iconsColor: null,
		iconsLabelColor: 'inherit',
		iconsHoverColor: null,
		iconsLabelHoverColor: '#f1f1f1',
		iconsFontSize: 40,
		iconsLabelFontSize: 15,
		iconsPaddingHorizontal: 10,
		iconsPaddingVertical: 10,
		iconsMarginHorizontal: 0,
		iconsMarginVertical: 0,
		iconsHasBorder: false,
		wasStyled: true,
		defaultIcon: {
			icon: 'facebook',
			color: null,
			hoverColor: null,
		},
	},
};

export function getStyleVariations( styleType, activeStyleName ) {
	return get( STYLE_VARIATIONS, styleType, false )
		? get( STYLE_VARIATIONS, styleType, false )
		: get( STYLE_VARIATIONS, activeStyleName );
}

/**
 * Attributes applied when the block switches to `styleName`: the style's
 * presets, and icon colors adjusted the way the block always has (missing
 * colors take the style's default, styles with a fixed icon color force it).
 *
 * @param {Object} attributes Current block attributes.
 * @param {string} styleName  Style to switch to.
 * @return {?Object} Attributes to set, including `selectedIcons`, or null.
 */
export function getStyleSwitchAttributes( attributes, styleName ) {
	const variation = getStyleVariations( styleName, styleName );
	if ( isEmpty( variation ) ) {
		return null;
	}

	const icons = JSON.parse( JSON.stringify( attributes.selectedIcons || [] ) );

	if ( ! isEmpty( variation.selectedIcons ) ) {
		icons.forEach( ( item ) => {
			if ( isEmpty( item.color ) ) {
				item.color = variation.defaultIcon.color;
			}
			if ( isEmpty( item.hoverColor ) ) {
				item.hoverColor = variation.defaultIcon.hoverColor;
			}
		} );
	}
	if ( ! isEmpty( variation.iconsColor ) ) {
		icons.forEach( ( item ) => {
			item.color = variation.iconsColor;
		} );
	}
	if ( ! isEmpty( variation.iconsHoverColor ) ) {
		icons.forEach( ( item ) => {
			item.hoverColor = variation.iconsHoverColor;
		} );
	}

	return { ...omit( variation, [ 'selectedIcons' ] ), selectedIcons: icons };
}
