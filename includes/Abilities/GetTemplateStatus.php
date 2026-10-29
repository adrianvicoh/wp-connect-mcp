<?php

namespace WpConnectMcp\Abilities;

use WP_Query;
use WpConnectMcp\Plugin;

defined('ABSPATH') || exit;

final class GetTemplateStatus
{
    public const NAME = 'wp-connect-mcp/get-template-status';

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Comprobar plantillas del blog',
            'description' => 'Comprueba si Elementor Pro tiene plantillas publicadas aplicables a la entrada individual y al archivo del blog. No devuelve su contenido.',
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
        ]);
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
            'single_post' => self::findTemplate(['single-post', 'single'], ['singular/post', 'single/post', 'general']),
            'archive' => self::findTemplate(['archive'], ['archive/post', 'post_archive', 'archive/posts', 'general']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function templateSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'exists' => ['type' => 'boolean'],
                'id' => ['type' => ['integer', 'null']],
                'title' => ['type' => ['string', 'null']],
                'conditions' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['exists', 'id', 'title', 'conditions'],
        ];
    }

    /**
     * @param array<string> $types
     * @param array<string> $conditionFragments
     * @return array<string, mixed>
     */
    private static function findTemplate(array $types, array $conditionFragments): array
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

        foreach ($query->posts as $post) {
            $conditions = self::conditions((int) $post->ID);
            if (!self::matches($conditions, $conditionFragments)) {
                continue;
            }

            return [
                'exists' => true,
                'id' => (int) $post->ID,
                'title' => $post->post_title,
                'conditions' => $conditions,
            ];
        }

        return ['exists' => false, 'id' => null, 'title' => null, 'conditions' => []];
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
     * @param array<string> $conditions
     * @param array<string> $fragments
     */
    private static function matches(array $conditions, array $fragments): bool
    {
        foreach ($conditions as $condition) {
            if (0 !== strpos($condition, 'include/')) {
                continue;
            }

            foreach ($fragments as $fragment) {
                if (false !== strpos($condition, $fragment)) {
                    return true;
                }
            }
        }

        return false;
    }
}
