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
	/** The "Icon Sets" menu item: Settings, Social Icons tab (the list of saved sets is linked from the Overview). */
	const ICON_SETS   = 'edit.php?post_type=wpzoom-shortcode&page=wpzoom-social-icons-widget#social-icons-tab';
	const SHARE_BTNS  = 'edit.php?post_type=wpzoom-shortcode&page=wpzoom-social-icons-widget#share-buttons';
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
		add_action( 'admin_head', array( $this, 'hide_settings_item' ) );
		add_filter( 'submenu_file', array( $this, 'settings_submenu_file' ), 20 );
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
	 * Overview first, so the "Social Icons" menu opens it, and the post type's
	 * own entry ("Icon Sets") pointed at Settings. The Settings entry stays in
	 * place here: WordPress resolves the page's parent from it when checking
	 * access; hide_settings_item() drops it just before the menu is drawn.
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
			} elseif ( self::PARENT === $item[2] ) {
				// The post type's own "all items" entry, named Icon Sets.
				$items[ $key ][2] = self::ICON_SETS;
			} elseif ( preg_match( '/post\.php\?post=(\d+)&action=edit$/', $item[2], $m ) && 'wpzoom-sharing' === get_post_type( (int) $m[1] ) ) {
				// Share Buttons opens its Settings tab, which links on to the editor.
				$items[ $key ][2] = self::SHARE_BTNS;
			}
		}
		if ( $overview ) {
			array_unshift( $items, $overview );
		}
		$submenu[ self::PARENT ] = array_values( $items ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentional menu reorder.
	}

	/**
	 * No Settings item in the menu: the Overview's sidebar opens each of its
	 * tabs. Removed on admin_head, after the access check that needs it.
	 */
	public function hide_settings_item() {
		global $submenu;
		if ( empty( $submenu[ self::PARENT ] ) || ! is_array( $submenu[ self::PARENT ] ) ) {
			return;
		}
		foreach ( $submenu[ self::PARENT ] as $key => $item ) {
			if ( isset( $item[2] ) && self::SETTINGS === $item[2] ) {
				unset( $submenu[ self::PARENT ][ $key ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentional menu edit.
			}
		}
	}

	/** WordPress takes the browser title from the menu item, which is gone. */
	public function settings_title() {
		global $title;
		$title = __( 'WPZOOM Connect Settings', 'social-icons-widget-by-wpzoom' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the admin page title.
	}

	/**
	 * With Settings out of the menu, highlight Icon Sets (which opens Settings)
	 * on that screen, and Overview on the saved-sets list it replaced.
	 *
	 * @param string|null $submenu_file The submenu item WordPress marks current.
	 * @return string|null
	 */
	public function settings_submenu_file( $submenu_file ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return $submenu_file;
		}
		if ( 'wpzoom-shortcode_page_' . self::SETTINGS === $screen->id ) {
			return self::ICON_SETS; // The settings script moves it to Share Buttons on that tab.
		}
		if ( 'post' === $screen->base && 'wpzoom-sharing' === $screen->post_type ) {
			return self::SHARE_BTNS;
		}
		if ( 'edit-wpzoom-shortcode' === $screen->id ) {
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
			'pinterest'     => '<circle cx="12" cy="12" r="9"/><path d="m10.6 20.8 1.6-7.4"/><path d="M8.9 12.9a3.7 3.7 0 0 1-.6-2.1c0-2.4 1.8-4.4 4.6-4.4 2.4 0 4 1.6 4 3.8 0 2.5-1.3 4.3-3.2 4.3-1 0-1.6-.6-1.5-1.5"/>',
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

	/** The product mark, as on wordpress.org (.wordpress-org/icon.svg) and the admin menu. */
	private static function logo( $size = 32 ) {
		return sprintf(
			'<svg class="wpzc-logo" width="%1$d" height="%1$d" viewBox="0 0 56 56" aria-hidden="true" focusable="false"><path fill="#665CEE" fill-rule="evenodd" d="M28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28C0 12.536 12.536 0 28 0ZM36.667 11C34.2738 11 32.333 12.9028 32.333 15.25C32.333 15.324 32.3351 15.3976 32.3389 15.4707L22.1104 20.4873C21.3584 19.8711 20.3896 19.5 19.333 19.5C16.9399 19.5002 15 21.4029 15 23.75C15 26.0971 16.9399 27.9998 19.333 28C20.3897 28 21.3583 27.6291 22.1104 27.0127L32.3389 32.0283C32.335 32.1017 32.333 32.1757 32.333 32.25C32.333 32.324 32.3351 32.3976 32.3389 32.4707L22.1094 37.4873C21.3575 36.8713 20.3893 36.5 19.333 36.5C16.9399 36.5002 15 38.4029 15 40.75C15 43.0971 16.9399 44.9998 19.333 45C21.7262 45 23.667 43.0972 23.667 40.75C23.667 40.6756 23.664 40.6017 23.6602 40.5283L33.8887 35.5127C34.6408 36.1293 35.61 36.5 36.667 36.5C39.0601 36.4998 41 34.5971 41 32.25C41 29.9029 39.0601 28.0002 36.667 28C35.6102 28 34.6407 28.3708 33.8887 28.9873L23.6602 23.9707C23.664 23.8976 23.667 23.824 23.667 23.75C23.667 23.6756 23.664 23.6017 23.6602 23.5283L33.8896 18.5127C34.6417 19.1291 35.6103 19.5 36.667 19.5C39.0601 19.4998 41 17.5971 41 15.25C41 12.9029 39.0601 11.0002 36.667 11Z"/></svg>',
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
				<?php
				// PRO's floating share bar has its own configuration post.
				$floating = self::config_post( 'wpzoom-floating' );
				if ( $floating ) :
					?>
					<a class="wpzc-button is-soft" href="<?php echo esc_url( get_edit_post_link( $floating->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Edit floating buttons', 'social-icons-widget-by-wpzoom' ); ?></a>
				<?php endif; ?>
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
			array( 'pinterest', __( 'Pinterest', 'social-icons-widget-by-wpzoom' ), $settings ? '#pinterest' : self::settings_url( 'pinterest' ), false, 'pinterest-tab', 'pinterest', ! $pro ),
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

	/**
	 * The product marks used on wpzoom.com's menu, as self-contained SVGs.
	 *
	 * @param string $key inspiro, instagram, forms or wpbruiser.
	 * @return string
	 */
	private static function product_icon( $key ) {
		$icons = array(
			'inspiro' => '<svg viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24ZM10.1236 5.38182C10.1236 4.3375 10.9702 3.49091 12.0145 3.49091H13.7164C13.8208 3.49091 13.9055 3.57557 13.9055 3.68V7.04477C13.9055 7.10713 13.8722 7.16476 13.8182 7.19594L10.3855 9.17782C10.2691 9.245 10.1236 9.16103 10.1236 9.02666V5.38182ZM10.2109 12.5537L13.6436 10.5719C13.76 10.5047 13.9055 10.5887 13.9055 10.723V18.6182C13.9055 19.6625 13.0589 20.5091 12.0145 20.5091H10.3127C10.2083 20.5091 10.1236 20.4244 10.1236 20.32V12.7049C10.1236 12.6425 10.1569 12.5849 10.2109 12.5537Z" fill="#242628"/></svg>',
			'instagram' => '<svg viewBox="0 0 49 55"><defs><linearGradient id="wpzc-ig-g" x1="0" y1="54.9297" x2="48.231" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#F9493B"/><stop offset="1" stop-color="#DD337D"/></linearGradient></defs><path d="M43.7646 0C46.231 0 48.2305 1.99942 48.2305 4.46582V43.7646C48.2305 46.231 46.231 48.2305 43.7646 48.2305H31.6953L25.4844 54.4414C24.7868 55.139 23.6556 55.139 22.958 54.4414L16.7471 48.2305H4.46582C1.99942 48.2305 0 46.231 0 43.7646V4.46582C0 1.99942 1.99942 0 4.46582 0H43.7646ZM24.1152 12.9512C17.3326 12.9512 11.834 18.4498 11.834 25.2324C11.8341 32.0149 17.3327 37.5127 24.1152 37.5127C30.8976 37.5126 36.3954 32.0148 36.3955 25.2324C36.3955 18.4499 30.8977 12.9513 24.1152 12.9512ZM24.1152 16.3008C29.0479 16.3009 33.0469 20.2997 33.0469 25.2324C33.0467 30.165 29.0478 34.1639 24.1152 34.1641C19.1825 34.1641 15.1837 30.1651 15.1836 25.2324C15.1836 20.2996 19.1824 16.3008 24.1152 16.3008ZM24.1143 18.5332C20.415 18.5335 17.4163 21.5322 17.416 25.2314C17.416 28.9309 20.4149 31.9304 24.1143 31.9307C27.8139 31.9307 30.8135 28.931 30.8135 25.2314C30.8132 21.5321 27.8137 18.5332 24.1143 18.5332ZM39.5215 7.14551C38.2885 7.14577 37.2891 8.14587 37.2891 9.37891C37.2893 10.6117 38.2887 11.6111 39.5215 11.6113C40.7545 11.6113 41.7546 10.6119 41.7549 9.37891C41.7549 8.14571 40.7547 7.14551 39.5215 7.14551Z" fill="url(#wpzc-ig-g)"/></svg>',
			'forms' => '<svg viewBox="0 0 43 52"><path d="M42.6865 51.2236H0V8.53711H8.53711V0H42.6865V51.2236Z" fill="#083EA7"/><rect x="17.0742" y="8.53516" width="17.0744" height="8.53731" fill="#1FDE91"/><rect x="8.54102" y="17.0762" width="8.53722" height="25.6119" fill="#1FDE91"/><rect x="17.0742" y="25.6113" width="8.53722" height="8.53731" fill="#1FDE91"/></svg>',
			'wpbruiser' => '<svg viewBox="0 0 256 256"><defs><linearGradient id="wpzc-wpb-g" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#54509F" stop-opacity="0.965"/><stop offset="1" stop-color="#5B2A83" stop-opacity="0.93"/></linearGradient></defs><rect width="256" height="256" rx="52" fill="url(#wpzc-wpb-g)"/><path fill="#8823B6" d="M116.7 10.7Q128-.6 139.3 10.7L243.3 114.7Q254.6 126 243.3 137.3L139.3 241.3Q128 252.6 116.7 241.3L12.7 137.3Q1.4 126 12.7 114.7Z"/><ellipse cx="128" cy="126" rx="70" ry="70.5" fill="#000" stroke="#551775" stroke-width="1.5"/><path fill="#666A6B" d="M129 63C98 64 73 85 68 113L75 109C82 85 100 69 129 63Z"/><path fill="#666A6B" d="M68 145C74 171 96 187 129 189C100 183 82 169 76 148Z"/><path fill="#FFFFFF" d="M65 117C82 108 102 103 125 102C150 101 173 108 191 118C192 124 192 133 190 140C172 150 151 154 128 154C104 154 83 150 66 141C63 134 63 125 65 117Z"/><path fill="#000" d="M76 120C89 125 102 130 118 134C106 139 95 140 86 136C81 133 78 127 76 120Z"/><path fill="#000" d="M179 120C166 125 153 130 138 134C150 139 161 140 170 136C175 133 177 127 179 120Z"/></svg>',
		);
		return isset( $icons[ $key ] ) ? str_replace( '<svg ', '<svg class="wpzc-mosaic__icon" aria-hidden="true" focusable="false" ', $icons[ $key ] ) : '';
	}

	/**
	 * Other WPZOOM products for the Overview, each only while the site doesn't
	 * have it: no theme card on a WPZOOM theme, no plugin whose free or Pro
	 * version is installed. Free installed but inactive offers Activate; free
	 * active offers the Pro upgrade; otherwise Install.
	 *
	 * @return array Items: name, blurb, action, url, external, icon (SVG).
	 */
	public static function more_products() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$items = array();
		$utm   = array(
			'utm_source'   => 'wpadmin',
			'utm_medium'   => 'plugin',
			'utm_campaign' => self::is_pro() ? 'social-icons-pro' : 'social-icons-free',
			'utm_content'  => 'overview-more',
		);
		$link  = function ( $url ) use ( $utm ) {
			return add_query_arg( $utm, $url );
		};
		$item  = function ( $name, $blurb, $action, $url, $external, $icon ) {
			return compact( 'name', 'blurb', 'action', 'url', 'external', 'icon' );
		};

		// Themes: only for sites not on a WPZOOM theme (child themes count via their parent).
		$theme     = wp_get_theme();
		$on_wpzoom = false;
		foreach ( array( $theme, $theme->parent() ) as $t ) {
			if ( $t && false !== stripos( (string) $t->get( 'Author' ), 'wpzoom' ) ) {
				$on_wpzoom = true;
			}
		}
		if ( ! $on_wpzoom ) {
			$inspiro = self::inspiro_themes();
			$icon    = self::product_icon( 'inspiro' );
			$blurb   = __( 'A theme for video, photo and business sites, with a full-screen video hero.', 'social-icons-widget-by-wpzoom' );
			if ( $inspiro['premium'] ) {
				$items[] = $item( $inspiro['premium']->get( 'Name' ), $blurb, __( 'Activate', 'social-icons-widget-by-wpzoom' ), admin_url( 'themes.php?theme=' . rawurlencode( $inspiro['premium']->get_stylesheet() ) ), false, $icon );
			} else {
				if ( $inspiro['lite'] ) {
					$items[] = $item( __( 'Inspiro', 'social-icons-widget-by-wpzoom' ), $blurb, __( 'Activate', 'social-icons-widget-by-wpzoom' ), admin_url( 'themes.php?theme=' . rawurlencode( $inspiro['lite']->get_stylesheet() ) ), false, $icon );
				} elseif ( current_user_can( 'install_themes' ) ) {
					$items[] = $item( __( 'Inspiro', 'social-icons-widget-by-wpzoom' ), $blurb, __( 'Install free theme', 'social-icons-widget-by-wpzoom' ), wp_nonce_url( self_admin_url( 'update.php?action=install-theme&theme=inspiro' ), 'install-theme_inspiro' ), false, $icon );
				} else {
					$items[] = $item( __( 'Inspiro', 'social-icons-widget-by-wpzoom' ), $blurb, __( 'View theme', 'social-icons-widget-by-wpzoom' ), 'https://wordpress.org/themes/inspiro/', true, $icon );
				}
				$items[] = $item( __( 'Inspiro Premium', 'social-icons-widget-by-wpzoom' ), __( 'The full theme: more layouts, one-click demos, WooCommerce and premium support.', 'social-icons-widget-by-wpzoom' ), __( 'Learn more', 'social-icons-widget-by-wpzoom' ), $link( 'https://www.wpzoom.com/themes/inspiro/' ), true, $icon );
			}
		}

		// Plugins: free file, Pro directory, wp.org slug, name, blurb, icon, Pro page.
		$plugins = array(
			array( 'instagram-widget-by-wpzoom/instagram-widget-by-wpzoom.php', 'instagram-pro-by-wpzoom', 'instagram-widget-by-wpzoom', __( 'Instagram Widget', 'social-icons-widget-by-wpzoom' ), __( 'Instagram feeds in your own colors, as a block or widget.', 'social-icons-widget-by-wpzoom' ), self::product_icon( 'instagram' ), 'https://www.wpzoom.com/plugins/instagram-widget/' ),
			array( 'wpzoom-forms/wpzoom-forms.php', 'wpzoom-forms-pro', 'wpzoom-forms', __( 'WPZOOM Forms', 'social-icons-widget-by-wpzoom' ), __( 'Contact forms built in the block editor, with spam protection.', 'social-icons-widget-by-wpzoom' ), self::product_icon( 'forms' ), 'https://www.wpzoom.com/plugins/wpzoom-forms/' ),
			array( 'goodbye-captcha/goodbye-captcha.php', 'wpbruiser-pro', 'goodbye-captcha', __( 'WPBruiser', 'social-icons-widget-by-wpzoom' ), __( 'Anti-spam and login protection, with no CAPTCHA for your visitors.', 'social-icons-widget-by-wpzoom' ), self::product_icon( 'wpbruiser' ), 'https://www.wpzoom.com/plugins/wpbruiser/' ),
		);
		foreach ( $plugins as $plugin ) {
			list( $free, $pro_dir, $slug, $name, $blurb, $icon, $pro_page ) = $plugin;
			if ( is_dir( WP_PLUGIN_DIR . '/' . $pro_dir ) ) {
				continue;
			}
			if ( file_exists( WP_PLUGIN_DIR . '/' . $free ) ) {
				if ( is_plugin_active( $free ) ) {
					$items[] = $item( $name, $blurb, __( 'Upgrade to Pro', 'social-icons-widget-by-wpzoom' ), $link( $pro_page ), true, $icon );
				} elseif ( current_user_can( 'activate_plugins' ) ) {
					$items[] = $item( $name, $blurb, __( 'Activate', 'social-icons-widget-by-wpzoom' ), wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $free ) ), 'activate-plugin_' . $free ), false, $icon );
				}
			} elseif ( current_user_can( 'install_plugins' ) ) {
				$items[] = $item( $name, $blurb, __( 'Install free', 'social-icons-widget-by-wpzoom' ), wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug ), false, $icon );
			} else {
				$items[] = $item( $name, $blurb, __( 'View', 'social-icons-widget-by-wpzoom' ), 'https://wordpress.org/plugins/' . $slug . '/', true, $icon );
			}
		}

		/**
		 * The "More from WPZOOM" cards on the Overview.
		 *
		 * @param array $items Each: name, blurb, action, url, external, icon (SVG).
		 */
		return apply_filters( 'wpzoom_connect_more_products', $items );
	}

	/** The "More from WPZOOM" cards. Prints nothing when there is nothing to offer. */
	private static function render_more( $items ) {
		if ( ! $items ) {
			return;
		}
		?>
		<section class="wpzc-card wpzc-more">
			<div class="wpzc-card__head">
				<h2><?php esc_html_e( 'More from WPZOOM', 'social-icons-widget-by-wpzoom' ); ?></h2>
				<a class="wpzc-link" href="<?php echo esc_url( add_query_arg( array( 'utm_source' => 'wpadmin', 'utm_medium' => 'plugin', 'utm_content' => 'overview-more' ), 'https://www.wpzoom.com/plugins/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'All plugins', 'social-icons-widget-by-wpzoom' ); ?> <?php echo self::icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a>
			</div>
			<div class="wpzc-mosaic">
				<?php foreach ( $items as $it ) : ?>
					<article class="wpzc-mosaic__card">
						<?php echo $it['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<h3><?php echo esc_html( $it['name'] ); ?></h3>
						<p><?php echo esc_html( $it['blurb'] ); ?></p>
						<?php // Install / Activate act on this site: dark button. Links to wpzoom.com: soft button. ?>
						<a class="wpzc-button wpzc-mosaic__button<?php echo ! empty( $it['external'] ) ? ' is-soft' : ''; ?>" href="<?php echo esc_url( $it['url'] ); ?>"<?php echo ! empty( $it['external'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $it['action'] ); ?></a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Installed Inspiro themes, by edition. Only Inspiro is offered here: the
	 * free one from wordpress.org (named "Inspiro") and the premium one
	 * ("Inspiro Premium" or "Inspiro PRO"). Other WPZOOM themes and Inspiro
	 * Blocks are left alone.
	 *
	 * @return array lite => WP_Theme|null, premium => WP_Theme|null.
	 */
	private static function inspiro_themes() {
		$found = array( 'lite' => null, 'premium' => null );
		foreach ( wp_get_themes() as $theme ) {
			$name = (string) $theme->get( 'Name' );
			if ( false === stripos( (string) $theme->get( 'Author' ), 'wpzoom' ) || 0 !== stripos( $name, 'Inspiro' ) || false !== stripos( $name, 'Blocks' ) ) {
				continue;
			}
			if ( preg_match( '/premium|\bpro\b/i', $name ) ) {
				$found['premium'] = $found['premium'] ? $found['premium'] : $theme;
			} elseif ( preg_match( '/^Inspiro( Lite)?$/i', trim( $name ) ) ) {
				$found['lite'] = $theme;
			}
		}
		return $found;
	}

	public function render() {
		$more = self::more_products();
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
				'icon'  => 'pinterest',
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
				'icon'  => 'pinterest',
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

						<?php self::render_more( $more ); ?>

				</div>
			</div>
		</div>
		<?php
	}
}

WPZOOM_Connect_Overview::get_instance();
