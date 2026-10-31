<?php

namespace WpConnectMcp\Abilities;

use WP_Query;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;

defined('ABSPATH') || exit;

final class GetTemplateStatus
{
    public const NAME = 'wp-connect-mcp/get-template-status';

    /**
     * Condiciones de Elementor Pro (sin include/ ni exclude/) que cubren las entradas del blog.
     * Las de archivo admiten un ID detrás (archive/category/5): cubren el blog igualmente.
     */
    private const SINGLE_COVERS = ['general', 'singular', 'singular/post'];
    private const ARCHIVE_COVERS = ['general', 'archive', 'archive/post_archive', 'archive/category', 'archive/post_tag'];

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Comprobar plantillas del blog',
            'description' => 'Comprueba si Elementor Pro tiene plantillas publicadas que se aplican a la entrada individual y al archivo del blog (categorías incluidas). Devuelve también las demás plantillas de cada tipo con sus condiciones y si cubren el blog. No devuelve su contenido.',
            'category' => Plugin::CATEGORY,
            'input_schema' => ['type' => 'object', 'default' => [], 'properties' => [], 'additionalProperties' => false],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'elementor_active' => ['type' => 'boolean'],
                    'elementor_pro_active' => ['type' => 'boolean'],
                    'single_post' => self::templateSchema(),
                    'archive' => self::templateSchema(),
                ],
                'required' => ['elementor_active', 'elementor_pro_active', 'single_post', 'archive'],
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
     * @return array<string, mixed>
     */
    public static function execute(): array
    {
        return [
            'elementor_active' => defined('ELEMENTOR_VERSION') || did_action('elementor/loaded') > 0,
            'elementor_pro_active' => defined('ELEMENTOR_PRO_VERSION'),
            'single_post' => self::findTemplate(['single-post', 'single'], self::SINGLE_COVERS, false),
            'archive' => self::findTemplate(['archive'], self::ARCHIVE_COVERS, true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function templateSchema(): array
    {
        $conditions = ['type' => 'array', 'items' => ['type' => 'string']];

        return [
            'type' => 'object',
            'properties' => [
                'exists' => ['type' => 'boolean', 'description' => 'Hay una plantilla publicada que se aplica al blog.'],
                'id' => ['type' => ['integer', 'null']],
                'title' => ['type' => ['string', 'null']],
                'conditions' => $conditions,
                'templates' => [
                    'type' => 'array',
                    'description' => 'Todas las plantillas publicadas de este tipo, cubran o no el blog.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'conditions' => $conditions,
                            'covers_blog' => ['type' => 'boolean'],
                        ],
                        'required' => ['id', 'title', 'conditions', 'covers_blog'],
                    ],
                ],
            ],
            'required' => ['exists', 'id', 'title', 'conditions', 'templates'],
        ];
    }

    /**
     * @param array<string> $types
     * @param array<string> $covers
     * @return array<string, mixed>
     */
    private static function findTemplate(array $types, array $covers, bool $allowId): array
    {
        $query = new WP_Query([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
            'meta_query' => [[
                'key' => '_elementor_template_type',
                'value' => $types,
                'compare' => 'IN',
            ]],
        ]);

        $result = ['exists' => false, 'id' => null, 'title' => null, 'conditions' => [], 'templates' => []];

        foreach ($query->posts as $post) {
            $conditions = self::conditions((int) $post->ID);
            $template = [
                'id' => (int) $post->ID,
                'title' => $post->post_title,
                'conditions' => $conditions,
                'covers_blog' => self::coversBlog($conditions, $covers, $allowId),
            ];
            $result['templates'][] = $template;

            if ($template['covers_blog'] && !$result['exists']) {
                $result = ['exists' => true] + $template + $result;
                unset($result['covers_blog']);
            }
        }

        return $result;
    }

    /**
     * @return array<string>
     */
    private static function conditions(int $postId): array
    {
        $raw = get_post_meta($postId, '_elementor_conditions', true);
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $raw), 'strlen'));
    }

    /**
     * Cubre el blog si alguna inclusión está en $covers y ninguna exclusión amplia (sin ID) la anula.
     *
     * @param array<string> $conditions
     * @param array<string> $covers
     */
    private static function coversBlog(array $conditions, array $covers, bool $allowId): bool
    {
        $included = false;

        foreach ($conditions as $condition) {
            $parts = explode('/', $condition);
            $mode = array_shift($parts);
            $name = implode('/', $parts);

            if ('exclude' === $mode && in_array($name, $covers, true)) {
                return false;
            }

            if ('include' !== $mode) {
                continue;
            }

            if ($allowId && count($parts) > 2 && ctype_digit((string) end($parts))) {
                $name = implode('/', array_slice($parts, 0, -1));
            }

            if (in_array($name, $covers, true)) {
                $included = true;
            }
        }

        return $included;
    }
}
