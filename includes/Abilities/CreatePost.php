<?php

namespace WpConnectMcp\Abilities;

use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\PostFormatter;
use WpConnectMcp\Support\PostInput;
use WpConnectMcp\Support\PostExtras;

defined('ABSPATH') || exit;

final class CreatePost
{
    public const NAME = 'wp-connect-mcp/create-post';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Crear entrada',
            'description' => 'Crea una entrada (post) del usuario como borrador, pendiente de revisión, programada o publicada (si el usuario puede publicar).',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => PostInput::schemaProperties() + [
                    'return_content' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Devolver también el contenido guardado. Por defecto solo se devuelve un resumen (longitud, MD5, encabezados y enlaces).',
                    ],
                ],
                'required' => ['title'],
                'additionalProperties' => false,
            ],
            'output_schema' => PostFormatter::savedSchema(),
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => [
                'annotations' => [
                    'readonly' => false,
                    'destructive' => false,
                    'idempotent' => false,
                ],
            ],
        ]));
    }

    public static function permission(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|\WP_Error
     */
    public static function execute(array $input)
    {
        $extras = PostExtras::validate($input);
        if (is_wp_error($extras)) {
            return $extras;
        }

        $data = PostInput::toPostData($input);
        if (is_wp_error($data)) {
            return $data;
        }

        $data['post_type'] = 'post';

        $id = wp_insert_post(wp_slash($data), true);
        if (is_wp_error($id)) {
            return $id;
        }

        PostExtras::apply($id, $extras);

        return PostFormatter::saved(get_post($id), !empty($input['return_content']));
    }
}
