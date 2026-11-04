<?php

namespace WpConnectMcp\Security;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WP_User;
use WpConnectMcp\Admin\ConnectPage;
use WpConnectMcp\Plugin;

defined('ABSPATH') || exit;

/**
 * Las contraseñas de aplicación creadas desde «Conectar con Claude» solo valen para el endpoint MCP:
 * se rechazan en el resto de la API REST (wp/v2, batch…) y en XML-RPC. Y el endpoint MCP solo acepta
 * estas (Guard::canConnect()), para que toda conexión quede limitada.
 *
 * Además, un usuario con algún paquete de Claude no puede usar la API de WordPress con otras credenciales:
 * ni contraseñas de aplicación creadas a mano (REST y XML-RPC) ni XML-RPC con su contraseña normal. Sin
 * excepciones por usuario. El escritorio (wp-admin, y la API que usa con la sesión del navegador) no cambia.
 */
final class ApplicationPasswordScope
{
    private const BLOCKED_MESSAGE = 'Este usuario tiene acceso de Claude: solo puede usar la API de WordPress a través de la conexión con Claude (MCP).';

    /**
     * @var bool
     */
    private static $pluginPassword = false;

    public static function register(): void
    {
        add_action('application_password_did_authenticate', [self::class, 'remember'], 10, 2);
        add_action('wp_authenticate_application_password_errors', [self::class, 'blockXmlRpc'], 10, 3);
        add_action('wp_authenticate_application_password_errors', [self::class, 'blockOtherPasswords'], 10, 3);
        add_filter('authenticate', [self::class, 'blockXmlRpcLogin'], 100);
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

    /**
     * Contraseñas de aplicación que no son del plugin: rechazadas para cualquier usuario con paquetes.
     *
     * @param \WP_User $user
     * @param array<string, mixed> $item
     */
    public static function blockOtherPasswords(WP_Error $error, $user, $item): void
    {
        if (self::isPluginPassword($item) || !self::hasPackages($user)) {
            return;
        }

        $error->add('wp_connect_mcp_api_blocked', self::BLOCKED_MESSAGE);
        self::logBlocked((int) $user->ID, 'contraseña de aplicación ajena al plugin');
    }

    /**
     * XML-RPC con usuario y contraseña (normal o de aplicación): rechazado para cualquier usuario con paquetes.
     *
     * @param mixed $user
     * @return mixed
     */
    public static function blockXmlRpcLogin($user)
    {
        if (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST || !self::hasPackages($user)) {
            return $user;
        }

        self::logBlocked((int) $user->ID, 'XML-RPC');

        return new WP_Error('wp_connect_mcp_api_blocked', self::BLOCKED_MESSAGE);
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
     * @param mixed $user
     */
    private static function hasPackages($user): bool
    {
        return $user instanceof WP_User && Grants::hasAny((int) $user->ID);
    }

    /**
     * Anota el rechazo en el registro como mucho una vez por usuario y hora, para que una integración
     * que reintenta no llene el registro y desplace el resto.
     */
    private static function logBlocked(int $userId, string $via): void
    {
        $key = 'wp_connect_mcp_api_blocked_' . $userId;
        if (get_transient($key)) {
            return;
        }

        set_transient($key, 1, HOUR_IN_SECONDS);
        AuditLog::add($userId, 'api', '', 'denied: API fuera del MCP (' . $via . ')');
    }

    /**
     * @param mixed $item
     */
    private static function isPluginPassword($item): bool
    {
        return is_array($item) && ConnectPage::APP_ID === ($item['app_id'] ?? '');
    }
}
