# Changelog

Cambios por versión publicada. El día a día (pruebas, decisiones, pendientes) está en [ESTADO.md](ESTADO.md).

## 0.3.0 (sin publicar)

Seguridad: aplica la alternativa A de [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md). Probada fuera de WordPress; falta probarla en el sitio antes de usarla en producción.

- **La contraseña de la conexión solo vale para el MCP.** Las creadas desde *Conectar con Claude* responden 403 (`wp_connect_mcp_password_scope`) en el resto de la API REST y en XML-RPC. El MCP solo acepta esas contraseñas: las creadas a mano en el perfil dan 403.
- **Paquetes por usuario:** *Leer*, *Escribir* y *Papelera*. Los otorga un administrador en la nueva página *Usuarios → Acceso de Claude*. Sin paquetes no se abre sesión ni se puede crear la conexión; con ellos, `tools/list` solo muestra sus herramientas. Al quitar todos los paquetes se revocan las conexiones del usuario. Al actualizar, quien ya tenía una conexión recibe los tres paquetes una sola vez.
- **Registro de uso:** herramientas usadas, accesos denegados y cambios de permisos, en *Usuarios → Acceso de Claude* (últimos 500, en la base de datos).
- **Límite por hora y usuario** en las herramientas que escriben (filtro `wp_connect_mcp_rate_limits`).
- **Servidor MCP por defecto del adapter desactivado** (`/wp-json/mcp/mcp-adapter-default-server`, con `execute-ability` y acceso con solo `read`). Se mantiene con el filtro `wp_connect_mcp_keep_default_server` (por ejemplo, para Novamira).
- `get-current-user` devuelve `packages`.
- `upload-media`: rechaza un envío demasiado grande antes de decodificarlo y las imágenes de más de 40 megapíxeles (filtro `wp_connect_mcp_max_upload_pixels`).
- **Página *Conectar con Claude*:**
  - muestra los paquetes del usuario;
  - propone revocar las conexiones anteriores (casilla marcada por defecto);
  - no crea nada si las contraseñas de aplicación no están disponibles;
  - valida también la dirección del sitio que se inserta en los comandos y deja de admitir `&` y `%`;
  - avisa de no pegar la contraseña si el comando se detiene antes.
- **Bloques de la terminal (página y README):**
  - mensaje claro con 403 (sin acceso);
  - `curl` recibe la contraseña por la entrada estándar (`-K -`), no como argumento;
  - los del README validan contra el MCP, no contra `wp/v2/users/me`, y rechazan direcciones `http://` salvo `localhost`.

## 0.2.2 (sin publicar)

No usar en producción, por el mismo motivo que la 0.2.1.

Cambios pedidos tras la primera prueba real con el plugin de Claude `post-create-wwwarcelona` (30–31/10/2026).

- **`get-template-status` ya no da falsos negativos:** reconoce todas las condiciones de Elementor Pro que cubren el blog (`include/archive`, `include/archive/post_archive`, categorías y etiquetas con o sin ID, `include/general`; en la entrada, `include/singular` e `include/singular/post`). Antes, una plantilla de Archivo con `include/archive` salía como inexistente. Nuevo campo `templates`, con todas las plantillas publicadas de cada tipo, sus condiciones y `covers_blog`.
- **`create-post` y `update-post` responden sin el contenido:** devuelven los campos de la entrada más `content_length`, `content_hash` (MD5), `headings` y `links`. Con el nuevo campo `return_content: true`, también el contenido. `get-post` no cambia.
- **Página *Conectar con Claude*:**
  - los comandos ya no llevan la contraseña: se muestra aparte, con su botón «Copiar» y el aviso de no pegarla en ningún chat;
  - hay un bloque para Mac y Linux y otro para Windows (PowerShell), en pestañas, que piden la contraseña sin mostrarla, la comprueban, preguntan antes de sustituir una conexión y la guardan en Claude Code. Deja de admitirse el Símbolo del sistema (cmd);
  - muestra destacados el usuario y el rol, avisa si el usuario puede gestionar entradas de otros y exige marcar una casilla de confirmación antes de crear la conexión.
