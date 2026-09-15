<?php
/**
 * AI Chat — Yamidoo inside WPZOOM Connect.
 *
 * The fifth Click to Chat channel: instead of sending the visitor to WhatsApp,
 * the floating button answers them itself, from the site's own pages, and
 * hands off to a human (or WhatsApp) when needed. Powered by Yamidoo
 * (yamidoo.ai), a WPZOOM product.
 *
 * What lives here:
 *  - the "AI Chat" card at the top of the Click to Chat screen: a readiness
 *    scan of this site before connecting, one-click connect, on/off after;
 *  - the connect handshake with app.yamidoo.ai (state nonce → site id + token);
 *  - the front-end embed of the Yamidoo widget (same hardening as the
 *    standalone Yamidoo plugin: optimizer opt-outs, inline site-id stub);
 *  - hiding the WhatsApp/Telegram/Messenger/Viber launcher while AI Chat is
 *    on, so there is one floating button, not two.
 *
 * Nothing leaves the site until the owner clicks Connect (or Scan).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_AI_Chat {

	const OPTION_KEY       = 'wpzoom_ai_chat';
	const STATE_TRANSIENT  = 'wpzoom_ai_chat_connect_state';
	const NOTICE_DISMISSED = 'wpzoom_ai_chat_notice_dismissed';
	const HANDLE           = 'yamidoo-widget';
	const PAGE_SLUG        = 'wpzoom-click-to-chat';

	private static $instance = null;

	/** Site id for the current front-end request ('' when not loading). */
	private $site_id = '';

	private static $defaults = array(
		'site_id'            => '',
		'token'              => '',
		'enabled'            => true,
		'identify_logged_in' => true,
		'hide_click_to_chat' => true,
		'connected_at'       => 0,
	);

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Admin: card on the Click to Chat screen + connect handshake.
		add_action( 'wpzoom_chat_admin_tab', array( $this, 'render_tab' ) );
		add_filter( 'wpzoom_ai_chat_is_connected', array( __CLASS__, 'is_connected' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_wpzoom_ai_chat_connect', array( $this, 'handle_connect_start' ) );
		add_action( 'admin_post_wpzoom_ai_chat_disconnect', array( $this, 'handle_disconnect' ) );
		add_action( 'admin_post_wpzoom_ai_chat_save', array( $this, 'handle_save' ) );
		add_action( 'admin_init', array( $this, 'handle_connect_return' ) );
		add_action( 'admin_init', array( $this, 'handle_notice_dismiss' ) );
		add_action( 'admin_notices', array( $this, 'render_update_notice' ) );
		add_action( 'admin_head', array( $this, 'menu_badge_css' ) );
		add_action( 'admin_menu', array( $this, 'reorder_submenu' ), 99 );
		add_action( 'admin_init', array( $this, 'canonical_redirect' ) );
		add_filter( 'wpzoom_notice_center_notices', array( $this, 'register_notice_center' ) );

		// The Click to Chat launcher steps aside while AI Chat is on.
		add_filter( 'wpzoom_ctc_should_render', array( $this, 'filter_ctc_render' ) );

		// Front end.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'wp_script_attributes', array( $this, 'script_attributes' ) );
		add_filter( 'wp_inline_script_attributes', array( $this, 'inline_script_attributes' ) );
		// Optimizer exclusions (each only matters when that plugin is active).
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'exclude_patterns' ) );
		add_filter( 'rocket_exclude_defer_js', array( $this, 'exclude_patterns' ) );
		add_filter( 'rocket_exclude_js', array( $this, 'exclude_patterns' ) );
		add_filter( 'rocket_excluded_inline_js', array( $this, 'exclude_inline_patterns' ) );
		add_filter( 'litespeed_optm_js_defer_exc', array( $this, 'exclude_patterns' ) );
		add_filter( 'litespeed_optimize_js_excludes', array( $this, 'exclude_patterns' ) );
		add_filter( 'perfmatters_delay_js_exclusions', array( $this, 'exclude_patterns' ) );
		add_filter( 'autoptimize_filter_js_exclude', array( $this, 'exclude_autoptimize' ) );
		add_filter( 'sgo_js_async_exclude', array( $this, 'exclude_handle' ) );
		add_filter( 'sgo_js_minify_exclude', array( $this, 'exclude_handle' ) );
	}

	// -------------------------------------------------------------------------
	// Settings
	// -------------------------------------------------------------------------

	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::$defaults );
	}

	/** App origin. Filterable so a staging app can be used: add_filter( 'wpzoom_ai_chat_app_url', … ). */
	public static function app_url() {
		$url = defined( 'WPZOOM_AI_CHAT_APP_URL' ) ? WPZOOM_AI_CHAT_APP_URL : 'https://app.yamidoo.ai';
		return untrailingslashit( apply_filters( 'wpzoom_ai_chat_app_url', $url ) );
	}

	public static function is_uuid( $value ) {
		return is_string( $value ) && 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value );
	}

	public static function is_connected() {
		$s = self::get_settings();
		return self::is_uuid( $s['site_id'] );
	}

	/** The standalone Yamidoo plugin, when present and configured, owns the embed. */
	public static function standalone_plugin_active() {
		return class_exists( 'Yamidoo_Frontend' );
	}

	/** yamidoo.ai with a UTM so plugin traffic shows up in analytics. */
	public static function site_link( $medium = 'ai-chat-card' ) {
		return 'https://yamidoo.ai/?utm_source=wp-plugin&utm_medium=' . rawurlencode( $medium ) . '&utm_campaign=wpzoom-connect';
	}

	/** The Yamidoo mark (logo.svg), white on the brand square. */
	public static function logo_svg( $size = 24 ) {
		$size = (int) $size;
		return '<svg viewBox="-12 0 251 250" width="' . $size . '" height="' . $size . '" fill="currentColor" aria-hidden="true"><path d="M113.5 0C176.184 0 227 50.9256 227 113.745C227 176.565 168.555 250 105.87 250C102.47 250 99.104 249.849 95.7793 249.555C94.4106 249.434 93.604 247.981 94.1956 246.741L179.28 68.3939H148.899L124.797 117.11C122.636 121.44 120.61 125.635 118.72 129.695C116.965 133.619 115.277 137.949 113.657 142.685C112.037 137.949 110.348 133.619 108.593 129.695C106.838 125.635 104.88 121.44 102.719 117.11L79.0217 68.3939H47.2231L97.2192 170.011C97.4945 170.571 97.493 171.227 97.2151 171.785L73.9774 218.477C73.5189 219.398 72.4338 219.822 71.4783 219.44C29.5968 202.702 0 161.688 0 113.745C0 50.9256 50.8157 0 113.5 0Z"/></svg>';
	}

	public static function settings_url() {
		return admin_url( 'edit.php?post_type=wpzoom-shortcode&page=' . self::PAGE_SLUG . '&tab=ai' );
	}

	// -------------------------------------------------------------------------
	// Connect handshake
	// -------------------------------------------------------------------------

	/** "Connect" button → app.yamidoo.ai/connect/wordpress with a one-time state nonce. */
	public function handle_connect_start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'social-icons-widget-by-wpzoom' ), 403 );
		}
		check_admin_referer( 'wpzoom_ai_chat_connect' );

		$state = wp_generate_password( 24, false, false );
		set_transient( self::STATE_TRANSIENT, $state, 15 * MINUTE_IN_SECONDS );

		$return = self::settings_url();
		$url    = add_query_arg(
			array(
				'site'   => rawurlencode( home_url( '/' ) ),
				'return' => rawurlencode( $return ),
				'state'  => $state,
			),
			self::app_url() . '/connect/wordpress'
		);
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- external app by design.
		exit;
	}

	/** Back from the app with ?yamidoo_site_id=…&yamidoo_token=…&yamidoo_state=… */
	public function handle_connect_return() {
		if ( empty( $_GET['yamidoo_site_id'] ) || empty( $_GET['yamidoo_token'] ) || empty( $_GET['yamidoo_state'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$clean = self::settings_url();

		$expected = get_transient( self::STATE_TRANSIENT );
		$state    = sanitize_text_field( wp_unslash( $_GET['yamidoo_state'] ) );
		$site_id  = strtolower( sanitize_text_field( wp_unslash( $_GET['yamidoo_site_id'] ) ) );
		$token    = sanitize_text_field( wp_unslash( $_GET['yamidoo_token'] ) );

		if ( ! $expected || ! hash_equals( (string) $expected, $state ) || ! self::is_uuid( $site_id ) || 0 !== strpos( $token, 'ycw_' ) ) {
			wp_safe_redirect( add_query_arg( 'ai_chat', 'connect_failed', $clean ) );
			exit;
		}
		delete_transient( self::STATE_TRANSIENT );

		$s                 = self::get_settings();
		$s['site_id']      = $site_id;
		$s['token']        = $token;
		$s['enabled']      = true;
		$s['connected_at'] = time();
		update_option( self::OPTION_KEY, $s );
		// The one-time notice has done its job.
		update_option( self::NOTICE_DISMISSED, 1 );

		wp_safe_redirect( add_query_arg( 'ai_chat', 'connected', $clean ) );
		exit;
	}

	public function handle_disconnect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'social-icons-widget-by-wpzoom' ), 403 );
		}
		check_admin_referer( 'wpzoom_ai_chat_disconnect' );
		delete_option( self::OPTION_KEY );
		wp_safe_redirect( add_query_arg( 'ai_chat', 'disconnected', self::settings_url() ) );
		exit;
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'social-icons-widget-by-wpzoom' ), 403 );
		}
		check_admin_referer( 'wpzoom_ai_chat_save' );
		$s                       = self::get_settings();
		$s['enabled']            = ! empty( $_POST['ai_enabled'] );
		$s['identify_logged_in'] = ! empty( $_POST['ai_identify'] );
		$s['hide_click_to_chat'] = ! empty( $_POST['ai_hide_ctc'] );
		update_option( self::OPTION_KEY, $s );
		wp_safe_redirect( add_query_arg( 'ai_chat', 'saved', self::settings_url() ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Admin UI
	// -------------------------------------------------------------------------

	public function enqueue_admin_assets( $hook ) {
		if ( 'wpzoom-shortcode_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'wpzoom-ai-chat-admin',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-ai-chat-admin.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);
		wp_enqueue_script(
			'wpzoom-ai-chat-admin',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/js/wpzoom-ai-chat-admin.js',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION,
			true
		);
		wp_localize_script(
			'wpzoom-ai-chat-admin',
			'wpzoomAiChat',
			array(
				'apiBase' => self::app_url(),
				'siteUrl' => home_url( '/' ),
				'i18n'    => array(
					'discover'   => __( 'Finding your sitemap…', 'social-icons-widget-by-wpzoom' ),
					'read'       => __( 'Reading %d pages…', 'social-icons-widget-by-wpzoom' ),
					'answer'     => __( 'Working out what your visitors will ask…', 'social-icons-widget-by-wpzoom' ),
					'failed'     => __( 'The scan didn’t work this time. You can still connect — the AI indexes your site after connecting.', 'social-icons-widget-by-wpzoom' ),
					'answered'   => __( 'Answered from', 'social-icons-widget-by-wpzoom' ),
					'more'       => __( '%d more questions answered — and the ones your site doesn’t answer yet — once connected.', 'social-icons-widget-by-wpzoom' ),
					'gaps'       => __( 'Questions your site doesn’t answer yet', 'social-icons-widget-by-wpzoom' ),
					'readiness'  => __( 'of likely visitor questions answered by your pages', 'social-icons-widget-by-wpzoom' ),
					'presale'    => __( 'Pre-sale', 'social-icons-widget-by-wpzoom' ),
					'howto'      => __( 'How-to', 'social-icons-widget-by-wpzoom' ),
					'policy'     => __( 'Policy', 'social-icons-widget-by-wpzoom' ),
					'technical'  => __( 'Technical', 'social-icons-widget-by-wpzoom' ),
					'general'    => __( 'General', 'social-icons-widget-by-wpzoom' ),
					'stats'      => __( '%1$d pages read in %2$ss', 'social-icons-widget-by-wpzoom' ),
				),
			)
		);
	}

	/** The AI Chat tab of the Chat screen. */
	public function render_tab( $tab ) {
		if ( 'ai' !== $tab || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s         = self::get_settings();
		$connected = self::is_connected();
		$flash     = isset( $_GET['ai_chat'] ) ? sanitize_key( wp_unslash( $_GET['ai_chat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag.
		$messages  = array(
			'connected'      => array( 'success', __( 'AI Chat is on. Yamidoo is reading your site now — first answers are usually ready within a few minutes.', 'social-icons-widget-by-wpzoom' ) ),
			'saved'          => array( 'success', __( 'Settings saved.', 'social-icons-widget-by-wpzoom' ) ),
			'disconnected'   => array( 'info', __( 'Disconnected. The chat is no longer shown on your site.', 'social-icons-widget-by-wpzoom' ) ),
			'connect_failed' => array( 'error', __( 'The connection didn’t complete (the link expired or was altered). Please click Connect again.', 'social-icons-widget-by-wpzoom' ) ),
		);
		?>
		<?php if ( isset( $messages[ $flash ] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $messages[ $flash ][0] ); ?> is-dismissible wpzoom-ai-chat-flash"><p><?php echo esc_html( $messages[ $flash ][1] ); ?></p></div>
		<?php endif; ?>
		<div id="wpzoom-ai-chat" class="wpzoom-ctc-card wpzoom-ai-chat-card">

			<div class="wpzoom-ai-chat-head">
				<span class="wpzoom-ai-chat-logo" aria-hidden="true"><?php echo self::logo_svg( 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
				<div class="wpzoom-ai-chat-title">
					<h2>
						<?php esc_html_e( 'AI Chat', 'social-icons-widget-by-wpzoom' ); ?>
						<?php if ( $connected ) : ?>
							<span class="wpzoom-ai-chat-badge"><?php esc_html_e( 'Connected', 'social-icons-widget-by-wpzoom' ); ?></span>
						<?php endif; ?>
					</h2>
					<p>
						<?php esc_html_e( 'Your floating chat button can now answer visitors itself — from your own pages, in seconds, 24/7 — and hand off to you when a human is needed.', 'social-icons-widget-by-wpzoom' ); ?>
						<?php
						printf(
							/* translators: %s: link to yamidoo.ai */
							esc_html__( 'Powered by %s, a WPZOOM product.', 'social-icons-widget-by-wpzoom' ),
							'<a href="' . esc_url( self::site_link( 'ai-chat-card' ) ) . '" target="_blank" rel="noopener">Yamidoo.ai ↗</a>'
						);
						?>
					</p>
				</div>
			</div>

			<?php if ( self::standalone_plugin_active() && ! $connected ) : ?>
				<p class="wpzoom-ai-chat-note">
					<?php esc_html_e( 'The standalone Yamidoo plugin is active on this site, so the chat is managed from its settings page.', 'social-icons-widget-by-wpzoom' ); ?>
				</p>
			<?php elseif ( ! $connected ) : ?>
				<div class="wpzoom-ai-chat-cta">
					<div class="wpzoom-ai-chat-cta-text">
						<strong><?php esc_html_e( 'See it before you switch it on.', 'social-icons-widget-by-wpzoom' ); ?></strong>
						<?php esc_html_e( 'The scan reads a few of your pages and shows the questions your visitors are likely to ask — answered from your content. Takes about ten seconds; nothing is installed.', 'social-icons-widget-by-wpzoom' ); ?>
					</div>
					<div class="wpzoom-ai-chat-cta-actions">
						<button type="button" class="button button-secondary" id="wpzoom-ai-chat-scan"><?php esc_html_e( 'Scan my site', 'social-icons-widget-by-wpzoom' ); ?></button>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpzoom-ai-chat-inline-form">
							<?php wp_nonce_field( 'wpzoom_ai_chat_connect' ); ?>
							<input type="hidden" name="action" value="wpzoom_ai_chat_connect">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Turn on AI Chat — free', 'social-icons-widget-by-wpzoom' ); ?></button>
						</form>
					</div>
				</div>
				<div id="wpzoom-ai-chat-scan-result" class="wpzoom-ai-chat-scan" hidden></div>
				<p class="wpzoom-ai-chat-offer">
					<span class="dashicons dashicons-tag"></span>
					<span>
						<?php esc_html_e( 'WordPress founding offer: 50% off any Yamidoo plan for your first year, applied automatically when you connect from here. The free plan needs no card.', 'social-icons-widget-by-wpzoom' ); ?>
						<a href="<?php echo esc_url( self::site_link( 'ai-chat-offer' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See how Yamidoo works and what it costs ↗', 'social-icons-widget-by-wpzoom' ); ?></a>
					</span>
				</p>

				<?php $this->render_why(); ?>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpzoom-ai-chat-settings">
					<?php wp_nonce_field( 'wpzoom_ai_chat_save' ); ?>
					<input type="hidden" name="action" value="wpzoom_ai_chat_save">
					<?php
					$rows = array(
						array(
							'name'  => 'ai_enabled',
							'on'    => ! empty( $s['enabled'] ),
							'title' => __( 'Show the AI chat on my site', 'social-icons-widget-by-wpzoom' ),
							'desc'  => __( 'Switch off to hide the chat without disconnecting.', 'social-icons-widget-by-wpzoom' ),
						),
						array(
							'name'  => 'ai_identify',
							'on'    => ! empty( $s['identify_logged_in'] ),
							'title' => __( 'Identify logged-in users', 'social-icons-widget-by-wpzoom' ),
							'desc'  => __( 'Passes the name and email of logged-in WordPress users, so your team sees who is chatting.', 'social-icons-widget-by-wpzoom' ),
						),
						array(
							'name'  => 'ai_hide_ctc',
							'on'    => ! empty( $s['hide_click_to_chat'] ),
							'title' => __( 'Hide the Click to Chat launcher while AI Chat is on', 'social-icons-widget-by-wpzoom' ),
							'desc'  => __( 'One floating button instead of two. Turn off to show the WhatsApp/Telegram/Messenger buttons alongside the chat.', 'social-icons-widget-by-wpzoom' ),
						),
					);
					foreach ( $rows as $row ) :
						?>
						<label class="wpzoom-ai-chat-row">
							<span class="wpzoom-ai-chat-row-text">
								<span class="wpzoom-ai-chat-row-title"><?php echo esc_html( $row['title'] ); ?></span>
								<span class="wpzoom-ai-chat-row-desc"><?php echo esc_html( $row['desc'] ); ?></span>
							</span>
							<span class="wpzoom-ctc-toggle-switch">
								<input type="checkbox" name="<?php echo esc_attr( $row['name'] ); ?>" value="1" <?php checked( $row['on'] ); ?>>
								<span class="wpzoom-ctc-slider"></span>
							</span>
						</label>
					<?php endforeach; ?>
					<div class="wpzoom-ai-chat-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'social-icons-widget-by-wpzoom' ); ?></button>
						<a class="button" href="<?php echo esc_url( self::app_url() . '/dashboard/sites/' . rawurlencode( $s['site_id'] ) ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Open Yamidoo dashboard', 'social-icons-widget-by-wpzoom' ); ?> ↗
						</a>
						<span class="wpzoom-ai-chat-meta">
							<?php
							/* translators: %s: site id */
							printf( esc_html__( 'Site ID %s', 'social-icons-widget-by-wpzoom' ), '<code>' . esc_html( $s['site_id'] ) . '</code>' );
							?>
						</span>
					</div>
				</form>
				<div class="wpzoom-ai-chat-foot">
					<p class="wpzoom-ai-chat-note">
						<?php esc_html_e( 'Colors, welcome message, suggested questions, human handoff and everything else are set in the Yamidoo dashboard — changes go live without touching WordPress.', 'social-icons-widget-by-wpzoom' ); ?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpzoom-ai-chat-disconnect" onsubmit="return confirm('<?php echo esc_js( __( 'Disconnect this site from Yamidoo? The chat will disappear from your site; your Yamidoo account and data stay.', 'social-icons-widget-by-wpzoom' ) ); ?>');">
						<?php wp_nonce_field( 'wpzoom_ai_chat_disconnect' ); ?>
						<input type="hidden" name="action" value="wpzoom_ai_chat_disconnect">
						<button type="submit" class="button-link wpzoom-ai-chat-disconnect-link"><?php esc_html_e( 'Disconnect', 'social-icons-widget-by-wpzoom' ); ?></button>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/** Why use it: what the dashboard looks like, and the reasons in one screen. */
	private function render_why() {
		$img = WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/images/yamidoo/';
		$shots = array(
			array(
				'src'   => $img . 'chat.png',
				'title' => __( 'What your visitors see', 'social-icons-widget-by-wpzoom' ),
				'desc'  => __( 'A clean chat in your colors: the AI answers first, a person takes over when asked — in the same window.', 'social-icons-widget-by-wpzoom' ),
			),
			array(
				'src'   => $img . 'live-monitor.png',
				'title' => __( 'Watch conversations live', 'social-icons-widget-by-wpzoom' ),
				'desc'  => __( 'See who is on your site and what they ask, as it happens — and step in whenever you want.', 'social-icons-widget-by-wpzoom' ),
			),
			array(
				'src'   => $img . 'handoff.png',
				'title' => __( 'Hand off to a human', 'social-icons-widget-by-wpzoom' ),
				'desc'  => __( 'When a visitor asks for a person, the AI passes the whole conversation to your team, with context.', 'social-icons-widget-by-wpzoom' ),
			),
		);
		$reasons = array(
			array( 'dashicons-superhero', __( 'Answers from your own content', 'social-icons-widget-by-wpzoom' ), __( 'Replies in seconds from your pages, docs and Q&A — not generic web data. In your visitors’ language.', 'social-icons-widget-by-wpzoom' ) ),
			array( 'dashicons-clock', __( '24/7, no more waiting for email', 'social-icons-widget-by-wpzoom' ), __( 'Pre-sale and support questions get answered on the spot, at 3 a.m. too, instead of joining an inbox.', 'social-icons-widget-by-wpzoom' ) ),
			array( 'dashicons-update', __( 'Always up to date', 'social-icons-widget-by-wpzoom' ), __( 'Your site is indexed automatically; re-sync anytime, add PDFs or Q&A pairs for anything not on a page.', 'social-icons-widget-by-wpzoom' ) ),
			array( 'dashicons-chart-bar', __( 'Knows what your site is missing', 'social-icons-widget-by-wpzoom' ), __( 'Ratings on every answer, plus a list of the questions your content can’t answer yet — your docs to-do list.', 'social-icons-widget-by-wpzoom' ) ),
			array( 'dashicons-format-image', __( 'Reads screenshots and PDFs', 'social-icons-widget-by-wpzoom' ), __( 'Visitors can attach an error screenshot or a receipt; the AI reads it and answers accordingly.', 'social-icons-widget-by-wpzoom' ) ),
			array( 'dashicons-lock', __( 'Private by default', 'social-icons-widget-by-wpzoom' ), __( 'Your workspace is isolated, EU company, GDPR-ready, and your content is never used to train shared models.', 'social-icons-widget-by-wpzoom' ) ),
		);
		?>
		<div class="wpzoom-ai-chat-steps">
			<h3><?php esc_html_e( 'Live in three steps, no code', 'social-icons-widget-by-wpzoom' ); ?></h3>
			<ol>
				<li>
					<span class="wpzoom-ai-chat-step-n">1</span>
					<span><strong><?php esc_html_e( 'Connect', 'social-icons-widget-by-wpzoom' ); ?></strong><?php esc_html_e( 'Click “Turn on AI Chat”, sign in with Google or create a free account. Your site is added automatically.', 'social-icons-widget-by-wpzoom' ); ?></span>
				</li>
				<li>
					<span class="wpzoom-ai-chat-step-n">2</span>
					<span><strong><?php esc_html_e( 'Train', 'social-icons-widget-by-wpzoom' ); ?></strong><?php esc_html_e( 'Yamidoo reads your pages on its own — first answers are ready in minutes. Add docs or Q&A anytime.', 'social-icons-widget-by-wpzoom' ); ?></span>
				</li>
				<li>
					<span class="wpzoom-ai-chat-step-n">3</span>
					<span><strong><?php esc_html_e( 'Done', 'social-icons-widget-by-wpzoom' ); ?></strong><?php esc_html_e( 'The chat is live on your site. Watch conversations and jump in from the Yamidoo dashboard.', 'social-icons-widget-by-wpzoom' ); ?></span>
				</li>
			</ol>
		</div>

		<?php
		$niches = array(
			array( 'dashicons-cart', __( 'Online stores', 'social-icons-widget-by-wpzoom' ), array(
				__( 'Pre-sale questions answered before the cart is abandoned', 'social-icons-widget-by-wpzoom' ),
				__( 'Shipping, returns & sizing — 24/7', 'social-icons-widget-by-wpzoom' ),
				__( '“Where is my order?” handed to your team', 'social-icons-widget-by-wpzoom' ),
			) ),
			array( 'dashicons-admin-plugins', __( 'SaaS, apps & plugins', 'social-icons-widget-by-wpzoom' ), array(
				__( 'Pricing & plan questions on the pricing page', 'social-icons-widget-by-wpzoom' ),
				__( 'Setup and how-to straight from your docs', 'social-icons-widget-by-wpzoom' ),
				__( 'Fewer tickets for things already documented', 'social-icons-widget-by-wpzoom' ),
			) ),
			array( 'dashicons-businessperson', __( 'Agencies, consultants & services', 'social-icons-widget-by-wpzoom' ), array(
				__( '“Do you do X?” answered before the contact form', 'social-icons-widget-by-wpzoom' ),
				__( 'Leads qualified while you sleep', 'social-icons-widget-by-wpzoom' ),
				__( 'Serious enquiries handed to you, with context', 'social-icons-widget-by-wpzoom' ),
			) ),
			array( 'dashicons-food', __( 'Hotels, restaurants & venues', 'social-icons-widget-by-wpzoom' ), array(
				__( 'Hours, directions, menu & availability — instantly', 'social-icons-widget-by-wpzoom' ),
				__( 'Answers in the guest’s own language', 'social-icons-widget-by-wpzoom' ),
				__( 'Booking requests handed to your staff', 'social-icons-widget-by-wpzoom' ),
			) ),
			array( 'dashicons-store', __( 'Local businesses', 'social-icons-widget-by-wpzoom' ), array(
				__( 'Opening hours, location & prices without a call', 'social-icons-widget-by-wpzoom' ),
				__( 'Appointment requests handed to you', 'social-icons-widget-by-wpzoom' ),
				__( 'After-hours enquiries that don’t go elsewhere', 'social-icons-widget-by-wpzoom' ),
			) ),
			array( 'dashicons-welcome-learn-more', __( 'Courses, schools & creators', 'social-icons-widget-by-wpzoom' ), array(
				__( 'Enrolment, dates & requirements answered at once', 'social-icons-widget-by-wpzoom' ),
				__( 'Student and reader support 24/7', 'social-icons-widget-by-wpzoom' ),
				__( 'Fewer repeat emails for a small team', 'social-icons-widget-by-wpzoom' ),
			) ),
		);
		?>
		<div class="wpzoom-ai-chat-niches">
			<h3><?php esc_html_e( 'Who it’s for', 'social-icons-widget-by-wpzoom' ); ?></h3>
			<p class="wpzoom-ai-chat-niches-intro"><?php esc_html_e( 'Faster pre-sales, faster support, answers at any hour — whatever your site sells or does.', 'social-icons-widget-by-wpzoom' ); ?></p>
			<div class="wpzoom-ai-chat-niche-grid">
				<?php foreach ( $niches as $n ) : ?>
					<div class="wpzoom-ai-chat-niche">
						<div class="wpzoom-ai-chat-niche-head">
							<span class="dashicons <?php echo esc_attr( $n[0] ); ?>"></span>
							<strong><?php echo esc_html( $n[1] ); ?></strong>
						</div>
						<ul>
							<?php foreach ( $n[2] as $benefit ) : ?>
								<li><span class="dashicons dashicons-yes"></span><?php echo esc_html( $benefit ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="wpzoom-ai-chat-why">
			<h3><?php esc_html_e( 'Why Yamidoo', 'social-icons-widget-by-wpzoom' ); ?></h3>
			<div class="wpzoom-ai-chat-shots">
				<?php foreach ( $shots as $shot ) : ?>
					<figure>
						<img src="<?php echo esc_url( $shot['src'] ); ?>" alt="<?php echo esc_attr( $shot['title'] ); ?>" loading="lazy">
						<figcaption><strong><?php echo esc_html( $shot['title'] ); ?></strong> <?php echo esc_html( $shot['desc'] ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
			<ul class="wpzoom-ai-chat-reasons">
				<?php foreach ( $reasons as $r ) : ?>
					<li>
						<span class="dashicons <?php echo esc_attr( $r[0] ); ?>"></span>
						<span><strong><?php echo esc_html( $r[1] ); ?></strong><?php echo esc_html( $r[2] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="wpzoom-ai-chat-why-more">
				<a href="<?php echo esc_url( self::site_link( 'ai-chat-why' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See all features on yamidoo.ai ↗', 'social-icons-widget-by-wpzoom' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Move the AI Chat page to the top of the plugin's submenu. WordPress points
	 * the top-level "AI Chat" item at whatever comes first, so this also makes
	 * the parent link open the chat screen instead of the Icon Sets list.
	 */
	public function reorder_submenu() {
		global $submenu;
		$parent = 'edit.php?post_type=wpzoom-shortcode';
		if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
			return;
		}
		$items = $submenu[ $parent ];
		foreach ( $items as $key => $item ) {
			if ( isset( $item[2] ) && self::PAGE_SLUG === $item[2] ) {
				unset( $items[ $key ] );
				array_unshift( $items, $item );
				$submenu[ $parent ] = array_values( $items ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentional menu reorder.
				return;
			}
		}
	}

	/**
	 * The top-level "AI Chat" link WordPress generates is admin.php?page=…, which
	 * renders the screen but doesn't mark the submenu item current. Send it to
	 * the canonical edit.php?post_type=…&page=… URL (query string preserved).
	 */
	public function canonical_redirect() {
		global $pagenow;
		if ( 'admin.php' !== $pagenow || ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
			return;
		}
		$args = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $args['page'] );
		$url = add_query_arg( array_map( 'sanitize_text_field', wp_unslash( $args ) ), admin_url( 'edit.php?post_type=wpzoom-shortcode&page=' . self::PAGE_SLUG ) );
		wp_safe_redirect( $url );
		exit;
	}

	/** The orange "New" badge on the AI Chat menu item — the menu is on every screen, so this is too. */
	public function menu_badge_css() {
		echo '<style>#adminmenu .wpzoom-new-badge{background:#fe551b;color:#fff;font-size:10px;line-height:1;padding:3px 6px;border-radius:3px;margin-left:5px;text-transform:uppercase;font-weight:600;vertical-align:middle;letter-spacing:.02em}</style>';
	}

	// -------------------------------------------------------------------------
	// Notices
	// -------------------------------------------------------------------------

	/** Notice-center card while AI Chat is off. */
	public function register_notice_center( $notices ) {
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		if ( self::is_connected() || self::standalone_plugin_active() ) {
			return $notices;
		}
		$notices[] = array(
			'id'             => 'wpzoom_ai_chat',
			'heading'        => __( 'Your chat button can now answer questions itself', 'social-icons-widget-by-wpzoom' ),
			'content'        => '<p>' . sprintf(
				/* translators: %s: link to yamidoo.ai */
				esc_html__( 'AI Chat answers visitors from your own pages in seconds and hands off to you when needed — powered by %s, a WPZOOM product. Scan your site first to see what it would say, then turn it on with one click. Free plan, plus 50%% off your first year on any paid plan.', 'social-icons-widget-by-wpzoom' ),
				'<a href="' . esc_url( self::site_link( 'notice-center' ) ) . '" target="_blank" rel="noopener">Yamidoo.ai</a>'
			) . '</p>',
			'icon'           => array(
				'type'             => 'svg',
				'svg'              => self::logo_svg( 22 ),
				'color'            => '#ffffff',
				'background_color' => '#fe551b',
			),
			'primary_button' => array(
				'label'   => __( 'See what visitors would ask', 'social-icons-widget-by-wpzoom' ),
				'url'     => self::settings_url(),
				'new_tab' => false,
			),
			'capability'     => 'manage_options',
			'screens'        => array( 'dashboard', 'plugins', 'edit-wpzoom-shortcode' ),
			'source'         => 'WPZOOM Connect',
			'priority'       => 5,
		);
		return $notices;
	}

	/**
	 * One dismissible notice after the update — Dashboard and Plugins screens only,
	 * and only where the WPZOOM Notice Center isn't available (it carries the same
	 * card, so showing both would be the double-nag we're avoiding).
	 */
	public function render_update_notice() {
		if ( ! current_user_can( 'manage_options' ) || self::is_connected() || self::standalone_plugin_active() ) {
			return;
		}
		if ( class_exists( 'WPZOOM_Notice_Center' ) ) {
			return;
		}
		if ( get_option( self::NOTICE_DISMISSED ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
			return;
		}
		$dismiss = wp_nonce_url( add_query_arg( 'wpzoom_ai_chat_dismiss', '1' ), 'wpzoom_ai_chat_dismiss' );
		?>
		<div class="notice notice-info wpzoom-ai-chat-notice">
			<p>
				<strong><?php esc_html_e( 'WPZOOM Connect: your Click to Chat button can now answer questions itself.', 'social-icons-widget-by-wpzoom' ); ?></strong>
				<?php
				printf(
					/* translators: %s: link to yamidoo.ai */
					esc_html__( 'AI Chat replies to visitors from your own pages in seconds and hands off to you when needed — powered by %s. Everything you had still works.', 'social-icons-widget-by-wpzoom' ),
					'<a href="' . esc_url( self::site_link( 'update-notice' ) ) . '" target="_blank" rel="noopener">Yamidoo.ai</a>'
				);
				?>
				<a class="button button-primary button-small" href="<?php echo esc_url( self::settings_url() ); ?>"><?php esc_html_e( 'See what visitors would ask', 'social-icons-widget-by-wpzoom' ); ?></a>
				<a class="wpzoom-ai-chat-notice-dismiss" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'social-icons-widget-by-wpzoom' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function handle_notice_dismiss() {
		if ( empty( $_GET['wpzoom_ai_chat_dismiss'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wpzoom_ai_chat_dismiss' );
		update_option( self::NOTICE_DISMISSED, 1 );
		wp_safe_redirect( remove_query_arg( array( 'wpzoom_ai_chat_dismiss', '_wpnonce' ) ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Front end
	// -------------------------------------------------------------------------

	private function should_load() {
		if ( self::standalone_plugin_active() ) {
			return false; // The standalone plugin renders the widget.
		}
		$s = self::get_settings();
		if ( empty( $s['enabled'] ) || ! self::is_uuid( $s['site_id'] ) ) {
			return false;
		}
		return (bool) apply_filters( 'wpzoom_ai_chat_should_load', true );
	}

	/** While AI Chat is on (and the owner kept the default), the WhatsApp launcher hides. */
	public function filter_ctc_render( $render ) {
		if ( ! $render ) {
			return $render;
		}
		$s = self::get_settings();
		if ( ! empty( $s['hide_click_to_chat'] ) && $this->should_load() ) {
			return false;
		}
		return $render;
	}

	public function enqueue() {
		if ( ! $this->should_load() ) {
			return;
		}
		$s             = self::get_settings();
		$this->site_id = $s['site_id'];

		wp_enqueue_script(
			self::HANDLE,
			self::app_url() . '/widget.js',
			array(),
			null, // Versioned by the service; a ?ver= would only defeat its caching.
			array(
				'strategy'  => 'async',
				'in_footer' => true,
			)
		);
		wp_add_inline_script( self::HANDLE, $this->inline_js( ! empty( $s['identify_logged_in'] ) ), 'before' );
	}

	/** Queue stub + site id (survives tag rewrites) + optional identity of the logged-in user. */
	private function inline_js( $identify ) {
		$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$js    = 'window.yamidoo=window.yamidoo||function(){(window.yamidoo.q=window.yamidoo.q||[]).push(arguments)};';
		$js   .= 'window.yamidooSiteId=' . wp_json_encode( $this->site_id, $flags ) . ';';
		if ( ! $identify ) {
			return $js;
		}
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$uid  = (string) $user->ID;
			$data = array(
				'name'     => html_entity_decode( $user->display_name, ENT_QUOTES, 'UTF-8' ),
				'email'    => $user->user_email,
				'userId'   => $uid,
				'username' => $user->user_login,
			);
			$js .= 'try{var u=' . wp_json_encode( $uid, $flags ) . ";if(localStorage.getItem('yamidoo_wp_uid')!==u){window.yamidoo('logout');localStorage.setItem('yamidoo_wp_uid',u);}}catch(e){}";
			$js .= 'window.yamidoo(' . wp_json_encode( 'identify', $flags ) . ',' . wp_json_encode( $data, $flags ) . ');';
			return $js;
		}
		$js .= "try{if(localStorage.getItem('yamidoo_wp_uid')){window.yamidoo('logout');localStorage.removeItem('yamidoo_wp_uid');}}catch(e){}";
		return $js;
	}

	private function optimizer_attributes() {
		return array(
			'nowprocket'       => true,
			'data-cfasync'     => 'false',
			'data-no-optimize' => '1',
			'data-noptimize'   => '1',
		);
	}

	public function script_attributes( $attributes ) {
		if ( '' === $this->site_id || ! isset( $attributes['id'] ) || self::HANDLE . '-js' !== $attributes['id'] ) {
			return $attributes;
		}
		$attributes['data-site-id'] = $this->site_id;
		return array_merge( $attributes, $this->optimizer_attributes() );
	}

	public function inline_script_attributes( $attributes ) {
		if ( '' === $this->site_id || ! isset( $attributes['id'] ) || self::HANDLE . '-js-before' !== $attributes['id'] ) {
			return $attributes;
		}
		return array_merge( $attributes, $this->optimizer_attributes() );
	}

	public function exclude_patterns( $excluded ) {
		$excluded   = is_array( $excluded ) ? $excluded : array();
		$excluded[] = 'widget.js';
		$excluded[] = 'yamidoo';
		return $excluded;
	}

	public function exclude_inline_patterns( $excluded ) {
		$excluded   = is_array( $excluded ) ? $excluded : array();
		$excluded[] = 'window.yamidoo';
		return $excluded;
	}

	public function exclude_autoptimize( $excluded ) {
		$excluded = is_string( $excluded ) ? $excluded : '';
		return ( '' === $excluded ? '' : $excluded . ', ' ) . 'widget.js, yamidoo';
	}

	public function exclude_handle( $excluded ) {
		$excluded   = is_array( $excluded ) ? $excluded : array();
		$excluded[] = self::HANDLE;
		return $excluded;
	}
}

WPZOOM_AI_Chat::get_instance();
