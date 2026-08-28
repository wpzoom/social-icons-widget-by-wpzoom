<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * Assets enqueued:
 * 1. style-wpzoom-social-icons.css - Frontend + Backend.
 * 2. wpzoom-social-icons.js - Backend.
 * 3. wpzoom-social-icons.css - Backend.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function wpzoom_social_icons_block_enqueue_assets() {
	$asset_file = wpzoom_social_icons_get_asset_file( 'block/dist/style-wpzoom-social-icons' );

	wp_register_style(
		'wpzoom-social-icons-block-style',
		plugins_url( 'dist/style-wpzoom-social-icons.css', dirname( __FILE__ ) ),
		$asset_file['dependencies'],
		$asset_file['version']
	);

	$asset_file = wpzoom_social_icons_get_asset_file( 'block/dist/wpzoom-social-icons' );

	// Register block editor script for backend.
	wp_register_script(
		'wpzoom-social-icons-block-js',
		plugins_url( '/dist/wpzoom-social-icons.js', dirname( __FILE__ ) ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_set_script_translations( 'wpzoom-social-icons-block-js', 'social-icons-widget-by-wpzoom' );

	// Register block editor styles for backend.
	wp_register_style(
		'wpzoom-social-icons-block-editor',
		plugins_url( 'dist/wpzoom-social-icons.css', dirname( __FILE__ ) ),
		array( 'wp-edit-blocks' ),
		$asset_file['version']
	);

	// Get sharing config ID and URL for the Auto-Display Configuration panel
	$sharing_config_id = null;
	$sharing_config_url = null;
	$is_editing_sharing_config = false;
	$sharing_posts = get_posts( array(
		'post_type'      => 'wpzoom-sharing',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	) );
	if ( ! empty( $sharing_posts ) ) {
		$sharing_config_id = $sharing_posts[0]->ID;
		$sharing_config_url = admin_url( 'post.php?post=' . $sharing_config_id . '&action=edit' );

		// Check if we're currently editing the sharing config post
		// get_current_screen() is only available after admin_init, so check if function exists
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			if ( $current_screen && $current_screen->post_type === 'wpzoom-sharing' ) {
				// Also check the post ID from the request
				$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;
				if ( $post_id === $sharing_config_id ) {
					$is_editing_sharing_config = true;
				}
			}
		}
	}

	// WP Localized globals.
	wp_localize_script(
		'wpzoom-social-icons-block-js',
		'wpzSocialIconsBlock',
		array(
			'pluginDirPath'            => plugin_dir_path( __DIR__ ),
			'pluginDirUrl'             => plugin_dir_url( __DIR__ ),
			'icons'                    => include WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'includes/icons-data.php',
			'iconKitsCategories'       => zoom_social_icons_kits_categories_list( 'block' ),
			'sharingConfigId'          => $sharing_config_id,
			'sharingConfigUrl'         => $sharing_config_url,
			'isEditingSharingConfig'   => $is_editing_sharing_config,
		)
	);

	/**
	 * Register Gutenberg block on server-side.
	 *
	 * Register the block on server-side to ensure that the block
	 * scripts and styles for both frontend and backend are
	 * enqueued when the editor loads.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
	 * @since 1.16.0
	 */
	if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'wpzoom-blocks/social-icons' ) ) {
		register_block_type(
			'wpzoom-blocks/social-icons',
			array(
				// Enqueue style-wpzoom-social-icons.css on both frontend & backend.
				'style'         => 'wpzoom-social-icons-block-style',
				// Enqueue wpzoom-social-icons.js in the editor only.
				'editor_script' => 'wpzoom-social-icons-block-js',
				// Enqueue wpzoom-social-icons.css in the editor only.
				'editor_style'  => 'wpzoom-social-icons-block-editor',
			)
		);
	}

	/**
	 * Register Social Sharing block.
	 */
	if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'wpzoom-blocks/social-sharing' ) ) {
		register_block_type(
			'wpzoom-blocks/social-sharing',
			array(
				// Enqueue style-wpzoom-social-icons.css on both frontend & backend.
				'style'         => 'wpzoom-social-icons-block-style',
				// Enqueue wpzoom-social-icons.js in the editor only.
				'editor_script' => 'wpzoom-social-icons-block-js',
				// Enqueue wpzoom-social-icons.css in the editor only.
				'editor_style'  => 'wpzoom-social-icons-block-editor',
				// Mirror the client-side supports declared in block.js. Without them
				// get_block_wrapper_attributes() below has nothing to apply, so the
				// Typography and Dimensions panels never reach the frontend.
				// `align` is left out on purpose: this block reuses the `align`
				// attribute for text alignment, and the core align support would turn
				// left/center/right into float-based alignleft/alignright classes.
				'supports'      => array(
					'html'       => false,
					'spacing'    => array(
						'margin'  => true,
						'padding' => true,
					),
					'typography' => array(
						'fontSize'   => true,
						'lineHeight' => true,
					),
				),
				'render_callback' => 'wpzoom_social_sharing_block_render_callback',
			)
		);
	}
}

