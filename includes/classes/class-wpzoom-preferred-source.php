<?php
/**
 * "Add to Preferred Sources" button for Google Search.
 *
 * Readers who pick a site as a preferred source see more of it in Top Stories
 * and in AI Mode / AI Overviews. The button is a plain link to Google's source
 * preferences page for this site's domain: no Google script is loaded, so it
 * costs nothing until someone clicks it.
 *
 * Used by the Preferred Source block, the [wpzoom_preferred_source] shortcode
 * and the Sharing Buttons auto-display.
 *
 * @link https://developers.google.com/search/docs/appearance/preferred-sources
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPZOOM_Preferred_Source
 */
class WPZOOM_Preferred_Source {

	/**
	 * Stylesheet handle, shared by the block, the shortcode and auto-display.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'wpzoom-preferred-source';

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const SHORTCODE = 'wpzoom_preferred_source';

	/**
	 * Google's source preferences page.
	 *
	 * @var string
	 */
	const GOOGLE_URL = 'https://www.google.com/preferences/source';

	/**
	 * Option holding the global settings (auto-display and site defaults).
	 *
	 * @var string
	 */
	const OPTION = 'wpzoom_preferred_source';

	/**
	 * Settings tab anchor on the plugin's Settings page.
	 *
	 * @var string
	 */
	const SETTINGS_TAB = 'preferred-source';

	/**
	 * Hook everything up.
	 */
	public static function init() {
		// Before the block registers on `init` (priority 10), which references this handle.
		add_action( 'init', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		// After the sharing buttons (priority 10), so the button sits outside them.
		add_filter( 'the_content', array( __CLASS__, 'add_to_content' ), 11 );
	}

	/**
	 * Default global settings.
	 *
	 * @return array
	 */
	public static function settings_defaults() {
		return array(
			'enabled'    => false,
			'post_types' => array( 'post' ),
			'position'   => 'bottom',
			'theme'      => 'light',
			'size'       => 'medium',
			'align'      => 'left',
			'label'      => '',
			'domain'     => '',
		);
	}

	/**
	 * Global settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::settings_defaults() );
	}

	/**
	 * URL of the settings tab.
	 *
	 * @return string
	 */
	public static function settings_url() {
		return admin_url( 'edit.php?post_type=wpzoom-shortcode&page=' . WPZOOM_Social_Icons_Settings::$menu_slug . '#' . self::SETTINGS_TAB );
	}

	/**
	 * Register the option with the Settings page's form. The page posts every
	 * tab in one form, under the General group.
	 */
	public static function register_settings() {
		register_setting(
			'wpzoom-social-icons-widget-settings-group-general',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::settings_defaults(),
			)
		);
	}

	/**
	 * Sanitize the global settings.
	 *
	 * @param mixed $input Submitted values.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_settings();
		}

		$defaults = self::settings_defaults();
		$choices  = array(
			'position' => array( 'top', 'bottom', 'both' ),
			'theme'    => array( 'light', 'dark', 'auto' ),
			'size'     => array( 'small', 'medium', 'large' ),
			'align'    => array( 'left', 'center', 'right' ),
		);

		$output = array(
			'enabled'    => ! empty( $input['enabled'] ),
			'post_types' => array(),
			'label'      => isset( $input['label'] ) ? sanitize_text_field( $input['label'] ) : '',
			'domain'     => isset( $input['domain'] ) ? self::normalize_domain( $input['domain'] ) : '',
		);

		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$output['post_types'] = array_values( array_filter( array_map( 'sanitize_key', $input['post_types'] ), 'post_type_exists' ) );
		}

		foreach ( $choices as $key => $allowed ) {
			$output[ $key ] = isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ? $input[ $key ] : $defaults[ $key ];
		}

		return $output;
	}

	/**
	 * Post types the button can be shown on.
	 *
	 * @return WP_Post_Type[]
	 */
	public static function available_post_types() {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		// Media and this plugin's own icon sets have no article content.
		unset( $types['attachment'], $types['wpzoom-shortcode'] );
		return $types;
	}

