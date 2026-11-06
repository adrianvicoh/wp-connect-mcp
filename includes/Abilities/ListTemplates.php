<?php

namespace WpConnectMcp\Abilities;

use WP_Post;
use WP_Query;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\SavedTemplates;

defined('ABSPATH') || exit;

final class ListTemplates
{
    public const NAME = 'wp-connect-mcp/list-templates';
    private const MAX_PER_PAGE = 100;

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Listar plantillas guardadas de Elementor',
            'description' => 'Lista las plantillas guardadas de Elementor publicadas de tipo sección, contenedor o página (id, título, tipo, fecha y shortcode), sin su contenido. Para insertar una en una entrada propia (por ejemplo, un CTA), poner su shortcode en el contenido dentro de un bloque de shortcode: <!-- wp:shortcode -->[elementor-template id="N"]<!-- /wp:shortcode -->.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'default' => [],
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Texto a buscar en el título.'],
                    'type' => ['type' => 'string', 'enum' => SavedTemplates::TYPES, 'description' => 'Solo plantillas de este tipo.'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_PER_PAGE, 'description' => 'Resultados por página (por defecto 50).'],
                    'page' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Página (por defecto 1).'],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'elementor_active' => ['type' => 'boolean'],
                    'templates' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'title' => ['type' => 'string'],
                                'type' => ['type' => 'string'],
                                'modified' => ['type' => ['string', 'null']],
                                'shortcode' => ['type' => 'string'],
                            ],
                            'required' => ['id', 'title', 'type', 'shortcode'],
                        ],
                    ],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ],
                'required' => ['elementor_active', 'templates', 'total', 'total_pages'],
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
        $types = isset($input['type']) && in_array($input['type'], SavedTemplates::TYPES, true)
            ? [$input['type']]
            : SavedTemplates::TYPES;
        $perPage = max(1, min(self::MAX_PER_PAGE, (int) ($input['per_page'] ?? 50)));

        // post_type directo: elementor_library no es público y WP_Query lo admite igual desde el servidor.
        $query = new WP_Query([
            'post_type' => SavedTemplates::POST_TYPE,
            'post_status' => 'publish',
            'has_password' => false,
            's' => isset($input['search']) ? sanitize_text_field((string) $input['search']) : '',
            'search_columns' => ['post_title'],
            'meta_query' => [['key' => '_elementor_template_type', 'value' => $types, 'compare' => 'IN']],
            'orderby' => 'title',
            'order' => 'ASC',
            'posts_per_page' => $perPage,
            'paged' => max(1, (int) ($input['page'] ?? 1)),
            'ignore_sticky_posts' => true,
            'no_found_rows' => false,
            'suppress_filters' => false,
        ]);

        return [
            'elementor_active' => defined('ELEMENTOR_VERSION') || did_action('elementor/loaded') > 0,
            'templates' => array_values(array_map(static function (WP_Post $post): array {
                $modified = get_post_datetime($post, 'modified');

                return [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => SavedTemplates::typeOf($post->ID),
                    'modified' => $modified ? $modified->format(DATE_ATOM) : null,
                    'shortcode' => SavedTemplates::shortcode($post->ID),
                ];
            }, array_filter($query->posts, static function ($post): bool {
                return $post instanceof WP_Post;
            }))),
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
        ];
    }
}
