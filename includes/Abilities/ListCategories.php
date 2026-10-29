<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WpConnectMcp\Plugin;

defined('ABSPATH') || exit;

final class ListCategories
{
    public const NAME = 'wp-connect-mcp/list-categories';
    private const MAX_PER_PAGE = 100;

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Listar categorías',
            'description' => 'Lista las categorías de entradas con sus IDs para poder asignarlas al crear o editar.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'default' => [],
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Texto a buscar en el nombre.'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_PER_PAGE, 'description' => 'Resultados por página (por defecto 100).'],
                    'page' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Página (por defecto 1).'],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'slug' => ['type' => 'string'],
                        'parent' => ['type' => 'integer'],
                        'count' => ['type' => 'integer'],
                        'link' => ['type' => 'string'],
                    ],
                    'required' => ['id', 'name', 'slug', 'parent', 'count', 'link'],
                ],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => ['annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
        ]);
    }

    public static function permission(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * @param array<string, mixed>|null $input
     * @return array<int, array<string, mixed>>|WP_Error
     */
    public static function execute($input = null)
    {
        $input = (array) $input;
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($input['per_page'] ?? self::MAX_PER_PAGE)));
        $page = max(1, (int) ($input['page'] ?? 1));
        $args = [
            'taxonomy' => 'category',
            'hide_empty' => false,
            'number' => $perPage,
            'offset' => ($page - 1) * $perPage,
            'orderby' => 'name',
            'order' => 'ASC',
        ];

        if (!empty($input['search'])) {
            $args['search'] = sanitize_text_field((string) $input['search']);
        }

        $terms = get_terms($args);
        if (is_wp_error($terms)) {
            return $terms;
        }

        return array_map(static function ($term): array {
            $link = get_category_link($term->term_id);

            return [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => (int) $term->parent,
                'count' => (int) $term->count,
                'link' => is_wp_error($link) ? '' : (string) $link,
            ];
        }, $terms);
    }
}
