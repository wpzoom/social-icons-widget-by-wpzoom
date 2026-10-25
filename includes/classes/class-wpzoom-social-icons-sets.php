<?php
/**
 * Icon sets: which sets the block and widget icon pickers offer.
 *
 * Icons are drawn as SVG, so a page includes only the icons it shows: the
 * switches no longer change what pages load, only what the pickers list. An
 * icon already in use keeps showing whichever sets are on. (On a site that
 * turns SVG off with the wpzoom_social_icons_use_svg filter, the switches also
 * decide which icon fonts load, as they did before.)
 *
 *   WPZOOM Icons   always on: the main set, and URL detection looks there first.
 *   Font Awesome   one switch for both versions: 5 (brands, solid, regular) in
 *                  the block, 4 in the classic widget, whose picker still draws
 *                  with that font.
 *   Academicons    academic profiles, found in no other set.
 *   Older sets     Dashicons and Genericons: their social icons are in WPZOOM
 *                  Icons and their general ones in Font Awesome. Off on new
 *                  sites; kept for sites that already use them.
 *
 * The settings keep their historical names ("disable-css-loading-for-…",
 * where TRUE means the set is ON). Free and PRO store them in the same option.
 * Shared with the PRO plugin through bin/sync-from-free.sh.
 *
 * @package WPZOOM_Social_Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Social_Icons_Sets {

	const OPTION = 'wpzoom-social-icons-widget-settings';

	/** Set once the "show every set" picker setting has been folded into the switches. */
	const MIGRATED = 'wpzoom_social_icons_sets_migrated';

	/**
	 * The sets, as shown on the Icon Sets tab.
	 *
	 * key: the setting (null for the always-on main set); mirror: settings that
	 * follow it; kits: icon kits it covers; older: shown under "Older icon sets".
	 *
	 * @return array
	 */
	public static function sets() {
		return array(
			'wpzoom'       => array(
				'key'         => null,
				'title'       => __( 'WPZOOM Icons', 'social-icons-widget-by-wpzoom' ),
				'badge'       => __( 'Main set', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Brand icons for social networks and apps, in their brand colors. Pasting a link picks its icon from this set first.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'socicon' ),
				'samples'     => array( 'socicon facebook', 'socicon x', 'socicon instagram', 'socicon youtube', 'socicon linkedin', 'socicon tiktok' ),
				'older'       => false,
			),
			'font-awesome' => array(
				'key'         => 'disable-css-loading-for-font-awesome-5',
				'mirror'      => array( 'disable-css-loading-for-font-awesome-3' ),
				'title'       => __( 'Font Awesome', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Brand icons plus general ones such as phone, email, location and website. The block offers Font Awesome 5 brands, solid and regular icons; the classic widget offers Font Awesome 4.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'fab', 'far', 'fas' ),
				'samples'     => array( 'fab facebook', 'fab x-twitter', 'fab instagram', 'fas phone', 'fas envelope', 'fas map-marker-alt' ),
				'older'       => false,
			),
			'academicons'  => array(
				'key'         => 'disable-css-loading-for-academicons',
				'title'       => __( 'Academicons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Icons for academic profiles: Google Scholar, ORCID, ResearchGate, arXiv and more.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'academicons' ),
				'samples'     => array( 'academicons google-scholar', 'academicons orcid', 'academicons researchgate', 'academicons arxiv', 'academicons academia', 'academicons pubmed' ),
				'older'       => false,
			),
			'dashicons'    => array(
				'key'         => 'disable-css-loading-for-dashicons',
				'title'       => __( 'Dashicons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'The WordPress admin icon font. Its social icons are also in WPZOOM Icons.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'dashicons' ),
				'samples'     => array( 'dashicons facebook', 'dashicons twitter', 'dashicons instagram', 'dashicons email', 'dashicons wordpress', 'dashicons rss' ),
				'older'       => true,
			),
			'genericons'   => array(
				'key'         => 'disable-css-loading-for-genericons',
				'title'       => __( 'Genericons', 'social-icons-widget-by-wpzoom' ),
				'description' => __( 'Simple general icons. Font Awesome has the same ones, such as mail, phone and RSS.', 'social-icons-widget-by-wpzoom' ),
				'kits'        => array( 'genericon' ),
				'samples'     => array( 'genericon facebook', 'genericon twitter', 'genericon instagram', 'genericon mail', 'genericon github', 'genericon youtube' ),
				'older'       => true,
			),
		);
	}

	/**
	 * Whether a setting is on. Free leaves extra sets off on new sites; PRO, whose
	 * pickers always listed every set, treats a setting it has never saved as on.
	 *
	 * @param string $key        Setting.
	 * @param bool   $default_on Value when the setting has never been saved.
	 * @return bool
	 */
	private static function is_on( $key, $default_on ) {
		$settings = get_option( self::OPTION, array() );
		return is_array( $settings ) && array_key_exists( $key, $settings ) ? (bool) $settings[ $key ] : $default_on;
	}

	/**
	 * Kit => whether the pickers offer it.
	 *
	 * @param bool $default_on See is_on().
	 * @return array
	 */
	public static function enabled_kits( $default_on ) {
		// Font Awesome is one switch; a site that had only one version on keeps both.
		$fa = self::is_on( 'disable-css-loading-for-font-awesome-5', $default_on ) || self::is_on( 'disable-css-loading-for-font-awesome-3', $default_on );

		return array(
			'socicon'     => true,
			'fab'         => $fa,
			'far'         => $fa,
			'fas'         => $fa,
			'fa'          => $fa,
			'academicons' => self::is_on( 'disable-css-loading-for-academicons', $default_on ),
			'dashicons'   => self::is_on( 'disable-css-loading-for-dashicons', $default_on ),
			'genericon'   => self::is_on( 'disable-css-loading-for-genericons', $default_on ),
		);
	}

	/**
	 * Copy the posted switches into the settings being saved: Font Awesome
	 * covers both versions, and WPZOOM Icons can't be switched off.
	 *
	 * @param array $input     Posted values.
	 * @param array $new_input Settings being saved.
	 * @return array
	 */
	public static function sanitize( $input, $new_input ) {
		foreach ( self::sets() as $set ) {
			if ( empty( $set['key'] ) || ! isset( $input[ $set['key'] ] ) ) {
				continue;
			}
			$on                         = wp_validate_boolean( $input[ $set['key'] ] );
			$new_input[ $set['key'] ] = $on;
			foreach ( isset( $set['mirror'] ) ? $set['mirror'] : array() as $mirror ) {
				$new_input[ $mirror ] = $on;
			}
		}
		$new_input['disable-css-loading-for-socicons'] = true;
		$new_input['categories-sync']                  = true;
		return $new_input;
	}

	/**
	 * Once: the old "Show only enabled sets in the icon picker" setting is gone,
	 * since the switches now only decide what the pickers show. A site that had
	 * it off saw every set; turn every set on there so its pickers don't change.
	 */
	public static function migrate() {
		if ( get_option( self::MIGRATED ) ) {
			return;
		}
		$settings = get_option( self::OPTION );
		if ( is_array( $settings ) && array_key_exists( 'categories-sync', $settings ) && ! $settings['categories-sync'] ) {
			foreach ( self::sets() as $set ) {
				if ( ! empty( $set['key'] ) ) {
					$settings[ $set['key'] ] = true;
					foreach ( isset( $set['mirror'] ) ? $set['mirror'] : array() as $mirror ) {
						$settings[ $mirror ] = true;
					}
				}
			}
			$settings['categories-sync'] = true;
			update_option( self::OPTION, $settings );
		}
		update_option( self::MIGRATED, 1 );
	}

	/**
	 * The Icon Sets tab.
	 *
	 * @param bool $default_on See is_on().
	 */
	public static function render( $default_on ) {
		$icons  = self::icon_counts();
		$sets   = self::sets();
		$main   = array_filter( $sets, function ( $set ) {
			return ! $set['older'];
		} );
		$older  = array_diff_key( $sets, $main );
		$on     = array_filter( $older, function ( $set ) use ( $default_on ) {
			return self::is_on( $set['key'], $default_on );
		} );
		$fonts  = class_exists( 'WPZOOM_Social_Icons_SVG' ) && ! WPZOOM_Social_Icons_SVG::use_svg();
		?>
		<div class="wpzoom-settings-card">
			<div class="wpzoom-settings-card__header">
				<span class="wpzoom-settings-card__icon dashicons dashicons-share" aria-hidden="true"></span>
				<div class="wpzoom-settings-card__text">
					<h2><?php esc_html_e( 'Icon Sets', 'social-icons-widget-by-wpzoom' ); ?></h2>
					<p>
						<?php
						if ( $fonts ) {
							esc_html_e( 'Choose the icon sets the icon picker offers. Each set also loads its icon font on your pages, so turn on only the sets you use.', 'social-icons-widget-by-wpzoom' );
						} else {
							esc_html_e( 'Choose which icon sets the icon picker offers. Your pages include only the icons they show, so this doesn’t affect speed. Icons you already use keep showing either way.', 'social-icons-widget-by-wpzoom' );
						}
						?>
					</p>
				</div>
				<button type="button" class="button wpzoom-icon-library-open" aria-haspopup="dialog"><?php esc_html_e( 'Browse icons', 'social-icons-widget-by-wpzoom' ); ?></button>
			</div>

			<?php
			foreach ( $main as $set ) {
				self::render_row( $set, $icons, $default_on );
			}
			?>

			<details class="wpzoom-settings-older"<?php echo $on ? ' open' : ''; ?>>
				<summary>
					<span class="wpzoom-settings-older__title"><?php esc_html_e( 'Older icon sets', 'social-icons-widget-by-wpzoom' ); ?></span>
					<span class="wpzoom-settings-older__meta">
						<?php echo esc_html( implode( ', ', wp_list_pluck( $older, 'title' ) ) ); ?>
						&middot;
						<?php
						echo esc_html(
							$on
								/* translators: %d: number of older icon sets switched on */
								? sprintf( _n( '%d on', '%d on', count( $on ), 'social-icons-widget-by-wpzoom' ), count( $on ) )
								: __( 'off', 'social-icons-widget-by-wpzoom' )
						);
						?>
					</span>
				</summary>
				<p class="wpzoom-settings-older__note"><?php esc_html_e( 'Kept for sites that already use them. Their icons keep showing on your pages either way; switching a set off only removes it from the icon picker.', 'social-icons-widget-by-wpzoom' ); ?></p>
				<?php
				foreach ( $older as $set ) {
					self::render_row( $set, $icons, $default_on );
				}
				?>
			</details>
		</div>
		<?php
		self::render_library( $default_on );
	}

	/**
	 * The icon library: every icon of every set, in a dialog like the one of
	 * the WordPress Icon block. Icons are fetched one set at a time when shown.
	 *
	 * @param bool $default_on See is_on().
	 */
	private static function render_library( $default_on ) {
		$counts = self::icon_counts();
		$groups = array();
		foreach ( self::library_groups() as $kit => $title ) {
			$set              = self::set_of_kit( $kit );
			$groups[ $kit ] = array(
				'title' => $title,
				'count' => isset( $counts[ $kit ] ) ? $counts[ $kit ] : 0,
				'on'    => ! $set || null === $set['key'] || self::is_on( $set['key'], $default_on ),
			);
		}

		?>
		<dialog class="wpzoom-icon-library" aria-labelledby="wpzoom-icon-library-title">
			<div class="wpzoom-icon-library__head">
				<h2 id="wpzoom-icon-library-title"><?php esc_html_e( 'Icon library', 'social-icons-widget-by-wpzoom' ); ?></h2>
				<button type="button" class="wpzoom-icon-library__close" aria-label="<?php esc_attr_e( 'Close', 'social-icons-widget-by-wpzoom' ); ?>">
					<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z" fill="currentColor"/></svg>
				</button>
			</div>
			<div class="wpzoom-icon-library__body">
				<div class="wpzoom-icon-library__side">
					<label class="wpzoom-icon-library__search">
						<span class="screen-reader-text"><?php esc_html_e( 'Search icons', 'social-icons-widget-by-wpzoom' ); ?></span>
						<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z" fill="currentColor"/></svg>
						<input type="search" placeholder="<?php esc_attr_e( 'Search', 'social-icons-widget-by-wpzoom' ); ?>" autocomplete="off">
					</label>
					<nav class="wpzoom-icon-library__sets" aria-label="<?php esc_attr_e( 'Icon sets', 'social-icons-widget-by-wpzoom' ); ?>">
						<button type="button" data-kit="" aria-pressed="false">
							<span class="wpzoom-icon-library__name"><?php esc_html_e( 'All', 'social-icons-widget-by-wpzoom' ); ?></span>
							<span class="wpzoom-icon-library__count"><?php echo esc_html( number_format_i18n( array_sum( wp_list_pluck( $groups, 'count' ) ) ) ); ?></span>
						</button>
						<?php foreach ( $groups as $kit => $group ) : ?>
							<button type="button" data-kit="<?php echo esc_attr( $kit ); ?>" aria-pressed="false">
								<span class="wpzoom-icon-library__name"><?php echo esc_html( $group['title'] ); ?></span>
								<?php if ( ! $group['on'] ) : ?>
									<span class="wpzoom-icon-library__off" title="<?php esc_attr_e( 'Not offered in the icon picker. Switch the set on to use it.', 'social-icons-widget-by-wpzoom' ); ?>"><?php esc_html_e( 'Off', 'social-icons-widget-by-wpzoom' ); ?></span>
								<?php endif; ?>
								<span class="wpzoom-icon-library__count"><?php echo esc_html( number_format_i18n( $group['count'] ) ); ?></span>
							</button>
						<?php endforeach; ?>
					</nav>
					<div class="wpzoom-icon-library__help">
						<h3><?php esc_html_e( 'How to add an icon', 'social-icons-widget-by-wpzoom' ); ?></h3>
						<ol>
							<li><?php esc_html_e( 'In the block editor, add the Social Icons block, or Icon by WPZOOM for a single icon.', 'social-icons-widget-by-wpzoom' ); ?></li>
							<li><?php esc_html_e( 'Click an icon in the block, or + to add one. Paste its link, or leave the link empty for an icon that isn’t a link.', 'social-icons-widget-by-wpzoom' ); ?></li>
							<li><?php esc_html_e( 'To pick another icon, search its name from this library in the Select Icon window.', 'social-icons-widget-by-wpzoom' ); ?></li>
						</ol>
						<p><?php esc_html_e( 'The classic widget and the Elementor widget use the same icons.', 'social-icons-widget-by-wpzoom' ); ?></p>
					</div>
				</div>
				<div class="wpzoom-icon-library__main" tabindex="-1">
					<p class="wpzoom-icon-library__status" role="status"></p>
					<div class="wpzoom-icon-library__grid"></div>
				</div>
			</div>
		</dialog>
		<?php
	}

	/**
	 * The library's script and styles, on the Settings screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue_library( $hook ) {
		if ( 'wpzoom-shortcode_page_wpzoom-social-icons-widget' !== $hook ) {
			return;
		}
		$url = WPZOOM_SOCIAL_ICONS_PLUGIN_URL;
		$ver = WPZOOM_SOCIAL_ICONS_PLUGIN_VERSION;
		wp_enqueue_style( 'wpzoom-icon-library', $url . 'assets/css/icon-library.css', array(), $ver );
		wp_enqueue_script( 'wpzoom-icon-library', $url . 'assets/js/icon-library.js', array(), $ver, true );
		wp_localize_script(
			'wpzoom-icon-library',
			'wpzoomIconLibrary',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpzoom_icon_library' ),
				'i18n'    => array(
					'loading' => __( 'Loading icons…', 'social-icons-widget-by-wpzoom' ),
					'failed'  => __( 'The icons couldn’t be loaded. Please try again.', 'social-icons-widget-by-wpzoom' ),
					/* translators: %s: search terms */
					'none'    => __( 'No icons match “%s”.', 'social-icons-widget-by-wpzoom' ),
				),
			)
		);
	}

	/**
	 * The library's groups: kit => title, in the order shown. Font Awesome is
	 * split by style, as the block's picker splits it.
	 *
	 * @return array
	 */
	private static function library_groups() {
		return array(
			'socicon'     => __( 'WPZOOM Icons', 'social-icons-widget-by-wpzoom' ),
			'fab'         => __( 'Font Awesome Brands', 'social-icons-widget-by-wpzoom' ),
			'fas'         => __( 'Font Awesome Solid', 'social-icons-widget-by-wpzoom' ),
			'far'         => __( 'Font Awesome Regular', 'social-icons-widget-by-wpzoom' ),
			'academicons' => __( 'Academicons', 'social-icons-widget-by-wpzoom' ),
			'dashicons'   => __( 'Dashicons', 'social-icons-widget-by-wpzoom' ),
			'genericon'   => __( 'Genericons', 'social-icons-widget-by-wpzoom' ),
		);
	}

	/**
	 * The set a kit belongs to.
	 *
	 * @param string $kit Kit.
	 * @return array|null
	 */
	private static function set_of_kit( $kit ) {
		foreach ( self::sets() as $set ) {
			if ( in_array( $kit, $set['kits'], true ) ) {
				return $set;
			}
		}
		return null;
	}

	/**
	 * AJAX: one kit's icons for the library, sorted by name, as
	 * array( array( n => name, s => svg, c => brand color ), … ).
	 */
	public static function ajax_library() {
		check_ajax_referer( 'wpzoom_icon_library', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$kit    = isset( $_GET['kit'] ) ? sanitize_key( wp_unslash( $_GET['kit'] ) ) : '';
		$data   = self::icon_data();
		$groups = self::library_groups();
		if ( ! isset( $groups[ $kit ], $data[ $kit ] ) || ! class_exists( 'WPZOOM_Social_Icons_Library' ) ) {
			wp_send_json_error( null, 400 );
		}

		$icons = array();
		foreach ( (array) $data[ $kit ] as $item ) {
			$svg = isset( $item['icon'] ) ? WPZOOM_Social_Icons_Library::inline_svg( $kit, $item['icon'] ) : '';
			if ( '' === $svg ) {
				continue;
			}
			$icons[ $item['icon'] ] = array(
				'n' => $item['icon'],
				's' => $svg,
				'c' => isset( $item['color'] ) ? $item['color'] : '',
			);
		}
		ksort( $icons, SORT_NATURAL );
		wp_send_json_success( array_values( $icons ) );
	}

	/**
	 * Icons per kit, from the icon data.
	 *
	 * @return array
	 */
	private static function icon_counts() {
		$counts = array();
		foreach ( self::icon_data() as $kit => $items ) {
			$counts[ $kit ] = is_array( $items ) ? count( $items ) : 0;
		}
		return $counts;
	}

	/**
	 * The plugin's icon data. In PRO, loading it also loads the icon library.
	 *
	 * @return array
	 */
	private static function icon_data() {
		static $data = null;
		if ( null === $data ) {
			$data = include WPZOOM_SOCIAL_ICONS_PLUGIN_PATH . 'includes/icons-data.php';
			$data = is_array( $data ) ? $data : array();
		}
		return $data;
	}

	/**
	 * One set: samples, name, description, icon count and its switch.
	 *
	 * @param array $set        Set.
	 * @param array $counts     Icons per kit.
	 * @param bool  $default_on See is_on().
	 */
	private static function render_row( $set, $counts, $default_on ) {
		$id    = 'wpzoom-setting-set-' . sanitize_title( $set['title'] );
		$count = 0;
		foreach ( $set['kits'] as $kit ) {
			$count += isset( $counts[ $kit ] ) ? $counts[ $kit ] : 0;
		}
		$brand = array();
		$data  = self::icon_data();
		if ( in_array( 'socicon', $set['kits'], true ) && ! empty( $data['socicon'] ) ) {
			$brand = wp_list_pluck( $data['socicon'], 'color', 'icon' );
		}
		?>
		<div class="wpzoom-settings-row wpzoom-settings-kit">
			<div class="wpzoom-settings-kit__samples" aria-hidden="true">
				<?php foreach ( $set['samples'] as $sample ) : ?>
					<?php
					list( $kit, $name ) = explode( ' ', $sample );
					$svg                = WPZOOM_Social_Icons_Library::inline_svg( $kit, $name );
					$color              = isset( $brand[ $name ] ) ? $brand[ $name ] : '';
					?>
					<i<?php echo $color ? ' style="color:' . esc_attr( $color ) . '"' : ''; ?>><?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from the plugin's own icon library. ?></i>
				<?php endforeach; ?>
			</div>
			<div class="wpzoom-settings-row__main">
				<div class="wpzoom-settings-row__title" id="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $set['title'] ); ?>
					<?php if ( ! empty( $set['badge'] ) ) : ?>
						<span class="wpzoom-settings-badge"><?php echo esc_html( $set['badge'] ); ?></span>
					<?php endif; ?>
				</div>
				<p class="wpzoom-settings-row__description">
					<?php echo esc_html( $set['description'] ); ?>
					<span class="wpzoom-settings-row__meta">
						<?php
						/* translators: %s: number of icons */
						echo esc_html( sprintf( _n( '%s icon', '%s icons', $count, 'social-icons-widget-by-wpzoom' ), number_format_i18n( $count ) ) );
						?>
					</span>
				</p>
			</div>
			<div class="wpzoom-settings-row__control">
				<?php if ( empty( $set['key'] ) ) : ?>
					<span class="wpzoom-settings-always"><?php esc_html_e( 'Always on', 'social-icons-widget-by-wpzoom' ); ?></span>
				<?php else : ?>
					<?php
					$on = self::is_on( $set['key'], $default_on );
					foreach ( isset( $set['mirror'] ) ? $set['mirror'] : array() as $mirror ) {
						$on = $on || self::is_on( $mirror, $default_on );
					}
					$name = self::OPTION . '[' . $set['key'] . ']';
					// The hidden field is what's sent when the switch is off; a checked
					// switch comes later in the form and overrides it.
					?>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0" />
					<label class="wpzoom-settings-switch">
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" aria-labelledby="<?php echo esc_attr( $id ); ?>" <?php checked( $on ); ?> />
						<span class="wpzoom-settings-switch__track" aria-hidden="true"></span>
					</label>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'WPZOOM_Social_Icons_Sets', 'migrate' ) );
add_action( 'wp_ajax_wpzoom_icon_library', array( 'WPZOOM_Social_Icons_Sets', 'ajax_library' ) );
add_action( 'admin_enqueue_scripts', array( 'WPZOOM_Social_Icons_Sets', 'enqueue_library' ) );