	/**
	 * Add the button before and/or after the content of the selected post types.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function add_to_content( $content ) {
		if ( is_admin() || is_feed() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$settings = self::get_settings();
		if ( ! $settings['enabled'] || empty( $settings['post_types'] ) || ! is_singular( $settings['post_types'] ) ) {
			return $content;
		}

		// Skip posts that already place the button themselves.
		$post = get_post();
		if ( $post && ( has_block( 'wpzoom-blocks/preferred-source', $post ) || has_shortcode( $post->post_content, self::SHORTCODE ) ) ) {
			return $content;
		}

		$button = self::render(
			array(
				'label'     => $settings['label'],
				'theme'     => $settings['theme'],
				'size'      => $settings['size'],
				'textAlign' => $settings['align'],
			)
		);
		if ( '' === $button ) {
			return $content;
		}

		if ( 'top' === $settings['position'] || 'both' === $settings['position'] ) {
			$content = '<div class="wpzoom-preferred-source-top">' . $button . '</div>' . $content;
		}
		if ( 'bottom' === $settings['position'] || 'both' === $settings['position'] ) {
			$content .= '<div class="wpzoom-preferred-source-bottom">' . $button . '</div>';
		}

		return $content;
	}

	/**
	 * The settings tab on the plugin's Settings page.
	 */
	public static function render_settings_tab() {
		$settings = self::get_settings();
		$name     = self::OPTION;
		$domain   = self::site_domain();
		$home     = self::normalize_domain( home_url() );

		$segments = array(
			'position' => array(
				'label'   => __( 'Position', 'social-icons-widget-by-wpzoom' ),
				'options' => array(
					'top'    => __( 'Above content', 'social-icons-widget-by-wpzoom' ),
					'bottom' => __( 'Below content', 'social-icons-widget-by-wpzoom' ),
					'both'   => __( 'Both', 'social-icons-widget-by-wpzoom' ),
				),
			),
			'theme'    => array(
				'label'   => __( 'Theme', 'social-icons-widget-by-wpzoom' ),
				'options' => array(
					'light' => __( 'Light', 'social-icons-widget-by-wpzoom' ),
					'dark'  => __( 'Dark', 'social-icons-widget-by-wpzoom' ),
					'auto'  => __( 'Auto', 'social-icons-widget-by-wpzoom' ),
				),
				'help'    => __( 'Auto follows each visitor’s light or dark mode setting.', 'social-icons-widget-by-wpzoom' ),
			),
			'size'     => array(
				'label'   => __( 'Size', 'social-icons-widget-by-wpzoom' ),
				'options' => array(
					'small'  => __( 'Small', 'social-icons-widget-by-wpzoom' ),
					'medium' => __( 'Medium', 'social-icons-widget-by-wpzoom' ),
					'large'  => __( 'Large', 'social-icons-widget-by-wpzoom' ),
				),
			),
			'align'    => array(
				'label'   => __( 'Alignment', 'social-icons-widget-by-wpzoom' ),
				'options' => array(
					'left'   => __( 'Left', 'social-icons-widget-by-wpzoom' ),
					'center' => __( 'Center', 'social-icons-widget-by-wpzoom' ),
					'right'  => __( 'Right', 'social-icons-widget-by-wpzoom' ),
				),
			),
		);
		?>
		<div class="wpzoom-settings-card wpzoom-ps-card">
			<div class="wpzoom-settings-card__header">
				<span class="wpzoom-settings-card__icon wpzoom-settings-card__icon--logo"><?php echo self::logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
				<div class="wpzoom-settings-card__text">
					<h2><?php esc_html_e( 'Google Preferred Sources', 'social-icons-widget-by-wpzoom' ); ?></h2>
					<p><?php esc_html_e( 'Readers who add your site as a preferred source on Google see more of your articles in Top Stories and in AI Mode / AI Overviews. The button is a plain link to Google: no Google script is loaded.', 'social-icons-widget-by-wpzoom' ); ?></p>
				</div>
				<?php if ( '' !== $domain ) : ?>
					<a class="button wpzoom-settings-card__action" href="<?php echo esc_url( self::google_url( $domain ) ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Check your site on Google', 'social-icons-widget-by-wpzoom' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
					</a>
				<?php endif; ?>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show automatically', 'social-icons-widget-by-wpzoom' ); ?></th>
					<td>
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0" />
						<label class="wpzoom-settings-switch">
							<input type="checkbox" id="wpzoom-ps-enabled" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
							<span class="wpzoom-settings-switch__track" aria-hidden="true"></span>
							<?php esc_html_e( 'Add the button to the content of these post types', 'social-icons-widget-by-wpzoom' ); ?>
						</label>
						<fieldset class="wpzoom-ps-chips wpzoom-ps-depends">
							<legend class="screen-reader-text"><?php esc_html_e( 'Post types', 'social-icons-widget-by-wpzoom' ); ?></legend>
							<?php foreach ( self::available_post_types() as $post_type ) : ?>
								<label class="wpzoom-ps-chip">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $settings['post_types'], true ) ); ?> />
									<span><?php echo esc_html( $post_type->label ); ?></span>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description wpzoom-ps-depends">
							<?php esc_html_e( 'Posts that already contain the block or the shortcode are skipped, so the button isn’t shown twice.', 'social-icons-widget-by-wpzoom' ); ?>
						</p>
					</td>
				</tr>

				<?php foreach ( $segments as $key => $segment ) : ?>
					<tr class="wpzoom-ps-depends">
						<th scope="row" id="wpzoom-ps-<?php echo esc_attr( $key ); ?>-label"><?php echo esc_html( $segment['label'] ); ?></th>
						<td>
							<div class="wpzoom-ps-segmented" role="radiogroup" aria-labelledby="wpzoom-ps-<?php echo esc_attr( $key ); ?>-label">
								<?php foreach ( $segment['options'] as $value => $text ) : ?>
									<label>
										<input type="radio" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $settings[ $key ], $value ); ?> />
										<span><?php echo esc_html( $text ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<?php if ( ! empty( $segment['help'] ) ) : ?>
								<p class="description"><?php echo esc_html( $segment['help'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr class="wpzoom-ps-depends">
					<th scope="row"><label for="wpzoom-ps-label"><?php esc_html_e( 'Button text', 'social-icons-widget-by-wpzoom' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="wpzoom-ps-label" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $settings['label'] ); ?>" placeholder="<?php echo esc_attr( self::default_label() ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to use the default text, translated to your site language.', 'social-icons-widget-by-wpzoom' ); ?></p>
					</td>
				</tr>

				<tr class="wpzoom-ps-depends">
					<th scope="row"><?php esc_html_e( 'Preview', 'social-icons-widget-by-wpzoom' ); ?></th>
					<td>
						<div class="wpzoom-ps-preview" data-default-label="<?php echo esc_attr( self::default_label() ); ?>">
							<?php
							echo self::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render().
								array(
									'label'        => $settings['label'],
									'theme'        => $settings['theme'],
									'size'         => $settings['size'],
									'textAlign'    => $settings['align'],
									'openInNewTab' => true,
								)
							);
							?>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="wpzoom-ps-domain"><?php esc_html_e( 'Domain', 'social-icons-widget-by-wpzoom' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="wpzoom-ps-domain" name="<?php echo esc_attr( $name ); ?>[domain]" value="<?php echo esc_attr( $settings['domain'] ); ?>" placeholder="<?php echo esc_attr( $home ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to use your site address. A domain or subdomain only, like news.example.com. Used by the automatic button, the block and the shortcode.', 'social-icons-widget-by-wpzoom' ); ?></p>

						<?php if ( 'local' === self::domain_problem( $domain ) ) : ?>
							<div class="notice notice-warning inline"><p>
								<?php
								printf(
									/* translators: %s: domain */
									esc_html__( '%s is a local or development address, so Google can’t list it. The button will point to your real domain once the site is live there.', 'social-icons-widget-by-wpzoom' ),
									'<code>' . esc_html( $domain ) . '</code>'
								);
								?>
							</p></div>
						<?php elseif ( '' === $settings['domain'] && self::is_subfolder_install() ) : ?>
							<div class="notice notice-warning inline"><p>
								<?php
								printf(
									/* translators: %s: domain */
									esc_html__( 'Your site is in a subfolder. Google lists whole domains and subdomains only, so the button points to %s. If your publication is listed under another domain, enter it above.', 'social-icons-widget-by-wpzoom' ),
									'<code>' . esc_html( $domain ) . '</code>'
								);
								?>
							</p></div>
						<?php endif; ?>

						<p class="description"><?php esc_html_e( 'Google only offers sites it lists. If your site isn’t found with “Check your site on Google”, the button can’t add it yet.', 'social-icons-widget-by-wpzoom' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="wpzoom-ps-footer">
				<?php
				printf(
					/* translators: %s: shortcode */
					esc_html__( 'To place the button anywhere else, use the Google Preferred Source block or the %s shortcode.', 'social-icons-widget-by-wpzoom' ),
					'<code>[' . esc_html( self::SHORTCODE ) . ']</code>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Register the stylesheet; it is enqueued only when a button is rendered.
	 */
	public static function register_assets() {
		wp_register_style(
			self::STYLE_HANDLE,
			WPZOOM_SOCIAL_ICONS_PLUGIN_URL . 'assets/css/wpzoom-preferred-source.css',
			array(),
			WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION
		);
	}

	/**
	 * Default button options.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'label'        => '',
			'theme'        => 'light',
			'size'         => 'medium',
			'textAlign'    => '',
			'domain'       => '',
			'openInNewTab' => true,
		);
	}

	/**
	 * The button text used when no label is set.
	 *
	 * @return string
	 */
	public static function default_label() {
		return __( 'Add to Preferred Sources', 'social-icons-widget-by-wpzoom' );
	}

	/**
	 * Reduce a URL or host to the domain Google expects: no scheme, path, port
	 * or leading "www.". Other subdomains are kept, as Google lists them
	 * separately.
	 *
	 * @param string $value URL or host.
	 * @return string Lowercase domain, or '' when there is none.
	 */
	public static function normalize_domain( $value ) {
		$value = strtolower( trim( (string) $value ) );
		if ( '' === $value ) {
			return '';
		}
		if ( false === strpos( $value, '://' ) ) {
			$value = 'http://' . $value;
		}
		$host = wp_parse_url( $value, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}
		$host = rtrim( $host, '.' );
		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}
		return $host;
	}

	/**
	 * This site's domain.
	 *
	 * @return string
	 */
	public static function site_domain() {
		$settings = self::get_settings();
		$domain   = $settings['domain'] ? $settings['domain'] : self::normalize_domain( home_url() );

		/**
		 * Filters the domain the Preferred Sources button points to by default.
		 *
		 * @param string $domain Domain from the settings, or else from the site address.
		 */
		return (string) apply_filters( 'wpzoom_preferred_source_domain', $domain );
	}

	/**
	 * Google's source preferences URL for a domain.
	 *
	 * @param string $domain Domain.
	 * @return string
	 */
	public static function google_url( $domain ) {
		return add_query_arg( 'q', rawurlencode( $domain ), self::GOOGLE_URL );
	}

	/**
	 * Why Google can't list a domain, if that can be told from the address.
	 *
	 * Google lists domains and subdomains of public sites only. This catches
	 * local and development addresses; whether Google actually lists a public
	 * site can only be seen on Google's page.
	 *
	 * @param string $domain Domain.
	 * @return string '' when the address looks fine, otherwise 'local'.
	 */
	public static function domain_problem( $domain ) {
		if ( '' === $domain || false === strpos( $domain, '.' ) || filter_var( $domain, FILTER_VALIDATE_IP ) ) {
			return 'local';
		}
		$tld = substr( strrchr( $domain, '.' ), 1 );
		if ( in_array( $tld, array( 'local', 'localhost', 'test', 'example', 'invalid', 'internal', 'lan', 'home' ), true ) ) {
			return 'local';
		}
		return '';
	}

	/**
	 * Whether the site lives in a subfolder (example.com/blog), which Google
	 * can't list on its own: the button can only point to the whole domain.
	 *
	 * @return bool
	 */
	public static function is_subfolder_install() {
		$path = wp_parse_url( home_url(), PHP_URL_PATH );
		return is_string( $path ) && '' !== trim( $path, '/' );
	}

	/**
	 * Data for the block editor (merged into `wpzSocialIconsBlock`).
	 *
	 * @return array
	 */
	public static function editor_data() {
		return array(
			'siteDomain'   => self::site_domain(),
			// A domain set in the settings replaces the subfolder guess.
			'isSubfolder'  => self::is_subfolder_install() && '' === self::get_settings()['domain'],
			'homeUrl'      => untrailingslashit( home_url() ),
			'googleUrl'    => self::GOOGLE_URL,
			'defaultLabel' => self::default_label(),
		);
	}

	/**
	 * Sanitize options from block attributes, shortcode attributes or settings.
	 *
	 * @param array $args Raw options.
	 * @return array
	 */
	public static function parse_args( $args ) {
		$args = wp_parse_args( is_array( $args ) ? $args : array(), self::defaults() );

		$args['label']     = trim( wp_strip_all_tags( (string) $args['label'] ) );
		$args['theme']     = in_array( $args['theme'], array( 'light', 'dark', 'auto' ), true ) ? $args['theme'] : 'light';
		$args['size']      = in_array( $args['size'], array( 'small', 'medium', 'large' ), true ) ? $args['size'] : 'medium';
		$args['textAlign'] = in_array( $args['textAlign'], array( 'left', 'center', 'right' ), true ) ? $args['textAlign'] : '';
		$args['domain']    = self::normalize_domain( $args['domain'] );

		$new_tab = $args['openInNewTab'];
		if ( is_string( $new_tab ) ) {
			$new_tab = ! in_array( strtolower( $new_tab ), array( '0', 'false', 'no', 'off', '' ), true );
		}
		$args['openInNewTab'] = (bool) $new_tab;

		return $args;
	}

	/**
	 * Class names for the button wrapper.
	 *
	 * @param array $args Parsed options.
	 * @return string[]
	 */
	public static function wrapper_classes( $args ) {
		$classes = array(
			'wpzoom-preferred-source',
			'is-theme-' . $args['theme'],
			'is-size-' . $args['size'],
		);
		if ( $args['textAlign'] ) {
			$classes[] = 'has-text-align-' . $args['textAlign'];
		}
		return $classes;
	}

	/**
	 * The button markup.
	 *
	 * @param array  $args               Options; see defaults().
	 * @param string $wrapper_attributes Optional wrapper attributes (from
	 *                                   get_block_wrapper_attributes()); built
	 *                                   from the options when empty.
	 * @return string
	 */
	public static function render( $args, $wrapper_attributes = '' ) {
		$args   = self::parse_args( $args );
		$domain = $args['domain'] ? $args['domain'] : self::site_domain();
		if ( '' === $domain ) {
			return '';
		}

		wp_enqueue_style( self::STYLE_HANDLE );

		if ( '' === $wrapper_attributes ) {
			$wrapper_attributes = 'class="' . esc_attr( implode( ' ', self::wrapper_classes( $args ) ) ) . '"';
		}

		$label = $args['label'] ? $args['label'] : self::default_label();
		$link  = $args['openInNewTab'] ? ' target="_blank" rel="noopener"' : '';

		return sprintf(
			'<div %1$s><a class="wpzoom-preferred-source__button" href="%2$s"%3$s>%4$s<span class="wpzoom-preferred-source__label">%5$s</span></a></div>',
			$wrapper_attributes,
			esc_url( self::google_url( $domain ) ),
			$link,
			self::logo_svg(),
			esc_html( $label )
		);
	}

	/**
	 * Render callback for the wpzoom-blocks/preferred-source block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $attributes ) {
		$args = self::parse_args( $attributes );
		return self::render(
			$args,
			get_block_wrapper_attributes( array( 'class' => implode( ' ', self::wrapper_classes( $args ) ) ) )
		);
	}

	/**
	 * [wpzoom_preferred_source] shortcode.
	 *
	 * Attributes: label, theme (light|dark|auto), size (small|medium|large),
	 * align (left|center|right), domain, new_tab (yes|no).
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'label'   => '',
				'theme'   => 'light',
				'size'    => 'medium',
				'align'   => '',
				'domain'  => '',
				'new_tab' => 'yes',
			),
			$atts,
			self::SHORTCODE
		);

		return self::render(
			array(
				'label'        => $atts['label'],
				'theme'        => $atts['theme'],
				'size'         => $atts['size'],
				'textAlign'    => $atts['align'],
				'domain'       => $atts['domain'],
				'openInNewTab' => $atts['new_tab'],
			)
		);
	}

	/**
	 * Google "G" logo.
	 *
	 * @return string
	 */
	public static function logo_svg() {
		return '<svg class="wpzoom-preferred-source__logo" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
			. '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>'
			. '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>'
			. '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>'
			. '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>'
			. '</svg>';
	}
}

WPZOOM_Preferred_Source::init();
