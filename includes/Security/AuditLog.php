<?php

namespace WpConnectMcp\Security;

defined('ABSPATH') || exit;

/**
 * Registro de uso: quién, qué ability, sobre qué y con qué resultado. Se guarda en una opción
 * (no en debug.log, que en algunos sitios es público) y conserva las últimas MAX entradas.
 */
final class AuditLog
{
    public const OPTION = 'wp_connect_mcp_log';
    private const MAX = 500;

    /**
     * @param int|string|null $target
     */
    public static function add(int $userId, string $action, $target = null, string $result = 'ok'): void
    {
        $log = get_option(self::OPTION, []);
        $log = is_array($log) ? $log : [];

        array_unshift($log, [
            'at' => time(),
            'user' => $userId,
            'action' => $action,
            'target' => null === $target ? '' : (string) $target,
            'result' => $result,
        ]);

        update_option(self::OPTION, array_slice($log, 0, self::MAX), false);
    }

    /**
     * @return list<array{at: int, user: int, action: string, target: string, result: string}>
     */
    public static function latest(int $limit): array
    {
        $log = get_option(self::OPTION, []);

        return is_array($log) ? array_slice($log, 0, $limit) : [];
    }
}
