import Helper from '../utils/helper';
import classnames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Builds the save component.
 *
 * @param {Object}  options          Options.
 * @param {boolean} options.linkless Save an icon with no URL without a link.
 *                                   deprecated.js passes false: blocks saved
 *                                   before that wrapped every icon in a link.
 * @param {boolean} options.pro      Save as WPZOOM Connect Pro does, so blocks
 *                                   saved by PRO are recognised here (see
 *                                   pro-deprecations.js): every icon span is
 *                                   `social-icon socicon socicon-<name>` with
 *                                   its SVG inside, taken from the saved
 *                                   markup (attributes.proIconsHtml), and the
 *                                   labels style has no class of its own.
 */
export function createSave( { linkless = true, pro = false } = {} ) {
	return function Save( { attributes } ) {
		const {
			selectedIcons,
			showIconsLabel,
			openLinkInNewTab,
			nofollow,
			noreferrer,
			noopener,
			relme,
			iconsFontSize,
			iconsPaddingHorizontal,
			iconsPaddingVertical,
			iconsMarginHorizontal,
			iconsMarginVertical,
			iconsBorderRadius,
			iconsLabelFontSize,
			iconsLabelColor,
			iconsLabelHoverColor,
			iconsAlignment,
		} = attributes;

		let extraClassName = attributes.className || '';
		if ( Helper.getBlockStyle( extraClassName ) == null ) {
			extraClassName = classnames( extraClassName, 'is-style-with-canvas-round' );
		}
		if ( showIconsLabel && ! pro ) {
			extraClassName = classnames( extraClassName, 'show-icon-labels-style' );
		}

		const blockProps = useBlockProps.save( { className: extraClassName } );

		const relAttr = [];
		if ( nofollow ) relAttr.push( 'nofollow' );
		if ( noreferrer ) relAttr.push( 'noreferrer' );
		if ( noopener ) relAttr.push( 'noopener' );
		if ( relme ) relAttr.push( 'me' );
		if ( openLinkInNewTab ) relAttr.splice( 0, relAttr.length, 'noopener' );

		const target = openLinkInNewTab ? '_blank' : undefined;

		const iconsAlignmentStyles = {
			left: 'flex-start',
			right: 'flex-end',
			center: 'center',
		};

		// A just-added icon with nothing chosen yet stays in the editor only.
		const IconsList = selectedIcons.filter( ( list ) => ! Helper.isBlankIcon( list ) ).map( ( list, key ) => {
			const showIconsLabelEl = showIconsLabel ? (
				<span className={ classnames( 'icon-label' ) }>{ list.label }</span>
			) : '';

			const isCustomSvg = list.iconKit === 'svg' && list.customSvg;

			let iconContent;
			if ( pro ) {
				const proIcon = ( attributes.proIconsHtml || [] )[ key ];
				iconContent = (
					<span
						className={
							isCustomSvg
								? 'social-icon social-icon-svg'
								: `social-icon socicon socicon-${ list.icon }`
						}
						dangerouslySetInnerHTML={ { __html: ( proIcon && proIcon.html ) || '' } }
					/>
				);
			} else if ( isCustomSvg ) {
				iconContent = (
					<span
						className={ classnames( 'social-icon', 'social-icon-svg' ) }
						dangerouslySetInnerHTML={ { __html: list.customSvg } }
					/>
				);
			} else {
				iconContent = (
					<span
						className={ classnames(
							Helper.getIconClassList( list.iconKit, list.icon )
						) }
					/>
				);
			}

			const itemStyle = {
				'--wpz-social-icons-block-item-color': list.color,
				'--wpz-social-icons-block-item-color-hover': list.hoverColor,
			};

			// No URL: a plain icon, not a link to the page it is on.
			if ( linkless && ! String( list.url || '' ).trim() ) {
				return (
					<span
						key={ key }
						className="social-icon-link social-icon-nolink"
						title={ list.label || undefined }
						style={ itemStyle }
					>
						{ iconContent }
						{ showIconsLabelEl }
					</span>
				);
			}

			return (
				<a
					key={ key }
					href={ list.url }
					className="social-icon-link"
					target={ target }
					rel={ relAttr.length ? relAttr.join( ' ' ) : undefined }
					title={ list.label }
					style={ itemStyle }
				>
					{ iconContent }
					{ showIconsLabelEl }
				</a>
			);
		} );

		return (
			<div
				{ ...blockProps }
				style={ {
					...blockProps.style,
					'--wpz-social-icons-block-item-font-size': Helper.addPixelsPipe( iconsFontSize ),
					'--wpz-social-icons-block-item-padding-horizontal': Helper.addPixelsPipe( iconsPaddingHorizontal ),
					'--wpz-social-icons-block-item-padding-vertical': Helper.addPixelsPipe( iconsPaddingVertical ),
					'--wpz-social-icons-block-item-margin-horizontal': Helper.addPixelsPipe( iconsMarginHorizontal ),
					'--wpz-social-icons-block-item-margin-vertical': Helper.addPixelsPipe( iconsMarginVertical ),
					'--wpz-social-icons-block-item-border-radius': Helper.addPixelsPipe( iconsBorderRadius ),
					'--wpz-social-icons-block-label-font-size': Helper.addPixelsPipe( iconsLabelFontSize ),
					'--wpz-social-icons-block-label-color': iconsLabelColor,
					'--wpz-social-icons-block-label-color-hover': iconsLabelHoverColor,
					'--wpz-social-icons-alignment': iconsAlignmentStyles[ iconsAlignment ],
				} }
			>
				{ IconsList }
			</div>
		);
	};
}

export default createSave();
