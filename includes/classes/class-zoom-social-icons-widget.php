<?php
/**
 * Class for Social Icons Widget
 *
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Zoom_Social_Icons_Widget
 */
class Zoom_Social_Icons_Widget extends WP_Widget {
	/**
	 * Path to plugin file.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Collection of icon kits including socicon, dashicons, fontawesome and genericons
	 *
	 * @var array|null
	 */
	protected $icons = null;

	/**
	 * Settings from database.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Protocols that are allowed in esc_url validation function.
	 *
	 * @var array
	 */
	protected $protocols = array(
		'skype',
		'tg',
		'viber',
		'http',
		'https',
		'mailto',
		'news',
		'irc',
		'feed',
		'tel',
		'fax',
		'mms',
		'xmpp',
	);

	/**
	 * The single class instance.
	 *
	 * @var $instance
	 */
	private static $instance = null;

	/**
	 * Widget base ID.
	 *
	 * @var string
	 */
	public $id_base = 'zoom-social-icons-widget';

	/**
	 * Widget name.
	 *
	 * @var string
	 */
	public $widget_name;

	/**
	 * Widget options.
	 *
	 * @var array
	 */
	public $widget_options = array();

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
		$this->widget_name    = esc_html__( 'Social Icons by WPZOOM', 'social-icons-widget-by-wpzoom' );
		$this->widget_options = array(
			'classname'             => 'zoom-social-icons-widget',
			'description'           => __( 'Sortable widget that supports more than 80+ social networks', 'social-icons-widget-by-wpzoom' ),
			'show_instance_in_rest' => true,
		);

		parent::__construct(
			$this->id_base,
			$this->widget_name,
			$this->widget_options
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->icons     = include WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'includes/icons-data.php';
		$removed_keys    = array( 'fab', 'fas', 'far' );
		$this->icons     = array_diff_key( $this->icons, array_flip( $removed_keys ) );
		// The widget's icon picker draws with the icon fonts, which don't have
		// the icons marked svg_only.
		foreach ( $this->icons as $kit => $kit_icons ) {
			$this->icons[ $kit ] = array_values(
				array_filter(
					$kit_icons,
					function ( $item ) {
						return empty( $item['svg_only'] );
					}
				)
			);
		}
		$this->icons     = apply_filters( 'zoom_social_icons_filter', $this->icons );
		$this->protocols = apply_filters( 'zoom_social_protocols_filter', $this->protocols );

		$this->plugin_file = WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . '/social-icons-widget-by-wpzoom.php';

		add_action( 'current_screen', array( $this, 'check_current_screen' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

		// Hooks to enqueue javascript file in SiteOrigin builder.
		add_action( 'siteorigin_panel_enqueue_admin_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'siteorigin_panel_enqueue_admin_scripts', array( $this, 'admin_js_templates' ) );

		// Hooks to enqueue javascript for beaver builder.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_for_beaver' ) );
		add_action( 'wp_footer', array( $this, 'admin_js_templates_for_beaver' ) );

		// Hooks to enqueue admin scripts in Elementor.
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'admin_enqueue_scripts_elementor' ) );
	}

	/**
	 * Include templates for Beaver
	 *
	 * @return void
	 */
	public function admin_js_templates_for_beaver() {
		if ( ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_active() ) ) {
			$this->admin_js_templates();
		}
	}

	/**
	 * Load scripts for Beaver.
	 *
	 * @return void
	 */
	public function enqueue_scripts_for_beaver() {
		if ( ! ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_active() ) ) {
			return;
		}

		wp_enqueue_style(
			'social-icons-widget-admin',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/social-icons-widget-admin.css',
			array( 'wpzoom-social-icons-socicon' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/social-icons-widget-admin.css' )
		);

		wp_enqueue_style( 'wp-color-picker' );

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		wp_enqueue_script(
			'iris',
			admin_url( 'js/iris.min.js' ),
			array(
				'jquery-ui-draggable',
				'jquery-ui-slider',
				'jquery-touch-punch',
			),
			false,
			true
		);

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		wp_enqueue_script(
			'wp-color-picker',
			admin_url( 'js/color-picker.min.js' ),
			array( 'iris' ),
			false,
			true
		);

		$colorpicker_l10n = array(
			'clear'         => __( 'Clear', 'social-icons-widget-by-wpzoom' ),
			'defaultString' => __( 'Default', 'social-icons-widget-by-wpzoom' ),
			'pick'          => __( 'Select Color', 'social-icons-widget-by-wpzoom' ),
			'current'       => __( 'Current Color', 'social-icons-widget-by-wpzoom' ),
		);
		wp_localize_script(
			'wp-color-picker',
			'wpColorPickerL10n',
			$colorpicker_l10n
		);

		wp_enqueue_media();

		wp_enqueue_script(
			'zoom-social-icons-widget-vue-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/vue.min.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/vue.min.js' ),
			true
		);

