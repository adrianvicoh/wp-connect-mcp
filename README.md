# wwwarcelona WP Connect

Plugin de WordPress que expone un servidor MCP propio para que Claude u otros agentes trabajen con **entradas (posts)** con permisos muy limitados.

## Qué puede hacer el agente

| Tool MCP | Qué hace |
|---|---|
| `wwwarcelona-list-posts` | Lista entradas editables por el usuario (búsqueda, estado, paginación). |
| `wwwarcelona-get-post` | Lee una entrada: contenido sin procesar, extracto, estado, fechas, categorías y etiquetas. |
| `wwwarcelona-create-post` | Crea una entrada como borrador, pendiente o programada. |
| `wwwarcelona-update-post` | Modifica solo los campos indicados de una entrada. |

Campos de cada herramienta (Claude los ve con su descripción en `tools/list`):

| Tool | Campos |
|---|---|
| `list-posts` | `search` (texto en título y contenido), `status` (lista de `draft`, `pending`, `future`, `publish`; por defecto todos), `per_page` (1–50, por defecto 20), `page`. Ordena por última modificación. |
| `get-post` | `id` (obligatorio). |
| `create-post` | `title` (obligatorio), `content`, `excerpt`, `status`, `scheduled_date`, `categories`, `tags`. |
| `update-post` | `id` (obligatorio) y cualquiera de los campos de `create-post`. Solo cambia los que se envían. |

- `content`: HTML o marcado de bloques; se filtra con las etiquetas permitidas en entradas.
- `status`: `draft` (por defecto), `pending` o `future`.
- `scheduled_date`: ISO 8601, en el futuro (por ejemplo, `2026-10-28T09:00:00+01:00`). Sin zona horaria se usa la del sitio. Implica `future`.
- `categories`: IDs de categorías existentes. `tags`: nombres; las que no existen se crean. Las dos listas sustituyen a las actuales.
- Cualquier otro campo (por ejemplo, `post_type` o `author`) se rechaza.

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

1. Copiar la carpeta del plugin a `wp-content/plugins/wwwarcelona-wp-connect/`, **con `vendor/` incluido** (no hace falta Composer en el servidor).
2. Activarlo en *Plugins*. Si WordPress es anterior a 6.9, aparece un aviso y el plugin no hace nada.
3. Crear el usuario que va a usar Claude (ver «Conexión») y conectarlo desde *Herramientas → Conectar con Claude*.

Para actualizar, basta con subir los ficheros cambiados; no hay que reactivar el plugin. Las conexiones ya creadas siguen funcionando.

## Conexión

El usuario con el que se conecta Claude determina lo que puede hacer:

| Rol | Qué puede hacer Claude |
|---|---|
| **Colaborador** | Crear y editar sus propias entradas como borrador o pendientes. No puede programar. |
| **Autor** | Lo mismo, y además programar. Solo ve y edita sus propias entradas: las de otros usuarios dan `Permission denied`. |
| **Editor** | Ver, editar y programar las entradas de cualquier usuario. |
| **Administrador** | Lo mismo que el Editor. No recomendado: si la credencial se filtra, es la de un administrador. |
| **Suscriptor** | Nada: todas las herramientas dan `Permission denied`. |

Ningún rol permite a Claude publicar ni borrar a través de este plugin. Lo recomendable es un usuario propio para Claude, con rol Autor (o Colaborador si una persona debe revisarlo todo), no el de una persona ni el de un administrador.

Endpoint: `https://<sitio>/wp-json/wwwarcelona/mcp` (es el dato que se usa en los comandos de abajo, no un comando).

La forma recomendada es desde el escritorio de WordPress (probada en Linux; falta probarla en Windows y Mac, ver [ESTADO.md](ESTADO.md)).

### Conectar Claude Code desde el escritorio (recomendado)

Para cualquier persona, también si no es informática. No hay que saber el usuario, la contraseña ni la dirección del sitio.

1. Entrar en el WordPress con el usuario que va a usar Claude.
2. Ir a **Herramientas → Conectar con Claude** y pulsar **Crear conexión**.
3. Pulsar **Copiar** en el comando que aparece.
4. Abrir la terminal (Mac: *Terminal*; Windows: *PowerShell*; Linux: *Terminal*), pegar el comando y pulsar Intro.
5. Abrir una sesión nueva de Claude Code y escribir `/mcp`: el sitio debe aparecer conectado y con 4 herramientas.

La página explica estos mismos pasos en pantalla.

Qué hace la página por dentro:

