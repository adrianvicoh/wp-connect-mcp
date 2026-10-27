# wwwarcelona WP Connect

Plugin de WordPress que expone un servidor MCP propio para que Claude u otros agentes trabajen con **entradas (posts)** con permisos muy limitados.

## Qué puede hacer el agente

| Tool MCP | Qué hace |
|---|---|
| `wwwarcelona-list-posts` | Lista entradas editables por el usuario (búsqueda, estado, paginación). |
| `wwwarcelona-get-post` | Lee una entrada: contenido sin procesar, extracto, estado, fechas, categorías y etiquetas. |
| `wwwarcelona-create-post` | Crea una entrada como borrador, pendiente o programada. |
| `wwwarcelona-update-post` | Modifica solo los campos indicados de una entrada. |

Límites:

- Solo el tipo `post`; páginas y tipos personalizados se rechazan.
- **No publica.** Estados permitidos: `draft`, `pending` y `future` (programada, con `scheduled_date` futura). Programar exige que el usuario pueda publicar.
- En entradas ya publicadas (o privadas) se puede editar el contenido, que cambia en directo, pero no su estado ni su fecha.
- No borra, no sube medios y no toca otras partes del sitio. El servidor solo expone estas cuatro tools, no las meta-tools de descubrimiento del servidor MCP por defecto.
- Cada acción respeta las capacidades del usuario de WordPress (`edit_posts`, `edit_post`, `publish_post`, asignar categorías y etiquetas). Sin `edit_posts` (por ejemplo, un Suscriptor) todas las herramientas responden `Permission denied`, sin revelar si un ID existe.
- Las sesiones MCP van ligadas al usuario que las abre: no se pueden usar con las credenciales de otro.
- El contenido se filtra con `wp_kses_post`, también para administradores.

## Requisitos

- WordPress 6.9 o superior (Abilities API en el núcleo).
- PHP 7.4 o superior.
- HTTPS y Application Passwords activos.

El paquete `wordpress/mcp-adapter` va incluido en `vendor/` y se carga con el autoloader de Jetpack. Si otro plugin trae una versión más nueva del adapter, se usa esa. Al cargarse como librería, el adapter deja un aviso de deprecación en el log de WordPress.

## Instalación

1. Copiar la carpeta del plugin a `wp-content/plugins/wwwarcelona-wp-connect/`, **con `vendor/` incluido** (no hace falta Composer en el servidor). Para actualizar basta con subir los ficheros cambiados.
2. Activarlo en *Plugins*. Si WordPress es anterior a 6.9, aparece un aviso y el plugin no hace nada.

## Conexión

1. Crear un usuario con el rol mínimo necesario (Autor para programar; Colaborador si solo debe dejar pendientes; un Suscriptor no puede usar nada) y generarle una **Application Password** en *Usuarios → Editar usuario → Contraseñas de aplicación*, en el perfil de ese usuario.
2. Endpoint: `https://<sitio>/wp-json/wwwarcelona/mcp`
3. En Claude Code (pendiente de probar; ver [ESTADO.md](ESTADO.md)):

   ```bash
   claude mcp add --transport http wwwarcelona-<sitio> https://<sitio>/wp-json/wwwarcelona/mcp \
     --header "Authorization: Basic $(printf '%s' 'usuario:xxxx xxxx xxxx xxxx xxxx xxxx' | base64)"
   ```

## Probar con curl

El transporte HTTP del adapter funciona con sesión, en tres pasos: abrir sesión (`initialize`), confirmarla (`notifications/initialized`) y hacer las peticiones. Todas van por POST al mismo endpoint, con Basic Auth y Application Password. A partir del segundo paso se envían las cabeceras `Mcp-Session-Id` y `MCP-Protocol-Version`.

Solo hacen falta `curl` y `php` (para leer el JSON); no hay que instalar `jq` ni nada más.

El paso 0 comprueba la Application Password contra la API normal de WordPress, sin el plugin: si ahí falla, el problema no es del MCP.

```bash
SITE="https://test.wwwarcelona.net"
WP_USER="usuario-del-agente"
read -s -p "Application Password: " WP_APP_PASS; echo   # no queda en el historial
MCP="$SITE/wp-json/wwwarcelona/mcp"
PV="2025-11-25"

# 0. Comprobar credenciales: debe devolver el usuario
curl -sS "$SITE/wp-json/wp/v2/users/me" -u "$WP_USER:$WP_APP_PASS"; echo

# 1. Abrir sesión y guardar el ID
curl -sS -D headers.txt "$MCP" -u "$WP_USER:$WP_APP_PASS" \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"'"$PV"'","capabilities":{},"clientInfo":{"name":"curl","version":"1.0"}}}'
SID=$(grep -i '^mcp-session-id:' headers.txt | awk '{print $2}' | tr -d '\r')

# 2. Confirmar la sesión
curl -sS "$MCP" -u "$WP_USER:$WP_APP_PASS" \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","method":"notifications/initialized"}'

# 3. Listar herramientas: deben salir exactamente las cuatro
curl -sS "$MCP" -u "$WP_USER:$WP_APP_PASS" \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' \
  | php -r '$r = json_decode(stream_get_contents(STDIN), true); foreach ($r["result"]["tools"] ?? [] as $t) echo $t["name"], PHP_EOL; if (isset($r["error"])) print_r($r["error"]);'
```

