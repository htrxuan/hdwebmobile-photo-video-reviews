<?php

namespace htrxuan\hdpvr;

if (!defined('ABSPATH')) {
    exit;
}

final class HDPVR_Core
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDPVR_PLUGIN_DIR . 'includes/class-hdpvr-upload-handler.php';
        require_once HDPVR_PLUGIN_DIR . 'includes/class-hdpvr-frontend.php';
        require_once HDPVR_PLUGIN_DIR . 'includes/class-hdpvr-admin.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        HDPVR_Upload_Handler::get_instance();
        HDPVR_Frontend::get_instance();

        if (is_admin()) {
            HDPVR_Admin::get_instance();
        }
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdpvr_wc_missing_notice')) {
            return;
        }
        delete_transient('hdpvr_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Photo & Video Reviews requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-photo-video-reviews'); ?>
            </p>
        </div>
        <?php
    }
}
