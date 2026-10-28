<?php

namespace WpConnectMcp\Abilities;

use WpConnectMcp\Plugin;
use WpConnectMcp\Support\PostFormatter;
use WpConnectMcp\Support\PostInput;

defined('ABSPATH') || exit;

final class CreatePost
{
    public const NAME = 'wp-connect-mcp/create-post';

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Crear entrada',
            'description' => 'Crea una entrada (post) del usuario como borrador, pendiente de revisión, programada o publicada (si el usuario puede publicar).',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => PostInput::schemaProperties(),
                'required' => ['title'],
                'additionalProperties' => false,
            ],
            'output_schema' => PostFormatter::fullSchema(),
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => [
                'annotations' => [
                    'readonly' => false,
                    'destructive' => false,
                    'idempotent' => false,
                ],
            ],
        ]);
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
        $data = PostInput::toPostData($input);
        if (is_wp_error($data)) {
            return $data;
        }

        $data['post_type'] = 'post';

        $id = wp_insert_post(wp_slash($data), true);
        if (is_wp_error($id)) {
            return $id;
        }

        return PostFormatter::full(get_post($id));
    }
}