- **Contraseña de aplicación:** crea una para el usuario conectado, con el nombre «Claude Code · fecha» y el identificador de aplicación del plugin (`ConnectPage::APP_ID`).
- **Comando de una línea:** `claude mcp add --transport http --scope user "<nombre>" "<endpoint>" --header "Authorization: Basic <credencial>"`. Solo usa comillas dobles y caracteres de base64, así que funciona igual en bash, zsh, PowerShell y el Símbolo del sistema.
- **Se muestra una sola vez:** si se pierde, se crea otra conexión.
- **Nombre de la conexión:** `wwwarcelona-` más el dominio con guiones (por ejemplo, `wwwarcelona-test-wwwarcelona-net`). Si ya existía, la terminal dice que ya existe; la página indica el `claude mcp remove` que hay que pegar antes.
- **Lista de conexiones:** muestra las del usuario con su último uso, y enlaza al perfil para revocarlas (*Contraseñas de aplicación*).
- **Si no están disponibles las contraseñas de aplicación** (sitio sin HTTPS o desactivadas por un plugin de seguridad), la página lo explica y no ofrece el botón.
- **Usuario de la conexión:** la conexión siempre es del usuario con el que se ha entrado en WordPress. La página lo indica (nombre y usuario) junto al botón y al crearla, porque Claude actuará en su nombre. Si no es el correcto, hay que cerrar sesión y entrar con el que toca.
- **Administradores:** si el usuario conectado es administrador, la página recomienda usar un usuario propio para Claude.
- **Quién la ve:** está en *Herramientas* para cualquier usuario con `edit_posts`, que es lo mínimo que necesitan las herramientas.

Contrapartida de seguridad: el comando lleva la credencial, así que queda en el historial de la terminal y en `~/.claude.json`. Es una contraseña de aplicación revocable, solo vale para ese usuario y no da acceso al escritorio. Si se sospecha que se ha visto, se revoca en el perfil y se crea otra.

### Alternativa: desde la terminal

Para cuando no se puede entrar en el escritorio de WordPress. Hay que crear antes la contraseña de aplicación a mano: *Usuarios → Editar usuario → Contraseñas de aplicación*, en el perfil del usuario que va a usar Claude.

No hay que descargar nada: se copia el bloque entero, se pega en la terminal y se pulsa Intro. El bloque pregunta, uno a uno:

1. la dirección del sitio (vale con o sin `https://`);
2. si el sitio que muestra (nombre y dirección) es el correcto;
3. el usuario de WordPress;
4. la contraseña de aplicación (no se ve mientras se escribe o se pega).

Antes de guardar nada comprueba que el usuario y la contraseña son correctos y que el sitio tiene el plugin activo. Si algo falla, explica en lenguaje claro qué revisar. Si el sitio ya estaba conectado, pregunta antes de sustituir la conexión. La contraseña no queda en el historial de la terminal.

Al terminar, el sitio queda conectado como `wwwarcelona-` más el dominio con guiones (por ejemplo, `wwwarcelona-test-wwwarcelona-net`). Después hay que abrir una sesión nueva de Claude Code y comprobar con `/mcp` que aparece conectado y con 4 herramientas.

#### Linux y macOS (Terminal)

Funciona igual en bash y en zsh (la terminal por defecto de macOS).

