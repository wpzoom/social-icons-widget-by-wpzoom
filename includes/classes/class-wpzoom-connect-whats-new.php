<?php
/**
 * "What's new" for a big release (free 5.0, PRO 3.0), shown only to sites that
 * update from an older version — never on a new install:
 *
 *   - a notice on the Dashboard and Plugins screens (through the WPZOOM Notice
 *     Center when it is loaded, plain markup otherwise);
 *   - an admin pointer on the "Social Icons" menu, on other admin screens;
 *   - a "What's new" card at the top of the Overview, where the details are.
 *
 * Each admin dismisses them for themselves; all of it stops 30 days after the
 * update. Opening the Overview counts as having seen the pointer.
 *
 * Shared with the PRO plugin through bin/sync-from-free.sh.
 *
 * @package WPZOOM_Social_Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Connect_Whats_New {

	/** How long after the update it all shows. */
	const DAYS = 30;

	/** User meta: the release whose notice, card and pointer this admin closed. */
	const DISMISSED_META = 'wpzoom_connect_whats_new_dismissed';

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Before the admin menu runs (it creates the share buttons post a new
		// install would otherwise count as an old site). PRO loads this file
		// during plugins_loaded, so it runs straight away there.
		if ( did_action( 'plugins_loaded' ) ) {
			self::detect_update();
		} else {
			add_action( 'plugins_loaded', array( __CLASS__, 'detect_update' ), 1 );
		}

		add_action( 'admin_init', array( $this, 'handle_dismiss' ) );
		add_filter( 'wpzoom_notice_center_notices', array( $this, 'register_notice_center' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pointer' ) );
	}

	/** Whether this is WPZOOM Connect Pro. */
	private static function is_pro() {
		return class_exists( 'WPZOOM_Connect_Overview' ) ? WPZOOM_Connect_Overview::is_pro() : function_exists( 'wpzoom_social_icons_pro' );
	}

	/** The release being announced: 5.0 for free, 3.0 for PRO. */
	public static function release() {
		return self::is_pro() ? '3.0' : '5.0';
	}

	private static function prefix() {
		return self::is_pro() ? 'wpzoom_connect_pro' : 'wpzoom_connect';
	}

	/** Pointer ID, in core's list of dismissed pointers (one per release). */
	private static function pointer_id() {
		return self::prefix() . '_' . str_replace( '.', '', self::release() );
	}

	/**
	 * Store the plugin version; when it changes from before this release (or
	 * the site clearly ran an older version, which didn't store it), start the
	 * 30 days.
	 */
	public static function detect_update() {
		if ( ! defined( 'WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION' ) ) {
			return;
		}
		$version_key = self::prefix() . '_version';
		$stored      = (string) get_option( $version_key, '' );
		$current     = WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION;
		if ( $stored === $current ) {
			return;
		}

		$updated = '' !== $stored ? version_compare( $stored, self::release(), '<' ) : self::ran_before();
		if ( $updated && version_compare( $current, self::release(), '>=' ) ) {
			update_option(
				self::prefix() . '_whats_new',
				array(
					'release' => self::release(),
					'since'   => time(),
				),
				false
			);
		}
		update_option( $version_key, $current );
	}

	/**
	 * Whether the plugin was in use before it stored its version: things only a
	 * site that used it has, none of which a new install creates by itself.
	 */
	private static function ran_before() {
		global $wpdb;

		if ( self::is_pro() && '' !== (string) get_option( 'wpzoom_social_icons_pro_license_key', '' ) ) {
			return true;
		}
		foreach ( array( 'wpzoom-social-icons-widget-settings', 'wpzoom_click_to_chat_settings', 'wpzoom_ai_chat' ) as $option ) {
			if ( false !== get_option( $option, false ) ) {
				return true;
			}
		}
		$widgets = get_option( 'widget_zoom-social-icons-widget', array() );
		if ( is_array( $widgets ) && array_filter( array_keys( $widgets ), 'is_int' ) ) {
			return true;
		}
		$block_widgets = get_option( 'widget_block', array() );
		if ( is_array( $block_widgets ) && false !== strpos( wp_json_encode( $block_widgets ), 'wpzoom-blocks/social-icons' ) ) {
			return true;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- runs once, when the version is first stored.
		return (bool) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type IN ( 'wpzoom-shortcode', 'wpzoom-sharing' )
				OR ( post_status IN ( 'publish', 'draft', 'private', 'future' ) AND post_content LIKE '%wp:wpzoom-blocks/social-icons%' )
			LIMIT 1"
		);
	}

	/** Whether the 30 days are running for this release. */
	private static function running() {
		$state = get_option( self::prefix() . '_whats_new', array() );
		return is_array( $state ) && isset( $state['release'], $state['since'] )
			&& self::release() === $state['release']
			&& time() < (int) $state['since'] + self::DAYS * DAY_IN_SECONDS;
	}

	/** Whether this admin should still see the notice, card and pointer. */
	public static function active() {
		return current_user_can( 'manage_options' )
			&& self::running()
			&& self::release() !== get_user_meta( get_current_user_id(), self::DISMISSED_META, true );
	}

	/** Whether the current screen is one of the plugin's own. */
	private static function own_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return false;
		}
		return in_array( $screen->post_type, array( 'wpzoom-shortcode', 'wpzoom-sharing' ), true )
			|| false !== strpos( $screen->id, 'wpzoom-shortcode_page_' );
	}

	private static function overview_url( $args = array() ) {
		$url = class_exists( 'WPZOOM_Connect_Overview' ) ? WPZOOM_Connect_Overview::url() : admin_url( 'edit.php?post_type=wpzoom-shortcode' );
		return add_query_arg( $args, $url );
	}

	private static function dismiss_url() {
		return wp_nonce_url( add_query_arg( 'wpzoom_whats_new_dismiss', '1' ), 'wpzoom_whats_new_dismiss' );
	}

	/** Close the notice, card and pointer for this admin. */
	public function handle_dismiss() {
		if ( empty( $_GET['wpzoom_whats_new_dismiss'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked below.
			return;
		}
		check_admin_referer( 'wpzoom_whats_new_dismiss' );
		self::dismiss();
		wp_safe_redirect( remove_query_arg( array( 'wpzoom_whats_new_dismiss', '_wpnonce' ) ) );
		exit;
	}

	private static function dismiss() {
		update_user_meta( get_current_user_id(), self::DISMISSED_META, self::release() );
		self::dismiss_pointer();
	}

	/** Mark the pointer seen, in core's list of dismissed pointers. */
	public static function dismiss_pointer() {
		$user_id   = get_current_user_id();
		$dismissed = array_filter( explode( ',', (string) get_user_meta( $user_id, 'dismissed_wp_pointers', true ) ) );
		if ( ! in_array( self::pointer_id(), $dismissed, true ) ) {
			$dismissed[] = self::pointer_id();
			update_user_meta( $user_id, 'dismissed_wp_pointers', implode( ',', $dismissed ) );
		}
	}

	private static function pointer_dismissed() {
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		return in_array( self::pointer_id(), $dismissed, true );
	}

	// -------------------------------------------------------------------------
	// Content
	// -------------------------------------------------------------------------

	private static function heading() {
		return self::is_pro()
			/* translators: %s: version number */
			? sprintf( __( 'WPZOOM Connect Pro %s is here', 'social-icons-widget-by-wpzoom' ), self::release() )
			/* translators: %s: version number */
			: sprintf( __( 'Social Icons Widget is now WPZOOM Connect %s', 'social-icons-widget-by-wpzoom' ), self::release() );
	}

	private static function summary() {
		return self::is_pro()
			? __( 'A new dashboard under Social Icons, 2,400+ icons, icons without links and the Google Preferred Sources button. Your icons, share buttons and settings are as you left them.', 'social-icons-widget-by-wpzoom' )
			: __( 'A new dashboard under Social Icons, faster SVG icons, 2,400+ icons and the Google Preferred Sources button. Your icons, share buttons and settings are as you left them.', 'social-icons-widget-by-wpzoom' );
	}

	private static function settings_url( $section ) {
		return admin_url( 'edit.php?post_type=wpzoom-shortcode&page=wpzoom-social-icons-widget#' . $section );
	}

	/**
	 * The highlights: icon, title, one line, and where to see it.
	 *
	 * @return array
	 */
	private static function highlights() {
		$pro   = self::is_pro();
		$items = array(
			array(
				'home',
				__( 'One place for everything', 'social-icons-widget-by-wpzoom' ),
				__( 'The menu is now Social Icons, opening on this Overview: icons, share buttons and chat, with what is set up on your site.', 'social-icons-widget-by-wpzoom' ),
				'',
			),
		);
		if ( ! $pro ) {
			$items[] = array(
				'sliders',
				__( 'Faster icons', 'social-icons-widget-by-wpzoom' ),
				__( 'Icons are drawn as SVG instead of icon fonts: up to 130 KB less per page, and no more clashes with a theme’s own Font Awesome.', 'social-icons-widget-by-wpzoom' ),
				'',
			);
		}
		$items[] = array(
			'social-icons',
			__( '2,400+ icons', 'social-icons-widget-by-wpzoom' ),
			__( '41 more networks (Twitch, Kick, Bluesky, Linktree…) and 148 Font Awesome 7 brands, in an icon library you can browse.', 'social-icons-widget-by-wpzoom' ),
			array( __( 'Browse icons', 'social-icons-widget-by-wpzoom' ), self::settings_url( 'font-styles' ) ),
		);
		$items[] = array(
			'layers',
			__( 'Icons without links', 'social-icons-widget-by-wpzoom' ),
			__( 'Leave an icon’s link empty for a plain icon, or add the new “Icon by WPZOOM” block for a single one: a phone, a feature, a decoration.', 'social-icons-widget-by-wpzoom' ),
			'',
		);
		$items[] = array(
			'google',
			__( 'Google Preferred Sources button', 'social-icons-widget-by-wpzoom' ),
			__( 'Readers who add your site see more of it in Google’s Top Stories and AI results.', 'social-icons-widget-by-wpzoom' ),
			array( __( 'Set it up', 'social-icons-widget-by-wpzoom' ), self::settings_url( 'preferred-source' ) ),
		);
		if ( $pro ) {
			$items[] = array(
				'key',
				__( 'Redesigned Settings and License pages', 'social-icons-widget-by-wpzoom' ),
				__( 'Settings are grouped by module, and the License page shows your license status at a glance.', 'social-icons-widget-by-wpzoom' ),
				'',
			);
		}
		$items[] = array(
			'message',
			__( 'Chat', 'social-icons-widget-by-wpzoom' ),
			__( 'Click to Chat has a redrawn launcher, and your chat button can now answer visitors itself with AI Chat.', 'social-icons-widget-by-wpzoom' ),
			array( __( 'Chat settings', 'social-icons-widget-by-wpzoom' ), admin_url( 'edit.php?post_type=wpzoom-shortcode&page=wpzoom-click-to-chat' ) ),
		);
		return $items;
	}

	/**
	 * Changes that may need a look on some sites.
	 *
	 * @return array
	 */
	private static function checks() {
		$items = array();
		if ( ! self::is_pro() ) {
			$items[] = __( 'If you styled icons with custom CSS that targets the icon font (such as :before rules), check them: the classes are the same, but icons are SVG now.', 'social-icons-widget-by-wpzoom' );
		}
		$items[] = __( 'Networks that have shut down (Google+, Vine, Skype…) are no longer in the icon picker. Icons already on your site keep working.', 'social-icons-widget-by-wpzoom' );
		$items[] = __( 'Socicons is now called WPZOOM Icons: the same icons, maintained by WPZOOM.', 'social-icons-widget-by-wpzoom' );
		if ( self::is_pro() ) {
			$items[] = __( 'Some icons used to show a different icon in the Social Icons block (e.g. “star” showed “star-of-life”). They show the right one now, and the block saves it the next time you edit the page.', 'social-icons-widget-by-wpzoom' );
		} else {
			$items[] = __( 'The switches to turn off the blocks and the widget are gone: icons load only where they are used, so they saved nothing.', 'social-icons-widget-by-wpzoom' );
		}
		return $items;
	}

	// -------------------------------------------------------------------------
	// Notice
	// -------------------------------------------------------------------------

	public function register_notice_center( $notices ) {
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		if ( ! self::active() ) {
			return $notices;
		}
		$notices[] = array(
			'id'             => self::prefix() . '_whats_new_' . str_replace( '.', '', self::release() ),
			'heading'        => self::heading(),
			'content'        => '<p>' . esc_html( self::summary() ) . '</p>',
			'icon'           => array(
				'type'             => 'svg',
				'svg'              => self::logo(),
				'color'            => '#ffffff',
				'background_color' => '#665cee',
			),
			'primary_button' => array(
				'label'   => __( 'See what’s new', 'social-icons-widget-by-wpzoom' ),
				'url'     => self::overview_url(),
				'new_tab' => false,
			),
			'capability'     => 'manage_options',
			'screens'        => array( 'dashboard', 'plugins' ),
			'source'         => 'WPZOOM Connect',
			'priority'       => 1,
		);
		return $notices;
	}

	/** Where the WPZOOM Notice Center isn't loaded (PRO): the same notice, plain. */
	public function render_notice() {
		if ( class_exists( 'WPZOOM_Notice_Center' ) || ! self::active() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
			return;
		}
		?>
		<div class="notice wpzoom-whats-new-notice" style="display:flex;gap:14px;align-items:center;padding:14px 16px;border-left-color:#665cee;">
			<span aria-hidden="true" style="flex:0 0 auto;display:grid;place-items:center;width:40px;height:40px;border-radius:10px;background:#665cee;color:#fff;"><?php echo self::logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			<div style="flex:1;min-width:0;">
				<p style="margin:0 0 2px;font-size:14px;font-weight:600;"><?php echo esc_html( self::heading() ); ?></p>
				<p style="margin:0;"><?php echo esc_html( self::summary() ); ?></p>
			</div>
			<a class="button button-primary" href="<?php echo esc_url( self::overview_url() ); ?>"><?php esc_html_e( 'See what’s new', 'social-icons-widget-by-wpzoom' ); ?></a>
			<a class="button-link" href="<?php echo esc_url( self::dismiss_url() ); ?>"><?php esc_html_e( 'Dismiss', 'social-icons-widget-by-wpzoom' ); ?></a>
		</div>
		<?php
	}

	/** The chat bubble mark, white on the tile. */
	private static function logo() {
		return '<svg width="22" height="22" viewBox="7 7 18 18" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M16 8.5c-4.4 0-8 3-8 6.8 0 2 1 3.8 2.6 5l-.6 3.2 3.4-1.8c.8.2 1.7.4 2.6.4 4.4 0 8-3 8-6.8s-3.6-6.8-8-6.8zM11.3 15.3a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0zM14.7 15.3a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0zM18.1 15.3a1.3 1.3 0 1 0 2.6 0a1.3 1.3 0 1 0-2.6 0z"/></svg>';
	}

	// -------------------------------------------------------------------------
	// Pointer
	// -------------------------------------------------------------------------

	/** The pointer on the "Social Icons" menu, on screens other than the plugin's own. */
	public function enqueue_pointer() {
		if ( ! self::active() || self::own_screen() || self::pointer_dismissed() ) {
			return;
		}
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );

		$content  = '<h3>' . esc_html( self::heading() ) . '</h3>';
		$content .= '<p>' . esc_html__( 'Everything now lives here: a new Overview, the Google Preferred Sources button, and redesigned Settings.', 'social-icons-widget-by-wpzoom' ) . '</p>';
		$content .= '<p><a class="button button-primary" href="' . esc_url( self::overview_url() ) . '">' . esc_html__( 'Take a look', 'social-icons-widget-by-wpzoom' ) . '</a></p>';

		$script = sprintf(
			'jQuery( function ( $ ) {
				var $menu = $( "#menu-posts-wpzoom-shortcode" );
				if ( ! $menu.length || typeof $.fn.pointer !== "function" ) { return; }
				$menu.pointer( {
					content: %1$s,
					position: { edge: "left", align: "center" },
					pointerWidth: 340,
					close: function () {
						$.post( ajaxurl, { pointer: %2$s, action: "dismiss-wp-pointer" } );
					}
				} ).pointer( "open" );
			} );',
			wp_json_encode( $content ),
			wp_json_encode( self::pointer_id() )
		);
		wp_add_inline_script( 'wp-pointer', $script );
	}

	// -------------------------------------------------------------------------
	// Overview card
	// -------------------------------------------------------------------------

	/**
	 * The card at the top of the Overview. Shown while active, and again on
	 * demand (?whats-new, the sidebar's "What's new" link). Opening the
	 * Overview also counts as having seen the pointer.
	 */
	public static function render_card() {
		$asked = isset( $_GET['whats-new'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( self::running() && current_user_can( 'manage_options' ) ) {
			self::dismiss_pointer();
		}
		if ( ! $asked && ! self::active() ) {
			return;
		}
		$icon = class_exists( 'WPZOOM_Connect_Overview' ) ? array( 'WPZOOM_Connect_Overview', 'icon' ) : null;
		?>
		<section class="wpzc-card wpzc-whats-new" id="whats-new">
			<div class="wpzc-card__head">
				<div>
					<p class="wpzc-whats-new__eyebrow">
						<?php
						/* translators: %s: version number */
						echo esc_html( sprintf( __( 'What’s new in %s', 'social-icons-widget-by-wpzoom' ), self::release() ) );
						?>
					</p>
					<h2><?php echo esc_html( self::heading() ); ?></h2>
				</div>
				<?php if ( self::active() ) : ?>
					<a class="wpzc-whats-new__close" href="<?php echo esc_url( self::dismiss_url() ); ?>"><?php esc_html_e( 'Got it', 'social-icons-widget-by-wpzoom' ); ?></a>
				<?php endif; ?>
			</div>

			<ul class="wpzc-whats-new__list">
				<?php foreach ( self::highlights() as $item ) : ?>
					<li>
						<span class="wpzc-whats-new__icon"><?php echo $icon ? call_user_func( $icon, $item[0], 18 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
						<div>
							<h3><?php echo esc_html( $item[1] ); ?></h3>
							<p><?php echo esc_html( $item[2] ); ?></p>
							<?php if ( $item[3] ) : ?>
								<a class="wpzc-link" href="<?php echo esc_url( $item[3][1] ); ?>"><?php echo esc_html( $item[3][0] ); ?> &rarr;</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<details class="wpzc-whats-new__checks">
				<summary><?php esc_html_e( 'Worth checking after the update', 'social-icons-widget-by-wpzoom' ); ?></summary>
				<ul>
					<?php foreach ( self::checks() as $check ) : ?>
						<li><?php echo esc_html( $check ); ?></li>
					<?php endforeach; ?>
				</ul>
			</details>
		</section>
		<?php
	}
}

WPZOOM_Connect_Whats_New::get_instance();
