<?php
/**
 * Upgrade to PRO Upsell Page
 *
 * Displays a dedicated page showcasing Pro features
 * with feature cards and a Free vs PRO comparison table.
 *
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPZOOM_Social_Icons_Upsell
 */
class WPZOOM_Social_Icons_Upsell {

	/**
	 * The single class instance.
	 *
	 * @var $instance
	 */
	private static $instance = null;

	/**
	 * Main Instance
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
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Add menu item
	 */
	public function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=wpzoom-shortcode',
			__( 'Upgrade to PRO', 'social-icons-widget-by-wpzoom' ),
			__( 'Upgrade to PRO', 'social-icons-widget-by-wpzoom' ) . ' ★',
			'manage_options',
			'wpzoom-social-icons-upsell',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue styles
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_styles( $hook ) {
		if ( 'wpzoom-shortcode_page_wpzoom-social-icons-upsell' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpzoom-admin-upsell',
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/admin-upsell.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);
	}

	/**
	 * Get the upgrade URL with UTM parameters
	 *
	 * @return string
	 */
	private function get_upgrade_url() {
		return 'https://www.wpzoom.com/plugins/social-share/?utm_source=wpadmin&utm_medium=plugin&utm_campaign=social-icons-free&utm_content=upsell-page';
	}

	/**
	 * Render check SVG icon
	 */
	private function render_check_icon() {
		return '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 0C4.03759 0 0 4.03759 0 9C0 13.9624 4.03759 18 9 18C13.9624 18 18 13.9624 18 9C18 4.03759 13.9624 0 9 0ZM14.0301 6.63158L8.2782 12.3383C7.93985 12.6767 7.3985 12.6992 7.03759 12.3609L3.99248 9.58647C3.63158 9.24812 3.60902 8.68421 3.92481 8.32331C4.26316 7.96241 4.82707 7.93985 5.18797 8.2782L7.6015 10.4887L12.7444 5.34586C13.1053 4.98496 13.6692 4.98496 14.0301 5.34586C14.391 5.70677 14.391 6.27068 14.0301 6.63158Z" fill="#3496FF"/></svg>';
	}

	/**
	 * Render cross SVG icon
	 */
	private function render_cross_icon() {
		return '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.364 2.63603C13.6641 0.936172 11.4039 0 9 0C6.59605 0 4.33589 0.936172 2.63603 2.63603C0.936172 4.33589 0 6.59605 0 9C0 11.4041 0.936172 13.6641 2.63603 15.364C4.33589 17.0638 6.59605 18 9 18C11.4039 18 13.6641 17.0638 15.364 15.364C17.0638 13.6641 18 11.4041 18 9C18 6.59605 17.0638 4.33589 15.364 2.63603ZM12.8927 11.6496C13.2359 11.993 13.2359 12.5494 12.8927 12.8926C12.7211 13.0643 12.4961 13.1501 12.2712 13.1501C12.0462 13.1501 11.8213 13.0643 11.6496 12.8926L9 10.243L6.35037 12.8927C6.17871 13.0643 5.95377 13.1501 5.72882 13.1501C5.50388 13.1501 5.27893 13.0643 5.10727 12.8927C4.76408 12.5494 4.76408 11.993 5.10727 11.6498L7.75703 9L5.10727 6.35037C4.76408 6.00705 4.76408 5.45059 5.10727 5.10741C5.45059 4.76408 6.00705 4.76408 6.35023 5.10741L9 7.75703L11.6496 5.10741C11.993 4.76422 12.5494 4.76408 12.8926 5.10741C13.2359 5.45059 13.2359 6.00705 12.8926 6.35037L10.243 9L12.8927 11.6496Z" fill="#81909C"/></svg>';
	}

