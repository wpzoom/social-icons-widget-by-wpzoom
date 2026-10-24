<?php
/**
 * Class for Social Sharing Buttons
 *
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPZOOM_Social_Sharing_Buttons
 */
class WPZOOM_Social_Sharing_Buttons {

	/**
	 * Post type name
	 *
	 * @var string
	 */
	public static $post_type = 'wpzoom-sharing';

	/**
	 * The single class instance.
	 *
	 * @var $instance
	 */
	private static $instance = null;

	/**
	 * Main Instance
	 * Ensures only one instance of this class exists in memory at any one time.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register custom post type
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Add menu item
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Keep menu highlighted when editing sharing buttons
		add_filter( 'parent_file', array( $this, 'keep_menu_highlighted' ) );
		add_filter( 'submenu_file', array( $this, 'keep_submenu_highlighted' ), 10, 2 );

		// Register meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		// Save post meta
		add_action( 'save_post_' . self::$post_type, array( $this, 'save_post_meta' ) );
		
		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
		// Setup block editor
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		
		// Filter content to add sharing buttons
		add_filter( 'the_content', array( $this, 'add_sharing_buttons_to_content' ) );
	}

	/**
	 * Register the custom post type
	 */
	public function register_post_type() {
		$args = array(
			'label'               => __( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
			'description'         => __( 'Configure sharing buttons that appear automatically', 'social-icons-widget-by-wpzoom' ),
			'labels'              => array(
				'name'          => _x( 'Sharing Buttons', 'Post Type General Name', 'social-icons-widget-by-wpzoom' ),
				'singular_name' => _x( 'Sharing Buttons', 'Post Type Singular Name', 'social-icons-widget-by-wpzoom' ),
				'menu_name'     => __( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
				'all_items'     => __( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
				'add_new'       => __( 'Add New', 'social-icons-widget-by-wpzoom' ),
				'add_new_item'  => __( 'Add New Sharing Configuration', 'social-icons-widget-by-wpzoom' ),
				'edit_item'     => __( 'Edit Sharing Configuration', 'social-icons-widget-by-wpzoom' ),
				'view_item'     => __( 'View Sharing Configuration', 'social-icons-widget-by-wpzoom' ),
			),
			'supports'            => array( 'title', 'editor', 'custom-fields' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		register_post_type( self::$post_type, $args );
	}

	/**
	 * Add menu item
	 */
	public function add_menu_item() {
		// Only administrators should be able to create/manage sharing configuration.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$parent_slug = 'edit.php?post_type=wpzoom-shortcode';
		
		// Check if there's an existing configuration
		$existing_config = $this->get_sharing_config();
		
		if ( $existing_config ) {
			$edit_url = admin_url( 'post.php?post=' . $existing_config->ID . '&action=edit' );
			add_submenu_page(
				$parent_slug,
				__( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
				__( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
				'manage_options',
				$edit_url,
				'',
				5
			);
		} else {
			// No configuration exists, create one
			// Set up default block content with properly serialized block attributes.
			//
			// The labels below are intentionally left untranslated: this is serialized
			// block markup and it has to keep matching what Save.js produces for these
			// attributes, in every locale, or the editor flags the block as invalid.
			// Translation happens at render time instead — see
			// wpzoom_social_sharing_get_platform_label() in includes/social-sharing-icons.php.
			$default_block_content = '<!-- wp:wpzoom-blocks/social-sharing {"platforms":[{"id":"facebook","name":"Facebook","enabled":true,"color":"#0866FF"},{"id":"x","name":"Share on X","enabled":true,"color":"#000000"},{"id":"threads","name":"Threads","enabled":false,"color":"#000000"},{"id":"linkedin","name":"LinkedIn","enabled":true,"color":"#0966c2"},{"id":"pinterest","name":"Pinterest","enabled":false,"color":"#E60023"},{"id":"reddit","name":"Reddit","enabled":false,"color":"#FF4500"},{"id":"telegram","name":"Telegram","enabled":false,"color":"#0088cc"},{"id":"whatsapp","name":"WhatsApp","enabled":true,"color":"#25D366"},{"id":"bluesky","name":"Bluesky","enabled":false,"color":"#1DA1F2"},{"id":"email","name":"Email","enabled":true,"color":"#333333"},{"id":"copy-link","name":"Copy Link","enabled":true,"color":"#333333"}]} -->
<div class="wp-block-wpzoom-blocks-social-sharing align-none wpzoom-social-sharing-block" style="text-align:none"><ul class="social-sharing-icons"><li class="social-sharing-icon-li"><a class="social-sharing-icon social-sharing-icon-facebook" style="padding:5px 15px;margin:5px 5px;border-radius:50px;font-size:20px;color:#ffffff;background-color:#0866FF;border:none" href="https://www.facebook.com/sharer/sharer.php?u={url}&amp;t={title}" title="Facebook" target="_blank" rel="noopener noreferrer" data-platform="facebook"><svg width="20" height="20" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#ffffff" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path></svg><span class="social-sharing-icon-label" style="font-size:16px;color:inherit">Facebook</span></a></li><li class="social-sharing-icon-li"><a class="social-sharing-icon social-sharing-icon-x" style="padding:5px 15px;margin:5px 5px;border-radius:50px;font-size:20px;color:#ffffff;background-color:#000000;border:none" href="https://x.com/intent/tweet?url={url}&amp;text={title}" title="Share on X" target="_blank" rel="noopener noreferrer" data-platform="x"><svg width="20" height="20" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#ffffff" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"></path></svg><span class="social-sharing-icon-label" style="font-size:16px;color:inherit">Share on X</span></a></li><li class="social-sharing-icon-li"><a class="social-sharing-icon social-sharing-icon-linkedin" style="padding:5px 15px;margin:5px 5px;border-radius:50px;font-size:20px;color:#ffffff;background-color:#0966c2;border:none" href="https://www.linkedin.com/sharing/share-offsite/?url={url}" title="LinkedIn" target="_blank" rel="noopener noreferrer" data-platform="linkedin"><svg width="20" height="20" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#ffffff" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"></path></svg><span class="social-sharing-icon-label" style="font-size:16px;color:inherit">LinkedIn</span></a></li><li class="social-sharing-icon-li"><a class="social-sharing-icon social-sharing-icon-whatsapp" style="padding:5px 15px;margin:5px 5px;border-radius:50px;font-size:20px;color:#ffffff;background-color:#25D366;border:none" href="https://api.whatsapp.com/send?text={title}%20{url}" title="WhatsApp" target="_blank" rel="noopener noreferrer" data-platform="whatsapp"><svg width="20" height="20" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#ffffff" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg><span class="social-sharing-icon-label" style="font-size:16px;color:inherit">WhatsApp</span></a></li><li class="social-sharing-icon-li"><a class="social-sharing-icon social-sharing-icon-email" style="padding:5px 15px;margin:5px 5px;border-radius:50px;font-size:20px;color:#ffffff;background-color:#333333;border:none" href="mailto:?subject={title}&amp;body={url}" title="Email" target="_blank" rel="noopener noreferrer" data-platform="email"><svg width="20" height="20" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#ffffff" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-.4 4.25l-7.07 4.42c-.32.2-.74.2-1.06 0L4.4 8.25c-.25-.16-.4-.43-.4-.72 0-.67.73-1.07 1.3-.72L12 11l6.7-4.19c.57-.35 1.3.05 1.3.72 0 .29-.15.56-.4.72z"></path></svg><span class="social-sharing-icon-label" style="font-size:16px;color:inherit">Email</span></a></li><li class="social-sharing-icon-li"><a class="social-sharing-icon social-sharing-icon-copy-link" style="padding:5px 15px;margin:5px 5px;border-radius:50px;font-size:20px;color:#ffffff;background-color:#333333;border:none" href="#copy-link" title="Copy Link" target="_blank" rel="noopener noreferrer" data-platform="copy-link"><svg width="20" height="20" viewBox="0 0 24 24" style="width:20px;height:20px;fill:#ffffff" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"></path></svg><span class="social-sharing-icon-label" style="font-size:16px;color:inherit">Copy Link</span></a></li></ul></div>
<!-- /wp:wpzoom-blocks/social-sharing -->';

			$new_config_id = wp_insert_post(
				array(
					'post_title'   => __( 'Sharing Buttons Configuration', 'social-icons-widget-by-wpzoom' ),
					'post_status'  => 'publish',
					'post_type'    => self::$post_type,
					'post_content' => $default_block_content,
				)
			);

			if ( ! is_wp_error( $new_config_id ) ) {
				$edit_url = admin_url( 'post.php?post=' . $new_config_id . '&action=edit' );
				add_submenu_page(
					$parent_slug,
					__( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
					__( 'Sharing Buttons', 'social-icons-widget-by-wpzoom' ),
					'manage_options',
					$edit_url,
					'',
					5
				);
				
				// Set default settings
				update_post_meta( $new_config_id, '_wpzoom_sharing_position', 'bottom' );
				update_post_meta( $new_config_id, '_wpzoom_sharing_post_types', array( 'post' ) );
			}
		}
	}

	/**
	 * Keep menu highlighted when editing sharing buttons post
	 *
	 * @param string $parent_file The parent file.
	 * @return string
	 */
	public function keep_menu_highlighted( $parent_file ) {
		global $post_type, $pagenow;

		if ( 'post.php' === $pagenow && self::$post_type === $post_type ) {
			return 'edit.php?post_type=wpzoom-shortcode';
		}

		return $parent_file;
	}

	/**
	 * Keep submenu item highlighted when editing sharing buttons post
	 *
	 * @param string $submenu_file The submenu file.
	 * @param string $parent_file  The parent file.
	 * @return string
	 */
	public function keep_submenu_highlighted( $submenu_file, $parent_file ) {
		global $post, $pagenow;

		if ( 'post.php' === $pagenow && $post && self::$post_type === $post->post_type ) {
			return admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
		}

		return $submenu_file;
	}

	/**
	 * Get sharing configuration
	 * 
	 * @return WP_Post|null
	 */
	private function get_sharing_config() {
		$args = array(
			'post_type'      => self::$post_type,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		);
		
		$posts = get_posts( $args );
		
		if ( ! empty( $posts ) ) {
			return $posts[0];
		}
		
		return null;
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'wpzoom_sharing_settings',
			__( 'Display Settings', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'display_settings_meta_box' ),
			self::$post_type,
			'side',
			'high'
		);
	}

	/**
	 * Display settings meta box
	 * 
	 * @param WP_Post $post Current post object.
	 */
	public function display_settings_meta_box( $post ) {
		// Add nonce for security
		wp_nonce_field( 'wpzoom_sharing_settings_nonce', 'wpzoom_sharing_settings_nonce' );
		
		// Get current values
		$position = get_post_meta( $post->ID, '_wpzoom_sharing_position', true );
		$position = ! empty( $position ) ? $position : 'bottom';
		
		$post_types = get_post_meta( $post->ID, '_wpzoom_sharing_post_types', true );
		$post_types = is_array( $post_types ) ? $post_types : array();

		$show_on_front = get_post_meta( $post->ID, '_wpzoom_sharing_show_on_front', true );
		
		// Get all public post types
		$all_post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
		
		// Exclude some post types
		$excluded_post_types = array( 'attachment', self::$post_type );
		
		?>
		<p>
			<strong><?php esc_html_e( 'Position', 'social-icons-widget-by-wpzoom' ); ?></strong>
		</p>
		<p>
			<label>
				<input type="radio" name="wpzoom_sharing_position" value="top" <?php checked( $position, 'top' ); ?>>
				<?php esc_html_e( 'Above content', 'social-icons-widget-by-wpzoom' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="radio" name="wpzoom_sharing_position" value="bottom" <?php checked( $position, 'bottom' ); ?>>
				<?php esc_html_e( 'Below content', 'social-icons-widget-by-wpzoom' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="radio" name="wpzoom_sharing_position" value="both" <?php checked( $position, 'both' ); ?>>
				<?php esc_html_e( 'Above and below content', 'social-icons-widget-by-wpzoom' ); ?>
			</label>
		</p>
		
		<p>
			<strong><?php esc_html_e( 'Show buttons on', 'social-icons-widget-by-wpzoom' ); ?></strong>
		</p>

		<p>
			<label>
				<input type="checkbox" name="wpzoom_sharing_show_on_front" value="1" <?php checked( $show_on_front, '1' ); ?>>
				<?php esc_html_e( 'Front Page', 'social-icons-widget-by-wpzoom' ); ?>
			</label>
		</p>
		
		<?php foreach ( $all_post_types as $post_type ) : ?>
			<?php if ( ! in_array( $post_type->name, $excluded_post_types, true ) ) : ?>
				<p>
					<label>
						<input type="checkbox" name="wpzoom_sharing_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $post_types, true ), true ); ?>>
						<?php echo esc_html( $post_type->label ); ?>
					</label>
				</p>
			<?php endif; ?>
		<?php endforeach; ?>
		
		<div class="description" style="margin-top: 15px;">
			<p><?php esc_html_e( 'Configure the social sharing buttons above, then use these settings to control where they appear on your site.', 'social-icons-widget-by-wpzoom' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %s: link to the settings tab */
					esc_html__( 'Want the “Add to Preferred Sources” button for Google too? Turn it on in %s.', 'social-icons-widget-by-wpzoom' ),
					'<a href="' . esc_url( WPZOOM_Preferred_Source::settings_url() ) . '">' . esc_html__( 'Settings → Google Preferred Sources', 'social-icons-widget-by-wpzoom' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save post meta
	 * 
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_post_meta( $post_id ) {
		// Check if nonce is set and valid
		if ( ! isset( $_POST['wpzoom_sharing_settings_nonce'] ) || ! wp_verify_nonce( $_POST['wpzoom_sharing_settings_nonce'], 'wpzoom_sharing_settings_nonce' ) ) {
			return;
		}
		
		// Check if user has permissions to save data
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		// Check if not an autosave
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}
		
		// Check if not a revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		
		// Save position setting
		if ( isset( $_POST['wpzoom_sharing_position'] ) ) {
			$position = sanitize_text_field( $_POST['wpzoom_sharing_position'] );
			update_post_meta( $post_id, '_wpzoom_sharing_position', $position );
		}
		
		// Save show on front setting
		$show_on_front = isset( $_POST['wpzoom_sharing_show_on_front'] ) ? '1' : '0';
		update_post_meta( $post_id, '_wpzoom_sharing_show_on_front', $show_on_front );
		
		// Save post types setting
		if ( isset( $_POST['wpzoom_sharing_post_types'] ) && is_array( $_POST['wpzoom_sharing_post_types'] ) ) {
			$post_types = array_map( 'sanitize_text_field', $_POST['wpzoom_sharing_post_types'] );
			update_post_meta( $post_id, '_wpzoom_sharing_post_types', $post_types );
		} else {
			// If no post types are selected, save an empty array
			update_post_meta( $post_id, '_wpzoom_sharing_post_types', array() );
		}
	}

	/**
	 * Admin enqueue scripts
	 * 
	 * @param string $hook Current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		$screen = get_current_screen();
		
		if ( 'post' === $screen->base && self::$post_type === $screen->post_type ) {
			// First check if the CSS file exists
			$css_path = WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/social-sharing-admin.css';
			$css_url = WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/social-sharing-admin.css';
			
			if ( file_exists( $css_path ) ) {
				wp_enqueue_style(
					'wpzoom-social-sharing-admin',
					$css_url,
					array(),
					WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
				);
			}
			
			// Check if the JS file exists
			$js_path = WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/social-sharing-admin.js';
			$js_url = WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-sharing-admin.js';
			
			if ( file_exists( $js_path ) ) {
				wp_enqueue_script(
					'wpzoom-social-sharing-admin',
					$js_url,
					array( 'jquery' ),
					WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
					true
				);
			}
		}
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();
		
		if ( self::$post_type === $screen->post_type ) {
			wp_enqueue_script(
				'wpzoom-social-sharing-editor',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-sharing-editor.js',
				array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-i18n' ),
				WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
				true
			);

			wp_set_script_translations( 'wpzoom-social-sharing-editor', 'social-icons-widget-by-wpzoom' );
		}
	}

	/**
	 * Add sharing buttons to content
	 * 
	 * @param string $content The content of the post.
	 * @return string The modified content.
	 */
	public function add_sharing_buttons_to_content( $content ) {
		// Don't modify content in admin or if it's a feed
		if ( is_admin() || is_feed() ) {
			return $content;
		}

		// Only add sharing buttons in the main loop (not in widgets or other secondary content)
		if ( ! in_the_loop() ) {
			return $content;
		}

		// Get config
		$config = $this->get_sharing_config();
		
		if ( ! $config ) {
			return $content;
		}
		
		// Get settings
		$position   = get_post_meta( $config->ID, '_wpzoom_sharing_position', true );
		$post_types = get_post_meta( $config->ID, '_wpzoom_sharing_post_types', true );
		$show_on_front = get_post_meta( $config->ID, '_wpzoom_sharing_show_on_front', true );
		
		// The front page has its own toggle; elsewhere, the selected post types.
		if ( is_front_page() ) {
			if ( '1' !== $show_on_front ) {
				return $content;
			}
		} elseif ( empty( $post_types ) || ! is_array( $post_types ) || ! is_singular( $post_types ) ) {
			return $content;
		}

		// Get sharing buttons from the config
		$sharing_buttons = do_blocks( $config->post_content );
		$sharing_buttons = self::prevent_pinterest_sdk_hijack( $sharing_buttons );

		$top    = '';
		$bottom = '';

		// Add buttons based on position setting
		if ( 'top' === $position || 'both' === $position ) {
			$top .= '<div class="wpzoom-social-sharing-buttons-top">' . $sharing_buttons . '</div>';
		}

		if ( 'bottom' === $position || 'both' === $position ) {
			$bottom .= '<div class="wpzoom-social-sharing-buttons-bottom">' . $sharing_buttons . '</div>';
		}

		return $top . $content . $bottom;
	}

	/**
	 * Prevent Pinterest SDK from hijacking our Pinterest share buttons.
	 *
	 * The Pinterest SDK (pinit.js) scans the DOM for links containing
	 * pinterest.com/pin/create/button/ and transforms them into its own
	 * save buttons. Adding data-pin-custom="true" tells the SDK to leave
	 * our custom-styled buttons alone.
	 *
	 * @param string $html The rendered HTML containing sharing buttons.
	 * @return string The HTML with data-pin-custom attribute added to Pinterest links.
	 */
	public static function prevent_pinterest_sdk_hijack( $html ) {
		return str_replace(
			'data-platform="pinterest"',
			'data-platform="pinterest" data-pin-custom="true"',
			$html
		);
	}
}

// Initialize the class
WPZOOM_Social_Sharing_Buttons::get_instance(); 
