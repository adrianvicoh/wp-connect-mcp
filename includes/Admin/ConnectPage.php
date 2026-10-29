<?php

namespace WpConnectMcp\Admin;

use WP_Application_Passwords;
use WP_Error;
use WP_User;
use WpConnectMcp\Plugin;

defined('ABSPATH') || exit;

/**
 * Herramientas → Conectar con Claude: crea una Application Password para el usuario actual
 * y muestra el comando de una línea que la registra en Claude Code.
 */
final class ConnectPage
{
    public const SLUG = 'wp-connect-mcp';

    /**
     * Identifica las Application Passwords creadas desde esta página.
     */
    public const APP_ID = '6f1c2b9e-4d7a-4e3b-9a58-0c2d7e1f4b36';

    private const ACTION = 'wp_connect_mcp_create';
    private const CAPABILITY = 'edit_posts';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
    }

    public static function addMenu(): void
    {
        $hook = add_management_page(
            'Conectar con Claude',
            'Conectar con Claude',
            self::CAPABILITY,
            self::SLUG,
            [self::class, 'render']
        );

        if ($hook) {
            add_action('admin_print_footer_scripts-' . $hook, [self::class, 'printCopyScript']);
        }
    }

    public static function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('No tienes permiso para ver esta página.', 'wp-connect-mcp'));
        }

        $user = wp_get_current_user();
        $result = null;

        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['_wpnonce'])) {
            check_admin_referer(self::ACTION);
            $result = self::createConnection($user);
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Conectar con Claude', 'wp-connect-mcp') . '</h1>';
        echo '<p>' . esc_html__('Conecta Claude Code con este sitio para que pueda consultar categorías y plantillas, crear y editar entradas, completar Yoast, subir imágenes, programar, publicar y enviar a la papelera. Solo modifica, publica y borra contenido creado con tu usuario, y solo publica o sube archivos si tu rol lo permite.', 'wp-connect-mcp') . '</p>';

        if (!wp_is_application_passwords_available_for_user($user)) {
            self::renderUnavailable();
            echo '</div>';
            return;
        }

        if (is_wp_error($result)) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($result->get_error_message()));
        }

        if (is_array($result)) {
            self::renderCommand($result['command'], $result['server'], $user);
        } else {
            self::renderStart($user);
        }

        self::renderConnections($user);
        echo '</div>';
    }

    /**
     * @return array{command: string, server: string}|WP_Error
     */
    private static function createConnection(WP_User $user)
    {
        $created = WP_Application_Passwords::create_new_application_password($user->ID, [
            'name' => sprintf('Claude Code · %s', wp_date('d/m/Y H:i:s')),
            'app_id' => self::APP_ID,
        ]);

        if (is_wp_error($created)) {
            return $created;
        }

        $server = self::serverName();
        $auth = base64_encode($user->user_login . ':' . $created[0]);

        return [
            'server' => $server,
            'command' => sprintf(
                'claude mcp add --transport http --scope user "%s" "%s" --header "Authorization: Basic %s"',
                $server,
                self::endpoint(),
                $auth
            ),
        ];
    }

    private static function renderStart(WP_User $user): void
    {
        echo '<div class="card">';
        echo '<h2>' . esc_html__('Cómo conectarse', 'wp-connect-mcp') . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__('Pulsa «Crear conexión». Se genera una contraseña solo para Claude, y debajo aparece un comando.', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Copia el comando con el botón «Copiar».', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Abre la terminal: en Mac, «Terminal»; en Windows, «PowerShell»; en Linux, «Terminal». Pega el comando y pulsa Intro.', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Abre una sesión nueva de Claude Code y escribe /mcp para comprobar que el sitio aparece conectado.', 'wp-connect-mcp') . '</li>';
        echo '</ol>';

        printf(
            '<p>%s <strong>%s</strong> (%s). %s</p>',
            esc_html__('La conexión se creará para el usuario', 'wp-connect-mcp'),
            esc_html($user->display_name),
            esc_html($user->user_login),
            esc_html__('Claude actuará en su nombre. Si no es el usuario que quieres usar, cierra la sesión y entra con el correcto.', 'wp-connect-mcp')
        );

        if (in_array('administrator', (array) $user->roles, true)) {
            echo '<p><strong>' . esc_html__('Estás conectado como administrador.', 'wp-connect-mcp') . '</strong> ';
            echo esc_html__('Claude actuará con tu usuario. Es preferible usar un usuario propio para Claude con rol Autor.', 'wp-connect-mcp') . '</p>';
        }

        echo '<form method="post">';
        wp_nonce_field(self::ACTION);
        submit_button(esc_html__('Crear conexión', 'wp-connect-mcp'), 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';
    }

    private static function renderCommand(string $command, string $server, WP_User $user): void
    {
        printf(
            '<div class="notice notice-success"><p>%s <strong>%s</strong> (%s). %s</p></div>',
            esc_html__('Conexión creada para el usuario', 'wp-connect-mcp'),
            esc_html($user->display_name),
            esc_html($user->user_login),
            esc_html__('Copia el comando ahora: por seguridad, no se volverá a mostrar.', 'wp-connect-mcp')
        );
        echo '<div class="card">';
        echo '<h2>' . esc_html__('Comando para la terminal', 'wp-connect-mcp') . '</h2>';
        printf(
            '<textarea id="wp-connect-mcp-command" class="large-text code" rows="4" readonly onclick="this.select()">%s</textarea>',
            esc_textarea($command)
        );
        printf(
            '<p><button type="button" class="button button-primary" data-wp-connect-mcp-copy="wp-connect-mcp-command">%s</button></p>',
            esc_html__('Copiar', 'wp-connect-mcp')
        );
        echo '<ol>';
        echo '<li>' . esc_html__('Abre la terminal: en Mac, «Terminal»; en Windows, «PowerShell»; en Linux, «Terminal».', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Pega el comando y pulsa Intro.', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Abre una sesión nueva de Claude Code y escribe /mcp: debe aparecer como conectado:', 'wp-connect-mcp') . ' <code>' . esc_html($server) . '</code></li>';
        echo '</ol>';
        echo '<p>' . esc_html__('Si la terminal responde que ya existe, este sitio ya estaba conectado. Para sustituir la conexión, pega primero este comando y después el de arriba:', 'wp-connect-mcp') . '</p>';
        printf('<p><code>claude mcp remove "%s"</code></p>', esc_html($server));
        echo '<p><strong>' . esc_html__('El comando contiene una contraseña.', 'wp-connect-mcp') . '</strong> ';
        echo esc_html__('No lo envíes por correo ni lo pegues en chats. Si crees que alguien lo ha visto, revoca la conexión abajo, en tu perfil, y crea otra.', 'wp-connect-mcp') . '</p>';
        echo '</div>';
    }

    private static function renderConnections(WP_User $user): void
    {
        $connections = array_filter(
            WP_Application_Passwords::get_user_application_passwords($user->ID),
            static function (array $item): bool {
                return self::APP_ID === ($item['app_id'] ?? '');
            }
        );

        echo '<h2>' . esc_html__('Tus conexiones con Claude', 'wp-connect-mcp') . '</h2>';

        if (!$connections) {
            echo '<p>' . esc_html__('Todavía no has creado ninguna.', 'wp-connect-mcp') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:48rem"><thead><tr>';
        echo '<th>' . esc_html__('Nombre', 'wp-connect-mcp') . '</th>';
        echo '<th>' . esc_html__('Último uso', 'wp-connect-mcp') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($connections as $item) {
            printf(
                '<tr><td>%s</td><td>%s</td></tr>',
                esc_html($item['name']),
                $item['last_used'] ? esc_html(wp_date('d/m/Y', $item['last_used'])) : esc_html__('Nunca', 'wp-connect-mcp')
            );
        }
        echo '</tbody></table>';
        printf(
            '<p><a href="%s">%s</a></p>',
            esc_url(admin_url('profile.php#application-passwords-section')),
            esc_html__('Revocar conexiones en tu perfil (Contraseñas de aplicación)', 'wp-connect-mcp')
        );
    }

    private static function renderUnavailable(): void
    {
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Las contraseñas de aplicación no están disponibles para tu usuario en este sitio, así que no se puede crear la conexión. Suele deberse a que el sitio no usa HTTPS o a que un plugin de seguridad las ha desactivado. Avisa a soporte técnico.', 'wp-connect-mcp');
        echo '</p></div>';
    }

    private static function endpoint(): string
    {
        return rest_url(Plugin::ROUTE_NAMESPACE . '/' . Plugin::ROUTE);
    }

    /**
     * Nombre de la conexión en Claude Code: wp-connect-mcp-<dominio con guiones>.
     */
    private static function serverName(): string
    {
        $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));

        return 'wp-connect-mcp-' . preg_replace('/[^a-z0-9-]/', '', str_replace('.', '-', $host));
    }

    public static function printCopyScript(): void
    {
        ?>
        <script>
        document.querySelectorAll('[data-wp-connect-mcp-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var field = document.getElementById(button.getAttribute('data-wp-connect-mcp-copy'));
                var done = function () { button.textContent = 'Copiado'; };
                field.select();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(field.value).then(done, function () { document.execCommand('copy'); done(); });
                } else {
                    document.execCommand('copy');
                    done();
                }
            });
        });
        </script>
        <?php
    }
}
