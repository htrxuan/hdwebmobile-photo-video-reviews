<?php

namespace htrxuan\hdpvr;

if (!defined('ABSPATH')) {
    exit;
}

class HDPVR_Admin
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
        require_once HDPVR_PLUGIN_DIR . 'includes/class-hdpvr-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['photo-video-reviews'] = array(
            'label'  => __('Photo & Video Reviews', 'hdwebmobile-photo-video-reviews'),
            'order'  => 140,
            'render' => array($this, 'render_settings_page'),
        );
        return $tabs;
    }

    public function render_settings_page()
    {
        ?>
        <p><?php esc_html_e('Let customers attach photos and short videos to their WooCommerce product reviews. Uploads only ever happen as part of a real, nonce-verified review submission -- there is no separate upload endpoint.', 'hdwebmobile-photo-video-reviews'); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields('hdpvr_option_group');
            do_settings_sections('hdpvr-settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function page_init()
    {
        register_setting(
            'hdpvr_option_group',
            'hdpvr_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => self::get_default_options(),
            )
        );

        add_settings_section(
            'hdpvr_section_general',
            __('General', 'hdwebmobile-photo-video-reviews'),
            '__return_false',
            'hdpvr-settings'
        );

        add_settings_field('enabled', __('Enable photo/video reviews', 'hdwebmobile-photo-video-reviews'), array($this, 'enabled_callback'), 'hdpvr-settings', 'hdpvr_section_general');
        add_settings_field('allow_video', __('Allow video uploads', 'hdwebmobile-photo-video-reviews'), array($this, 'allow_video_callback'), 'hdpvr-settings', 'hdpvr_section_general');
        add_settings_field('max_files', __('Max files per review', 'hdwebmobile-photo-video-reviews'), array($this, 'max_files_callback'), 'hdpvr-settings', 'hdpvr_section_general');
        add_settings_field('max_image_size_mb', __('Max photo size (MB)', 'hdwebmobile-photo-video-reviews'), array($this, 'max_image_size_callback'), 'hdpvr-settings', 'hdpvr_section_general');
        add_settings_field('max_video_size_mb', __('Max video size (MB)', 'hdwebmobile-photo-video-reviews'), array($this, 'max_video_size_callback'), 'hdpvr-settings', 'hdpvr_section_general');
    }

    public static function get_default_options()
    {
        return array(
            'enabled'           => 1,
            'allow_video'       => 1,
            'max_files'         => 3,
            'max_image_size_mb' => 5,
            'max_video_size_mb' => 20,
        );
    }

    public static function get_options()
    {
        return wp_parse_args(get_option('hdpvr_options', array()), self::get_default_options());
    }

    public function sanitize($input)
    {
        $defaults = self::get_default_options();
        $new_input = array();

        $new_input['enabled']           = isset($input['enabled']) ? 1 : 0;
        $new_input['allow_video']       = isset($input['allow_video']) ? 1 : 0;
        $new_input['max_files']         = isset($input['max_files']) ? min(10, max(1, absint($input['max_files']))) : $defaults['max_files'];
        $new_input['max_image_size_mb'] = isset($input['max_image_size_mb']) ? min(50, max(1, absint($input['max_image_size_mb']))) : $defaults['max_image_size_mb'];
        $new_input['max_video_size_mb'] = isset($input['max_video_size_mb']) ? min(200, max(1, absint($input['max_video_size_mb']))) : $defaults['max_video_size_mb'];

        return $new_input;
    }

    public function enabled_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="checkbox" name="hdpvr_options[enabled]" value="1" %s />',
            checked(1, $options['enabled'], false)
        );
    }

    public function allow_video_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="checkbox" name="hdpvr_options[allow_video]" value="1" %s /> <span class="description">%s</span>',
            checked(1, $options['allow_video'], false),
            esc_html__('MP4, WebM, and MOV. Turn off to accept photos only.', 'hdwebmobile-photo-video-reviews')
        );
    }

    public function max_files_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="1" max="10" name="hdpvr_options[max_files]" value="%s" class="small-text" />',
            esc_attr($options['max_files'])
        );
    }

    public function max_image_size_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="1" max="50" name="hdpvr_options[max_image_size_mb]" value="%s" class="small-text" />',
            esc_attr($options['max_image_size_mb'])
        );
    }

    public function max_video_size_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="1" max="200" name="hdpvr_options[max_video_size_mb]" value="%s" class="small-text" />',
            esc_attr($options['max_video_size_mb'])
        );
    }
}
