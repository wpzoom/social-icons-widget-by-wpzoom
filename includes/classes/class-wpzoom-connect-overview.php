<?php
/**
 * WPZOOM Connect overview: the first screen under "Social Icons".
 *
 * The plugin is three modules, Social Icons, Share Buttons and Chat, the same
 * way wpzoom.com/plugins/social-share/ presents it. Layout: a header bar with
 * the product's own navigation, a welcome card, the three modules, a grid of
 * quick settings that each show their current state, and a sidebar with what
 * Pro adds (free) or the Pro perk on AI Chat (Pro), plus quick links.
 *
 * In PRO the Pro tiles show their real state (floating bar, Pinterest, share
 * analytics, custom icons); in free they link to the upgrade page.
 * Styles: assets/css/social-icons-settings-page.css ("Overview").
 * Shared with the PRO plugin through bin/sync-from-free.sh.
 *
 * @package WPZOOM_Social_Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Connect_Overview {

	const PAGE_SLUG   = 'wpzoom-connect';
	const SETTINGS    = 'wpzoom-social-icons-widget';
	const PARENT      = 'edit.php?post_type=wpzoom-shortcode';
	const OPTION      = 'wpzoom-social-icons-widget-settings';
	const UPGRADE_URL = 'https://www.wpzoom.com/plugins/social-share/upgrade/';
	const DOCS_URL    = 'https://www.wpzoom.com/documentation/social-icons-widget-pro/';

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 5 );
		// After AI Chat moves itself to the top (priority 99).
		add_action( 'admin_menu', array( $this, 'reorder_submenu' ), 100 );
		add_filter( 'submenu_file', array( $this, 'settings_submenu_file' ) );
		add_action( 'load-wpzoom-shortcode_page_' . self::SETTINGS, array( $this, 'settings_title' ) );
		add_action( 'admin_init', array( $this, 'canonical_redirect' ) );
		add_action( 'load-edit.php', array( $this, 'sharing_list_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/** Whether WPZOOM Connect Pro is running (it replaces the free plugin). */
	public static function is_pro() {
		return function_exists( 'wpzoom_social_icons_pro' );
	}

	public static function url( $fragment = '' ) {
		return admin_url( self::PARENT . '&page=' . self::PAGE_SLUG ) . ( $fragment ? '#' . $fragment : '' );
	}

	/**
	 * The upgrade page on wpzoom.com, tagged for the place it's linked from.
	 *
	 * @param string $content  utm_content.
	 * @param string $fragment Section of the page.
	 * @return string
	 */
	public static function upgrade_url( $content, $fragment = 'pro' ) {
		return add_query_arg(
			array(
				'utm_source'   => 'wpadmin',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'social-icons-free',
				'utm_content'  => $content,
			),
			self::UPGRADE_URL
		) . ( $fragment ? '#' . $fragment : '' );
	}

	public function add_menu_item() {
		add_submenu_page(
			self::PARENT,
			__( 'WPZOOM Connect', 'social-icons-widget-by-wpzoom' ),
			__( 'Overview', 'social-icons-widget-by-wpzoom' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' ),
			0
		);
	}

	/**
	 * Overview first, so the "Social Icons" menu opens it, and no Settings
	 * item: the Overview's sidebar opens each of its tabs. The page stays
	 * registered, so links to it keep working.
	 */
	public function reorder_submenu() {
		global $submenu;
		if ( empty( $submenu[ self::PARENT ] ) || ! is_array( $submenu[ self::PARENT ] ) ) {
			return;
		}
		$items    = $submenu[ self::PARENT ];
		$overview = null;
		foreach ( $items as $key => $item ) {
			if ( ! isset( $item[2] ) ) {
				continue;
			}
			if ( self::PAGE_SLUG === $item[2] ) {
				$overview = $item;
				unset( $items[ $key ] );
			} elseif ( self::SETTINGS === $item[2] ) {
				unset( $items[ $key ] );
			}
		}
		if ( $overview ) {
			array_unshift( $items, $overview );
		}
		$submenu[ self::PARENT ] = array_values( $items ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentional menu reorder.
	}

	/** WordPress takes the browser title from the menu item, which is gone. */
	public function settings_title() {
		global $title;
		$title = __( 'WPZOOM Connect Settings', 'social-icons-widget-by-wpzoom' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the admin page title.
	}

	/**
	 * With Settings out of the menu, keep Overview highlighted on its screen.
	 *
	 * @param string|null $submenu_file The submenu item WordPress marks current.
	 * @return string|null
	 */
	public function settings_submenu_file( $submenu_file ) {
		$screen = get_current_screen();
		if ( $screen && 'wpzoom-shortcode_page_' . self::SETTINGS === $screen->id ) {
			return self::PAGE_SLUG;
		}
		return $submenu_file;
	}

	/**
	 * The top-level menu links to admin.php?page=…, which renders the screen but
	 * doesn't mark the submenu item current. Send it to the canonical URL.
	 */
	public function canonical_redirect() {
		global $pagenow;
		if ( 'admin.php' !== $pagenow || ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
			return;
		}
		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * The Share Buttons post list holds only the one configuration post, but the
	 * block editor's back button leads there. Send it to the Overview instead.
	 * The Trash view stays, to restore a trashed configuration.
	 */
	public function sharing_list_redirect() {
		global $typenow;
		if ( 'wpzoom-sharing' !== $typenow || isset( $_GET['post_status'] ) || isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
			return;
		}
		wp_safe_redirect( self::url() );
		exit;
	}

	public function enqueue( $hook ) {
		// The Overview, and the chat screen, which shares its sidebar.
		if ( false === strpos( (string) $hook, self::PAGE_SLUG ) && false === strpos( (string) $hook, 'wpzoom-click-to-chat' ) ) {
			return;
		}
		wp_enqueue_style(
			'zoom-social-icons-settings-page',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/social-icons-settings-page.css',
			array(),
			filemtime( WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'assets/css/social-icons-settings-page.css' )
		);
	}

	// -------------------------------------------------------------------------
	// State
	// -------------------------------------------------------------------------

	private static function setting( $key, $default = false ) {
		$settings = get_option( self::OPTION, array() );
		return is_array( $settings ) && array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/** The first published configuration post of a post type (sharing / floating buttons). */
	private static function config_post( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return null;
		}
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		return $posts ? $posts[0] : null;
	}

	/** "Posts and Pages" from a list of post type names. */
	private static function post_type_labels( $types ) {
		$labels = array();
		foreach ( (array) $types as $type ) {
			$object = get_post_type_object( $type );
			if ( $object ) {
				$labels[] = $object->labels->name;
			}
		}
		return $labels ? wp_sprintf( '%l', $labels ) : '';
	}

	private static function position_label( $position ) {
		$labels = array(
			'top'    => __( 'Above posts', 'social-icons-widget-by-wpzoom' ),
			'bottom' => __( 'Below posts', 'social-icons-widget-by-wpzoom' ),
			'both'   => __( 'Above and below posts', 'social-icons-widget-by-wpzoom' ),
		);
		return isset( $labels[ $position ] ) ? $labels[ $position ] : $labels['bottom'];
	}

	/** Share clicks in the last 30 days (PRO's analytics table), or null without it. */
	private static function shares_last_30_days() {
		if ( ! class_exists( 'WPZOOM_Share_Analytics' ) || ! defined( 'WPZOOM_Share_Analytics::TABLE_NAME' ) ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . WPZOOM_Share_Analytics::TABLE_NAME;
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return null;
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE clicked_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a class constant.
	}

	private static function settings_url( $section ) {
		return admin_url( self::PARENT . '&page=' . self::SETTINGS . '#' . $section );
	}

	private static function chat_url( $tab ) {
		return admin_url( self::PARENT . '&page=wpzoom-click-to-chat&tab=' . $tab );
	}

	/**
	 * Everything the screen shows, read once.
	 *
	 * @return array
	 */
	private static function state() {
		$pro = self::is_pro();

		// Icon sets the pickers offer.
		$kits  = class_exists( 'WPZOOM_Social_Icons_Sets' ) ? WPZOOM_Social_Icons_Sets::enabled_kits( $pro ) : array( 'socicon' => true );
		$names = array(
			'socicon'     => __( 'WPZOOM Icons', 'social-icons-widget-by-wpzoom' ),
			'fab'         => __( 'Font Awesome', 'social-icons-widget-by-wpzoom' ),
			'academicons' => __( 'Academicons', 'social-icons-widget-by-wpzoom' ),
			'dashicons'   => __( 'Dashicons', 'social-icons-widget-by-wpzoom' ),
			'genericon'   => __( 'Genericons', 'social-icons-widget-by-wpzoom' ),
		);
		$sets  = array();
		foreach ( $names as $kit => $name ) {
			if ( ! empty( $kits[ $kit ] ) ) {
				$sets[] = $name;
			}
		}

		// Custom icons (Pro).
		$custom = self::setting( 'custom-icon-set' );
		$custom = is_string( $custom ) && '' !== $custom ? maybe_unserialize( $custom ) : $custom;

		// Share buttons added to posts automatically.
		$sharing     = self::config_post( 'wpzoom-sharing' );
		$share_types = $sharing ? get_post_meta( $sharing->ID, '_wpzoom_sharing_post_types', true ) : array();
		$share_types = is_array( $share_types ) ? array_values( array_filter( $share_types ) ) : array();

		// Floating share bar (Pro).
		$floating = $pro ? self::config_post( 'wpzoom-floating' ) : null;
		$f_types  = $floating ? get_post_meta( $floating->ID, '_wpzoom_floating_post_types', true ) : array();
		$f_types  = is_array( $f_types ) ? array_values( array_filter( $f_types ) ) : array();

		// Google Preferred Sources.
		$ps = class_exists( 'WPZOOM_Preferred_Source' ) ? WPZOOM_Preferred_Source::get_settings() : array();

		// Click to Chat channels.
		$ctc      = class_exists( 'WPZOOM_Click_To_Chat' ) ? WPZOOM_Click_To_Chat::get_settings() : array();
		$channels = array();
		foreach ( array( 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'messenger' => 'Messenger', 'viber' => 'Viber' ) as $key => $name ) {
			if ( ! empty( $ctc[ $key . '_enabled' ] ) ) {
				$channels[] = $name;
			}
		}

		$ai_connected = class_exists( 'WPZOOM_AI_Chat' ) && WPZOOM_AI_Chat::is_connected();

		return array(
			'pro'          => $pro,
			'widget'       => ! self::setting( 'disable-widget' ),
			'sets'         => $sets,
			'custom_icons' => is_array( $custom ) ? count( $custom ) : 0,
			'sharing'      => $sharing,
			'share_types'  => $share_types,
			'share_pos'    => $sharing ? get_post_meta( $sharing->ID, '_wpzoom_sharing_position', true ) : 'bottom',
			'floating'     => $floating,
			'float_types'  => $f_types,
			'pinterest'    => (bool) self::setting( 'pinterest-hover-enabled' ),
			'shares'       => $pro ? self::shares_last_30_days() : null,
			'ps_on'        => ! empty( $ps['enabled'] ) && ! empty( $ps['post_types'] ),
			'ps_types'     => isset( $ps['post_types'] ) ? (array) $ps['post_types'] : array(),
			'ctc_on'       => ! empty( $ctc['enabled'] ) && $channels,
			'channels'     => $channels,
			'ai_connected' => $ai_connected,
			'ai_showing'   => $ai_connected && WPZOOM_AI_Chat::bubble_on_front_end(),
		);
	}

	// -------------------------------------------------------------------------
	// Pieces
	// -------------------------------------------------------------------------

	/**
	 * A line icon (24px grid, 1.75 stroke), in the style of the module marks on
	 * wpzoom.com/plugins/social-share/.
	 *
	 * @param string $name Icon.
	 * @param int    $size Width and height.
	 * @return string SVG markup.
	 */
	public static function icon( $name, $size = 20 ) {
		$paths = array(
			'social-icons'  => '<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="3.5"/>',
			'share-buttons' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
			'chat'          => '<path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.3 8.7 8.7 0 0 1-3.8-.9L3 20.5l1.6-4.9A8.1 8.1 0 0 1 3.5 11.5 8.4 8.4 0 0 1 12 3.2a8.4 8.4 0 0 1 9 8.3z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/>',
			'layers'        => '<path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="m3 13 9 5 9-5"/>',
			'upload'        => '<path d="M12 15V4"/><path d="m7.5 8.5 4.5-4.5 4.5 4.5"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
			'layout'        => '<rect x="3" y="3.5" width="18" height="17" rx="2.5"/><path d="M3 15.5h18"/><path d="M7.5 18h3"/>',
			'google'        => '<path d="M20.5 12.2c0-.7-.1-1.3-.2-1.9H12v3.6h4.8a4.2 4.2 0 0 1-1.8 2.7v2.3h2.9c1.7-1.6 2.6-3.9 2.6-6.7z"/><path d="M12 21c2.4 0 4.4-.8 5.9-2.2L15 16.6c-.8.5-1.8.9-3 .9-2.3 0-4.3-1.6-5-3.7H4v2.3A9 9 0 0 0 12 21z"/><path d="M7 13.8a5.4 5.4 0 0 1 0-3.6V7.9H4a9 9 0 0 0 0 8.2l3-2.3z"/><path d="M12 6.5c1.3 0 2.5.5 3.4 1.3L18 5.3A9 9 0 0 0 4 7.9l3 2.3c.7-2.1 2.7-3.7 5-3.7z"/>',
			'message'       => '<path d="M4 5.5h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-9l-5 3.5v-3.5H4a1 1 0 0 1-1-1v-10a1 1 0 0 1 1-1z"/>',
			'sidebar'       => '<rect x="3" y="3.5" width="18" height="17" rx="2.5"/><path d="M16.5 7.5v9"/>',
			'pin'           => '<path d="M12 21v-5"/><path d="M8.5 3.5h7l-1 6 3 3v1.5h-11V12.5l3-3-1-6z"/>',
			'chart'         => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
			'book'          => '<path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H20v15H5.5A1.5 1.5 0 0 0 4 19.5v-15z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20v-3"/>',
			'lifebuoy'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.5"/><path d="m5.6 5.6 3.9 3.9M14.5 14.5l3.9 3.9M18.4 5.6l-3.9 3.9M9.5 14.5l-3.9 3.9"/>',
			'news'          => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M7 8.5h10M7 12h10M7 15.5h6"/>',
			'heart'         => '<path d="M12 20s-7.5-4.4-7.5-10A4.3 4.3 0 0 1 12 7.4 4.3 4.3 0 0 1 19.5 10c0 5.6-7.5 10-7.5 10z"/>',
			'check'         => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.3 2.3 2.3 4.7-5"/>',
			'arrow'         => '<path d="M7 17 17 7M9 7h8v8"/>',
			'chevron'       => '<path d="m9 6 6 6-6 6"/>',
			'help'          => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.3a2.5 2.5 0 0 1 4.8.9c0 1.7-2.4 2.2-2.4 3.6M12 17h.01"/>',
			'key'           => '<circle cx="8" cy="15" r="4.5"/><path d="m11.2 11.8 8.3-8.3M16.5 6.5l2.5 2.5M14 9l2 2"/>',
			'home'          => '<path d="m3.5 11 8.5-7 8.5 7"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5.5h4V20"/>',
			'sliders'       => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
		);
		if ( 'yamidoo' === $name ) {
			// AI Chat is Yamidoo: its solid Y mark, padded to sit with the line icons.
			return sprintf(
				'<svg class="wpzc-icon" width="%1$d" height="%1$d" viewBox="-32 -20 291 290" fill="currentColor" aria-hidden="true" focusable="false"><path d="M113.5 0C176.184 0 227 50.9256 227 113.745C227 176.565 168.555 250 105.87 250C102.47 250 99.104 249.849 95.7793 249.555C94.4106 249.434 93.604 247.981 94.1956 246.741L179.28 68.3939H148.899L124.797 117.11C122.636 121.44 120.61 125.635 118.72 129.695C116.965 133.619 115.277 137.949 113.657 142.685C112.037 137.949 110.348 133.619 108.593 129.695C106.838 125.635 104.88 121.44 102.719 117.11L79.0217 68.3939H47.2231L97.2192 170.011C97.4945 170.571 97.493 171.227 97.2151 171.785L73.9774 218.477C73.5189 219.398 72.4338 219.822 71.4783 219.44C29.5968 202.702 0 161.688 0 113.745C0 50.9256 50.8157 0 113.5 0Z"/></svg>',
				(int) $size
			);
		}
		if ( empty( $paths[ $name ] ) ) {
			return '';
		}
		return sprintf(
			'<svg class="wpzc-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
			(int) $size,
			$paths[ $name ]
		);
	}

	/** The product mark: the chat bubble of the plugin's wordpress.org icon. */
	private static function logo( $size = 32 ) {
		return sprintf(
			'<svg class="wpzc-logo" width="%1$d" height="%1$d" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><circle cx="16" cy="16" r="16" fill="#665CEE"/><path d="M16 8.5c-4.4 0-8 3-8 6.8 0 2 1 3.8 2.6 5l-.6 3.2 3.4-1.8c.8.2 1.7.4 2.6.4 4.4 0 8-3 8-6.8s-3.6-6.8-8-6.8z" fill="#fff"/><circle cx="12.6" cy="15.3" r="1.1" fill="#665CEE"/><circle cx="16" cy="15.3" r="1.1" fill="#665CEE"/><circle cx="19.4" cy="15.3" r="1.1" fill="#665CEE"/></svg>',
			(int) $size
		);
	}

	/** A brand icon from the plugin's own icon library (WPZOOM Icons), as inline SVG. */
	private static function brand( $name ) {
		if ( ! class_exists( 'WPZOOM_Social_Icons_Library' ) ) {
			return '';
		}
		return WPZOOM_Social_Icons_Library::inline_svg( 'socicon', $name );
	}

	// -------------------------------------------------------------------------
	// Cards for the Settings screen
	// -------------------------------------------------------------------------

	/**
	 * "Saved icon sets" on the Social Icons tab: sets of icons made once and
	 * shown anywhere with a shortcode or the Elementor widget.
	 */
	public static function render_icon_sets_card() {
		$sets  = get_posts(
			array(
				'post_type'      => 'wpzoom-shortcode',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$total = (int) wp_count_posts( 'wpzoom-shortcode' )->publish + (int) wp_count_posts( 'wpzoom-shortcode' )->draft + (int) wp_count_posts( 'wpzoom-shortcode' )->private;
		?>
		<div class="wpzoom-settings-card">
			<div class="wpzoom-settings-card__header">
				<span class="wpzoom-settings-card__icon dashicons dashicons-shortcode" aria-hidden="true"></span>
				<div class="wpzoom-settings-card__text">
					<h2><?php esc_html_e( 'Saved icon sets', 'social-icons-widget-by-wpzoom' ); ?></h2>
					<p><?php esc_html_e( 'Make a set of icons once and show it anywhere: paste its shortcode into a page, a widget or a page builder, or pick it in the Social Icon Sets widget for Elementor. Change the set and every place updates.', 'social-icons-widget-by-wpzoom' ); ?></p>
				</div>
			</div>

			<?php if ( $sets ) : ?>
				<ul class="wpzc-sets">
					<?php foreach ( $sets as $set ) : ?>
						<?php
						$shortcode = '[wpzoom_social_icons id="' . $set->ID . '"]';
						$title     = '' !== $set->post_title ? $set->post_title : __( '(no title)', 'social-icons-widget-by-wpzoom' );
						?>
						<li class="wpzoom-settings-row">
							<div class="wpzoom-settings-row__main">
								<a class="wpzoom-settings-row__title" href="<?php echo esc_url( get_edit_post_link( $set->ID, 'raw' ) ); ?>"><?php echo esc_html( $title ); ?></a>
								<?php if ( 'publish' !== $set->post_status ) : ?>
									<p class="wpzoom-settings-row__description"><?php echo esc_html( get_post_status_object( $set->post_status )->label ); ?></p>
								<?php endif; ?>
							</div>
							<div class="wpzoom-settings-row__control wpzc-copy-field">
								<label class="screen-reader-text" for="wpzc-set-<?php echo (int) $set->ID; ?>"><?php esc_html_e( 'Shortcode', 'social-icons-widget-by-wpzoom' ); ?></label>
								<input type="text" id="wpzc-set-<?php echo (int) $set->ID; ?>" class="code" value="<?php echo esc_attr( $shortcode ); ?>" readonly />
								<button type="button" class="button wpzc-copy" data-copy="<?php echo esc_attr( $shortcode ); ?>" data-done="<?php esc_attr_e( 'Copied', 'social-icons-widget-by-wpzoom' ); ?>"><?php esc_html_e( 'Copy', 'social-icons-widget-by-wpzoom' ); ?></button>
								<a class="button" href="<?php echo esc_url( get_edit_post_link( $set->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Edit', 'social-icons-widget-by-wpzoom' ); ?></a>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="wpzoom-settings-row">
					<p class="wpzoom-settings-row__description"><?php esc_html_e( 'No saved sets yet. In the block editor you don’t need one: add the Social Icons block wherever you want icons.', 'social-icons-widget-by-wpzoom' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="wpzoom-settings-row wpzc-card-actions">
				<a class="wpzc-button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpzoom-shortcode' ) ); ?>"><?php esc_html_e( 'Add icon set', 'social-icons-widget-by-wpzoom' ); ?></a>
				<?php if ( $total ) : ?>
					<a class="wpzc-button is-soft" href="<?php echo esc_url( admin_url( self::PARENT ) ); ?>">
						<?php
						/* translators: %s: number of saved icon sets */
						echo esc_html( sprintf( _n( 'Manage %s set', 'Manage all %s sets', $total, 'social-icons-widget-by-wpzoom' ), number_format_i18n( $total ) ) );
						?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * "Share buttons" on the Share Buttons tab: how they work, where they show
	 * now, and the way into the block editor where they are designed.
	 */
	public static function render_share_buttons_card() {
		$sharing = self::config_post( 'wpzoom-sharing' );
		$types   = $sharing ? get_post_meta( $sharing->ID, '_wpzoom_sharing_post_types', true ) : array();
		$types   = is_array( $types ) ? array_values( array_filter( $types ) ) : array();
		$edit    = $sharing ? get_edit_post_link( $sharing->ID, 'raw' ) : admin_url( 'edit.php?post_type=wpzoom-sharing' );
		?>
		<div class="wpzoom-settings-card">
			<div class="wpzoom-settings-card__header">
				<span class="wpzoom-settings-card__icon dashicons dashicons-share" aria-hidden="true"></span>
				<div class="wpzoom-settings-card__text">
					<h2><?php esc_html_e( 'Share buttons', 'social-icons-widget-by-wpzoom' ); ?></h2>
					<p><?php esc_html_e( 'Your share buttons are one block, designed in the block editor. Set them up once and they appear on your posts automatically.', 'social-icons-widget-by-wpzoom' ); ?></p>
				</div>
			</div>

			<div class="wpzoom-settings-row">
				<div class="wpzoom-settings-row__main">
					<div class="wpzoom-settings-row__title"><?php esc_html_e( 'Right now', 'social-icons-widget-by-wpzoom' ); ?></div>
					<p class="wpzoom-settings-row__description">
						<?php
						if ( $types ) {
							$where = array(
								'top'  => __( 'Shown above the content', 'social-icons-widget-by-wpzoom' ),
								'both' => __( 'Shown above and below the content', 'social-icons-widget-by-wpzoom' ),
							);
							$pos   = get_post_meta( $sharing->ID, '_wpzoom_sharing_position', true );
							/* translators: 1: where, e.g. "Shown below the content", 2: post types, e.g. "Posts and Pages" */
							echo esc_html( sprintf( __( '%1$s, on %2$s.', 'social-icons-widget-by-wpzoom' ), isset( $where[ $pos ] ) ? $where[ $pos ] : __( 'Shown below the content', 'social-icons-widget-by-wpzoom' ), self::post_type_labels( $types ) ) );
						} else {
							esc_html_e( 'Not added to posts automatically. They show only where you add the Social Sharing block.', 'social-icons-widget-by-wpzoom' );
						}
						?>
					</p>
				</div>
				<div class="wpzoom-settings-row__control">
					<span class="wpzc-status<?php echo $types ? ' is-on' : ''; ?>"><?php echo esc_html( $types ? __( 'On your posts', 'social-icons-widget-by-wpzoom' ) : __( 'Block only', 'social-icons-widget-by-wpzoom' ) ); ?></span>
				</div>
			</div>

			<ol class="wpzc-how">
				<li>
					<strong><?php esc_html_e( 'Design them', 'social-icons-widget-by-wpzoom' ); ?></strong>
					<span><?php esc_html_e( 'Pick the networks, the button style, colors and labels in the Social Sharing block.', 'social-icons-widget-by-wpzoom' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Choose where they appear', 'social-icons-widget-by-wpzoom' ); ?></strong>
					<span><?php esc_html_e( 'In the editor’s sidebar, Display Settings put them above or below the content of the post types you choose.', 'social-icons-widget-by-wpzoom' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Or place them yourself', 'social-icons-widget-by-wpzoom' ); ?></strong>
					<span><?php esc_html_e( 'Add the Social Sharing block to any post, page or template, with its own settings.', 'social-icons-widget-by-wpzoom' ); ?></span>
				</li>
			</ol>

			<div class="wpzoom-settings-row wpzc-card-actions">
				<a class="wpzc-button" href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit share buttons', 'social-icons-widget-by-wpzoom' ); ?></a>
			</div>
		</div>
		<?php
	}

	/** Help links for the sidebar: icon, label, URL. */
	private static function help_links( $pro ) {
		$links = array(
			array( 'book', __( 'Documentation', 'social-icons-widget-by-wpzoom' ), self::DOCS_URL ),
			array( 'lifebuoy', $pro ? __( 'Priority support', 'social-icons-widget-by-wpzoom' ) : __( 'Support forum', 'social-icons-widget-by-wpzoom' ), $pro ? 'https://www.wpzoom.com/support/' : 'https://wordpress.org/support/plugin/social-icons-widget-by-wpzoom/' ),
			array( 'news', __( 'What’s new', 'social-icons-widget-by-wpzoom' ), add_query_arg( 'whats-new', '1', self::url( 'whats-new' ) ) ),
		);
		if ( ! $pro ) {
			$links[] = array( 'heart', __( 'Rate us on WordPress.org', 'social-icons-widget-by-wpzoom' ), 'https://wordpress.org/support/plugin/social-icons-widget-by-wpzoom/reviews/#new-post' );
		}
		return $links;
	}

	/**
	 * The left column of the Overview and Settings screens: product name,
	 * version and navigation, laid out like the WPZOOM theme framework's
	 * dashboard so WPZOOM products feel like one family.
	 *
	 * On the Settings screen the Social Icons and Share Buttons items are its
	 * tabs (data-wpzc-tab); assets/js/social-icons-settings-page.js switches
	 * them and marks the current one.
	 *
	 * @param string $current overview, settings, ai or ctc (the chat screen's tabs),
	 *                        or license (PRO's License page).
	 */
	public static function render_sidebar( $current = 'overview' ) {
		$pro      = self::is_pro();
		$version  = defined( 'WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION' ) ? WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION : '';
		$settings = 'settings' === $current;

		/*
		 * icon, label, URL, current, and for Settings items the tab and the
		 * section they open (the script switches tabs in place there), and
		 * whether to badge the item as a Pro feature.
		 */
		$nav = array(
			array( 'home', __( 'Overview', 'social-icons-widget-by-wpzoom' ), self::url(), 'overview' === $current, '', '', false ),
			array( 'yamidoo', __( 'AI Chat', 'social-icons-widget-by-wpzoom' ), self::chat_url( 'ai' ), 'ai' === $current, '', '', false ),
			array( 'message', __( 'Click to Chat', 'social-icons-widget-by-wpzoom' ), self::chat_url( 'ctc' ), 'ctc' === $current, '', '', false ),
			array( 'social-icons', __( 'Social Icons', 'social-icons-widget-by-wpzoom' ), $settings ? '#social-icons-tab' : self::settings_url( 'social-icons-tab' ), false, 'social-icons-tab', '', false ),
			array( 'layout', __( 'Share Buttons', 'social-icons-widget-by-wpzoom' ), $settings ? '#share-buttons' : self::settings_url( 'share-buttons' ), false, 'share-buttons-tab', 'share-buttons', false ),
			array( 'google', __( 'Google Preferred Sources', 'social-icons-widget-by-wpzoom' ), $settings ? '#preferred-source' : self::settings_url( 'preferred-source' ), false, 'preferred-source-tab', 'preferred-source', false ),
			// In free the Pinterest tab holds the Pro upsell card; the badge says so.
			array( 'pin', __( 'Pinterest', 'social-icons-widget-by-wpzoom' ), $settings ? '#pinterest' : self::settings_url( 'pinterest' ), false, 'pinterest-tab', 'pinterest', ! $pro ),
		);
		if ( ! $pro && class_exists( 'WPZOOM_Social_Icons_SVG' ) && ! WPZOOM_Social_Icons_SVG::use_svg() ) {
			// Only on sites that turned SVG icons off and load icon fonts again.
			$nav[] = array( 'sliders', __( 'Optimization', 'social-icons-widget-by-wpzoom' ), $settings ? '#font-preload' : self::settings_url( 'font-preload' ), false, 'font-preload', '', false );
		}
		if ( $pro ) {
			$nav[] = array( 'chart', __( 'Share Analytics', 'social-icons-widget-by-wpzoom' ), admin_url( self::PARENT . '&page=wpzoom-share-analytics' ), false, '', '', false );
			$nav[] = array( 'key', __( 'License', 'social-icons-widget-by-wpzoom' ), admin_url( self::PARENT . '&page=wpzoom-social-icons-license' ), 'license' === $current, '', '', false );
		}
		?>
		<aside class="wpzc-sidebar">
			<div class="wpzc-brand">
				<?php echo self::logo( 36 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<div>
					<p class="wpzc-brand__name"><?php echo esc_html( $pro ? __( 'WPZOOM Connect Pro', 'social-icons-widget-by-wpzoom' ) : __( 'WPZOOM Connect', 'social-icons-widget-by-wpzoom' ) ); ?></p>
					<?php if ( $version ) : ?>
						<p class="wpzc-brand__version"><?php echo esc_html( $version ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<p class="wpzc-sidebar__label"><?php esc_html_e( 'Dashboard', 'social-icons-widget-by-wpzoom' ); ?></p>
			<nav class="wpzc-menu" aria-label="<?php esc_attr_e( 'WPZOOM Connect', 'social-icons-widget-by-wpzoom' ); ?>">
				<?php foreach ( $nav as $item ) : ?>
					<a href="<?php echo esc_url( $item[2] ); ?>"<?php echo $item[3] ? ' class="is-current" aria-current="page"' : ''; ?><?php echo $item[4] ? ' data-wpzc-tab="' . esc_attr( $item[4] ) . '"' : ''; ?><?php echo $item[5] ? ' data-wpzc-section="' . esc_attr( $item[5] ) . '"' : ''; ?>>
						<?php echo self::icon( $item[0], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span><?php echo esc_html( $item[1] ); ?></span>
						<?php if ( $item[6] ) : ?>
							<span class="wpzc-badge"><?php esc_html_e( 'Pro', 'social-icons-widget-by-wpzoom' ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<p class="wpzc-sidebar__label"><?php esc_html_e( 'Help', 'social-icons-widget-by-wpzoom' ); ?></p>
			<nav class="wpzc-menu" aria-label="<?php esc_attr_e( 'Help', 'social-icons-widget-by-wpzoom' ); ?>">
				<?php foreach ( self::help_links( $pro ) as $link ) : ?>
					<a href="<?php echo esc_url( $link[2] ); ?>"<?php echo 0 === strpos( $link[2], admin_url() ) ? '' : ' target="_blank" rel="noopener"'; ?>>
						<?php echo self::icon( $link[0], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span><?php echo esc_html( $link[1] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( ! $pro ) : ?>
				<a class="wpzc-sidebar__pro" href="<?php echo esc_url( self::upgrade_url( 'sidebar-' . $current ) ); ?>" target="_blank" rel="noopener">
					<span><?php esc_html_e( 'Get WPZOOM Connect Pro', 'social-icons-widget-by-wpzoom' ); ?></span>
					<?php echo self::icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				</a>
			<?php endif; ?>
		</aside>
		<?php
	}

	/**
	 * The welcome card's picture: a post with the three modules on it, drawn
	 * with the plugin's own icons (as on the plugin's page on wpzoom.com).
	 */
	private static function render_welcome_visual() {
		$follow = array( 'instagram' => '#E4405F', 'youtube' => '#FF0000', 'tiktok' => '#000000', 'facebook' => '#0866FF' );
		$share  = array( 'facebook' => '#0866FF', 'x' => '#000000', 'pinterest' => '#E60023', 'whatsapp' => '#25D366' );
		?>
		<div class="wpzc-visual" aria-hidden="true">
			<div class="wpzc-visual__post">
				<span class="wpzc-visual__line is-short"></span>
				<span class="wpzc-visual__title"><?php esc_html_e( 'Ten tips for slow travel', 'social-icons-widget-by-wpzoom' ); ?></span>
				<span class="wpzc-visual__line"></span>
				<span class="wpzc-visual__line is-mid"></span>
				<span class="wpzc-visual__share">
					<?php foreach ( $share as $name => $color ) : ?>
						<i style="background:<?php echo esc_attr( $color ); ?>"><?php echo self::brand( $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from the plugin's icon library. ?></i>
					<?php endforeach; ?>
				</span>
			</div>
			<div class="wpzc-visual__follow">
				<?php foreach ( $follow as $name => $color ) : ?>
					<i style="color:<?php echo esc_attr( $color ); ?>"><?php echo self::brand( $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from the plugin's icon library. ?></i>
				<?php endforeach; ?>
			</div>
			<div class="wpzc-visual__chat">
				<span class="is-visitor"><?php esc_html_e( 'Do you ship to Canada?', 'social-icons-widget-by-wpzoom' ); ?></span>
				<span class="is-ai"><?php esc_html_e( 'Yes, in 5–8 days.', 'social-icons-widget-by-wpzoom' ); ?></span>
			</div>
			<span class="wpzc-visual__launcher"><?php echo self::logo( 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
		</div>
		<?php
	}

	/**
	 * A quick-settings tile: icon, name, current state and where to change it.
	 *
	 * @param array $tile icon, title, value, url, state (on|off|pro).
	 */
	private static function render_tile( $tile ) {
		$pro_link = 'pro' === $tile['state'];
		?>
		<a class="wpzc-tile is-<?php echo esc_attr( $tile['state'] ); ?>" href="<?php echo esc_url( $tile['url'] ); ?>"<?php echo $pro_link ? ' target="_blank" rel="noopener"' : ''; ?>>
			<span class="wpzc-tile__icon"><?php echo self::icon( $tile['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			<span class="wpzc-tile__text">
				<span class="wpzc-tile__title"><?php echo esc_html( $tile['title'] ); ?></span>
				<span class="wpzc-tile__value">
					<?php if ( $pro_link ) : ?>
						<span class="wpzc-badge"><?php esc_html_e( 'Pro', 'social-icons-widget-by-wpzoom' ); ?></span>
					<?php else : ?>
						<span class="wpzc-dot" aria-hidden="true"></span><?php echo esc_html( $tile['value'] ); ?>
					<?php endif; ?>
				</span>
			</span>
			<?php echo self::icon( $pro_link ? 'arrow' : 'chevron', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		</a>
		<?php
	}

	// -------------------------------------------------------------------------
	// Screen
	// -------------------------------------------------------------------------

	public function render() {
		$s    = self::state();
		$pro  = $s['pro'];
		$user = wp_get_current_user();
		$name = $user->first_name ? $user->first_name : $user->display_name;

		$off = __( 'Off', 'social-icons-widget-by-wpzoom' );

		// The three modules, each with one line saying where it stands.
		$modules = array(
			array(
				'key'    => 'social-icons',
				'title'  => __( 'Social Icons', 'social-icons-widget-by-wpzoom' ),
				'text'   => __( 'Icons that link to your profiles, picked from each link you paste.', 'social-icons-widget-by-wpzoom' ),
				'on'     => true,
				'status' => __( 'Ready', 'social-icons-widget-by-wpzoom' ),
				'link'   => array( __( 'Icon sets', 'social-icons-widget-by-wpzoom' ), self::settings_url( 'font-styles' ) ),
			),
			array(
				'key'    => 'share-buttons',
				'title'  => __( 'Share Buttons', 'social-icons-widget-by-wpzoom' ),
				'text'   => __( 'Buttons that let readers share your posts, added for you or with a block.', 'social-icons-widget-by-wpzoom' ),
				'on'     => (bool) $s['share_types'],
				'status' => $s['share_types'] ? __( 'On your posts', 'social-icons-widget-by-wpzoom' ) : __( 'Block only', 'social-icons-widget-by-wpzoom' ),
				'link'   => array( __( 'Edit share buttons', 'social-icons-widget-by-wpzoom' ), $s['sharing'] ? get_edit_post_link( $s['sharing']->ID, 'raw' ) : admin_url( 'edit.php?post_type=wpzoom-sharing' ) ),
			),
			array(
				'key'    => 'chat',
				'title'  => __( 'Chat', 'social-icons-widget-by-wpzoom' ),
				'text'   => __( 'One floating button for WhatsApp and the rest, and an AI chat that answers from your pages.', 'social-icons-widget-by-wpzoom' ),
				'on'     => $s['ctc_on'] || $s['ai_showing'],
				'status' => $s['ai_showing'] ? __( 'AI Chat answering', 'social-icons-widget-by-wpzoom' ) : ( $s['ctc_on'] ? __( 'Click to Chat on', 'social-icons-widget-by-wpzoom' ) : __( 'Not set up', 'social-icons-widget-by-wpzoom' ) ),
				'link'   => array( __( 'Chat settings', 'social-icons-widget-by-wpzoom' ), self::chat_url( $s['ai_connected'] ? 'ai' : 'ctc' ) ),
			),
		);

		// Quick settings: every option worth reaching from here, with its state.
		$tiles = array(
			array(
				'icon'  => 'layers',
				'title' => __( 'Icon sets', 'social-icons-widget-by-wpzoom' ),
				/* translators: %d: number of icon sets */
				'value' => sprintf( _n( '%d set on', '%d sets on', count( $s['sets'] ), 'social-icons-widget-by-wpzoom' ), count( $s['sets'] ) ),
				'url'   => self::settings_url( 'font-styles' ),
				'state' => 'on',
			),
			$pro
				? array(
					'icon'  => 'upload',
					'title' => __( 'Custom icons', 'social-icons-widget-by-wpzoom' ),
					/* translators: %d: number of uploaded icons */
					'value' => $s['custom_icons'] ? sprintf( _n( '%d uploaded', '%d uploaded', $s['custom_icons'], 'social-icons-widget-by-wpzoom' ), $s['custom_icons'] ) : __( 'None yet', 'social-icons-widget-by-wpzoom' ),
					'url'   => self::settings_url( 'custom-icons' ),
					'state' => $s['custom_icons'] ? 'on' : 'off',
				)
				: array(
					'icon'  => 'upload',
					'title' => __( 'Custom SVG icons', 'social-icons-widget-by-wpzoom' ),
					'value' => '',
					'url'   => self::upgrade_url( 'overview-custom-icons' ),
					'state' => 'pro',
				),
			array(
				'icon'  => 'layout',
				'title' => __( 'Share buttons', 'social-icons-widget-by-wpzoom' ),
				'value' => $s['share_types'] ? self::position_label( $s['share_pos'] ) : __( 'Not added to posts', 'social-icons-widget-by-wpzoom' ),
				'url'   => $s['sharing'] ? get_edit_post_link( $s['sharing']->ID, 'raw' ) : admin_url( 'edit.php?post_type=wpzoom-sharing' ),
				'state' => $s['share_types'] ? 'on' : 'off',
			),
			array(
				'icon'  => 'google',
				'title' => __( 'Preferred Sources', 'social-icons-widget-by-wpzoom' ),
				/* translators: %s: post types, e.g. "Posts" */
				'value' => $s['ps_on'] ? sprintf( __( 'On %s', 'social-icons-widget-by-wpzoom' ), self::post_type_labels( $s['ps_types'] ) ) : $off,
				'url'   => class_exists( 'WPZOOM_Preferred_Source' ) ? WPZOOM_Preferred_Source::settings_url() : self::settings_url( 'preferred-source' ),
				'state' => $s['ps_on'] ? 'on' : 'off',
			),
			array(
				'icon'  => 'message',
				'title' => __( 'Click to Chat', 'social-icons-widget-by-wpzoom' ),
				'value' => $s['ctc_on'] ? implode( ', ', $s['channels'] ) : $off,
				'url'   => self::chat_url( 'ctc' ),
				'state' => $s['ctc_on'] ? 'on' : 'off',
			),
			array(
				'icon'  => 'yamidoo',
				'title' => __( 'AI Chat', 'social-icons-widget-by-wpzoom' ),
				'value' => $s['ai_showing'] ? __( 'Answering visitors', 'social-icons-widget-by-wpzoom' ) : ( $s['ai_connected'] ? __( 'Hidden on your site', 'social-icons-widget-by-wpzoom' ) : __( 'Not connected', 'social-icons-widget-by-wpzoom' ) ),
				'url'   => self::chat_url( 'ai' ),
				'state' => $s['ai_showing'] ? 'on' : 'off',
			),
		);
		if ( $pro ) {
			$tiles[] = array(
				'icon'  => 'sidebar',
				'title' => __( 'Floating share bar', 'social-icons-widget-by-wpzoom' ),
				/* translators: %s: post types */
				'value' => $s['float_types'] ? sprintf( __( 'On %s', 'social-icons-widget-by-wpzoom' ), self::post_type_labels( $s['float_types'] ) ) : $off,
				'url'   => $s['floating'] ? get_edit_post_link( $s['floating']->ID, 'raw' ) : admin_url( 'edit.php?post_type=wpzoom-floating' ),
				'state' => $s['float_types'] ? 'on' : 'off',
			);
			$tiles[] = array(
				'icon'  => 'pin',
				'title' => __( 'Pinterest Save', 'social-icons-widget-by-wpzoom' ),
				'value' => $s['pinterest'] ? __( 'On images', 'social-icons-widget-by-wpzoom' ) : $off,
				'url'   => self::settings_url( 'pinterest' ),
				'state' => $s['pinterest'] ? 'on' : 'off',
			);
			if ( null !== $s['shares'] ) {
				$tiles[] = array(
					'icon'  => 'chart',
					'title' => __( 'Share analytics', 'social-icons-widget-by-wpzoom' ),
					/* translators: %s: number of shares */
					'value' => sprintf( __( '%s shares in 30 days', 'social-icons-widget-by-wpzoom' ), number_format_i18n( $s['shares'] ) ),
					'url'   => admin_url( self::PARENT . '&page=wpzoom-share-analytics' ),
					'state' => $s['shares'] ? 'on' : 'off',
				);
			}
		} else {
			$tiles[] = array(
				'icon'  => 'sidebar',
				'title' => __( 'Floating share bar', 'social-icons-widget-by-wpzoom' ),
				'value' => '',
				'url'   => self::upgrade_url( 'overview-floating' ),
				'state' => 'pro',
			);
			$tiles[] = array(
				'icon'  => 'pin',
				'title' => __( 'Pinterest Save', 'social-icons-widget-by-wpzoom' ),
				'value' => '',
				'url'   => self::upgrade_url( 'overview-pinterest' ),
				'state' => 'pro',
			);
		}

		?>
		<div class="wrap wpzc">
			<?php // WordPress moves admin notices to just after the first heading, above the layout. ?>
			<h1 class="screen-reader-text"><?php esc_html_e( 'WPZOOM Connect', 'social-icons-widget-by-wpzoom' ); ?></h1>

			<div class="wpzc-layout">
				<?php self::render_sidebar( 'overview' ); ?>

				<div class="wpzc-main">

						<section class="wpzc-card wpzc-welcome">
							<div class="wpzc-welcome__text">
								<?php /* translators: %s: first name */ ?>
								<p class="wpzc-welcome__hello"><?php echo esc_html( sprintf( __( 'Hello %s', 'social-icons-widget-by-wpzoom' ), $name ) ); ?></p>
								<h2><?php esc_html_e( 'Welcome to WPZOOM Connect', 'social-icons-widget-by-wpzoom' ); ?></h2>
								<p><?php esc_html_e( 'Social icons, share buttons and a chat that answers your visitors, in one plugin. Here is what is set up on your site.', 'social-icons-widget-by-wpzoom' ); ?></p>
								<p class="wpzc-actions">
									<?php if ( $s['ai_connected'] ) : ?>
										<a class="wpzc-button" href="<?php echo esc_url( WPZOOM_AI_Chat::app_url() . '/dashboard' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open Yamidoo dashboard', 'social-icons-widget-by-wpzoom' ); ?></a>
									<?php else : ?>
										<a class="wpzc-button" href="<?php echo esc_url( self::chat_url( 'ai' ) ); ?>"><?php esc_html_e( 'Set up AI Chat', 'social-icons-widget-by-wpzoom' ); ?></a>
									<?php endif; ?>
									<a class="wpzc-button is-soft" href="<?php echo esc_url( $modules[1]['link'][1] ); ?>"><?php esc_html_e( 'Edit share buttons', 'social-icons-widget-by-wpzoom' ); ?></a>
								</p>
							</div>
							<?php self::render_welcome_visual(); ?>
						</section>

						<?php
						if ( class_exists( 'WPZOOM_Connect_Whats_New' ) ) {
							WPZOOM_Connect_Whats_New::render_card();
						}
						?>

						<section class="wpzc-card">
							<div class="wpzc-card__head">
								<h2><?php esc_html_e( 'Modules', 'social-icons-widget-by-wpzoom' ); ?></h2>
							</div>
							<div class="wpzc-modules">
								<?php foreach ( $modules as $module ) : ?>
									<div class="wpzc-module">
										<div class="wpzc-module__top">
											<span class="wpzc-module__icon"><?php echo self::icon( $module['key'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
											<span class="wpzc-status<?php echo $module['on'] ? ' is-on' : ''; ?>"><?php echo esc_html( $module['status'] ); ?></span>
										</div>
										<h3><?php echo esc_html( $module['title'] ); ?></h3>
										<p><?php echo esc_html( $module['text'] ); ?></p>
										<a class="wpzc-link" href="<?php echo esc_url( $module['link'][1] ); ?>"><?php echo esc_html( $module['link'][0] ); ?> <?php echo self::icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
									</div>
								<?php endforeach; ?>
							</div>
						</section>

						<div class="wpzc-split<?php echo ( $pro && $s['ai_connected'] ) ? ' is-single' : ''; ?>">
							<section class="wpzc-card">
								<div class="wpzc-card__head">
									<h2><?php esc_html_e( 'Quick settings', 'social-icons-widget-by-wpzoom' ); ?></h2>
									<a class="wpzc-link" href="<?php echo esc_url( admin_url( self::PARENT . '&page=wpzoom-social-icons-widget' ) ); ?>"><?php esc_html_e( 'All settings', 'social-icons-widget-by-wpzoom' ); ?> <?php echo self::icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
								</div>
								<div class="wpzc-tiles">
									<?php
									foreach ( $tiles as $tile ) {
										self::render_tile( $tile );
									}
									?>
								</div>
							</section>


							<div class="wpzc-side">
								<?php if ( ! $pro ) : ?>
								<section class="wpzc-card wpzc-promo">
									<div class="wpzc-promo__art" aria-hidden="true">
										<span class="wpzc-promo__bar">
											<?php foreach ( array( 'facebook' => '#0866FF', 'x' => '#000000', 'pinterest' => '#E60023' ) as $brand => $color ) : ?>
												<i style="background:<?php echo esc_attr( $color ); ?>"><?php echo self::brand( $brand ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from the plugin's icon library. ?></i>
											<?php endforeach; ?>
										</span>
										<span class="wpzc-promo__stat"><strong>1,284</strong> <?php esc_html_e( 'shares', 'social-icons-widget-by-wpzoom' ); ?></span>
										<span class="wpzc-promo__chart"><i style="height:38%"></i><i style="height:55%"></i><i style="height:46%"></i><i style="height:72%"></i><i style="height:64%"></i><i style="height:92%"></i></span>
									</div>
									<h2><?php esc_html_e( 'Get more shares with Pro', 'social-icons-widget-by-wpzoom' ); ?></h2>
									<p><?php esc_html_e( 'A floating share bar, share counts and analytics, and Pinterest Save buttons on your images.', 'social-icons-widget-by-wpzoom' ); ?></p>
									<a class="wpzc-button is-block" href="<?php echo esc_url( self::upgrade_url( 'overview-sidebar' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade to Pro', 'social-icons-widget-by-wpzoom' ); ?></a>
									<ul class="wpzc-checks">
										<li><?php echo self::icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Floating share bar', 'social-icons-widget-by-wpzoom' ); ?></li>
										<li><?php echo self::icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Share counts and a Like button', 'social-icons-widget-by-wpzoom' ); ?></li>
										<li><?php echo self::icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Share analytics', 'social-icons-widget-by-wpzoom' ); ?></li>
										<li><?php echo self::icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Pinterest Save button on images', 'social-icons-widget-by-wpzoom' ); ?></li>
										<li><?php echo self::icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Your own SVG icons', 'social-icons-widget-by-wpzoom' ); ?></li>
									</ul>
								</section>
								<?php elseif ( ! $s['ai_connected'] ) : ?>
								<section class="wpzc-card wpzc-promo">
									<h2><?php esc_html_e( 'Answer visitors with AI Chat', 'social-icons-widget-by-wpzoom' ); ?></h2>
									<p><?php esc_html_e( 'Your chat button answers questions from your own pages, day and night, and hands off to you when needed. The free plan needs no card.', 'social-icons-widget-by-wpzoom' ); ?></p>
									<a class="wpzc-button is-block" href="<?php echo esc_url( self::chat_url( 'ai' ) ); ?>"><?php esc_html_e( 'Set up AI Chat', 'social-icons-widget-by-wpzoom' ); ?></a>
								</section>
								<?php endif; ?>


							</div>
						</div>

				</div>
			</div>
		</div>
		<?php
	}
}

WPZOOM_Connect_Overview::get_instance();
