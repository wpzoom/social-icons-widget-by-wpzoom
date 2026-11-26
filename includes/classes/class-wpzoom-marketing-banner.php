<?php
/**
 * Handle the plugin marketing stuff.
 *
 * @since   3.4.0
 * @package WPZOOM_Social_Icons
 */
if ( ! class_exists( 'WPZOOM_BF_Banner' ) ) {
	class WPZOOM_BF_Banner {
		const BTN_UPGRADE_NOW_LINK = 'https://www.wpzoom.com/black-friday/?utm_source=wpadmin&utm_medium=bf-social-banner-btn&utm_campaign=bf-social';
		const BF_START_DATE = '2026-11-25 00:00:00';
		const BF_END_DATE = '2026-12-02 23:59:59';

		public static function init() {

			global $pagenow;

			// add notices only on specific pages
			if ( ( is_admin() && $pagenow === 'index.php' ) || $pagenow === 'plugins.php' ) {
				add_action( 'admin_notices', [ __CLASS__, 'show_black_friday_banner' ] );
			}

			add_action( 'wp_ajax_wpz_dismiss_bf_banner', [
				__CLASS__,
				'dismiss_black_friday_banner'
			] );
		}

		/**
		 * Sale start and end as Unix timestamps. The dates are read in the site's timezone.
		 *
		 * @return array{start:int,end:int}
		 */
		private static function get_period() {
			$dates = apply_filters(
				'wpzoom_social_icons_bf_dates',
				[
					'start' => self::BF_START_DATE,
					'end'   => self::BF_END_DATE,
				]
			);

			try {
				$start = ( new DateTimeImmutable( $dates['start'], wp_timezone() ) )->getTimestamp();
				$end   = ( new DateTimeImmutable( $dates['end'], wp_timezone() ) )->getTimestamp();
			} catch ( Exception $e ) {
				return [ 'start' => 0, 'end' => 0 ];
			}

			return [ 'start' => $start, 'end' => $end ];
		}

		/**
		 * Display the Black Friday banner if the conditions are met.
		 */
		public static function show_black_friday_banner() {
			// Inspiro (free) shows its own Black Friday banner: never stack ours on top of it.
			// Checked here, not at load time, because the theme is loaded after plugins, and by
			// function name because the theme folder is not always called "inspiro".
			if ( function_exists( 'inspiro_show_black_friday_banner' ) ) {
				return;
			}

			if ( self::is_black_friday_period() && ! self::has_dismissed_banner() ) {
				self::social_widget_display_black_friday_banner();
			}
		}

		/**
		 * Check if the Black Friday period is ongoing.
		 *
		 * @param int|null $now Unix timestamp to check, defaults to now.
		 * @return bool
		 */
		private static function is_black_friday_period( $now = null ) {
			$period = self::get_period();
			$now    = null === $now ? time() : (int) $now;

			return $period['start'] > 0 && $now >= $period['start'] && $now <= $period['end'];
		}

		/**
		 * User meta key for the dismissal. It carries the sale's year, so closing
		 * last year's banner does not hide this year's.
		 *
		 * @return string
		 */
		private static function dismiss_meta_key() {
			return 'wpzoom_wpz_dismiss_black_friday_banner_' . wp_date( 'Y', self::get_period()['start'] );
		}

		/**
		 * Check if the user has dismissed the Black Friday banner.
		 *
		 * @return bool
		 */
		private static function has_dismissed_banner() {
			return (bool) get_user_meta( get_current_user_id(), self::dismiss_meta_key(), true );
		}

		/**
		 * Handle the AJAX request to dismiss the Banner.
		 */
		public static function dismiss_black_friday_banner() {
			check_ajax_referer( 'wpz_dismiss_bf_banner', 'security' );
			update_user_meta( get_current_user_id(), self::dismiss_meta_key(), true );
			wp_send_json_success();
		}

		/**
		 * Render Banner Layout
		 *
		 * @return mixed
		 */
		private static function social_widget_display_black_friday_banner() { ?>
			<div class="wpzoom-banner-container-wrapper">
				<div id="wpzoom-banner-container" class="wpzoom-banner-container notice is-dismissible">
					<a href="https://www.wpzoom.com/black-friday/?utm_source=wpadmin&utm_medium=social-banner-image&utm_campaign=bf-social"><img src="<?php echo untrailingslashit( WPZOOM_SOCIAL_ICONS_PLUGIN_URL ) . '/assets/images/bf.png'; ?>" class="bf-wpzoom-banner-image" alt="WPZOOM Black Friday Deals" ></a>
					<div class="wpz-banner-text-container">
						<h2><?php
							/* translators: %s: price of the yearly All Access plan, e.g. $99 */
							printf( esc_html__( "WPZOOM's Black Friday Sale: All Access for %s", 'social-icons-widget-by-wpzoom' ), '$99' );
						?></h2>
						<span class="banner-text"><?php esc_html_e( 'Every premium theme and plugin in one plan, or 40% off any single theme or plugin. Prices apply to new licenses, first year.', 'social-icons-widget-by-wpzoom' ); ?></span>
						<div class="wpz-banner-promo-btns">
							<div class="banner-btn"><a href="<?php echo esc_url( self::BTN_UPGRADE_NOW_LINK ); ?>" target="_blank"><?php
								/* translators: %s: price, e.g. $99 */
								printf( esc_html__( 'All Access %s', 'social-icons-widget-by-wpzoom' ), '$99' );
							?></a></div>
							<div class="banner-btn"><a href="<?php echo esc_url( self::BTN_UPGRADE_NOW_LINK ); ?>" target="_blank"><?php
								/* translators: %s: price, e.g. $49 */
								printf( esc_html__( 'Inspiro Premium %s', 'social-icons-widget-by-wpzoom' ), '$49' );
							?></a></div>
							<div class="banner-btn"><?php esc_html_e( '40% off everything else', 'social-icons-widget-by-wpzoom' ); ?></div>
							<div class="banner-btn"><a href="https://www.wpzoom.com/themes/?utm_source=wpadmin&utm_medium=bf-social-banner-themes&utm_campaign=bf-social" target="_blank"><?php esc_html_e( '30 Premium Themes', 'social-icons-widget-by-wpzoom' ); ?></a></div>
							<div class="banner-btn"><a href="https://www.wpzoom.com/plugins/?utm_source=wpadmin&utm_medium=bf-social-banner-plugins&utm_campaign=bf-social" target="_blank"><?php esc_html_e( 'Premium Plugins', 'social-icons-widget-by-wpzoom' ); ?></a></div>
						</div>
					</div>
					<div class="wpz-upgrade-banner">
						<div class="banner-clock">
							<span class="hurry-up"><?php esc_html_e( 'Hurry Up! Offer ends soon!', 'social-icons-widget-by-wpzoom' ); ?></span>
							<div class="clock-digits">
								<span><i id="wpz-bf-days"></i><?php echo esc_html_x( 'd', 'days abbreviation in the sale countdown', 'social-icons-widget-by-wpzoom' ); ?></span>
								<span><i id="wpz-bf-hours"></i><?php echo esc_html_x( 'h', 'hours abbreviation in the sale countdown', 'social-icons-widget-by-wpzoom' ); ?></span>
								<span><i id="wpz-bf-minutes"></i><?php echo esc_html_x( 'm', 'minutes abbreviation in the sale countdown', 'social-icons-widget-by-wpzoom' ); ?></span>
								<span><i id="wpz-bf-seconds"></i><?php echo esc_html_x( 's', 'seconds abbreviation in the sale countdown', 'social-icons-widget-by-wpzoom' ); ?></span>
							</div>
						</div>
						<a href="<?php echo esc_url( self::BTN_UPGRADE_NOW_LINK ); ?>" target="_blank" class="btn-upgrade-now"><?php esc_html_e( 'View All Deals', 'social-icons-widget-by-wpzoom' ); ?> &rarr;</a>
					</div>
				</div>
			</div>
			<style>
				.wpzoom-banner-container-wrapper {
					margin: 10px 20px 10px 2px;
				}

				.wpzoom-banner-container {
					display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    color: #fff;
                    /* drow squares with CSS */
                    background-color: #242628; /* Base background color for the squares */
                    background-image:
                        linear-gradient(90deg, rgba(255, 255, 255, .1)  1px, transparent 1px),
                        linear-gradient(180deg, rgba(255, 255, 255, .1) 1px, transparent 1px);
                    background-size: 20px 20px; /* Size of the squares, including borders */
                    border-radius: 14px;
                    margin-bottom: 20px;
                    padding: 15px 30px 10px;
				}

				/*	rewrite WP core rule */
				.wpzoom-banner-container.notice {
					border: unset;
					padding: 0 12px 0 0;
				}

				.wpzoom-banner-container.notice.is-dismissible {
					padding-right: 14px;
					margin: 5px 0 15px;
					border-radius: 3px;
				}

				/* close (X) button: WP core colors it dark gray, invisible on the dark banner */
				.wpzoom-banner-container .notice-dismiss:before {
					color: rgba(255, 255, 255, .75);
				}

				.wpzoom-banner-container .notice-dismiss:hover:before,
				.wpzoom-banner-container .notice-dismiss:active:before,
				.wpzoom-banner-container .notice-dismiss:focus:before {
					color: #fff;
				}

				/* start banner content */
				.bf-wpzoom-banner-image {
                    margin: 10px 30px;
                    max-width: 150px;
				}

				/* text container */
				.wpz-banner-text-container h2 {
					color: #fff;
					font-size: 30px;
					line-height: 1.2;
					margin: 10px 0;
				}

				.wpz-banner-text-container .banner-text {
					font-size: 14px;
					font-weight: 500;
					margin-bottom: 10px;
					display: inline-block;
				}

				.wpz-banner-promo-btns .banner-btn {
                    padding: 4px 16px;
                    border: 1px solid #22BB66;
                    background: #343434;
                    border-radius: 30px;
                    display: inline-block;
                    margin: 0 5px 8px 0;
                    font-size: 12px;
                    font-weight: 600;
                }

                .wpz-banner-promo-btns .banner-btn a {
                    color: #fff;
                    text-decoration: none;
                }

				/* CTA btn */
				.wpz-upgrade-banner .banner-clock {
					display: flex;
					flex-direction: column;
				}

				.wpz-upgrade-banner .hurry-up {
					font-size: 14px;
					font-weight: 500;
				}

				.wpz-upgrade-banner .clock-digits {
					display: flex;
					font-size: 14px;
					font-weight: 300;
					margin-bottom: 10px;
				}

				.wpz-upgrade-banner .clock-digits span {
					margin-right: 8px;
				}

				.wpz-upgrade-banner .clock-digits i {
					font-style: normal !important;
					margin-right: 2px;
					font-weight: 600;
					font-size: 20px;
				}

				.wpz-upgrade-banner a.btn-upgrade-now {
					color: #fff;
					font-size: 18px;
					line-height: 20px;
					padding: 15px 29px;
					font-weight: 600;
					background: #22BB66;
					text-decoration: none;
					text-transform: uppercase;
					display: inline-block;
					z-index: 999;
					position: relative;
					border-radius: 5px;
					box-shadow: rgba(0, 0, 0, .1) 0 1px 3px 0, rgba(0, 0, 0, .06) 0 1px 2px 0;
					white-space: nowrap;
				}

                .wpz-upgrade-banner a.btn-upgrade-now:hover {
                    background: #29cf73;
                    box-shadow: rgba(0, 0, 0, .1) 0 4px 6px -1px, rgba(0, 0, 0, .06) 0 2px 4px -1px;
                }

				/* Responsive */
				@media screen and (max-width: 550px) {
					.wpzoom-banner-container {
						flex-direction: column;
					}

					.wpz-banner-text-container h2 .orange-text {
						display: block;
					}

					.wpz-upgrade-banner a.btn-upgrade-now {
						margin-bottom: 20px;
					}
				}

				@media screen and (max-width: 700px) {
					.bf-wpzoom-banner-image {
						display: none;
					}
				}

				@media screen and (max-width: 782px) {
					.wpzoom-banner-container.notice {
						line-height: unset;
					}
				}

				@media screen and (max-width: 1023px) {
					.wpz-banner-promo-btns,
					.wpz-upgrade-banner .banner-clock {
						display: none;
					}
				}

				@media screen and (max-width: 1200px) {
					.wpzoom-banner-container-wrapper {
						margin-right: 10px;
					}

					.wpzoom-banner-container.notice.is-dismissible {
						padding-right: 0;
					}

					.bf-wpzoom-banner-image {
						margin-right: 0;
					}

					.wpz-banner-text-container {
						margin: 14px 14px 15px 14px;
					}

					.wpz-banner-text-container .orange-text {
						line-height: 30px;
					}
				}

				@media screen and (min-width: 1201px) {
					.wpz-upgrade-banner {
						margin-left: auto;
						margin-right: 20px;
					}

					.wpz-upgrade-banner .hurry-up {
						margin-bottom: 5px;
					}
				}

				@media screen and (min-width: 1201px) and (max-width: 1300px) {
					.wpz-banner-text-container .banner-text {
						max-width: 520px;
					}
				}

				@media screen and (max-width: 1230px) {
					.wpz-banner-text-container h2 {
						font-size: 28px;
					}

					.wpz-upgrade-banner a.btn-upgrade-now {
						font-size: 16px;
						padding: 13px 25px;
						margin-right: 18px;
					}
				}
			</style>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery(document).on('click', '#wpzoom-banner-container button.notice-dismiss', function (e) {
						jQuery.ajax({
							url: ajaxurl,
							type: 'GET',
							data: {
								action: 'wpz_dismiss_bf_banner',
								security: '<?php echo esc_js( wp_create_nonce( 'wpz_dismiss_bf_banner' ) ); ?>',
							},
						});
					});
				});

				// Set the date we're counting down to
				(function () {
					// Constants
					// Unix time in ms from PHP, in the site's timezone (parsing a date string fails in Safari).
					const COUNTDOWN_END_DATE = <?php echo (int) self::get_period()['end'] * 1000; ?>;

					// Element references
					const daysContainer = document.getElementById("wpz-bf-days");
					const hoursContainer = document.getElementById("wpz-bf-hours");
					const minutesContainer = document.getElementById("wpz-bf-minutes");
					const secondsContainer = document.getElementById("wpz-bf-seconds");

					// Function to calculate the time difference
					function calculateTimeDifference(targetDate) {
						const now = new Date().getTime();
						const distance = targetDate - now;

						if (distance > 0) {
							return {
								days: Math.floor(distance / (1000 * 60 * 60 * 24)),
								hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
								minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
								seconds: Math.floor((distance % (1000 * 60)) / 1000)
							};
						} else {
							return {days: 0, hours: 0, minutes: 0, seconds: 0};
						}
					}

					// Function to update the HTML elements with the calculated time
					function updateCountdownDisplay(time) {
						daysContainer.innerText = time.days;
						hoursContainer.innerText = time.hours;
						minutesContainer.innerText = time.minutes;
						secondsContainer.innerText = time.seconds;
					}

					// Render the countdown initially
					updateCountdownDisplay(calculateTimeDifference(COUNTDOWN_END_DATE));

					// Update the countdown every 1 second
					const intervalId = setInterval(function () {
						const timeDifference = calculateTimeDifference(COUNTDOWN_END_DATE);
						updateCountdownDisplay(timeDifference);

						// Clear interval if the countdown is over
						if (timeDifference.days === 0 && timeDifference.hours === 0 &&
							timeDifference.minutes === 0 && timeDifference.seconds === 0) {
							clearInterval(intervalId);
						}
					}, 1000);
				})();
			</script>
		<?php }
	}
}
WPZOOM_BF_Banner::init();
