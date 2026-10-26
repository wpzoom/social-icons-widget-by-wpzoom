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
		return WPZOOM_Social_Icons_Sets::enabled_kits( false );
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

			// For the sample icons on the Icon Sets tab, unless they're drawn as SVG.
			foreach ( WPZOOM_Social_Icons_SVG::use_svg() ? array() : self::icon_set_stylesheets() as $handle => $file ) {
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
			esc_html__( 'WPZOOM Connect Settings', 'social-icons-widget-by-wpzoom' ),
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
	 * @return array|string|bool|int|null Null for a key this plugin doesn't define.
	 */
	public static function get_option_key( $key ) {
		$options = self::get_settings();

		if ( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}
		// A key this plugin doesn't define: while both plugins are active (for
		// one request, until PRO deactivates free) the other plugin's code may
		// ask this class for its own settings, e.g. PRO's Pinterest ones.
		return array_key_exists( $key, self::$option_defaults ) ? self::$option_defaults[ $key ] : null;
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
<div class="wrap zoom-social-icons-settings wpzc wpzc-settings">
	<h1 class="screen-reader-text"><?php esc_html_e( 'WPZOOM Connect Settings', 'social-icons-widget-by-wpzoom' ); ?></h1>

	<div class="wpzc-layout">
	<?php WPZOOM_Connect_Overview::render_sidebar( 'settings' ); ?>

	<div class="wpzc-main">
	<?php settings_errors(); ?>

	<?php // The tab list is kept for jQuery UI Tabs; the sidebar's items switch the tabs. ?>
	<div class="wpzoom-social-icons-settings-inner">
		<div class="wp-filter">
			<ul class="filter-links">
				<li>
					<a href="#social-icons-tab"><?php esc_html_e( 'Social Icons', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<li>
					<a href="#share-buttons-tab"><?php esc_html_e( 'Share Buttons', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<li>
					<a href="#preferred-source-tab"><?php esc_html_e( 'Google Preferred Sources', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<li>
					<a href="#pinterest-tab"><?php esc_html_e( 'Pinterest', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<?php if ( ! WPZOOM_Social_Icons_SVG::use_svg() ) : ?>
				<li>
					<a href="#font-preload"><?php esc_html_e( 'Optimization', 'social-icons-widget-by-wpzoom' ); ?></a>
				</li>
				<?php endif; ?>
			</ul>
		</div>

		<form method="post" action="options.php">
			<?php
			// Every tab posts with this one form, under the General group.
			settings_fields( 'wpzoom-social-icons-widget-settings-group-general' );
			?>

			<?php /* Grouped by module, as on the Overview screen. Each card's header is its heading. Section ids keep old links (#font-styles, #preferred-source…) working. */ ?>
			<div id="social-icons-tab" class="tab">
				<div id="font-styles" class="wpzoom-settings-section">
					<?php $this->render_icon_sets_tab(); ?>
				</div>
				<div id="saved-icon-sets" class="wpzoom-settings-section">
					<?php WPZOOM_Connect_Overview::render_icon_sets_card(); ?>
				</div>
				<div id="custom-icons" class="wpzoom-settings-section">
					<?php $this->render_custom_icons_tab(); ?>
				</div>
			</div>

			<div id="share-buttons-tab" class="tab">
				<div id="share-buttons" class="wpzoom-settings-section">
					<?php WPZOOM_Connect_Overview::render_share_buttons_card(); ?>
				</div>
			</div>

						<div id="preferred-source-tab" class="tab">
				<div id="<?php echo esc_attr( WPZOOM_Preferred_Source::SETTINGS_TAB ); ?>" class="wpzoom-settings-section">
					<?php WPZOOM_Preferred_Source::render_settings_tab(); ?>
				</div>
			</div>

			<div id="pinterest-tab" class="tab">
				<div id="pinterest" class="wpzoom-settings-section">
					<?php $this->render_pinterest_tab(); ?>
				</div>
			</div>

			<?php if ( ! WPZOOM_Social_Icons_SVG::use_svg() ) : ?>
			<div id="font-preload" class="tab">
				<?php $this->render_optimization_tab(); ?>
			</div>
			<?php endif; ?>

			<?php submit_button(); ?>
		</form>
	</div>
	</div>
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
		// Start from the saved settings: the blocks and widget switches are saved
		// from the Overview screen, so this form doesn't post them.
		$new_input = self::get_settings();
		$new_input = is_array( $new_input ) ? $new_input : array();
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

		return WPZOOM_Social_Icons_Sets::sanitize( $input, $new_input );
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
		WPZOOM_Social_Icons_Sets::render( false );
	}

	/**
	 * A card for a PRO feature: a preview, what it does and a link to PRO.
	 *
	 * @param array $args {
	 *     @type string   $icon        Dashicon name, without the "dashicons-" prefix.
	 *     @type string   $title       Title.
	 *     @type string   $description Description.
	 *     @type callable $preview     Prints the preview.
	 *     @type string[] $points      What the feature does.
	 *     @type string   $button      Button text.
	 *     @type string   $utm_content utm_content of the link, to tell the cards apart.
	 * }
	 */
	private function render_upsell_card( $args ) {
		$upgrade_url = WPZOOM_Connect_Overview::upgrade_url( $args['utm_content'] );
		?>
		<div class="wpzoom-settings-card wpzoom-settings-upsell">
			<?php $this->render_card_header( $args['icon'], $args['title'], $args['description'] ); ?>
			<div class="wpzoom-settings-upsell__body">
				<div class="wpzoom-settings-upsell__preview" aria-hidden="true">
					<?php call_user_func( $args['preview'] ); ?>
				</div>
				<div class="wpzoom-settings-upsell__info">
					<ul>
						<?php foreach ( $args['points'] as $point ) : ?>
							<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php echo esc_html( $point ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a class="button button-primary" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $args['button'] ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'social-icons-widget-by-wpzoom' ); ?></span>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Custom Icons tab: what uploading your own SVG icons in PRO looks like.
	 */
	private function render_custom_icons_tab() {
		$this->render_upsell_card(
			array(
				'icon'        => 'upload',
				'title'       => __( 'Custom Icons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Add your own SVG icons and use them next to the built-in ones.', 'social-icons-widget-by-wpzoom' ),
				'preview'     => function () {
					// Stand-ins for a site's own logos.
					$samples = array(
						array( 'store', '#e2574c' ),
						array( 'microphone', '#7b5cd6' ),
						array( 'utensils', '#f0a020' ),
						array( 'paw', '#2e9e6a' ),
						array( 'graduation-cap', '#2a6fdb' ),
						array( 'heart', '#e0457b' ),
					);
					?>
					<div class="wpzoom-settings-upsell__drop">
						<span class="dashicons dashicons-upload"></span>
						<span><?php esc_html_e( 'Upload an SVG icon', 'social-icons-widget-by-wpzoom' ); ?></span>
					</div>
					<div class="wpzoom-settings-upsell__label"><?php esc_html_e( 'Custom Icons', 'social-icons-widget-by-wpzoom' ); ?></div>
					<div class="wpzoom-settings-upsell__icons">
						<?php foreach ( $samples as $sample ) : ?>
							<span style="background-color:<?php echo esc_attr( $sample[1] ); ?>">
								<?php echo WPZOOM_Social_Icons_Library::inline_svg( 'fas', $sample[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the plugin's own icon library. ?>
							</span>
						<?php endforeach; ?>
					</div>
					<?php
				},
				'points'      => array(
					__( 'Upload any SVG: your own logo, a podcast, a shop, or a network that isn’t in the library yet.', 'social-icons-widget-by-wpzoom' ),
					__( 'Uploaded icons appear in a “Custom Icons” category in the Social Icons block and widget, next to the built-in sets.', 'social-icons-widget-by-wpzoom' ),
					__( 'They take the block’s colors, sizes and styles like any other icon.', 'social-icons-widget-by-wpzoom' ),
				),
				'button'      => __( 'Get Custom Icons with PRO', 'social-icons-widget-by-wpzoom' ),
				'utm_content' => 'settings-custom-icons',
			)
		);
	}

	/**
	 * Pinterest tab: the Save button PRO adds to images on hover, with a
	 * preview the admin can try (corner, shape, label) before upgrading.
	 */
	private function render_pinterest_tab() {
		$upgrade_url = WPZOOM_Connect_Overview::upgrade_url( 'settings-pinterest' );
		$pin_icon    = WPZOOM_Social_Icons_Library::inline_svg( 'fab', 'pinterest' );

		$positions = array(
			'top-left'     => __( 'Top left', 'social-icons-widget-by-wpzoom' ),
			'top-right'    => __( 'Top right', 'social-icons-widget-by-wpzoom' ),
			'bottom-left'  => __( 'Bottom left', 'social-icons-widget-by-wpzoom' ),
			'bottom-right' => __( 'Bottom right', 'social-icons-widget-by-wpzoom' ),
		);
		$shapes    = array(
			'rectangular' => __( 'Rectangular', 'social-icons-widget-by-wpzoom' ),
			'rounded'     => __( 'Rounded', 'social-icons-widget-by-wpzoom' ),
			'circular'    => __( 'Circular', 'social-icons-widget-by-wpzoom' ),
		);
		$features  = array(
			array(
				'title' => __( 'Only where you want it', 'social-icons-widget-by-wpzoom' ),
				'text'  => __( 'Pick the post types: posts, pages, products or any custom type.', 'social-icons-widget-by-wpzoom' ),
			),
			array(
				'title' => __( 'Skips the small stuff', 'social-icons-widget-by-wpzoom' ),
				'text'  => __( 'Set a minimum width and height, so logos and avatars don’t get a button.', 'social-icons-widget-by-wpzoom' ),
			),
			array(
				'title' => __( 'Leave images out', 'social-icons-widget-by-wpzoom' ),
				'text'  => __( 'Skip images by CSS class. Images marked nopin="nopin" are always skipped.', 'social-icons-widget-by-wpzoom' ),
			),
			array(
				'title' => __( 'Phones too, if you like', 'social-icons-widget-by-wpzoom' ),
				'text'  => __( 'Phones can’t hover, so the button can stay visible on small screens.', 'social-icons-widget-by-wpzoom' ),
			),
		);
		?>
		<div class="wpzoom-settings-card wpzoom-pin-upsell">
			<?php
			$this->render_card_header(
				'pinterest',
				__( 'Pinterest', 'social-icons-widget-by-wpzoom' ),
				__( 'Show a Pinterest "Save" button when visitors hover over the images in your content.', 'social-icons-widget-by-wpzoom' )
			);
			?>
			<div class="wpzoom-pin-upsell__hero">
				<div class="wpzoom-pin-upsell__demo">
					<div class="wpzoom-pin-demo" data-position="top-left" data-shape="rounded" data-label="1" aria-hidden="true">
						<div class="wpzoom-pin-demo__post">
							<span class="wpzoom-pin-demo__line is-title"></span>
							<span class="wpzoom-pin-demo__line is-meta"></span>
							<div class="wpzoom-pin-demo__image">
								<span class="wpzoom-pin-demo__button">
									<?php echo $pin_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the plugin's own icon library. ?>
									<span class="wpzoom-pin-demo__label"><?php esc_html_e( 'Save', 'social-icons-widget-by-wpzoom' ); ?></span>
								</span>
								<svg class="wpzoom-pin-demo__cursor" viewBox="0 0 24 24" width="22" height="22"><path d="M5 3l14 8.5-6.2 1.3 3.7 6.4-2.6 1.5-3.7-6.4L5.6 19z" fill="#fff" stroke="#1d2327" stroke-width="1.5" stroke-linejoin="round"/></svg>
							</div>
							<span class="wpzoom-pin-demo__line"></span>
							<span class="wpzoom-pin-demo__line is-short"></span>
						</div>
						<div class="wpzoom-pin-demo__toast">
							<span class="wpzoom-pin-demo__toast-icon"><?php echo $pin_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the plugin's own icon library. ?></span>
							<?php esc_html_e( 'Saved to “Weekend ideas”', 'social-icons-widget-by-wpzoom' ); ?>
						</div>
					</div>

					<div class="wpzoom-pin-try">
						<div class="wpzoom-pin-try__title"><?php esc_html_e( 'Try it', 'social-icons-widget-by-wpzoom' ); ?></div>
						<div class="wpzoom-pin-try__group" role="group" aria-label="<?php esc_attr_e( 'Button position', 'social-icons-widget-by-wpzoom' ); ?>">
							<?php foreach ( $positions as $value => $label ) : ?>
								<button type="button" class="wpzoom-pin-try__option" data-pin-set="position" data-value="<?php echo esc_attr( $value ); ?>" aria-pressed="<?php echo 'top-left' === $value ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
							<?php endforeach; ?>
						</div>
						<div class="wpzoom-pin-try__group" role="group" aria-label="<?php esc_attr_e( 'Button shape', 'social-icons-widget-by-wpzoom' ); ?>">
							<?php foreach ( $shapes as $value => $label ) : ?>
								<button type="button" class="wpzoom-pin-try__option" data-pin-set="shape" data-value="<?php echo esc_attr( $value ); ?>" aria-pressed="<?php echo 'rounded' === $value ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
							<?php endforeach; ?>
							<button type="button" class="wpzoom-pin-try__option" data-pin-set="label" aria-pressed="true"><?php esc_html_e( 'Label', 'social-icons-widget-by-wpzoom' ); ?></button>
						</div>
					</div>
				</div>

				<div class="wpzoom-pin-upsell__info">
					<span class="wpzoom-pin-upsell__badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
					<h3><?php esc_html_e( 'Turn every image into a way back to your site', 'social-icons-widget-by-wpzoom' ); ?></h3>
					<p><?php esc_html_e( 'Pinterest is a search engine for ideas: a pin keeps sending visitors long after the post is published. The Save button makes pinning one click, right on the image.', 'social-icons-widget-by-wpzoom' ); ?></p>
					<ul>
						<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Appears on hover over the images in your posts, without editing them.', 'social-icons-widget-by-wpzoom' ); ?></li>
						<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Pins link back to the post the image came from, described by the image’s alt text or the post title.', 'social-icons-widget-by-wpzoom' ); ?></li>
						<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Your corner, shape and label, with no Pinterest script on the page.', 'social-icons-widget-by-wpzoom' ); ?></li>
					</ul>
					<a class="button button-primary" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Get Pinterest pins with PRO', 'social-icons-widget-by-wpzoom' ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'social-icons-widget-by-wpzoom' ); ?></span>
					</a>
				</div>
			</div>

			<div class="wpzoom-pin-upsell__features">
				<?php foreach ( $features as $feature ) : ?>
					<div class="wpzoom-pin-upsell__feature">
						<strong><?php echo esc_html( $feature['title'] ); ?></strong>
						<span><?php echo esc_html( $feature['text'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<p class="wpzoom-pin-upsell__free">
				<?php
				printf(
					/* translators: %s: link to the Share Buttons settings */
					esc_html__( 'A Pinterest share button is free: turn it on in %s.', 'social-icons-widget-by-wpzoom' ),
					'<a href="#share-buttons">' . esc_html__( 'Share Buttons', 'social-icons-widget-by-wpzoom' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Optimization tab, shown only when a site turns SVG icons off with the
	 * wpzoom_social_icons_use_svg filter and so loads the icon fonts again.
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

}

if ( is_admin() ) {
	$wpzoom_social_icons_settings = new WPZOOM_Social_Icons_Settings();
}