// Hook: Block assets.
add_action( 'init', 'wpzoom_social_icons_block_enqueue_assets' );

/**
 * Render callback for the social sharing block.
 * 
 * @param array $attributes The block attributes.
 * @return string The block HTML.
 */
function wpzoom_social_sharing_block_render_callback( $attributes ) {
	// Get the sharing buttons configuration
	$sharing_config = get_posts(
		array(
			'post_type'      => 'wpzoom-sharing',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		)
	);

	if ( empty( $sharing_config ) ) {
		return '';
	}

	$config_id = $sharing_config[0]->ID;
	$show_on_front = get_post_meta( $config_id, '_wpzoom_sharing_show_on_front', true );

	// Skip rendering on front page if not enabled in settings
	if ( is_front_page() && $show_on_front !== '1' ) {
		return '';
	}

	// Include social sharing icons functions if not already included
	if ( ! function_exists( 'wpzoom_social_sharing_get_svg_icon' ) ) {
		require_once WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . '/includes/social-sharing-icons.php';
	}

	// Get current post URL and title
	$current_url   = get_permalink();
	$current_title = get_the_title();
	$featured_image = get_the_post_thumbnail_url();
	$xUsername = isset( $attributes['xUsername'] ) ? $attributes['xUsername'] : '';

	// Get block attributes with defaults
	$align = isset( $attributes['align'] ) ? $attributes['align'] : 'none';
	$showLabels = isset( $attributes['showLabels'] ) ? $attributes['showLabels'] : true;
	$iconColor = isset( $attributes['iconColor'] ) ? $attributes['iconColor'] : '#ffffff';
	$labelColor = isset( $attributes['labelColor'] ) ? $attributes['labelColor'] : 'inherit';
	$iconSize = isset( $attributes['iconSize'] ) ? $attributes['iconSize'] : 20;
	$labelSize = isset( $attributes['labelSize'] ) ? $attributes['labelSize'] : 16;
	$paddingVertical = isset( $attributes['paddingVertical'] ) ? $attributes['paddingVertical'] : 5;
	$paddingHorizontal = isset( $attributes['paddingHorizontal'] ) ? $attributes['paddingHorizontal'] : 15;
	$marginVertical = isset( $attributes['marginVertical'] ) ? $attributes['marginVertical'] : 5;
	$marginHorizontal = isset( $attributes['marginHorizontal'] ) ? $attributes['marginHorizontal'] : 5;
	$borderRadius = isset( $attributes['borderRadius'] ) ? $attributes['borderRadius'] : 50;
	$hasBorder = isset( $attributes['hasBorder'] ) ? $attributes['hasBorder'] : false;
	$borderWidth = isset( $attributes['borderWidth'] ) ? $attributes['borderWidth'] : 1;
	$borderColor = isset( $attributes['borderColor'] ) ? $attributes['borderColor'] : '';
	$platforms = isset( $attributes['platforms'] ) ? $attributes['platforms'] : array();
	$oneToneColor = isset( $attributes['oneToneColor'] ) ? $attributes['oneToneColor'] : '#000000';

	// Class for block
	$class_name = isset( $attributes['className'] ) ? $attributes['className'] : '';

	// A font size picked in the Typography panel is applied to the block wrapper,
	// so the per-label font size further down has to step aside and let it cascade.
	$has_typography_font_size = ! empty( $attributes['fontSize'] )
		|| ! empty( $attributes['style']['typography']['fontSize'] );
	
	// Check styles
	$is_one_tone_style = strpos($class_name, 'is-style-one-tone') !== false;
	$is_outlined_pill = strpos($class_name, 'is-style-outlined-pill') !== false;
	$is_outlined_square = strpos($class_name, 'is-style-outlined-square') !== false;
	$is_minimal = strpos($class_name, 'is-style-minimal') !== false;
	$is_filled_square = strpos($class_name, 'is-style-filled') !== false;

	// Ensure oneToneColor has a value
	if ($is_one_tone_style && empty($oneToneColor)) {
		$oneToneColor = '#000000';
	}
	
	// Don't override user-set colors regardless of style
	// The user's color settings from the block attributes should always take precedence
	
	// Start building output. This is a dynamic block, so the classes and inline
	// styles that block supports generate (font size, line height, spacing, custom
	// class name) only reach the frontend if the render callback prints them.
	$wrapper_attributes = array( 'class' => 'align-' . $align );
	if ($align !== 'none') {
		$wrapper_attributes['style'] = 'text-align:' . $align . ';';
	}
	$output = '<div ' . get_block_wrapper_attributes( $wrapper_attributes ) . '>';
	$output .= '<ul class="social-sharing-icons">';

	// Only show enabled platforms
	$enabled_platforms = array_filter( $platforms, function( $platform ) {
		return isset( $platform['enabled'] ) && $platform['enabled'];
	});

	// If no platforms are enabled, use default platforms.
	// Names are kept untranslated here — wpzoom_social_sharing_get_platform_label()
	// translates them below, the same way it does for stored platform names.
	if ( empty( $enabled_platforms ) ) {
		$enabled_platforms = array(
			array(
				'id' => 'facebook',
				'name' => 'Facebook',
				'enabled' => true,
				'color' => '#0866FF'
			),
			array(
				'id' => 'x',
				'name' => 'X',
				'enabled' => true,
				'color' => '#000000'
			),
            array(
                'id' => 'threads',
                'name' => 'Threads',
                'enabled' => true,
                'color' => '#000000'
            ),
			array(
				'id' => 'linkedin',
				'name' => 'LinkedIn',
				'enabled' => true,
				'color' => '#0966C2'
			),
			array(
				'id' => 'reddit',
				'name' => 'Reddit',
				'enabled' => true,
				'color' => '#FF4500'
			),
			array(
				'id' => 'whatsapp',
				'name' => 'WhatsApp',
				'enabled' => true,
				'color' => '#25D366'
			),
			array(
				'id' => 'email',
				'name' => 'Email',
				'enabled' => true,
				'color' => '#333333'
			),
			array(
				'id' => 'copy-link',
				'name' => 'Copy Link',
				'enabled' => true,
				'color' => '#333333'
			)
		);
	}

	// Loop through platforms
	foreach ( $enabled_platforms as $platform ) {
		// Stored labels are English (the serialized markup has to stay canonical
		// so it keeps validating against Save.js), so translate them here.
		$platform_label = wpzoom_social_sharing_get_platform_label(
			$platform['id'],
			isset( $platform['name'] ) ? $platform['name'] : ''
		);

		// Generate share URL
		$share_url = wpzoom_social_sharing_get_share_url(
			$platform['id'],
			$current_url,
			$current_title,
			$featured_image,
			$xUsername
		);
		
		// Override borderRadius for filled-square style
		$style_specific_border_radius = $is_filled_square ? 0 : $borderRadius;
		
		// Set appropriate colors based on user settings
		$icon_color_value = $iconColor;
		$label_color_value = $labelColor;
		
		// Set background color based on style
		$platform_color = isset( $platform['color'] ) ? $platform['color'] : wpzoom_social_sharing_get_platform_color( $platform['id'] );
		
		// Special handling for One Tone style
		if ($is_one_tone_style) {
			$platform_color = $oneToneColor;
		} elseif ($is_outlined_pill || $is_outlined_square || $is_minimal) {
			$platform_color = 'transparent';
		}
		
		// Set border based on style
		$border_style = '';
		$border_width_px = $borderWidth . 'px';
		
		if ($is_outlined_pill || $is_outlined_square) {
			// For outlined styles, use icon color for border and respect border width
			$border_style = 'border:' . $border_width_px . ' solid ' . $icon_color_value . ';';
		} elseif ($hasBorder) {
			$border_color = !empty($borderColor) ? $borderColor : $icon_color_value;
			$border_style = 'border:' . $border_width_px . ' solid ' . $border_color . ';';
		}
		
		// Calculate padding
		$padding_vertical = $paddingVertical;
		$padding_horizontal = $paddingHorizontal;
		if ($is_minimal) {
			$padding_vertical = 5;
			$padding_horizontal = 5;
		}
		
		// Build output for this platform
		// No font-size here on purpose: the SVG carries its own width/height, and an
		// inline font size would stop the wrapper's from cascading to the label.
		$button_style = sprintf(
			'padding:%dpx %dpx;margin:%dpx %dpx;border-radius:%dpx;color:%s;background-color:%s;%s',
			$padding_vertical,
			$padding_horizontal,
			$marginVertical,
			$marginHorizontal,
			$style_specific_border_radius,
			$icon_color_value,
			$platform_color,
			$border_style
		);
		
		$output .= '<li class="social-sharing-icon-li">';
		$output .= '<a class="social-sharing-icon social-sharing-icon-' . esc_attr( $platform['id'] ) . '" ';
		$output .= 'style="' . esc_attr( $button_style ) . '" ';
		$output .= 'href="' . esc_url( $share_url ) . '" ';
		$output .= 'title="' . esc_attr( $platform_label ) . '" ';
		
		// Don't use target="_blank" for copy-link and print
		if ( $platform['id'] !== 'copy-link' && $platform['id'] !== 'print' ) {
			$output .= 'target="_blank" rel="noopener noreferrer" ';
		}
		
		$output .= 'data-platform="' . esc_attr( $platform['id'] ) . '">';
		
		// Add SVG icon
		$output .= wpzoom_social_sharing_get_svg_icon( $platform['id'], $iconSize, $icon_color_value );
		
		if ( $showLabels ) {
			$label_style = $has_typography_font_size
				? sprintf( 'color:%s;', $label_color_value )
				: sprintf( 'font-size:%dpx;color:%s;', $labelSize, $label_color_value );
			$output .= '<span class="social-sharing-icon-label" style="' . esc_attr( $label_style ) . '">' . esc_html( $platform_label ) . '</span>';
		}
		
		$output .= '</a>';
		$output .= '</li>';
	}
	
	$output .= '</ul>';

	// Check which special platforms are enabled
	$copy_link_enabled = false;
	$print_enabled = false;
	foreach ( $enabled_platforms as $platform ) {
		if ( $platform['id'] === 'copy-link' ) {
			$copy_link_enabled = true;
		}
		if ( $platform['id'] === 'print' ) {
			$print_enabled = true;
		}
	}

	// Add JS for copy link functionality (only once per page)
	static $copy_link_script_added = false;
	if ( $copy_link_enabled && ! $copy_link_script_added ) {
		// Get success icon SVG
		$success_icon = wpzoom_social_sharing_get_success_icon(20, '#ffffff');

		$output .= '<script>
			document.addEventListener("DOMContentLoaded", function() {
				var copyLinks = document.querySelectorAll("a[data-platform=\'copy-link\']");
				copyLinks.forEach(function(link) {
					if (link.hasAttribute("data-listener-added")) return;
					link.setAttribute("data-listener-added", "true");

					link.addEventListener("click", function(e) {
						e.preventDefault();
						var tempInput = document.createElement("input");
						tempInput.value = window.location.href;
						document.body.appendChild(tempInput);
						tempInput.select();
						document.execCommand("copy");
						document.body.removeChild(tempInput);

						var originalText = this.querySelector(".social-sharing-icon-label")?.textContent || "";
						var originalTitle = this.getAttribute("title");
						var originalIcon = this.querySelector("svg").outerHTML;

						// Show success feedback
						this.setAttribute("title", "' . esc_js( __( 'Copied!', 'social-icons-widget-by-wpzoom' ) ) . '");
						this.classList.add("copied");

						if (this.querySelector(".social-sharing-icon-label")) {
							this.querySelector(".social-sharing-icon-label").textContent = "' . esc_js( __( 'Copied!', 'social-icons-widget-by-wpzoom' ) ) . '";
						} else {
							this.querySelector("svg").outerHTML = \'' . $success_icon . '\';
						}

						var self = this;
						setTimeout(function() {
							self.setAttribute("title", originalTitle);
							self.classList.remove("copied");
							if (self.querySelector(".social-sharing-icon-label")) {
								self.querySelector(".social-sharing-icon-label").textContent = originalText;
							} else {
								self.querySelector("svg").outerHTML = originalIcon;
							}
						}, 2000);
					});
				});
			});
		</script>';
		$copy_link_script_added = true;
	}

	// Add JS for print functionality (only once per page)
	static $print_script_added = false;
	if ( $print_enabled && ! $print_script_added ) {
		$output .= '<script>
			document.addEventListener("DOMContentLoaded", function() {
				var printLinks = document.querySelectorAll("a[data-platform=\'print\']");
				printLinks.forEach(function(link) {
					if (link.hasAttribute("data-listener-added")) return;
					link.setAttribute("data-listener-added", "true");

					link.addEventListener("click", function(e) {
						e.preventDefault();
						window.print();
					});
				});
			});
		</script>';
		$print_script_added = true;
	}

	$output .= '</div>';

	return $output;
}

