<?php

namespace WpConnectMcp\Abilities;

use WP_Post;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\PublishedContent;

defined('ABSPATH') || exit;

final class ListPages
{
    public const NAME = 'wp-connect-mcp/list-pages';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Listar páginas publicadas',
            'description' => 'Lista las páginas publicadas del sitio (id, título, URL y padre), sin el contenido. Para el enlazado interno; el contenido de una se lee con get-published.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'default' => [],
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Texto a buscar en título y contenido.'],
                    'parent' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Solo las hijas de esta página (0: solo las de primer nivel).'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => PublishedContent::MAX_PER_PAGE, 'description' => 'Resultados por página (por defecto 20).'],
                    'page' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Página (por defecto 1).'],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'pages' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'title' => ['type' => 'string'],
                                'link' => ['type' => 'string'],
                                'parent' => ['type' => 'integer'],
                                'modified' => ['type' => ['string', 'null']],
                            ],
                            'required' => ['id', 'title', 'link', 'parent'],
                        ],
                    ],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ],
                'required' => ['pages', 'total', 'total_pages'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => ['annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
        ]));
    }

    public static function permission(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * @param array<string, mixed>|null $input
     * @return array<string, mixed>
     */
    public static function execute($input = null): array
    {
        $input = (array) $input;
        $extra = isset($input['parent']) ? ['post_parent' => (int) $input['parent']] : [];
        $result = PublishedContent::query('page', $input, $extra);

        return [
            'pages' => array_map(static function (WP_Post $post): array {
                return [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'link' => (string) get_permalink($post),
                    'parent' => (int) $post->post_parent,
                    'modified' => PublishedContent::modified($post),
                ];
            }, $result['items']),
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
        ];
    }
}
