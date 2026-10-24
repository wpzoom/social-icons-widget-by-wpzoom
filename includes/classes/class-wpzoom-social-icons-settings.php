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

			// For the button preview on the Google Preferred Sources tab.
			wp_enqueue_style( WPZOOM_Preferred_Source::STYLE_HANDLE );

			// For the sample icons on the Icon Sets tab.
			foreach ( self::icon_set_stylesheets() as $handle => $file ) {
				if ( 'dashicons' === $handle ) {
					wp_enqueue_style( 'dashicons' );
					continue;
				}
				wp_enqueue_style( $handle, WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/' . $file, array(), filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/' . $file ) );
			}

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
					<a href="#general-tab"><?php esc_html_e( 'Modules', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<li>
					<a href="#<?php echo esc_attr( WPZOOM_Preferred_Source::SETTINGS_TAB ); ?>"><?php esc_html_e( 'Google Preferred Sources', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
			</ul>
		</div>

		<form method="post" action="options.php">
			<?php
			// Every tab posts with this one form, under the General group.
			settings_fields( 'wpzoom-social-icons-widget-settings-group-general' );
			?>

			<div id="font-styles" class="tab">
				<?php $this->render_icon_sets_tab(); ?>
			</div>

			<div id="font-preload" class="tab">
				<?php $this->render_optimization_tab(); ?>
			</div>

			<div id="general-tab" class="tab">
				<?php $this->render_modules_tab(); ?>
			</div>

			<div id="<?php echo esc_attr( WPZOOM_Preferred_Source::SETTINGS_TAB ); ?>" class="tab">
				<?php WPZOOM_Preferred_Source::render_settings_tab(); ?>
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
	 * Icon font stylesheets, keyed by handle, for the samples on the Icon Sets tab.
	 *
	 * @return array
	 */
	public static function icon_set_stylesheets() {
		return array(
			'wpzoom-social-icons-socicon'       => 'wpzoom-socicon.css',
			'wpzoom-social-icons-font-awesome-5' => 'font-awesome-5.min.css',
			'wpzoom-social-icons-font-awesome-3' => 'font-awesome-3.min.css',
			'dashicons'                         => '',
			'wpzoom-social-icons-genericons'    => 'genericons.css',
			'wpzoom-social-icons-academicons'   => 'academicons.min.css',
		);
	}

	/**
	 * The icon sets shown on the Icon Sets tab, keyed by their setting.
	 *
	 * @return array
	 */
	private static function icon_sets() {
		return array(
			'disable-css-loading-for-socicons'       => array(
				'title'       => __( 'Socicons', 'social-icons-widget-by-wpzoom' ),
				'badge'       => __( 'Main set', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Brand icons for social networks and apps. Used by default in the block and the widget.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'socicon' ),
				'samples'     => array( 'social-icon socicon socicon-facebook', 'social-icon socicon socicon-x', 'social-icon socicon socicon-instagram', 'social-icon socicon socicon-youtube', 'social-icon socicon socicon-linkedin', 'social-icon socicon socicon-tiktok' ),
			),
			'disable-css-loading-for-font-awesome-5' => array(
				'title'       => __( 'Font Awesome 5', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Brand, solid and regular icons. Available in the Social Icons block.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'fab', 'far', 'fas' ),
				'samples'     => array( 'fab fa-facebook', 'fab fa-twitter', 'fab fa-instagram', 'fab fa-youtube', 'fab fa-github', 'fas fa-envelope' ),
			),
			'disable-css-loading-for-font-awesome-3' => array(
				'title'       => __( 'Font Awesome 3', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'The older Font Awesome set. Available only in the Social Icons widget.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'fa' ),
				'samples'     => array( 'fa fa-facebook', 'fa fa-twitter', 'fa fa-instagram', 'fa fa-youtube', 'fa fa-github', 'fa fa-envelope' ),
			),
			'disable-css-loading-for-dashicons'      => array(
				'title'       => __( 'Dashicons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'The WordPress icon font, with a few social and general icons.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'dashicons' ),
				'samples'     => array( 'dashicons dashicons-facebook', 'dashicons dashicons-twitter', 'dashicons dashicons-instagram', 'dashicons dashicons-email', 'dashicons dashicons-wordpress', 'dashicons dashicons-rss' ),
			),
			'disable-css-loading-for-genericons'     => array(
				'title'       => __( 'Genericons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Simple generic icons, such as mail, phone, RSS and location.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'genericon' ),
				'samples'     => array( 'genericon genericon-facebook', 'genericon genericon-twitter', 'genericon genericon-instagram', 'genericon genericon-mail', 'genericon genericon-github', 'genericon genericon-youtube' ),
			),
			'disable-css-loading-for-academicons'    => array(
				'title'       => __( 'Academicons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Icons for academic profiles: Google Scholar, ORCID, ResearchGate, arXiv and more.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'academicons' ),
				'samples'     => array( 'academicons academicons-google-scholar', 'academicons academicons-orcid', 'academicons academicons-researchgate', 'academicons academicons-arxiv', 'academicons academicons-academia', 'academicons academicons-pubmed' ),
			),
		);
	}

	/**
	 * Card header shared by the settings tabs.
	 *
	 * @param string $icon        Dashicon name, without the "dashicons-" prefix.
	 * @param string $title       Title.
	 * @param string $description Description.
	 */
	private function render_card_header( $icon, $title, $description ) {
		?>
		<div class="wpzoom-settings-card__header">
			<span class="wpzoom-settings-card__icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<div class="wpzoom-settings-card__text">
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * On/off switch for a boolean setting.
	 *
	 * @param string $key      Setting key.
	 * @param string $label_id ID of the element that labels the switch.
	 * @param bool   $inverted True for "disable-…" settings shown as "on = enabled".
	 */
	private function render_switch( $key, $label_id, $inverted = false ) {
		$name = self::$option_name . '[' . $key . ']';
		$on   = (bool) self::get_option_key( $key );
		if ( $inverted ) {
			$on = ! $on;
		}
		// The hidden field is what's sent when the switch is off; a checked
		// switch comes later in the form and overrides it.
		?>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $inverted ? '1' : '0'; ?>" />
		<label class="wpzoom-settings-switch">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $inverted ? '0' : '1'; ?>" aria-labelledby="<?php echo esc_attr( $label_id ); ?>" <?php checked( $on ); ?> />
			<span class="wpzoom-settings-switch__track" aria-hidden="true"></span>
		</label>
		<?php
	}

	/**
	 * A row with a title, a description and a switch.
	 *
	 * @param string $key         Setting key.
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @param bool   $inverted    See render_switch().
	 */
	private function render_switch_row( $key, $title, $description, $inverted = false ) {
		$id = 'wpzoom-setting-' . $key;
		?>
		<div class="wpzoom-settings-row">
			<div class="wpzoom-settings-row__main">
				<div class="wpzoom-settings-row__title" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></div>
				<p class="wpzoom-settings-row__description"><?php echo esc_html( $description ); ?></p>
			</div>
			<div class="wpzoom-settings-row__control">
				<?php $this->render_switch( $key, $id, $inverted ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Icon Sets tab.
	 */
	private function render_icon_sets_tab() {
		$icons = include WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'includes/icons-data.php';
		?>
		<div class="wpzoom-settings-card">
			<?php
			$this->render_card_header(
				'share',
				__( 'Icon Sets', 'social-icons-widget-by-wpzoom' ),
				__( 'Choose the icon fonts your site loads. Each set adds a stylesheet and a font file to your pages, so turn on only the sets you use.', 'social-icons-widget-by-wpzoom' )
			);
			?>

			<?php foreach ( self::icon_sets() as $key => $set ) : ?>
				<?php
				$id    = 'wpzoom-setting-' . $key;
				$count = 0;
				foreach ( $set['kits'] as $kit ) {
					$count += isset( $icons[ $kit ] ) ? count( $icons[ $kit ] ) : 0;
				}
				?>
				<div class="wpzoom-settings-row wpzoom-settings-kit">
					<div class="wpzoom-settings-kit__samples" aria-hidden="true">
						<?php foreach ( $set['samples'] as $class ) : ?>
							<i class="<?php echo esc_attr( $class ); ?>"></i>
						<?php endforeach; ?>
					</div>
					<div class="wpzoom-settings-row__main">
						<div class="wpzoom-settings-row__title" id="<?php echo esc_attr( $id ); ?>">
							<?php echo esc_html( $set['title'] ); ?>
							<?php if ( ! empty( $set['badge'] ) ) : ?>
								<span class="wpzoom-settings-badge"><?php echo esc_html( $set['badge'] ); ?></span>
							<?php endif; ?>
						</div>
						<p class="wpzoom-settings-row__description">
							<?php echo esc_html( $set['description'] ); ?>
							<span class="wpzoom-settings-row__meta">
								<?php
								/* translators: %s: number of icons */
								echo esc_html( sprintf( _n( '%s icon', '%s icons', $count, 'social-icons-widget-by-wpzoom' ), number_format_i18n( $count ) ) );
								?>
							</span>
						</p>
					</div>
					<div class="wpzoom-settings-row__control">
						<?php $this->render_switch( $key, $id ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wpzoom-settings-card">
			<?php
			$this->render_switch_row(
				'categories-sync',
				__( 'Show only enabled sets in the icon picker', 'social-icons-widget-by-wpzoom' ),
				__( 'The block and widget icon pickers list just the sets turned on above. Turn off to list every set.', 'social-icons-widget-by-wpzoom' )
			);
			?>
		</div>
		<?php
	}

	/**
	 * Optimization tab.
	 */
	private function render_optimization_tab() {
		?>
		<div class="wpzoom-settings-card">
			<?php
			$this->render_card_header(
				'performance',
				__( 'Optimization', 'social-icons-widget-by-wpzoom' ),
				__( 'Control how icon fonts are loaded on your site.', 'social-icons-widget-by-wpzoom' )
			);
			$this->render_switch_row(
				'disable-fonts-preloading',
				__( 'Preload icon fonts', 'social-icons-widget-by-wpzoom' ),
				__( 'Loads the fonts of the enabled icon sets early with rel="preload", so icons show up without a flash of empty squares and your PageSpeed score improves.', 'social-icons-widget-by-wpzoom' )
			);
			?>
		</div>
		<?php
	}

	/**
	 * Modules tab.
	 */
	private function render_modules_tab() {
		?>
		<div class="wpzoom-settings-card">
			<?php
			$this->render_card_header(
				'screenoptions',
				__( 'Modules', 'social-icons-widget-by-wpzoom' ),
				__( 'Turn off the parts of the plugin you don’t use.', 'social-icons-widget-by-wpzoom' )
			);
			$this->render_switch_row(
				'disable-block',
				__( 'Blocks', 'social-icons-widget-by-wpzoom' ),
				__( 'The Social Icons, Social Sharing Buttons and Google Preferred Source blocks in the block editor.', 'social-icons-widget-by-wpzoom' ),
				true
			);
			$this->render_switch_row(
				'disable-widget',
				__( 'Social Icons widget', 'social-icons-widget-by-wpzoom' ),
				__( 'The classic widget for widget areas and the Customizer.', 'social-icons-widget-by-wpzoom' ),
				true
			);
			?>
		</div>
		<?php
	}
}

if ( is_admin() ) {
	$wpzoom_social_icons_settings = new WPZOOM_Social_Icons_Settings();
}
