<?php
/**
 * Class for Social Icons Settings
 *
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPZOOM_Social_Icons_Settings
 */
class WPZOOM_Social_Icons_Settings {
	/**
	 * Settings option name
	 *
	 * @var string
	 */
	public static $option_name = 'wpzoom-social-icons-widget-settings';

	/**
	 * Admin menu slug
	 *
	 * @var string
	 */
	public static $menu_slug = 'wpzoom-social-icons-widget';

	/**
	 * Settings options defaults
	 *
	 * Note: despite the "disable-" prefix, a TRUE value means the icon set
	 * IS loaded. Only Socicons (the native set) is enabled by default —
	 * every extra set costs a render-blocking stylesheet plus a preloaded
	 * font file on each page. Sites that already used the plugin before
	 * this change are migrated once to all-enabled on upgrade
	 * (see zoom_social_icons_migrate_kit_defaults()).
	 *
	 * @var array
	 */
	public static $option_defaults = array(
		'disable-widget'                         => false,
		'disable-block'                          => false,
		'disable-fonts-preloading'               => true,
		'disable-css-loading-for-academicons'    => false,
		'disable-css-loading-for-font-awesome-3' => false,
		'disable-css-loading-for-font-awesome-5' => false,
		'disable-css-loading-for-genericons'     => false,
		'disable-css-loading-for-dashicons'      => false,
		'disable-css-loading-for-socicons'       => true,
		'categories-sync'                        => true,
		'custom-icon-set'                        => array(),
	);

	/**
	 * Holds the values to be used in the fields callbacks
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Get settings for icons kits
	 *
	 * @return array
	 */
	public static function get_settings_for_icons_kits() {
		return array(
			'socicon'     => self::get_option_key( 'disable-css-loading-for-socicons' ),
			'dashicons'   => self::get_option_key( 'disable-css-loading-for-dashicons' ),
			'genericon'   => self::get_option_key( 'disable-css-loading-for-genericons' ),
			'academicons' => self::get_option_key( 'disable-css-loading-for-academicons' ),
			'fab'         => self::get_option_key( 'disable-css-loading-for-font-awesome-5' ),
			'far'         => self::get_option_key( 'disable-css-loading-for-font-awesome-5' ),
			'fas'         => self::get_option_key( 'disable-css-loading-for-font-awesome-5' ),
			'fa'          => self::get_option_key( 'disable-css-loading-for-font-awesome-3' ),
		);
	}

	/**
	 * Load scripts and styles
	 *
	 * @param string $hook The current admin page.
	 * @return void
	 */
	public function enqueue( $hook ) {

		if ( $this->get_hook_name() === $hook ) {
			wp_enqueue_style(
				'zoom-social-icons-settings-page',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/social-icons-settings-page.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/social-icons-settings-page.css' )
			);

			wp_enqueue_style(
				'wpzoom-admin-upsell',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/admin-upsell.css',
				array(),
				WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
			);

			wp_enqueue_script(
				'zoom-social-icons-settings-page',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-icons-settings-page.js',
				array( 'jquery', 'jquery-ui-tabs' ),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/social-icons-settings-page.js' ),
				true
			);
		}
	}

	/**
	 * Get hook name
	 *
	 * @return string
	 */
	public function get_hook_name() {

		return 'wpzoom-shortcode_page_' . self::$menu_slug;
	}

	/**
	 * Add page to admin menu
	 *
	 * @return void
	 */
	public function add_plugin_page() {

		// Remove Add New submenu item.
		remove_submenu_page( 'edit.php?post_type=wpzoom-shortcode', 'post-new.php?post_type=wpzoom-shortcode' );

		// This page will be under "Settings".
		add_submenu_page(
			'edit.php?post_type=wpzoom-shortcode',
			esc_html__( 'Social Icons     Page', 'social-icons-widget-by-wpzoom' ),
			esc_html__( 'Settings', 'social-icons-widget-by-wpzoom' ),
			'manage_options',
			self::$menu_slug,
			array( $this, 'create_admin_page' ),
			10
		);
	}

	/**
	 * Get option name by passed parameter $key
	 *
	 * @param string $key The option key to receive options for.
	 * @return array|string
	 */
	public static function get_option_key( $key ) {
		$options = self::get_settings();

		return array_key_exists( $key, $options ) ? $options[ $key ] : self::$option_defaults[ $key ];
	}

