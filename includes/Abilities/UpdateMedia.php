<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WP_Post;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;

defined('ABSPATH') || exit;

final class UpdateMedia
{
    public const NAME = 'wp-connect-mcp/update-media';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Editar imagen',
            'description' => 'Cambia el texto alternativo y el título de una imagen subida por este usuario. No cambia el archivo.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la imagen (el que devuelve upload-media).'],
                    'alt' => ['type' => 'string', 'description' => 'Texto alternativo nuevo (vacío lo quita).'],
                    'title' => ['type' => 'string', 'description' => 'Título nuevo del medio.'],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'url' => ['type' => 'string'],
                    'filename' => ['type' => 'string'],
                    'alt' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                ],
                'required' => ['id', 'url', 'filename', 'alt', 'title'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => ['annotations' => ['readonly' => false, 'destructive' => false, 'idempotent' => true]],
        ]));
    }

    /**
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    public static function permission($input = null)
    {
        // Antes de buscar el medio, para no revelar qué IDs existen a quien no puede subir archivos.
        if (!current_user_can('upload_files')) {
            return false;
        }

        $attachment = self::find(((array) $input)['id'] ?? 0);
        if (is_wp_error($attachment)) {
            return $attachment;
        }

        return current_user_can('edit_post', $attachment->ID);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function execute(array $input)
    {
        $attachment = self::find($input['id']);
        if (is_wp_error($attachment)) {
            return $attachment;
        }

        if (!array_key_exists('alt', $input) && !array_key_exists('title', $input)) {
            return new WP_Error('wp_connect_mcp_nothing_to_update', 'No se ha indicado ningún campo que cambiar (alt o title).', ['status' => 400]);
        }

        if (array_key_exists('title', $input)) {
            $result = wp_update_post(wp_slash([
                'ID' => $attachment->ID,
                'post_title' => sanitize_text_field((string) $input['title']),
            ]), true);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        if (array_key_exists('alt', $input)) {
            update_post_meta($attachment->ID, '_wp_attachment_image_alt', sanitize_text_field((string) $input['alt']));
        }

        return [
            'id' => $attachment->ID,
            'url' => (string) wp_get_attachment_url($attachment->ID),
            'filename' => wp_basename((string) get_attached_file($attachment->ID)),
            'alt' => (string) get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'title' => (string) get_the_title($attachment->ID),
        ];
    }

    /**
     * Solo imágenes subidas por el usuario actual; las demás dan el mismo error que un ID inexistente.
     *
     * @param mixed $id
     * @return WP_Post|WP_Error
     */
    private static function find($id)
    {
        $attachment = get_post((int) $id);

        if (
            !$attachment instanceof WP_Post
            || 'attachment' !== $attachment->post_type
            || !wp_attachment_is_image($attachment->ID)
            || (int) $attachment->post_author !== get_current_user_id()
        ) {
            return new WP_Error(
                'wp_connect_mcp_media_not_found',
                'No existe ninguna imagen subida por este usuario con ese ID.',
                ['status' => 404]
            );
        }

        return $attachment;
    }
}
