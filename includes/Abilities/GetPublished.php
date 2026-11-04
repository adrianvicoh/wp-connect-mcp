<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\ContentOutline;
use WpConnectMcp\Support\PublishedContent;

defined('ABSPATH') || exit;

/**
 * Lee una entrada o página publicada de cualquier autor. Por defecto devuelve solo su esquema
 * (encabezados, enlaces y palabras); el contenido entero, con include_content.
 */
final class GetPublished
{
    public const NAME = 'wp-connect-mcp/get-published';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Leer contenido publicado',
            'description' => 'Lee una entrada o página publicada de cualquier autor. Por defecto devuelve solo su esquema: encabezados, enlaces (con su texto y si son internos) y número de palabras, suficiente para decidir el enlazado. include_content: true añade el contenido completo; pedirlo solo si hace falta buscar un ancla en el texto.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la entrada o página publicada.'],
                    'include_content' => ['type' => 'boolean', 'description' => 'Devolver también el contenido completo (por defecto, no).'],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'type' => ['type' => 'string', 'enum' => PublishedContent::TYPES],
                    'title' => ['type' => 'string'],
                    'link' => ['type' => 'string'],
                    'parent' => ['type' => 'integer'],
                    'modified' => ['type' => ['string', 'null']],
                    'categories' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Solo en entradas.'],
                    'excerpt' => ['type' => 'string'],
                    'word_count' => ['type' => 'integer'],
                    'headings' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => ['level' => ['type' => 'integer'], 'text' => ['type' => 'string']],
                            'required' => ['level', 'text'],
                        ],
                    ],
                    'links' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'href' => ['type' => 'string'],
                                'text' => ['type' => 'string'],
                                'internal' => ['type' => 'boolean'],
                            ],
                            'required' => ['href', 'text', 'internal'],
                        ],
                    ],
                    'built_with_elementor' => ['type' => 'boolean', 'description' => 'Si es true, el contenido es la copia en HTML que guarda Elementor y puede no reflejar el último cambio.'],
                    'content' => ['type' => 'string', 'description' => 'Solo con include_content: true.'],
                ],
                'required' => ['id', 'type', 'title', 'link', 'excerpt', 'word_count', 'headings', 'links', 'built_with_elementor'],
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
     * @return array<string, mixed>|WP_Error
     */
    public static function execute($input = null)
    {
        $input = (array) $input;
        $post = PublishedContent::find($input['id'] ?? 0);

        if (is_wp_error($post)) {
            return $post;
        }

        $data = [
            'id' => $post->ID,
            'type' => $post->post_type,
            'title' => $post->post_title,
            'link' => (string) get_permalink($post),
            'parent' => (int) $post->post_parent,
            'modified' => PublishedContent::modified($post),
        ];

        if ('post' === $post->post_type) {
            $data['categories'] = array_map('intval', wp_get_post_categories($post->ID, ['fields' => 'ids']));
        }

        $data += ['excerpt' => PublishedContent::excerpt($post)]
            + ContentOutline::of($post->post_content)
            + ['built_with_elementor' => 'builder' === get_post_meta($post->ID, '_elementor_edit_mode', true)];

        if (!empty($input['include_content'])) {
            $data['content'] = $post->post_content;
        }

        return $data;
    }
}
