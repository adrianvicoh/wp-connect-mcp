<?php

namespace Wwwarcelona\WpConnect\Abilities;

use WP_Error;
use Wwwarcelona\WpConnect\Plugin;
use Wwwarcelona\WpConnect\Support\PostAccess;
use Wwwarcelona\WpConnect\Support\PostFormatter;
use Wwwarcelona\WpConnect\Support\PostInput;

defined('ABSPATH') || exit;

final class UpdatePost
{
    public const NAME = 'wwwarcelona/update-post';

    public static function register(): void
    {
        wp_register_ability(self::NAME, [
            'label' => 'Editar entrada',
            'description' => 'Modifica solo los campos indicados de una entrada (post) creada por este usuario. Puede pasarla a borrador, pendiente, programada o publicada (si el usuario puede publicar). Publicar una programada la publica ahora.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la entrada.'],
                ] + PostInput::schemaProperties(),
                'required' => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => PostFormatter::fullSchema(),
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [PostAccess::class, 'canEditOwn'],
            'meta' => [
                'annotations' => [
                    'readonly' => false,
                    'destructive' => true,
                    'idempotent' => true,
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function execute(array $input)
    {
        $post = PostAccess::find($input['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        $data = PostInput::toPostData($input, $post);
        if (is_wp_error($data)) {
            return $data;
        }

        if (!$data) {
            return new WP_Error('wwwarcelona_nothing_to_update', 'No se ha indicado ningún campo que cambiar.', ['status' => 400]);
        }

        $data['ID'] = $post->ID;

        $result = wp_update_post(wp_slash($data), true);
        if (is_wp_error($result)) {
            return $result;
        }

        return PostFormatter::full(get_post($post->ID));
    }
}