		wp_enqueue_script(
			'zoom-social-icons-widget-sortable-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/sortable.min.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/sortable.min.js' ),
			true
		);

		wp_enqueue_script(
			'zoom-social-icons-widget-vue-sortable-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/vue-sortable.js',
			array( 'zoom-social-icons-widget-sortable-js' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/vue-sortable.js' ),
			true
		);

		wp_enqueue_script(
			'zoom-social-icons-widget-uri-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/URI.min.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/URI.min.js' ),
			true
		);

		wp_enqueue_script(
			'zoom-social-icons-widget-scroll-to',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/jquery.scrollTo.min.js',
			array( 'jquery' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/jquery.scrollTo.min.js' ),
			true
		);

		wp_enqueue_script(
			'zoom-social-icons-widget',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-icons-widget-backend.js',
			array(
				'jquery',
				'underscore',
				'wp-util',
				'wp-color-picker',
			),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/social-icons-widget-backend.js' ),
			true
		);

		wp_localize_script(
			'zoom-social-icons-widget',
			'zoom_social_widget_data',
			array(
				'icons'      => $this->get_icons_pack(),
				'categories' => $this->get_icon_categories(),
			)
		);
	}

	/**
	 * Include admin scripts for Elementor
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts_elementor() {
		$this->admin_scripts();
		$this->admin_js_templates();
	}

	/**
	 * Enqueue admin javascript only on widgets and customizer pages.
	 */
	public function check_current_screen() {
		$current_screen = get_current_screen();

		$is_screen_widgets       = ! empty( $current_screen->id ) && 'widgets' === $current_screen->id;
		$is_screen_block_widgets = ! empty( $current_screen->id ) && 'appearance_page_gutenberg-widgets' === $current_screen->id;
		$is_screen_customize     = ! empty( $current_screen->id ) && 'customize' === $current_screen->id;
		$is_screen_shortcode     = ! empty( $current_screen->id ) && 'wpzoom-shortcode' === $current_screen->id;

		if ( $is_screen_widgets || $is_screen_block_widgets || $is_screen_customize || $is_screen_shortcode ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_js_templates' ) );
		}
	}

	/**
	 * JavaScript templates for back-end widget form.
	 */
	public function admin_js_templates() {
		?>
<script type="text/x-template" id="tmpl-zoom-social-modal"><?php $this->get_modal_template(); ?></script>
		<?php
	}

	/**
	 * Modal template render function.
	 */
	public function get_modal_template() {
		?>
<div class="modal-mask">
	<div class="media-modal wp-core-ui zoom-social-modal-wrapper">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text"><?php esc_html_e( 'Close media panel', 'social-icons-widget-by-wpzoom' ); ?></span></span>
		</button>
		<div class="media-modal-content" ref="mediaModal">

			<div class="zoom-social-modal-title">
				<slot name="header">
					<h3><?php esc_html_e( 'Select Icon', 'social-icons-widget-by-wpzoom' ); ?></h3>
				</slot>
			</div>

			<div class="zoom-social-modal-content">
				<slot name="body">

					<div class="zoom-social-modal-form">
						<div class="form-group">
							<div class="wrap-label">
								<label><?php esc_html_e( 'Choose icon color', 'social-icons-widget-by-wpzoom' ); ?></label>

							</div>
							<div class="wrap-input wrap-input-color-picker">
								<input type="text" class="zoom-social-icons__field-color-picker" name="zoom-social-icons__field-color-picker" v-model="modal_color_picker" :value="modal_color_picker">
							</div>
						</div>
						<div class="form-group">
							<div class="wrap-label">
								<label><?php esc_html_e( 'Choose hover color', 'social-icons-widget-by-wpzoom' ); ?></label>

							</div>
							<div class="wrap-input wrap-input-color-picker-hover">
								<input type="text" class="zoom-social-icons__field-color-picker" name="zoom-social-icons__field-color-picker-hover" v-model="modal_color_picker_hover" :value="modal_color_picker_hover">
							</div>
						</div>
						<div class="form-group">
							<div class="wrap-label">
								<label><?php esc_html_e( 'Select Icon Kit', 'social-icons-widget-by-wpzoom' ); ?></label>
							</div>
							<div class="wrap-input">
								<select v-model='modal_icon_kit' class="zoom-social-icons__field-icon-kit" name="zoom-social-icons__field-icon-kit">
									<?php
											$icons_kits = zoom_social_icons_kits_categories_list();
									foreach ( $icons_kits as $icon_kit ) :
										?>
									<option value="<?php echo esc_attr( $icon_kit['value'] ); ?>"><?php echo esc_html( $icon_kit['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<select v-model="modal_icon_kit_category">
									<option v-for="cat in getIconCategories" :value="cat">{{cat | spacify |
												capitalize }}
									</option>
								</select>
							</div>
						</div>

						<div class='modal-icons-wrapper'>
							<template v-for="(icons_kit, icon_type) in filterBySocicons">
								<p v-show="searchIconsLength && icons_kit.length ">{{ icon_type | humanizeIconType | capitalize }}</p>
								<div v-show=" modal_icon_kit == icon_type || searchIconsLength && icons_kit.length " class="icon-kit" :class="[icon_type+'-wrapper']">
									<span :style="normalizeStyle(icon.icon, icon_type)" :data-icon="icon.icon" :data-kit="icon_type" @click="clickOnIcon" @mouseover="overOnIcon" @mouseleave="leaveOnIcon" v-for="(icon, icon_key) in icons_kit" :class='["zoom-social-icons__single-element social-icon" ,icon_type , icon_type+"-"+icon.icon, icon_canvas_style, {selected : icon.icon === modal_icon && icon_type === modal_icon_kit }]'></span>
								</div>
							</template>
						</div>


						<input type="hidden" v-model="modal_icon" name="zoom-social-icons__field-icon" class="zoom-social-icons__field-icon" />
					</div>
				</slot>
			</div>

			<div class="zoom-social-modal-toolbar">
				<slot name="footer">
					<input class="search-action-input" style="width: 50%; float: left;" type="text" v-model='searchIcons' placeholder="<?php esc_attr_e( 'Type to search icon', 'social-icons-widget-by-wpzoom' ); ?>" />
					<a href='#' class="button-primary zoom-social-modal-save-btn" @click.prevent="saveModal"><?php esc_html_e( 'Save', 'social-icons-widget-by-wpzoom' ); ?></a>
				</slot>
			</div>
		</div>
	</div>
</div>
		<?php
	}

	/**
	 * Included styles and js files in the backend part.
	 */
	public function admin_scripts() {
		wp_enqueue_style(
			'wpzoom-social-icons-socicon',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-socicon.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-socicon.css' )
		);

		wp_enqueue_style(
			'wpzoom-social-icons-styles',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-styles.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-social-icons-styles.css' )
		);

		wp_enqueue_style(
			'wpzoom-social-icons-admin',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/social-icons-widget-admin.css',
			array( 'wpzoom-social-icons-socicon' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/social-icons-widget-admin.css' )
		);

		wp_enqueue_style(
			'wpzoom-social-icons-genericons',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/genericons.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/genericons.css' )
		);

		wp_enqueue_style(
			'wpzoom-social-icons-academicons',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/academicons.min.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/academicons.min.css' )
		);

		wp_enqueue_style(
			'wpzoom-social-icons-fontawesome-3',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-3.min.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/font-awesome-3.min.css' )
		);

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_script(
			'wpzoom-social-icons-vue-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/vue.min.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/vue.min.js' ),
			true
		);

		wp_enqueue_script(
			'wpzoom-social-icons-sortable-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/sortable.min.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/sortable.min.js' ),
			true
		);

		wp_enqueue_script(
			'wpzoom-social-icons-vue-sortable-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/vue-sortable.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/vue-sortable.js' ),
			true
		);

		wp_enqueue_script(
			'wpzoom-social-icons-uri-js',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/URI.min.js',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/URI.min.js' ),
			true
		);

		wp_enqueue_script(
			'wpzoom-social-icons-scroll-to',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/jquery.scrollTo.min.js',
			array( 'jquery' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/jquery.scrollTo.min.js' ),
			true
		);

		wp_enqueue_script(
			'wpzoom-social-icons-widget',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-icons-widget-backend.js',
			array( 'jquery', 'underscore', 'wp-util', 'wp-color-picker' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/social-icons-widget-backend.js' ),
			true
		);

		wp_localize_script(
			'wpzoom-social-icons-widget',
			'zoom_social_widget_data',
			array(
				'icons'      => $this->get_icons_pack(),
				'categories' => $this->get_icon_categories(),
			)
		);
	}

	/**
	 * Get icons pack by its name by default return all packs.
	 *
	 * @param string $type The icons type name.
	 * @return array
	 */
	public function get_icons_pack( $type = 'all' ) {
		// The picker doesn't offer networks that have shut down; widgets that
		// already use one keep showing it.
		$retired = WPZOOM_Social_Icons_SVG::retired_icons();
		$icons   = $this->icons;
		foreach ( $icons as $kit => $kit_icons ) {
			if ( empty( $retired[ $kit ] ) ) {
				continue;
			}
			$icons[ $kit ] = array_values(
				array_filter(
					$kit_icons,
					function ( $item ) use ( $retired, $kit ) {
						return ! in_array( $item['icon'], $retired[ $kit ], true );
					}
				)
			);
		}
		return array_key_exists( $type, $icons ) ? $icons[ $type ] : $icons;
	}

	/**
	 * Get icons pack categories.
	 *
	 * @return array
	 */
	public function get_icon_categories() {
		return apply_filters(
			'zoom_social_icons_get_icon_categories',
			array(
				'socicon'     =>
					array(
						'all',
						'audio',
						'blogging',
						'communication',
						'design',
						'ecommerce',
						'games',
						'learning',
						'music',
						'news',
						'payment',
						'photography',
						'programming',
						'search-engines',
						'social-media',
						'software',
						'travel',
						'video',
						'web-tools',
					),
				'dashicons'   =>
					array(
						'all',
						'admin-menu',
						'image-editing',
						'media',
						'misc',
						'notifications',
						'post-formats',
						'posts-screen',
						'products',
						'social',
						'sorting',
						'taxonomies',
						'tinymce',
						'welcome-screen',
						'widgets',
						'wordpress-specific',
					),
				'genericon'   =>
					array(
						'all',
					),
				'academicons' =>
					array(
						'all',
					),
				'fa'          =>
					array(
						'accessibility',
						'all',
						'brand',
						'chart',
						'currency',
						'directional',
						'file-type',
						'form-control',
						'gender',
						'hand',
						'medical',
						'payment',
						'spinner',
						'text-editor',
						'transportation',
						'video-player',
						'web-application',
					),
			)
		);
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$defaults = $this->get_defaults();
		$instance = $this->normalize_data_array( $new_instance );
		$instance = wp_parse_args( (array) $instance, $defaults );

		$instance['title']                     = sanitize_text_field( $new_instance['title'] );
		$instance['description']               = balanceTags( wp_kses( $new_instance['description'], wp_kses_allowed_html() ), true );
		$instance['show_icon_labels']          = ( ! empty( $new_instance['show_icon_labels'] ) && 'true' === $new_instance['show_icon_labels'] ) ? 'true' : 'false';
		$instance['open_new_tab']              = ( ! empty( $new_instance['open_new_tab'] ) && 'true' === $new_instance['open_new_tab'] ) ? 'true' : 'false';
		$instance['no_follow']                 = ( ! empty( $new_instance['no_follow'] ) && 'true' === $new_instance['no_follow'] ) ? 'true' : 'false';
		$instance['no_opener']                 = ( ! empty( $new_instance['no_opener'] ) && 'true' === $new_instance['no_opener'] ) ? 'true' : 'false';
		$instance['no_referrer']               = ( ! empty( $new_instance['no_referrer'] ) && 'true' === $new_instance['no_referrer'] ) ? 'true' : 'false';
		$instance['rel_me']                    = ( ! empty( $new_instance['rel_me'] ) && 'true' === $new_instance['rel_me'] ) ? 'true' : 'false';
		$instance['icon_padding_size']         = (int) $new_instance['icon_padding_size'];
		$instance['icon_font_size']            = (int) $new_instance['icon_font_size'];
		$instance['global_color_picker']       = $new_instance['global_color_picker'];
		$instance['global_color_picker_hover'] = $new_instance['global_color_picker_hover'];

		if ( in_array( $new_instance['icon_style'], array( 'with-canvas', 'without-canvas' ) ) ) {
			$instance['icon_style'] = $new_instance['icon_style'];
		}

		if ( in_array( $new_instance['icon_alignment'], array( 'left', 'center', 'right', 'none' ) ) ) {
			$instance['icon_alignment'] = $new_instance['icon_alignment'];
		}

		if ( in_array( $new_instance['icon_canvas_style'], array( 'round', 'rounded', 'square' ) ) ) {
			$instance['icon_canvas_style'] = $new_instance['icon_canvas_style'];
		}

		$field_count = empty( $new_instance['url_fields'] ) ? 0 : count( $new_instance['url_fields'] );

		$instance['fields'] = array();

		for ( $i = 0; $i < $field_count; $i++ ) {
			$url   = esc_url( $new_instance['url_fields'][ $i ], $this->protocols );
			$label = esc_html( $new_instance['label_fields'][ $i ] );

			if ( $url ) {
				$instance['fields'][] = array(
					'url'                => $url,
					'label'              => $label,
					'icon'               => $new_instance['icon_fields'][ $i ],
					'icon_kit'           => $new_instance['icon_kit_fields'][ $i ],
					'color_picker'       => $new_instance['color_picker_fields'][ $i ],
					'color_picker_hover' => $new_instance['color_picker_hover_fields'][ $i ],
					'aria_label'         => ! empty( $new_instance['aria_label_fields'][ $i ] ) ? $new_instance['aria_label_fields'][ $i ] : '',
				);
			}

			/**
			 * Register strings for translation.
			 */
			if ( function_exists( 'icl_register_string' ) ) {
				icl_register_string( 'social-icons-widget-by-wpzoom', 'url-' . $i, $url );
			}
		}

		return $instance;
	}

	/**
	 * Render wigdet form in the backend.
	 *
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function form( $instance ) {
		global $wp_version;

		$defaults = $this->get_defaults();

		if ( isset( $instance['show-icon-labels'] ) || isset( $instance['open-new-tab'] ) || isset( $instance['no-follow'] ) ) {
			$instance['show-icon-labels'] = ! empty( $instance['show-icon-labels'] ) ? 'true' : 'false';
			$instance['open-new-tab']     = ! empty( $instance['open-new-tab'] ) ? 'true' : 'false';
			$instance['no-follow']        = ! empty( $instance['no-follow'] ) ? 'true' : 'false';
		}

		$instance = $this->normalize_data_array( $instance );
		$instance = wp_parse_args( $instance, $defaults );

		$this->inject_values( $instance );

		$instance['fields'] = $this->inject_fields_with_data( $instance['fields'] );

		$instance_attr = '';
		$default_field = $this->inject_fields_with_data( $this->get_default_field() );
		$default_field = array_pop( $default_field );

		if ( ! empty( $instance ) ) {
			$encoded = array(
				'id'            => $this->id,
				'instance'      => $instance,
				'default_field' => $default_field,
			);

			$instance_attr = 'data-instance="' . htmlentities( json_encode( $encoded ) ) . '"'; // phpcs:ignore PHPCompatibility.ParameterValues.NewHTMLEntitiesEncodingDefault.NotSet
		}
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
<div class="form-instance" <?php echo $instance_attr; ?> id="<?php echo $this->id; ?>">
<div class="social_shortcode_wrap_left">
	<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'social-icons-widget-by-wpzoom' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" v-model="title" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" />
	</p>

	<p>
		<label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php esc_html_e( 'Text above icons:', 'social-icons-widget-by-wpzoom' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" cols="20" v-model="description" rows="3"></textarea>
	</p>

	<p class="description"><?php esc_html_e( 'You can add here a text above the icons. Basic HTML allowed.', 'social-icons-widget-by-wpzoom' ); ?></p>


	<p>
		<input class="checkbox zoom-social-icons-show-icon-labels" type="checkbox" v-model="show_icon_labels" :true-value="'true'" :false-value="'false'" :value="show_icon_labels" id="<?php echo $this->get_field_id( 'show_icon_labels' ); ?>" name="<?php echo $this->get_field_name( 'show_icon_labels' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_icon_labels' ); ?>"><?php esc_html_e( 'Show icon labels? ', 'social-icons-widget-by-wpzoom' ); ?></label>
	</p>

	<p>
		<input class="checkbox" type="checkbox" v-model="open_new_tab" :true-value="'true'" :false-value="'false'" :value="open_new_tab" id="<?php echo $this->get_field_id( 'open_new_tab' ); ?>" name="<?php echo $this->get_field_name( 'open_new_tab' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'open_new_tab' ); ?>"><?php esc_html_e( 'Open links in new tab? ', 'social-icons-widget-by-wpzoom' ); ?></label>
	</p>
	<p>
		<input class="checkbox" type="checkbox" v-model="no_follow" :true-value="'true'" :false-value="'false'" :value="no_follow" id="<?php echo $this->get_field_id( 'no_follow' ); ?>" name="<?php echo $this->get_field_name( 'no_follow' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'no_follow' ); ?>"><?php /* translators: %s: the rel attribute that gets added, wrapped in a <code> tag. */ echo sprintf( __( 'Add %s to links', 'social-icons-widget-by-wpzoom' ), '<code>rel="nofollow"</code>' ); ?></label>
	</p>

	<p>
		<input class="checkbox" type="checkbox" v-model="no_referrer" :true-value="'true'" :false-value="'false'" :value="no_referrer" id="<?php echo $this->get_field_id( 'no_referrer' ); ?>" name="<?php echo $this->get_field_name( 'no_referrer' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'no_referrer' ); ?>"><?php /* translators: %s: the rel attribute that gets added, wrapped in a <code> tag. */ echo sprintf( __( 'Add %s to links', 'social-icons-widget-by-wpzoom' ), '<code>rel="noreferrer"</code>' ); ?></label>
	</p>

	<p>
		<input class="checkbox" type="checkbox" v-model="no_opener" :true-value="'true'" :false-value="'false'" :value="no_opener" id="<?php echo $this->get_field_id( 'no_opener' ); ?>" name="<?php echo $this->get_field_name( 'no_opener' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'no_opener' ); ?>"><?php /* translators: %s: the rel attribute that gets added, wrapped in a <code> tag. */ echo sprintf( __( 'Add %s to links', 'social-icons-widget-by-wpzoom' ), '<code>rel="noopener"</code>' ); ?></label>
	</p>
	<p>
		<input class="checkbox" type="checkbox" v-model="rel_me" :true-value="'true'" :false-value="'false'" :value="rel_me" id="<?php echo $this->get_field_id( 'rel_me' ); ?>" name="<?php echo $this->get_field_name( 'rel_me' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'rel_me' ); ?>"><?php /* translators: %s: the rel attribute that gets added, wrapped in a <code> tag. */ echo sprintf( __( 'Add %s to links', 'social-icons-widget-by-wpzoom' ), '<code>rel="me"</code>' ); ?></label>
	</p>

	<p class="description"><?php esc_html_e( 'Recommended if links or icons open in a new tab', 'social-icons-widget-by-wpzoom' ); ?></p>


	<p>
		<label for="<?php echo $this->get_field_id( 'icon_alignment' ); ?>"><?php esc_html_e( 'Icons Alignment:', 'social-icons-widget-by-wpzoom' ); ?>
		</label>
		<select name="<?php echo $this->get_field_name( 'icon_alignment' ); ?>" id="<?php echo $this->get_field_id( 'icon_alignment' ); ?>" v-model="icon_alignment" class="widefat">
			<option value="none"><?php esc_html_e( 'None', 'social-icons-widget-by-wpzoom' ); ?></option>
			<option value="left"><?php esc_html_e( 'Align Left', 'social-icons-widget-by-wpzoom' ); ?></option>
			<option value="center"><?php esc_html_e( 'Align Center', 'social-icons-widget-by-wpzoom' ); ?></option>
			<option value="right"><?php esc_html_e( 'Align Right', 'social-icons-widget-by-wpzoom' ); ?></option>
		</select>
	</p>

	<p>
		<label for="<?php echo $this->get_field_id( 'icon_style' ); ?>"><?php esc_html_e( 'Icon Style:', 'social-icons-widget-by-wpzoom' ); ?>
		</label>
		<select name="<?php echo $this->get_field_name( 'icon_style' ); ?>" id="<?php echo $this->get_field_id( 'icon_style' ); ?>" v-model="icon_style" class="widefat">
			<option value="with-canvas"><?php esc_html_e( 'Color Background / White Icon', 'social-icons-widget-by-wpzoom' ); ?></option>
			<option value="without-canvas"><?php esc_html_e( 'Color Icon / No Background', 'social-icons-widget-by-wpzoom' ); ?></option>
		</select>
	</p>

	<p>
		<label :style="iconCanvasStyleLabel()" for="<?php echo $this->get_field_id( 'icon_canvas_style' ); ?>"><?php esc_html_e( 'Icon Background Style:', 'social-icons-widget-by-wpzoom' ); ?></label>
		<select :disabled="this.icon_style == 'without-canvas'" name="<?php echo $this->get_field_name( 'icon_canvas_style' ); ?>" id="<?php echo $this->get_field_id( 'icon_canvas_style' ); ?>" v-model="icon_canvas_style" class="widefat zoom-social-icons-change-icon-canvas-style">
			<option value="round"><?php esc_html_e( 'Round', 'social-icons-widget-by-wpzoom' ); ?></option>
			<option value="rounded"><?php esc_html_e( 'Rounded Corners', 'social-icons-widget-by-wpzoom' ); ?></option>
			<option value="square"><?php esc_html_e( 'Square', 'social-icons-widget-by-wpzoom' ); ?></option>
		</select>
	</p>

	<p class="description">
		<?php echo wp_kses_post( __( 'This option has no effect if the <i>Color Icon / No Background</i> icon style is selected.', 'social-icons-widget-by-wpzoom' ) ); ?>
	</p>

	<p>
		<label for="<?php echo $this->get_field_id( 'icon_padding_size' ); ?>"><?php esc_html_e( 'Icon Padding (pixels):', 'social-icons-widget-by-wpzoom' ); ?>
			<input type="number" min="0" max="200" id="<?php echo $this->get_field_id( 'icon_padding_size' ); ?>" name="<?php echo $this->get_field_name( 'icon_padding_size' ); ?>" v-model="icon_padding_size" class="widefat" />
		</label>
	</p>

	<p>
		<label for="<?php echo $this->get_field_id( 'icon_font_size' ); ?>"><?php esc_html_e( 'Icon Size (pixels):', 'social-icons-widget-by-wpzoom' ); ?>
			<input type="number" min="0" max="200" id="<?php echo $this->get_field_id( 'icon_font_size' ); ?>" name="<?php echo $this->get_field_name( 'icon_font_size' ); ?>" v-model="icon_font_size" class="widefat" />
		</label>
	</p>

	</div>

	<div class="social_shortcode_wrap_right">

	<p>
		<label><?php esc_html_e( 'Set color for all icons', 'social-icons-widget-by-wpzoom' ); ?></label>
	<div class="wrap-input-color-picker">
		<input v-model="global_color_picker" type="text" class="zoom-social-icons__field-color-picker" id="<?php echo $this->get_field_id( 'global_color_picker' ); ?>" name="<?php echo $this->get_field_name( 'global_color_picker' ); ?>" :value="global_color_picker">
	</div>
	</p>
	<p>
		<label><?php esc_html_e( 'Set hover color for all icons', 'social-icons-widget-by-wpzoom' ); ?></label>
	<div class="wrap-input-color-picker">
		<input v-model="global_color_picker_hover" type="text" class="zoom-social-icons__field-color-picker" id="<?php echo $this->get_field_id( 'global_color_picker_hover' ); ?>" name="<?php echo $this->get_field_name( 'global_color_picker_hover' ); ?>" :value="global_color_picker_hover">
	</div>
	</p>
	<p style="margin-bottom: 0;"><?php esc_html_e( 'Icons:', 'social-icons-widget-by-wpzoom' ); ?></p>

	<div class="must-remove">
		<input type="hidden" value="<?php echo $defaults['title']; ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" />
		<input type="hidden" value="<?php echo $defaults['description']; ?>" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" />
		<input type='hidden' value="<?php echo $defaults['open_new_tab']; ?>" id="<?php echo $this->get_field_id( 'open_new_tab' ); ?>" name="<?php echo $this->get_field_name( 'open_new_tab' ); ?>" />
		<input type='hidden' value="<?php echo $defaults['icon_alignment']; ?>" id="<?php echo $this->get_field_id( 'icon_alignment' ); ?>" name="<?php echo $this->get_field_name( 'icon_alignment' ); ?>" />
		<input type='hidden' value="<?php echo $defaults['no_follow']; ?>" id="<?php echo $this->get_field_id( 'no_follow' ); ?>" name="<?php echo $this->get_field_name( 'no_follow' ); ?>" />
		<input type='hidden' value="<?php echo $defaults['no_opener']; ?>" id="<?php echo $this->get_field_id( 'no_opener' ); ?>" name="<?php echo $this->get_field_name( 'no_opener' ); ?>" />
		<input type='hidden' value="<?php echo $defaults['no_referrer']; ?>" id="<?php echo $this->get_field_id( 'no_referrer' ); ?>" name="<?php echo $this->get_field_name( 'no_referrer' ); ?>" />
		<input type='hidden' value="<?php echo $defaults['rel_me']; ?>" id="<?php echo $this->get_field_id( 'rel_me' ); ?>" name="<?php echo $this->get_field_name( 'rel_me' ); ?>" />

		<input type='hidden' value="<?php echo $defaults['show_icon_labels']; ?>" id="<?php echo $this->get_field_id( 'show_icon_labels' ); ?>" name="<?php echo $this->get_field_name( 'show_icon_labels' ); ?>" />
		<?php

		foreach ( $instance['fields'] as $field ) {
			printf(
				'<input  type="hidden" id="%1$s" name="%2$s"  value="%3$s">',
				$field['url_field_id'],
				$field['url_field_name'],
				esc_attr( $field['url'] )
			);

			printf(
				'<input type="hidden" id="%1$s" name="%2$s" value="%3$s">',
				$field['label_field_id'],
				$field['label_field_name'],
				esc_attr( $field['label'] )
			);

			printf(
				'<input type="hidden"  id="%1$s" name="%2$s" value="%3$s">',
				$field['color_picker_field_id'],
				$field['color_picker_field_name'],
				esc_attr( $field['color_picker'] )
			);

			printf(
				'<input type="hidden"  id="%1$s" name="%2$s" value="%3$s">',
				$field['color_picker_hover_field_id'],
				$field['color_picker_hover_field_name'],
				esc_attr( $field['color_picker_hover'] )
			);

			printf(
				'<input type="hidden"  id="%1$s" name="%2$s" value="%3$s">',
				$field['icon_field_id'],
				$field['icon_field_name'],
				esc_attr( $field['icon'] )
			);

			printf(
				'<input type="hidden"   id="%1$s" name="%2$s" value="%3$s">',
				$field['icon_kit_field_id'],
				$field['icon_kit_field_name'],
				esc_attr( $field['icon_kit'] )
			);
		}
		?>
	</div>

	<ul v-sortable="{ handle: '.zoom-social-icons__field-handle', onUpdate : onUpdate  }" class="zoom-social-icons__list">

		<template v-for="(field, key) in fields">

			<li class="zoom-social-icons__field">

				<modal ref="modals" @input='onInputModal' @keyup.esc.stop="closeModal(key)" @close='closeModal(key)' v-if='field.show_modal' :field_key="key" :icon="field.icon" :icon_style="icon_style" :icon_canvas_style="icon_canvas_style" :icon_kit="field.icon_kit" :color_picker="field.color_picker" :color_picker_hover="field.color_picker_hover" :icon_categories='icons.categories' :icons="icons.icons">
				</modal>

				<span class="dashicons dashicons-sort zoom-social-icons__field-handle"></span>
				<span class="zoom-social-icons__field-icon-handler social-icon" :style="normalizeStyle(key)" :class="[ field.icon_kit, field.icon_kit+'-'+field.icon, icon_canvas_style]" @mouseover='mouseoverIcon(key, $event)' @mouseleave='mouseleaveIcon(key, $event)' @click.stop="clickonIconHandler(key)"></span>
				<div class="zoom-social-icons__cw">
					<div class="zoom-social-icons__inputs" ref="inputFields">

						<input class="widefat zoom-social-icons__field-url" :id="field.url_field_id" :name="field.url_field_name" v-model="field.url" type="text" :value="field.url_field_name" @input="urlFieldNameHandler(key)" @keyup.enter.stop="insertField" placeholder="<?php esc_html_e( 'Start typing the URL...', 'social-icons-widget-by-wpzoom' ); ?>">
						<input class="widefat zoom-social-icons__field-label" :id="field.label_field_id" :name="field.label_field_name" v-model="field.label" type="text" :value="field.label_field_name" placeholder="<?php esc_html_e( 'Label', 'social-icons-widget-by-wpzoom' ); ?>">
						<input type="hidden" :id="field.color_picker_field_id" :name="field.color_picker_field_name" v-model='field.color_picker' :value="field.color_picker">
						<input type="hidden" :id="field.color_picker_hover_field_id" :name="field.color_picker_hover_field_name" v-model='field.color_picker_hover' :value="field.color_picker_hover">
						<input type="hidden" :id="field.icon_field_id" :name="field.icon_field_name" v-model="field.icon" :value="field.icon">
						<input type="hidden" :id="field.icon_kit_field_id" :name="field.icon_kit_field_name" v-model="field.icon_kit" :value="field.icon_kit">
					</div>
				</div>

				<a title="<?php esc_attr_e( 'Edit', 'social-icons-widget-by-wpzoom' ); ?>" v-show='fields.length > 1' @click.prevent="openModal(key)" class="zoom-social-icons__field-edit" href="#"><span class="dashicons dashicons-admin-appearance"></span></a>
				<a title="<?php esc_attr_e( 'Remove', 'social-icons-widget-by-wpzoom' ); ?>" v-show='fields.length > 1' class="zoom-social-icons__field-trash" href="#" @click.prevent="clickOnDeleteIconHandler(key)"><span class="dashicons dashicons-trash"></span></a>
				<span title="<?php esc_attr_e( 'Expand', 'social-icons-widget-by-wpzoom' ); ?>" :class="toggleExtraOptionsClass(key)" @click.prevent='toggleExtraOptions(key, field)'></span>
				<br style="clear:both">
				<div class="extra-options" v-show="field.show_extra_options == true">
					<p>
						<label :for="field.aria_label_field_id">
							<code>"aria-label"</code> <?php esc_html_e( 'description', 'social-icons-widget-by-wpzoom' ); ?>
							<input class="widefat" :id="field.aria_label_field_id" :name="field.aria_label_field_name" v-model="field.aria_label" type="text" :value="field.aria_label_field_name">
						</label>
					</p>

					<p class="description">
						<?php echo wp_kses_post( __( 'This is used to provide a description of this icon to screen reader users (for accessibility purposes).', 'social-icons-widget-by-wpzoom' ) ); ?>
					</p>

				</div>
			</li>

		</template>

	</ul>

	<div class="zoom-social-icons__add-button">
		<a @click.prevent='insertField' class="button"><?php esc_html_e( 'Add more', 'social-icons-widget-by-wpzoom' ); ?></a>
	</div>

	<p class="description">
		<?php echo wp_kses_post( __( 'To add an icon with an email address, use the <strong><em>mailto:mail@example.com</em></strong> format.', 'social-icons-widget-by-wpzoom' ) ); ?>
	</p>

	<p class="description">
		<?php echo wp_kses_post( __( 'Note that the icons above may not look the same on the front end. This is just for reference.', 'social-icons-widget-by-wpzoom' ) ); ?>
	</p>
	</div>
</div>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get default values for the first render when nothing is saved in databases.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return apply_filters(
			'zoom_social_icons_get_defaults',
			array(
				'title'                     => esc_html__( 'Follow us', 'social-icons-widget-by-wpzoom' ),
				'description'               => '',
				'show_icon_labels'          => 'false',
				'open_new_tab'              => 'true',
				'no_follow'                 => 'false',
				'no_opener'                 => 'false',
				'no_referrer'               => 'false',
				'rel_me'                    => 'false',
				'icon_style'                => 'with-canvas',
				'icon_alignment'            => 'none',
				'icon_canvas_style'         => 'round',
				'icon_padding_size'         => 8,
				'icon_font_size'            => 18,
				'global_color_picker'       => '#1e73be',
				'global_color_picker_hover' => '#1e73be',
				'fields'                    => array(
					array(
						'url'                => 'https://facebook.com/',
						'label'              => 'Facebook',
						'icon'               => 'facebook',
						'icon_kit'           => 'socicon',
						'color_picker'       => '#0866FF',
						'color_picker_hover' => '#0866FF',
						'aria_label'         => '',
					),
					array(
						'url'                => 'https://x.com/',
						'label'              => 'X',
						'icon'               => 'x',
						'icon_kit'           => 'socicon',
						'color_picker'       => '#000000',
						'color_picker_hover' => '#000000',
						'aria_label'         => '',

					),
					array(
						'url'                => 'https://instagram.com/',
						'label'              => 'Instagram',
						'icon'               => 'instagram',
						'icon_kit'           => 'socicon',
						'color_picker'       => '#e4405f',
						'color_picker_hover' => '#e4405f',
						'aria_label'         => '',
					),
				),
			)
		);
	}

	/**
	 * Recursive function that replace kebab-case keys with snake_case keys for backward compatibility.
	 *
	 * @param array $value Widget instance data.
	 *
	 * @return array
	 */
	public function normalize_data_array( $value ) {
		$collector = array();

		if ( empty( $value ) ) {
			return $collector;
		}

		foreach ( $value as $val_key => $val ) {
			if ( is_array( $val ) ) {
				$val = $this->normalize_data_array( $val );
			}
			$collector[ str_replace( '-', '_', $val_key ) ] = $val;
		}

		return $collector;
	}

	/**
	 * It is a function for backward compatibility that normalize data values between old and new version
	 * in order to work properly after users will make an update to the new version.
	 *
	 * @param array $data Widget instance data.
	 */
	public function inject_values( &$data ) {
		$socicons = wp_list_pluck( $this->icons['socicon'], 'color', 'icon' );
		foreach ( $data['fields'] as $key => &$field ) {
			$get_icon    = $this->get_icon( $field['url'] );
			$parsed_icon = empty( $get_icon ) ? 'WordPress' : $get_icon;

			if ( empty( $field['icon'] ) && empty( $field['icon_kit'] ) ) {
				$field['icon']     = $parsed_icon;
				$field['icon_kit'] = 'socicon';

				$field['color_picker']       = $socicons[ $parsed_icon ];
				$field['color_picker_hover'] = $socicons[ $parsed_icon ];
			}

			if ( empty( $field['color_picker'] ) || ( strpos( $field['color_picker'], 'rgb' ) !== false ) ) {
				$color                       = empty( $field['color_picker'] ) ? $socicons[ $parsed_icon ] : $this->rgb2hex( $field['color_picker'] );
				$field['color_picker']       = $color;
				$field['color_picker_hover'] = $color;
			}

			if ( empty( $field['color_picker_hover'] ) ) {
				$field['color_picker_hover'] = $field['color_picker'];
			}
		}
	}

	/**
	 * Parse icon name from an url and return it.
	 *
	 * @param string $url Icon url.
	 *
	 * @return mixed|void
	 */
	protected function get_icon( $url ) {
		$icon       = '';
		$parsed_url = $this->extract_domain( $url );
		if ( $url ) {
			if ( strstr( $url, 'feedburner.google.com' )
				|| strstr( $url, 'mailto:' )
			) {
				$icon = 'mail';
			}
			if ( strstr( $url, 'feedburner.com' ) ) {
				$icon = 'rss';
			}
			if ( ! $icon ) {
				$icons = wp_list_pluck( $this->icons['socicon'], 'icon' );
				foreach ( $icons as $icon_id ) {
					if ( strstr( $parsed_url, $icon_id ) ) {
						$icon = $icon_id;
						break;
					}
				}
			}
		}

		return apply_filters( 'zoom-social-icons-widget-icon', $icon, $url );
	}

	/**
	 * Extract domain from an url.
	 *
	 * @param string $url Icon url.
	 *
	 * @return mixed
	 */
	public function extract_domain( $url ) {
		$parsed_url = parse_url( trim( $url ) );
		$path       = empty( $parsed_url['path'] ) ? array( $url ) : explode( '/', $parsed_url['path'], 2 );

		return empty( $parsed_url['host'] ) ? array_shift( $path ) : $parsed_url['host'];
	}

	/**
	 * Convert rgb string to hex string.
	 *
	 * @param string $rgb RGB code color.
	 *
	 * @return string
	 */
	public function rgb2hex( $rgb ) {
		$rgb = $this->parse_rgb( $rgb );

		return '#' . sprintf( '%02x', $rgb['r'] ) . sprintf( '%02x', $rgb['g'] ) . sprintf( '%02x', $rgb['b'] );
	}

	/**
	 * Parse rgb string and covert its to an array.
	 *
	 * @param string $rgb_string RGB code color.
	 *
	 * @return array
	 */
	public function parse_rgb( $rgb_string ) {
		$rgb      = array(
			'r' => 0,
			'g' => 1,
			'b' => 2,
		);
		$exploded = explode( ',', $rgb_string );
		foreach ( $rgb as $key => &$value ) {
			$value = filter_var( $exploded[ $value ], FILTER_SANITIZE_NUMBER_INT );
		}

		return $rgb;
	}

	/**
	 * Inject field with data in order to render the correct names ids and other html attributes in the template.
	 *
	 * @param array $fields Icon fields.
	 *
	 * @return array
	 */
	private function inject_fields_with_data( $fields ) {
		$merged_fields = array();

		$will_be_merged = array(
			'show_modal'                    => false,
			'show_extra_options'            => false,
			'url_field_id'                  => $this->get_field_id( 'url_fields' ),
			'url_field_name'                => $this->get_field_name( 'url_fields' ) . '[]',
			'label_field_id'                => $this->get_field_id( 'label_fields' ),
			'label_field_name'              => $this->get_field_name( 'label_fields' ) . '[]',
			'color_picker_field_id'         => $this->get_field_id( 'color_picker_fields' ),
			'color_picker_field_name'       => $this->get_field_name( 'color_picker_fields' ) . '[]',
			'color_picker_hover_field_id'   => $this->get_field_id( 'color_picker_hover_fields' ),
			'color_picker_hover_field_name' => $this->get_field_name( 'color_picker_hover_fields' ) . '[]',
			'icon_field_id'                 => $this->get_field_id( 'icon_fields' ),
			'icon_field_name'               => $this->get_field_name( 'icon_fields' ) . '[]',
			'icon_kit_field_id'             => $this->get_field_id( 'icon_kit_fields' ),
			'icon_kit_field_name'           => $this->get_field_name( 'icon_kit_fields' ) . '[]',
			'aria_label_field_id'           => $this->get_field_id( 'aria_label_fields' ),
			'aria_label_field_name'         => $this->get_field_name( 'aria_label_fields' ) . '[]',

		);

		foreach ( $fields as $field ) {
			$merged_fields[] = array_merge( $field, $will_be_merged );
		}

		return $merged_fields;
	}

	/**
	 * Get default field for a new added element.
	 *
	 * @return array
	 */
	public function get_default_field() {
		return array(
			array(
				'url'                => '',
				'label'              => '',
				'icon'               => 'wordpress',
				'icon_kit'           => 'socicon',
				'color_picker'       => '#1e73be',
				'color_picker_hover' => '#1e73be',
				'aria_label'         => '',
			),
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance = $this->normalize_data_array( $instance );
		$instance = wp_parse_args( (array) $instance, $this->get_defaults() );

		$this->inject_values( $instance );

		echo wp_kses_post( $args['before_widget'] );

		if ( $instance['title'] ) {
			echo wp_kses_post( $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'] );
		}

		$class_list   = array();
		$desc_class   = '';
		$class_list[] = 'zoom-social-icons-list--' . $instance['icon_style'];
		$class_list[] = 'zoom-social-icons-list--' . $instance['icon_canvas_style'];

		if ( ! empty( $instance['icon_alignment'] ) && in_array( $instance['icon_alignment'], array( 'left', 'center', 'right' ) ) ) {
			$class_list[] = 'zoom-social-icons-list--align-' . $instance['icon_alignment'];
			$desc_class   = 'zoom-social-icons-list--align-' . $instance['icon_alignment'];
		}

		if ( is_bool( $instance['show_icon_labels'] ) ) {
			$instance['show_icon_labels'] = true === $instance['show_icon_labels'] ? 'true' : 'false';
		}

		if ( is_bool( $instance['open_new_tab'] ) ) {
			$instance['open_new_tab'] = true === $instance['open_new_tab'] ? 'true' : 'false';
		}

		if ( is_bool( $instance['no_follow'] ) ) {
			$instance['no_follow'] = true === $instance['no_follow'] ? 'true' : 'false';
		}

		if ( is_bool( $instance['no_opener'] ) ) {
			$instance['no_opener'] = true === $instance['no_opener'] ? 'true' : 'false';
		}

		if ( is_bool( $instance['no_referrer'] ) ) {
			$instance['no_referrer'] = true === $instance['no_referrer'] ? 'true' : 'false';
		}

		if ( is_bool( $instance['rel_me'] ) ) {
			$instance['rel_me'] = true === $instance['rel_me'] ? 'true' : 'false';
		}

		if ( 'false' === $instance['show_icon_labels'] ) {
			$class_list[] = 'zoom-social-icons-list--no-labels';
		}
		?>

		<?php if ( ! empty( $instance['description'] ) ) : ?>

<p class="<?php echo esc_attr( $desc_class ); ?>"><?php echo wp_kses_post( $instance['description'] ); ?></p>

		<?php endif; ?>

<ul class="zoom-social-icons-list <?php echo esc_attr( implode( ' ', $class_list ) ); ?>">

		<?php
		foreach ( $instance['fields'] as $key => $field ) :
			?>

			<?php
			$rule            = ( 'with-canvas' === $instance['icon_style'] ) ? 'background-color' : 'color';
			$hover_style     = empty( $field['color_picker_hover'] ) ? '' : 'data-hover-rule="' . $rule . '" data-hover-color="' . esc_attr( $field['color_picker_hover'] ) . '"';
			$rel_tag         = 'true' == $instance['no_follow'] ? 'nofollow' : '';
			$rel_tag        .= 'true' == $instance['no_opener'] ? ' noopener' : '';
			$rel_tag        .= 'true' == $instance['no_referrer'] ? ' noreferrer' : '';
			$rel_tag        .= 'true' == $instance['rel_me'] ? ' me' : '';
			$aria_image_role = '';
			$url             = esc_url( $field['url'], $this->protocols );

			if ( function_exists( 'icl_t' ) ) {
				$url = icl_t( 'zoom-social-icons-widget', 'url-' . $key, $field['url'] );
			}

			if ( ! empty( $field['aria_label'] ) ) {
				$aria_image_role = "role='img' aria-label='" . esc_attr( $field['aria_label'] ) . "'";
			}
			?>
	<li class="zoom-social_icons-list__item">
		<a class="zoom-social_icons-list__link" href="<?php echo esc_url( $url ); ?>" <?php echo( 'true' === $instance['open_new_tab'] ? 'target="_blank"' : '' ); ?> title="<?php echo esc_html( $field['label'] ); ?>" <?php echo( strlen( $rel_tag ) > 0 ? 'rel="' . esc_attr( trim( $rel_tag ) ) . '"' : '' ); ?>>
			<?php
			if ( ! empty( $field['icon'] ) && ! empty( $field['icon_kit'] ) && ! empty( $field['color_picker'] ) ) {
				$class = $field['icon_kit'] . ' ' . $field['icon_kit'] . '-' . $field['icon'];
				$style = $rule . ' : ' . esc_attr( $field['color_picker'] );
			} else {
				$style = '';

				$class = 'socicon socicon-' . esc_attr( $this->get_icon( $field['url'] ) );
			}
			?>
			<?php
			// Inline SVG instead of the icon font, on the front end.
			$svg = '';
			if ( WPZOOM_Social_Icons_SVG::enabled() ) {
				$svg_icon = WPZOOM_Social_Icons_SVG::parse_classes( $class );
				$svg      = $svg_icon ? WPZOOM_Social_Icons_SVG::icon( $svg_icon[0], $svg_icon[1] ) : '';
				if ( '' !== $svg ) {
					$class .= ' wpzoom-svg';
					// An icon without its own color took its brand color from the
					// Socicons stylesheet, which isn't loaded now.
					if ( '' === $style && 'socicon' === $svg_icon[0] ) {
						$brand = wp_list_pluck( $this->icons['socicon'], 'color', 'icon' );
						if ( ! empty( $brand[ $svg_icon[1] ] ) ) {
							$style = $rule . ' : ' . esc_attr( $brand[ $svg_icon[1] ] );
						}
					}
				}
			}
			?>

			<?php
			if ( ! empty( $instance['icon_font_size'] ) ) {
				$style .= '; font-size: ' . esc_attr( $instance['icon_font_size'] ) . 'px';
			}
			?>
			<?php
			if ( ! empty( $instance['icon_padding_size'] ) ) {
				$style .= '; padding:' . esc_attr( $instance['icon_padding_size'] ) . 'px';
			}
			?>

			<?php if ( 'false' === $instance['show_icon_labels'] ) : ?>
			<span class="screen-reader-text"><?php echo esc_html( $field['icon'] ); ?></span>
			<?php endif; ?>

			<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- the SVG comes from the plugin's own icon library. ?>
			<span class="zoom-social_icons-list-span social-icon <?php echo esc_attr( $class ); ?>" <?php echo $hover_style; ?> style="<?php echo $style; ?>" <?php echo $aria_image_role; ?>><?php echo $svg; ?></span>
			<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php
			if ( 'true' === $instance['show_icon_labels'] ) :
				?>
			<span class="zoom-social_icons-list__label"><?php echo esc_html( $field['label'] ); ?></span>
			<?php endif; ?>
		</a>
	</li>

	<?php endforeach; ?>

</ul>

		<?php
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Register scripts & styles.
	 */
	public function register_scripts() {
		wp_register_style(
			'wpzoom-social-icons-socicon',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-socicon.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-socicon.css' )
		);

		wp_register_style(
			'wpzoom-social-icons-styles',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-styles.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-social-icons-styles.css' )
		);

		wp_register_style(
			'wpzoom-social-icons-genericons',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/genericons.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/genericons.css' )
		);

		wp_register_style(
			'wpzoom-social-icons-academicons',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/academicons.min.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/academicons.min.css' )
		);

		wp_register_style(
			'wpzoom-social-icons-font-awesome-3',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-3.min.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/font-awesome-3.min.css' )
		);

		wp_register_script(
			'zoom-social-icons-widget-frontend',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-icons-widget-frontend.js',
			array( 'jquery' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/social-icons-widget-frontend.js' ),
			true
		);
	}

	/**
	 * Scripts & styles for front-end display of widget.
	 */
	public function enqueue_scripts() {
		$disable_css_loading_socicons    = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-socicons' );
		$disable_css_loading_genericons  = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-genericons' );
		$disable_css_loading_academicons = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-academicons' );
		$disable_css_loading_fa3         = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-font-awesome-3' );
		$disable_css_loading_dashicons   = WPZOOM_Social_Icons_Settings::get_option_key( 'disable-css-loading-for-dashicons' );

		// Icons are inline SVG on the front end: one small stylesheet instead
		// of the icon fonts (an icon without an SVG still gets its font).
		if ( WPZOOM_Social_Icons_SVG::enabled() ) {
			WPZOOM_Social_Icons_SVG::enqueue_style();
			$disable_css_loading_socicons    = false;
			$disable_css_loading_genericons  = false;
			$disable_css_loading_academicons = false;
			$disable_css_loading_fa3         = false;
			$disable_css_loading_dashicons   = false;
		}

		if ( ! empty( $disable_css_loading_socicons ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-socicon',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-socicon.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-socicon.css' )
			);
		}

		if ( ! empty( $disable_css_loading_genericons ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-genericons',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/genericons.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/genericons.css' )
			);
		}

		if ( ! empty( $disable_css_loading_academicons ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-academicons',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/academicons.min.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/academicons.min.css' )
			);
		}

		if ( ! empty( $disable_css_loading_fa3 ) ) {
			wp_enqueue_style(
				'wpzoom-social-icons-font-awesome-3',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-3.min.css',
				array(),
				filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/font-awesome-3.min.css' )
			);
		}

		if ( ! empty( $disable_css_loading_dashicons ) ) {
			wp_enqueue_style( 'dashicons' );
		}

		wp_enqueue_style(
			'wpzoom-social-icons-styles',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-styles.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/wpzoom-social-icons-styles.css' )
		);

		wp_enqueue_script(
			'zoom-social-icons-widget-frontend',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/social-icons-widget-frontend.js',
			array( 'jquery' ),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/js/social-icons-widget-frontend.js' ),
			true
		);
	}

}
