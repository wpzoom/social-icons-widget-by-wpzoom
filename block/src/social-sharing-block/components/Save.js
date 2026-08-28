/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import SocialIcons from './SocialIcons';

/**
 * Get platform data based on ID
 * @param {string} id Platform ID
 * @returns {object} Platform data
 */
const getPlatformData = (id, xUsername = '') => {
	switch (id) {
		case 'facebook':
			return { 
				color: '#0866FF',
				hoverColor: '#0866FF',
				shareUrl: 'https://www.facebook.com/sharer/sharer.php?u={url}&t={title}',
			};
		case 'x':
			let shareUrl = 'https://x.com/intent/tweet?url={url}&text={title}';
			// Add the via parameter if username is provided
			if (xUsername && xUsername.trim() !== '') {
				// Remove @ if it was included
				const username = xUsername.trim().replace(/^@/, '');
				shareUrl += '&via=' + username;
			}
			return { 
				color: '#000000',
				hoverColor: '#000000',
				shareUrl: shareUrl,
			};
		case 'linkedin':
			return { 
				color: '#0966c2',
				hoverColor: '#0966c2',
				shareUrl: 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
			};
		case 'pinterest':
			return { 
				color: '#E60023',
				hoverColor: '#E60023',
				shareUrl: 'https://pinterest.com/pin/create/button/?url={url}&media={featured_image}&description={title}',
			};
		case 'reddit':
			return { 
				color: '#FF4500',
				hoverColor: '#FF4500',
				shareUrl: 'https://www.reddit.com/submit?url={url}&title={title}',
			};
		case 'telegram':
			return { 
				color: '#0088cc',
				hoverColor: '#0088cc',
				shareUrl: 'https://t.me/share/url?url={url}&text={title}',
			};
		case 'whatsapp':
			return { 
				color: '#25D366',
				hoverColor: '#25D366',
				shareUrl: 'https://api.whatsapp.com/send?text={title}%20{url}',
			};
		case 'bluesky':
			return { 
				color: '#1DA1F2',
				hoverColor: '#1DA1F2',
				shareUrl: 'https://bsky.app/intent?text={title}%20{url}',
			};
		case 'email':
			return { 
				color: '#333333',
				hoverColor: '#333333',
				shareUrl: 'mailto:?subject={title}&body={url}',
			};
		case 'copy-link':
			return {
				color: '#333333',
				hoverColor: '#333333',
				shareUrl: '#copy-link',
			};
		case 'print':
			return {
				color: '#333333',
				hoverColor: '#333333',
				shareUrl: '#print',
			};
		default:
			return {
				color: '#333333',
				hoverColor: '#333333',
				shareUrl: '#',
			};
	}
};

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
// NOTE: this block is dynamic — init.php renders it through
// wpzoom_social_sharing_block_render_callback(), so the markup below is only what
// gets serialized into post_content and never what the frontend shows. It is kept
// byte-for-byte as-is on purpose: any change here invalidates every already-saved
// block. Frontend fixes belong in the render callback, editor fixes in Edit.js.
export default function Save({ attributes }) {
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
		xUsername,
	} = attributes;

	const blockProps = useBlockProps.save({
		className: classnames({
			[`align-${align}`]: align,
			'wpzoom-social-sharing-block': true,
		}),
	});

	const containerStyle = {
		textAlign: align,
	};

	// Only render enabled platforms
	const enabledPlatforms = platforms.filter(platform => platform.enabled);

	return (
		<div {...blockProps} style={containerStyle}>
			<ul className="social-sharing-icons">
				{enabledPlatforms.map((platform) => {
					const platformData = getPlatformData(platform.id, xUsername);
					const buttonStyle = {
						padding: `${paddingVertical}px ${paddingHorizontal}px`,
						margin: `${marginVertical}px ${marginHorizontal}px`,
						borderRadius: `${borderRadius}px`,
						fontSize: `${iconSize}px`,
						color: iconColor,
						backgroundColor: platformData.color,
						border: hasBorder ? '1px solid' : 'none',
					};

					const shareUrl = platform.id === 'copy-link' 
						? '#copy-link' 
						: platformData.shareUrl;

					return (
						<li key={platform.id} className="social-sharing-icon-li">
							<a 
								className={`social-sharing-icon social-sharing-icon-${platform.id}`}
								style={buttonStyle}
								href={shareUrl}
								title={platform.name}
								target="_blank"
								rel="noopener noreferrer"
								data-platform={platform.id}
								{...(platform.id === 'pinterest' ? { 'data-pin-custom': 'true' } : {})}
							>
								<SocialIcons
									id={platform.id} 
									size={iconSize} 
									color={iconColor || '#ffffff'} 
								/>
								{showLabels && (
									<span 
										className="social-sharing-icon-label"
										style={{ 
											fontSize: `${labelSize}px`,
											color: labelColor
										}}
									>
										{platform.name}
									</span>
								)}
							</a>
						</li>
					);
				})}
			</ul>
		</div>
	);
} 