```bash
bash <<'EOF'
fallo() { printf '\n❌ %s\n' "$1"; [ -n "${2:-}" ] && printf '   %s\n' "$2"; exit 1; }
si() { case "$1" in s|S|si|sí|Si|Sí|SI|SÍ) return 0 ;; *) return 1 ;; esac; }
command -v claude >/dev/null 2>&1 || fallo "No se encuentra Claude Code en este equipo." "Instálalo primero y vuelve a pegar este bloque."
echo "Conectar Claude Code con un sitio WordPress"
echo "-------------------------------------------"
read -r -p "1/4 Dirección del sitio (por ejemplo, https://test.wwwarcelona.net): " SITE </dev/tty
SITE=$(printf '%s' "$SITE" | tr -d '[:space:]'); SITE=${SITE%/}
[ -n "$SITE" ] || fallo "No has escrito la dirección del sitio."
case "$SITE" in http://*|https://*) ;; *) SITE="https://$SITE" ;; esac
HOST=${SITE#*://}; HOST=${HOST%%/*}
RESP=$(curl -sS --max-time 20 -w '\n%{http_code}' "$SITE/wp-json/" 2>/dev/null)
[ "${RESP##*$'\n'}" != "000" ] || fallo "No se puede conectar con $SITE." "Revisa que la dirección esté bien escrita y que tengas internet."
SITE_NAME=$(printf '%s' "$RESP" | grep -o '"name":"[^"]*"' | head -1 | cut -d'"' -f4)
echo
echo "Vas a conectar Claude Code con este sitio:"
echo "   Nombre:    ${SITE_NAME:-(no disponible)}"
echo "   Dirección: $SITE"
read -r -p "2/4 ¿Es correcto? (s/n): " OK </dev/tty
si "$OK" || { echo "No se ha cambiado nada."; exit 0; }
read -r -p "3/4 Usuario de WordPress: " WP_USER </dev/tty
[ -n "$WP_USER" ] || fallo "No has escrito el usuario."
read -r -s -p "4/4 Contraseña de aplicación (no se verá mientras escribes o pegas): " WP_APP_PASS </dev/tty; echo
[ -n "$WP_APP_PASS" ] || fallo "No has escrito la contraseña de aplicación."
echo
echo "Comprobando el usuario y la contraseña…"
CODE=$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' -u "$WP_USER:$WP_APP_PASS" "$SITE/wp-json/wp/v2/users/me" 2>/dev/null)
case "$CODE" in
  200) echo "✅ Usuario y contraseña correctos." ;;
  401) fallo "El usuario o la contraseña de aplicación no son correctos." "Revisa el usuario y genera una contraseña nueva en WordPress: Usuarios → (el usuario) → Contraseñas de aplicación." ;;
  *)   fallo "El sitio ha respondido con un error ($CODE)." "Pide ayuda a soporte técnico y diles este número." ;;
esac
echo "Comprobando que el sitio tiene el plugin wwwarcelona WP Connect…"
MCP="$SITE/wp-json/wwwarcelona/mcp"
BODY=$(curl -sS --max-time 20 -u "$WP_USER:$WP_APP_PASS" "$MCP" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"conectar-claude","version":"1.0"}}}' 2>/dev/null)
case "$BODY" in
  *'"wwwarcelona WP Connect"'*) echo "✅ Plugin encontrado." ;;
  *) fallo "El sitio no tiene el plugin wwwarcelona WP Connect activo." "Pide a soporte técnico que lo instale y lo active." ;;
esac
NAME="wwwarcelona-$(printf '%s' "$HOST" | tr '.' '-' | tr -cd 'a-zA-Z0-9-')"
if claude mcp get "$NAME" >/dev/null 2>&1; then
  read -r -p "Este sitio ya estaba conectado. ¿Quieres sustituir la conexión? (s/n): " OK </dev/tty
  si "$OK" || { echo "No se ha cambiado nada."; exit 0; }
  claude mcp remove "$NAME" >/dev/null 2>&1
fi
AUTH=$(printf '%s' "$WP_USER:$WP_APP_PASS" | base64 | tr -d '\n')
claude mcp add --transport http --scope user "$NAME" "$MCP" --header "Authorization: Basic $AUTH" >/dev/null \
  || fallo "Claude Code no ha podido guardar la conexión." "Pide ayuda a soporte técnico."
echo
echo "✅ Listo. «${SITE_NAME:-$HOST}» está conectado en Claude Code como «$NAME»."
echo "   1. Abre una sesión nueva de Claude Code (las que ya estaban abiertas no lo verán)."
echo "   2. Escribe /mcp y comprueba que aparece «$NAME» como conectado."
echo "   3. Pídele, por ejemplo: «Lista las últimas entradas de $HOST»."
EOF
```

#### Windows (PowerShell)

Abrir **PowerShell** (no «Símbolo del sistema»), pegar el bloque entero y pulsar Intro. Este bloque aún no se ha probado en un Windows real; ver [ESTADO.md](ESTADO.md).

