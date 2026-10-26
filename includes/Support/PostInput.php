<?php

namespace Wwwarcelona\WpConnect\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use WP_Error;
use WP_Post;

defined('ABSPATH') || exit;

final class PostInput
{
    /**
     * Estados que el agente puede asignar. Publicar queda siempre en manos de una persona.
     */
    public const STATUSES = ['draft', 'pending', 'future'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schemaProperties(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'description' => 'Título de la entrada (texto plano).',
            ],
            'content' => [
                'type' => 'string',
                'description' => 'Contenido en HTML o marcado de bloques. Se filtra con las etiquetas permitidas en entradas.',
            ],
            'excerpt' => [
                'type' => 'string',
                'description' => 'Extracto (texto plano).',
            ],
            'status' => [
                'type' => 'string',
                'enum' => self::STATUSES,
                'description' => 'draft (borrador), pending (pendiente de revisión) o future (programada; requiere scheduled_date). No se puede publicar directamente.',
            ],
            'scheduled_date' => [
                'type' => 'string',
                'format' => 'date-time',
                'description' => 'Fecha de publicación programada (ISO 8601, futura). Sin zona horaria se usa la del sitio. Implica status future.',
            ],
            'categories' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'description' => 'IDs de categorías existentes. Sustituye las actuales.',
            ],
            'tags' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Nombres de etiquetas; las que no existan se crean. Sustituye las actuales.',
            ],
        ];
    }

    /**
     * Convierte la entrada del agente en datos para wp_insert_post()/wp_update_post().
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    public static function toPostData(array $input, ?WP_Post $current = null)
    {
        $data = [];

        if (array_key_exists('title', $input)) {
            $data['post_title'] = sanitize_text_field($input['title']);
        }

        if (array_key_exists('content', $input)) {
            $data['post_content'] = wp_kses_post($input['content']);
        }

        if (array_key_exists('excerpt', $input)) {
            $data['post_excerpt'] = sanitize_textarea_field($input['excerpt']);
        }

        $schedule = self::schedule($input, $current);
        if (is_wp_error($schedule)) {
            return $schedule;
        }
        $data += $schedule;

        if (array_key_exists('categories', $input)) {
            $categories = self::categories((array) $input['categories']);
            if (is_wp_error($categories)) {
                return $categories;
            }
            $data['post_category'] = $categories;
        }

        if (array_key_exists('tags', $input)) {
            $tags = self::tags((array) $input['tags']);
            if (is_wp_error($tags)) {
                return $tags;
            }
            $data['tags_input'] = $tags;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|WP_Error
     */
    private static function schedule(array $input, ?WP_Post $current)
    {
        $status = $input['status'] ?? null;
        $date = $input['scheduled_date'] ?? null;

        if (null === $status && null === $date) {
            return $current ? [] : ['post_status' => 'draft'];
        }

        if ($current && !in_array($current->post_status, self::STATUSES, true)) {
            return new WP_Error(
                'wwwarcelona_status_locked',
                sprintf('La entrada está en estado "%s": se puede editar su contenido, pero no su estado ni su fecha.', $current->post_status),
                ['status' => 403]
            );
        }

        if (null === $status) {
            $status = 'future';
        }

        if (!in_array($status, self::STATUSES, true)) {
            return new WP_Error(
                'wwwarcelona_invalid_status',
                'Estado no permitido. Usa draft, pending o future; publicar lo hace una persona.',
                ['status' => 400]
            );
        }

        if ('future' !== $status) {
            if (null !== $date) {
                return new WP_Error('wwwarcelona_invalid_schedule', 'scheduled_date solo se admite con status future.', ['status' => 400]);
            }

            return ['post_status' => $status];
        }

        $can_schedule = $current ? current_user_can('publish_post', $current->ID) : current_user_can('publish_posts');
        if (!$can_schedule) {
            return new WP_Error('wwwarcelona_cannot_schedule', 'Este usuario no puede programar entradas.', ['status' => 403]);
        }

        if (null === $date) {
            if ($current && 'future' === $current->post_status) {
                return ['post_status' => 'future'];
            }

            return new WP_Error('wwwarcelona_missing_schedule', 'Para programar hace falta scheduled_date.', ['status' => 400]);
        }

        try {
            $when = new DateTimeImmutable((string) $date, wp_timezone());
        } catch (Exception $e) {
            return new WP_Error('wwwarcelona_invalid_schedule', 'scheduled_date no es una fecha válida.', ['status' => 400]);
        }

        if ($when->getTimestamp() <= time()) {
            return new WP_Error('wwwarcelona_schedule_in_past', 'scheduled_date debe ser una fecha futura.', ['status' => 400]);
        }

        return [
            'post_status' => 'future',
            'post_date' => $when->setTimezone(wp_timezone())->format('Y-m-d H:i:s'),
            'post_date_gmt' => $when->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'edit_date' => true,
        ];
    }

    /**
     * @param array<mixed> $ids
     * @return array<int>|WP_Error
     */
    private static function categories(array $ids)
    {
        if (!self::canAssign('category')) {
            return new WP_Error('wwwarcelona_cannot_assign_terms', 'Este usuario no puede asignar categorías.', ['status' => 403]);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        foreach ($ids as $id) {
            if (!term_exists($id, 'category')) {
                return new WP_Error(
                    'wwwarcelona_invalid_category',
                    sprintf('No existe ninguna categoría con ID %d.', $id),
                    ['status' => 400]
                );
            }
        }

        return $ids;
    }

    /**
     * @param array<mixed> $names
     * @return array<string>|WP_Error
     */
    private static function tags(array $names)
    {
        if (!self::canAssign('post_tag')) {
            return new WP_Error('wwwarcelona_cannot_assign_terms', 'Este usuario no puede asignar etiquetas.', ['status' => 403]);
        }

        return array_values(array_unique(array_filter(array_map(static function ($name): string {
            return sanitize_text_field((string) $name);
        }, $names), 'strlen')));
    }

    private static function canAssign(string $taxonomy): bool
    {
        $object = get_taxonomy($taxonomy);

        return $object && current_user_can($object->cap->assign_terms);
    }
}
