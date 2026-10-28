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

    /**
     * Como canEdit(), pero solo para entradas cuyo autor es el usuario actual, aunque su rol permita editar las de otros.
     *
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    public static function canEditOwn($input = null)
    {
        return self::canOwn($input, 'edit_posts', 'edit_post');
    }

    /**
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    public static function canDeleteOwn($input = null)
    {
        return self::canOwn($input, 'delete_posts', 'delete_post');
    }

    /**
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    private static function canOwn($input, string $general_cap, string $post_cap)
    {
        // Antes de buscar la entrada, para no revelar qué IDs existen a quien no tiene la capacidad general.
        if (!current_user_can($general_cap)) {
            return false;
        }

        $post = self::find(((array) $input)['id'] ?? 0);

        if (is_wp_error($post)) {
            return $post;
        }

        if ((int) $post->post_author !== get_current_user_id()) {
            return new WP_Error(
                'wwwarcelona_not_own',
                'Solo se pueden modificar o borrar las entradas creadas por este usuario.',
                ['status' => 403]
            );
        }

        return current_user_can($post_cap, $post->ID);
    }
}
