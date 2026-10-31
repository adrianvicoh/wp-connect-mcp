<?php

namespace WpConnectMcp\Admin;

use WP_User;
use WpConnectMcp\Security\AuditLog;
use WpConnectMcp\Security\Grants;

defined('ABSPATH') || exit;

/**
 * Usuarios → Acceso de Claude: un administrador otorga o quita paquetes de herramientas a cada usuario
 * y ve el registro de uso. Quitar todos los paquetes revoca las conexiones del usuario.
 */
final class GrantsPage
{
    public const SLUG = 'wp-connect-mcp-access';

    private const ACTION = 'wp_connect_mcp_grants';
    private const CAPABILITY = 'promote_users';
    private const MAX_USERS = 300;
    private const LOG_ROWS = 100;

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
    }

    public static function addMenu(): void
    {
        add_users_page(
            'Acceso de Claude',
            'Acceso de Claude',
            self::CAPABILITY,
            self::SLUG,
            [self::class, 'render']
        );
    }

    public static function url(): string
    {
        return admin_url('users.php?page=' . self::SLUG);
    }

    public static function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('No tienes permiso para ver esta página.', 'wp-connect-mcp'));
        }

        $users = get_users([
            'capability' => 'edit_posts',
            'orderby' => 'login',
            'number' => self::MAX_USERS,
        ]);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Acceso de Claude', 'wp-connect-mcp') . '</h1>';

        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['_wpnonce'])) {
            check_admin_referer(self::ACTION);
            self::save($users);
        }

        echo '<p>' . esc_html__('Elige qué paquetes de herramientas puede usar Claude con cada usuario. Sin ningún paquete, el usuario no puede crear la conexión ni usar las que ya tenga. Claude solo actúa sobre contenido del propio usuario y dentro de lo que permite su rol.', 'wp-connect-mcp') . '</p>';
        echo '<p>' . esc_html__('Al quitar todos los paquetes a un usuario, se revocan también sus conexiones con Claude.', 'wp-connect-mcp') . '</p>';

        self::renderPackages();
        self::renderForm($users);
        self::renderLog();

        echo '</div>';
    }

    /**
     * @param list<WP_User> $users
     */
    private static function save(array $users): void
    {
        $posted = isset($_POST['wp_connect_mcp_packages']) && is_array($_POST['wp_connect_mcp_packages'])
            ? wp_unslash($_POST['wp_connect_mcp_packages'])
            : [];
        $changedBy = get_current_user_id();
        $revoked = 0;

        // Solo se tocan los usuarios que se mostraron en el formulario.
        foreach ($users as $user) {
            $packages = isset($posted[$user->ID]) && is_array($posted[$user->ID])
                ? array_map('sanitize_key', $posted[$user->ID])
                : [];
            $revoked += Grants::set((int) $user->ID, $packages, $changedBy);
        }

        $message = __('Cambios guardados.', 'wp-connect-mcp');
        if ($revoked) {
            /* translators: %d: número de conexiones revocadas */
            $message .= ' ' . sprintf(__('Conexiones revocadas: %d.', 'wp-connect-mcp'), $revoked);
        }

        printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($message));
    }

    private static function renderPackages(): void
    {
        echo '<h2>' . esc_html__('Paquetes', 'wp-connect-mcp') . '</h2>';
        echo '<ul class="ul-disc">';
        foreach (Grants::PACKAGES as $key => $package) {
            printf(
                '<li><strong>%s</strong> (<code>%s</code>): %s</li>',
                esc_html($package['label']),
                esc_html($key),
                esc_html($package['description'])
            );
        }
        echo '</ul>';
    }

    /**
     * @param list<WP_User> $users
     */
    private static function renderForm(array $users): void
    {
        echo '<h2>' . esc_html__('Usuarios', 'wp-connect-mcp') . '</h2>';

        if (!$users) {
            echo '<p>' . esc_html__('No hay usuarios que puedan editar entradas.', 'wp-connect-mcp') . '</p>';
            return;
        }

        echo '<form method="post">';
        wp_nonce_field(self::ACTION);
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Usuario', 'wp-connect-mcp') . '</th>';
        echo '<th>' . esc_html__('Rol', 'wp-connect-mcp') . '</th>';
        foreach (Grants::PACKAGES as $package) {
            echo '<th>' . esc_html($package['label']) . '</th>';
        }
        echo '<th>' . esc_html__('Conexiones', 'wp-connect-mcp') . '</th>';
        echo '<th>' . esc_html__('Último cambio', 'wp-connect-mcp') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($users as $user) {
            $granted = Grants::packagesOf((int) $user->ID);

            echo '<tr>';
            printf('<td><strong>%s</strong><br>%s</td>', esc_html($user->user_login), esc_html($user->display_name));
            printf('<td>%s</td>', esc_html(self::roleLabel($user)));

            foreach (array_keys(Grants::PACKAGES) as $key) {
                printf(
                    '<td><input type="checkbox" name="wp_connect_mcp_packages[%d][]" value="%s" aria-label="%s" %s></td>',
                    (int) $user->ID,
                    esc_attr($key),
                    esc_attr(sprintf('%s: %s', $user->user_login, Grants::PACKAGES[$key]['label'])),
                    checked(in_array($key, $granted, true), true, false)
                );
            }

            printf('<td>%s</td>', esc_html(self::connectionsLabel((int) $user->ID)));
            printf('<td>%s</td>', esc_html(self::changedLabel((int) $user->ID)));
            echo '</tr>';
        }

        echo '</tbody></table>';
        submit_button(esc_html__('Guardar cambios', 'wp-connect-mcp'));
        echo '</form>';
    }

    private static function renderLog(): void
    {
        echo '<h2>' . esc_html__('Registro de uso', 'wp-connect-mcp') . '</h2>';
        /* translators: %d: número de entradas del registro */
        echo '<p>' . esc_html(sprintf(__('Últimos %d movimientos: herramientas usadas por Claude, accesos denegados y cambios de permisos.', 'wp-connect-mcp'), self::LOG_ROWS)) . '</p>';

        $log = AuditLog::latest(self::LOG_ROWS);
        if (!$log) {
            echo '<p>' . esc_html__('Todavía no hay movimientos.', 'wp-connect-mcp') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        foreach ([__('Fecha', 'wp-connect-mcp'), __('Usuario', 'wp-connect-mcp'), __('Acción', 'wp-connect-mcp'), __('Sobre', 'wp-connect-mcp'), __('Resultado', 'wp-connect-mcp')] as $title) {
            echo '<th>' . esc_html($title) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($log as $row) {
            printf(
                '<tr><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td><td>%s</td></tr>',
                esc_html(wp_date('d/m/Y H:i:s', (int) $row['at'])),
                esc_html(self::userLabel((int) $row['user'])),
                esc_html((string) $row['action']),
                esc_html((string) $row['target']),
                esc_html((string) $row['result'])
            );
        }

        echo '</tbody></table>';
    }

    private static function connectionsLabel(int $userId): string
    {
        $connections = Grants::connectionsOf($userId);
        if (!$connections) {
            return '—';
        }

        $lastUsed = max(array_map(static function (array $item): int {
            return (int) ($item['last_used'] ?? 0);
        }, $connections));

        return sprintf(
            /* translators: 1: número de conexiones, 2: fecha de último uso */
            __('%1$d · último uso: %2$s', 'wp-connect-mcp'),
            count($connections),
            $lastUsed ? wp_date('d/m/Y', $lastUsed) : __('nunca', 'wp-connect-mcp')
        );
    }

    private static function changedLabel(int $userId): string
    {
        $changed = get_user_meta($userId, Grants::CHANGED_META, true);
        if (!is_array($changed) || empty($changed['at'])) {
            return '—';
        }

        return sprintf('%s · %s', wp_date('d/m/Y H:i', (int) $changed['at']), self::userLabel((int) ($changed['by'] ?? 0)));
    }

    private static function userLabel(int $userId): string
    {
        if (0 === $userId) {
            return __('(sistema)', 'wp-connect-mcp');
        }

        $user = get_userdata($userId);

        return $user ? $user->user_login : sprintf('#%d', $userId);
    }

    private static function roleLabel(WP_User $user): string
    {
        $names = wp_roles()->role_names;
        $roles = array_map(static function (string $role) use ($names): string {
            return isset($names[$role]) ? translate_user_role($names[$role]) : $role;
        }, array_values((array) $user->roles));

        return $roles ? implode(', ', $roles) : __('sin rol', 'wp-connect-mcp');
    }
}
