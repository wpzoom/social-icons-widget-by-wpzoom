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
		'telegram_enabled'    => false,
		'telegram_username'   => '',
		'messenger_enabled'   => false,
		'messenger_page'      => '',
		'viber_enabled'       => false,
		'viber_phone'         => '',
		'platform_order'      => array( 'whatsapp', 'telegram', 'messenger', 'viber' ),
		'open_icon'           => 'comment',
		'open_icon_kit'       => 'fa',
		'close_icon'          => 'times',
		'close_icon_kit'      => 'fa',
		'button_size'         => 'M',
	);

	private static $open_icon_choices = array(
		array( 'kit' => 'fa', 'icon' => 'comment',    'label' => 'Comment' ),
		array( 'kit' => 'fa', 'icon' => 'commenting', 'label' => 'Commenting' ),
		array( 'kit' => 'fa', 'icon' => 'comments',   'label' => 'Comments' ),
		array( 'kit' => 'fa', 'icon' => 'envelope',   'label' => 'Envelope' ),
		array( 'kit' => 'fa', 'icon' => 'phone',      'label' => 'Phone' ),
		array( 'kit' => 'fa', 'icon' => 'headphones', 'label' => 'Headphones' ),
		array( 'kit' => 'dashicons', 'icon' => 'format-chat',      'label' => 'Chat' ),
		array( 'kit' => 'dashicons', 'icon' => 'admin-comments',   'label' => 'Admin Comments' ),
		array( 'kit' => 'dashicons', 'icon' => 'welcome-comments', 'label' => 'Welcome Comments' ),
	);

	private static $close_icon_choices = array(
		array( 'kit' => 'fa', 'icon' => 'times',         'label' => 'Times ×' ),
		array( 'kit' => 'fa', 'icon' => 'times-circle',  'label' => 'Times Circle' ),
		array( 'kit' => 'fa', 'icon' => 'minus',         'label' => 'Minus' ),
		array( 'kit' => 'fa', 'icon' => 'chevron-down',  'label' => 'Chevron Down' ),
		array( 'kit' => 'fa', 'icon' => 'angle-down',    'label' => 'Angle Down' ),
		array( 'kit' => 'dashicons', 'icon' => 'no',      'label' => 'No' ),
		array( 'kit' => 'dashicons', 'icon' => 'no-alt',  'label' => 'No Alt' ),
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

		// Ensure any newly added platform appears in the order.
		$all_known = array( 'whatsapp', 'telegram', 'messenger', 'viber' );
		foreach ( $all_known as $key ) {
			if ( ! in_array( $key, $settings['platform_order'], true ) ) {
				$settings['platform_order'][] = $key;
			}
		}

		return $settings;
	}

	// -------------------------------------------------------------------------
	// Admin
	// -------------------------------------------------------------------------

	public function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=wpzoom-shortcode',
			__( 'Click to Chat', 'social-icons-widget-by-wpzoom' ),
			__( 'Click to Chat', 'social-icons-widget-by-wpzoom' ),
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
		$allowed = array( 'whatsapp', 'telegram', 'messenger', 'viber' );
		$order   = array_filter( array_map( 'sanitize_key', explode( ',', $raw ) ), function( $v ) use ( $allowed ) {
			return in_array( $v, $allowed, true );
		} );
		$order = array_values( $order );
		// Ensure all three are present (append missing ones).
		foreach ( $allowed as $p ) {
			if ( ! in_array( $p, $order, true ) ) {
				$order[] = $p;
			}
		}
		return $order;
	}

	private static function icon_span( $kit, $icon ) {
		if ( 'dashicons' === $kit ) {
			return '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
		}
		// fa kit
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

			$valid_kits = array( 'fa', 'dashicons' );
			$open_icon_kit   = isset( $_POST['ctc_open_icon_kit'] ) && in_array( $_POST['ctc_open_icon_kit'], $valid_kits, true ) ? $_POST['ctc_open_icon_kit'] : 'fa';
			$close_icon_kit  = isset( $_POST['ctc_close_icon_kit'] ) && in_array( $_POST['ctc_close_icon_kit'], $valid_kits, true ) ? $_POST['ctc_close_icon_kit'] : 'fa';

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
				'viber_enabled'     => ! empty( $_POST['ctc_viber_enabled'] ),
				'viber_phone'       => preg_replace( '/[^\d+]/', '', isset( $_POST['ctc_viber_phone'] ) ? wp_unslash( $_POST['ctc_viber_phone'] ) : '' ),
				'open_icon'         => sanitize_key( isset( $_POST['ctc_open_icon'] ) ? wp_unslash( $_POST['ctc_open_icon'] ) : 'comment' ),
				'open_icon_kit'     => $open_icon_kit,
				'close_icon'        => sanitize_key( isset( $_POST['ctc_close_icon'] ) ? wp_unslash( $_POST['ctc_close_icon'] ) : 'times' ),
				'close_icon_kit'    => $close_icon_kit,
				'button_size'        => $button_size,
				'platform_order'     => $this->sanitize_platform_order( isset( $_POST['ctc_platform_order'] ) ? wp_unslash( $_POST['ctc_platform_order'] ) : '' ),
			);

			update_option( self::OPTION_KEY, $data );
			$notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'social-icons-widget-by-wpzoom' ) . '</p></div>';
		}

		$s = self::get_settings();
		?>
		<div class="wrap wpzoom-ctc-admin-wrap">
			<h1><?php esc_html_e( 'Click to Chat', 'social-icons-widget-by-wpzoom' ); ?></h1>
			<p class="wpzoom-ctc-admin-description">
				<?php esc_html_e( 'One-tap contact buttons for WhatsApp, Telegram & Messenger — floating, always visible.', 'social-icons-widget-by-wpzoom' ); ?>
			</p>

			<?php echo $notice; // phpcs:ignore -- escaped above ?>

			<form method="post" action="">
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

				<div class="wpzoom-ctc-two-col" <?php echo empty( $s['enabled'] ) ? 'style="display:none"' : ''; ?>>

					<!-- Left column: platforms (drag to reorder) -->
					<div class="wpzoom-ctc-col">

						<input type="hidden" name="ctc_platform_order" id="ctc_platform_order"
							value="<?php echo esc_attr( implode( ',', $s['platform_order'] ) ); ?>">

						<?php
						$drag_handle = '<span class="wpzoom-ctc-drag-handle" title="' . esc_attr__( 'Drag to reorder', 'social-icons-widget-by-wpzoom' ) . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm8-12a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg></span>';

						$platform_defs = array(
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
							<div class="wpzoom-ctc-card wpzoom-ctc-platform-card" data-platform="<?php echo esc_attr( $key ); ?>">
								<div class="wpzoom-ctc-platform-header <?php echo esc_attr( $p['header_class'] ); ?>">
									<?php echo $drag_handle; // phpcs:ignore ?>
									<span class="wpzoom-ctc-platform-icon">
										<?php echo $p['icon']; // phpcs:ignore ?>
									</span>
									<span class="wpzoom-ctc-platform-name"><?php echo esc_html( $p['name'] ); ?></span>
									<label class="wpzoom-ctc-toggle-switch wpzoom-ctc-platform-toggle">
										<input type="checkbox" name="<?php echo esc_attr( $p['toggle_name'] ); ?>" value="1" <?php checked( $s[ $p['enabled_key'] ] ?? false ); ?>>
										<span class="wpzoom-ctc-slider"></span>
									</label>
								</div>
								<div class="wpzoom-ctc-platform-fields" <?php echo empty( $s[ $p['enabled_key'] ] ) ? 'style="display:none"' : ''; ?>>
									<?php call_user_func( $p['fields'] ); ?>
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
									'whatsapp'  => array( 'key' => 'whatsapp',  'color' => '#25d366', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>' ),
									'telegram'  => array( 'key' => 'telegram',  'color' => '#229ED9', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>' ),
									'messenger' => array( 'key' => 'messenger', 'color' => '#0084ff', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.4l3.131 3.259L19.752 8.4l-6.561 6.563z"/></svg>' ),
									'viber'     => array( 'key' => 'viber',     'color' => '#7360f2', 'icon' => '<svg viewBox="0 0 512 512"><path fill-rule="evenodd" fill="#fff" d="M95 232c0-91 17-147 161-147s161 56 161 147-17 147-161 147l-26-1-53 63c-4 4-8 1-8-3v-69c-6 0-31-12-38-19-22-23-36-40-36-118zm-30 0c0-126 55-177 191-177s191 51 191 177-55 177-191 177c-10 0-18 0-32-2l-38 43c-7 8-28 11-28-13v-42c-6 0-20-6-39-18-19-13-54-44-54-145zm223 42q10-13 24-4l36 27q8 10-7 28t-28 15q-53-12-102-60t-61-104q0-20 25-34 13-9 22 5l25 35q6 12-7 22c-39 15 51 112 73 70z"/><path fill="none" stroke="#fff" stroke-linecap="round" stroke-width="10" d="M269 186a30 30 0 0 1 31 31m-38-58a64 64 0 0 1 64 67m-73-93a97 97 0 0 1 99 104"/></svg>' ),
								);
								$preview_order     = ! empty( $s['platform_order'] ) ? $s['platform_order'] : array_keys( $all_platforms_map );
								$preview_platforms = array();
								foreach ( $preview_order as $key ) {
									if ( isset( $all_platforms_map[ $key ] ) ) {
										$all_platforms_map[ $key ]['enabled'] = ! empty( $s[ $key . '_enabled' ] );
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
								<label><?php esc_html_e( 'Widget size', 'social-icons-widget-by-wpzoom' ); ?></label>
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
									<?php foreach ( self::$open_icon_choices as $choice ) :
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
									<?php foreach ( self::$close_icon_choices as $choice ) :
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
				function syncFields() {
					fields.style.display = toggle.checked ? '' : 'none';
				}
				toggle.addEventListener('change', syncFields);
				syncFields();
			});

			/* Icon grid: sync hidden inputs and selected state */
			function ctcIconHtml(kit, icon) {
				if (kit === 'dashicons') {
					return '<span class="dashicons dashicons-' + icon + '"></span>';
				}
				return '<span class="social-icon fa fa-' + icon + '"></span>';
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
					if (previewLauncher) previewLauncher.innerHTML = ctcIconHtml(parts[0], parts[1]);
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

			function applyEnabledState() {
				var on = mainToggle && mainToggle.checked;
				[formBody, saveRow].forEach(function(el) {
					if (!el) return;
					el.style.display = on ? '' : 'none';
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

	public function enqueue_frontend_assets() {
		$s = self::get_settings();
		if ( empty( $s['enabled'] ) ) {
			return;
		}

		// Base icon styles (.social-icon sizing).
		wp_enqueue_style(
			'wpzoom-social-icons-styles',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-social-icons-styles.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);

		// Enqueue icon kit CSS for whichever kits the launcher icons use.
		$kits_needed = array_unique( array( $s['open_icon_kit'], $s['close_icon_kit'] ) );
		foreach ( $kits_needed as $kit ) {
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

		// JS only needed for corner mode with multiple buttons (launcher toggle).
		if ( 'corner' === $s['position_type'] && $this->count_active_buttons( $s ) > 1 ) {
			wp_enqueue_script(
				'wpzoom-click-to-chat',
				WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/wpzoom-click-to-chat-frontend.js',
				array(),
				WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
				true
			);
		}
	}

	private function count_active_buttons( $s ) {
		$count = 0;
		if ( ! empty( $s['whatsapp_enabled'] ) && ! empty( $s['whatsapp_phone'] ) )    { $count++; }
		if ( ! empty( $s['telegram_enabled'] ) && ! empty( $s['telegram_username'] ) ) { $count++; }
		if ( ! empty( $s['messenger_enabled'] ) && ! empty( $s['messenger_page'] ) )   { $count++; }
		if ( ! empty( $s['viber_enabled'] ) && ! empty( $s['viber_phone'] ) )         { $count++; }
		return $count;
	}

	public function render_frontend_widget() {
		$s = self::get_settings();
		if ( empty( $s['enabled'] ) ) {
			return;
		}

		$all_buttons = array(
			'whatsapp' => ( ! empty( $s['whatsapp_enabled'] ) && ! empty( $s['whatsapp_phone'] ) ) ? array(
				'platform' => 'whatsapp',
				'url'      => 'https://web.whatsapp.com/send?phone=' . preg_replace( '/[^\d]/', '', $s['whatsapp_phone'] ) . ( ! empty( $s['whatsapp_message'] ) ? '&text=' . rawurlencode( $s['whatsapp_message'] ) : '' ),
				'label'    => __( 'Chat on WhatsApp', 'social-icons-widget-by-wpzoom' ),
				'icon'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
			) : null,
			'telegram' => ( ! empty( $s['telegram_enabled'] ) && ! empty( $s['telegram_username'] ) ) ? array(
				'platform' => 'telegram',
				'url'      => 'https://t.me/' . rawurlencode( ltrim( $s['telegram_username'], '@' ) ),
				'label'    => __( 'Chat on Telegram', 'social-icons-widget-by-wpzoom' ),
				'icon'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
			) : null,
			'messenger' => ( ! empty( $s['messenger_enabled'] ) && ! empty( $s['messenger_page'] ) ) ? array(
				'platform' => 'messenger',
				'url'      => 'https://m.me/' . rawurlencode( $s['messenger_page'] ),
				'label'    => __( 'Message us on Facebook', 'social-icons-widget-by-wpzoom' ),
				'icon'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.4l3.131 3.259L19.752 8.4l-6.561 6.563z"/></svg>',
			) : null,
			'viber' => ( ! empty( $s['viber_enabled'] ) && ! empty( $s['viber_phone'] ) ) ? array(
				'platform'  => 'viber',
				'url'       => 'viber://chat?number=' . rawurlencode( ( $s['viber_country'] ?? '' ) . $s['viber_phone'] ),
				'protocols' => array( 'viber' ),
				'label'     => __( 'Chat on Viber', 'social-icons-widget-by-wpzoom' ),
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
					<a class="wpzoom-ctc-btn wpzoom-ctc-btn--<?php echo esc_attr( $btn['platform'] ); ?>"
					   href="<?php echo esc_url( $btn['url'], array_merge( array( 'http', 'https' ), $btn['protocols'] ?? array() ) ); ?>"
					   target="_blank"
					   rel="noopener noreferrer"
					   title="<?php echo esc_attr( $btn['label'] ); ?>">
						<?php echo $btn['icon']; // phpcs:ignore -- hardcoded SVG ?>
						<span class="screen-reader-text"><?php echo esc_html( $btn['label'] ); ?></span>
					</a>
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
				<a class="wpzoom-ctc-btn wpzoom-ctc-btn--<?php echo esc_attr( $btn['platform'] ); ?> <?php echo $single_corner ? 'wpzoom-ctc-btn--solo' : ''; ?>"
				   href="<?php echo esc_url( $btn['url'], array_merge( array( 'http', 'https' ), $btn['protocols'] ?? array() ) ); ?>"
				   target="_blank"
				   rel="noopener noreferrer"
				   title="<?php echo esc_attr( $btn['label'] ); ?>">
					<?php echo $btn['icon']; // phpcs:ignore ?>
					<span class="screen-reader-text"><?php echo esc_html( $btn['label'] ); ?></span>
				</a>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

WPZOOM_Click_To_Chat::get_instance();
