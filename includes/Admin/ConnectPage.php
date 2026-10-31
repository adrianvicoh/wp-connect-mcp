<?php

namespace WpConnectMcp\Admin;

use WP_Application_Passwords;
use WP_Error;
use WP_User;
use WpConnectMcp\Plugin;
use WpConnectMcp\Security\Grants;

defined('ABSPATH') || exit;

/**
 * Herramientas → Conectar con Claude: crea una Application Password para el usuario actual,
 * la muestra aparte y da un bloque por sistema (Mac/Linux y PowerShell) que la pide, la comprueba
 * y registra la conexión en Claude Code. Los bloques no llevan la contraseña.
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

    /**
     * Bloques para la terminal. __SITE__, __MCP__, __NAME__ y __USER__ se sustituyen al crear la conexión.
     * Hacen lo mismo que los del README («Alternativa: desde la terminal»), con el sitio y el usuario ya puestos.
     */
    private const UNIX_BLOCK = <<<'BASH'
bash <<'EOF'
SITE="__SITE__"
MCP="__MCP__"
NAME="__NAME__"
WP_USER="__USER__"
fallo() { printf '\n❌ %s\n' "$1"; [ -n "${2:-}" ] && printf '   %s\n' "$2"; exit 1; }
si() { case "$1" in s|S|si|sí|Si|Sí|SI|SÍ) return 0 ;; *) return 1 ;; esac; }
command -v claude >/dev/null 2>&1 || fallo "No se encuentra Claude Code en este equipo." "Instálalo primero y vuelve a pegar este bloque."
echo "Conectar Claude Code con $SITE (usuario $WP_USER)"
echo "-------------------------------------------"
read -r -s -p "Pega la contraseña de aplicación que muestra la página (no se verá): " WP_APP_PASS </dev/tty; echo
WP_APP_PASS=$(printf '%s' "$WP_APP_PASS" | tr -d '[:space:]')
[ -n "$WP_APP_PASS" ] || fallo "No has pegado la contraseña de aplicación." "Cópiala de la página con su botón «Copiar» y vuelve a pegar este bloque."
echo "Comprobando la contraseña…"
RESP=$(printf 'user = "%s:%s"\n' "$WP_USER" "$WP_APP_PASS" | curl -sS --max-time 20 -K - -w '\n%{http_code}' "$MCP" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"conectar-claude","version":"1.0"}}}' 2>/dev/null)
CODE=${RESP##*$'\n'}
case "$CODE" in
  200) ;;
  000) fallo "No se puede conectar con $SITE." "Revisa que tengas internet y vuelve a pegar este bloque." ;;
  401) fallo "La contraseña de aplicación no es correcta." "Cópiala otra vez de la página con el botón «Copiar». Si ya no se ve, crea otra conexión." ;;
  403) fallo "Tu usuario no tiene acceso a Claude en este sitio." "Pide a un administrador que te lo dé en Usuarios → Acceso de Claude y crea otra conexión." ;;
  404) fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo active." ;;
  *)   fallo "El sitio ha respondido con un error ($CODE)." "Pide ayuda a soporte técnico y diles este número." ;;
esac
case "$RESP" in
  *'"WP Connect MCP"'*) echo "✅ Contraseña correcta." ;;
  *) fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo active." ;;
esac
if claude mcp get "$NAME" >/dev/null 2>&1; then
  read -r -p "Este sitio ya estaba conectado. ¿Quieres sustituir la conexión? (s/n): " OK </dev/tty
  si "$OK" || { echo "No se ha cambiado nada."; exit 0; }
  claude mcp remove "$NAME" >/dev/null 2>&1
fi
AUTH=$(printf '%s' "$WP_USER:$WP_APP_PASS" | base64 | tr -d '\n')
claude mcp add --transport http --scope user "$NAME" "$MCP" --header "Authorization: Basic $AUTH" >/dev/null \
  || fallo "Claude Code no ha podido guardar la conexión." "Pide ayuda a soporte técnico."
echo
echo "✅ Listo. $SITE está conectado en Claude Code como «$NAME»."
echo "   1. Abre una sesión nueva de Claude Code (las que ya estaban abiertas no lo verán)."
echo "   2. Escribe /mcp y comprueba que aparece «$NAME» como conectado."
EOF
BASH;

    private const WINDOWS_BLOCK = <<<'POWERSHELL'
