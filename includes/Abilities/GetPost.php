<?php

namespace WpConnectMcp\Abilities;

use WpConnectMcp\Plugin;
use WpConnectMcp\Support\PostAccess;
use WpConnectMcp\Support\PostFormatter;

defined('ABSPATH') || exit;

final class GetPost
{
    public const NAME = 'wp-connect-mcp/get-post';

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Leer entrada',
            'description' => 'Devuelve una entrada (post) con su contenido sin procesar, extracto, estado, fechas, categorías y etiquetas.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la entrada.'],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => PostFormatter::fullSchema(),
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [PostAccess::class, 'canEdit'],
            'meta' => [
                'annotations' => [
                    'readonly' => true,
                    'destructive' => false,
                    'idempotent' => true,
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|\WP_Error
     */
    public static function execute(array $input)
    {
        $post = PostAccess::find($input['id']);

        return is_wp_error($post) ? $post : PostFormatter::full($post);
    }
}
