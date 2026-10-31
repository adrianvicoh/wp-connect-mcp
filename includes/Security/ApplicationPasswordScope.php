<?php

namespace WpConnectMcp\Security;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WpConnectMcp\Admin\ConnectPage;
use WpConnectMcp\Plugin;

defined('ABSPATH') || exit;

/**
 * Las contraseñas de aplicación creadas desde «Conectar con Claude» solo valen para el endpoint MCP:
 * se rechazan en el resto de la API REST (wp/v2, batch…) y en XML-RPC. Y el endpoint MCP solo acepta
 * estas (Guard::canConnect()), para que toda conexión quede limitada. Las creadas a mano no se tocan fuera del MCP.
 */
final class ApplicationPasswordScope
{
    /**
     * @var bool
     */
    private static $pluginPassword = false;

    public static function register(): void
    {
        add_action('application_password_did_authenticate', [self::class, 'remember'], 10, 2);
        add_action('wp_authenticate_application_password_errors', [self::class, 'blockXmlRpc'], 10, 3);
        add_filter('rest_pre_dispatch', [self::class, 'restrictRoute'], 10, 3);
    }

    /**
     * @param \WP_User $user
     * @param array<string, mixed> $item
     */
    public static function remember($user, $item): void
    {
        self::$pluginPassword = self::isPluginPassword($item);
    }

    /**
     * @param \WP_User $user
     * @param array<string, mixed> $item
     */
    public static function blockXmlRpc(WP_Error $error, $user, $item): void
    {
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST && self::isPluginPassword($item)) {
            $error->add('wp_connect_mcp_password_scope', 'Esta contraseña de aplicación solo vale para la conexión con Claude (MCP).');
        }
    }

    public static function usedPluginPassword(): bool
    {
        wp_get_current_user();

        return self::$pluginPassword;
    }

    /**
     * @param mixed $result
     * @return mixed
     */
    public static function restrictRoute($result, WP_REST_Server $server, WP_REST_Request $request)
    {
        // Asegura que la autenticación ya se ha hecho y que remember() ha podido ejecutarse.
        wp_get_current_user();

        if (!self::$pluginPassword) {
            return $result;
        }

        $mcp = '/' . Plugin::ROUTE_NAMESPACE . '/' . Plugin::ROUTE;
        $route = untrailingslashit($request->get_route());

        if ($route === $mcp || 0 === strpos($route, $mcp . '/')) {
            return $result;
        }

        return new WP_Error(
            'wp_connect_mcp_password_scope',
            'Esta contraseña de aplicación solo vale para la conexión con Claude (MCP), no para el resto de la API.',
            ['status' => 403]
        );
    }

    /**
     * @param mixed $item
     */
    private static function isPluginPassword($item): bool
    {
        return is_array($item) && ConnectPage::APP_ID === ($item['app_id'] ?? '');
    }
}
