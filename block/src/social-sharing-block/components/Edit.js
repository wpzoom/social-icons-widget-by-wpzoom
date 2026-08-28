/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import { 
	Popover, 
	Button, 
	TextControl, 
	Tooltip,
	PanelBody
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Inspector from './Inspector';
import SocialIcons from './SocialIcons';
import { getPlatformLabel } from '../utils/labels';

/**
 * Block edit function
 */
export default function Edit({ attributes, setAttributes, className, isSelected }) {
	const {
		align,
		showLabels,
		iconColor,
		iconHoverColor,
		labelColor,
		labelHoverColor,
		iconSize,
		labelSize,
		paddingVertical,
		paddingHorizontal,
		marginVertical,
		marginHorizontal,
		borderRadius,
		backgroundStyle,
		hasBorder,
		platforms,
		oneToneColor
	} = attributes;

	// State for popover
	const [editingPlatform, setEditingPlatform] = useState(null);
	const [editingLabel, setEditingLabel] = useState('');

	const blockProps = useBlockProps({
		className: classnames(className, {
			[`align-${align}`]: align,
			'wpzoom-social-sharing-block': true,
		}),
	});

	// Check styles directly from className for more reliability
	const isOneTone = className?.includes('is-style-one-tone');
	// Backup check using blockProps
	const isOneToneStyle = isOneTone || blockProps.className?.includes('is-style-one-tone');
	const isOutlinedPill = blockProps.className?.includes('is-style-outlined-pill');
	const isOutlinedSquare = blockProps.className?.includes('is-style-outlined-square');
	const isMinimal = blockProps.className?.includes('is-style-minimal');
	const isFilledSquare = blockProps.className?.includes('is-style-filled');

	// Ensure oneToneColor has a value if it's undefined
	useEffect(() => {
		if (isOneToneStyle && (!oneToneColor || oneToneColor === undefined)) {
			setAttributes({ oneToneColor: '#000000' });
		}
	}, [isOneToneStyle, oneToneColor]);

	// Migrate existing blocks to include the print platform if it's missing
	useEffect(() => {
		const hasPrintPlatform = platforms.some(platform => platform.id === 'print');

		if (!hasPrintPlatform) {
			const updatedPlatforms = [
				...platforms,
				{
					id: 'print',
					name: 'Print',
					enabled: false,
					color: '#333333'
				}
			];
			setAttributes({ platforms: updatedPlatforms });
		}
	}, []); // Run only once on mount

	// blockProps.style is where block supports put line height, a custom font
	// size and spacing. Spread it in rather than replacing it, otherwise those
	// controls do nothing in the editor.
	const containerStyle = {
		...blockProps.style,
		textAlign: align,
	};

	// A font size picked in the Typography panel lands on the wrapper, so labels
	// have to inherit it instead of hard-coding labelSize.
	const hasTypographyFontSize =
		!! attributes.fontSize || !! attributes.style?.typography?.fontSize;

	// Filter enabled platforms
	const enabledPlatforms = platforms.filter(platform => platform.enabled);

	// Update platform label
	const updatePlatformLabel = (platformId, newLabel) => {
		const updatedPlatforms = platforms.map(platform => {
			if (platform.id === platformId) {
				return {
					...platform,
					name: newLabel
				};
			}
			return platform;
		});
		
		setAttributes({ platforms: updatedPlatforms });
		setEditingPlatform(null);
	};

	return (
		<>
			<Inspector attributes={attributes} setAttributes={setAttributes} />
			<div {...blockProps} style={containerStyle}>
				{enabledPlatforms.length > 0 ? (
					<ul className="social-sharing-icons">
						{enabledPlatforms.map((platform, index) => {
							// Override borderRadius for filled-square style
							const styleSpecificBorderRadius = isFilledSquare ? 0 : borderRadius;
							
							// Set appropriate colors for outlined and minimal styles
							const iconColorValue = iconColor;
							const labelColorValue = labelColor;
							
							// Set background color based on style
							let bgColor = platform.color || '#333333';
							
							// Special handling for One Tone style
							if (isOneToneStyle) {
								// Ensure we have a valid color
								bgColor = oneToneColor || '#000000';
							} else if (isOutlinedPill || isOutlinedSquare || isMinimal) {
								bgColor = 'transparent';
							}
							
							// Handle border styling
							let borderStyle = 'none';
							const borderWidth = attributes.borderWidth || 1;
							
							if (isOutlinedPill || isOutlinedSquare) {
								// For outlined styles, use icon color for border and respect border width
								borderStyle = `${borderWidth}px solid ${iconColorValue}`;
							} else if (hasBorder) {
								const borderColor = attributes.borderColor || iconColorValue;
								borderStyle = `${borderWidth}px solid ${borderColor}`;
							}
							
							// No fontSize here on purpose: the SVG carries its own width/height,
							// and an inline font size would stop the wrapper's from cascading
							// down to the label.
							const buttonStyle = {
								padding: `${paddingVertical}px ${paddingHorizontal}px`,
								margin: `${marginVertical}px ${marginHorizontal}px`,
								borderRadius: `${styleSpecificBorderRadius}px`,
								color: iconColorValue,
								backgroundColor: bgColor,
								border: borderStyle,
							};

							// Minimal style has reduced padding
							if (isMinimal) {
								buttonStyle.padding = '5px';
							}

							// Stored names stay in English so the saved markup keeps
							// validating against Save.js — translate for display only.
							const platformLabel = getPlatformLabel(platform.id, platform.name);

							return (
								<li key={platform.id} className="social-sharing-icon-li">
									<a
										className={`social-sharing-icon social-sharing-icon-${platform.id}`}
										style={buttonStyle}
										href="#"
										title={platformLabel}
										onClick={(e) => {
											e.preventDefault();
											if (isSelected) {
												setEditingPlatform(platform.id);
												setEditingLabel(platformLabel);
											}
										}}
									>
										<SocialIcons 
											id={platform.id} 
											size={iconSize} 
											color={iconColorValue} 
										/>
										
										{showLabels && (
											<span 
												className="social-sharing-icon-label"
												style={{ 
													...( hasTypographyFontSize ? {} : { fontSize: `${labelSize}px` } ),
													color: labelColorValue
												}}
											>
												{platformLabel}
											</span>
										)}
									</a>

									{isSelected && editingPlatform === platform.id && (
										<Popover
											className="wpzoom-social-sharing-edit-label-popover"
											position="bottom center"
											onClose={() => setEditingPlatform(null)}
										>
											<div style={{ padding: '16px', minWidth: '240px' }}>
												<h3 style={{ margin: '0 0 8px 0' }}>
													{__('Edit Label', 'social-icons-widget-by-wpzoom')}
												</h3>
												<TextControl
													label={__('Label Text', 'social-icons-widget-by-wpzoom')}
													value={editingLabel}
													onChange={setEditingLabel}
												/>
												<div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '12px' }}>
													<Button 
														isSecondary
														onClick={() => setEditingPlatform(null)}
													>
														{__('Cancel', 'social-icons-widget-by-wpzoom')}
													</Button>
													<Button 
														isPrimary
														onClick={() => updatePlatformLabel(platform.id, editingLabel)}
													>
														{__('Save', 'social-icons-widget-by-wpzoom')}
													</Button>
												</div>
											</div>
										</Popover>
									)}
								</li>
							);
						})}
					</ul>
				) : (
					<p className="no-sharing-platforms-selected">
						{__('Select sharing networks in the block sidebar.', 'social-icons-widget-by-wpzoom')}
					</p>
				)}
				
				{isSelected && enabledPlatforms.length > 0 && (
					<div className="wpzoom-click-to-edit-hint" style={{ 
						textAlign: 'center', 
						fontSize: '13px', 
						color: '#757575',
						marginTop: '8px'
					}}>
						{__('Click on any icon to edit its label', 'social-icons-widget-by-wpzoom')}
					</div>
				)}
			</div>
		</>
	);
} 