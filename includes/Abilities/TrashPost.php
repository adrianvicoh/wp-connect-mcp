<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WP_Post;
use WpConnectMcp\Plugin;
use WpConnectMcp\Support\PostAccess;
use WpConnectMcp\Support\PostFormatter;

defined('ABSPATH') || exit;

final class TrashPost
{
    public const NAME = 'wp-connect-mcp/trash-post';

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Enviar entrada a la papelera',
            'description' => 'Borra una entrada (post) creada por este usuario enviándola a la papelera, de donde una persona puede restaurarla. Si estaba publicada, deja de verse en el sitio.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la entrada.'],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => PostFormatter::summarySchema(),
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [PostAccess::class, 'canDeleteOwn'],
            'meta' => [
                'annotations' => [
                    'readonly' => false,
                    'destructive' => true,
                    'idempotent' => false,
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function execute(array $input)
    {
        $post = PostAccess::find($input['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        // Sin papelera, wp_trash_post() borra para siempre.
        if (!EMPTY_TRASH_DAYS) {
            return new WP_Error(
                'wp_connect_mcp_trash_disabled',
                'La papelera está desactivada en este sitio (EMPTY_TRASH_DAYS): no se borra nada.',
                ['status' => 409]
            );
        }

        $result = wp_trash_post($post->ID);
        if (!$result instanceof WP_Post) {
            return new WP_Error('wp_connect_mcp_trash_failed', 'No se ha podido enviar la entrada a la papelera.', ['status' => 500]);
        }

        return PostFormatter::summary(get_post($post->ID));
    }
}
