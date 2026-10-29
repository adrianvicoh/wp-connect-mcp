<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WpConnectMcp\Plugin;
use WpConnectMcp\Support\PostAccess;

defined('ABSPATH') || exit;

final class UploadMedia
{
    public const NAME = 'wp-connect-mcp/upload-media';
    private const DEFAULT_MAX_BYTES = 2097152;
    private const ALLOWED_MIMES = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Subir imagen',
            'description' => 'Sube una imagen JPEG, PNG, WebP o GIF codificada en base64 (máximo 2 MB por defecto). No descarga URLs remotas.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'filename' => ['type' => 'string', 'description' => 'Nombre de archivo, con extensión.'],
                    'data' => ['type' => 'string', 'description' => 'Contenido del archivo codificado en base64.'],
                    'alt' => ['type' => 'string', 'description' => 'Texto alternativo.'],
                    'title' => ['type' => 'string', 'description' => 'Título del medio.'],
                    'post_id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Entrada propia a la que adjuntar la imagen (opcional).'],
                ],
                'required' => ['filename', 'data'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'url' => ['type' => 'string'],
                    'filename' => ['type' => 'string'],
                    'alt' => ['type' => 'string'],
                    'width' => ['type' => 'integer'],
                    'height' => ['type' => 'integer'],
                ],
                'required' => ['id', 'url', 'filename', 'alt', 'width', 'height'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => ['annotations' => ['readonly' => false, 'destructive' => false, 'idempotent' => false]],
        ]);
    }

    /**
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    public static function permission($input = null)
    {
        if (!current_user_can('upload_files')) {
            return false;
        }

        $input = (array) $input;

        return empty($input['post_id']) ? true : PostAccess::canEditOwn(['id' => (int) $input['post_id']]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function execute(array $input)
    {
        $filename = sanitize_file_name((string) $input['filename']);
        if ('' === $filename) {
            return new WP_Error('wp_connect_mcp_invalid_filename', 'filename no es válido.', ['status' => 400]);
        }

        $encoded = preg_replace('/\s+/', '', (string) $input['data']);
        $data = base64_decode($encoded, true);
        if (false === $data) {
            return new WP_Error('wp_connect_mcp_invalid_base64', 'data no contiene base64 válido.', ['status' => 400]);
        }

        $maxBytes = (int) apply_filters('wp_connect_mcp_max_upload_bytes', self::DEFAULT_MAX_BYTES);
        if (strlen($data) > $maxBytes) {
            return new WP_Error(
                'wp_connect_mcp_file_too_large',
                sprintf('La imagen supera el límite de %d bytes.', $maxBytes),
                ['status' => 413]
            );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $temporary = wp_tempnam($filename);
        if (!$temporary || false === file_put_contents($temporary, $data)) {
            return new WP_Error('wp_connect_mcp_temp_file_failed', 'No se ha podido preparar la imagen.', ['status' => 500]);
        }

        $checked = wp_check_filetype_and_ext($temporary, $filename, self::ALLOWED_MIMES);
        if (empty($checked['type']) || !in_array($checked['type'], self::ALLOWED_MIMES, true)) {
            wp_delete_file($temporary);

            return new WP_Error(
                'wp_connect_mcp_invalid_image',
                'El archivo no es una imagen JPEG, PNG, WebP o GIF válida.',
                ['status' => 400]
            );
        }

        if (!empty($checked['proper_filename'])) {
            $filename = sanitize_file_name($checked['proper_filename']);
        }

        $file = ['name' => $filename, 'tmp_name' => $temporary, 'size' => strlen($data), 'error' => 0];
        $postId = (int) ($input['post_id'] ?? 0);
        $title = array_key_exists('title', $input) ? sanitize_text_field((string) $input['title']) : '';
        $attachmentId = media_handle_sideload($file, $postId, $title);
        if (is_wp_error($attachmentId)) {
            if (file_exists($temporary)) {
                wp_delete_file($temporary);
            }

            return $attachmentId;
        }

        wp_update_post(['ID' => $attachmentId, 'post_author' => get_current_user_id()]);
        $alt = sanitize_text_field((string) ($input['alt'] ?? ''));
        update_post_meta($attachmentId, '_wp_attachment_image_alt', $alt);
        $metadata = wp_get_attachment_metadata($attachmentId);

        return [
            'id' => (int) $attachmentId,
            'url' => (string) wp_get_attachment_url($attachmentId),
            'filename' => wp_basename((string) get_attached_file($attachmentId)),
            'alt' => $alt,
            'width' => (int) ($metadata['width'] ?? 0),
            'height' => (int) ($metadata['height'] ?? 0),
        ];
    }
}
