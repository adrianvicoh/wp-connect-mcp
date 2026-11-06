<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\ContentReplace;
use WpConnectMcp\Support\PostAccess;
use WpConnectMcp\Support\PostFormatter;
use WpConnectMcp\Support\PostInput;
use WpConnectMcp\Support\PostExtras;

defined('ABSPATH') || exit;

final class UpdatePost
{
    public const NAME = 'wp-connect-mcp/update-post';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Editar entrada',
            'description' => 'Modifica solo los campos indicados de una entrada (post) creada por este usuario. Puede pasarla a borrador, pendiente, programada o publicada (si el usuario puede publicar). Publicar una programada la publica ahora. Para cambiar solo unos fragmentos del contenido (por ejemplo, añadir enlaces), usar replace en lugar de reenviar content. Para programar varias entradas, usar schedule-posts.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la entrada.'],
                ] + PostInput::schemaProperties() + [
                    'replace' => ContentReplace::schema(),
                    'return_content' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Devolver también el contenido guardado. Por defecto solo se devuelve un resumen (longitud, MD5, encabezados y enlaces).',
                    ],
                ],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => PostFormatter::savedSchema(),
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [PostAccess::class, 'canEditOwn'],
            'meta' => [
                'annotations' => [
                    'readonly' => false,
                    'destructive' => true,
                    'idempotent' => true,
                ],
            ],
        ]));
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

        if (array_key_exists('replace', $input)) {
            if (array_key_exists('content', $input)) {
                return new WP_Error('wp_connect_mcp_invalid_replace', 'replace no se admite junto a content: usa uno u otro.', ['status' => 400]);
            }

            $content = ContentReplace::apply($post->post_content, $input['replace']);
            if (is_wp_error($content)) {
                return $content;
            }

            $input['content'] = $content;
            unset($input['replace']);
        }

        $extras = PostExtras::validate($input);
        if (is_wp_error($extras)) {
            return $extras;
        }

        $data = PostInput::toPostData($input, $post);
        if (is_wp_error($data)) {
            return $data;
        }

        if (!$data && !$extras) {
            return new WP_Error('wp_connect_mcp_nothing_to_update', 'No se ha indicado ningún campo que cambiar.', ['status' => 400]);
        }

        if ($data) {
            $data['ID'] = $post->ID;

            $result = wp_update_post(wp_slash($data), true);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        PostExtras::apply($post->ID, $extras);

        return PostFormatter::saved(get_post($post->ID), !empty($input['return_content']));
    }
}
