<?php

namespace WpConnectMcp\Support;

use WP_Error;

defined('ABSPATH') || exit;

/**
 * Sustituciones parciales del contenido guardado (update-post, campo replace), para no reenviar
 * el contenido entero. Todo o nada: si una búsqueda no aparece exactamente una vez, no cambia nada.
 */
final class ContentReplace
{
    public const MAX_ITEMS = 100;

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'type' => 'array',
            'minItems' => 1,
            'maxItems' => self::MAX_ITEMS,
            'description' => 'Sustituciones sobre el contenido guardado, en orden: cada search debe aparecer exactamente una vez en el contenido tal como está en ese momento (con el HTML y las entidades guardados: leerlo antes con get-post si hace falta). Si una falla, no se cambia nada. No se admite junto a content. Para comprobar el resultado, comparar content_hash.',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'minLength' => 1, 'description' => 'Texto exacto que buscar.'],
                    'replace' => ['type' => 'string', 'description' => 'Texto que lo sustituye.'],
                ],
                'required' => ['search', 'replace'],
                'additionalProperties' => false,
            ],
        ];
    }

    /**
     * @param mixed $items
     * @return string|WP_Error Contenido con las sustituciones aplicadas (sin filtrar todavía).
     */
    public static function apply(string $content, $items)
    {
        if (!is_array($items) || !$items || count($items) > self::MAX_ITEMS) {
            return new WP_Error(
                'wp_connect_mcp_invalid_replace',
                sprintf('replace debe ser una lista de 1 a %d sustituciones {search, replace}.', self::MAX_ITEMS),
                ['status' => 400]
            );
        }

        foreach (array_values($items) as $index => $item) {
            $search = is_array($item) && isset($item['search']) && is_string($item['search']) ? $item['search'] : '';
            $replace = is_array($item) && isset($item['replace']) && is_string($item['replace']) ? $item['replace'] : null;

            if ('' === $search || null === $replace) {
                return new WP_Error(
                    'wp_connect_mcp_invalid_replace',
                    sprintf('La sustitución %d necesita search (no vacío) y replace.', $index + 1),
                    ['status' => 400]
                );
            }

            $count = substr_count($content, $search);
            if (1 !== $count) {
                return new WP_Error(
                    'wp_connect_mcp_replace_not_unique',
                    sprintf(
                        'La sustitución %d no se ha aplicado: su search aparece %d veces en el contenido y debe aparecer exactamente una. No se ha cambiado nada.',
                        $index + 1,
                        $count
                    ),
                    ['status' => 409, 'index' => $index + 1, 'count' => $count]
                );
            }

            $position = strpos($content, $search);
            $content = substr_replace($content, $replace, (int) $position, strlen($search));
        }

        return $content;
    }
}
