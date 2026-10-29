<?php

namespace WpConnectMcp\Support;

use WP_Error;
use WP_Post;

defined('ABSPATH') || exit;

final class PostExtras
{
    private const SEO_FIELDS = [
        'seo_title' => ['meta' => '_yoast_wpseo_title', 'max' => 70],
        'seo_description' => ['meta' => '_yoast_wpseo_metadesc', 'max' => 160],
        'focus_keyword' => ['meta' => '_yoast_wpseo_focuskw', 'max' => 0],
    ];

    /**
     * Valida los campos que se guardan fuera de wp_insert_post()/wp_update_post().
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function validate(array $input)
    {
        $extras = [];
        $hasSeo = false;

        foreach (self::SEO_FIELDS as $field => $settings) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $hasSeo = true;
            $value = sanitize_text_field((string) $input[$field]);
            $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
            if ($settings['max'] && $length > $settings['max']) {
                return new WP_Error(
                    'wp_connect_mcp_seo_too_long',
                    sprintf('%s admite como máximo %d caracteres.', $field, $settings['max']),
                    ['status' => 400]
                );
            }

            $extras[$field] = $value;
        }

        if ($hasSeo && !self::isYoastActive()) {
            return new WP_Error(
                'wp_connect_mcp_yoast_inactive',
                'No se pueden guardar los campos SEO porque Yoast SEO no está activo.',
                ['status' => 409]
            );
        }

        if (array_key_exists('featured_image', $input)) {
            $attachmentId = (int) $input['featured_image'];
            if ($attachmentId > 0) {
                $attachment = get_post($attachmentId);
                if (!$attachment instanceof WP_Post || 'attachment' !== $attachment->post_type || !wp_attachment_is_image($attachmentId)) {
                    return new WP_Error('wp_connect_mcp_invalid_featured_image', 'featured_image no corresponde a una imagen.', ['status' => 400]);
                }

                if ((int) $attachment->post_author !== get_current_user_id()) {
                    return new WP_Error(
                        'wp_connect_mcp_not_own_media',
                        'Solo se puede usar una imagen subida por este usuario.',
                        ['status' => 403]
                    );
                }
            }

            $extras['featured_image'] = $attachmentId;
        }

        return $extras;
    }

    /**
     * @param array<string, mixed> $extras
     */
    public static function apply(int $postId, array $extras): void
    {
        foreach (self::SEO_FIELDS as $field => $settings) {
            if (array_key_exists($field, $extras)) {
                update_post_meta($postId, $settings['meta'], $extras[$field]);
            }
        }

        if (array_key_exists('featured_image', $extras)) {
            if ($extras['featured_image']) {
                set_post_thumbnail($postId, (int) $extras['featured_image']);
            } else {
                delete_post_thumbnail($postId);
            }
        }
    }

    private static function isYoastActive(): bool
    {
        return defined('WPSEO_VERSION') || defined('WPSEO_FILE') || class_exists('WPSEO_Options');
    }
}
