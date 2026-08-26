<?php

namespace htrxuan\hdpvr;

if (!defined('ABSPATH')) {
    exit;
}

final class HDPVR_Frontend
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
        add_filter('woocommerce_product_review_comment_form_args', array($this, 'add_upload_field'));
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
        add_action('woocommerce_review_after_comment_text', array($this, 'render_review_media'));
    }

    public function maybe_enqueue_assets()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        $options = HDPVR_Admin::get_options();
        if (empty($options['enabled'])) {
            return;
        }

        wp_enqueue_style('hdpvr-frontend', HDPVR_PLUGIN_URL . 'assets/css/hdpvr-frontend.css', array(), HDPVR_VERSION);
        wp_enqueue_script('hdpvr-frontend', HDPVR_PLUGIN_URL . 'assets/js/hdpvr-frontend.js', array(), HDPVR_VERSION, true);
    }

    public function add_upload_field($comment_form)
    {
        $options = HDPVR_Admin::get_options();
        if (empty($options['enabled'])) {
            return $comment_form;
        }

        global $product;
        if (!$product) {
            return $comment_form;
        }

        $accept = $this->get_accept_attribute($options);
        $max_files = (int) $options['max_files'];

        ob_start();
        HDPVR_Upload_Handler::nonce_field($product->get_id());
        $nonce_html = ob_get_clean();

        $label = $options['allow_video']
            ? sprintf(
                /* translators: %d: maximum number of files */
                esc_html__('Add photos or a short video (up to %d files)', 'hdwebmobile-photo-video-reviews'),
                $max_files
            )
            : sprintf(
                /* translators: %d: maximum number of files */
                esc_html__('Add photos (up to %d files)', 'hdwebmobile-photo-video-reviews'),
                $max_files
            );

        $field_html  = '<p class="comment-form-hdpvr-media">';
        $field_html .= '<label for="hdpvr_media">' . $label . '</label>';
        $field_html .= '<input type="file" id="hdpvr_media" name="hdpvr_media[]" accept="' . esc_attr($accept) . '" data-max-files="' . esc_attr($max_files) . '" multiple="multiple" />';
        $field_html .= $nonce_html;
        $field_html .= '</p>';

        $comment_form['comment_field'] = isset($comment_form['comment_field']) ? $comment_form['comment_field'] . $field_html : $field_html;

        return $comment_form;
    }

    private function get_accept_attribute($options)
    {
        $types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!empty($options['allow_video'])) {
            $types = array_merge($types, array('video/mp4', 'video/webm', 'video/quicktime'));
        }
        return implode(',', $types);
    }

    public function render_review_media($comment)
    {
        $attachment_ids = get_comment_meta($comment->comment_ID, '_hdpvr_media_ids', true);
        if (empty($attachment_ids) || !is_array($attachment_ids)) {
            return;
        }

        echo '<div class="hdpvr-review-media">';
        foreach ($attachment_ids as $attachment_id) {
            $mime = get_post_mime_type($attachment_id);
            if (!$mime) {
                continue;
            }
            if (0 === strpos($mime, 'video/')) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    printf(
                        '<video class="hdpvr-review-video" controls preload="metadata" src="%s"></video>',
                        esc_url($url)
                    );
                }
            } else {
                $thumb = wp_get_attachment_image($attachment_id, 'thumbnail', false, array('class' => 'hdpvr-review-photo'));
                $full  = wp_get_attachment_image_url($attachment_id, 'large');
                if ($thumb && $full) {
                    printf(
                        '<a href="%s" class="hdpvr-review-photo-link" target="_blank" rel="noopener noreferrer">%s</a>',
                        esc_url($full),
                        $thumb // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() already escapes its own output.
                    );
                }
            }
        }
        echo '</div>';
    }
}