```powershell
& {
  [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12
  function Fallo($msg, $hint) { Write-Host ""; Write-Host "ERROR: $msg" -ForegroundColor Red; if ($hint) { Write-Host "   $hint" } }
  function Si($r) { return ("$r".Trim() -match '^(s|si|sí)$') }
  if (-not (Get-Command claude -ErrorAction SilentlyContinue)) { Fallo "No se encuentra Claude Code en este equipo." "Instálalo primero y vuelve a pegar este bloque."; return }
  Write-Host "Conectar Claude Code con un sitio WordPress"
  Write-Host "-------------------------------------------"
  $site = ("" + (Read-Host "1/4 Dirección del sitio (por ejemplo, https://test.wwwarcelona.net)")).Trim().TrimEnd('/')
  if (-not $site) { Fallo "No has escrito la dirección del sitio."; return }
  if ($site -notmatch '^https?://') { $site = "https://$site" }
  try { $siteHost = ([Uri]$site).Host } catch { Fallo "La dirección no es válida." "Escríbela como en el navegador, por ejemplo https://test.wwwarcelona.net"; return }
  $siteName = $null
  try { $siteName = (Invoke-RestMethod -Uri "$site/wp-json/" -TimeoutSec 20 -UseBasicParsing).name }
  catch { if (-not $_.Exception.Response) { Fallo "No se puede conectar con $site." "Revisa que la dirección esté bien escrita y que tengas internet."; return } }
  if (-not $siteName) { $siteName = "(no disponible)" }
  Write-Host ""
  Write-Host "Vas a conectar Claude Code con este sitio:"
  Write-Host "   Nombre:    $siteName"
  Write-Host "   Dirección: $site"
  if (-not (Si (Read-Host "2/4 ¿Es correcto? (s/n)"))) { Write-Host "No se ha cambiado nada."; return }
  $user = ("" + (Read-Host "3/4 Usuario de WordPress")).Trim()
  if (-not $user) { Fallo "No has escrito el usuario."; return }
  $sec = Read-Host "4/4 Contraseña de aplicación (no se verá mientras escribes o pegas)" -AsSecureString
  $pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
  if (-not $pass) { Fallo "No has escrito la contraseña de aplicación."; return }
  $auth = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($user + ':' + $pass))
  $headers = @{ Authorization = "Basic $auth" }
  Write-Host ""
  Write-Host "Comprobando el usuario y la contraseña..."
  try { Invoke-RestMethod -Uri "$site/wp-json/wp/v2/users/me" -Headers $headers -TimeoutSec 20 -UseBasicParsing | Out-Null; Write-Host "OK: usuario y contraseña correctos." -ForegroundColor Green }
  catch {
    $code = 0; if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
    if ($code -eq 401) { Fallo "El usuario o la contraseña de aplicación no son correctos." "Revisa el usuario y genera una contraseña nueva en WordPress: Usuarios -> (el usuario) -> Contraseñas de aplicación." }
    else { Fallo "El sitio ha respondido con un error ($code)." "Pide ayuda a soporte técnico y diles este número." }
    return
  }
  Write-Host "Comprobando que el sitio tiene el plugin wwwarcelona WP Connect..."
  $mcp = "$site/wp-json/wwwarcelona/mcp"
  $body = '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"conectar-claude","version":"1.0"}}}'
  $init = $null
  try { $init = Invoke-RestMethod -Method Post -Uri $mcp -Headers ($headers + @{ Accept = "application/json, text/event-stream" }) -ContentType "application/json" -Body $body -TimeoutSec 20 -UseBasicParsing } catch { }
  if ($init.result.serverInfo.name -ne "wwwarcelona WP Connect") { Fallo "El sitio no tiene el plugin wwwarcelona WP Connect activo." "Pide a soporte técnico que lo instale y lo active."; return }
  Write-Host "OK: plugin encontrado." -ForegroundColor Green
  $name = "wwwarcelona-" + (($siteHost -replace '\.', '-') -replace '[^a-zA-Z0-9-]', '')
  claude mcp get $name *> $null
  if ($LASTEXITCODE -eq 0) {
    if (-not (Si (Read-Host "Este sitio ya estaba conectado. ¿Quieres sustituir la conexión? (s/n)"))) { Write-Host "No se ha cambiado nada."; return }
    claude mcp remove $name *> $null
  }
  claude mcp add --transport http --scope user $name $mcp --header "Authorization: Basic $auth" *> $null
  if ($LASTEXITCODE -ne 0) { Fallo "Claude Code no ha podido guardar la conexión." "Pide ayuda a soporte técnico."; return }
  Write-Host ""
  Write-Host "Listo. «$siteName» está conectado en Claude Code como «$name»." -ForegroundColor Green
  Write-Host "   1. Abre una sesión nueva de Claude Code (las que ya estaban abiertas no lo verán)."
  Write-Host "   2. Escribe /mcp y comprueba que aparece «$name» como conectado."
  Write-Host "   3. Pídele, por ejemplo: «Lista las últimas entradas de $siteHost»."
}
```

#### Detalles para técnicos