- Bloque de Windows del README: lee la contraseña con `NetworkCredential`, que funciona igual en PowerShell 5.1 y 7.

## 0.2.1 (sin publicar)

No usar en producción: la contraseña de la conexión vale para toda la API REST de WordPress (ver «Seguridad y limitaciones conocidas» en el README).

- Nuevas tools `list-categories`, `get-current-user`, `get-template-status` y `upload-media`. La subida solo admite imágenes JPEG, PNG, WebP o GIF en base64, con límite de 2 MB por defecto y sin URLs remotas.
- `create-post` y `update-post` admiten `slug`, título SEO, metadescripción y frase clave de Yoast, e imagen destacada subida por el mismo usuario. `get-post` devuelve estos campos.
- `list-posts` devuelve categorías y enlace, y admite filtrar por categoría.
- Documentación de herramientas, permisos, campos, límites y pruebas actualizada para las nueve tools.

## 0.2.0 (sin publicar)

No usar en producción: la contraseña de la conexión vale para toda la API REST de WordPress (ver «Seguridad y limitaciones conocidas» en el README).

- **Nuevo nombre: WP Connect MCP** (`wp-connect-mcp`). Cambian la carpeta y el fichero principal (`wp-connect-mcp/wp-connect-mcp.php`), el endpoint (`/wp-json/wp-connect-mcp/mcp`), los nombres de las tools (`wp-connect-mcp-*`) y de las abilities (`wp-connect-mcp/*`), los códigos de error (`wp_connect_mcp_*`), el espacio de nombres (`WpConnectMcp\`) y el nombre de la conexión en Claude Code (`wp-connect-mcp-<dominio>`). Hay que instalarlo como plugin nuevo, borrar el anterior y volver a crear la conexión.
- Nueva tool `trash-post`: envía a la papelera (recuperable) una entrada propia, en cualquier estado, también publicada. Si el sitio tiene la papelera desactivada, no borra nada.
- `create-post` y `update-post` admiten `status: publish`: publican ahora entradas propias, si el usuario puede publicar (Autor o superior). Publicar una programada la publica con la fecha actual. Las propias publicadas se pueden despublicar (pasar a borrador o pendiente).
- `update-post` solo actúa sobre entradas creadas por el usuario de la conexión, sea cual sea su rol (antes, un Editor podía editar las de otros).
- El agente sigue sin borrar definitivamente, sin tocar entradas de otros usuarios y sin tocar páginas.
- Documentación: herramientas por rol («Quién puede usar cada herramienta»), qué permite la API de WordPress frente al plugin con la misma credencial, Elementor y restaurar desde la papelera.

## 0.1.0 (sin publicar)

Tag `v0.1.0`. No usar en producción, por el mismo motivo.

- Servidor MCP propio con cuatro tools: listar, leer, crear y editar entradas.
- El agente solo puede dejar entradas en borrador, pendientes o programadas; no publica, no borra y no toca páginas.
- Página *Herramientas → Conectar con Claude*: crea la conexión con un clic y muestra un comando de una línea para pegar en la terminal (Mac, Linux y Windows), con instrucciones en pantalla, el usuario para el que se crea y la lista de conexiones del usuario.
- Quien no puede editar entradas recibe siempre `Permission denied`, sin revelar si un ID existe.
- Probado en el sitio de pruebas: conexión, herramientas, creación, edición, programación, rechazos, usuarios Suscriptor y Autor, y tareas reales desde Claude Code conectado con la página.
- Documentación: campos de cada herramienta, permisos por rol, uso con Claude Code, cómo quitar una conexión, seguridad y limitaciones conocidas, y pruebas con `curl` y `php -r` sin dependencias extra.
