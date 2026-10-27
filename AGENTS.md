# AGENTS.md

- **Antes de empezar, leer [ESTADO.md](ESTADO.md)**: situación actual, pendientes, decisiones y pruebas. **Al terminar cada paso, actualizarlo**:
  - situación actual;
  - casillas de pendientes;
  - fila en la tabla de pruebas o de decisiones, con la fecha;
  - entrada en el historial.

  Lo más reciente va arriba en cada sección.

- Plugin de WordPress (PHP 7.4+, WP 6.9+). Código propio en `includes/`, PSR-4 `Wwwarcelona\WpConnect\`; estilo PSR-12 con `defined('ABSPATH') || exit;` en cada fichero.
- `vendor/` se versiona. Tras añadir o mover clases: `composer dump-autoload`. No editar nada dentro de `vendor/`.
- No usar clases de `vendor/` antes de `plugins_loaded` (autoloader de Jetpack).
- Alcance deliberadamente limitado: solo entradas (`post`), sin publicar ni borrar. Cualquier ability nueva debe añadirse a `Plugin::ABILITIES`, comprobar capacidades en `permission_callback` (las generales, como `edit_posts`, antes de cargar nada, para no revelar qué existe) y declarar `input_schema` con `additionalProperties: false` y `output_schema`.
- Las reglas de estado y programación viven en `Support/PostInput.php`; no duplicarlas en las abilities.
- Verificación: `php -l` de los ficheros tocados y, en un WordPress de pruebas, `tools/list` y `tools/call` contra `/wp-json/wwwarcelona/mcp`. Los pasos con `curl` (sesión, cabeceras) y la lista de comprobación están en la sección «Probar con curl» del README.
- En comandos de prueba y documentación no depender de herramientas que haya que instalar (`jq` y similares): usar solo `curl` y `php -r` para leer el JSON.
- La forma principal de conectar Claude Code es la página **Herramientas → Conectar con Claude** (`includes/Admin/ConnectPage.php`). Al tocarla:
  - las instrucciones de la página y las del README («Conectar Claude Code desde el escritorio») deben decir lo mismo;
  - el comando generado debe seguir siendo **una sola línea** válida en bash, zsh, PowerShell y cmd: solo comillas dobles, sin `$`, comillas simples, acentos graves ni `&`;
  - la contraseña se muestra una sola vez y no se guarda;
  - todo texto que se imprima se escapa (`esc_html`, `esc_textarea`, `esc_url`);
  - el formulario lleva nonce;
  - si se cambia el formato del nombre de la conexión (`wwwarcelona-<dominio>`), cambiarlo también en los bloques de terminal.
- La alternativa son los **bloques para copiar y pegar** de la sección «Alternativa: desde la terminal» del README: uno para Linux y macOS, otro para Windows (PowerShell). **No crear ficheros ejecutables para usuarios finales.** Los usan personas no técnicas:
  - pedir siempre los datos de forma interactiva y confirmar el sitio (nombre y dirección) antes de seguir;
  - mensajes en español llano, que digan qué hacer;
  - validar usuario, contraseña y plugin antes de guardar nada;
  - sin dependencias aparte de `curl`/`claude` (Unix) o PowerShell/`claude` (Windows). No usar `php` ni `jq` en estos bloques: macOS no trae PHP;
  - Unix: dentro de `bash <<'EOF' … EOF` con `read … </dev/tty`, para que funcione también en zsh;
  - Windows: todo dentro de `& { … }` y `return` en lugar de `exit`, para no cerrar la ventana. Válido en PowerShell 5.1 y 7.

  Los dos bloques hacen lo mismo: si se cambia uno, cambiar el otro. Tras tocarlos, probar los caminos de error con datos falsos (en Unix, quitando `</dev/tty` y pasando las respuestas por tubería).
- Los comandos de la documentación deben poder pegarse tal cual. Nada de huecos tipo `<sitio>`: en bash, `<` es una redirección y el comando falla. Los datos variables se piden con `read`.
- Nunca escribir Application Passwords en ficheros del repositorio ni en comandos que queden en el historial.
