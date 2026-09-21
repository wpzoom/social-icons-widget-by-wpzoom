<?php
/**
 * Click-to-Chat Floating Buttons
 *
 * Adds a floating WhatsApp / Telegram / Messenger contact launcher for small businesses.
 *
 * @package WPZOOM_Social_Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Click_To_Chat {

	private static $instance = null;

	const OPTION_KEY = 'wpzoom_click_to_chat_settings';

	private static $defaults = array(
		'enabled'             => false,
		'position_type'       => 'corner',
		'side'                => 'right',
		'launcher_color'      => '#25d366',
		'show_on_mobile'      => true,
		'whatsapp_enabled'    => false,
		'whatsapp_phone'      => '',
		'whatsapp_message'    => '',
		'whatsapp_label'      => '',
		'telegram_enabled'    => false,
		'telegram_username'   => '',
		'telegram_label'      => '',
		'messenger_enabled'   => false,
		'messenger_page'      => '',
		'messenger_label'     => '',
		'viber_enabled'       => false,
		'viber_phone'         => '',
		'viber_label'         => '',
		/** Yamidoo AI Chat as one of the launcher channels (needs the chat connected). */
		'yamidoo_enabled'     => false,
		'yamidoo_label'       => '',
		'yamidoo_icon'        => 'yamidoo',
		'platform_order'      => array( 'yamidoo', 'whatsapp', 'telegram', 'messenger', 'viber' ),
		'open_icon'           => 'chat',
		'open_icon_kit'       => 'svg',
		'close_icon'          => 'close',
		'close_icon_kit'      => 'svg',
		'button_size'         => 'M',
	);

	/**
	 * Launcher icons, drawn in one family so the picker looks like a set:
	 * 24×24, 1.75 stroke for the open icons and a lighter 1.6 for the close
	 * ones, round caps and joins throughout.
	 *
	 * These replaced a mix of Font Awesome 3 and Dashicons glyphs. Inline SVG
	 * means the launcher no longer pulls an icon font just to draw one button,
	 * and the weights actually match each other.
	 */
	const ICON_STROKE       = 'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';
	const ICON_STROKE_LIGHT = 'fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';

	/** The speech bubble every chat icon in the set is built from. */
	const ICON_BUBBLE_PATH = 'M12 4C7.3 4 3.5 7.4 3.5 11.4c0 1.9.9 3.7 2.3 5v3.9l3.7-2c.8.2 1.6.3 2.5.3 4.7 0 8.5-3.4 8.5-7.4S16.7 4 12 4Z';

	private static function svg_icons() {
		$stroke = self::ICON_STROKE;
		$light  = self::ICON_STROKE_LIGHT;
		$bubble = self::ICON_BUBBLE_PATH;

		return array(
			// Open.
			'chat'         => '<path ' . $stroke . ' d="' . $bubble . '"/>',
			'chat-dots'    => '<path ' . $stroke . ' d="' . $bubble . '"/><circle fill="currentColor" cx="8.4" cy="11.4" r="1.05"/><circle fill="currentColor" cx="12" cy="11.4" r="1.05"/><circle fill="currentColor" cx="15.6" cy="11.4" r="1.05"/>',
			'chat-solid'   => '<path fill="currentColor" d="' . $bubble . '"/>',
			'chat-sparkle' => '<path ' . $stroke . ' d="' . $bubble . '"/><path fill="currentColor" d="M12 7.4l.8 3.2 3.2.8-3.2.8-.8 3.2-.8-3.2-3.2-.8 3.2-.8Z"/>',
			'conversation' => '<path ' . $stroke . ' d="M17.6 13.9h1a2.4 2.4 0 0 0 2.4-2.4V6.2a2.4 2.4 0 0 0-2.4-2.4h-8.7a2.4 2.4 0 0 0-2.4 2.4v1"/><path ' . $stroke . ' d="M14.2 7.2H5.5a2.4 2.4 0 0 0-2.4 2.4v5.3a2.4 2.4 0 0 0 2.4 2.4h1.1v3.3l3.6-3.3h4a2.4 2.4 0 0 0 2.4-2.4V9.6a2.4 2.4 0 0 0-2.4-2.4Z"/>',
			'envelope'     => '<rect ' . $stroke . ' x="3" y="5.5" width="18" height="13" rx="2.4"/><path ' . $stroke . ' d="m3.9 7.2 7 5.1c.7.5 1.5.5 2.2 0l7-5.1"/>',
			'phone'        => '<path ' . $stroke . ' d="M7.6 3.9h-2A2.1 2.1 0 0 0 3.5 6c0 7.9 6.6 14.5 14.5 14.5a2.1 2.1 0 0 0 2.1-2.1v-2a1.4 1.4 0 0 0-1.1-1.4l-3.3-.7a1.4 1.4 0 0 0-1.4.5l-1 1.3a11.4 11.4 0 0 1-5.4-5.4l1.3-1a1.4 1.4 0 0 0 .5-1.4L9 5A1.4 1.4 0 0 0 7.6 3.9Z"/>',
			'headset'      => '<path ' . $stroke . ' d="M4.6 14.2v-2.4a7.4 7.4 0 0 1 14.8 0v2.4"/><rect ' . $stroke . ' x="2.7" y="12.4" width="3.9" height="5.8" rx="1.95"/><rect ' . $stroke . ' x="17.4" y="12.4" width="3.9" height="5.8" rx="1.95"/><path ' . $stroke . ' d="M19.35 18.2v.6a2.4 2.4 0 0 1-2.4 2.4h-2.5"/>',
			'question'     => '<circle ' . $stroke . ' cx="12" cy="12" r="8.4"/><path ' . $stroke . ' d="M9.7 9.6a2.4 2.4 0 1 1 3.1 2.8c-.5.2-.8.7-.8 1.2v.5"/><circle fill="currentColor" cx="12" cy="16.6" r="1.05"/>',

			// The Yamidoo mark, for the AI Chat channel. Drawn in its own
			// coordinate space — see icon_viewbox().
			'yamidoo'      => '<path fill="currentColor" d="M113.5 0C176.184 0 227 50.9256 227 113.745C227 176.565 168.555 250 105.87 250C102.47 250 99.104 249.849 95.7793 249.555C94.4106 249.434 93.604 247.981 94.1956 246.741L179.28 68.3939H148.899L124.797 117.11C122.636 121.44 120.61 125.635 118.72 129.695C116.965 133.619 115.277 137.949 113.657 142.685C112.037 137.949 110.348 133.619 108.593 129.695C106.838 125.635 104.88 121.44 102.719 117.11L79.0217 68.3939H47.2231L97.2192 170.011C97.4945 170.571 97.493 171.227 97.2151 171.785L73.9774 218.477C73.5189 219.398 72.4338 219.822 71.4783 219.44C29.5968 202.702 0 161.688 0 113.745C0 50.9256 50.8157 0 113.5 0Z"/>',

			// Close.
			'close'        => '<path ' . $light . ' d="m7.4 7.4 9.2 9.2M16.6 7.4l-9.2 9.2"/>',
			'chevron-down' => '<path ' . $light . ' d="m6.6 9.9 5.4 5.1 5.4-5.1"/>',
			'arrow-down'   => '<path ' . $light . ' d="M12 5.6v12.8M6.9 13.3 12 18.4l5.1-5.1"/>',
			'minus'        => '<path ' . $light . ' d="M6.2 12h11.6"/>',
		);
	}

	/** Icons are 24×24 apart from the logo mark, which keeps its own space. */
	private static function icon_viewbox( $icon ) {
		return 'yamidoo' === $icon ? '-12 0 251 250' : '0 0 24 24';
	}

	/**
	 * Icons offered for the AI Chat channel button.
	 *
	 * The other channels are locked to their platform's mark — a WhatsApp button
	 * has to look like WhatsApp. This one has no such constraint, so a site that
	 * would rather show a plain chat bubble than the Yamidoo logo can.
	 */
	public static function channel_icon_choices() {
		return array(
			'yamidoo'      => __( 'Yamidoo mark', 'social-icons-widget-by-wpzoom' ),
			'chat'         => __( 'Chat', 'social-icons-widget-by-wpzoom' ),
			'chat-solid'   => __( 'Chat, solid', 'social-icons-widget-by-wpzoom' ),
			'chat-dots'    => __( 'Chat with dots', 'social-icons-widget-by-wpzoom' ),
			'chat-sparkle' => __( 'AI chat', 'social-icons-widget-by-wpzoom' ),
			'conversation' => __( 'Conversation', 'social-icons-widget-by-wpzoom' ),
			'headset'      => __( 'Headset', 'social-icons-widget-by-wpzoom' ),
			'question'     => __( 'Question', 'social-icons-widget-by-wpzoom' ),
		);
	}

	/** Public wrapper around icon_span() for one SVG icon by key. */
	public static function icon_markup( $icon ) {
		return self::icon_span( 'svg', $icon );
	}

	/** The AI Chat channel's chosen icon, falling back to the Yamidoo mark. */
	public static function channel_icon( $s ) {
		$icon = $s['yamidoo_icon'] ?? 'yamidoo';
		if ( ! isset( self::channel_icon_choices()[ $icon ] ) ) {
			$icon = 'yamidoo';
		}
		return self::icon_span( 'svg', $icon );
	}

	private static function open_icon_choices() {
		return array(
			array( 'kit' => 'svg', 'icon' => 'chat',         'label' => __( 'Chat', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'chat-dots',    'label' => __( 'Chat with dots', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'chat-solid',   'label' => __( 'Chat, solid', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'chat-sparkle', 'label' => __( 'AI chat', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'conversation', 'label' => __( 'Conversation', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'envelope',     'label' => __( 'Envelope', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'phone',        'label' => __( 'Phone', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'headset',      'label' => __( 'Headset', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'question',     'label' => __( 'Question', 'social-icons-widget-by-wpzoom' ) ),
		);
	}

	private static function close_icon_choices() {
		return array(
			array( 'kit' => 'svg', 'icon' => 'close',        'label' => __( 'Close', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'chevron-down', 'label' => __( 'Chevron down', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'arrow-down',   'label' => __( 'Arrow down', 'social-icons-widget-by-wpzoom' ) ),
			array( 'kit' => 'svg', 'icon' => 'minus',        'label' => __( 'Minus', 'social-icons-widget-by-wpzoom' ) ),
		);
	}

	/**
	 * Font Awesome 3 / Dashicons values saved by earlier versions, mapped to
	 * their closest icon in the new set. Applied when settings are read, so a
	 * site that never revisits the screen still gets the redrawn launcher and
	 * stops loading an icon font for it.
	 */
	private static $legacy_icon_map = array(
		'fa:comment'                   => 'chat',
		'fa:commenting'                => 'chat-dots',
		'fa:comments'                  => 'conversation',
		'fa:envelope'                  => 'envelope',
		'fa:phone'                     => 'phone',
		'fa:headphones'                => 'headset',
		'dashicons:format-chat'        => 'chat',
		'dashicons:admin-comments'     => 'chat',
		'dashicons:welcome-comments'   => 'conversation',
		'fa:times'                     => 'close',
		'fa:times-circle'              => 'close',
		'fa:minus'                     => 'minus',
		'fa:chevron-down'              => 'chevron-down',
		'fa:angle-down'                => 'chevron-down',
		'dashicons:no'                 => 'close',
		'dashicons:no-alt'             => 'close',
	);

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 23 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_frontend_widget' ) );
		add_action( 'wp_ajax_wpzoom_ctc_toggle_enabled', array( $this, 'ajax_toggle_enabled' ) );
	}

	public function ajax_toggle_enabled() {
		check_ajax_referer( 'wpzoom_ctc_toggle_enabled', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}
		$s            = self::get_settings();
		$s['enabled'] = ! empty( $_POST['enabled'] );
		update_option( self::OPTION_KEY, $s );
		wp_send_json_success();
	}

	public static function get_settings() {
		$saved    = get_option( self::OPTION_KEY, array() );
		$settings = wp_parse_args( $saved, self::$defaults );

		$settings = self::migrate_icons( $settings );

		// Ensure any newly added platform appears in the order.
		$all_known = self::platform_keys();
		foreach ( $all_known as $key ) {
			if ( ! in_array( $key, $settings['platform_order'], true ) ) {
				$settings['platform_order'][] = $key;
			}
		}

		return $settings;
	}

	/** Every channel the launcher knows about, in default order. */
	public static function platform_keys() {
		return array( 'yamidoo', 'whatsapp', 'telegram', 'messenger', 'viber' );
	}

	/**
	 * The hover label a channel falls back to when the owner has not set one:
	 * just the platform's name.
	 *
	 * @param string $key Platform key.
	 */
	public static function default_label( $key ) {
		$defaults = array(
			'yamidoo'   => __( 'AI Chat', 'social-icons-widget-by-wpzoom' ),
			'whatsapp'  => __( 'WhatsApp', 'social-icons-widget-by-wpzoom' ),
			'telegram'  => __( 'Telegram', 'social-icons-widget-by-wpzoom' ),
			'messenger' => __( 'Messenger', 'social-icons-widget-by-wpzoom' ),
			'viber'     => __( 'Viber', 'social-icons-widget-by-wpzoom' ),
		);
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	/**
	 * The label actually shown for a channel: the owner's wording, or the
	 * platform name when they left the field empty.
	 *
	 * @param string $key Platform key.
	 * @param array  $s   Settings.
	 */
	public static function label_for( $key, $s ) {
		$custom = isset( $s[ $key . '_label' ] ) ? trim( (string) $s[ $key . '_label' ] ) : '';
		return '' !== $custom ? $custom : self::default_label( $key );
	}

	/**
	 * Whether the AI Chat channel can be offered: the Yamidoo widget has to be
	 * on the front end already, embedded either by this plugin's AI Chat tab or
	 * by the standalone Yamidoo plugin. Without it the button has nothing to open.
	 */
	public static function ai_chat_available() {
		if ( ! class_exists( 'WPZOOM_AI_Chat' ) ) {
			return false;
		}
		return WPZOOM_AI_Chat::widget_on_front_end();
	}

	/**
	 * Whether the AI Chat button actually renders in the launcher right now.
	 *
	 * WPZOOM_AI_Chat reads this to know it must leave the launcher alone: the
	 * launcher is the only way into the chat once its own bubble is hidden.
	 */
	public static function yamidoo_channel_active() {
		$s = self::get_settings();
		return ! empty( $s['enabled'] ) && ! empty( $s['yamidoo_enabled'] ) && self::ai_chat_available();
	}

	/**
	 * Whether the front end will show two floating buttons: this launcher and
	 * the Yamidoo bubble, each in the corner, with the chat not folded in.
	 *
	 * Mirrors what the front end does rather than re-deriving it: the launcher
	 * renders when enabled and nothing filters it out, and the bubble renders
	 * when either plugin embeds the widget. Without the AI Chat channel on,
	 * that is two buttons.
	 *
	 * @return string|false 'standalone' when the Yamidoo plugin owns the embed
	 *                      (this plugin cannot hide the launcher for it),
	 *                      'hide_off' when this plugin's AI Chat is on but the
	 *                      "hide the launcher" setting was turned off, false
	 *                      when there is no collision.
	 */
	public static function collides_with_ai_chat( $s = null ) {
		if ( null === $s ) {
			$s = self::get_settings();
		}
		if ( empty( $s['enabled'] ) || ! self::ai_chat_available() ) {
			return false;
		}
		if ( ! empty( $s['yamidoo_enabled'] ) ) {
			return false; // The chat is one of the launcher's channels — one button.
		}
		if ( ! apply_filters( 'wpzoom_ctc_should_render', true ) ) {
			return false; // AI Chat already hides the launcher.
		}
		return WPZOOM_AI_Chat::standalone_plugin_active() ? 'standalone' : 'hide_off';
	}

	/** The Yamidoo mark, used for the launcher button and the admin card. */
	private static function yamidoo_icon() {
		return '<svg viewBox="-12 0 251 250" fill="currentColor" aria-hidden="true"><path d="M113.5 0C176.184 0 227 50.9256 227 113.745C227 176.565 168.555 250 105.87 250C102.47 250 99.104 249.849 95.7793 249.555C94.4106 249.434 93.604 247.981 94.1956 246.741L179.28 68.3939H148.899L124.797 117.11C122.636 121.44 120.61 125.635 118.72 129.695C116.965 133.619 115.277 137.949 113.657 142.685C112.037 137.949 110.348 133.619 108.593 129.695C106.838 125.635 104.88 121.44 102.719 117.11L79.0217 68.3939H47.2231L97.2192 170.011C97.4945 170.571 97.493 171.227 97.2151 171.785L73.9774 218.477C73.5189 219.398 72.4338 219.822 71.4783 219.44C29.5968 202.702 0 161.688 0 113.745C0 50.9256 50.8157 0 113.5 0Z"/></svg>';
	}

	// -------------------------------------------------------------------------
	// Admin
	// -------------------------------------------------------------------------

	public function add_menu_item() {
		// "New" badge in the menu until AI Chat is connected — same treatment as the Pro badge.
		$badge = apply_filters( 'wpzoom_ai_chat_is_connected', false ) ? '' : ' <span class="wpzoom-pro-badge wpzoom-new-badge">New</span>';
		add_submenu_page(
			'edit.php?post_type=wpzoom-shortcode',
			__( 'AI Chat', 'social-icons-widget-by-wpzoom' ),
			__( 'AI Chat', 'social-icons-widget-by-wpzoom' ) . $badge,
			'manage_options',
			'wpzoom-click-to-chat',
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'wpzoom-shortcode_page_wpzoom-click-to-chat' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'dashicons' );
		// FA3 for the icon picker preview.
		wp_enqueue_style(
			'wpzoom-social-icons-font-awesome-3',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-3.min.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);
		wp_enqueue_style(
			'wpzoom-social-icons-styles',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-styles.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);
		wp_enqueue_script(
			'wpzoom-ctc-sortable',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/sortable.min.js',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
			true
		);
		wp_enqueue_style(
			'wpzoom-click-to-chat-admin',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-click-to-chat-admin.css',
			array( 'wp-color-picker' ),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);
	}

	private function sanitize_platform_order( $raw ) {
		$allowed = self::platform_keys();
		$order   = array_filter( array_map( 'sanitize_key', explode( ',', $raw ) ), function( $v ) use ( $allowed ) {
			return in_array( $v, $allowed, true );
		} );
		$order = array_values( $order );
		// Ensure every known platform is present (append missing ones).
		foreach ( $allowed as $p ) {
			if ( ! in_array( $p, $order, true ) ) {
				$order[] = $p;
			}
		}
		return $order;
	}

	/** Point a saved Font Awesome / Dashicons launcher icon at its replacement. */
	private static function migrate_icons( $settings ) {
		foreach ( array( 'open', 'close' ) as $which ) {
			$kit_key  = $which . '_icon_kit';
			$icon_key = $which . '_icon';
			if ( 'svg' === ( $settings[ $kit_key ] ?? '' ) ) {
				continue;
			}
			$lookup = ( $settings[ $kit_key ] ?? '' ) . ':' . ( $settings[ $icon_key ] ?? '' );
			if ( isset( self::$legacy_icon_map[ $lookup ] ) ) {
				$settings[ $icon_key ] = self::$legacy_icon_map[ $lookup ];
				$settings[ $kit_key ]  = 'svg';
			}
		}
		return $settings;
	}

	private static function icon_span( $kit, $icon ) {
		if ( 'svg' === $kit ) {
			$icons = self::svg_icons();
			if ( ! isset( $icons[ $icon ] ) ) {
				$icon = 'chat';
			}
			return '<svg viewBox="' . self::icon_viewbox( $icon ) . '" aria-hidden="true">' . $icons[ $icon ] . '</svg>';
		}
		if ( 'dashicons' === $kit ) {
			return '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
		}
		// fa kit — only reached by a saved value with no mapping.
		return '<span class="social-icon fa fa-' . esc_attr( $icon ) . '"></span>';
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = '';
		if ( isset( $_POST['wpzoom_ctc_save'] ) && check_admin_referer( 'wpzoom_ctc_save' ) ) {
			$position_type = isset( $_POST['ctc_position_type'] ) && in_array( $_POST['ctc_position_type'], array( 'corner', 'sidebar' ), true )
				? $_POST['ctc_position_type'] : 'corner';
			$side = isset( $_POST['ctc_side'] ) && in_array( $_POST['ctc_side'], array( 'left', 'right' ), true )
				? $_POST['ctc_side'] : 'right';
			$color = isset( $_POST['ctc_launcher_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['ctc_launcher_color'] ) ) : '';

			$valid_kits = array( 'svg', 'fa', 'dashicons' );
			$open_icon_kit   = isset( $_POST['ctc_open_icon_kit'] ) && in_array( $_POST['ctc_open_icon_kit'], $valid_kits, true ) ? $_POST['ctc_open_icon_kit'] : 'svg';
			$close_icon_kit  = isset( $_POST['ctc_close_icon_kit'] ) && in_array( $_POST['ctc_close_icon_kit'], $valid_kits, true ) ? $_POST['ctc_close_icon_kit'] : 'svg';

			$valid_sizes = array( 'S', 'M', 'L', 'XL', 'XXL' );
			$button_size = isset( $_POST['ctc_button_size'] ) && in_array( $_POST['ctc_button_size'], $valid_sizes, true )
				? $_POST['ctc_button_size'] : 'M';

			$data = array(
				'enabled'           => ! empty( $_POST['ctc_enabled'] ),
				'position_type'     => $position_type,
				'side'              => $side,
				'launcher_color'    => $color ?: '#25d366',
				'show_on_mobile'    => ! empty( $_POST['ctc_show_on_mobile'] ),
				'whatsapp_enabled'  => ! empty( $_POST['ctc_whatsapp_enabled'] ),
				'whatsapp_phone'    => preg_replace( '/[^\d+]/', '', isset( $_POST['ctc_whatsapp_phone'] ) ? wp_unslash( $_POST['ctc_whatsapp_phone'] ) : '' ),
				'whatsapp_message'  => sanitize_textarea_field( isset( $_POST['ctc_whatsapp_message'] ) ? wp_unslash( $_POST['ctc_whatsapp_message'] ) : '' ),
				'telegram_enabled'  => ! empty( $_POST['ctc_telegram_enabled'] ),
				'telegram_username' => sanitize_text_field( isset( $_POST['ctc_telegram_username'] ) ? wp_unslash( $_POST['ctc_telegram_username'] ) : '' ),
				'messenger_enabled' => ! empty( $_POST['ctc_messenger_enabled'] ),
				'messenger_page'    => sanitize_text_field( isset( $_POST['ctc_messenger_page'] ) ? wp_unslash( $_POST['ctc_messenger_page'] ) : '' ),
				'yamidoo_enabled'   => ! empty( $_POST['ctc_yamidoo_enabled'] ) && self::ai_chat_available(),
				'viber_enabled'     => ! empty( $_POST['ctc_viber_enabled'] ),
				'viber_phone'       => preg_replace( '/[^\d+]/', '', isset( $_POST['ctc_viber_phone'] ) ? wp_unslash( $_POST['ctc_viber_phone'] ) : '' ),
				'open_icon'         => sanitize_key( isset( $_POST['ctc_open_icon'] ) ? wp_unslash( $_POST['ctc_open_icon'] ) : 'chat' ),
				'open_icon_kit'     => $open_icon_kit,
				'close_icon'        => sanitize_key( isset( $_POST['ctc_close_icon'] ) ? wp_unslash( $_POST['ctc_close_icon'] ) : 'close' ),
				'close_icon_kit'    => $close_icon_kit,
				'button_size'        => $button_size,
				'platform_order'     => $this->sanitize_platform_order( isset( $_POST['ctc_platform_order'] ) ? wp_unslash( $_POST['ctc_platform_order'] ) : '' ),
			);

			$icon_choices           = self::channel_icon_choices();
			$posted_icon            = isset( $_POST['ctc_yamidoo_icon'] ) ? sanitize_key( wp_unslash( $_POST['ctc_yamidoo_icon'] ) ) : '';
			$data['yamidoo_icon']   = isset( $icon_choices[ $posted_icon ] ) ? $posted_icon : 'yamidoo';

			// Hover labels — empty stays empty so the platform name keeps showing
			// (and keeps following the site language).
			foreach ( self::platform_keys() as $pkey ) {
				$field = 'ctc_' . $pkey . '_label';
				$data[ $pkey . '_label' ] = isset( $_POST[ $field ] )
					? sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
					: '';
			}

			update_option( self::OPTION_KEY, $data );
			$notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'social-icons-widget-by-wpzoom' ) . '</p></div>';
		}

		$s = self::get_settings();

		// Two tabs: AI Chat (Yamidoo) and the classic Click to Chat launcher. The
		// menu item is "AI Chat", so that tab is always the landing one; Click to
		// Chat links carry &tab=ctc (its form posts back to the same URL).
		$tabs = array(
			'ai'  => __( 'AI Chat', 'social-icons-widget-by-wpzoom' ),
			'ctc' => __( 'Click to Chat', 'social-icons-widget-by-wpzoom' ),
		);
		$tabs = apply_filters( 'wpzoom_chat_admin_tabs', $tabs );
		$default_tab = 'ai';
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $default_tab; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = $default_tab;
		}
		$base_url = admin_url( 'edit.php?post_type=wpzoom-shortcode&page=wpzoom-click-to-chat' );
		?>
		<div class="wrap wpzoom-ctc-admin-wrap">
			<h1><?php esc_html_e( 'AI Chat & Click to Chat', 'social-icons-widget-by-wpzoom' ); ?></h1>
			<p class="wpzoom-ctc-admin-description">
				<?php esc_html_e( 'One floating button for every way visitors reach you: an AI chat that answers from your pages, or one-tap buttons for WhatsApp, Telegram, Messenger and Viber.', 'social-icons-widget-by-wpzoom' ); ?>
			</p>

			<nav class="nav-tab-wrapper wpzoom-chat-tabs" aria-label="<?php esc_attr_e( 'Chat settings', 'social-icons-widget-by-wpzoom' ); ?>">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $key, $base_url ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
						<?php if ( 'ai' === $key && ! apply_filters( 'wpzoom_ai_chat_is_connected', false ) ) : ?>
							<span class="wpzoom-chat-tab-badge"><?php esc_html_e( 'New', 'social-icons-widget-by-wpzoom' ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'ctc' !== $tab ) : ?>
				<?php
				/**
				 * Content of a non-default tab (AI Chat renders on 'ai').
				 *
				 * @param string $tab Active tab key.
				 */
				do_action( 'wpzoom_chat_admin_tab', $tab );
				?>
		</div>
		<?php
			return;
		endif;
		?>

			<?php echo $notice; // phpcs:ignore -- escaped above ?>

			<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'ctc', $base_url ) ); ?>">
				<?php wp_nonce_field( 'wpzoom_ctc_save' ); ?>

				<!-- Enable toggle -->
				<div class="wpzoom-ctc-card wpzoom-ctc-card--top">
					<label class="wpzoom-ctc-toggle-row">
						<span class="wpzoom-ctc-toggle-label"><?php esc_html_e( 'Enable Click to Chat', 'social-icons-widget-by-wpzoom' ); ?></span>
						<span class="wpzoom-ctc-toggle-switch">
							<input type="checkbox" name="ctc_enabled" id="ctc_enabled" value="1" <?php checked( $s['enabled'] ); ?>>
							<span class="wpzoom-ctc-slider"></span>
						</span>
					</label>
				</div>

				<?php $collision = self::collides_with_ai_chat( $s ); ?>
				<?php if ( $collision ) : ?>
				<div class="notice notice-warning inline wpzoom-ctc-collision" id="ctc-collision-notice">
					<p>
						<strong><?php esc_html_e( 'Visitors currently see two floating chat buttons.', 'social-icons-widget-by-wpzoom' ); ?></strong>
						<?php
						if ( 'standalone' === $collision ) {
							esc_html_e( 'The Yamidoo plugin is showing its chat bubble on the front end, and this launcher is on as well, so they sit on top of each other in the corner.', 'social-icons-widget-by-wpzoom' );
						} else {
							esc_html_e( 'AI Chat is on and set to show alongside this launcher, so its bubble and these buttons sit on top of each other in the corner.', 'social-icons-widget-by-wpzoom' );
						}
						?>
					</p>
					<p>
						<?php esc_html_e( 'Add AI Chat as one of the channels here and the chat opens from this launcher instead — one button, with WhatsApp and the rest a tap away. Or turn Click to Chat off above.', 'social-icons-widget-by-wpzoom' ); ?>
						<?php if ( 'hide_off' === $collision ) : ?>
							<?php
							printf(
								/* translators: %s: link to the AI Chat tab */
								esc_html__( 'You can also switch "Hide the Click to Chat launcher while AI Chat is on" back on in the %s.', 'social-icons-widget-by-wpzoom' ),
								'<a href="' . esc_url( add_query_arg( 'tab', 'ai', $base_url ) ) . '">' . esc_html__( 'AI Chat tab', 'social-icons-widget-by-wpzoom' ) . '</a>'
							);
							?>
						<?php endif; ?>
					</p>
					<p>
						<button type="button" class="button button-primary" id="ctc-collision-add-ai">
							<?php esc_html_e( 'Add AI Chat to this launcher', 'social-icons-widget-by-wpzoom' ); ?>
						</button>
						<span class="description"><?php esc_html_e( 'Switches the channel on below — remember to save.', 'social-icons-widget-by-wpzoom' ); ?></span>
					</p>
				</div>
				<?php endif; ?>

				<div class="wpzoom-ctc-two-col" <?php echo empty( $s['enabled'] ) ? 'style="display:none"' : ''; ?>>

					<!-- Left column: platforms (drag to reorder) -->
					<div class="wpzoom-ctc-col">

						<input type="hidden" name="ctc_platform_order" id="ctc_platform_order"
							value="<?php echo esc_attr( implode( ',', $s['platform_order'] ) ); ?>">

						<?php
						$drag_handle = '<span class="wpzoom-ctc-drag-handle" title="' . esc_attr__( 'Drag to reorder', 'social-icons-widget-by-wpzoom' ) . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm8-12a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg></span>';

						$ai_ready      = self::ai_chat_available();
						$ai_standalone = class_exists( 'WPZOOM_AI_Chat' ) && WPZOOM_AI_Chat::standalone_plugin_active();
						$ai_settings   = class_exists( 'WPZOOM_AI_Chat' ) ? WPZOOM_AI_Chat::owner_settings_url() : '';

						$platform_defs = array(
							'yamidoo' => array(
								'name'         => __( 'AI Chat', 'social-icons-widget-by-wpzoom' ),
								'header_class' => 'wpzoom-ctc-platform-header--yamidoo',
								'icon'         => self::yamidoo_icon(),
								'fields'       => function() use ( $ai_ready, $ai_standalone, $ai_settings, $s ) { ?>
									<?php if ( $ai_ready ) : ?>
										<div class="wpzoom-ctc-field">
											<label><?php esc_html_e( 'Button Icon', 'social-icons-widget-by-wpzoom' ); ?></label>
											<div class="wpzoom-ctc-icon-grid" id="ctc-yamidoo-icon-grid">
												<?php
												$chosen = $s['yamidoo_icon'] ?? 'yamidoo';
												foreach ( WPZOOM_Click_To_Chat::channel_icon_choices() as $ikey => $ilabel ) :
													?>
													<label class="wpzoom-ctc-icon-choice <?php echo $chosen === $ikey ? 'is-selected' : ''; ?>" title="<?php echo esc_attr( $ilabel ); ?>">
														<input type="radio" name="ctc_yamidoo_icon" value="<?php echo esc_attr( $ikey ); ?>" <?php checked( $chosen, $ikey ); ?>>
														<?php echo WPZOOM_Click_To_Chat::icon_markup( $ikey ); // phpcs:ignore -- hardcoded SVG ?>
													</label>
												<?php endforeach; ?>
											</div>
											<p class="description"><?php esc_html_e( 'Pick the Yamidoo mark, or a plain chat icon if you would rather not show the logo.', 'social-icons-widget-by-wpzoom' ); ?></p>
										</div>
										<p class="description">
											<?php esc_html_e( 'Adds your Yamidoo AI chat to the launcher, next to the other channels. Its own floating bubble is hidden, so visitors see one button: they can ask the AI first and reach you on WhatsApp or Viber if they would rather talk to a person.', 'social-icons-widget-by-wpzoom' ); ?>
										</p>
										<p class="description">
											<?php
											printf(
												/* translators: %s: link to the screen where the chat connection is managed. */
												esc_html__( 'The chat itself is set up on %s.', 'social-icons-widget-by-wpzoom' ),
												'<a href="' . esc_url( $ai_settings ) . '">' . (
													$ai_standalone
														? esc_html__( 'the Yamidoo plugin settings page', 'social-icons-widget-by-wpzoom' )
														: esc_html__( 'the AI Chat tab', 'social-icons-widget-by-wpzoom' )
												) . ' &rarr;</a>'
											);
											?>
										</p>
									<?php else : ?>
										<p class="description">
											<?php esc_html_e( 'Connect the AI chat first — this channel opens the Yamidoo chat widget, so it needs a chat to open.', 'social-icons-widget-by-wpzoom' ); ?>
										</p>
										<?php if ( $ai_settings ) : ?>
										<p>
											<a class="button button-secondary" href="<?php echo esc_url( $ai_settings ); ?>">
												<?php
												echo $ai_standalone
													? esc_html__( 'Open Yamidoo settings', 'social-icons-widget-by-wpzoom' )
													: esc_html__( 'Set up AI Chat', 'social-icons-widget-by-wpzoom' );
												?>
											</a>
										</p>
										<?php endif; ?>
									<?php endif; ?>
								<?php },
								'enabled_key'  => 'yamidoo_enabled',
								'toggle_name'  => 'ctc_yamidoo_enabled',
								'disabled'     => ! $ai_ready,
							),
							'whatsapp' => array(
								'name'        => 'WhatsApp',
								'header_class' => 'wpzoom-ctc-platform-header--whatsapp',
								'icon'        => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
								'fields'      => function() use ( $s ) { ?>
									<div class="wpzoom-ctc-field">
										<label for="ctc_whatsapp_phone"><?php esc_html_e( 'Phone Number', 'social-icons-widget-by-wpzoom' ); ?></label>
										<input type="tel" id="ctc_whatsapp_phone" name="ctc_whatsapp_phone" value="<?php echo esc_attr( $s['whatsapp_phone'] ); ?>" placeholder="+1234567890" class="regular-text">
										<p class="description"><?php esc_html_e( 'Include country code, e.g. +15551234567', 'social-icons-widget-by-wpzoom' ); ?></p>
									</div>
									<div class="wpzoom-ctc-field">
										<label for="ctc_whatsapp_message"><?php esc_html_e( 'Pre-filled Message', 'social-icons-widget-by-wpzoom' ); ?></label>
										<textarea id="ctc_whatsapp_message" name="ctc_whatsapp_message" rows="3" class="regular-text"><?php echo esc_textarea( $s['whatsapp_message'] ); ?></textarea>
										<p class="description"><?php esc_html_e( 'This message will pre-fill the WhatsApp chat window.', 'social-icons-widget-by-wpzoom' ); ?></p>
									</div>
								<?php },
								'enabled_key' => 'whatsapp_enabled',
								'toggle_name' => 'ctc_whatsapp_enabled',
							),
							'telegram' => array(
								'name'        => 'Telegram',
								'header_class' => 'wpzoom-ctc-platform-header--telegram',
								'icon'        => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
								'fields'      => function() use ( $s ) { ?>
									<div class="wpzoom-ctc-field">
										<label for="ctc_telegram_username"><?php esc_html_e( 'Username or Bot', 'social-icons-widget-by-wpzoom' ); ?></label>
										<div class="wpzoom-ctc-input-prefix">
											<span>t.me/</span>
											<input type="text" id="ctc_telegram_username" name="ctc_telegram_username" value="<?php echo esc_attr( $s['telegram_username'] ); ?>" placeholder="yourusername" class="regular-text">
										</div>
									</div>
								<?php },
								'enabled_key' => 'telegram_enabled',
								'toggle_name' => 'ctc_telegram_enabled',
							),
							'messenger' => array(
								'name'        => 'Facebook Messenger',
								'header_class' => 'wpzoom-ctc-platform-header--messenger',
								'icon'        => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.4l3.131 3.259L19.752 8.4l-6.561 6.563z"/></svg>',
								'fields'      => function() use ( $s ) { ?>
									<div class="wpzoom-ctc-field">
										<label for="ctc_messenger_page"><?php esc_html_e( 'Facebook Page Username', 'social-icons-widget-by-wpzoom' ); ?></label>
										<div class="wpzoom-ctc-input-prefix">
											<span>m.me/</span>
											<input type="text" id="ctc_messenger_page" name="ctc_messenger_page" value="<?php echo esc_attr( $s['messenger_page'] ); ?>" placeholder="yourpage" class="regular-text">
										</div>
										<p class="description"><?php esc_html_e( 'Enter your Facebook Page username or ID.', 'social-icons-widget-by-wpzoom' ); ?></p>
									</div>
								<?php },
								'enabled_key' => 'messenger_enabled',
								'toggle_name' => 'ctc_messenger_enabled',
							),
							'viber' => array(
								'name'         => 'Viber',
								'header_class' => 'wpzoom-ctc-platform-header--viber',
								'icon'         => '<svg viewBox="0 0 512 512" width="22" height="22"><path fill-rule="evenodd" fill="#fff" d="M95 232c0-91 17-147 161-147s161 56 161 147-17 147-161 147l-26-1-53 63c-4 4-8 1-8-3v-69c-6 0-31-12-38-19-22-23-36-40-36-118zm-30 0c0-126 55-177 191-177s191 51 191 177-55 177-191 177c-10 0-18 0-32-2l-38 43c-7 8-28 11-28-13v-42c-6 0-20-6-39-18-19-13-54-44-54-145zm223 42q10-13 24-4l36 27q8 10-7 28t-28 15q-53-12-102-60t-61-104q0-20 25-34 13-9 22 5l25 35q6 12-7 22c-39 15 51 112 73 70z"/><path fill="none" stroke="#fff" stroke-linecap="round" stroke-width="10" d="M269 186a30 30 0 0 1 31 31m-38-58a64 64 0 0 1 64 67m-73-93a97 97 0 0 1 99 104"/></svg>',
								'fields'       => function() use ( $s ) { ?>
									<div class="wpzoom-ctc-field">
										<label for="ctc_viber_phone"><?php esc_html_e( 'Phone Number', 'social-icons-widget-by-wpzoom' ); ?></label>
										<input type="tel" id="ctc_viber_phone" name="ctc_viber_phone" value="<?php echo esc_attr( $s['viber_phone'] ); ?>" placeholder="+1234567890" class="regular-text">
										<p class="description"><?php esc_html_e( 'Include country code, e.g. +15551234567', 'social-icons-widget-by-wpzoom' ); ?></p>
									</div>
								<?php },
								'enabled_key' => 'viber_enabled',
								'toggle_name' => 'ctc_viber_enabled',
							),
						);

						$ordered_keys = ! empty( $s['platform_order'] ) ? $s['platform_order'] : array_keys( $platform_defs );
						?>

						<div id="wpzoom-ctc-platforms-sortable">
						<?php foreach ( $ordered_keys as $key ) :
							if ( ! isset( $platform_defs[ $key ] ) ) continue;
							$p = $platform_defs[ $key ];
							?>
							<div class="wpzoom-ctc-card wpzoom-ctc-platform-card <?php echo ! empty( $p['disabled'] ) ? 'wpzoom-ctc-platform-card--unavailable' : ''; ?>" data-platform="<?php echo esc_attr( $key ); ?>">
								<div class="wpzoom-ctc-platform-header <?php echo esc_attr( $p['header_class'] ); ?>">
									<?php echo $drag_handle; // phpcs:ignore ?>
									<span class="wpzoom-ctc-platform-icon">
										<?php echo $p['icon']; // phpcs:ignore ?>
									</span>
									<span class="wpzoom-ctc-platform-name"><?php echo esc_html( $p['name'] ); ?></span>
									<label class="wpzoom-ctc-toggle-switch wpzoom-ctc-platform-toggle">
										<input type="checkbox" name="<?php echo esc_attr( $p['toggle_name'] ); ?>" value="1" <?php checked( ! empty( $p['disabled'] ) ? false : ( $s[ $p['enabled_key'] ] ?? false ) ); ?> <?php disabled( ! empty( $p['disabled'] ) ); ?>>
										<span class="wpzoom-ctc-slider"></span>
									</label>
								</div>
								<?php
								// An unavailable channel keeps its panel open: that panel is where the
								// "connect the chat first" explanation lives.
								$fields_hidden = empty( $p['disabled'] ) && empty( $s[ $p['enabled_key'] ] );
								?>
								<div class="wpzoom-ctc-platform-fields" <?php echo $fields_hidden ? 'style="display:none"' : ''; ?>>
									<?php call_user_func( $p['fields'] ); ?>
									<div class="wpzoom-ctc-field">
										<label for="ctc_<?php echo esc_attr( $key ); ?>_label"><?php esc_html_e( 'Label', 'social-icons-widget-by-wpzoom' ); ?></label>
										<input type="text"
										       id="ctc_<?php echo esc_attr( $key ); ?>_label"
										       name="ctc_<?php echo esc_attr( $key ); ?>_label"
										       class="regular-text wpzoom-ctc-label-input"
										       data-platform-label="<?php echo esc_attr( $key ); ?>"
										       value="<?php echo esc_attr( $s[ $key . '_label' ] ?? '' ); ?>"
										       placeholder="<?php echo esc_attr( self::default_label( $key ) ); ?>">
										<p class="description"><?php esc_html_e( 'Shown next to the button when a visitor hovers over it. Leave empty to use the platform name.', 'social-icons-widget-by-wpzoom' ); ?></p>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
						</div>

					</div><!-- /.wpzoom-ctc-col -->

					<!-- Right column: display settings -->
					<div class="wpzoom-ctc-col">

						<div class="wpzoom-ctc-card">
							<h2 class="wpzoom-ctc-card-title"><?php esc_html_e( 'Display Settings', 'social-icons-widget-by-wpzoom' ); ?></h2>

							<div class="wpzoom-ctc-field">
								<label><?php esc_html_e( 'Button Style', 'social-icons-widget-by-wpzoom' ); ?></label>

								<!-- Pill toggles -->
								<div class="wpzoom-ctc-size-picker">
									<label class="wpzoom-ctc-size-option <?php echo 'corner' === $s['position_type'] ? 'is-selected' : ''; ?>">
										<input type="radio" name="ctc_position_type" value="corner" <?php checked( $s['position_type'], 'corner' ); ?>>
										<?php esc_html_e( 'Corner Launcher', 'social-icons-widget-by-wpzoom' ); ?>
									</label>
									<label class="wpzoom-ctc-size-option <?php echo 'sidebar' === $s['position_type'] ? 'is-selected' : ''; ?>">
										<input type="radio" name="ctc_position_type" value="sidebar" <?php checked( $s['position_type'], 'sidebar' ); ?>>
										<?php esc_html_e( 'Sidebar Strip', 'social-icons-widget-by-wpzoom' ); ?>
									</label>
								</div>

								<!-- Large preview -->
								<?php
								// Build preview — always render all platforms in saved order; hide disabled ones via inline style.
								$all_platforms_map = array(
									'yamidoo'   => array( 'key' => 'yamidoo',   'color' => '#fe551b', 'icon' => self::channel_icon( $s ) ),
									'whatsapp'  => array( 'key' => 'whatsapp',  'color' => '#25d366', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>' ),
									'telegram'  => array( 'key' => 'telegram',  'color' => '#229ED9', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>' ),
									'messenger' => array( 'key' => 'messenger', 'color' => '#0084ff', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.4l3.131 3.259L19.752 8.4l-6.561 6.563z"/></svg>' ),
									'viber'     => array( 'key' => 'viber',     'color' => '#7360f2', 'icon' => '<svg viewBox="0 0 512 512"><path fill-rule="evenodd" fill="#fff" d="M95 232c0-91 17-147 161-147s161 56 161 147-17 147-161 147l-26-1-53 63c-4 4-8 1-8-3v-69c-6 0-31-12-38-19-22-23-36-40-36-118zm-30 0c0-126 55-177 191-177s191 51 191 177-55 177-191 177c-10 0-18 0-32-2l-38 43c-7 8-28 11-28-13v-42c-6 0-20-6-39-18-19-13-54-44-54-145zm223 42q10-13 24-4l36 27q8 10-7 28t-28 15q-53-12-102-60t-61-104q0-20 25-34 13-9 22 5l25 35q6 12-7 22c-39 15 51 112 73 70z"/><path fill="none" stroke="#fff" stroke-linecap="round" stroke-width="10" d="M269 186a30 30 0 0 1 31 31m-38-58a64 64 0 0 1 64 67m-73-93a97 97 0 0 1 99 104"/></svg>' ),
								);
								$preview_order     = ! empty( $s['platform_order'] ) ? $s['platform_order'] : array_keys( $all_platforms_map );
								$preview_platforms = array();
								foreach ( $preview_order as $key ) {
									if ( isset( $all_platforms_map[ $key ] ) ) {
										$on = ! empty( $s[ $key . '_enabled' ] );
										if ( 'yamidoo' === $key && ! self::ai_chat_available() ) {
											$on = false;
										}
										$all_platforms_map[ $key ]['enabled'] = $on;
										$preview_platforms[] = $all_platforms_map[ $key ];
									}
								}
								?>
								<div class="wpzoom-ctc-style-preview" id="ctc-style-preview"
									data-type="<?php echo esc_attr( $s['position_type'] ); ?>"
									data-side="<?php echo esc_attr( $s['side'] ); ?>">

									<!-- Page lines (fake content) -->
									<div class="wpzoom-ctc-sp-lines">
										<span></span><span></span><span></span><span></span>
									</div>

									<!-- Corner mode -->
									<div class="wpzoom-ctc-sp-corner">
										<div class="wpzoom-ctc-sp-buttons">
											<?php foreach ( $preview_platforms as $p ) : ?>
											<span class="wpzoom-ctc-sp-btn wpzoom-ctc-sp-btn--<?php echo esc_attr( $p['key'] ); ?>" style="background:<?php echo esc_attr( $p['color'] ); ?>;<?php echo empty( $p['enabled'] ) ? 'display:none;' : ''; ?>">
												<?php echo $p['icon']; // phpcs:ignore ?>
											</span>
											<?php endforeach; ?>
										</div>
										<div class="wpzoom-ctc-sp-launcher" style="background-color:<?php echo esc_attr( $s['launcher_color'] ); ?>">
											<?php echo self::icon_span( $s['open_icon_kit'], $s['open_icon'] ); // phpcs:ignore ?>
										</div>
									</div>

									<!-- Sidebar mode -->
									<div class="wpzoom-ctc-sp-sidebar">
										<div class="wpzoom-ctc-sp-strip">
											<?php foreach ( $preview_platforms as $p ) : ?>
											<span class="wpzoom-ctc-sp-btn wpzoom-ctc-sp-btn--<?php echo esc_attr( $p['key'] ); ?>" style="background:<?php echo esc_attr( $p['color'] ); ?>;<?php echo empty( $p['enabled'] ) ? 'display:none;' : ''; ?>">
												<?php echo $p['icon']; // phpcs:ignore ?>
											</span>
											<?php endforeach; ?>
										</div>
									</div>

								</div>
							</div>

							<div class="wpzoom-ctc-field">
								<label><?php esc_html_e( 'Position', 'social-icons-widget-by-wpzoom' ); ?></label>
								<div class="wpzoom-ctc-size-picker">
									<?php foreach ( array( 'left' => __( 'Left', 'social-icons-widget-by-wpzoom' ), 'right' => __( 'Right', 'social-icons-widget-by-wpzoom' ) ) as $val => $label ) : ?>
									<label class="wpzoom-ctc-size-option <?php echo $s['side'] === $val ? 'is-selected' : ''; ?>">
										<input type="radio" name="ctc_side" value="<?php echo esc_attr( $val ); ?>" <?php checked( $s['side'], $val ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="wpzoom-ctc-field">
								<label class="wpzoom-ctc-field-label">
									<?php esc_html_e( 'Widget size', 'social-icons-widget-by-wpzoom' ); ?>
									<span class="wpzoom-ctc-info-icon" title="<?php esc_attr_e( 'Controls the diameter of the chat buttons', 'social-icons-widget-by-wpzoom' ); ?>">&#9432;</span>
								</label>
								<div class="wpzoom-ctc-size-picker">
									<?php foreach ( array( 'S', 'M', 'L', 'XL', 'XXL' ) as $size ) : ?>
									<label class="wpzoom-ctc-size-option <?php echo $s['button_size'] === $size ? 'is-selected' : ''; ?>">
										<input type="radio" name="ctc_button_size" value="<?php echo esc_attr( $size ); ?>" <?php checked( $s['button_size'], $size ); ?>>
										<?php echo esc_html( $size ); ?>
									</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="wpzoom-ctc-field" id="ctc-launcher-color-field" <?php echo 'sidebar' === $s['position_type'] ? 'style="display:none"' : ''; ?>>
								<label for="ctc_launcher_color"><?php esc_html_e( 'Launcher Color', 'social-icons-widget-by-wpzoom' ); ?></label>
								<input type="text" id="ctc_launcher_color" name="ctc_launcher_color" value="<?php echo esc_attr( $s['launcher_color'] ); ?>" class="wpzoom-ctc-color-picker">
							</div>

							<!-- Launcher icon picker (corner mode only) -->
							<div class="wpzoom-ctc-field" id="ctc-launcher-icons-field" <?php echo 'sidebar' === $s['position_type'] ? 'style="display:none"' : ''; ?>>
								<label><?php esc_html_e( 'Open Icon', 'social-icons-widget-by-wpzoom' ); ?></label>
								<div class="wpzoom-ctc-icon-grid">
									<?php foreach ( self::open_icon_choices() as $choice ) :
										$val     = $choice['kit'] . ':' . $choice['icon'];
										$checked = ( $s['open_icon_kit'] === $choice['kit'] && $s['open_icon'] === $choice['icon'] );
									?>
									<label class="wpzoom-ctc-icon-choice <?php echo $checked ? 'is-selected' : ''; ?>" title="<?php echo esc_attr( $choice['label'] ); ?>">
										<input type="radio" name="ctc_open_icon_value" value="<?php echo esc_attr( $val ); ?>" <?php checked( $checked ); ?>>
										<?php echo self::icon_span( $choice['kit'], $choice['icon'] ); ?>
									</label>
									<?php endforeach; ?>
								</div>
								<input type="hidden" id="ctc_open_icon" name="ctc_open_icon" value="<?php echo esc_attr( $s['open_icon'] ); ?>">
								<input type="hidden" id="ctc_open_icon_kit" name="ctc_open_icon_kit" value="<?php echo esc_attr( $s['open_icon_kit'] ); ?>">
							</div>

							<div class="wpzoom-ctc-field" id="ctc-close-icon-field" <?php echo 'sidebar' === $s['position_type'] ? 'style="display:none"' : ''; ?>>
								<label><?php esc_html_e( 'Close Icon', 'social-icons-widget-by-wpzoom' ); ?></label>
								<div class="wpzoom-ctc-icon-grid">
									<?php foreach ( self::close_icon_choices() as $choice ) :
										$val     = $choice['kit'] . ':' . $choice['icon'];
										$checked = ( $s['close_icon_kit'] === $choice['kit'] && $s['close_icon'] === $choice['icon'] );
									?>
									<label class="wpzoom-ctc-icon-choice <?php echo $checked ? 'is-selected' : ''; ?>" title="<?php echo esc_attr( $choice['label'] ); ?>">
										<input type="radio" name="ctc_close_icon_value" value="<?php echo esc_attr( $val ); ?>" <?php checked( $checked ); ?>>
										<?php echo self::icon_span( $choice['kit'], $choice['icon'] ); ?>
									</label>
									<?php endforeach; ?>
								</div>
								<input type="hidden" id="ctc_close_icon" name="ctc_close_icon" value="<?php echo esc_attr( $s['close_icon'] ); ?>">
								<input type="hidden" id="ctc_close_icon_kit" name="ctc_close_icon_kit" value="<?php echo esc_attr( $s['close_icon_kit'] ); ?>">
							</div>

							<div class="wpzoom-ctc-field">
								<label class="wpzoom-ctc-toggle-row wpzoom-ctc-toggle-row--inline">
									<span><?php esc_html_e( 'Show on Mobile', 'social-icons-widget-by-wpzoom' ); ?></span>
									<span class="wpzoom-ctc-toggle-switch">
										<input type="checkbox" name="ctc_show_on_mobile" value="1" <?php checked( $s['show_on_mobile'] ); ?>>
										<span class="wpzoom-ctc-slider"></span>
									</span>
								</label>
							</div>
						</div>

					</div><!-- /.wpzoom-ctc-col -->

				</div><!-- /.wpzoom-ctc-two-col -->

				<div class="wpzoom-ctc-save-row" <?php echo empty( $s['enabled'] ) ? 'style="display:none"' : ''; ?>>
					<?php submit_button( __( 'Save Settings', 'social-icons-widget-by-wpzoom' ), 'primary large', 'wpzoom_ctc_save', false ); ?>
				</div>

			</form>
		</div>

		<script>
		jQuery(function($) {
			/* Init wp-color-picker — sync change to preview launcher */
			$('.wpzoom-ctc-color-picker').wpColorPicker({
				palettes: true,
				change: function(event, ui) {
					var launcher = document.querySelector('.wpzoom-ctc-sp-launcher');
					if (launcher) launcher.style.backgroundColor = ui.color.toString();
				}
			});

			/* Pill-group selected state (style + size + side pickers) */
			['ctc_position_type', 'ctc_button_size', 'ctc_side'].forEach(function(name) {
				document.querySelectorAll('input[name="' + name + '"]').forEach(function(r) {
					r.addEventListener('change', function() {
						this.closest('.wpzoom-ctc-size-picker').querySelectorAll('.wpzoom-ctc-size-option').forEach(function(o) {
							o.classList.toggle('is-selected', o.querySelector('input').checked);
						});
					});
				});
			});

			/* Update large preview when side or type changes */
			var stylePreview = document.getElementById('ctc-style-preview');
			document.querySelectorAll('input[name="ctc_side"]').forEach(function(r) {
				r.addEventListener('change', function() {
					if (stylePreview) stylePreview.setAttribute('data-side', r.value);
				});
			});
			document.querySelectorAll('input[name="ctc_position_type"]').forEach(function(r) {
				r.addEventListener('change', function() {
					if (stylePreview) stylePreview.setAttribute('data-type', r.value);
				});
			});

			var mainToggle  = document.getElementById('ctc_enabled');
			var formBody    = document.querySelector('.wpzoom-ctc-two-col');
			var saveRow     = document.querySelector('.wpzoom-ctc-save-row');
			var radios      = document.querySelectorAll('input[name="ctc_position_type"]');
			var colorField  = document.getElementById('ctc-launcher-color-field');
			var iconsField  = document.getElementById('ctc-launcher-icons-field');
			var closeField  = document.getElementById('ctc-close-icon-field');
			var radioCards  = document.querySelectorAll('.wpzoom-ctc-radio-card');
			var iconChoices = document.querySelectorAll('.wpzoom-ctc-icon-choice');

			/* Drag-to-reorder platform cards */
			var sortableEl = document.getElementById('wpzoom-ctc-platforms-sortable');
			var orderInput = document.getElementById('ctc_platform_order');
			if (sortableEl && typeof Sortable !== 'undefined') {
				Sortable.create(sortableEl, {
					handle: '.wpzoom-ctc-drag-handle',
					animation: 150,
					onEnd: function() {
						var order = Array.from(sortableEl.querySelectorAll('[data-platform]')).map(function(el) {
							return el.getAttribute('data-platform');
						});
						if (orderInput) orderInput.value = order.join(',');

						// Sync preview button order in both corner and sidebar previews.
						['.wpzoom-ctc-sp-buttons', '.wpzoom-ctc-sp-strip'].forEach(function(container) {
							var wrap = document.querySelector(container);
							if (!wrap) return;
							order.forEach(function(key) {
								var btn = wrap.querySelector('.wpzoom-ctc-sp-btn--' + key);
								if (btn) wrap.appendChild(btn);
							});
						});
					}
				});
			}

			/* Platform toggles: show/hide fields AND update preview */
			document.querySelectorAll('.wpzoom-ctc-platform-toggle input[type="checkbox"]').forEach(function(toggle) {
				toggle.addEventListener('change', function() {
					var platform = toggle.closest('[data-platform]').getAttribute('data-platform');
					document.querySelectorAll('.wpzoom-ctc-sp-btn--' + platform).forEach(function(btn) {
						btn.style.display = toggle.checked ? '' : 'none';
					});
				});
			});

			document.querySelectorAll('.wpzoom-ctc-platform-toggle input[type="checkbox"]').forEach(function(toggle) {
				var fields = toggle.closest('.wpzoom-ctc-platform-card').querySelector('.wpzoom-ctc-platform-fields');
				if (!fields) return;
				// A channel that cannot be switched on keeps its panel open: the panel
				// is where the "connect the chat first" explanation lives.
				if (toggle.disabled) return;
				function syncFields() {
					fields.style.display = toggle.checked ? '' : 'none';
				}
				toggle.addEventListener('change', syncFields);
				syncFields();
			});

			/* Icon grid: sync hidden inputs and selected state. The preview copies
			   the chosen swatch's own markup, so it always matches the picker. */
			function ctcIconHtml(radio) {
				var art = radio.closest('.wpzoom-ctc-icon-choice').querySelector('svg, .social-icon, .dashicons');
				return art ? art.outerHTML : '';
			}

			document.querySelectorAll('input[name="ctc_open_icon_value"]').forEach(function(r) {
				r.addEventListener('change', function() {
					var parts = this.value.split(':');
					document.getElementById('ctc_open_icon_kit').value = parts[0];
					document.getElementById('ctc_open_icon').value     = parts[1];
					this.closest('.wpzoom-ctc-icon-grid').querySelectorAll('.wpzoom-ctc-icon-choice').forEach(function(c) {
						c.classList.remove('is-selected');
					});
					this.closest('.wpzoom-ctc-icon-choice').classList.add('is-selected');
					// Update preview
					var previewLauncher = document.querySelector('.wpzoom-ctc-sp-launcher');
					if (previewLauncher) previewLauncher.innerHTML = ctcIconHtml(this);
				});
			});
			/* AI Chat channel icon: mirror the pick into the launcher preview. */
			document.querySelectorAll('input[name="ctc_yamidoo_icon"]').forEach(function(r) {
				r.addEventListener('change', function() {
					this.closest('.wpzoom-ctc-icon-grid').querySelectorAll('.wpzoom-ctc-icon-choice').forEach(function(c) {
						c.classList.remove('is-selected');
					});
					this.closest('.wpzoom-ctc-icon-choice').classList.add('is-selected');
					var art = this.closest('.wpzoom-ctc-icon-choice').querySelector('svg');
					document.querySelectorAll('.wpzoom-ctc-sp-btn--yamidoo').forEach(function(btn) {
						if (art) btn.innerHTML = art.outerHTML;
					});
				});
			});

			document.querySelectorAll('input[name="ctc_close_icon_value"]').forEach(function(r) {
				r.addEventListener('change', function() {
					var parts = this.value.split(':');
					document.getElementById('ctc_close_icon_kit').value = parts[0];
					document.getElementById('ctc_close_icon').value     = parts[1];
					this.closest('.wpzoom-ctc-icon-grid').querySelectorAll('.wpzoom-ctc-icon-choice').forEach(function(c) {
						c.classList.remove('is-selected');
					});
					this.closest('.wpzoom-ctc-icon-choice').classList.add('is-selected');
				});
			});

			var collisionNotice = document.getElementById('ctc-collision-notice');

			function applyEnabledState() {
				var on = mainToggle && mainToggle.checked;
				[formBody, saveRow, collisionNotice].forEach(function(el) {
					if (!el) return;
					el.style.display = on ? '' : 'none';
				});
			}

			/* "Add AI Chat to this launcher": flip the channel on, open its card,
			   and bring it into view. The save button does the rest. */
			var addAiButton = document.getElementById('ctc-collision-add-ai');
			if (addAiButton) {
				addAiButton.addEventListener('click', function() {
					var toggle = document.querySelector('input[name="ctc_yamidoo_enabled"]');
					if (!toggle || toggle.disabled) return;
					if (!toggle.checked) {
						toggle.checked = true;
						toggle.dispatchEvent(new Event('change', { bubbles: true }));
					}
					var card = toggle.closest('.wpzoom-ctc-platform-card');
					if (card) {
						card.scrollIntoView({ behavior: 'smooth', block: 'center' });
						card.classList.remove('wpzoom-ctc-card--flash');
						void card.offsetWidth; // restart the animation on repeat clicks
						card.classList.add('wpzoom-ctc-card--flash');
					}
					if (collisionNotice) {
						collisionNotice.classList.add('is-resolved');
					}
				});
			}

			function updatePositionUI() {
				var val = document.querySelector('input[name="ctc_position_type"]:checked');
				var isCorner = val && val.value === 'corner';
				if (colorField)  colorField.style.display = isCorner ? '' : 'none';
				if (iconsField)  iconsField.style.display = isCorner ? '' : 'none';
				if (closeField)  closeField.style.display = isCorner ? '' : 'none';
				radioCards.forEach(function(card) {
					card.classList.toggle('is-selected', card.querySelector('input').checked);
				});
			}

			if (mainToggle) {
				mainToggle.addEventListener('change', function() {
					applyEnabledState();
					$.post(ajaxurl, {
						action:  'wpzoom_ctc_toggle_enabled',
						nonce:   '<?php echo esc_js( wp_create_nonce( 'wpzoom_ctc_toggle_enabled' ) ); ?>',
						enabled: mainToggle.checked ? 1 : 0
					});
				});
			}
			radios.forEach(function(r) { r.addEventListener('change', updatePositionUI); });

			applyEnabledState();
			updatePositionUI();
		});
		</script>
		<?php
	}

	// -------------------------------------------------------------------------
	// Frontend
	// -------------------------------------------------------------------------

	/**
	 * Whether the floating launcher should render on this request.
	 * `wpzoom_ctc_should_render` lets AI Chat hide it (one floating button, not two).
	 */
	private static function should_render( $s ) {
		if ( empty( $s['enabled'] ) ) {
			return false;
		}
		return (bool) apply_filters( 'wpzoom_ctc_should_render', true );
	}

	public function enqueue_frontend_assets() {
		$s = self::get_settings();
		if ( ! self::should_render( $s ) ) {
			return;
		}

		// The launcher icons are inline SVG now, so an icon font is only loaded
		// for a site still on a saved Font Awesome / Dashicons value that has no
		// mapping in the new set — normally nothing is loaded here at all.
		$kits_needed = array_unique( array( $s['open_icon_kit'], $s['close_icon_kit'] ) );
		$legacy_kits = array_intersect( $kits_needed, array( 'fa', 'dashicons' ) );

		if ( $legacy_kits ) {
			// Base icon styles (.social-icon sizing) — icon-font path only.
			wp_enqueue_style(
				'wpzoom-social-icons-styles',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-styles.css',
				array(),
				WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
			);
		}
		foreach ( $legacy_kits as $kit ) {
			if ( 'fa' === $kit ) {
				wp_enqueue_style(
					'wpzoom-social-icons-font-awesome-3',
					WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/font-awesome-3.min.css',
					array(),
					WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
				);
			} elseif ( 'dashicons' === $kit ) {
				wp_enqueue_style( 'dashicons' );
			}
		}

		wp_enqueue_style(
			'wpzoom-click-to-chat',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-click-to-chat.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);

		// JS is needed for the corner launcher toggle, and for the AI Chat channel
		// in any layout (it opens the Yamidoo panel and hides its own bubble).
		$needs_launcher_js = ( 'corner' === $s['position_type'] && $this->count_active_buttons( $s ) > 1 );
		$yamidoo_channel   = self::yamidoo_channel_active();

		if ( $needs_launcher_js || $yamidoo_channel ) {
			wp_enqueue_script(
				'wpzoom-click-to-chat',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/wpzoom-click-to-chat-frontend.js',
				array(),
				WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
				true
			);
			wp_localize_script(
				'wpzoom-click-to-chat',
				'wpzoomCtcSettings',
				array( 'yamidoo' => $yamidoo_channel )
			);
		}
	}

	private function count_active_buttons( $s ) {
		$count = 0;
		if ( ! empty( $s['yamidoo_enabled'] ) && self::ai_chat_available() )        { $count++; }
		if ( ! empty( $s['whatsapp_enabled'] ) && ! empty( $s['whatsapp_phone'] ) )    { $count++; }
		if ( ! empty( $s['telegram_enabled'] ) && ! empty( $s['telegram_username'] ) ) { $count++; }
		if ( ! empty( $s['messenger_enabled'] ) && ! empty( $s['messenger_page'] ) )   { $count++; }
		if ( ! empty( $s['viber_enabled'] ) && ! empty( $s['viber_phone'] ) )         { $count++; }
		return $count;
	}

	/**
	 * One channel button.
	 *
	 * Link channels (WhatsApp, Telegram, …) navigate to the app, so they are
	 * anchors. The AI Chat channel opens the Yamidoo panel on the same page, so
	 * it is a real <button> — an anchor with no href is not keyboard-operable.
	 *
	 * @param array  $btn         Button definition from render_frontend_widget().
	 * @param string $extra_class Extra class for the solo-corner variant.
	 */
	private static function render_button( $btn, $extra_class = '' ) {
		$classes = 'wpzoom-ctc-btn wpzoom-ctc-btn--' . $btn['platform'];
		if ( $extra_class ) {
			$classes .= ' ' . $extra_class;
		}

		// The label span is both the hover chip and the button's accessible name,
		// so there is no title attribute: it would duplicate the chip with the
		// browser's own tooltip on top of it.
		$label = '<span class="wpzoom-ctc-btn-label">' . esc_html( $btn['label'] ) . '</span>';

		if ( isset( $btn['tag'] ) && 'button' === $btn['tag'] ) {
			?>
			<button type="button"
			        class="<?php echo esc_attr( $classes ); ?>"
			        data-wpzoom-ctc-action="<?php echo esc_attr( $btn['platform'] ); ?>">
				<?php echo $btn['icon']; // phpcs:ignore -- hardcoded SVG ?>
				<?php echo $label; // phpcs:ignore -- escaped above ?>
			</button>
			<?php
			return;
		}
		?>
		<a class="<?php echo esc_attr( $classes ); ?>"
		   href="<?php echo esc_url( $btn['url'], array_merge( array( 'http', 'https' ), $btn['protocols'] ?? array() ) ); ?>"
		   target="_blank"
		   rel="noopener noreferrer">
			<?php echo $btn['icon']; // phpcs:ignore -- hardcoded SVG ?>
			<?php echo $label; // phpcs:ignore -- escaped above ?>
		</a>
		<?php
	}

	public function render_frontend_widget() {
		$s = self::get_settings();
		if ( ! self::should_render( $s ) ) {
			return;
		}

		$all_buttons = array(
			// Opens the Yamidoo chat panel in place instead of navigating away, so
			// it is a <button>, not a link. See render_button().
			'yamidoo' => ( ! empty( $s['yamidoo_enabled'] ) && self::ai_chat_available() ) ? array(
				'platform' => 'yamidoo',
				'tag'      => 'button',
				'label'    => self::label_for( 'yamidoo', $s ),
				'icon'     => self::channel_icon( $s ),
			) : null,
			'whatsapp' => ( ! empty( $s['whatsapp_enabled'] ) && ! empty( $s['whatsapp_phone'] ) ) ? array(
				'platform' => 'whatsapp',
				// wa.me is WhatsApp's universal click-to-chat link: it opens the native app on
				// mobile/desktop when installed and falls back to WhatsApp Web otherwise.
				'url'      => 'https://wa.me/' . preg_replace( '/[^\d]/', '', $s['whatsapp_phone'] ) . ( ! empty( $s['whatsapp_message'] ) ? '?text=' . rawurlencode( $s['whatsapp_message'] ) : '' ),
				'label'    => self::label_for( 'whatsapp', $s ),
				'icon'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
			) : null,
			'telegram' => ( ! empty( $s['telegram_enabled'] ) && ! empty( $s['telegram_username'] ) ) ? array(
				'platform' => 'telegram',
				'url'      => 'https://t.me/' . rawurlencode( ltrim( $s['telegram_username'], '@' ) ),
				'label'    => self::label_for( 'telegram', $s ),
				'icon'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
			) : null,
			'messenger' => ( ! empty( $s['messenger_enabled'] ) && ! empty( $s['messenger_page'] ) ) ? array(
				'platform' => 'messenger',
				'url'      => 'https://m.me/' . rawurlencode( $s['messenger_page'] ),
				'label'    => self::label_for( 'messenger', $s ),
				'icon'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.4l3.131 3.259L19.752 8.4l-6.561 6.563z"/></svg>',
			) : null,
			'viber' => ( ! empty( $s['viber_enabled'] ) && ! empty( $s['viber_phone'] ) ) ? array(
				'platform'  => 'viber',
				'url'       => 'viber://chat?number=' . rawurlencode( ( $s['viber_country'] ?? '' ) . $s['viber_phone'] ),
				'protocols' => array( 'viber' ),
				'label'     => self::label_for( 'viber', $s ),
				'icon'     => '<svg viewBox="0 0 512 512" aria-hidden="true"><path fill-rule="evenodd" fill="#fff" d="M95 232c0-91 17-147 161-147s161 56 161 147-17 147-161 147l-26-1-53 63c-4 4-8 1-8-3v-69c-6 0-31-12-38-19-22-23-36-40-36-118zm-30 0c0-126 55-177 191-177s191 51 191 177-55 177-191 177c-10 0-18 0-32-2l-38 43c-7 8-28 11-28-13v-42c-6 0-20-6-39-18-19-13-54-44-54-145zm223 42q10-13 24-4l36 27q8 10-7 28t-28 15q-53-12-102-60t-61-104q0-20 25-34 13-9 22 5l25 35q6 12-7 22c-39 15 51 112 73 70z"/><path fill="none" stroke="#fff" stroke-linecap="round" stroke-width="10" d="M269 186a30 30 0 0 1 31 31m-38-58a64 64 0 0 1 64 67m-73-93a97 97 0 0 1 99 104"/></svg>',
			) : null,
		);

		$active_buttons = array();
		$order = ! empty( $s['platform_order'] ) ? $s['platform_order'] : array_keys( $all_buttons );
		foreach ( $order as $key ) {
			if ( ! empty( $all_buttons[ $key ] ) ) {
				$active_buttons[] = $all_buttons[ $key ];
			}
		}

		if ( empty( $active_buttons ) ) {
			return;
		}

		$position_type  = $s['position_type'];
		$side           = $s['side'];
		$launcher_color = $s['launcher_color'];
		$hide_mobile    = empty( $s['show_on_mobile'] ) ? ' wpzoom-ctc--hide-mobile' : '';

		$single_corner = ( 'corner' === $position_type && count( $active_buttons ) === 1 );
		$classes = 'wpzoom-ctc wpzoom-ctc--' . esc_attr( $position_type ) . ' wpzoom-ctc--' . esc_attr( $side ) . ' wpzoom-ctc--size-' . esc_attr( $s['button_size'] ) . $hide_mobile;
		?>
		<div id="wpzoom-ctc-widget" class="<?php echo esc_attr( $classes ); ?>">
			<?php if ( 'corner' === $position_type && ! $single_corner ) : ?>
				<div class="wpzoom-ctc-buttons" aria-hidden="true">
					<?php foreach ( $active_buttons as $btn ) : ?>
					<?php self::render_button( $btn ); ?>
					<?php endforeach; ?>
				</div>
				<button class="wpzoom-ctc-launcher"
				        style="background-color: <?php echo esc_attr( $launcher_color ); ?>;"
				        aria-label="<?php esc_attr_e( 'Open chat options', 'social-icons-widget-by-wpzoom' ); ?>"
				        aria-expanded="false"
				        aria-controls="wpzoom-ctc-widget">
					<span class="wpzoom-ctc-launcher-icon wpzoom-ctc-launcher-icon--open" aria-hidden="true">
						<?php echo self::icon_span( $s['open_icon_kit'], $s['open_icon'] ); // phpcs:ignore ?>
					</span>
					<span class="wpzoom-ctc-launcher-icon wpzoom-ctc-launcher-icon--close" aria-hidden="true">
						<?php echo self::icon_span( $s['close_icon_kit'], $s['close_icon'] ); // phpcs:ignore ?>
					</span>
				</button>
			<?php else : ?>
				<?php foreach ( $active_buttons as $btn ) : ?>
				<?php self::render_button( $btn, $single_corner ? 'wpzoom-ctc-btn--solo' : '' ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

WPZOOM_Click_To_Chat::get_instance();