- **`--scope user`:** el sitio queda disponible en todas las carpetas, guardado solo en ese equipo (`~/.claude.json`, que contiene la credencial en texto plano). No usar `--scope local` (solo vale en la carpeta desde la que se ejecuta) ni `--scope project` (escribe la credencial en un `.mcp.json` del repositorio).
- **Sin ficheros:** los bloques se pegan en la terminal en lugar de distribuirse como script. Así no hay que repartir ni actualizar ficheros, y en Windows no influye la política de ejecución de scripts.
- **macOS:** el bloque se ejecuta dentro de `bash <<'EOF' … EOF` (con `read … </dev/tty`) para que funcione aunque la terminal sea zsh. Las variables, contraseña incluida, desaparecen al terminar.
- **Sesión MCP:** Claude Code la gestiona solo.
- **Para quitar una conexión:** `claude mcp list` muestra el nombre y `claude mcp remove` seguido del nombre la borra.

## Uso con Claude Code

Con el sitio conectado, se le pide a Claude en lenguaje normal. Por ejemplo:

- «Lista mis últimas 5 entradas en test.wwwarcelona.net.»
- «Crea un borrador titulado "Novedades de octubre" con este texto: …»
- «Programa la entrada 2470 para el lunes a las 9:00.»
- «Cambia el extracto de la entrada 1439.»

Claude pide permiso antes de usar cada herramienta (salvo que se haya autorizado de forma permanente). Si se le pide publicar o borrar, responde que no puede: el plugin lo rechaza. Para publicar, una persona entra en el escritorio, revisa la entrada y la publica.

Las fechas sin zona horaria se interpretan en la zona del sitio (*Ajustes → Generales*). Las entradas programadas se publican solas a su hora con el cron de WordPress, que se ejecuta cuando alguien visita el sitio.

### Quitar una conexión

1. En la terminal: `claude mcp list` muestra el nombre (por ejemplo, `wwwarcelona-test-wwwarcelona-net`) y `claude mcp remove "wwwarcelona-test-wwwarcelona-net"` la borra del equipo.
2. En WordPress: *Herramientas → Conectar con Claude → Revocar conexiones en tu perfil*, y revocar la contraseña «Claude Code · fecha». Sin este paso, la credencial sigue siendo válida aunque se haya quitado del equipo.

## Seguridad y limitaciones conocidas

- **Otras conexiones al mismo sitio:** el límite de «no publicar ni borrar» solo vale para este plugin. Si el mismo Claude Code tiene también otra conexión con más permisos (por ejemplo, Novamira o un conector de claude.ai), podría publicar o borrar por esa vía. Para que el límite sea real, quien use esta conexión no debe tener otras al mismo sitio.
- **Entradas ya publicadas:** se puede editar su contenido, y el cambio se ve en directo; no se puede cambiar su estado ni su fecha. Pendiente de decidir si se prohíbe (ver [ESTADO.md](ESTADO.md)).
- **Etiquetas:** `tags` crea las que no existen, si el usuario puede asignar etiquetas.
- **Credencial en el equipo:** queda en `~/.claude.json` y en el historial de la terminal. Es revocable y solo vale para la API de ese usuario.
- **Log de WordPress:** si el sitio tiene `WP_DEBUG_LOG` activo, comprobar que `wp-content/debug.log` no se puede descargar desde internet. En `test.wwwarcelona.net` sí se podía; es un problema del sitio, no del plugin.
- **Aviso de deprecación:** el adapter MCP incluido deja un aviso en el log en cada petición. No afecta al funcionamiento.

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
- Fallan como deben: `status: publish`, `status: trash`, el ID de una página, una fecha pasada, un campo desconocido (`post_type`), un usuario Suscriptor y, con un Autor, una entrada de otro usuario.
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
| `Permission denied` con un Autor o Colaborador | La entrada es de otro usuario: estos roles solo editan las suyas. |
| Menos de cuatro herramientas | Revisar el log de PHP; los errores del plugin llevan el prefijo `[wwwarcelona-wp-connect]`. |

## Desarrollo

```bash
composer install          # o composer dump-autoload tras añadir clases
```

El estado del proyecto, los pendientes, las decisiones y el historial de pruebas están en [ESTADO.md](ESTADO.md).

Código propio en `includes/` (PSR-4 `Wwwarcelona\WpConnect\`):

- `Plugin.php`: registra la categoría, las abilities, el servidor MCP y, en el escritorio, la página de conexión.
- `Admin/ConnectPage.php`: página *Herramientas → Conectar con Claude* (crea la contraseña de aplicación y muestra el comando).
- `Abilities/`: una clase por ability (`NAME`, `register()`, `execute()`).
- `Support/PostInput.php`: saneado y reglas de estado y programación.
- `Support/PostAccess.php`: carga de la entrada y comprobación de permisos.
- `Support/PostFormatter.php`: formato y esquemas de salida.
