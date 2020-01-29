<?php
/**
 * Plugin Name: Social Icons Widget by WPZOOM
 * Plugin URI: https://www.wpzoom.com/plugins/social-widget/
 * Description: Social Icons Widget to display links to social sharing websites. Supports more than 80 sites and includes 400 icons. Sort icons by Drag & Drop and change their color easily.
 * Author: WPZOOM
 * Author URI: https://www.wpzoom.com/
 * Version: 3.4.1
 * License: GPLv2 or later
 * Text Domain: zoom-social-icons-widget
 * Domain Path: /languages
 */

require_once plugin_dir_path(__FILE__) . 'class.zoom-social-icons-settings.php';
require_once plugin_dir_path(__FILE__) . 'zoom-helper.php';

$wpzoom_social_icons_settings = WPZOOM_Social_Icons_Settings::get_settings();

if (empty($wpzoom_social_icons_settings['disable-block'])) {
    require_once plugin_dir_path(__FILE__) . 'block/src/init.php';
}

if (empty($wpzoom_social_icons_settings['disable-widget'])) {
    require_once plugin_dir_path(__FILE__) . 'class.zoom-social-icons-widget.php';

    /**
     * Register the widget
     */
    add_action('widgets_init', function () {
        register_widget('Zoom_Social_Icons_Widget');
    });

}


/**
 * Load textdomain
 */
function zoom_social_icons_widget_load_textdomain()
{
    load_plugin_textdomain('zoom-social-icons-widget', false, plugin_basename(dirname(__FILE__)) . '/languages');
}

add_action('init', 'zoom_social_icons_widget_load_textdomain');