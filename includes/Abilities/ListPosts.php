<?php

namespace Wwwarcelona\WpConnect\Abilities;

use WP_Query;
use Wwwarcelona\WpConnect\Plugin;
use Wwwarcelona\WpConnect\Support\PostFormatter;

defined('ABSPATH') || exit;

final class ListPosts
{
    public const NAME = 'wwwarcelona/list-posts';

    private const LISTABLE_STATUSES = ['draft', 'pending', 'future', 'publish'];
    private const MAX_PER_PAGE = 50;

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Listar entradas',
            'description' => 'Lista entradas (posts) que el usuario puede editar, ordenadas por última modificación. Admite búsqueda, filtro por estado y paginación.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'default' => [],
                'properties' => [
                    'search' => [
                        'type' => 'string',
                        'description' => 'Texto a buscar en título y contenido.',
                    ],
                    'status' => [
                        'type' => 'array',
                        'items' => ['type' => 'string', 'enum' => self::LISTABLE_STATUSES],
                        'description' => 'Estados a incluir. Por defecto, todos.',
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => self::MAX_PER_PAGE,
                        'description' => 'Resultados por página (por defecto 20).',
                    ],
                    'page' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'description' => 'Página (por defecto 1).',
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'posts' => ['type' => 'array', 'items' => PostFormatter::summarySchema()],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ],
                'required' => ['posts', 'total', 'total_pages'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => [
                'annotations' => [
                    'readonly' => true,
                    'destructive' => false,
                    'idempotent' => true,
                ],
            ],
        ]);
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

        $args = [
            'post_type' => 'post',
            'post_status' => !empty($input['status']) ? array_values(array_intersect($input['status'], self::LISTABLE_STATUSES)) : self::LISTABLE_STATUSES,
            'posts_per_page' => min(self::MAX_PER_PAGE, max(1, (int) ($input['per_page'] ?? 20))),
            'paged' => max(1, (int) ($input['page'] ?? 1)),
            'orderby' => 'modified',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
        ];

        if (!empty($input['search'])) {
            $args['s'] = sanitize_text_field($input['search']);
        }

        if (!current_user_can('edit_others_posts')) {
            $args['author'] = get_current_user_id();
        }

        $query = new WP_Query($args);

        return [
            'posts' => array_map([PostFormatter::class, 'summary'], $query->posts),
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
        ];
    }
}