	/**
	 * Options page callback
	 *
	 * @return void
	 */
	public function create_admin_page() {
		// Set class property.
		$this->options = self::get_settings();

		?>
<div class="wrap zoom-social-icons-settings">
	<h1><?php esc_html_e( 'Social Icons & Sharing Buttons Settings', 'social-icons-widget-by-wpzoom' ); ?></h1>

	<div class="wpzoom-social-icons-settings-inner">
		<div class="wp-filter">
			<ul class="filter-links">
				<li>
					<a href="#font-styles"><?php esc_html_e( 'Icon Sets', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>

				<li>
					<a href="#font-preload"><?php esc_html_e( 'Optimization', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<li>
					<a href="#general-tab"><?php esc_html_e( 'Misc.', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
			</ul>
		</div>

		<form method="post" action="options.php">

			<div id="font-styles" class="tab">
				<?php
						settings_fields( 'wpzoom-social-icons-widget-settings-group-font-styles' );
						do_settings_sections( 'wpzoom-social-icons-widget-settings-group-font-styles' );
				?>
			</div>

			<div id="font-preload" class="tab">
				<?php
						settings_fields( 'wpzoom-social-icons-widget-settings-group-font-preload' );
						do_settings_sections( 'wpzoom-social-icons-widget-settings-group-font-preload' );
				?>
			</div>

			<div id="general-tab" class="tab">
				<?php
						settings_fields( 'wpzoom-social-icons-widget-settings-group-general' );
						do_settings_sections( 'wpzoom-social-icons-widget-settings-group-general' );
				?>
			</div>

			<?php submit_button(); ?>
		</form>
	</div>
</div>

		<?php
	}

	/**
	 * Get all social icons settings
	 *
	 * @return array
	 */
	public static function get_settings() {
		return get_option( self::$option_name, self::$option_defaults );
	}

	/**
	 * Register settings, add sections and settings
	 *
	 * @return void
	 */
	public function page_init() {
		/**
		 * Register settings for General tab.
		 */
		register_setting(
			'wpzoom-social-icons-widget-settings-group-general',
			self::$option_name, // Option name.
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::$option_defaults,
			)
		);

		/**
		 * Register settings for Font Preload tab.
		 */
		register_setting(
			'wpzoom-social-icons-widget-settings-group-font-preload',
			self::$option_name, // Option name.
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::$option_defaults,
			)
		);

		/**
		 * Register settings for Font Styles tab.
		 */
		register_setting(
			'wpzoom-social-icons-widget-settings-group-font-styles',
			self::$option_name, // Option name.
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::$option_defaults,
			)
		);

		/**
		 * General tab section and settings.
		 */
		add_settings_section(
			'wpzoom-social-icons-widget-settings-general',
			false, // Title.
			'__return_false', // Callback.
			'wpzoom-social-icons-widget-settings-group-general'
		);

		add_settings_field(
			'wpzoom-disable-social-icons-widget-checkbox',
			__( 'Social Icons Widget', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_widget_checkbox' ),
			'wpzoom-social-icons-widget-settings-group-general',
			'wpzoom-social-icons-widget-settings-general'
		);

		add_settings_field(
			'wpzoom-disable-social-icons-block-checkbox',
			__( 'Social Icons Block', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_block_checkbox' ),
			'wpzoom-social-icons-widget-settings-group-general',
			'wpzoom-social-icons-widget-settings-general'
		);

		/**
		 * Font Preloader tab section and settings.
		 */
		add_settings_section(
			'wpzoom-social-icons-widget-settings-font-preload',
			false,
			'__return_false',
			'wpzoom-social-icons-widget-settings-group-font-preload'
		);

		add_settings_field(
			'wpzoom-enable-social-icons-fonts-preloader',
			__( 'Preload Fonts', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_fonts_preloading' ),
			'wpzoom-social-icons-widget-settings-group-font-preload',
			'wpzoom-social-icons-widget-settings-font-preload'
		);

		/**
		 * Font Styles tab section and settings.
		 */
		add_settings_section(
			'wpzoom-social-icons-widget-settings-font-styles',
			false, // Title.
			'__return_false',
			'wpzoom-social-icons-widget-settings-group-font-styles'
		);

		add_settings_field(
			'wpzoom-disable-css-loading-for-academicons',
			__( 'Academicons', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_css_loading_for_academicons' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);

		add_settings_field(
			'wpzoom-disable-css-loading-for-font-awesome-3',
			__( 'Font Awesome 3', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_css_loading_for_font_awesome_3' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);

		add_settings_field(
			'wpzoom-disable-css-loading-for-font-awesome-5',
			__( 'Font Awesome 5', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_css_loading_for_font_awesome_5' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);

		add_settings_field(
			'wpzoom-disable-css-loading-for-font-genericons',
			__( 'Genericons', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_css_loading_for_genericons' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);

		add_settings_field(
			'wpzoom-disable-css-loading-for-font-dashicons',
			__( 'Dashicons', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_css_loading_for_dashicons' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);

		add_settings_field(
			'wpzoom-disable-css-loading-for-font-socicons',
			__( 'Socicons', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_disable_css_loading_for_socicons' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);

		add_settings_field(
			'wpzoom-categories-sync',
			__( 'Sync Icon Sets', 'social-icons-widget-by-wpzoom' ),
			array( $this, 'field_categories_sync' ),
			'wpzoom-social-icons-widget-settings-group-font-styles',
			'wpzoom-social-icons-widget-settings-font-styles'
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['disable-widget'] ) ) {
			$new_input['disable-widget'] = wp_validate_boolean( $input['disable-widget'] );
		}

		if ( isset( $input['disable-block'] ) ) {
			$new_input['disable-block'] = wp_validate_boolean( $input['disable-block'] );
		}

		if ( isset( $input['disable-fonts-preloading'] ) ) {
			$new_input['disable-fonts-preloading'] = wp_validate_boolean( $input['disable-fonts-preloading'] );
		}

		if ( isset( $input['disable-css-loading-for-academicons'] ) ) {
			$new_input['disable-css-loading-for-academicons'] = wp_validate_boolean( $input['disable-css-loading-for-academicons'] );
		}

		if ( isset( $input['disable-css-loading-for-font-awesome-3'] ) ) {
			$new_input['disable-css-loading-for-font-awesome-3'] = wp_validate_boolean( $input['disable-css-loading-for-font-awesome-3'] );
		}

		if ( isset( $input['disable-css-loading-for-font-awesome-5'] ) ) {
			$new_input['disable-css-loading-for-font-awesome-5'] = wp_validate_boolean( $input['disable-css-loading-for-font-awesome-5'] );
		}

		if ( isset( $input['disable-css-loading-for-genericons'] ) ) {
			$new_input['disable-css-loading-for-genericons'] = wp_validate_boolean( $input['disable-css-loading-for-genericons'] );
		}

		if ( isset( $input['disable-css-loading-for-dashicons'] ) ) {
			$new_input['disable-css-loading-for-dashicons'] = wp_validate_boolean( $input['disable-css-loading-for-dashicons'] );
		}

		if ( isset( $input['disable-css-loading-for-socicons'] ) ) {
			$new_input['disable-css-loading-for-socicons'] = wp_validate_boolean( $input['disable-css-loading-for-socicons'] );
		}

		if ( isset( $input['categories-sync'] ) ) {
			$new_input['categories-sync'] = wp_validate_boolean( $input['categories-sync'] );
		}

		return $new_input;
	}

	/**
	 * Render Disable Widget checkbox in settings page.
	 */
	public function field_disable_widget_checkbox() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-widget]" value="0" />
        	<input type="checkbox" id="disable-widget" name="wpzoom-social-icons-widget-settings[disable-widget]" value="1" <?php checked( self::get_option_key( 'disable-widget' ), 1 ); ?> />
        		<?php esc_html_e( 'Disable', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Social Icons Widget module functionality, by default enabled.', 'social-icons-widget-by-wpzoom' ); ?></span>

		<?php
	}

	/**
	 * Render Disable Block checkbox in settings page.
	 */
	public function field_disable_block_checkbox() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-block]" value="0" />
        	<input type="checkbox" id="disable-block" name="wpzoom-social-icons-widget-settings[disable-block]" value="1" <?php checked( self::get_option_key( 'disable-block' ), 1 ); ?> />
        		<?php esc_html_e( 'Disable', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Social Icons Block module functionality, by default enabled.', 'social-icons-widget-by-wpzoom' ); ?></span>

		<?php
	}

	/**
	 * Render Fonts Preloading checkbox in settings page.
	 */
	public function field_disable_fonts_preloading() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-fonts-preloading]" value="0" />
        	<input type="checkbox" id="disable-fonts-preloading" name="wpzoom-social-icons-widget-settings[disable-fonts-preloading]" value="1" <?php checked( self::get_option_key( 'disable-fonts-preloading' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Preload web fonts using rel="preload" to remove any flash of unstyled text and improve the PageSpeed score.', 'social-icons-widget-by-wpzoom' ); ?></span>
		<?php
	}

	/**
	 * Render Academicons CSS Loading checkbox in settings page.
	 */
	public function field_disable_css_loading_for_academicons() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-academicons]" value="0" />
        	<input type="checkbox" id="disable-css-loading-for-academicons" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-academicons]" value="1" <?php checked( self::get_option_key( 'disable-css-loading-for-academicons' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable this font', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
		<?php
	}

	/**
	 * Render Font Awesome 3 CSS Loading checkbox in settings page.
	 */
	public function field_disable_css_loading_for_font_awesome_3() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-font-awesome-3]" value="0" />
        	<input type="checkbox" id="disable-css-loading-for-font-awesome-3" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-font-awesome-3]" value="1" <?php checked( self::get_option_key( 'disable-css-loading-for-font-awesome-3' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable this font', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Font Awesome 3 is loaded only in the Social Icons Widget', 'social-icons-widget-by-wpzoom' ); ?></span>
		<?php
	}

	/**
	 * Render Font Awesome 5 CSS Loading checkbox in settings page.
	 */
	public function field_disable_css_loading_for_font_awesome_5() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-font-awesome-5]" value="0" />
        	<input type="checkbox" id="disable-css-loading-for-font-awesome-5" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-font-awesome-5]" value="1" <?php checked( self::get_option_key( 'disable-css-loading-for-font-awesome-5' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable this font', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Font Awesome 5 is loaded only in the Social Icons Block.', 'social-icons-widget-by-wpzoom' ); ?></span>
		<?php
	}

	/**
	 * Render Genericons CSS Loading checkbox in settings page.
	 */
	public function field_disable_css_loading_for_genericons() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-genericons]" value="0" />
        	<input type="checkbox" id="disable-css-loading-for-genericons" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-genericons]" value="1" <?php checked( self::get_option_key( 'disable-css-loading-for-genericons' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable this font', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
		<?php
	}

