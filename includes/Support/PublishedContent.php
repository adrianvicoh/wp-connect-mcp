<?php

namespace WpConnectMcp\Support;

use WP_Error;
use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

/**
 * Lectura del contenido publicado del sitio (paquete «Leer el sitio»): solo entradas y páginas
 * publicadas y sin contraseña, de cualquier autor. Es lo mismo que ve un visitante.
 */
final class PublishedContent
{
    public const TYPES = ['post', 'page'];
    public const MAX_PER_PAGE = 100;
    private const EXCERPT_WORDS = 40;

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $extra Argumentos adicionales de WP_Query.
     * @return array{items: list<WP_Post>, total: int, total_pages: int}
     */
    public static function query(string $type, array $input, array $extra = []): array
    {
        $args = [
            'post_type' => $type,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => min(self::MAX_PER_PAGE, max(1, (int) ($input['per_page'] ?? 20))),
            'paged' => max(1, (int) ($input['page'] ?? 1)),
            'orderby' => 'post' === $type ? 'date' : 'menu_order title',
            'order' => 'post' === $type ? 'DESC' : 'ASC',
            'ignore_sticky_posts' => true,
            'no_found_rows' => false,
        ] + $extra;

        if (!empty($input['search'])) {
            $args['s'] = sanitize_text_field((string) $input['search']);
        }

        $query = new WP_Query($args);

        return [
            'items' => array_values(array_filter($query->posts, [self::class, 'isPublic'])),
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
        ];
    }

    /**
     * @param mixed $id
     * @return WP_Post|WP_Error
     */
    public static function find($id)
    {
        $post = get_post((int) $id);

        // El mismo mensaje para todo lo que no es público, para no revelar qué IDs existen.
        if (!$post instanceof WP_Post || !self::isPublic($post)) {
            return new WP_Error('wp_connect_mcp_not_found', 'No existe ningún contenido publicado (entrada o página) con ese ID.', ['status' => 404]);
        }

        return $post;
    }

    public static function isPublic(WP_Post $post): bool
    {
        return in_array($post->post_type, self::TYPES, true)
            && 'publish' === $post->post_status
            && '' === (string) $post->post_password;
    }

    public static function excerpt(WP_Post $post): string
    {
        if ('' !== trim($post->post_excerpt)) {
            return trim(wp_strip_all_tags($post->post_excerpt));
        }

        return wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), self::EXCERPT_WORDS);
    }

    public static function modified(WP_Post $post): ?string
    {
        $date = get_post_datetime($post, 'modified');

        return $date ? $date->format(DATE_ATOM) : null;
    }
}
