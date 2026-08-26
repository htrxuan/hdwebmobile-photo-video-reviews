<?php

namespace htrxuan\hdpvr;

if (!defined('ABSPATH')) {
    exit;
}

class HDPVR_Activator
{

    public static function activate()
    {
        if (!self::is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(HDPVR_PLUGIN_FILE));
            set_transient('hdpvr_wc_missing_notice', true, 30);
            return;
        }

        if (false === get_option('hdpvr_options')) {
            add_option('hdpvr_options', array(
                'enabled'           => 1,
                'allow_video'       => 1,
                'max_files'         => 3,
                'max_image_size_mb' => 5,
                'max_video_size_mb' => 20,
            ));
        }
    }

    public static function is_woocommerce_active()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce');
    }
}
