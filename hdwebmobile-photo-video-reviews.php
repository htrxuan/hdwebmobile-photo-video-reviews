<?php

/**
 * Plugin Name: HDWebmobile Photo & Video Reviews
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-photo-video-reviews/
 * Description: Let customers attach photos and short videos to their WooCommerce product reviews. Uploads are only ever processed as part of a real, nonce-verified review submission -- never a standalone always-open upload endpoint.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-photo-video-reviews
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdpvr;

if (!defined('ABSPATH')) {
    exit;
}

define('HDPVR_VERSION', '1.0.0');
define('HDPVR_PLUGIN_FILE', __FILE__);
define('HDPVR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDPVR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDPVR_PLUGIN_DIR . 'includes/class-hdpvr-activator.php';

register_activation_hook(__FILE__, array(HDPVR_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDPVR_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDPVR_PLUGIN_DIR . 'includes/class-hdpvr-core.php';
    HDPVR_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-photo-video-reviews') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