/**
 * Add custom block category
 *
 * @since 1.0.0
 *
 * @param array $categories Array of categories for block types.
 * @return array Filters the default array of categories for block types.
 */
function wpzoom_social_icons_block_add_custom_category( $categories ) {
	if ( empty( $categories ) || ( ! empty( $categories ) && is_array( $categories ) && ! in_array( 'wpzoom-blocks', wp_list_pluck( $categories, 'slug' ) ) ) ) {
		$categories = array_merge(
			$categories,
			array(
				array(
					'slug'  => 'wpzoom-blocks',
					'title' => __( 'WPZOOM - Blocks', 'social-icons-widget-by-wpzoom' ),
				),
			)
		);
	}

	return $categories;
}

/**
 * Use hook for block categories depending by WordPress version.
 *
 * @return void
 */
function wpzoom_social_icons_block_categories() {
	global $wp_version;
	if ( version_compare( $wp_version, '5.8', '<' ) ) {
		add_filter( 'block_categories', 'wpzoom_social_icons_block_add_custom_category', 10, 2 );
	} else {
		add_filter( 'block_categories_all', 'wpzoom_social_icons_block_add_custom_category', 10, 2 );
	}
}
wpzoom_social_icons_block_categories();

/**
 * Register css and js files.
 */
function wpzoom_social_icons_block_register_secondary_assets() {

	/**
	 * Register academicons.css
	 */
	wp_register_style(
		'wpzoom-social-icons-academicons',
		WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/academicons.min.css',
		array(),
		filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/academicons.min.css' )
	);

	/**
	 * Register socicons.css
	 */
	wp_register_style(
		'wpzoom-social-icons-socicon',
		WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-socicon.css',
		array(),
		filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-socicon.css' )
	);

	/**
	 * Register font-awesome.css
	 */
	wp_register_style(
		'wpzoom-social-icons-font-awesome-5',
		WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-5.min.css',
		array(),
		filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/font-awesome-5.min.css' )
	);

	/**
	 * Register genericons.css
	 */
	wp_register_style(
		'wpzoom-social-icons-genericons',
		WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/genericons.css',
		array(),
		filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/genericons.css' )
	);
}