	/**
	 * Render Dashicons CSS Loading checkbox in settings page.
	 */
	public function field_disable_css_loading_for_dashicons() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-dashicons]" value="0" />
        	<input type="checkbox" id="disable-css-loading-for-dashicons" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-dashicons]" value="1" <?php checked( self::get_option_key( 'disable-css-loading-for-dashicons' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable this font', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
		<?php
	}

	/**
	 * Render Socicons CSS Loading checkbox in settings page.
	 */
	public function field_disable_css_loading_for_socicons() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-socicons]" value="0" />
        	<input type="checkbox" id="disable-css-loading-for-socicons" name="wpzoom-social-icons-widget-settings[disable-css-loading-for-socicons]" value="1" <?php checked( self::get_option_key( 'disable-css-loading-for-socicons' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable this font', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Main icon set', 'social-icons-widget-by-wpzoom' ); ?></span>
		<?php
	}

	/**
	 * Render categories sync checkbox in settings page.
	 */
	public function field_categories_sync() {
		?>
        <label>
        	<input type="hidden" name="wpzoom-social-icons-widget-settings[categories-sync]" value="0" />
        	<input type="checkbox" id="categories-sync" name="wpzoom-social-icons-widget-settings[categories-sync]" value="1" <?php checked( self::get_option_key( 'categories-sync' ), 1 ); ?> />
        		<?php esc_html_e( 'Enable sync', 'social-icons-widget-by-wpzoom' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Sync Icon sets with Block and Widget Settings from popup.', 'social-icons-widget-by-wpzoom' ); ?></span>
		<?php
	}


}

if ( is_admin() ) {
	$wpzoom_social_icons_settings = new WPZOOM_Social_Icons_Settings();
}
