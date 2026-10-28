# AGENTS.md

- **Antes de empezar, leer [ESTADO.md](ESTADO.md)**: situación actual, pendientes, decisiones y pruebas. **Al terminar cada paso, actualizarlo**:
  - situación actual;
  - casillas de pendientes;
  - fila en la tabla de pruebas o de decisiones, con la fecha;
  - entrada en el historial.

  Lo más reciente va arriba en cada sección.

- Documentación: [README.md](README.md) para quien instala, conecta y usa el plugin; [ESTADO.md](ESTADO.md) para el día a día (pruebas, decisiones, pendientes, historial); [CHANGELOG.md](CHANGELOG.md) solo con cambios por versión; [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) para la comparación de alternativas, la seguridad y el control de funciones por paquetes (actualizarlo si cambia la estrategia). Si cambia una herramienta, un campo, un límite o un rol, actualizar las tablas del README («Qué puede hacer el agente», «Conexión») y «Seguridad y limitaciones conocidas».
- Plugin de WordPress (PHP 7.4+, WP 6.9+). Código propio en `includes/`, PSR-4 `WpConnectMcp\`; estilo PSR-12 con `defined('ABSPATH') || exit;` en cada fichero.
- `vendor/` se versiona. Tras añadir o mover clases: `composer dump-autoload`. No editar nada dentro de `vendor/`.
- No usar clases de `vendor/` antes de `plugins_loaded` (autoloader de Jetpack).
- Alcance deliberadamente limitado: solo entradas (`post`), sin borrar definitivamente. Modificar, publicar y enviar a la papelera, solo entradas propias (`PostAccess::canEditOwn()` y `canDeleteOwn()`), también si están publicadas; publicar y programar, solo si el usuario puede publicar. Cualquier ability nueva debe añadirse a `Plugin::ABILITIES`, comprobar capacidades en `permission_callback` (las generales, como `edit_posts`, antes de cargar nada, para no revelar qué existe) y declarar `input_schema` con `additionalProperties: false` y `output_schema`.
- Las reglas de estado y programación viven en `Support/PostInput.php`; no duplicarlas en las abilities.
- Despliegue en el sitio de pruebas: subir solo los ficheros cambiados a `wp-content/plugins/wp-connect-mcp/` (con `vendor/` si cambia). No hay paso de compilación.
- Zip de cada versión, para instalar desde *Plugins → Añadir nuevo → Subir plugin*: tras el commit de la versión, desde la raíz del repositorio y con el árbol limpio, generar `~/Descargas/wp-connect-mcp-<versión>.zip` (la versión sale de la cabecera `Version:` de `wp-connect-mcp.php`). Lleva solo `wp-connect-mcp.php`, `includes/` y `vendor/`, dentro de la carpeta `wp-connect-mcp/` (sin versión, para que WordPress lo instale siempre en la misma carpeta):

  ```bash
  V=$(sed -n 's/^ \* Version: *//p' wp-connect-mcp.php)
  git archive --format=zip --prefix=wp-connect-mcp/ -o ~/Descargas/wp-connect-mcp-$V.zip HEAD wp-connect-mcp.php includes vendor
  ```
- Verificación: `php -l` de los ficheros tocados y, en un WordPress de pruebas, `tools/list` y `tools/call` contra `/wp-json/wp-connect-mcp/mcp`. Los pasos con `curl` (sesión, cabeceras) y la lista de comprobación están en la sección «Probar con curl» del README.
- En comandos de prueba y documentación no depender de herramientas que haya que instalar (`jq` y similares): usar solo `curl` y `php -r` para leer el JSON.
- La forma principal de conectar Claude Code es la página **Herramientas → Conectar con Claude** (`includes/Admin/ConnectPage.php`). Al tocarla:
  - las instrucciones de la página y las del README («Conectar Claude Code desde el escritorio») deben decir lo mismo;
  - el comando generado debe seguir siendo **una sola línea** válida en bash, zsh, PowerShell y cmd: solo comillas dobles, sin `$`, comillas simples, acentos graves ni `&`;
  - la contraseña se muestra una sola vez y no se guarda;
  - la página muestra siempre con qué usuario se crea la conexión (antes de pulsar y al crearla): Claude actúa en su nombre;
  - todo texto que se imprima se escapa (`esc_html`, `esc_textarea`, `esc_url`);
  - el formulario lleva nonce;
  - si se cambia el formato del nombre de la conexión (`wp-connect-mcp-<dominio>`), cambiarlo también en los bloques de terminal.
- La alternativa son los **bloques para copiar y pegar** de la sección «Alternativa: desde la terminal» del README: uno para Linux y macOS, otro para Windows (PowerShell). **No crear ficheros ejecutables para usuarios finales.** Los usan personas no técnicas:
  - pedir siempre los datos de forma interactiva y confirmar el sitio (nombre y dirección) antes de seguir;
  - mensajes en español llano, que digan qué hacer;
  - validar usuario, contraseña y plugin antes de guardar nada;
  - sin dependencias aparte de `curl`/`claude` (Unix) o PowerShell/`claude` (Windows). No usar `php` ni `jq` en estos bloques: macOS no trae PHP;
  - Unix: dentro de `bash <<'EOF' … EOF` con `read … </dev/tty`, para que funcione también en zsh;
  - Windows: todo dentro de `& { … }` y `return` en lugar de `exit`, para no cerrar la ventana. Válido en PowerShell 5.1 y 7.

  Los dos bloques hacen lo mismo: si se cambia uno, cambiar el otro. Tras tocarlos, probar los caminos de error con datos falsos (en Unix, quitando `</dev/tty` y pasando las respuestas por tubería).
- Los comandos de la documentación deben poder pegarse tal cual. Nada de huecos tipo `<sitio>`: en bash, `<` es una redirección y el comando falla. Los datos variables se piden con `read`.
- Nunca escribir Application Passwords en ficheros del repositorio ni en comandos que queden en el historial. Para comprobar una conexión de Claude Code, leer la cabecera de `~/.claude.json` dentro de `php -r` y mostrar solo el usuario, nunca la contraseña.
- Antes de anotar una prueba en ESTADO.md, confirmar con qué usuario y rol se hizo (`wp/v2/users/me?context=edit`, o el autor de la entrada creada). El nombre visible puede no coincidir con el usuario, y los roles de los usuarios de prueba cambian.
- Al trabajar con un sitio conectado, no usar otras conexiones al mismo sitio (Novamira, conectores de claude.ai) para hacer lo que este plugin no permite (tocar entradas de otros, borrar definitivamente o trabajar con páginas). Si el usuario lo pide, explicar el límite.
