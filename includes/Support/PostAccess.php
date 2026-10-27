<?php

namespace Wwwarcelona\WpConnect\Support;

use WP_Error;
use WP_Post;

defined('ABSPATH') || exit;

final class PostAccess
{
    /**
     * @param mixed $id
     * @return WP_Post|WP_Error
     */
    public static function find($id)
    {
        $post = get_post((int) $id);

        if (!$post instanceof WP_Post || 'trash' === $post->post_status) {
            return new WP_Error('wwwarcelona_not_found', 'No existe ninguna entrada con ese ID.', ['status' => 404]);
        }

        if ('post' !== $post->post_type) {
            return new WP_Error('wwwarcelona_not_a_post', 'El ID no corresponde a una entrada (post).', ['status' => 400]);
        }

        return $post;
    }

    /**
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    public static function canEdit($input = null)
    {
        // Antes de buscar la entrada, para no revelar qué IDs existen a quien no puede editar.
        if (!current_user_can('edit_posts')) {
            return false;
        }

        $post = self::find(((array) $input)['id'] ?? 0);

        if (is_wp_error($post)) {
            return $post;
        }

        return current_user_can('edit_post', $post->ID);
    }
}
