<?php
/**
 * Social Icons Shortcode
 *
 * This class adds all the hooks neceessary to enable the desired shortcode features
 * in the WordPress installation. It uses the class ZOOM_Social_Icons_Widget().
 *
 * @package WPZOOM_Social_Icons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for Social Icons Widget
 */
class WPZOOM_Social_Icons_Free_Shortcode {

	/**
	 * Post type name
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public static $post_type_name = 'Social Icons';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_custom_post_type' ) );
		add_action( 'edit_form_after_title', array( $this, 'add_form_to_post' ) );
		add_action( 'save_post_wpzoom-shortcode', array( $this, 'save_data' ) );
		add_action( 'manage_wpzoom-shortcode_posts_columns', array( $this, 'register_columns' ) );
		add_action( 'manage_wpzoom-shortcode_posts_custom_column', array( $this, 'render_column' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Register custom post type
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function register_custom_post_type() {
		$labels = array(
			'name'               => _x( 'Social Icon Sets', 'post type general name', 'social-icons-widget-by-wpzoom' ),
			'singular_name'      => _x( 'Social Icon Sets', 'post type singular name', 'social-icons-widget-by-wpzoom' ),
			'add_new'            => _x( 'Add New', 'shortcode', 'social-icons-widget-by-wpzoom' ),
			'add_new_item'       => __( 'Add New Shortcode', 'social-icons-widget-by-wpzoom' ),
			'edit_item'          => __( 'Edit Social Icon Shortcode', 'social-icons-widget-by-wpzoom' ),
			'new_item'           => __( 'New Social Icon Shortcodes Memeber', 'social-icons-widget-by-wpzoom' ),
			'all_items'          => __( 'Icon Sets', 'social-icons-widget-by-wpzoom' ),
			'view_item'          => __( 'View Shortcodes', 'social-icons-widget-by-wpzoom' ),
			'search_items'       => __( 'Search Social Icon Shortcodes', 'social-icons-widget-by-wpzoom' ),
			'not_found'          => __( 'No shortcode found', 'social-icons-widget-by-wpzoom' ),
			'not_found_in_trash' => __( 'No shortcode found in Trash', 'social-icons-widget-by-wpzoom' ),
			'parent_item_colon'  => '',
			'menu_name'          => self::$post_type_name,
		);
		$args   = array(
			'labels'             => $labels,
			'description'        => 'A post type for entering shortcode information.',
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'query_var'          => true,
			'hierarchical'       => false,
			'show_in_nav_menus'  => false,
			'supports'           => array( 'title' ),
			'has_archive'        => false,
			'menu_position'      => 80,
			'menu_icon'          => 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-3 -3 62 62"><path fill="#a7aaad" fill-rule="evenodd" d="M28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28C0 12.536 12.536 0 28 0ZM36.667 11C34.2738 11 32.333 12.9028 32.333 15.25C32.333 15.324 32.3351 15.3976 32.3389 15.4707L22.1104 20.4873C21.3584 19.8711 20.3896 19.5 19.333 19.5C16.9399 19.5002 15 21.4029 15 23.75C15 26.0971 16.9399 27.9998 19.333 28C20.3897 28 21.3583 27.6291 22.1104 27.0127L32.3389 32.0283C32.335 32.1017 32.333 32.1757 32.333 32.25C32.333 32.324 32.3351 32.3976 32.3389 32.4707L22.1094 37.4873C21.3575 36.8713 20.3893 36.5 19.333 36.5C16.9399 36.5002 15 38.4029 15 40.75C15 43.0971 16.9399 44.9998 19.333 45C21.7262 45 23.667 43.0972 23.667 40.75C23.667 40.6756 23.664 40.6017 23.6602 40.5283L33.8887 35.5127C34.6408 36.1293 35.61 36.5 36.667 36.5C39.0601 36.4998 41 34.5971 41 32.25C41 29.9029 39.0601 28.0002 36.667 28C35.6102 28 34.6407 28.3708 33.8887 28.9873L23.6602 23.9707C23.664 23.8976 23.667 23.824 23.667 23.75C23.667 23.6756 23.664 23.6017 23.6602 23.5283L33.8896 18.5127C34.6417 19.1291 35.6103 19.5 36.667 19.5C39.0601 19.4998 41 17.5971 41 15.25C41 12.9029 39.0601 11.0002 36.667 11Z"/></svg>' ), // phpcs:ignore
		);
		register_post_type( 'wpzoom-shortcode', $args );
	}

	/**
	 * Add form to post
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function add_form_to_post( $post ) {
		if ( 'wpzoom-shortcode' === $post->post_type ) {
			$post_id         = $post->ID;
			$widget_instance = $this->get_data( $post_id );
			$widget_instance['widget']->_set( $post_id );

			?>
			<p>
				<label><strong><?php esc_html_e( 'Shortcode:', 'social-icons-widget-by-wpzoom' ); ?></strong></label>

				<input type="text" id="wpz-social-shortcode" onClick="this.select();" value="<?php $this->display_shortcode_string( $post->ID ); ?>">

			</p>

			<div class="social_shortcode_wrap">

				<?php

					$widget_instance['widget']->form( $widget_instance['instance'] );

				?>
				</div>
			<?php
		}
	}

	/**
	 * Get shortcode data
	 *
	 * @since 1.1.0
	 *
	 * @param int|string $post_id The post ID.
	 * @return array
	 */
	public function get_data( $post_id ) {
		$widget   = ZOOM_Social_Icons_Widget::get_instance();
		$instance = $widget->get_defaults();

		$serialised_data = get_post_meta( $post_id, '_shortcode_item_wpzoom-icons' );

		if ( ! empty( $serialised_data ) ) {
			$unserialised_data = unserialize( $serialised_data[0] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

			if ( ! empty( $unserialised_data['fields'] ) ) {
				return array(
					'widget'   => $widget,
					'instance' => $unserialised_data,
				);
			}
		}

		return array(
			'widget'   => $widget,
			'instance' => $instance,
		);
	}

	/**
	 * Save shortcode data
	 *
	 * @since 1.1.0
	 *
	 * @param int|string $post_id The post ID.
	 * @return void
	 */
	public function save_data( $post_id ) {
		$widget = ZOOM_Social_Icons_Widget::get_instance();
		$data   = isset( $_POST['widget-zoom-social-icons-widget'] ) ? wp_unslash( $_POST['widget-zoom-social-icons-widget'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( isset( $data[ $post_id ] ) ) {
			$defaults     = $widget->get_defaults();
			$new_instance = wp_parse_args( $data[ $post_id ], $defaults );

			unset( $new_instance['fields'] );

			$data_to_save = $widget->update(
				$new_instance,
				array()
			);
			update_post_meta( $post_id, '_shortcode_item_wpzoom-icons', serialize( $data_to_save ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		}
	}

	/**
	 * Register columns
	 *
	 * @since 1.1.0
	 *
	 * @param array $columns The registered columns.
	 * @return array
	 */
	public function register_columns( array $columns ) {
		$columns['shortcode_wpzoom-icons'] = __( 'Shortcode', 'social-icons-widget-by-wpzoom' );
		return $columns;
	}

	/**
	 * Render column
	 *
	 * @since 1.1.0
	 *
	 * @param string $column_id The column id.
	 * @return mixed
	 */
	public function render_column( $column_id ) {
		if ( 'shortcode_wpzoom-icons' !== $column_id ) {
			return;
		}

		$post = get_post();
		?>
		<input type="text" size="33" id="wpz-social-shortcode" onClick="this.select();" value="<?php $this->display_shortcode_string( $post->ID ); ?>">
		<?php
	}

	/**
	 * Display generated shortcode string
	 *
	 * @since 1.1.0
	 *
	 * @param int|string $post_id The post ID.
	 * @return void
	 */
	public function display_shortcode_string( $post_id ) {
		echo esc_html( '[wpzoom_social_icons id="' . $post_id . '"]' );
	}

	/**
	 * Register shortcode
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'wpzoom_social_icons', array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$post_id  = $atts['id'];
		$instance = $this->get_data( $post_id );

		// Remove title from shortcode.
		$instance['instance']['title'] = '';

		ob_start();
		$instance['widget']->widget(
			array(
				'before_widget' => '<section class="zoom-social-icons-shortcode">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			),
			$instance['instance']
		);
		$item_output = ob_get_clean();

		return $item_output;
	}
}

new WPZOOM_Social_Icons_Free_Shortcode();