add_action( 'wp_enqueue_scripts', 'wpzoom_social_icons_block_register_secondary_assets' );

/**
 * Check if a block exists in widget areas (block-based widgets).
 *
 * WordPress stores block widgets in the 'widget_block' option.
 * This function parses all widget blocks to check if a specific block is used.
 *
 * @since 4.2.9
 * @param string $block_name The block name to search for.
 * @return boolean True if the block is found in any widget area.
 */
function wpzoom_has_block_in_widget_area( $block_name ) {
	// Get all block widgets from the option.
	$widget_blocks = get_option( 'widget_block', array() );

	if ( empty( $widget_blocks ) || ! is_array( $widget_blocks ) ) {
		return false;
	}

	foreach ( $widget_blocks as $widget_block ) {
		// Skip if not an array or doesn't have content.
		if ( ! is_array( $widget_block ) || empty( $widget_block['content'] ) ) {
			continue;
		}

		// Check if the block name exists in the widget content.
		if ( false !== strpos( $widget_block['content'], '<!-- wp:' . $block_name ) ) {
			return true;
		}

		// Also check for nested blocks by parsing the content.
		$blocks = parse_blocks( $widget_block['content'] );
		if ( wpzoom_search_blocks_recursive( $blocks, $block_name ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Recursively search for a block name in parsed blocks.
 *
 * @since 4.2.9
 * @param array  $blocks Array of parsed blocks.
 * @param string $block_name The block name to search for.
 * @return boolean True if block is found.
 */
function wpzoom_search_blocks_recursive( $blocks, $block_name ) {
	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) && $block['blockName'] === $block_name ) {
			return true;
		}

		// Check inner blocks recursively.
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			if ( wpzoom_search_blocks_recursive( $block['innerBlocks'], $block_name ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Check has reusable block
 *
 * @param string $block_name The block name.
 * @param int    $id The post id.
 * @return boolean
 */
function wpzoom_has_reusable_block( $block_name, $id = 0 ) {
	$id = ( ! $id ) ? get_the_ID() : $id;
	if ( $id ) {
		if ( has_block( 'block', $id ) ) {
			// Check reusable blocks.
			$content = get_post_field( 'post_content', $id );
			$blocks  = parse_blocks( $content );

			if ( ! is_array( $blocks ) || empty( $blocks ) ) {
				return false;
			}

			foreach ( $blocks as $block ) {
				if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
					if ( has_block( $block_name, $block['attrs']['ref'] ) ) {
						return true;
					}
				}
			}
		}
	}

	return false;
}

/**
 * Enqueue css and js files.
 */
function wpzoom_social_icons_block_enqueue_secondary_assets() {
	if ( wpzoom_has_reusable_block( 'wpzoom-blocks/social-icons' ) ||
		 wpzoom_has_reusable_block( 'wpzoom-blocks/social-sharing' ) ||
		 has_block( 'wpzoom-blocks/social-icons' ) ||
		 has_block( 'wpzoom-blocks/social-sharing' ) ||
		 wpzoom_has_block_in_widget_area( 'wpzoom-blocks/social-icons' ) ||
		 wpzoom_has_block_in_widget_area( 'wpzoom-blocks/social-sharing' ) ||
		 is_admin() ) {
		$disable_css_loading_socicons    = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-socicons' );
		$disable_css_loading_genericons  = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-genericons' );
		$disable_css_loading_academicons = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-academicons' );
		$disable_css_loading_fa5         = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-font-awesome-5' );
		$disable_css_loading_dashicons   = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-dashicons' );

		/**
		 * Enqueue dashicons.css
		 */

		if ( ! empty( $disable_css_loading_dashicons ) ) {
			wp_enqueue_style( 'dashicons' );
		}

		/**
		 * Enqueue academicons.css
		 */

		if ( ! empty( $disable_css_loading_academicons ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-academicons',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/academicons.min.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/academicons.min.css' )
			);
		}
		/**
		 * Enqueue socicons.css
		 */
		if ( ! empty( $disable_css_loading_socicons ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-socicon',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-socicon.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-socicon.css' )
			);
		}

		/**
		 * Enqueue font-awesome.css
		 */
		if ( ! empty( $disable_css_loading_fa5 ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-font-awesome-5',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-5.min.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/font-awesome-5.min.css' )
			);
		}

		/**
		 * Enqueue genericons.css
		 */
		if ( ! empty( $disable_css_loading_genericons ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-genericons',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/genericons.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/genericons.css' )
			);
		}
	}
}

add_action( 'enqueue_block_assets', 'wpzoom_social_icons_block_enqueue_secondary_assets' );

if ( ! function_exists( 'wpzoom_social_icons_get_asset_file' ) ) {
	/**
	 * Loads the asset file for the given script or style.
	 * Returns a default if the asset file is not found.
	 *
	 * @since 4.2.0
	 * @param string $filepath The name of the file without the extension.
	 *
	 * @return array The asset file contents.
	 */
	function wpzoom_social_icons_get_asset_file( $filepath ) {
		$asset_path = WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . $filepath . '.asset.php';

		return file_exists( $asset_path )
			? include $asset_path
			: array(
				'dependencies' => array(),
				'version'      => WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
			);
	}
}
