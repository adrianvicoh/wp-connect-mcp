<?php

namespace WpConnectMcp\Abilities;

use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Grants;
use WpConnectMcp\Security\Guard;

defined('ABSPATH') || exit;

final class GetCurrentUser
{
    public const NAME = 'wp-connect-mcp/get-current-user';

    public static function register(): void
    {
        wp_register_ability(self::NAME, Guard::protect(self::NAME, [
            'label' => 'Consultar usuario conectado',
            'description' => 'Devuelve el usuario, las capacidades relevantes, los paquetes de herramientas otorgados a la conexión, la versión del plugin y el alcance de la contraseña, sin correo ni otros datos personales.',
            'category' => Plugin::CATEGORY,
            'input_schema' => ['type' => 'object', 'default' => [], 'properties' => [], 'additionalProperties' => false],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'login' => ['type' => 'string'],
                    'display_name' => ['type' => 'string'],
                    'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'can' => [
                        'type' => 'object',
                        'properties' => [
                            'publish_posts' => ['type' => 'boolean'],
                            'edit_others_posts' => ['type' => 'boolean'],
                            'upload_files' => ['type' => 'boolean'],
                        ],
                        'required' => ['publish_posts', 'edit_others_posts', 'upload_files'],
                    ],
                    'packages' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Paquetes de herramientas otorgados por un administrador.'],
                    'version' => ['type' => 'string', 'description' => 'Versión de WP Connect MCP instalada en el sitio.'],
                    'password_scope' => ['type' => 'string', 'enum' => ['mcp'], 'description' => 'La contraseña de la conexión solo vale para este MCP, y el usuario no puede usar la API de WordPress con otras credenciales.'],
                ],
                'required' => ['id', 'login', 'display_name', 'roles', 'can', 'packages', 'version', 'password_scope'],
            ],
            'execute_callback' => [self::class, 'execute'],
            'permission_callback' => [self::class, 'permission'],
            'meta' => ['annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
        ]));
    }

    public static function permission(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * @return array<string, mixed>
     */
    public static function execute(): array
    {
        $user = wp_get_current_user();

        return [
            'id' => (int) $user->ID,
            'login' => $user->user_login,
            'display_name' => $user->display_name,
            'roles' => array_values((array) $user->roles),
            'can' => [
                'publish_posts' => current_user_can('publish_posts'),
                'edit_others_posts' => current_user_can('edit_others_posts'),
                'upload_files' => current_user_can('upload_files'),
            ],
            'packages' => Grants::packagesOf((int) $user->ID),
            'version' => WP_CONNECT_MCP_VERSION,
            // Siempre «mcp»: el servidor solo acepta contraseñas de la página, limitadas a esta ruta (Guard::canConnect()).
            'password_scope' => 'mcp',
        ];
    }
}
