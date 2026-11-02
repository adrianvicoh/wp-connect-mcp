# AGENTS.md

- **Antes de empezar, leer [ESTADO.md](ESTADO.md)**: situación actual, pendientes, decisiones y pruebas. **Al terminar cada paso, actualizarlo**:
  - situación actual;
  - casillas de pendientes;
  - fila en la tabla de pruebas o de decisiones, con la fecha;
  - entrada en el historial.

  Lo más reciente va arriba en cada sección.

- Documentación: [README.md](README.md) para quien instala, conecta y usa el plugin; [ESTADO.md](ESTADO.md) para el día a día (pruebas, decisiones, pendientes, historial); [CHANGELOG.md](CHANGELOG.md) solo con cambios por versión; [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) para la comparación de alternativas, la seguridad y el control de funciones por paquetes (actualizarlo si cambia la estrategia); [docs/INFORME-SEGURIDAD.md](docs/INFORME-SEGURIDAD.md), versión local del informe para sistemas publicado como artifact (si se cambia uno, cambiar el otro). Si cambia una herramienta, un campo, un límite o un rol, actualizar las tablas del README («Qué puede hacer el agente», «Conexión») y «Seguridad y limitaciones conocidas».
- Plugin de WordPress (PHP 7.4+, WP 6.9+). Código propio en `includes/`, PSR-4 `WpConnectMcp\`; estilo PSR-12 con `defined('ABSPATH') || exit;` en cada fichero.
- `vendor/` se versiona. Tras añadir o mover clases: `composer dump-autoload`. No editar nada dentro de `vendor/`.
- No usar clases de `vendor/` antes de `plugins_loaded` (autoloader de Jetpack).
- Alcance deliberadamente limitado: solo entradas (`post`), sin borrar definitivamente. Modificar, publicar y enviar a la papelera, solo entradas propias (`PostAccess::canEditOwn()` y `canDeleteOwn()`), también si están publicadas; publicar y programar, solo si el usuario puede publicar. Cualquier ability nueva debe añadirse a `Plugin::ABILITIES` y a un paquete de `Security\Grants::PACKAGES` (si no, nadie podrá usarla), registrarse con `wp_register_ability(self::NAME, Guard::protect(self::NAME, [ … ]))`, comprobar capacidades en `permission_callback` (las generales, como `edit_posts`, antes de cargar nada, para no revelar qué existe) y declarar `input_schema` con `additionalProperties: false` y `output_schema`.
- Las reglas de estado y programación viven en `Support/PostInput.php`; no duplicarlas en las abilities.
- Seguridad en `includes/Security/`: las contraseñas creadas desde la página (`ConnectPage::APP_ID`) solo valen para el MCP y el MCP solo acepta esas (`ApplicationPasswordScope`, `Guard::canConnect()`). Sin paquetes no hay acceso. No quitar estas comprobaciones ni añadir rutas REST propias sin revisar `ApplicationPasswordScope::restrictRoute()`. Un paquete nuevo o ampliado se anota en el CHANGELOG y en el README (tabla de paquetes).
- Las abilities que escriben (`create-post`, `update-post`) responden con `PostFormatter::saved()`: sin el contenido salvo `return_content: true`, para no gastar contexto del agente. `schedule-posts` responde con `PostFormatter::summary()` de cada entrada. Solo `get-post` devuelve el contenido completo. Lo que dependa de la forma de una respuesta en `Security\Guard` (registro, límite por hora) debe limitarse a la ability concreta: `list-posts` también devuelve una lista `posts`.
- Despliegue en el sitio de pruebas: subir solo los ficheros cambiados a `wp-content/plugins/wp-connect-mcp/` (con `vendor/` si cambia). No hay paso de compilación.
- Zip de cada versión, para instalar desde *Plugins → Añadir nuevo → Subir plugin*: tras el commit de la versión, desde la raíz del repositorio y con el árbol limpio, generar `wp-connect-mcp-<versión>.zip` siempre en la raíz del repositorio, que lo excluye con `.gitignore`, borrando antes el zip anterior: solo queda el de la última versión (la versión sale de la cabecera `Version:` de `wp-connect-mcp.php`). Lleva solo `wp-connect-mcp.php`, `includes/` y `vendor/`, dentro de la carpeta `wp-connect-mcp/` (sin versión, para que WordPress lo instale siempre en la misma carpeta):

  ```bash
  V=$(sed -n 's/^ \* Version: *//p' wp-connect-mcp.php)
  rm -f wp-connect-mcp-*.zip
  git archive --format=zip --prefix=wp-connect-mcp/ -o wp-connect-mcp-$V.zip HEAD wp-connect-mcp.php includes vendor
  ```
- Verificación: `php -l` de los ficheros tocados y, en un WordPress de pruebas, `tools/list` y `tools/call` contra `/wp-json/wp-connect-mcp/mcp`. Los pasos con `curl` (sesión, cabeceras) y la lista de comprobación están en la sección «Probar con curl» del README.
- En comandos de prueba y documentación no depender de herramientas que haya que instalar (`jq` y similares): usar solo `curl` y `php -r` para leer el JSON.
- La forma principal de conectar Claude Code es la página **Herramientas → Conectar con Claude** (`includes/Admin/ConnectPage.php`). Al tocarla:
  - las instrucciones de la página y las del README («Conectar Claude Code desde el escritorio») deben decir lo mismo;
  - **los comandos no llevan nunca la contraseña.** La página da dos bloques, en pestañas: Mac y Linux (`UNIX_BLOCK`) y Windows PowerShell (`WINDOWS_BLOCK`), con el sitio, el endpoint, el nombre de la conexión y el usuario ya puestos. Piden la contraseña al ejecutarse, sin mostrarla, y la comprueban antes de guardar nada. Siguen las mismas reglas que los bloques del README (ver abajo) y hacen lo mismo que ellos, sin preguntar el sitio ni el usuario: si se cambia uno de los cuatro, revisar los otros;
  - los valores que se insertan en los bloques solo pueden llevar caracteres que no interpreten bash ni PowerShell entre comillas dobles (`createConnection()` lo comprueba antes de crear la contraseña);
  - la contraseña se muestra aparte, una sola vez, con su botón «Copiar» y el aviso de no pegarla en ningún chat, y no se guarda;
  - la página muestra siempre con qué usuario y rol se crea la conexión (antes de pulsar y al crearla): Claude actúa en su nombre. Exige marcar la casilla de confirmación (comprobada también en el servidor) y avisa si el usuario tiene `edit_others_posts`;
  - solo crea la conexión si el usuario tiene algún paquete (comprobado también en `createConnection()`) y si las contraseñas de aplicación están disponibles;
  - todo texto que se imprima se escapa (`esc_html`, `esc_textarea`, `esc_url`);
  - el formulario lleva nonce;
  - si se cambia el formato del nombre de la conexión (`wp-connect-mcp-<dominio>`), cambiarlo también en los bloques de terminal del README;
  - tras tocar los bloques, probar sus caminos de error como los del README: extraer de la página generada el cuerpo del bloque de Mac y Linux (sin la primera y la última línea), quitar `</dev/tty` y pasar las respuestas por tubería, con un servidor local (`php -S`) y un `claude` simulado.
- La alternativa son los **bloques para copiar y pegar** de la sección «Alternativa: desde la terminal» del README: uno para Linux y macOS, otro para Windows (PowerShell). **No crear ficheros ejecutables para usuarios finales.** Los usan personas no técnicas:
  - pedir siempre los datos de forma interactiva y confirmar el sitio (nombre y dirección) antes de seguir;
  - mensajes en español llano, que digan qué hacer;
  - validar usuario, contraseña y plugin antes de guardar nada;
  - sin dependencias aparte de `curl`/`claude` (Unix) o PowerShell/`claude` (Windows). No usar `php` ni `jq` en estos bloques: macOS no trae PHP;
  - validar contra el MCP (`initialize`), no contra `wp/v2`: la contraseña del plugin da 403 fuera del MCP. Tratar el 403 como «sin acceso»;
  - la contraseña nunca como argumento de `curl`: pasarla por la entrada estándar (`printf 'user = "%s:%s"\n' … | curl -K - …`). En la documentación para técnicos, `-K <(auth)`;
  - rechazar direcciones `http://` salvo `localhost`;
  - Unix: dentro de `bash <<'EOF' … EOF` con `read … </dev/tty`, para que funcione también en zsh;
  - Windows: todo dentro de `& { … }` y `return` en lugar de `exit`, para no cerrar la ventana. Válido en PowerShell 5.1 y 7.

  Los dos bloques hacen lo mismo: si se cambia uno, cambiar el otro. Tras tocarlos, probar los caminos de error con datos falsos (en Unix, quitando `</dev/tty` y pasando las respuestas por tubería).
- Los comandos de la documentación deben poder pegarse tal cual. Nada de huecos tipo `<sitio>`: en bash, `<` es una redirección y el comando falla. Los datos variables se piden con `read`.
- **El repositorio remoto es público.** No escribir en él el dominio del sitio de pruebas ni de clientes, credenciales o datos personales: decir «el sitio de pruebas» y usar `example.com` en los ejemplos.
- Nunca escribir Application Passwords en ficheros del repositorio ni en comandos que queden en el historial. Para comprobar una conexión de Claude Code, leer la cabecera de `~/.claude.json` dentro de `php -r` y mostrar solo el usuario, nunca la contraseña.
- Antes de anotar una prueba en ESTADO.md, confirmar con qué usuario y rol se hizo (`wp/v2/users/me?context=edit`, o el autor de la entrada creada). El nombre visible puede no coincidir con el usuario, y los roles de los usuarios de prueba cambian.
- Al trabajar con un sitio conectado, no usar otras conexiones al mismo sitio (Novamira, conectores de claude.ai) para hacer lo que este plugin no permite (tocar entradas de otros, borrar definitivamente o trabajar con páginas). Si el usuario lo pide, explicar el límite.