& {
  [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12
  $site = "__SITE__"
  $mcp = "__MCP__"
  $name = "__NAME__"
  $user = "__USER__"
  function Fallo($msg, $hint) { Write-Host ""; Write-Host "ERROR: $msg" -ForegroundColor Red; if ($hint) { Write-Host "   $hint" } }
  function Si($r) { return ("$r".Trim() -match '^(s|si|sí)$') }
  if (-not (Get-Command claude -ErrorAction SilentlyContinue)) { Fallo "No se encuentra Claude Code en este equipo." "Instálalo primero y vuelve a pegar este bloque."; return }
  Write-Host "Conectar Claude Code con $site (usuario $user)"
  Write-Host "-------------------------------------------"
  $sec = Read-Host "Pega la contraseña de aplicación que muestra la página (no se verá)" -AsSecureString
  $pass = (New-Object Net.NetworkCredential('', $sec)).Password -replace '\s', ''
  if (-not $pass) { Fallo "No has pegado la contraseña de aplicación." "Cópiala de la página con su botón «Copiar» y vuelve a pegar este bloque."; return }
  $auth = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($user + ':' + $pass))
  $headers = @{ Authorization = "Basic $auth"; Accept = "application/json, text/event-stream" }
  $body = '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"conectar-claude","version":"1.0"}}}'
  Write-Host "Comprobando la contraseña..."
  $init = $null
  try { $init = Invoke-RestMethod -Method Post -Uri $mcp -Headers $headers -ContentType "application/json" -Body $body -TimeoutSec 20 -UseBasicParsing }
  catch {
    $code = 0; if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
    if ($code -eq 0) { Fallo "No se puede conectar con $site." "Revisa que tengas internet y vuelve a pegar este bloque." }
    elseif ($code -eq 401) { Fallo "La contraseña de aplicación no es correcta." "Cópiala otra vez de la página con el botón «Copiar». Si ya no se ve, crea otra conexión." }
    elseif ($code -eq 403) { Fallo "Tu usuario no tiene acceso a Claude en este sitio." "Pide a un administrador que te lo dé en Usuarios -> Acceso de Claude y crea otra conexión." }
    elseif ($code -eq 404) { Fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo active." }
    else { Fallo "El sitio ha respondido con un error ($code)." "Pide ayuda a soporte técnico y diles este número." }
    return
  }
  if ($init.result.serverInfo.name -ne "WP Connect MCP") { Fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo active."; return }
  Write-Host "OK: contraseña correcta." -ForegroundColor Green
  claude mcp get $name *> $null
  if ($LASTEXITCODE -eq 0) {
    if (-not (Si (Read-Host "Este sitio ya estaba conectado. ¿Quieres sustituir la conexión? (s/n)"))) { Write-Host "No se ha cambiado nada."; return }
    claude mcp remove $name *> $null
  }
  claude mcp add --transport http --scope user $name $mcp --header "Authorization: Basic $auth" *> $null
  if ($LASTEXITCODE -ne 0) { Fallo "Claude Code no ha podido guardar la conexión." "Pide ayuda a soporte técnico."; return }
  Write-Host ""
  Write-Host "Listo. $site está conectado en Claude Code como «$name»." -ForegroundColor Green
  Write-Host "   1. Abre una sesión nueva de Claude Code (las que ya estaban abiertas no lo verán)."
  Write-Host "   2. Escribe /mcp y comprueba que aparece «$name» como conectado."
}
POWERSHELL;

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

        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['_wpnonce']) && wp_is_application_passwords_available_for_user($user)) {
            check_admin_referer(self::ACTION);
            $result = self::createConnection($user);
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Conectar con Claude', 'wp-connect-mcp') . '</h1>';
        echo '<p>' . esc_html__('Conecta Claude Code con este sitio para que pueda consultar categorías y plantillas, crear y editar entradas, completar Yoast, subir imágenes, programar, publicar y enviar a la papelera. Solo modifica, publica y borra contenido creado con tu usuario, solo publica o sube archivos si tu rol lo permite y solo usa las herramientas que un administrador te haya dado.', 'wp-connect-mcp') . '</p>';

        if (!wp_is_application_passwords_available_for_user($user)) {
            self::renderUnavailable();
            echo '</div>';
            return;
        }

        if (!Grants::hasAny((int) $user->ID)) {
            self::renderNoAccess();
            self::renderConnections($user);
            echo '</div>';
            return;
        }

        if (is_wp_error($result)) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($result->get_error_message()));
        }

        if (is_array($result)) {
            self::renderCommand($result, $user);
        } else {
            self::renderStart($user);
        }

        self::renderConnections($user);
        echo '</div>';
    }

    /**
     * @return array{server: string, revoked: int, password: string, unix: string, windows: string}|WP_Error
     */
    private static function createConnection(WP_User $user)
    {
        if (empty($_POST['wp_connect_mcp_confirm'])) {
            return new WP_Error('wp_connect_mcp_not_confirmed', __('Marca la casilla para confirmar con qué usuario se crea la conexión.', 'wp-connect-mcp'));
        }

        if (!Grants::hasAny((int) $user->ID)) {
            return new WP_Error('wp_connect_mcp_no_access', __('Tu usuario no tiene acceso a Claude en este sitio. Pide a un administrador que te lo dé.', 'wp-connect-mcp'));
        }

        $endpoint = self::endpoint();

        // Estos valores van dentro de los comandos entre comillas dobles: solo caracteres que no interpreten bash, PowerShell
        // ni cmd.exe (en Windows, claude suele ser un claude.cmd).
        $site = home_url();
        $url = '#^https?://[A-Za-z0-9._~:/?=+-]+$#';
        if (!preg_match('/^[A-Za-z0-9 _.@-]+$/', $user->user_login) || !preg_match($url, $endpoint) || !preg_match($url, $site)) {
            return new WP_Error('wp_connect_mcp_unsafe_value', __('El nombre de usuario o la dirección del sitio tienen caracteres que el comando no admite. Usa los bloques de la terminal del README o pide ayuda a soporte técnico.', 'wp-connect-mcp'));
        }

        $revoked = empty($_POST['wp_connect_mcp_revoke_previous']) ? 0 : Grants::revokeConnections((int) $user->ID, (int) $user->ID);

        $created = WP_Application_Passwords::create_new_application_password($user->ID, [
            'name' => sprintf('Claude Code · %s', wp_date('d/m/Y H:i:s')),
            'app_id' => self::APP_ID,
        ]);

        if (is_wp_error($created)) {
            return $created;
        }

        $values = [
            '__SITE__' => $site,
            '__MCP__' => $endpoint,
            '__NAME__' => self::serverName(),
            '__USER__' => $user->user_login,
        ];

        return [
            'server' => $values['__NAME__'],
            'revoked' => $revoked,
            'password' => WP_Application_Passwords::chunk_password($created[0]),
            'unix' => strtr(self::UNIX_BLOCK, $values),
            'windows' => strtr(self::WINDOWS_BLOCK, $values),
        ];
    }

    private static function renderStart(WP_User $user): void
    {
        echo '<div class="card">';
        echo '<h2>' . esc_html__('Cómo conectarse', 'wp-connect-mcp') . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__('Comprueba el usuario de abajo, marca la casilla y pulsa «Crear conexión».', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Copia el comando de tu sistema (Mac y Linux, o Windows) con su botón «Copiar».', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Abre la terminal: en Mac, «Terminal»; en Windows, «PowerShell»; en Linux, «Terminal». Pega el comando y pulsa Intro.', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Cuando el comando te la pida, copia la contraseña de aplicación de esta página y pégala en la terminal.', 'wp-connect-mcp') . '</li>';
        echo '<li>' . esc_html__('Al terminar, abre una sesión nueva de Claude Code (las que ya estaban abiertas no verán el sitio) y escribe /mcp para comprobar que aparece conectado.', 'wp-connect-mcp') . '</li>';
        echo '</ol>';

        self::renderUserBox($user);

        echo '<form method="post">';
        wp_nonce_field(self::ACTION);
        printf(
            '<p><label><input type="checkbox" name="wp_connect_mcp_confirm" value="1" required> %s</label></p>',
            esc_html(sprintf(
                /* translators: 1: usuario, 2: rol */
                __('Sí, crear la conexión para %1$s (%2$s).', 'wp-connect-mcp'),
                $user->user_login,
                self::roleLabel($user)
            ))
        );
        printf(
            '<p><label><input type="checkbox" name="wp_connect_mcp_revoke_previous" value="1" checked> %s</label></p>',
            esc_html__('Revocar mis conexiones anteriores con Claude (recomendado: las contraseñas antiguas o perdidas dejan de valer). Desmárcalo solo si usas Claude en otro equipo.', 'wp-connect-mcp')
        );
        submit_button(esc_html__('Crear conexión', 'wp-connect-mcp'), 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';
    }

    /**
     * Usuario y rol con los que se crea la conexión: Claude actuará en su nombre.
     */
    private static function renderUserBox(WP_User $user): void
    {
        echo '<div class="wp-connect-mcp-user">';
        echo '<p>' . esc_html__('La conexión se creará para este usuario. Claude actuará en su nombre:', 'wp-connect-mcp') . '</p>';
        printf(
            '<p class="wp-connect-mcp-user-name"><strong>%s</strong> (%s) · %s</p>',
            esc_html($user->display_name),
            esc_html($user->user_login),
            esc_html(self::roleLabel($user))
        );
        printf(
            '<p>%s %s</p>',
            esc_html__('Herramientas que te ha dado un administrador:', 'wp-connect-mcp'),
            esc_html(self::packagesLabel($user))
        );
        echo '<p>' . esc_html__('Si no es el usuario que quieres usar, cierra la sesión y entra con el correcto.', 'wp-connect-mcp') . '</p>';

        if (user_can($user, 'edit_others_posts')) {
            echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__('Este usuario puede ver y gestionar contenido de otros usuarios.', 'wp-connect-mcp') . '</strong> ';
            echo esc_html__('Si la contraseña se filtra, sirve para todo lo que permite su rol. Recomendado: un usuario propio para Claude con rol Autor.', 'wp-connect-mcp') . '</p></div>';
        }
        echo '</div>';
    }

    /**
     * @param array{server: string, revoked: int, password: string, unix: string, windows: string} $result
     */
    private static function renderCommand(array $result, WP_User $user): void
    {
        printf(
            '<div class="notice notice-success"><p>%s <strong>%s</strong> (%s) · %s. %s</p></div>',
            esc_html__('Conexión creada para el usuario', 'wp-connect-mcp'),
            esc_html($user->display_name),
            esc_html($user->user_login),
            esc_html(self::roleLabel($user)),
            esc_html__('Sigue los pasos ahora: por seguridad, la contraseña no se volverá a mostrar.', 'wp-connect-mcp')
        );

        if ($result['revoked']) {
            printf(
                '<div class="notice notice-info"><p>%s</p></div>',
                /* translators: %d: número de conexiones revocadas */
                esc_html(sprintf(__('Se han revocado %d conexiones anteriores: en otros equipos habrá que volver a conectar.', 'wp-connect-mcp'), $result['revoked']))
            );
        }

        echo '<div class="card wp-connect-mcp-card">';
        echo '<h2>' . esc_html__('1. Copia el comando de tu sistema', 'wp-connect-mcp') . '</h2>';
        echo '<p>' . esc_html__('El comando no lleva la contraseña: la pedirá al ejecutarlo.', 'wp-connect-mcp') . '</p>';
        echo '<nav class="nav-tab-wrapper wp-connect-mcp-tabs" hidden>';
        printf('<a href="#wp-connect-mcp-unix" class="nav-tab" data-wp-connect-mcp-tab="unix">%s</a>', esc_html__('Mac y Linux', 'wp-connect-mcp'));
        printf('<a href="#wp-connect-mcp-windows" class="nav-tab" data-wp-connect-mcp-tab="windows">%s</a>', esc_html__('Windows (PowerShell)', 'wp-connect-mcp'));
        echo '</nav>';
        self::renderBlock('unix', __('Mac y Linux (Terminal)', 'wp-connect-mcp'), $result['unix']);
        self::renderBlock('windows', __('Windows (PowerShell, no «Símbolo del sistema»)', 'wp-connect-mcp'), $result['windows']);

        echo '<h2>' . esc_html__('2. Pégalo en la terminal', 'wp-connect-mcp') . '</h2>';
        echo '<p>' . esc_html__('Abre la terminal: en Mac, «Terminal»; en Windows, «PowerShell»; en Linux, «Terminal». Pega el comando entero y pulsa Intro.', 'wp-connect-mcp') . '</p>';

        echo '<h2>' . esc_html__('3. Cuando te la pida, pega la contraseña de aplicación', 'wp-connect-mcp') . '</h2>';
        printf(
            '<textarea id="wp-connect-mcp-password" class="large-text code" rows="1" readonly onclick="this.select()">%s</textarea>',
            esc_textarea($result['password'])
        );
        printf(
            '<p><button type="button" class="button" data-wp-connect-mcp-copy="wp-connect-mcp-password">%s</button></p>',
            esc_html__('Copiar', 'wp-connect-mcp')
        );
        echo '<p><strong>' . esc_html__('No la pegues en ningún chat, tampoco en el de Claude: solo en la terminal, cuando el comando la pida.', 'wp-connect-mcp') . '</strong> ';
        echo esc_html__('Pégala solo cuando la terminal muestre la pregunta; si el comando se detiene antes con un error, no la pegues. No se ve mientras la pegas: es normal. Se muestra una sola vez; si la pierdes, crea otra conexión. Si crees que alguien la ha visto, revoca la conexión abajo, en tu perfil.', 'wp-connect-mcp') . '</p>';

        echo '<h2>' . esc_html__('4. Comprueba la conexión', 'wp-connect-mcp') . '</h2>';
        echo '<p>' . esc_html__('Abre una sesión nueva de Claude Code y escribe /mcp: debe aparecer como conectado:', 'wp-connect-mcp') . ' <code>' . esc_html($result['server']) . '</code></p>';
        echo '</div>';
    }

    private static function renderBlock(string $key, string $title, string $command): void
    {
        $id = 'wp-connect-mcp-' . $key;

        printf('<div id="%s" class="wp-connect-mcp-panel" data-wp-connect-mcp-panel="%s">', esc_attr($id), esc_attr($key));
        echo '<h3>' . esc_html($title) . '</h3>';
        printf(
            '<textarea id="%s-command" class="large-text code" rows="10" readonly onclick="this.select()">%s</textarea>',
            esc_attr($id),
            esc_textarea($command)
        );
        printf(
            '<p><button type="button" class="button button-primary" data-wp-connect-mcp-copy="%s-command">%s</button></p>',
            esc_attr($id),
            esc_html__('Copiar', 'wp-connect-mcp')
        );
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

    private static function renderNoAccess(): void
    {
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Tu usuario no tiene acceso a Claude en este sitio, así que no puedes crear la conexión ni usar las que ya tengas. Pide a un administrador que te dé acceso en Usuarios → Acceso de Claude.', 'wp-connect-mcp');
        echo '</p></div>';

        if (current_user_can('promote_users')) {
            printf('<p><a class="button" href="%s">%s</a></p>', esc_url(GrantsPage::url()), esc_html__('Ir a Acceso de Claude', 'wp-connect-mcp'));
        }
    }

    private static function packagesLabel(WP_User $user): string
    {
        return implode(', ', array_map(static function (string $package): string {
            return Grants::PACKAGES[$package]['label'];
        }, Grants::packagesOf((int) $user->ID)));
    }

    private static function renderUnavailable(): void
    {
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('Las contraseñas de aplicación no están disponibles para tu usuario en este sitio, así que no se puede crear la conexión. Suele deberse a que el sitio no usa HTTPS o a que un plugin de seguridad las ha desactivado. Avisa a soporte técnico.', 'wp-connect-mcp');
        echo '</p></div>';
    }

    private static function roleLabel(WP_User $user): string
    {
        $names = wp_roles()->role_names;
        $roles = array_map(static function (string $role) use ($names): string {
            return isset($names[$role]) ? translate_user_role($names[$role]) : $role;
        }, array_values((array) $user->roles));

        return $roles ? implode(', ', $roles) : __('sin rol', 'wp-connect-mcp');
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
        <style>
        .wp-connect-mcp-user { margin: 1em 0; padding: 0.5em 1em; background: #f6f7f7; border-left: 4px solid #2271b1; }
        .wp-connect-mcp-user-name { font-size: 1.3em; }
        .wp-connect-mcp-card { max-width: 60rem; }
        .wp-connect-mcp-tabs { margin-bottom: 0.5em; }
        </style>
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

        (function () {
            var tabs = document.querySelector('.wp-connect-mcp-tabs');
            if (!tabs) {
                return;
            }
            var show = function (key) {
                tabs.querySelectorAll('[data-wp-connect-mcp-tab]').forEach(function (tab) {
                    tab.classList.toggle('nav-tab-active', tab.getAttribute('data-wp-connect-mcp-tab') === key);
                });
                document.querySelectorAll('[data-wp-connect-mcp-panel]').forEach(function (panel) {
                    panel.hidden = panel.getAttribute('data-wp-connect-mcp-panel') !== key;
                });
            };
            tabs.hidden = false;
            tabs.addEventListener('click', function (event) {
                var tab = event.target.closest('[data-wp-connect-mcp-tab]');
                if (tab) {
                    event.preventDefault();
                    show(tab.getAttribute('data-wp-connect-mcp-tab'));
                }
            });
            show(/Windows/i.test(navigator.userAgent) ? 'windows' : 'unix');
        })();
        </script>
        <?php
    }
}