	/**
	 * Render the upsell page
	 */
	public function render_page() {
		$upgrade_url = $this->get_upgrade_url();
		$check       = $this->render_check_icon();
		$cross       = $this->render_cross_icon();
		?>
		<div class="wrap wpzoom-upsell-wrap">
			<div class="wpzoom-upsell-page">

				<!-- Header -->
				<div class="wpzoom-upsell-header">
					<div class="wpzoom-upsell-logo">
						<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/icons/social-share-buttons.svg" alt="<?php esc_attr_e( 'Social Icons Widget PRO', 'social-icons-widget-by-wpzoom' ); ?>" />
					</div>
					<h2><?php esc_html_e( 'Upgrade to Social Icons PRO', 'social-icons-widget-by-wpzoom' ); ?></h2>
					<p><?php esc_html_e( 'Take your social icons and sharing buttons to the next level with powerful Pro features. Track shares, display counts, add AI sharing, and much more.', 'social-icons-widget-by-wpzoom' ); ?></p>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="wpzoom-upsell-btn" target="_blank">
						<?php esc_html_e( 'Upgrade to PRO &rarr;', 'social-icons-widget-by-wpzoom' ); ?>
					</a>
				</div>

				<!-- Features Grid -->
				<div class="wpzoom-upsell-content">
					<h3><?php esc_html_e( 'Unlock powerful PRO features', 'social-icons-widget-by-wpzoom' ); ?></h3>
					<div class="wpzoom-upsell-grid">

						<!-- Share Analytics -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image">
								<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/social-landing/feature-analytics.png" alt="<?php esc_attr_e( 'Share Analytics', 'social-icons-widget-by-wpzoom' ); ?>" />
							</div>
							<span class="wpzoom-upsell-badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-chart-bar"></span>
								<?php esc_html_e( 'Share Analytics', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Track how visitors share your content with a detailed analytics dashboard. See shares by platform, top shared posts, and engagement trends over time.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'No sharing analytics', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

						<!-- Like Button -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image wpzoom-upsell-like-preview">
								<button type="button" class="wpzoom-upsell-like-btn-preview" id="wpzoom-upsell-like-btn">
									<svg class="wpzoom-like-icon-outlined" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" width="20" height="20"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
									<svg class="wpzoom-like-icon-filled" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
									<span class="wpzoom-upsell-like-label"><?php esc_html_e( 'Like', 'social-icons-widget-by-wpzoom' ); ?></span>
									<span class="wpzoom-upsell-like-count" id="wpzoom-upsell-like-count">0</span>
								</button>
								<span class="wpzoom-upsell-like-hint"><?php esc_html_e( 'Click to try it!', 'social-icons-widget-by-wpzoom' ); ?></span>
							</div>
							<span class="wpzoom-upsell-badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-heart"></span>
								<?php esc_html_e( 'Like Button', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Add a like button to posts and pages, letting visitors engage with your content. Includes built-in tracking and display of like counts.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'No like functionality', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

						<!-- AI Share Buttons -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image">
								<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/social-landing/feature-ai.png" alt="<?php esc_attr_e( 'AI Share Buttons', 'social-icons-widget-by-wpzoom' ); ?>" />
							</div>
							<span class="wpzoom-upsell-badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-format-chat"></span>
								<?php esc_html_e( 'AI Share Buttons', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Let visitors share your content to AI platforms like ChatGPT, Claude, and Perplexity. Stay ahead with the latest sharing options.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'Standard sharing platforms only', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

						<!-- Share Counts -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image">
								<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/social-landing/feature-sharing.png" alt="<?php esc_attr_e( 'Share Counts', 'social-icons-widget-by-wpzoom' ); ?>" />
							</div>
							<span class="wpzoom-upsell-badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-admin-links"></span>
								<?php esc_html_e( 'Share Counts', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Display total and individual share counts on sharing buttons, tracked by the plugin. Optionally fetch real Facebook share counts with your own SharedCount API key.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'No share count display', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

						<!-- Custom SVG Uploads -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image">
								<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/social-landing/feature-5.png" alt="<?php esc_attr_e( 'Custom SVG Uploads', 'social-icons-widget-by-wpzoom' ); ?>" />
							</div>
							<span class="wpzoom-upsell-badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-upload"></span>
								<?php esc_html_e( 'Custom SVG Uploads', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Upload your own custom SVG icons to create unique social icon sets. Perfect for brands with custom icons or niche platforms.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'Built-in icons only', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

						<!-- Nav Menu Integration -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image">
								<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/social-landing/feature-6.png" alt="<?php esc_attr_e( 'Nav Menu Integration', 'social-icons-widget-by-wpzoom' ); ?>" />
							</div>
							<span class="wpzoom-upsell-badge"><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-menu"></span>
								<?php esc_html_e( 'Nav Menu Integration', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Add social icons directly to your WordPress navigation menus. Icons can be accompanied by text labels for a polished menu design.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'Widget and block only', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

						<!-- Floating Buttons -->
						<div class="wpzoom-upsell-section">
							<div class="wpzoom-upsell-section-image">
								<img src="https://www.wpzoom.com/wp-content/themes/wpzoom/images/social-landing/floating-buttons.png" alt="<?php esc_attr_e( 'Floating Buttons', 'social-icons-widget-by-wpzoom' ); ?>" />
							</div>
							<span class="wpzoom-upsell-badge wpzoom-upsell-badge-new"><?php esc_html_e( 'NEW', 'social-icons-widget-by-wpzoom' ); ?></span>
							<h4>
								<span class="dashicons dashicons-align-left"></span>
								<?php esc_html_e( 'Floating Buttons', 'social-icons-widget-by-wpzoom' ); ?>
							</h4>
							<p class="about"><?php esc_html_e( 'Display social sharing icons as a fixed floating bar on the side of your website. Visitors can share from anywhere on the page. Configure position, layout, and visibility for specific post types.', 'social-icons-widget-by-wpzoom' ); ?></p>
							<p class="section_footer">
								<strong><?php esc_html_e( 'Without PRO:', 'social-icons-widget-by-wpzoom' ); ?></strong>
								<?php esc_html_e( 'No floating share bar', 'social-icons-widget-by-wpzoom' ); ?>
							</p>
						</div>

					</div>

					<!-- Free vs PRO Table -->
					<h3><?php esc_html_e( 'Free vs PRO', 'social-icons-widget-by-wpzoom' ); ?></h3>
					<ul class="wpzoom-features-table">
						<li class="t-head">
							<div>&nbsp;</div>
							<div class="c"><?php esc_html_e( 'FREE', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><span><?php esc_html_e( 'PRO', 'social-icons-widget-by-wpzoom' ); ?></span></div>
						</li>

						<!-- Social Icons -->
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Social Icons Block', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( '400+ Icons from 5 Icon Sets', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Icon Sets & Shortcodes', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Custom SVG Icon Uploads', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Nav Menu Integration', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>

						<!-- Sharing Buttons -->
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Social Sharing Buttons Block', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( '12+ Sharing Platforms', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'AI Share Buttons (ChatGPT, Claude, Perplexity)', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Like Button', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Share Counts', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Floating Buttons Bar', 'social-icons-widget-by-wpzoom' ); ?> <span class="wpzoom-new-badge"><?php esc_html_e( 'New', 'social-icons-widget-by-wpzoom' ); ?></span></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>

						<!-- Analytics & More -->
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Share Analytics Dashboard', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Dashboard Stats Widget', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>
						<li class="t-row">
							<div class="h-wrap"><?php esc_html_e( 'Priority Support', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="c"><?php echo $cross; // phpcs:ignore ?></div>
							<div class="c"><?php echo $check; // phpcs:ignore ?></div>
						</li>

						<li class="t-footer">
							<div class="h-wrap"></div>
							<div class="c"></div>
							<div class="c">
								<a href="<?php echo esc_url( $upgrade_url ); ?>" class="wpzoom-upsell-btn" target="_blank">
									<?php esc_html_e( 'Upgrade to PRO', 'social-icons-widget-by-wpzoom' ); ?>
								</a>
							</div>
						</li>
					</ul>
				</div>

			</div>
		</div>
		<script>
		(function() {
			var btn = document.getElementById( 'wpzoom-upsell-like-btn' );
			var countEl = document.getElementById( 'wpzoom-upsell-like-count' );
			if ( ! btn || ! countEl ) return;

			var isLiked = false;
			var count = 0;
			var colors = ['#3496ff', '#ffcc00', '#ff3366', '#22bb66', '#ff6633', '#9b59b6'];

			btn.addEventListener( 'click', function() {
				isLiked = ! isLiked;
				count = isLiked ? count + 1 : Math.max( 0, count - 1 );
				countEl.textContent = count;

				if ( isLiked ) {
					btn.classList.add( 'is-liked', 'animate' );
					btn.querySelector( '.wpzoom-upsell-like-label' ).textContent = '<?php echo esc_js( __( 'Liked', 'social-icons-widget-by-wpzoom' ) ); ?>';
					// Confetti
					for ( var i = 0; i < 12; i++ ) {
						var p = document.createElement( 'span' );
						p.className = 'wpzoom-upsell-confetti';
						var angle = ( Math.PI * 2 * i ) / 12 + ( Math.random() * 0.5 - 0.25 );
						var dist = 30 + Math.random() * 40;
						p.style.setProperty( '--dx', Math.cos( angle ) * dist + 'px' );
						p.style.setProperty( '--dy', Math.sin( angle ) * dist + 'px' );
						p.style.background = colors[ i % colors.length ];
						btn.appendChild( p );
						( function( el ) {
							setTimeout( function() {
								if ( el.parentNode ) el.parentNode.removeChild( el );
							}, 800 );
						} )( p );
					}
				} else {
					btn.classList.remove( 'is-liked' );
					btn.classList.add( 'animate' );
					btn.querySelector( '.wpzoom-upsell-like-label' ).textContent = '<?php echo esc_js( __( 'Like', 'social-icons-widget-by-wpzoom' ) ); ?>';
				}

				setTimeout( function() {
					btn.classList.remove( 'animate' );
				}, 400 );
			} );
		})();
		</script>
		<?php
	}
}

// Initialize the class.
WPZOOM_Social_Icons_Upsell::get_instance();
