<?php

namespace WpConnectMcp\Support;

use WP_Error;
use WP_Post;
use WpConnectMcp\Abilities\ListTemplates;
use WpConnectMcp\Security\Grants;

defined('ABSPATH') || exit;

/**
 * Plantillas guardadas de Elementor (elementor_library) que se pueden listar e insertar en una entrada
 * con el shortcode [elementor-template id="N"]. Solo las publicadas de los tipos de TYPES.
 */
final class SavedTemplates
{
    public const POST_TYPE = 'elementor_library';
    public const TYPES = ['section', 'container', 'page'];
    private const SHORTCODE = 'elementor-template';

    public static function isInsertable(int $id): bool
    {
        $post = get_post($id);

        return $post instanceof WP_Post
            && self::POST_TYPE === $post->post_type
            && 'publish' === $post->post_status
            && '' === $post->post_password
            && in_array(self::typeOf($post->ID), self::TYPES, true);
    }

    public static function typeOf(int $id): string
    {
        return (string) get_post_meta($id, '_elementor_template_type', true);
    }

    public static function shortcode(int $id): string
    {
        return sprintf('[%s id="%d"]', self::SHORTCODE, $id);
    }

    /**
     * Comprueba los shortcodes [elementor-template] que el contenido nuevo añade respecto al actual:
     * solo con el paquete de plantillas, solo el atributo id y solo plantillas insertables. Los que ya
     * estaban en la entrada no se vuelven a comprobar (la plantilla puede haber cambiado después).
     *
     * @return true|WP_Error
     */
    public static function validateContent(string $content, string $previous = '')
    {
        $found = self::find($content);
        $added = array_values(array_diff($found, self::find($previous)));

        if (!$added) {
            return true;
        }

        if (!Grants::allows(ListTemplates::NAME, get_current_user_id())) {
            return new WP_Error(
                'wp_connect_mcp_templates_not_allowed',
                'Insertar plantillas de Elementor ([elementor-template]) exige el paquete «Plantillas de Elementor».',
                ['status' => 403]
            );
        }

        foreach ($added as $shortcode) {
            $id = self::idOf($shortcode);
            if (null === $id || !self::isInsertable($id)) {
                return new WP_Error(
                    'wp_connect_mcp_invalid_template',
                    sprintf(
                        '%s no es una plantilla insertable. Usa [elementor-template id="N"], solo con el atributo id, y el ID de una plantilla de list-templates.',
                        $shortcode
                    ),
                    ['status' => 400]
                );
            }
        }

        return true;
    }

    /**
     * @return list<string> Shortcodes [elementor-template …] del contenido, tal cual aparecen.
     */
    private static function find(string $content): array
    {
        if (false === stripos($content, '[' . self::SHORTCODE)) {
            return [];
        }

        preg_match_all('/\[' . preg_quote(self::SHORTCODE, '/') . '(?=[\s\]\/])[^\]]*\]/i', $content, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * Solo se acepta la forma exacta [elementor-template id="N"] (comillas dobles, simples o sin ellas).
     */
    private static function idOf(string $shortcode): ?int
    {
        if (!preg_match('/^\[' . preg_quote(self::SHORTCODE, '/') . '\s+id=(["\']?)([1-9][0-9]{0,18})\1\s*\]$/i', $shortcode, $match)) {
            return null;
        }

        return (int) $match[2];
    }
}
