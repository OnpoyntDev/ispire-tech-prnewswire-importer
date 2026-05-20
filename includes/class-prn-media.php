<?php

if (!defined('ABSPATH')) {
    exit;
}

class PRN_Media
{
    /**
     * Sideload remote image
     */
    public static function sideload_image(
        $image_url,
        $post_id,
        $title = ''
    ) {

        if (!$image_url) {
            return false;
        }

        /**
         * Load required WP media libraries
         */
        require_once ABSPATH .
            'wp-admin/includes/media.php';

        require_once ABSPATH .
            'wp-admin/includes/file.php';

        require_once ABSPATH .
            'wp-admin/includes/image.php';

        PRN_Logger::log(
            'Downloading image',
            [
                'url' => $image_url,
                'post_id' => $post_id,
            ]
        );

        /**
         * Download + attach image
         */
        $attachment_id = media_sideload_image(
            esc_url_raw($image_url),
            $post_id,
            $title,
            'id'
        );

        /**
         * Handle errors
         */
        if (is_wp_error($attachment_id)) {

            PRN_Logger::error(
                'Image sideload failed',
                [
                    'url' => $image_url,
                    'error' => $attachment_id->get_error_message(),
                ]
            );

            return false;
        }

        /**
         * Set alt text
         */
        if ($title) {

            update_post_meta(
                $attachment_id,
                '_wp_attachment_image_alt',
                sanitize_text_field($title)
            );
        }

        /**
         * Optional caption
         */
        wp_update_post([
            'ID' => $attachment_id,
            'post_title' => sanitize_text_field($title),
            'post_excerpt' => sanitize_text_field($title),
        ]);

        PRN_Logger::log(
            'Image imported',
            [
                'attachment_id' => $attachment_id,
                'post_id' => $post_id,
            ]
        );

        return intval($attachment_id);
    }
}