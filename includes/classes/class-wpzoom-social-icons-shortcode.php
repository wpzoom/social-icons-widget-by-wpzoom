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
	public static $post_type_name = 'AI Chat';

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
			'menu_icon'          => 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-12 0 251 250"><path fill="#a7aaad" d="M113.5 0C176.184 0 227 50.9256 227 113.745C227 176.565 168.555 250 105.87 250C102.47 250 99.104 249.849 95.7793 249.555C94.4106 249.434 93.604 247.981 94.1956 246.741L179.28 68.3939H148.899L124.797 117.11C122.636 121.44 120.61 125.635 118.72 129.695C116.965 133.619 115.277 137.949 113.657 142.685C112.037 137.949 110.348 133.619 108.593 129.695C106.838 125.635 104.88 121.44 102.719 117.11L79.0217 68.3939H47.2231L97.2192 170.011C97.4945 170.571 97.493 171.227 97.2151 171.785L73.9774 218.477C73.5189 219.398 72.4338 219.822 71.4783 219.44C29.5968 202.702 0 161.688 0 113.745C0 50.9256 50.8157 0 113.5 0Z"/></svg>' ), // phpcs:ignore
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
