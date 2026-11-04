<?php

namespace WpConnectMcp\Abilities;

use WP_Error;
use WP_Term;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Guard;

defined('ABSPATH') || exit;

/**
 * Crea una categoría de entradas. Si ya existe una con ese slug (o con ese nombre bajo el mismo padre),
 * devuelve la existente sin crear otra. Se controla con el paquete «Categorías y etiquetas», no con
 * manage_categories, para que un Autor pueda usarla si un administrador se lo da.
 */
final class CreateCategory
{
    public const NAME = 'wp-connect-mcp/create-category';
    private const MAX_NAME = 200;

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Crear categoría',
            'description' => 'Crea una categoría de entradas. Si ya existe una con ese slug (o con ese nombre bajo el mismo padre), devuelve la existente con created: false. Confirmar con el usuario antes de crearla.',
            'category' => Plugin::CATEGORY,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => self::MAX_NAME, 'description' => 'Nombre de la categoría.'],
                    'slug' => ['type' => 'string', 'description' => 'Slug; por defecto, el que WordPress saca del nombre.'],
                    'parent' => ['type' => 'integer', 'minimum' => 0, 'description' => 'ID de la categoría padre (0 o sin enviar: sin padre).'],
                ],
                'required' => ['name'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'parent' => ['type' => 'integer'],
                    'link' => ['type' => 'string'],
                    'created' => ['type' => 'boolean', 'description' => 'false si ya existía y se devuelve la existente.'],
                ],
                'required' => ['id', 'name', 'slug', 'parent', 'link', 'created'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => ['annotations' => ['readonly' => false, 'destructive' => false, 'idempotent' => true]],
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
        $name = sanitize_text_field((string) ($input['name'] ?? ''));
        if ('' === $name) {
            return new WP_Error('wp_connect_mcp_invalid_name', 'El nombre de la categoría no puede estar vacío.', ['status' => 400]);
        }

        $slug = sanitize_title('' !== trim((string) ($input['slug'] ?? '')) ? (string) $input['slug'] : $name);
        $parent = (int) ($input['parent'] ?? 0);

        if ($parent > 0 && !term_exists($parent, 'category')) {
            return new WP_Error('wp_connect_mcp_invalid_category', sprintf('No existe ninguna categoría con ID %d.', $parent), ['status' => 400]);
        }

        if ('' !== $slug) {
            $existing = get_term_by('slug', $slug, 'category');
            if ($existing instanceof WP_Term) {
                return self::format($existing, false);
            }
        }

        $result = wp_insert_term($name, 'category', array_filter(['slug' => $slug, 'parent' => $parent]));

        if (is_wp_error($result)) {
            // Mismo nombre bajo el mismo padre: WordPress da term_exists con el ID de la existente.
            $existingId = (int) $result->get_error_data('term_exists');
            if ($existingId > 0) {
                $existing = get_term($existingId, 'category');
                if ($existing instanceof WP_Term) {
                    return self::format($existing, false);
                }
            }

            return $result;
        }

        $term = get_term((int) $result['term_id'], 'category');
        if (!$term instanceof WP_Term) {
            return new WP_Error('wp_connect_mcp_category_failed', 'No se ha podido leer la categoría creada.', ['status' => 500]);
        }

        return self::format($term, true);
    }

    /**
     * @return array<string, mixed>
     */
    private static function format(WP_Term $term, bool $created): array
    {
        $link = get_category_link($term->term_id);

        return [
            'id' => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'parent' => (int) $term->parent,
            'link' => is_wp_error($link) ? '' : (string) $link,
            'created' => $created,
        ];
    }
}
