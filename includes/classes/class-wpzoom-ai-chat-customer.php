<?php
/**
 * Customer data connector for AI Chat (Yamidoo).
 *
 * Yamidoo calls POST /wp-json/yamidoo/v1/customer with {email, ts}, signed
 * with the per-site lookup secret it handed us at connect time
 * (X-Yamidoo-Signature: sha256=HMAC_SHA256(secret, "ts.email")). We answer
 * with a small JSON "card" — what this store knows about that email — that
 * the Yamidoo inbox shows next to the conversation, and that the AI uses to
 * answer a logged-in customer's questions about their own orders, licenses
 * and subscriptions.
 *
 * Adapters: Easy Digital Downloads (+ Software Licensing, Recurring) and
 * WooCommerce (+ Subscriptions). Each returns sections; empty ones are dropped.
 * Only ever called with a valid signature, and only for one email at a time.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_AI_Chat_Customer {

	const NAMESPACE  = 'yamidoo/v1';
	const MAX_ITEMS = 15;

	/**
	 * Licenses are one line each and the one a customer asks about is often
	 * not the newest, so they get more room. Live ones are listed first.
	 */
	const MAX_LICENSES = 25;
	const MAX_SKEW_S = 300;

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		// The standalone Yamidoo plugin serves the same route with its own secret;
		// when it is active it owns the endpoint (like it owns the embed).
		if ( class_exists( 'Yamidoo_Customer' ) ) {
			return;
		}
		register_rest_route(
			self::NAMESPACE,
			'/customer',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => array( __CLASS__, 'verify_request' ),
			)
		);
	}

	/** Which stores are present on this site (for the settings screen). */
	public static function detected_stores() {
		$stores = array();
		if ( function_exists( 'edd_get_customer_by' ) ) {
			$stores[] = 'Easy Digital Downloads';
		}
		if ( function_exists( 'wc_get_orders' ) ) {
			$stores[] = 'WooCommerce';
		}
		return $stores;
	}

	// -------------------------------------------------------------------------
	// Auth
	// -------------------------------------------------------------------------

	/** HMAC over "ts.email" with the lookup secret, timestamp within 5 minutes. */
	public static function verify_request( WP_REST_Request $request ) {
		$settings = WPZOOM_AI_Chat::get_settings();
		$secret   = isset( $settings['lookup_secret'] ) ? (string) $settings['lookup_secret'] : '';
		if ( '' === $secret || empty( $settings['share_customer_data'] ) ) {
			return new WP_Error( 'yamidoo_disabled', 'Customer data sharing is off.', array( 'status' => 403 ) );
		}
		$ts  = (int) $request->get_header( 'x-yamidoo-timestamp' );
		$sig = (string) $request->get_header( 'x-yamidoo-signature' );
		$body  = $request->get_json_params();
		$email = isset( $body['email'] ) ? strtolower( trim( (string) $body['email'] ) ) : '';
		if ( ! $ts || '' === $sig || ! is_email( $email ) ) {
			return new WP_Error( 'yamidoo_bad_request', 'Missing signature, timestamp or email.', array( 'status' => 400 ) );
		}
		if ( abs( time() - $ts ) > self::MAX_SKEW_S ) {
			return new WP_Error( 'yamidoo_stale', 'Request timestamp out of range.', array( 'status' => 401 ) );
		}
		$expected = 'sha256=' . hash_hmac( 'sha256', $ts . '.' . $email, $secret );
		if ( ! hash_equals( $expected, $sig ) ) {
			return new WP_Error( 'yamidoo_bad_signature', 'Invalid signature.', array( 'status' => 401 ) );
		}
		return true;
	}

	// -------------------------------------------------------------------------
	// Card
	// -------------------------------------------------------------------------

	public static function handle( WP_REST_Request $request ) {
		$body  = $request->get_json_params();
		$email = strtolower( trim( (string) $body['email'] ) );

		$sections = array();
		$source   = array();
		$url      = '';

		if ( function_exists( 'edd_get_customer_by' ) ) {
			$edd = self::edd_card( $email );
			if ( $edd ) {
				$sections = array_merge( $sections, $edd['sections'] );
				$source[] = 'Easy Digital Downloads';
				$url      = $url ?: $edd['url'];
			}
		}
		if ( function_exists( 'wc_get_orders' ) ) {
			$woo = self::woo_card( $email );
			if ( $woo ) {
				$sections = array_merge( $sections, $woo['sections'] );
				$source[] = 'WooCommerce';
				$url      = $url ?: $woo['url'];
			}
		}

		/**
		 * Filter the customer card before it is sent to Yamidoo. Add sections for
		 * other plugins (memberships, Freemius, CRM…): each is
		 * array( 'title' => …, 'items' => array( array( 'value' => …, 'label' => …, 'badge' => …, 'tone' => …, 'meta' => …, 'url' => … ) ) ).
		 *
		 * @param array  $sections Sections so far.
		 * @param string $email    The customer email being looked up.
		 */
		$sections = apply_filters( 'wpzoom_ai_chat_customer_sections', $sections, $email );

		$card = array( 'sections' => array_values( array_filter( $sections, array( __CLASS__, 'section_has_items' ) ) ) );
		if ( $source ) {
			$card['source'] = implode( ' + ', $source );
		}
		if ( $url ) {
			$card['url'] = $url;
		}
		return rest_ensure_response( $card );
	}

	private static function section_has_items( $s ) {
		return ! empty( $s['items'] );
	}

	private static function item( $value, $args = array() ) {
		$item = array( 'value' => (string) $value );
		foreach ( array( 'label', 'badge', 'tone', 'meta', 'url' ) as $k ) {
			if ( ! empty( $args[ $k ] ) ) {
				$item[ $k ] = (string) $args[ $k ];
			}
		}
		return $item;
	}

	/** Map a store status to a badge tone. */
	private static function tone( $status ) {
		$status = strtolower( (string) $status );
		if ( in_array( $status, array( 'active', 'complete', 'completed', 'publish', 'processing' ), true ) ) {
			return 'success';
		}
		if ( in_array( $status, array( 'pending', 'on-hold', 'trialling', 'trialing', 'expiring', 'inactive' ), true ) ) {
			return 'warning';
		}
		if ( in_array( $status, array( 'expired', 'refunded', 'failed', 'cancelled', 'canceled', 'revoked', 'disabled', 'abandoned' ), true ) ) {
			return 'danger';
		}
		return 'neutral';
	}

	/** Strip tags and decode entities from a store-formatted amount ("&#36;3,775.76" → "$3,775.76"). */
	private static function money( $html ) {
		return html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );
	}

	private static function date( $value ) {
		if ( empty( $value ) ) {
			return '';
		}
		$ts = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
		return $ts ? date_i18n( get_option( 'date_format' ), $ts ) : '';
	}

	// -------------------------------------------------------------------------
	// Easy Digital Downloads
	// -------------------------------------------------------------------------

	private static function edd_card( $email ) {
		$customer = edd_get_customer_by( 'email', $email );
		if ( ! $customer || empty( $customer->id ) ) {
			return null;
		}
		$sections = array();
		$admin    = function_exists( 'edd_get_admin_url' )
			? edd_get_admin_url( array( 'page' => 'edd-customers', 'view' => 'overview', 'id' => (int) $customer->id ) )
			: admin_url( 'edit.php?post_type=download&page=edd-customers&view=overview&id=' . (int) $customer->id );

		// Customer
		$meta = array();
		if ( ! empty( $customer->purchase_count ) ) {
			$meta[] = sprintf( _n( '%d purchase', '%d purchases', (int) $customer->purchase_count, 'social-icons-widget-by-wpzoom' ), (int) $customer->purchase_count );
		}
		if ( function_exists( 'edd_currency_filter' ) && isset( $customer->purchase_value ) ) {
			$meta[] = sprintf( __( 'lifetime %s', 'social-icons-widget-by-wpzoom' ), self::money( edd_currency_filter( edd_format_amount( (float) $customer->purchase_value ) ) ) );
		}
		if ( ! empty( $customer->date_created ) ) {
			$meta[] = sprintf( __( 'since %s', 'social-icons-widget-by-wpzoom' ), self::date( $customer->date_created ) );
		}
		$sections[] = array(
			'title' => __( 'Customer', 'social-icons-widget-by-wpzoom' ),
			'items' => array(
				self::item(
					trim( $customer->name ?: $email ) . ' (#' . (int) $customer->id . ')',
					array( 'meta' => implode( ' · ', $meta ), 'url' => $admin )
				),
			),
		);

		// Licenses (Software Licensing)
		if ( function_exists( 'edd_software_licensing' ) ) {
			$licenses = edd_software_licensing()->licenses_db->get_licenses(
				array( 'customer_id' => (int) $customer->id, 'number' => 200, 'orderby' => 'date_created', 'order' => 'DESC' )
			);
			// Active and inactive first (the ones that still matter), then newest.
			$licenses = (array) $licenses;
			usort(
				$licenses,
				function ( $a, $b ) {
					$rank = array( 'active' => 0, 'inactive' => 1 );
					$ra   = isset( $rank[ $a->status ] ) ? $rank[ $a->status ] : 2;
					$rb   = isset( $rank[ $b->status ] ) ? $rank[ $b->status ] : 2;
					if ( $ra !== $rb ) {
						return $ra - $rb;
					}
					return strcmp( (string) $b->date_created, (string) $a->date_created );
				}
			);
			$total_licenses = count( $licenses );
			$licenses       = array_slice( $licenses, 0, self::MAX_LICENSES );
			$items = array();
			foreach ( (array) $licenses as $lic ) {
				$name = '';
				if ( ! empty( $lic->download_id ) ) {
					$name = get_the_title( (int) $lic->download_id );
					if ( ! empty( $lic->price_id ) && function_exists( 'edd_get_price_option_name' ) ) {
						$opt = edd_get_price_option_name( (int) $lic->download_id, (int) $lic->price_id );
						if ( $opt ) {
							$name .= ' · ' . $opt;
						}
					}
				}
				$exp = '';
				if ( ! empty( $lic->is_lifetime ) ) {
					$exp = __( 'lifetime', 'social-icons-widget-by-wpzoom' );
				} elseif ( ! empty( $lic->expiration ) ) {
					$exp = sprintf( __( 'expires %s', 'social-icons-widget-by-wpzoom' ), self::date( $lic->expiration ) );
				}
				$sites = '';
				if ( isset( $lic->activation_count ) ) {
					$limit = ! empty( $lic->activation_limit ) ? (int) $lic->activation_limit : '∞';
					$sites = sprintf( __( 'sites %1$s/%2$s', 'social-icons-widget-by-wpzoom' ), (int) $lic->activation_count, $limit );
				}
				$items[] = self::item(
					$name ?: __( 'License', 'social-icons-widget-by-wpzoom' ),
					array(
						'badge' => $lic->status,
						'tone'  => self::tone( $lic->status ),
						'meta'  => implode( ' · ', array_filter( array( $exp, $sites, ! empty( $lic->key ) ? $lic->key : '' ) ) ),
						'url'   => function_exists( 'edd_get_admin_url' )
							? edd_get_admin_url( array( 'page' => 'edd-licenses', 'view' => 'overview', 'license' => (int) $lic->ID ) )
							: '',
					)
				);
			}
			$sections[] = array( 'title' => __( 'Licenses', 'social-icons-widget-by-wpzoom' ), 'items' => $items, 'total' => $total_licenses );
		}

		// Orders
		if ( function_exists( 'edd_get_orders' ) ) {
			$orders = edd_get_orders(
				array( 'customer_id' => (int) $customer->id, 'number' => self::MAX_ITEMS, 'orderby' => 'date_created', 'order' => 'DESC', 'type' => 'sale' )
			);
			$total_orders = function_exists( 'edd_count_orders' ) ? (int) edd_count_orders( array( 'customer_id' => (int) $customer->id, 'type' => 'sale' ) ) : count( (array) $orders );
			$items = array();
			foreach ( (array) $orders as $order ) {
				$products = array();
				if ( method_exists( $order, 'get_items' ) ) {
					foreach ( (array) $order->get_items() as $oi ) {
						$products[] = $oi->product_name;
					}
				}
				$amount = function_exists( 'edd_currency_filter' )
					? self::money( edd_currency_filter( edd_format_amount( (float) $order->total ), $order->currency ) )
					: (string) $order->total;
				$items[] = self::item(
					'#' . ( method_exists( $order, 'get_number' ) ? $order->get_number() : $order->id ) . ' · ' . $amount,
					array(
						'badge' => $order->status,
						'tone'  => self::tone( $order->status ),
						'meta'  => implode( ' · ', array_filter( array( implode( ', ', array_unique( $products ) ), self::date( $order->date_created ), $order->gateway ? ucfirst( $order->gateway ) : '' ) ) ),
						'url'   => function_exists( 'edd_get_admin_url' )
							? edd_get_admin_url( array( 'page' => 'edd-payment-history', 'view' => 'view-order-details', 'id' => (int) $order->id ) )
							: '',
					)
				);
			}
			$sections[] = array( 'title' => __( 'Orders', 'social-icons-widget-by-wpzoom' ), 'items' => $items, 'collapsed' => count( $items ) > 3, 'total' => $total_orders );
		}

		// Subscriptions (Recurring)
		if ( class_exists( 'EDD_Recurring_Subscriber' ) ) {
			$subscriber = new EDD_Recurring_Subscriber( (int) $customer->id );
			$subs       = (array) $subscriber->get_subscriptions();
			// Active ones first, then newest; a long-time customer can have dozens.
			usort(
				$subs,
				function ( $a, $b ) {
					$live = array( 'active' => 0, 'trialling' => 0, 'pending' => 1 );
					$ra   = isset( $live[ $a->status ] ) ? $live[ $a->status ] : 2;
					$rb   = isset( $live[ $b->status ] ) ? $live[ $b->status ] : 2;
					if ( $ra !== $rb ) {
						return $ra - $rb;
					}
					return strcmp( (string) $b->created, (string) $a->created );
				}
			);
			$total_subs = count( $subs );
			$subs       = array_slice( $subs, 0, self::MAX_ITEMS );
			$items      = array();
			foreach ( $subs as $sub ) {
				$name = ! empty( $sub->product_id ) ? get_the_title( (int) $sub->product_id ) : __( 'Subscription', 'social-icons-widget-by-wpzoom' );
				$meta = array();
				if ( ! empty( $sub->created ) ) {
					$meta[] = sprintf( __( 'created %s', 'social-icons-widget-by-wpzoom' ), self::date( $sub->created ) );
				}
				if ( ! empty( $sub->expiration ) ) {
					$meta[] = sprintf( __( 'renews/expires %s', 'social-icons-widget-by-wpzoom' ), self::date( $sub->expiration ) );
				}
				if ( isset( $sub->recurring_amount ) && function_exists( 'edd_currency_filter' ) ) {
					$meta[] = self::money( edd_currency_filter( edd_format_amount( (float) $sub->recurring_amount ) ) ) . ( ! empty( $sub->period ) ? ' / ' . $sub->period : '' );
				}
				$items[] = self::item(
					$name . ' (#' . (int) $sub->id . ')',
					array(
						'badge' => $sub->status,
						'tone'  => self::tone( $sub->status ),
						'meta'  => implode( ' · ', $meta ),
						'url'   => admin_url( 'edit.php?post_type=download&page=edd-subscriptions&id=' . (int) $sub->id ),
					)
				);
			}
			$sections[] = array(
				'title'     => __( 'Subscriptions', 'social-icons-widget-by-wpzoom' ),
				'total'     => $total_subs,
				'items'     => $items,
				'collapsed' => count( $items ) > 3,
				'url'       => admin_url( 'edit.php?post_type=download&page=edd-subscriptions&s=' . rawurlencode( $email ) ),
			);
		}

		return array( 'sections' => $sections, 'url' => $admin );
	}

	// -------------------------------------------------------------------------
	// WooCommerce
	// -------------------------------------------------------------------------

	private static function woo_card( $email ) {
		$paged  = wc_get_orders( array( 'customer' => $email, 'limit' => self::MAX_ITEMS, 'orderby' => 'date', 'order' => 'DESC', 'paginate' => true ) );
		$orders = $paged && isset( $paged->orders ) ? (array) $paged->orders : array();
		$total_orders = $paged && isset( $paged->total ) ? (int) $paged->total : count( $orders );
		$user   = get_user_by( 'email', $email );
		if ( empty( $orders ) && ! $user ) {
			return null;
		}
		$sections = array();
		$admin    = $user ? admin_url( 'user-edit.php?user_id=' . (int) $user->ID ) : '';

		$total = 0.0;
		$items = array();
		foreach ( (array) $orders as $order ) {
			$total += (float) $order->get_total();
			$products = array();
			foreach ( $order->get_items() as $it ) {
				$products[] = $it->get_name();
			}
			$items[] = self::item(
				'#' . $order->get_order_number() . ' · ' . self::money( $order->get_formatted_order_total() ),
				array(
					'badge' => $order->get_status(),
					'tone'  => self::tone( $order->get_status() ),
					'meta'  => implode( ' · ', array_filter( array( implode( ', ', array_unique( $products ) ), self::date( $order->get_date_created() ? $order->get_date_created()->getTimestamp() : '' ), $order->get_payment_method_title() ) ) ),
					'url'   => $order->get_edit_order_url(),
				)
			);
		}
		$meta = array();
		if ( $orders ) {
			$meta[] = sprintf( _n( '%d order', '%d orders', $total_orders, 'social-icons-widget-by-wpzoom' ), $total_orders );
			$meta[] = sprintf( __( 'recent total %s', 'social-icons-widget-by-wpzoom' ), self::money( wc_price( $total ) ) );
		}
		if ( $user && ! empty( $user->user_registered ) ) {
			$meta[] = sprintf( __( 'since %s', 'social-icons-widget-by-wpzoom' ), self::date( $user->user_registered ) );
		}
		$sections[] = array(
			'title' => __( 'Customer', 'social-icons-widget-by-wpzoom' ),
			'items' => array( self::item( $user ? $user->display_name : $email, array( 'meta' => implode( ' · ', $meta ), 'url' => $admin ) ) ),
		);
		$sections[] = array( 'title' => __( 'Orders', 'social-icons-widget-by-wpzoom' ), 'items' => $items, 'collapsed' => count( $items ) > 3, 'total' => $total_orders );

		if ( $user && function_exists( 'wcs_get_users_subscriptions' ) ) {
			$items = array();
			$all_subs   = (array) wcs_get_users_subscriptions( $user->ID );
			$total_subs = count( $all_subs );
			foreach ( array_slice( $all_subs, 0, self::MAX_ITEMS ) as $sub ) {
				$names = array();
				foreach ( $sub->get_items() as $it ) {
					$names[] = $it->get_name();
				}
				$next = $sub->get_date( 'next_payment' );
				$items[] = self::item(
					implode( ', ', $names ) . ' (#' . $sub->get_id() . ')',
					array(
						'badge' => $sub->get_status(),
						'tone'  => self::tone( $sub->get_status() ),
						'meta'  => implode( ' · ', array_filter( array( self::money( $sub->get_formatted_order_total() ), $next ? sprintf( __( 'next payment %s', 'social-icons-widget-by-wpzoom' ), self::date( $next ) ) : '' ) ) ),
						'url'   => $sub->get_edit_order_url(),
					)
				);
			}
			$sections[] = array( 'title' => __( 'Subscriptions', 'social-icons-widget-by-wpzoom' ), 'items' => $items, 'total' => $total_subs );
		}

		return array( 'sections' => $sections, 'url' => $admin );
	}
}

WPZOOM_AI_Chat_Customer::init();
