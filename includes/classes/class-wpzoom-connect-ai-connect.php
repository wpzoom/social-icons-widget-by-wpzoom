<?php
/**
 * "Manage with AI": WPZOOM AI Connect, offered from inside WPZOOM Connect.
 *
 * WPZOOM AI Connect (a separate, free plugin on wordpress.org) lets Claude, ChatGPT and other AI assistants work on
 * the site. Until it is active, WPZOOM Connect offers it in its own screens:
 *
 *   - a "Manage with AI" item in the plugin's own menu (under Social Icons) and in the Overview's navigation, opening a
 *     page with one button that installs and activates AI Connect, then takes the user to its screen;
 *   - a card in the Overview's side column;
 *   - one notice on the Dashboard and Plugins screens, through the WPZOOM Notice Center, from a week after this
 *     version first runs until two months after, never while the "What's new" notice is showing. Each admin
 *     dismisses it for themselves.
 *
 * None of it shows on WordPress older than 6.9 (AI Connect needs the Abilities API), to users who can't install or
 * activate plugins, or once AI Connect is active. Nothing is installed until the user clicks the button.
 *
 * @package WPZOOM_Social_Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Connect_AI_Connect {

	const PAGE_SLUG   = 'wpzoom-connect-ai';
	const PLUGIN_SLUG = 'wpzoom-ai-connect';
	const PLUGIN_FILE = 'wpzoom-ai-connect/wpzoom-ai-connect.php';
	const AJAX_ACTION = 'wpzoom_connect_install_ai_connect';
	const NOTICE_ID   = 'wpzoom_connect_ai_connect';
	/** Option: when this version first ran, which starts the notice's window. */
	const SINCE       = 'wpzoom_connect_ai_connect_since';
	/** Source tag AI Connect reports with "Connect this site", to compare channels. */
	const REF         = 'social-icons';

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 6 );
		add_action( 'load-wpzoom-shortcode_page_' . self::PAGE_SLUG, array( $this, 'redirect_when_active' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_install' ) );
		add_filter( 'wpzoom_notice_center_notices', array( $this, 'register_notice' ) );
	}

	/* ----- state ----- */

	private static function load_plugin_functions() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	public static function is_installed() {
		return file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE );
	}

	public static function is_active() {
		self::load_plugin_functions();
		return is_plugin_active( self::PLUGIN_FILE );
	}

	/** Whether to offer AI Connect to the current user: it can run here, isn't active yet, and they can set it up. */
	public static function available() {
		if ( ! function_exists( 'wp_register_ability' ) || self::is_active() ) {
			return false;
		}
		return self::is_installed() ? current_user_can( 'activate_plugin', self::PLUGIN_FILE ) : current_user_can( 'install_plugins' );
	}

	/** The "Manage with AI" page. */
	public static function url() {
		return admin_url( 'edit.php?post_type=wpzoom-shortcode&page=' . self::PAGE_SLUG );
	}

	/** AI Connect's own screen, tagged with where the user came from. */
	public static function ai_connect_url() {
		return add_query_arg( array( 'page' => 'wpzoom-ai-connect', 'ref' => self::REF ), admin_url( 'admin.php' ) );
	}

	/** WordPress's own install screen for AI Connect: the fallback when the one-click install can't run. */
	private static function core_install_url() {
		return wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . self::PLUGIN_SLUG ), 'install-plugin_' . self::PLUGIN_SLUG );
	}

	private static function button_label() {
		return self::is_installed() ? __( 'Activate AI Connect', 'social-icons-widget-by-wpzoom' ) : __( 'Install AI Connect', 'social-icons-widget-by-wpzoom' );
	}

	/* ----- menu and page ----- */

	public function add_menu_item() {
		if ( ! self::available() ) {
			return;
		}
		add_submenu_page(
			'edit.php?post_type=wpzoom-shortcode',
			__( 'Manage with AI', 'social-icons-widget-by-wpzoom' ),
			__( 'Manage with AI', 'social-icons-widget-by-wpzoom' ),
			'activate_plugins',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/** Once AI Connect is active the page has nothing to offer: go to AI Connect itself. */
	public function redirect_when_active() {
		if ( self::is_active() ) {
			wp_safe_redirect( self::ai_connect_url() );
			exit;
		}
	}

	/** The button's script: install over AJAX, then go to AI Connect; on failure, offer WordPress's own install screen. */
	private static function print_script() {
		$config = array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'action'      => self::AJAX_ACTION,
			'nonce'       => wp_create_nonce( self::AJAX_ACTION ),
			'working'     => self::is_installed() ? __( 'Activating…', 'social-icons-widget-by-wpzoom' ) : __( 'Installing and activating…', 'social-icons-widget-by-wpzoom' ),
			'done'        => __( 'Done. Opening AI Connect…', 'social-icons-widget-by-wpzoom' ),
			'failed'      => __( 'That did not work:', 'social-icons-widget-by-wpzoom' ),
			'fallback'    => __( 'Install it from WordPress instead', 'social-icons-widget-by-wpzoom' ),
			'fallbackUrl' => self::core_install_url(),
		);
		$js = 'window.wpzoomConnectAI=' . wp_json_encode( $config ) . ';'
			. '(function(){var b=document.querySelector(".js-wpzc-ai-install"),s=document.querySelector(".js-wpzc-ai-status");if(!b||!s){return;}'
			. 'b.addEventListener("click",function(e){e.preventDefault();if(b.getAttribute("aria-disabled")==="true"){return;}var c=window.wpzoomConnectAI,label=b.textContent;'
			. 'b.setAttribute("aria-disabled","true");b.classList.add("is-busy");s.className="wpzc-ai__status js-wpzc-ai-status";s.textContent=c.working;'
			. 'var d=new FormData();d.append("action",c.action);d.append("_ajax_nonce",c.nonce);'
			. 'fetch(c.ajaxUrl,{method:"POST",credentials:"same-origin",body:d}).then(function(r){return r.json();}).then(function(r){'
			. 'if(r&&r.success&&r.data&&r.data.redirect){s.textContent=c.done;window.location.href=r.data.redirect;return;}throw new Error(r&&r.data&&r.data.message?r.data.message:"");'
			. '}).catch(function(err){b.removeAttribute("aria-disabled");b.classList.remove("is-busy");b.textContent=label;s.className="wpzc-ai__status is-error js-wpzc-ai-status";'
			. 's.textContent=c.failed+" "+(err&&err.message?err.message:"")+" ";var a=document.createElement("a");a.href=c.fallbackUrl;a.textContent=c.fallback;s.appendChild(a);});});})();';
		wp_print_inline_script_tag( $js );
	}

	public function render_page() {
		if ( ! self::available() ) {
			return;
		}
		$prompts = array(
			__( 'Change my site’s colors to match my logo', 'social-icons-widget-by-wpzoom' ),
			__( 'Add an About page with a team section and a contact form', 'social-icons-widget-by-wpzoom' ),
			__( 'Write a blog post about our spring opening hours', 'social-icons-widget-by-wpzoom' ),
			__( 'Run a checkup of my site and fix what you can', 'social-icons-widget-by-wpzoom' ),
		);
		?>
		<div class="wrap wpzc">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Manage with AI', 'social-icons-widget-by-wpzoom' ); ?></h1>
			<div class="wpzc-layout">
				<?php
				if ( class_exists( 'WPZOOM_Connect_Overview' ) ) {
					WPZOOM_Connect_Overview::render_sidebar( 'ai-connect' );
				}
				?>
				<div class="wpzc-main">
					<section class="wpzc-card wpzc-ai">
						<span class="wpzc-ai__mark"><?php echo self::sparkles( 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
						<h2><?php esc_html_e( 'Manage your site with AI', 'social-icons-widget-by-wpzoom' ); ?></h2>
						<p class="wpzc-ai__lead"><?php esc_html_e( 'Connect Claude, ChatGPT or another AI assistant to this site with our free WPZOOM AI Connect plugin. Then just tell it what you need, in plain words. It asks before it changes anything.', 'social-icons-widget-by-wpzoom' ); ?></p>
						<p class="wpzc-ai__label"><?php esc_html_e( 'For example:', 'social-icons-widget-by-wpzoom' ); ?></p>
						<ul class="wpzc-ai__prompts">
							<?php foreach ( $prompts as $prompt ) : ?>
								<li>“<?php echo esc_html( $prompt ); ?>”</li>
							<?php endforeach; ?>
						</ul>
						<p class="wpzc-actions">
							<a class="wpzc-button js-wpzc-ai-install" href="<?php echo esc_url( self::is_installed() ? wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( self::PLUGIN_FILE ) ), 'activate-plugin_' . self::PLUGIN_FILE ) : self::core_install_url() ); ?>"><?php echo esc_html( self::button_label() ); ?></a>
							<a class="wpzc-button is-soft" href="https://www.wpzoom.com/ai-connect/" target="_blank" rel="noopener"><?php esc_html_e( 'Learn more', 'social-icons-widget-by-wpzoom' ); ?></a>
						</p>
						<p class="wpzc-ai__status js-wpzc-ai-status" role="status" aria-live="polite"></p>
						<p class="wpzc-ai__note"><?php esc_html_e( 'Free, from wordpress.org. Works with your own Claude or ChatGPT account. You can disconnect or remove it any time.', 'social-icons-widget-by-wpzoom' ); ?></p>
					</section>
				</div>
			</div>
		</div>
		<?php
		self::print_script();
	}

	/** The card in the Overview's side column. Prints nothing when there is nothing to offer. */
	public static function render_card() {
		if ( ! self::available() ) {
			return;
		}
		?>
		<section class="wpzc-card wpzc-promo wpzc-ai-card">
			<span class="wpzc-ai__mark"><?php echo self::sparkles( 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			<h2><?php esc_html_e( 'Manage your site with AI', 'social-icons-widget-by-wpzoom' ); ?></h2>
			<p><?php esc_html_e( 'Ask Claude or ChatGPT to change colors and layouts, add pages or write posts, right on this site. Free with WPZOOM AI Connect.', 'social-icons-widget-by-wpzoom' ); ?></p>
			<a class="wpzc-button is-block" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Set up in one click', 'social-icons-widget-by-wpzoom' ); ?></a>
		</section>
		<?php
	}

	/** The Overview navigation's item (its array shape), or null when not offered. */
	public static function nav_item( $current ) {
		if ( ! self::available() ) {
			return null;
		}
		return array( 'sparkles', __( 'Manage with AI', 'social-icons-widget-by-wpzoom' ), self::url(), 'ai-connect' === $current, '', '', false );
	}

	/** Sparkles, from the Overview's icon set (same line style as the plugin's other icons). */
	public static function sparkles( $size = 20 ) {
		return class_exists( 'WPZOOM_Connect_Overview' ) ? WPZOOM_Connect_Overview::icon( 'sparkles', $size ) : '';
	}

	/* ----- one-click install ----- */

	/** admin-ajax: install AI Connect from wordpress.org if needed, activate it, and say where to go next. */
	public function ajax_install() {
		check_ajax_referer( self::AJAX_ACTION );
		if ( ! function_exists( 'wp_register_ability' ) ) {
			wp_send_json_error( array( 'message' => __( 'WPZOOM AI Connect needs WordPress 6.9 or newer.', 'social-icons-widget-by-wpzoom' ) ) );
		}
		self::load_plugin_functions();

		if ( ! self::is_installed() ) {
			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to install plugins on this site.', 'social-icons-widget-by-wpzoom' ) ) );
			}
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$api = plugins_api( 'plugin_information', array( 'slug' => self::PLUGIN_SLUG, 'fields' => array( 'sections' => false ) ) );
			if ( is_wp_error( $api ) ) {
				wp_send_json_error( array( 'message' => $api->get_error_message() ) );
			}
			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			if ( is_wp_error( $skin->result ) ) {
				wp_send_json_error( array( 'message' => $skin->result->get_error_message() ) );
			}
			if ( $skin->get_errors()->has_errors() ) {
				wp_send_json_error( array( 'message' => $skin->get_error_messages() ) );
			}
			if ( ! $result ) {
				// Typically: WordPress needs FTP details to write files, which only its own screen can ask for.
				wp_send_json_error( array( 'message' => __( 'WordPress could not write the plugin files here.', 'social-icons-widget-by-wpzoom' ) ) );
			}
			wp_clean_plugins_cache();
		}

		if ( ! current_user_can( 'activate_plugin', self::PLUGIN_FILE ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to activate plugins on this site.', 'social-icons-widget-by-wpzoom' ) ) );
		}
		if ( ! is_plugin_active( self::PLUGIN_FILE ) ) {
			$activated = activate_plugin( self::PLUGIN_FILE );
			if ( is_wp_error( $activated ) ) {
				wp_send_json_error( array( 'message' => $activated->get_error_message() ) );
			}
		}
		// AI Connect opens its welcome screen on the next page after activation; we go there ourselves, with the source tag.
		delete_transient( 'wpzoom_ai_connect_activated' );
		wp_send_json_success( array( 'redirect' => self::ai_connect_url() ) );
	}

	/* ----- notice ----- */

	/**
	 * The Dashboard / Plugins notice, through the WPZOOM Notice Center: from a week after this version first ran until
	 * two months after, and never while the "What's new" notice is up.
	 */
	public function register_notice( $notices ) {
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		$since = (int) get_option( self::SINCE, 0 );
		if ( ! $since ) {
			add_option( self::SINCE, time(), '', false );
			return $notices;
		}
		$age = time() - $since;
		if ( $age < 7 * DAY_IN_SECONDS || $age > 60 * DAY_IN_SECONDS || ! self::available() ) {
			return $notices;
		}
		if ( class_exists( 'WPZOOM_Connect_Whats_New' ) && WPZOOM_Connect_Whats_New::running() ) {
			return $notices;
		}
		$notices[] = array(
			'id'               => self::NOTICE_ID,
			'heading'          => __( 'Manage your site with AI', 'social-icons-widget-by-wpzoom' ),
			'content'          => '<p>' . esc_html__( 'Ask Claude or ChatGPT to change colors and layouts, add pages or write posts, right on this site. Set it up in one click with our free WPZOOM AI Connect plugin.', 'social-icons-widget-by-wpzoom' ) . '</p>',
			'icon'             => array(
				'type'             => 'svg',
				'svg'              => self::sparkles( 22 ),
				'color'            => '#ffffff',
				'background_color' => '#3496fe',
			),
			'primary_button'   => array(
				'label'   => __( 'Set up in one click', 'social-icons-widget-by-wpzoom' ),
				'url'     => self::url(),
				'new_tab' => false,
			),
			'secondary_button' => array(
				'label'   => __( 'Learn more', 'social-icons-widget-by-wpzoom' ),
				'url'     => 'https://www.wpzoom.com/ai-connect/',
				'new_tab' => true,
			),
			'capability'       => 'activate_plugins',
			'screens'          => array( 'dashboard', 'plugins' ),
			'source'           => 'WPZOOM Connect',
			'priority'         => 20,
		);
		return $notices;
	}
}

WPZOOM_Connect_AI_Connect::get_instance();
