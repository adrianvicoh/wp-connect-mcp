<?php

namespace WpConnectMcp\Security;

use WP\MCP\Core\McpServer;
use WP\MCP\Domain\Utils\McpNameSanitizer;
use WP_Error;
use WpConnectMcp\Abilities\CreatePost;
use WpConnectMcp\Abilities\SchedulePosts;
use WpConnectMcp\Abilities\TrashPost;
use WpConnectMcp\Abilities\UpdatePost;
use WpConnectMcp\Abilities\UploadMedia;
use WpConnectMcp\Plugin;

defined('ABSPATH') || exit;

/**
 * Envuelve cada ability: paquetes del usuario y límite de volumen antes del permission_callback
 * propio, y registro de uso tras ejecutarla. También filtra tools/list y da acceso al transporte.
 */
final class Guard
{
    /**
     * Máximo de ejecuciones por usuario y hora de las abilities que escriben. Filtro: wp_connect_mcp_rate_limits.
     * En schedule-posts cuenta cada entrada del lote, no cada llamada.
     */
    private const RATE_LIMITS = [
        CreatePost::NAME => 30,
        UpdatePost::NAME => 120,
        SchedulePosts::NAME => 120,
        UploadMedia::NAME => 60,
        TrashPost::NAME => 30,
    ];

    /**
     * @param array<string, mixed> $args Argumentos de wp_register_ability().
     * @return array<string, mixed>
     */
    public static function protect(string $ability, array $args): array
    {
        $permission = $args['permission_callback'];
        $execute = $args['execute_callback'];

        $args['permission_callback'] = static function (...$params) use ($ability, $permission) {
            $userId = get_current_user_id();

            if (!Grants::allows($ability, $userId)) {
                AuditLog::add($userId, $ability, self::target($ability, $params[0] ?? null), 'denied: sin paquete');
                return false;
            }

            $limited = self::rateLimited($ability, $userId, self::units($ability, $params[0] ?? null));
            if ($limited) {
                AuditLog::add($userId, $ability, self::target($ability, $params[0] ?? null), 'denied: límite por hora');
                return $limited;
            }

            return call_user_func_array($permission, $params);
        };

        $args['execute_callback'] = static function (...$params) use ($ability, $execute) {
            $result = call_user_func_array($execute, $params);
            $userId = get_current_user_id();

            if (is_wp_error($result)) {
                AuditLog::add($userId, $ability, self::target($ability, $params[0] ?? null), 'error: ' . $result->get_error_code());
                return $result;
            }

            self::count($ability, $userId, self::units($ability, $params[0] ?? null));
            $failed = is_array($result) ? count((array) ($result['failed'] ?? [])) : 0;
            AuditLog::add(
                $userId,
                $ability,
                self::target($ability, is_array($result) ? $result : ($params[0] ?? null)),
                $failed ? sprintf('ok, %d sin guardar', $failed) : 'ok'
            );

            return $result;
        };

        return $args;
    }

    /**
     * transport_permission_callback del servidor: solo con una contraseña creada desde «Conectar con Claude»
     * (limitada a esta ruta) y con algún paquete otorgado. Si no, 401 o 403 y no se abre sesión.
     */
    public static function canConnect(): bool
    {
        return ApplicationPasswordScope::usedPluginPassword() && Grants::hasAny(get_current_user_id());
    }

    /**
     * mcp_adapter_tools_list: cada usuario ve solo las herramientas de sus paquetes.
     *
     * @param array<\WP\McpSchema\Record\Tool> $tools
     * @return array<\WP\McpSchema\Record\Tool>
     */
    public static function filterTools(array $tools, McpServer $server): array
    {
        if (Plugin::SERVER_ID !== $server->get_server_id()) {
            return $tools;
        }

        $allowed = [];
        foreach (Grants::abilitiesOf(get_current_user_id()) as $ability) {
            $name = McpNameSanitizer::sanitize_name($ability);
            if (is_string($name)) {
                $allowed[] = $name;
            }
        }

        return array_values(array_filter($tools, static function ($tool) use ($allowed): bool {
            return in_array($tool->getName(), $allowed, true);
        }));
    }

    /**
     * @return WP_Error|null
     */
    private static function rateLimited(string $ability, int $userId, int $units)
    {
        $limits = (array) apply_filters('wp_connect_mcp_rate_limits', self::RATE_LIMITS);
        $limit = (int) ($limits[$ability] ?? 0);

        if ($limit <= 0 || (int) get_transient(self::counterKey($ability, $userId)) + $units <= $limit) {
            return null;
        }

        return new WP_Error(
            'wp_connect_mcp_rate_limited',
            sprintf('Se ha alcanzado el máximo de %d usos por hora de esta herramienta. Inténtalo más tarde o pide a un administrador que revise el límite.', $limit),
            ['status' => 429]
        );
    }

    private static function count(string $ability, int $userId, int $units): void
    {
        if (!isset(self::RATE_LIMITS[$ability])) {
            return;
        }

        $key = self::counterKey($ability, $userId);
        set_transient($key, (int) get_transient($key) + $units, HOUR_IN_SECONDS);
    }

    /**
     * Usos que consume una llamada: uno, o uno por entrada en schedule-posts.
     *
     * @param mixed $input
     */
    private static function units(string $ability, $input): int
    {
        if (SchedulePosts::NAME !== $ability) {
            return 1;
        }

        return max(1, count(SchedulePosts::ids(is_array($input) ? $input : null)));
    }

    private static function counterKey(string $ability, int $userId): string
    {
        return 'wp_connect_mcp_rl_' . $userId . '_' . md5($ability . gmdate('YmdH'));
    }

    /**
     * @param mixed $data Entrada o resultado de la ability.
     */
    private static function target(string $ability, $data): string
    {
        $data = (array) $data;

        // Solo schedule-posts: list-posts también devuelve «posts», y no se anotan los IDs listados.
        if (SchedulePosts::NAME === $ability) {
            return implode(',', array_unique(array_merge(
                SchedulePosts::ids($data),
                array_map('intval', array_column((array) ($data['failed'] ?? []), 'id'))
            )));
        }

        foreach (['id', 'post_id'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        return '';
    }
}
