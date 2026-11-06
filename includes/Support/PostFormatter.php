<?php

namespace WpConnectMcp\Support;

use WP_Post;

defined('ABSPATH') || exit;

final class PostFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'date' => self::date($post, 'date'),
            'modified' => self::date($post, 'modified'),
            'link' => (string) get_permalink($post),
            'edit_link' => (string) get_edit_post_link($post, 'raw'),
            'categories' => array_map('intval', wp_get_post_categories($post->ID, ['fields' => 'ids'])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function full(WP_Post $post): array
    {
        return self::summary($post) + [
            'slug' => $post->post_name,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'author' => (int) $post->post_author,
            'tags' => array_values(wp_get_post_tags($post->ID, ['fields' => 'names'])),
            'seo_title' => (string) get_post_meta($post->ID, '_yoast_wpseo_title', true),
            'seo_description' => (string) get_post_meta($post->ID, '_yoast_wpseo_metadesc', true),
            'focus_keyword' => (string) get_post_meta($post->ID, '_yoast_wpseo_focuskw', true),
            'featured_image' => get_post_thumbnail_id($post->ID) ?: null,
            'reviewed_by' => PostExtras::reviewedBy($post->ID),
        ];
    }

    /**
     * Respuesta de create-post y update-post: sin el contenido (salvo que se pida), con un resumen
     * para comprobar que ha llegado entero sin volver a enviarlo.
     *
     * @return array<string, mixed>
     */
    public static function saved(WP_Post $post, bool $withContent = false): array
    {
        $data = self::full($post);
        $content = $post->post_content;

        if (!$withContent) {
            unset($data['content']);
        }

        return $data + [
            'content_length' => function_exists('mb_strlen') ? mb_strlen($content) : strlen($content),
            'content_hash' => md5($content),
            'headings' => (int) preg_match_all('/<h[1-6][\s>]/i', $content),
            'links' => (int) preg_match_all('/<a\s[^>]*href=/i', $content),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function summarySchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'date' => ['type' => ['string', 'null'], 'description' => 'Fecha de publicación (o programada), ISO 8601.'],
                'modified' => ['type' => ['string', 'null']],
                'link' => ['type' => 'string'],
                'edit_link' => ['type' => 'string'],
                'categories' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'required' => ['id', 'title', 'status'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fullSchema(): array
    {
        $schema = self::summarySchema();
        $schema['properties'] += [
            'slug' => ['type' => 'string'],
            'content' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'author' => ['type' => 'integer'],
            'categories' => ['type' => 'array', 'items' => ['type' => 'integer']],
            'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
            'seo_title' => ['type' => 'string'],
            'seo_description' => ['type' => 'string'],
            'focus_keyword' => ['type' => 'string'],
            'featured_image' => ['type' => ['integer', 'null']],
            'reviewed_by' => ['type' => 'string', 'description' => 'Quién firma o revisa la entrada (vacío si no consta).'],
        ];
        $schema['required'][] = 'content';

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public static function savedSchema(): array
    {
        $schema = self::fullSchema();
        $schema['required'] = array_values(array_diff($schema['required'], ['content']));
        $schema['properties']['content']['description'] = 'Solo con return_content: true.';
        $schema['properties'] += [
            'content_length' => ['type' => 'integer', 'description' => 'Caracteres del contenido guardado.'],
            'content_hash' => ['type' => 'string', 'description' => 'MD5 del contenido guardado: si coincide con el del enviado, ha llegado entero.'],
            'headings' => ['type' => 'integer', 'description' => 'Número de encabezados (h1 a h6).'],
            'links' => ['type' => 'integer', 'description' => 'Número de enlaces.'],
        ];
        $schema['required'] = array_merge($schema['required'], ['content_length', 'content_hash', 'headings', 'links']);

        return $schema;
    }

    private static function date(WP_Post $post, string $field): ?string
    {
        $date = get_post_datetime($post, $field);

        return $date ? $date->format(DATE_ATOM) : null;
    }
}