Para llamar a una herramienta, con la misma sesión:

```bash
curl -sS "$MCP" -u "$WP_USER:$WP_APP_PASS" \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"wwwarcelona-list-posts","arguments":{"per_page":5}}}' \
  | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;'
```

Para no repetir cabeceras en cada llamada, se puede definir esta función en la terminal (con la sesión ya abierta). Muestra `OK`, `RECHAZADO` o `ERROR JSON-RPC`:

```bash
mcp_call() {
  curl -sS "$MCP" -u "$WP_USER:$WP_APP_PASS" \
    -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
    -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
    -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"'"$1"'","arguments":'"$2"'}}' \
  | php -r '$r = json_decode(stream_get_contents(STDIN), true); $res = $r["result"] ?? null;
    if (!$res) { echo "ERROR JSON-RPC: ", json_encode($r["error"] ?? $r, JSON_UNESCAPED_UNICODE), PHP_EOL; exit; }
    if (!empty($res["isError"])) { echo "RECHAZADO: ", $res["content"][0]["text"] ?? "", PHP_EOL; exit; }
    $p = $res["structuredContent"]; echo "OK: ", $p["id"] ?? "", " ", $p["status"] ?? "", " ", $p["date"] ?? "", PHP_EOL;'
}

mcp_call wwwarcelona-create-post '{"title":"Prueba MCP"}'
mcp_call wwwarcelona-update-post '{"id":123,"status":"publish"}'          # debe dar RECHAZADO
WHEN=$(date -d '+10 minutes' +%Y-%m-%dT%H:%M:%S%:z)
mcp_call wwwarcelona-update-post '{"id":123,"scheduled_date":"'"$WHEN"'"}' # debe dar OK ... future
```

Los errores de las herramientas llegan dentro de `result`, con `isError: true` y el mensaje en `content[0].text`. Los rechazos por esquema (estado no permitido, campo desconocido) llegan con el texto de WordPress «tiene una entrada no válida».

### Lista de comprobación

Los resultados de cada ronda de pruebas se anotan en [ESTADO.md](ESTADO.md).

- `tools/list` devuelve exactamente `wwwarcelona-list-posts`, `wwwarcelona-get-post`, `wwwarcelona-create-post` y `wwwarcelona-update-post`.
- Crear un borrador, leerlo y editar su título funciona.
- Programar con `scheduled_date` a 10 minutos deja la entrada en `future`, y se publica sola a esa hora. El cron de WordPress solo se ejecuta cuando alguien visita el sitio, así que puede tardar algo más.
- Fallan como deben: `status: publish`, el ID de una página, una fecha pasada y un usuario Suscriptor.
- Funciona junto a otros plugins que usan el adapter MCP (Novamira).

### Problemas habituales

| Síntoma | Causa probable |
|---|---|
| `404` / `rest_no_route` | Plugin inactivo o WordPress anterior a 6.9 (sale un aviso en el escritorio). |
| `401` `incorrect_password` | El usuario existe, pero la Application Password no vale: es de otro sitio u otro usuario, o se pegó incompleta (`echo ${#WP_APP_PASS}` debe dar 24 o 29). Generar una nueva en el perfil de ese usuario. |
| `401` `invalid_username` | El usuario no existe en ese sitio. |
| `401` sin código de WordPress | El servidor no pasa la cabecera `Authorization` a PHP (Apache con FastCGI). |
| `curl: (23) Failure writing output` | Falla el comando que va detrás de la tubería (por ejemplo, `jq` no instalado). Usar las líneas con `php -r`. |
| `Missing Mcp-Session-Id header` | `$SID` vacío: revisar `headers.txt`. |
| `-32005 Session not found` | La sesión no es de ese usuario o ha caducado. Al cambiar `WP_USER` hay que repetir los pasos 1 y 2: cada sesión va ligada al usuario que la abrió. |
| Error de `MCP-Protocol-Version` | Falta esa cabecera en los pasos 2 y siguientes. |
| Menos de cuatro herramientas | Revisar el log de PHP; los errores del plugin llevan el prefijo `[wwwarcelona-wp-connect]`. |

## Desarrollo

```bash
composer install          # o composer dump-autoload tras añadir clases
```

El estado del proyecto, los pendientes, las decisiones y el historial de pruebas están en [ESTADO.md](ESTADO.md).

Código propio en `includes/` (PSR-4 `Wwwarcelona\WpConnect\`):

- `Plugin.php`: registra la categoría, las abilities y el servidor MCP.
- `Abilities/`: una clase por ability (`NAME`, `register()`, `execute()`).
- `Support/PostInput.php`: saneado y reglas de estado y programación.
- `Support/PostAccess.php`: carga de la entrada y comprobación de permisos.
- `Support/PostFormatter.php`: formato y esquemas de salida.
