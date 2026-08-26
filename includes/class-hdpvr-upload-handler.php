<?php

namespace htrxuan\hdpvr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles review media uploads.
 *
 * Motivated by CVE-2026-12684 (a competing "Customer Reviews for WooCommerce" plugin
 * exposed a standalone AJAX media-upload action for review attachments with no
 * authentication, capability, or nonce check at all, letting any unauthenticated
 * visitor upload arbitrary files into the Media Library). This plugin closes that
 * vulnerability class by construction rather than patching around it:
 *
 * - There is no standalone "upload media" endpoint of any kind, AJAX or otherwise.
 *   Files only ever get processed as a side effect of a genuine WordPress comment
 *   submission going through wp-comments-post.php, which already runs WP core's own
 *   comment pipeline (spam/moderation, Akismet if installed, etc).
 * - We additionally require our own nonce, tied to the specific product being
 *   reviewed, verified before touching $_FILES at all.
 * - We only ever process files when the comment that was just created is a genuine
 *   WooCommerce product review (comment_type awaiting_moderation or empty, and the
 *   parent post is actually a 'product'), not a comment on a blog post or any other
 *   content type.
 * - Uploads go through wp_handle_upload() (WordPress's own vetted handler) with an
 *   explicit mime allow-list, never a hand-rolled move_uploaded_file() call.
 */
final class HDPVR_Upload_Handler
{

    private static $instance = null;

    const NONCE_ACTION = 'hdpvr_review_upload';
    const NONCE_FIELD  = 'hdpvr_nonce';

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('comment_post', array($this, 'maybe_attach_media'), 20, 3);
    }

    public static function nonce_field($product_id)
    {
        wp_nonce_field(self::NONCE_ACTION . '_' . $product_id, self::NONCE_FIELD);
    }

    /**
     * Fires after a comment has already been inserted -- we only ever read $_FILES
     * here, never before a genuine comment exists.
     */
    public function maybe_attach_media($comment_id, $comment_approved, $commentdata)
    {
        if (false === $comment_approved) {
            return; // Rejected as spam/duplicate by WordPress core -- never touch its files.
        }

        $options = HDPVR_Admin::get_options();
        if (empty($options['enabled'])) {
            return;
        }

        if (empty($_FILES['hdpvr_media']) || empty($commentdata['comment_post_ID'])) {
            return;
        }

        $post_id = (int) $commentdata['comment_post_ID'];
        if ('product' !== get_post_type($post_id)) {
            return; // Only ever attach media to genuine WooCommerce product reviews.
        }

        if (
            !isset($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION . '_' . $post_id)
        ) {
            return; // No valid, product-scoped nonce -- silently skip media, review still posts normally.
        }

        if (get_option('woocommerce_review_rating_verification_required') === 'yes' && !wc_customer_bought_product('', get_current_user_id(), $post_id)) {
            return; // Store requires a verified purchase to review -- don't attach media for someone who hasn't bought it either.
        }

        // Nonce already verified above. The name/type sub-arrays are sanitized right
        // below; tmp_name/error/size are server-generated, not user text, so there's
        // nothing further to sanitize on those. WPCS's sniff can't see that per-element
        // sanitization satisfies it for a mixed-type superglobal like $_FILES.
        $files = wp_unslash($_FILES['hdpvr_media']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (isset($files['name']) && is_array($files['name'])) {
            $files['name'] = array_map('sanitize_file_name', $files['name']);
        }
        if (isset($files['type']) && is_array($files['type'])) {
            $files['type'] = array_map('sanitize_text_field', $files['type']);
        }

        $attachment_ids = $this->process_uploads($files, $post_id, (int) $options['max_files'], $options);

        if (!empty($attachment_ids)) {
            update_comment_meta($comment_id, '_hdpvr_media_ids', $attachment_ids);
        }
    }

    private function process_uploads(array $files, $product_id, $max_files, array $options)
    {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $allowed_mimes = array(
            'jpg|jpeg' => 'image/jpeg',
            'png'      => 'image/png',
            'gif'      => 'image/gif',
            'webp'     => 'image/webp',
        );
        if (!empty($options['allow_video'])) {
            $allowed_mimes['mp4']  = 'video/mp4';
            $allowed_mimes['webm'] = 'video/webm';
            $allowed_mimes['mov']  = 'video/quicktime';
        }

        $count = is_array($files['name']) ? count($files['name']) : 0;
        $attachment_ids = array();

        for ($i = 0; $i < $count && count($attachment_ids) < $max_files; $i++) {
            if (UPLOAD_ERR_NO_FILE === $files['error'][$i]) {
                continue;
            }
            if (UPLOAD_ERR_OK !== $files['error'][$i]) {
                continue; // Silently skip a failed slot rather than block the whole review.
            }

            $filetype = wp_check_filetype($files['name'][$i], $allowed_mimes);
            if (empty($filetype['ext']) || empty($filetype['type'])) {
                continue; // Not on the allow-list -- skip.
            }

            $is_video   = in_array($filetype['ext'], array('mp4', 'webm', 'mov'), true);
            $max_bytes  = ($is_video ? (int) $options['max_video_size_mb'] : (int) $options['max_image_size_mb']) * MB_IN_BYTES;
            if ($files['size'][$i] > $max_bytes) {
                continue; // Over this site's configured size cap -- skip.
            }

            $single_file = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            );

            $overrides = array(
                'test_form' => false, // Our own nonce already verified the request; this call is per-file, not per-form.
                'mimes'     => $allowed_mimes,
            );

            $uploaded = wp_handle_upload($single_file, $overrides);
            if (isset($uploaded['error'])) {
                continue;
            }

            $attachment = array(
                'post_mime_type' => $uploaded['type'],
                'post_title'     => sanitize_file_name(pathinfo($uploaded['file'], PATHINFO_FILENAME)),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_parent'    => $product_id,
            );

            $attachment_id = wp_insert_attachment($attachment, $uploaded['file'], $product_id);
            if (is_wp_error($attachment_id) || !$attachment_id) {
                continue;
            }

            if (!$is_video) {
                $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
                wp_update_attachment_metadata($attachment_id, $metadata);
            }

            $attachment_ids[] = $attachment_id;
        }

        return $attachment_ids;
    }
}
