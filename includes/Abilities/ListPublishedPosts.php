<?php

namespace WpConnectMcp\Abilities;

use WP_Post;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\PublishedContent;

defined('ABSPATH') || exit;

final class ListPublishedPosts
{
    public const NAME = 'wp-connect-mcp/list-published-posts';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Listar entradas publicadas',
            'description' => 'Lista las entradas publicadas del blog, de cualquier autor (id, título, URL, categorías, extracto), sin el contenido, de la más reciente a la más antigua. Para el enlazado interno y para detectar canibalización; el contenido de una se lee con get-published. Para editar, usar list-posts.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'default' => [],
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Texto a buscar en título y contenido (WordPress exige todas las palabras: mejor una o dos).'],
                    'category' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de categoría por la que filtrar.'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => PublishedContent::MAX_PER_PAGE, 'description' => 'Resultados por página (por defecto 20).'],
                    'page' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Página (por defecto 1).'],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'posts' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'title' => ['type' => 'string'],
                                'link' => ['type' => 'string'],
                                'date' => ['type' => ['string', 'null']],
                                'modified' => ['type' => ['string', 'null']],
                                'categories' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'excerpt' => ['type' => 'string'],
                            ],
                            'required' => ['id', 'title', 'link', 'categories', 'excerpt'],
                        ],
                    ],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ],
                'required' => ['posts', 'total', 'total_pages'],
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
        $extra = !empty($input['category']) ? ['cat' => (int) $input['category']] : [];
        $result = PublishedContent::query('post', $input, $extra);

        return [
            'posts' => array_map(static function (WP_Post $post): array {
                $date = get_post_datetime($post, 'date');

                return [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'link' => (string) get_permalink($post),
                    'date' => $date ? $date->format(DATE_ATOM) : null,
                    'modified' => PublishedContent::modified($post),
                    'categories' => array_map('intval', wp_get_post_categories($post->ID, ['fields' => 'ids'])),
                    'excerpt' => PublishedContent::excerpt($post),
                ];
            }, $result['items']),
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
        ];
    }
}
