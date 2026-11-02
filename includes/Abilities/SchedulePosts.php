<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;
use WpConnectMcp\Support\PostAccess;
use WpConnectMcp\Support\PostFormatter;
use WpConnectMcp\Support\PostInput;

defined('ABSPATH') || exit;

/**
 * Programa varias entradas propias en una sola llamada. Evita las ráfagas de update-post, que pueden
 * hacer saltar el cortafuegos del sitio (CrowdSec bloqueó la IP tras 10 llamadas en paralelo).
 */
final class SchedulePosts
{
    public const NAME = 'wp-connect-mcp/schedule-posts';
    public const MAX_POSTS = 20;

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Programar entradas',
            'description' => sprintf('Programa a la vez hasta %d entradas (post) creadas por este usuario, cada una con su fecha. Si alguna no se puede programar, no se cambia ninguna. Usar en lugar de varias llamadas a update-post.', self::MAX_POSTS),
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'posts' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'maxItems' => self::MAX_POSTS,
                        'description' => 'Entradas que programar, sin repetir ninguna.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID de la entrada.'],
                                'scheduled_date' => PostInput::schemaProperties()['scheduled_date'],
                            ],
                            'required' => ['id', 'scheduled_date'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['posts'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'posts' => ['type' => 'array', 'items' => PostFormatter::summarySchema()],
                    'failed' => [
                        'type' => 'array',
                        'description' => 'Entradas que WordPress no pudo guardar después de validarlas todas. Normalmente vacía.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'error' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'required' => ['posts', 'failed'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'canSchedule'],
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
     * @param array<string, mixed>|null $input
     * @return bool|WP_Error
     */
    public static function canSchedule($input = null)
    {
        // Antes de buscar las entradas, para no revelar qué IDs existen a quien no puede programar.
        if (!current_user_can('edit_posts') || !current_user_can('publish_posts')) {
            return false;
        }

        // El adapter comprueba permisos antes de validar el esquema: no buscar entradas de una lista demasiado larga.
        $ids = self::ids($input);
        if (count($ids) > self::MAX_POSTS) {
            return new WP_Error(
                'wp_connect_mcp_too_many_posts',
                sprintf('Como máximo %d entradas por llamada.', self::MAX_POSTS),
                ['status' => 400]
            );
        }

        foreach ($ids as $id) {
            $allowed = PostAccess::canEditOwn(['id' => $id]);

            if (is_wp_error($allowed)) {
                return new WP_Error(
                    $allowed->get_error_code(),
                    sprintf('Entrada %d: %s', $id, $allowed->get_error_message()),
                    $allowed->get_error_data()
                );
            }

            if (true !== $allowed) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function execute(array $input)
    {
        $updates = [];
        $errors = [];

        // Primero se validan todas, para no dejar el lote a medias.
        foreach ((array) $input['posts'] as $item) {
            $id = (int) $item['id'];

            if (isset($updates[$id])) {
                $errors[] = sprintf('%d: repetida en la lista', $id);
                continue;
            }

            $post = PostAccess::find($id);
            if (is_wp_error($post)) {
                $errors[] = sprintf('%d: %s', $id, rtrim($post->get_error_message(), '.'));
                continue;
            }

            $data = PostInput::toPostData(['scheduled_date' => $item['scheduled_date']], $post);
            if (is_wp_error($data)) {
                $errors[] = sprintf('%d: %s', $id, rtrim($data->get_error_message(), '.'));
                continue;
            }

            $updates[$id] = ['ID' => $id] + $data;
        }

        if ($errors) {
            return new WP_Error(
                'wp_connect_mcp_schedule_rejected',
                'No se ha programado ninguna entrada. ' . implode('; ', $errors) . '.',
                ['status' => 400]
            );
        }

        $scheduled = [];
        $failed = [];

        foreach ($updates as $id => $data) {
            $result = wp_update_post(wp_slash($data), true);

            if (is_wp_error($result)) {
                $failed[] = ['id' => $id, 'error' => $result->get_error_message()];
                continue;
            }

            $scheduled[] = PostFormatter::summary(get_post($id));
        }

        return ['posts' => $scheduled, 'failed' => $failed];
    }

    /**
     * @param array<string, mixed>|null $input
     * @return list<int>
     */
    public static function ids($input): array
    {
        $ids = [];

        foreach ((array) (((array) $input)['posts'] ?? []) as $item) {
            if (is_array($item) && isset($item['id']) && is_scalar($item['id'])) {
                $ids[] = (int) $item['id'];
            }
        }

        return $ids;
    }
}
