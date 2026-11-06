# WP Connect MCP

Plugin de WordPress que expone un servidor MCP propio para que Claude u otros agentes trabajen con **entradas (posts)** con permisos muy limitados.

## Qué puede hacer el agente

| Tool MCP | Qué hace |
|---|---|
| `wp-connect-mcp-list-posts` | Lista entradas editables por el usuario (búsqueda, estado, paginación). |
| `wp-connect-mcp-list-categories` | Lista categorías con sus IDs, jerarquía y enlaces. |
| `wp-connect-mcp-get-post` | Lee una entrada: contenido sin procesar, extracto, estado, fechas, categorías y etiquetas. |
| `wp-connect-mcp-get-current-user` | Indica el usuario, sus roles, las capacidades relevantes y los paquetes de herramientas que tiene otorgados. |
| `wp-connect-mcp-get-template-status` | Comprueba si hay plantillas publicadas de Elementor Pro que se aplican a la entrada y al archivo del blog, y lista todas las de cada tipo con sus condiciones. |
| `wp-connect-mcp-create-post` | Crea una entrada como borrador, pendiente, programada o publicada. Devuelve un resumen sin el contenido. |
| `wp-connect-mcp-update-post` | Modifica solo los campos indicados de una entrada creada por el usuario; también la publica. Puede cambiar solo unos fragmentos del contenido (`replace`) sin reenviarlo entero. Devuelve un resumen sin el contenido. |
| `wp-connect-mcp-schedule-posts` | Programa a la vez hasta 20 entradas creadas por el usuario, cada una con su fecha. Si alguna no se puede programar, no cambia ninguna. |
| `wp-connect-mcp-upload-media` | Sube una imagen en base64, opcionalmente adjunta a una entrada propia. |
| `wp-connect-mcp-update-media` | Cambia el texto alternativo y el título de una imagen subida por el usuario. |
| `wp-connect-mcp-trash-post` | Envía a la papelera una entrada creada por el usuario, también si ya está publicada. |
| `wp-connect-mcp-create-category` | Crea una categoría de entradas; si ya existe con ese slug (o con ese nombre bajo el mismo padre), devuelve la existente. |
| `wp-connect-mcp-list-pages` | Lista las páginas publicadas (id, título, URL y padre), sin el contenido. |
| `wp-connect-mcp-list-published-posts` | Lista las entradas publicadas de cualquier autor (id, título, URL, categorías y extracto), sin el contenido. |
| `wp-connect-mcp-get-published` | Lee una entrada o página publicada: por defecto solo su esquema (encabezados, enlaces y palabras); el contenido, si se pide. |
| `wp-connect-mcp-list-templates` | Lista las plantillas guardadas de Elementor publicadas (sección, contenedor o página) con su shortcode, para insertarlas en una entrada propia (por ejemplo, un CTA). |

Cada tool es una ability de WordPress con el mismo nombre y `/` tras el prefijo `wp-connect-mcp` (`wp-connect-mcp/list-posts`, etc.), en la categoría `wp-connect-mcp`. Solo se exponen en el servidor `/wp-json/wp-connect-mcp/mcp`, y cada usuario solo ve y usa las de los **paquetes** que le haya dado un administrador (ver «Dar y quitar acceso»).

### Quién puede usar cada herramienta

Para usar una herramienta hacen falta dos cosas: el **paquete** que la contiene, otorgado por un administrador en *Usuarios → Acceso de Claude*, y el **rol** adecuado (tabla de abajo).

| Paquete | Herramientas |
|---|---|
| **Leer** (`posts-lectura`) | `list-posts`, `list-categories`, `get-post`, `get-current-user`, `get-template-status` |
| **Escribir** (`posts-escritura`) | `create-post`, `update-post`, `schedule-posts`, `upload-media`, `update-media` |
| **Papelera** (`posts-papelera`) | `trash-post` |
| **Categorías y etiquetas** (`categorias-escritura`) | `create-category`. Además, con este paquete `create-post` y `update-post` pueden asignar etiquetas que aún no existen (se crean). |
| **Leer el sitio** (`sitio-lectura`) | `list-pages`, `list-published-posts`, `get-published`: solo lo publicado, de cualquier autor (lo mismo que ve un visitante). Para el enlazado interno. |
| **Plantillas de Elementor** (`plantillas-elementor`) | `list-templates`. Además, con este paquete `create-post` y `update-post` pueden añadir al contenido plantillas guardadas con `[elementor-template id="N"]` (solo las publicadas de tipo sección, contenedor o página). |

Sin ningún paquete, el usuario no puede crear la conexión ni abrir sesión MCP (403). Con paquetes, `tools/list` solo muestra las herramientas de esos paquetes, y las demás dan `Permission denied`.

| Tool | Comprobación | Colaborador | Autor | Editor y Administrador |
|---|---|---|---|---|
| `list-posts` | `edit_posts` | Solo las suyas | Solo las suyas | Las de todos |
| `list-categories` | `edit_posts` | Sí | Sí | Sí |
| `get-post` | `edit_posts` y `edit_post` sobre la entrada | Solo las suyas no publicadas | Solo las suyas | Cualquiera |
| `get-current-user` | `edit_posts` | Sí | Sí | Sí |
| `get-template-status` | `edit_posts` | Sí | Sí | Sí |
| `create-post` | `edit_posts`; para programar o publicar, `publish_posts` | Sí, sin programar ni publicar | Sí, también programar y publicar | Sí, también programar y publicar |
| `update-post` | `edit_posts`, que la entrada sea suya y `edit_post`; para programar o publicar, `publish_post` | Solo las suyas no publicadas, sin programar ni publicar | Solo las suyas: también programar, publicar y despublicar | Solo las suyas |
| `schedule-posts` | `edit_posts` y `publish_posts`; cada entrada, suya y con `edit_post` y `publish_post` | No | Solo las suyas | Solo las suyas |
| `upload-media` | `upload_files`; con `post_id`, además entrada propia y `edit_post` | No por defecto | Sí | Sí |
| `update-media` | `upload_files`, que la imagen la haya subido el usuario y `edit_post` sobre ella | No por defecto | Solo las suyas | Solo las suyas |
| `trash-post` | `delete_posts`, que la entrada sea suya y `delete_post` | Solo las suyas no publicadas | Solo las suyas, también publicadas | Solo las suyas |
| `create-category` | `edit_posts` (y el paquete; no exige `manage_categories`) | Sí | Sí | Sí |
| `list-pages`, `list-published-posts`, `get-published` | `edit_posts`; solo contenido publicado y sin contraseña | Sí | Sí | Sí |
| `list-templates` | `edit_posts`; solo plantillas publicadas | Sí | Sí | Sí |

- Un **Suscriptor** no puede usar ninguna: todas dan `Permission denied`, sin revelar si un ID existe.
- **Modificar y borrar, solo entradas propias**, también para Editores y Administradores. Leer y listar sí incluye las de otros cuando el rol lo permite.
- Lo de «no publicadas» del Colaborador lo impone WordPress, no el plugin: un Colaborador no puede editar ni borrar sus entradas una vez publicadas.
- Asignar categorías y etiquetas exige además la capacidad de asignarlas (la tienen todos estos roles por defecto). Asignar etiquetas que no existen (y que, por tanto, se crean) exige además el paquete *Categorías y etiquetas* o `manage_categories`.
- Si el sitio cambia los roles o sus capacidades (por ejemplo, con un plugin de roles), manda lo que tenga el usuario, no el nombre del rol.

Campos de cada herramienta (Claude los ve con su descripción en `tools/list`):

| Tool | Campos |
|---|---|
| `list-posts` | `search` (texto en título y contenido), `status` (lista de `draft`, `pending`, `future`, `publish`; por defecto todos), `category` (ID), `per_page` (1–50, por defecto 20), `page`. Devuelve también `link` y `categories`; ordena por última modificación. |
| `list-categories` | `search`, `per_page` (1–100, por defecto 100), `page`. |
| `get-post` | `id` (obligatorio). |
| `get-current-user` | Sin campos. |
| `get-template-status` | Sin campos. |
| `create-post` | `title` (obligatorio), `slug`, `content`, `excerpt`, `status`, `scheduled_date`, `categories`, `tags`, `seo_title`, `seo_description`, `focus_keyword`, `featured_image`, `reviewed_by`, `return_content`. |
| `update-post` | `id` (obligatorio), cualquiera de los campos de `create-post` y `replace` (en lugar de `content`). Solo cambia los que se envían. |
| `schedule-posts` | `posts` (obligatorio): lista de 1 a 20 `{id, scheduled_date}`, sin IDs repetidos. |
| `upload-media` | `filename` y `data` (base64) obligatorios; `alt`, `title` y `post_id` opcionales. |
| `update-media` | `id` (obligatorio), `alt` y `title` (al menos uno). |
| `trash-post` | `id` (obligatorio). |
| `create-category` | `name` (obligatorio, máximo 200 caracteres), `slug`, `parent` (ID de una categoría existente). |
| `list-pages` | `search`, `parent` (ID; 0 para las de primer nivel), `per_page` (1–100, por defecto 20), `page`. Ordena por orden de menú y título. |
| `list-published-posts` | `search`, `category` (ID), `per_page` (1–100, por defecto 20), `page`. Ordena de la más reciente a la más antigua. |
| `get-published` | `id` (obligatorio), `include_content` (por defecto `false`). |
| `list-templates` | `search` (en el título), `type` (`section`, `container` o `page`), `per_page` (1–100, por defecto 50), `page`. |

- `content`: HTML o marcado de bloques; se filtra con las etiquetas permitidas en entradas.
- `replace` (solo `update-post`): lista de 1 a 100 sustituciones `{search, replace}` sobre el contenido guardado, aplicadas en orden. Cada `search` debe aparecer **exactamente una vez** en el contenido tal como está en ese momento (con el HTML y las entidades guardados, por ejemplo `&amp;`); si una no aparece o aparece más de una vez, no se cambia nada y el error dice cuál falla y cuántas veces aparece (`wp_connect_mcp_replace_not_unique`). No se admite junto a `content`. El resultado se filtra como `content`, y `content_hash` de la respuesta permite comparar con la copia local. Sirve para añadir enlaces internos o cambiar un párrafo enviando unos cientos de bytes en lugar del texto entero.
- **Plantillas guardadas de Elementor en el contenido:** con el paquete *Plantillas de Elementor*, `content` (o `replace`) puede añadir `<!-- wp:shortcode -->[elementor-template id="N"]<!-- /wp:shortcode -->` con el ID de una plantilla de `list-templates`. Solo se acepta esa forma exacta (solo el atributo `id`) y solo plantillas publicadas, sin contraseña, de tipo sección, contenedor o página; si no, error `wp_connect_mcp_invalid_template`. Sin el paquete, añadir ese shortcode da `wp_connect_mcp_templates_not_allowed`. Los que ya estaban en la entrada no se vuelven a comprobar. El shortcode lo pinta Elementor Pro al mostrar la entrada; los demás shortcodes no cambian (los trata WordPress como en el editor).
- `reviewed_by`: quién firma o revisa la entrada (texto plano, máximo 150 caracteres; vacío lo quita), para el E-E-A-T. No cambia el autor: se guarda en el campo personalizado `wp_connect_mcp_reviewed_by`, que la plantilla de Elementor puede mostrar con la etiqueta dinámica «Campo personalizado». Para usar un campo que el sitio ya tenga (por ejemplo, de ACF), filtro `wp_connect_mcp_reviewed_by_meta_key` (no admite campos protegidos, con guion bajo). `get-post`, `create-post` y `update-post` lo devuelven.
- `status`: `draft` (por defecto), `pending`, `future` o `publish`. `publish` publica ahora: si la entrada estaba programada, se publica con la fecha y hora actuales. No se admite junto a `scheduled_date`.
- `scheduled_date`: ISO 8601, en el futuro (por ejemplo, `2026-10-28T09:00:00+01:00`). Sin zona horaria se usa la del sitio. Implica `future`.
- `categories`: IDs de categorías existentes (las nuevas se crean antes con `create-category`). `tags`: nombres. Las que no existen solo se crean si el usuario tiene el paquete *Categorías y etiquetas* o `manage_categories`; si no, la operación se rechaza entera (`wp_connect_mcp_tags_not_found`) y el error dice cuáles faltan. Las dos listas sustituyen a las actuales.
- `slug`: se sanea con las reglas de WordPress; si ya existe, WordPress lo hace único.
- `seo_title` (máximo 70 caracteres), `seo_description` (máximo 160) y `focus_keyword`: se guardan en los campos de Yoast SEO. Si Yoast no está activo, la operación se rechaza con un error claro.
- `featured_image`: ID de una imagen subida por el mismo usuario de la conexión; `0` la quita.
- Respuesta de `create-post` y `update-post`: los mismos campos que `get-post` salvo `content`, más `content_length` (caracteres), `content_hash` (MD5 del contenido guardado), `headings` (número de encabezados) y `links` (número de enlaces). Si `content_hash` coincide con el MD5 del contenido enviado, ha llegado entero y el filtro no ha quitado nada. Si el agente copió el contenido de un fichero en la llamada, el MD5 se calcula sin el salto de línea final del fichero, que el agente no reenvía (`php -r 'echo md5(rtrim(file_get_contents($argv[1]), "\n")), PHP_EOL;' post.html`): `md5sum` del fichero entero daría «distinto» con el contenido intacto. Si lo envía un script con el fichero tal cual, vale `md5sum` (ver «Enviar el contenido desde un archivo»). Con `return_content: true` devuelven también `content`. Así, subir un lote no repite cada texto de ida y de vuelta.
- `schedule-posts`: valida todas las entradas antes de tocar ninguna. Si una falla (no es propia, no existe, fecha pasada, repetida, privada), no programa nada y el error dice qué ID falla y por qué. Más de 20 se rechazan antes de buscar ninguna. Si todas son válidas, responde con `posts` (resumen de cada una: `id`, `title`, `status`, `date`…) y `failed` (las que WordPress no pudo guardar después de validarlas; normalmente vacía). Una entrada publicada que se programa deja de verse hasta su nueva fecha, igual que con `update-post`.
- **Varias entradas, una llamada.** Muchas llamadas seguidas o en paralelo pueden hacer saltar el cortafuegos del sitio (CrowdSec, Wordfence, Cloudflare), que bloquea la IP de quien las hace: en el sitio de pruebas pasó tras 10 `update-post` en paralelo (02/11/2026). No pasa siempre (el 03/11/2026 otras 10 en paralelo no lo hicieron saltar), así que una vez sin bloqueo no demuestra que sea seguro. Para programar un lote, `schedule-posts`; para subirlo, llamadas en serie. La descripción del servidor MCP se lo indica al agente.
- `get-template-status`: para `single_post` y `archive`, `exists`, `id`, `title` y `conditions` indican la plantilla que se aplica al blog, y `templates` lista todas las publicadas de ese tipo con sus condiciones y `covers_blog`. Cubren el blog, en el archivo, las condiciones de Elementor `include/general`, `include/archive` (todos los archivos), `include/archive/post_archive` y las de categorías y etiquetas (con o sin ID); en la entrada, `include/general`, `include/singular` y `include/singular/post`. Una exclusión amplia del mismo grupo (por ejemplo, `exclude/archive`) lo anula. Las plantillas solo de autor, fecha, búsqueda, una entrada concreta o una categoría de entradas aparecen en `templates` con `covers_blog: false`.
- `create-category`: responde `id`, `name`, `slug`, `parent`, `link` y `created`. Si ya existe una categoría con ese slug (el enviado o el que sale del nombre), o con el mismo nombre bajo el mismo padre, devuelve esa con `created: false` y no crea otra: se puede repetir sin duplicar.
- **Leer el sitio** (`list-pages`, `list-published-posts`, `get-published`): solo entradas y páginas publicadas y sin contraseña, de cualquier autor. Borradores, privadas, con contraseña, otros tipos o IDs inexistentes dan el mismo error («No existe ningún contenido publicado…»), sin revelar si el ID existe. Las listas no llevan el contenido. `get-published` devuelve por defecto el esquema: `headings` (`level`, `text`), `links` (`href`, `text`, `internal`: relativo o del mismo dominio, con o sin `www.`), `word_count`, `excerpt` (el manual o las primeras 40 palabras), y `categories` en las entradas; con `include_content: true`, también `content`. Así el enlazado lee unos cientos de bytes por página en lugar del texto entero, y pide el contenido solo para buscar un ancla. `built_with_elementor: true` avisa de que el contenido es la copia en HTML que guarda Elementor, que puede no reflejar el último cambio. `list-posts` y `get-post` no cambian: son para las entradas que el usuario puede editar.
- `update-media`: solo imágenes subidas por el mismo usuario; las de otros dan el mismo error que un ID inexistente. No cambia el archivo ni el contenido de las entradas que la usan: si el `alt` también está escrito en una etiqueta `<img>` del contenido, hay que cambiarlo ahí con `update-post` (`replace`).
- `list-templates`: responde `templates` (`id`, `title`, `type`, `modified`, `shortcode`), `total`, `total_pages` y `elementor_active`. Solo las plantillas guardadas (*Plantillas → Plantillas guardadas*) publicadas de tipo sección, contenedor o página: no las de cabecera, pie, entrada o archivo del Theme Builder (esas las comprueba `get-template-status`). No devuelve su contenido.
- `upload-media` solo admite JPEG, PNG, WebP y GIF reales, no solo por extensión; máximo 2 MB y 40 megapíxeles por defecto. Rechaza un envío demasiado grande antes de decodificarlo. No admite URLs remotas para evitar SSRF. Los límites se cambian con los filtros `wp_connect_mcp_max_upload_bytes` y `wp_connect_mcp_max_upload_pixels`.
- Cualquier otro campo (por ejemplo, `post_type` o `author`) se rechaza.

Límites:

- Solo el tipo `post`; páginas y tipos personalizados se rechazan.
- **Publica solo entradas propias.** Estados permitidos: `draft`, `pending`, `future` (programada, con `scheduled_date` futura) y `publish`. Programar y publicar exigen que el usuario pueda publicar (Autor o superior); un Colaborador solo deja borradores y pendientes.
- En entradas propias ya publicadas se puede editar el contenido, que cambia en directo, y también cambiar su estado: volver a borrador o pendiente la despublica. En entradas privadas se puede editar el contenido, pero no su estado ni su fecha.
- **Solo modifica, publica y borra entradas propias** (las creadas por el usuario de la conexión), aunque su rol permita tocar las de otros: las demás dan «Solo se pueden modificar o borrar las entradas creadas por este usuario». Leer y listar sí incluye las de otros si el rol lo permite.
- **Borrar es enviar a la papelera**: una persona puede restaurar la entrada desde el escritorio. Vuelve como borrador y sin programar, aunque estuviera programada o publicada: para que salga hay que volver a programarla o publicarla. Vale para cualquier estado: si la entrada estaba publicada, deja de verse en el sitio al momento. Un Colaborador no puede borrar sus entradas ya publicadas (WordPress no se lo permite). Si el sitio tiene la papelera desactivada (`EMPTY_TRASH_DAYS` a 0), no se borra nada.
- No borra definitivamente ni toca páginas, usuarios, ajustes, plugins o temas. Solo sube imágenes del usuario autenticado. El servidor expone como mucho estas dieciséis tools, no las meta-tools de descubrimiento. Crear categorías, leer páginas y listar plantillas no cambia esto: no se editan páginas, plantillas ni categorías existentes, ni se borra ninguna.
- **El servidor MCP por defecto del adapter queda desactivado** (`/wp-json/mcp/mcp-adapter-default-server`, con `execute-ability` y acceso con solo `read`). Si el sitio lo necesita (por ejemplo, Novamira, que lo usa), se mantiene con `add_filter('wp_connect_mcp_keep_default_server', '__return_true');` en un plugin propio o mu-plugin.
- **Límite por hora y usuario** en las herramientas que escriben: `create-post` 30, `update-post` 120, `schedule-posts` 120 entradas (cuenta cada entrada del lote, no cada llamada), `upload-media` 60, `update-media` 60, `trash-post` 30 y `create-category` 10. Al superarlo, la herramienta responde que se ha alcanzado el máximo. Se cambian con el filtro `wp_connect_mcp_rate_limits`.
- **Registro de uso:** cada herramienta usada, cada acceso denegado y cada cambio de permisos queda anotado (fecha, usuario, acción, ID y resultado) y se ve en *Usuarios → Acceso de Claude*. Se guardan los últimos 500 movimientos en la base de datos, no en `debug.log`.
- Cada acción respeta las capacidades del usuario de WordPress (`edit_posts`, `edit_post`, `delete_posts`, `delete_post`, `publish_post`, asignar categorías y etiquetas). Sin `edit_posts` (por ejemplo, un Suscriptor) todas las herramientas responden `Permission denied`, sin revelar si un ID existe.
- Las sesiones MCP van ligadas al usuario que las abre: no se pueden usar con las credenciales de otro.
- El contenido se filtra con `wp_kses_post`, también para administradores.

## Requisitos

- WordPress 6.9 o superior (Abilities API en el núcleo).
- PHP 7.4 o superior.
- HTTPS y Application Passwords activos.

El paquete `wordpress/mcp-adapter` va incluido en `vendor/` y se carga con el autoloader de Jetpack. Si otro plugin trae una versión más nueva del adapter, se usa esa. Al cargarse como librería, el adapter deja un aviso de deprecación en el log de WordPress.

## Instalación

1. Copiar la carpeta del plugin a `wp-content/plugins/wp-connect-mcp/`, **con `vendor/` incluido** (no hace falta Composer en el servidor).
2. Activarlo en *Plugins*. Si WordPress es anterior a 6.9, aparece un aviso y el plugin no hace nada.
3. Crear el usuario que va a usar Claude (ver «Conexión») y darle acceso en *Usuarios → Acceso de Claude*.
4. Conectarlo desde *Herramientas → Conectar con Claude*, entrando con ese usuario.

Para actualizar, basta con subir los ficheros cambiados; no hay que reactivar el plugin. Al pasar a la 0.3.0, quien ya tenía una conexión creada desde la página recibe los tres paquetes de entonces una sola vez (queda en el registro de uso como «(sistema)»), así que sus conexiones siguen funcionando. Las contraseñas creadas a mano en el perfil dejan de valer para el MCP. Al pasar a la 0.4.5, nadie recibe los paquetes nuevos automáticamente, y los usuarios con paquetes dejan de poder usar la API de WordPress con contraseñas creadas a mano o por XML-RPC. Al pasar a la 0.4.6, nadie recibe *Plantillas de Elementor*; quien tiene *Escribir* puede usar además `update-media`.

## Conexión

El usuario con el que se conecta Claude determina lo que puede hacer (detalle por herramienta en «Quién puede usar cada herramienta»):

| Rol | Qué puede hacer Claude |
|---|---|
| **Colaborador** | Crear, editar y enviar a la papelera sus propias entradas mientras no estén publicadas, como borrador o pendientes. No puede programar ni publicar. |
| **Autor** | Lo mismo, y además programar, publicar y despublicar sus entradas, y editar y enviar a la papelera las programadas y publicadas. Solo ve y edita sus propias entradas: las de otros usuarios dan `Permission denied`. |
| **Editor** | Ver las entradas de cualquier usuario; editar, programar, publicar y borrar solo las suyas. |
| **Administrador** | Lo mismo que el Editor. No recomendado: si la credencial se filtra, es la de un administrador. |
| **Suscriptor** | Nada: todas las herramientas dan `Permission denied`. |

Ningún rol permite a Claude borrar definitivamente ni tocar entradas de otros usuarios a través de este plugin. Lo recomendable es un usuario propio para Claude, con rol Autor si puede publicar (o Colaborador si una persona debe revisarlo y publicarlo todo), no el de una persona ni el de un administrador.

### Dar y quitar acceso

Lo hace un administrador (capacidad `promote_users`) en **Usuarios → Acceso de Claude**:

- una fila por usuario que puede editar entradas, con una casilla por paquete (*Leer*, *Escribir*, *Papelera*, *Categorías y etiquetas*, *Leer el sitio*, *Plantillas de Elementor*), sus conexiones con el último uso y quién hizo el último cambio y cuándo;
- nadie tiene acceso por defecto (los paquetes *Categorías y etiquetas* y *Leer el sitio*, de la 0.4.5, y *Plantillas de Elementor*, de la 0.4.6, tampoco se dan a quien ya tenía los otros). Al pasar a la 0.4.6, quien tiene *Escribir* recibe con él `update-media`;
- **un usuario con algún paquete solo puede usar la API de WordPress a través del MCP**: sus contraseñas de aplicación creadas a mano y XML-RPC dejan de funcionar (ver «Seguridad y limitaciones conocidas»). Puede seguir entrando en el escritorio;
- al quitar todos los paquetes a un usuario, **se revocan también sus conexiones** con Claude (sus contraseñas creadas desde la página);
- debajo, el **registro de uso**: los últimos 100 movimientos.

Para un usuario de marketing que usa el plugin de Claude de posts, lo normal es *Leer* + *Escribir*, *Leer el sitio* para el enlazado interno, *Categorías y etiquetas* solo si debe poder crear categorías o etiquetas nuevas, *Plantillas de Elementor* si los posts llevan un CTA u otro bloque de una plantilla guardada, y *Papelera* solo si debe poder retirar entradas.

Endpoint: `https://<sitio>/wp-json/wp-connect-mcp/mcp` (es el dato que se usa en los comandos de abajo, no un comando).

La forma recomendada es desde el escritorio de WordPress. La 0.3.0 (acceso por paquetes y contraseña limitada al MCP) está probada fuera de WordPress, con el bloque de Mac y Linux; falta probarla en el sitio, en Windows y en un Mac (ver [ESTADO.md](ESTADO.md)).

### Conectar Claude Code desde el escritorio (recomendado)

Para cualquier persona, también si no es informática. No hay que saber el usuario, la contraseña ni la dirección del sitio.

1. Entrar en el WordPress con el usuario que va a usar Claude.
2. Ir a **Herramientas → Conectar con Claude**, comprobar el usuario y el rol que muestra, marcar la casilla «Sí, crear la conexión para…» y pulsar **Crear conexión**.
3. Elegir la pestaña del sistema (*Mac y Linux* o *Windows (PowerShell)*; se abre sola la del equipo) y pulsar **Copiar** en el comando.
4. Abrir la terminal (Mac: *Terminal*; Windows: *PowerShell*; Linux: *Terminal*), pegar el comando y pulsar Intro.
5. Cuando el comando la pida, copiar la **contraseña de aplicación** de la página con su botón **Copiar** y pegarla en la terminal (no se ve mientras se pega). No pegarla nunca en un chat, tampoco en el de Claude.
6. Abrir una sesión nueva de Claude Code (las que ya estaban abiertas no ven el sitio) y preguntarle «¿Con qué usuario estás conectado a WordPress?»: debe decir el usuario de la conexión. En la terminal, `/mcp` también muestra el sitio conectado, con las herramientas de sus paquetes (11 con *Leer*, *Escribir* y *Papelera*); en la app de escritorio, `/mcp` solo dice cuántas conexiones hay.

Si el usuario no tiene ningún paquete, la página lo dice y no ofrece el botón: hay que pedir acceso a un administrador.

La página explica estos mismos pasos en pantalla.

Qué hace la página por dentro:

- **Contraseña de aplicación:** crea una para el usuario conectado, con el nombre «Claude Code · fecha» y el identificador de aplicación del plugin (`ConnectPage::APP_ID`). **Solo vale para el MCP:** en el resto de la API REST (`wp/v2`, `batch`…) y en XML-RPC responde 403. Y el MCP solo acepta contraseñas creadas aquí.
- **Solo con acceso:** si el usuario no tiene ningún paquete, no se crea nada (se comprueba también en el servidor).
- **Revocar las anteriores:** la casilla «Revocar mis conexiones anteriores con Claude» viene marcada; así, una contraseña perdida o de un equipo antiguo deja de valer. Se desmarca solo para usar Claude en varios equipos.
- **Contraseña aparte:** se muestra en su propio recuadro, con su botón «Copiar» y el aviso de no pegarla en ningún chat. Los comandos no la llevan, así que se pueden reenviar sin riesgo y no queda en el historial de la terminal.
- **Un comando por sistema, en pestañas:** *Mac y Linux* (bloque `bash <<'EOF' … EOF`, válido también en zsh) y *Windows (PowerShell)* (bloque `& { … }`, PowerShell 5.1 y 7; no vale el Símbolo del sistema). Al cargar se abre la pestaña del sistema del navegador. Llevan ya puestos el sitio, el endpoint, el nombre de la conexión y el usuario. Al ejecutarse piden la contraseña sin mostrarla, la comprueban contra el MCP (contraseña incorrecta, sitio sin plugin o sin conexión dan un mensaje claro y no guardan nada), preguntan antes de sustituir una conexión que ya exista y guardan la conexión con `claude mcp add --transport http --scope user`. Hacen lo mismo que los bloques de «Alternativa: desde la terminal», sin preguntar el sitio ni el usuario.
- **Se muestra una sola vez:** si se pierde la contraseña, se crea otra conexión (con «Revocar mis conexiones anteriores» marcada, la perdida deja de valer).
- **Nombre de la conexión:** `wp-connect-mcp-` más el dominio con guiones (por ejemplo, `wp-connect-mcp-example-com`).
- **Lista de conexiones:** muestra las del usuario con su último uso, y enlaza al perfil para revocarlas (*Contraseñas de aplicación*).
- **Si no están disponibles las contraseñas de aplicación** (sitio sin HTTPS o desactivadas por un plugin de seguridad), la página lo explica y no ofrece el botón.
- **Usuario de la conexión:** la conexión siempre es del usuario con el que se ha entrado en WordPress. La página lo muestra destacado (nombre, usuario y rol) antes de pulsar y al crearla, porque Claude actuará en su nombre, y pide marcar la casilla «Sí, crear la conexión para *usuario* (*rol*)»; sin marcarla no se crea nada. Si no es el correcto, hay que cerrar sesión y entrar con el que toca.
- **Roles amplios:** si el usuario puede gestionar entradas de otros (`edit_others_posts`: Editor, Administrador o un rol personalizado parecido), la página lo advierte y recomienda un usuario propio para Claude con rol Autor.
- **Caracteres raros:** si el nombre de usuario o la dirección tienen caracteres que el comando no admite (fuera de letras, números, espacio, `_ . @ -` en el usuario), la página no crea la contraseña y remite a los bloques de la terminal.
- **Quién la ve:** está en *Herramientas* para cualquier usuario con `edit_posts`, que es lo mínimo que necesitan las herramientas.

Contrapartida de seguridad: la credencial ya no queda en el historial de la terminal, pero sí en `~/.claude.json`. Es una contraseña de aplicación revocable, solo vale para ese usuario y para el MCP, con sus paquetes, y no da acceso al escritorio. Si se sospecha que se ha visto, se revoca en el perfil y se crea otra.

### Alternativa: desde la terminal

Para cuando el comando de la página no sirve (por ejemplo, se quiere conectar otro equipo) o lo hace un técnico. La contraseña de aplicación tiene que crearse igualmente desde *Herramientas → Conectar con Claude*, con el usuario que va a usar Claude y con acceso otorgado en *Usuarios → Acceso de Claude*: el plugin rechaza las contraseñas creadas a mano en el perfil.

No hay que descargar nada: se copia el bloque entero, se pega en la terminal y se pulsa Intro. El bloque pregunta, uno a uno:

1. la dirección del sitio (vale con o sin `https://`);
2. si el sitio que muestra (nombre y dirección) es el correcto;
3. el usuario de WordPress;
4. la contraseña de aplicación (no se ve mientras se escribe o se pega).

Antes de guardar nada comprueba, contra el propio MCP, que el usuario y la contraseña son correctos, que el usuario tiene acceso y que el sitio tiene el plugin activo. Si algo falla, explica en lenguaje claro qué revisar. Si el sitio ya estaba conectado, pregunta antes de sustituir la conexión. La contraseña no queda en el historial de la terminal.

Al terminar, el sitio queda conectado como `wp-connect-mcp-` más el dominio con guiones (por ejemplo, `wp-connect-mcp-example-com`). Después hay que abrir una sesión nueva de Claude Code y preguntarle con qué usuario está conectado a WordPress (en la terminal, `/mcp` también lo muestra, con las herramientas de sus paquetes).

#### Linux y macOS (Terminal)

Funciona igual en bash y en zsh (la terminal por defecto de macOS).

```bash
bash <<'EOF'
fallo() { printf '\n❌ %s\n' "$1"; [ -n "${2:-}" ] && printf '   %s\n' "$2"; exit 1; }
si() { case "$1" in s|S|si|sí|Si|Sí|SI|SÍ) return 0 ;; *) return 1 ;; esac; }
command -v claude >/dev/null 2>&1 || fallo "No se encuentra Claude Code en este equipo." "Instálalo primero y vuelve a pegar este bloque."
echo "Conectar Claude Code con un sitio WordPress"
echo "-------------------------------------------"
read -r -p "1/4 Dirección del sitio (por ejemplo, https://example.com): " SITE </dev/tty
SITE=$(printf '%s' "$SITE" | tr -d '[:space:]'); SITE=${SITE%/}
[ -n "$SITE" ] || fallo "No has escrito la dirección del sitio."
case "$SITE" in http://*|https://*) ;; *) SITE="https://$SITE" ;; esac
case "$SITE" in http://localhost*|http://127.0.0.1*) ;; http://*) fallo "La dirección empieza por http://: la contraseña viajaría sin cifrar." "Escríbela con https:// (como en el navegador, con el candado)." ;; esac
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
echo "Comprobando el usuario, la contraseña y el plugin…"
MCP="$SITE/wp-json/wp-connect-mcp/mcp"
RESP=$(printf 'user = "%s:%s"\n' "$WP_USER" "$WP_APP_PASS" | curl -sS --max-time 20 -K - -w '\n%{http_code}' "$MCP" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"conectar-claude","version":"1.0"}}}' 2>/dev/null)
CODE=${RESP##*$'\n'}
case "$CODE" in
  200) ;;
  000) fallo "No se puede conectar con $SITE." "Revisa que tengas internet y vuelve a pegar este bloque." ;;
  401) fallo "El usuario o la contraseña de aplicación no son correctos." "Revisa el usuario y crea una conexión nueva en WordPress: Herramientas → Conectar con Claude." ;;
  403) fallo "Tu usuario no tiene acceso a Claude o la contraseña no se creó desde «Conectar con Claude»." "Pide acceso a un administrador (Usuarios → Acceso de Claude) y crea la contraseña en Herramientas → Conectar con Claude." ;;
  404) fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo instale y lo active." ;;
  *)   fallo "El sitio ha respondido con un error ($CODE)." "Pide ayuda a soporte técnico y diles este número." ;;
esac
case "$RESP" in
  *'"WP Connect MCP"'*) echo "✅ Usuario, contraseña y plugin correctos." ;;
  *) fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo instale y lo active." ;;
esac
NAME="wp-connect-mcp-$(printf '%s' "$HOST" | tr '.' '-' | tr -cd 'a-zA-Z0-9-')"
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
echo "   2. Pregúntale: «¿Con qué usuario estás conectado a WordPress?». Debe decir $WP_USER."
echo "      (En la terminal, /mcp también muestra «$NAME» como conectado; en la app de escritorio, no.)"
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
  $site = ("" + (Read-Host "1/4 Dirección del sitio (por ejemplo, https://example.com)")).Trim().TrimEnd('/')
  if (-not $site) { Fallo "No has escrito la dirección del sitio."; return }
  if ($site -notmatch '^https?://') { $site = "https://$site" }
  if ($site -match '^http://' -and $site -notmatch '^http://(localhost|127\.0\.0\.1)([:/]|$)') { Fallo "La dirección empieza por http://: la contraseña viajaría sin cifrar." "Escríbela con https:// (como en el navegador, con el candado)."; return }
  try { $siteHost = ([Uri]$site).Host } catch { Fallo "La dirección no es válida." "Escríbela como en el navegador, por ejemplo https://example.com"; return }
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
  $pass = (New-Object Net.NetworkCredential('', $sec)).Password
  if (-not $pass) { Fallo "No has escrito la contraseña de aplicación."; return }
  $auth = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($user + ':' + $pass))
  $headers = @{ Authorization = "Basic $auth"; Accept = "application/json, text/event-stream" }
  Write-Host ""
  Write-Host "Comprobando el usuario, la contraseña y el plugin..."
  $mcp = "$site/wp-json/wp-connect-mcp/mcp"
  $body = '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"conectar-claude","version":"1.0"}}}'
  $init = $null
  try { $init = Invoke-RestMethod -Method Post -Uri $mcp -Headers $headers -ContentType "application/json" -Body $body -TimeoutSec 20 -UseBasicParsing }
  catch {
    $code = 0; if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
    if ($code -eq 0) { Fallo "No se puede conectar con $site." "Revisa que tengas internet y vuelve a pegar este bloque." }
    elseif ($code -eq 401) { Fallo "El usuario o la contraseña de aplicación no son correctos." "Revisa el usuario y crea una conexión nueva en WordPress: Herramientas -> Conectar con Claude." }
    elseif ($code -eq 403) { Fallo "Tu usuario no tiene acceso a Claude o la contraseña no se creó desde «Conectar con Claude»." "Pide acceso a un administrador (Usuarios -> Acceso de Claude) y crea la contraseña en Herramientas -> Conectar con Claude." }
    elseif ($code -eq 404) { Fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo instale y lo active." }
    else { Fallo "El sitio ha respondido con un error ($code)." "Pide ayuda a soporte técnico y diles este número." }
    return
  }
  if ($init.result.serverInfo.name -ne "WP Connect MCP") { Fallo "El sitio no tiene el plugin WP Connect MCP activo." "Pide a soporte técnico que lo instale y lo active."; return }
  Write-Host "OK: usuario, contraseña y plugin correctos." -ForegroundColor Green
  $name = "wp-connect-mcp-" + (($siteHost -replace '\.', '-') -replace '[^a-zA-Z0-9-]', '')
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
  Write-Host "   2. Pregúntale: «¿Con qué usuario estás conectado a WordPress?». Debe decir $user."
  Write-Host "      (En la terminal, /mcp también muestra «$name» como conectado; en la app de escritorio, no.)"
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

- «Lista mis últimas 5 entradas en example.com.»
- «Crea un borrador titulado "Novedades de octubre" con este texto: …»
- «Programa la entrada 2470 para el lunes a las 9:00.»
- «Cambia el extracto de la entrada 1439.»
- «Publica ahora la entrada 2501.»
- «Borra el borrador que acabas de crear, me he equivocado de título.»

Claude pide permiso antes de usar cada herramienta (salvo que se haya autorizado de forma permanente). Si se le pide tocar una entrada de otro usuario, o publicar con un Colaborador, responde que no puede: el plugin lo rechaza. Lo que envía a la papelera se puede restaurar desde *Entradas → Papelera*.

Las fechas sin zona horaria se interpretan en la zona del sitio (*Ajustes → Generales*). Las entradas programadas se publican solas a su hora con el cron de WordPress, que se ejecuta cuando alguien visita el sitio.

### Quitar una conexión

1. En la terminal: `claude mcp list` muestra el nombre (por ejemplo, `wp-connect-mcp-example-com`) y `claude mcp remove "wp-connect-mcp-example-com"` la borra del equipo.
2. En WordPress: *Herramientas → Conectar con Claude → Revocar conexiones en tu perfil*, y revocar la contraseña «Claude Code · fecha». Sin este paso, la credencial sigue siendo válida aunque se haya quitado del equipo.
3. Un administrador puede cortar todas las conexiones de un usuario quitándole todos los paquetes en *Usuarios → Acceso de Claude*.

## Seguridad y limitaciones conocidas

- **La contraseña de la conexión solo vale para el MCP** (desde la 0.3.0): en `wp/v2`, `batch` y XML-RPC responde 403 (`wp_connect_mcp_password_scope`), y el MCP rechaza las contraseñas creadas a mano. Hasta la 0.2.2 valía para toda la API REST y permitía saltarse los límites del plugin.
- **Un usuario con acceso de Claude solo usa la API por el MCP** (desde la 0.4.5, sin excepciones): si tiene algún paquete, sus contraseñas de aplicación creadas a mano se rechazan en la API REST y en XML-RPC (`wp_connect_mcp_api_blocked`), y XML-RPC con su contraseña normal también. El rechazo queda en el registro (una vez por usuario y hora). No cambia el escritorio: puede entrar en `wp-admin`, ver la vista previa de sus borradores y usar el editor, que trabaja con la sesión del navegador. Dejan de funcionar las aplicaciones que usen contraseñas de aplicación de ese usuario (por ejemplo, la app móvil de WordPress): si otra integración necesita la API, debe usar otro usuario. No cubre plugins de autenticación de terceros (JWT, OAuth, Basic Auth con la contraseña normal): si el sitio los tiene, revisar con sistemas. Análisis en [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) e informe para sistemas en [docs/INFORME-SEGURIDAD.md](docs/INFORME-SEGURIDAD.md).
- **Paquetes por usuario:** sin paquetes no hay acceso; con ellos, solo sus herramientas. Se imponen en el servidor, no en las skills de Claude. Ver «Dar y quitar acceso».
- **Credencial en los argumentos de `claude mcp add`:** al guardar la conexión, la cabecera `Authorization` (usuario y contraseña en base64) va como argumento de `claude` durante un instante, así que en equipos con auditoría de procesos (EDR, Sysmon, auditd) puede quedar en sus registros. Las comprobaciones con `curl` de los bloques ya la pasan por la entrada estándar. Si sistemas lo considera un riesgo, revocar la conexión tras configurar el equipo y crear otra.
- **Elementor:** `get-template-status` solo comprueba si existen plantillas publicadas aplicables al blog, y `list-templates` lista las plantillas guardadas; ninguna lee ni modifica su contenido. Ni el plugin ni la API de WordPress editan el diseño de Elementor (se guarda en `_elementor_data`, que no está en la API). Insertar una plantilla en una entrada (`[elementor-template id="N"]`) exige el paquete *Plantillas de Elementor* y solo admite plantillas publicadas de sección, contenedor o página, así que una entrada no puede mostrar un borrador ni una plantilla privada. Quien tiene el paquete sí puede mostrar en sus entradas cualquiera de esas plantillas publicadas, también las creadas por otros: es lo que pide el CTA, y por eso va en un paquete aparte. Los demás shortcodes del contenido no los comprueba el plugin, igual que no lo hace el editor de WordPress para un Autor.
- **Yoast SEO:** se escriben sus metadatos de título, descripción y frase clave. No se simula la puntuación del editor de Yoast; el análisis se recalcula al abrir o guardar la entrada en WordPress.

- **Otras conexiones al mismo sitio:** el límite de «no tocar entradas de otros» solo vale para este plugin. Si el mismo Claude Code tiene también otra conexión con más permisos (por ejemplo, Novamira o un conector de claude.ai), podría publicar o borrar por esa vía. Para que el límite sea real, quien use esta conexión no debe tener otras al mismo sitio.
- **Publicar sin revisión:** con un Autor o superior, Claude puede publicar y modificar en directo sus entradas sin que nadie las revise. Si una persona debe revisar antes de publicar, conectar con un Colaborador.
- **Categorías y etiquetas:** con el paquete *Categorías y etiquetas*, un Autor o Colaborador puede crear categorías y etiquetas, más de lo que le permite WordPress por `wp/v2` (exige `manage_categories`). Es a propósito, controlado por el paquete y con un límite de 10 categorías por hora. Sin el paquete, solo se asignan etiquetas existentes.
- **Leer el sitio:** con *Leer el sitio*, cualquier rol con `edit_posts` lee lo publicado de todos los autores, incluido el contenido de las páginas. Es información pública; no da acceso a borradores, privadas ni contenido con contraseña de otros.
- **Credencial en el equipo:** queda en `~/.claude.json` (no en el historial de la terminal: tanto la página como los bloques la piden sin mostrarla). Es revocable y solo vale para el MCP de ese usuario. El botón «Copiar» la deja en el portapapeles: en equipos con historial o sincronización del portapapeles (Windows con Win+V, portapapeles universal de macOS), conviene borrarlo después.
- **Direcciones `http://`:** los bloques de la terminal las rechazan (salvo `localhost`), porque la contraseña viajaría sin cifrar.
- **Log de WordPress:** si el sitio tiene `WP_DEBUG_LOG` activo, comprobar que `wp-content/debug.log` no se puede descargar desde internet. En `example.com` sí se podía; es un problema del sitio, no del plugin.
- **Cortafuegos del sitio:** si un WAF (CrowdSec, Wordfence, Cloudflare) bloquea la IP, la respuesta no llega a WordPress, así que el plugin no puede cambiarla: Claude recibe la página HTML del cortafuegos en lugar de JSON. Hay que esperar a que se levante el bloqueo o pedir a sistemas que lo levante, y no reintentar en bucle. Para evitarlo, `schedule-posts` y llamadas en serie (ver «Varias entradas, una llamada»).
- **Aviso de deprecación:** el adapter MCP incluido deja un aviso en el log en cada petición. No afecta al funcionamiento.

## Probar con curl

El transporte HTTP del adapter funciona con sesión, en tres pasos: abrir sesión (`initialize`), confirmarla (`notifications/initialized`) y hacer las peticiones. Todas van por POST al mismo endpoint, con Basic Auth y Application Password. A partir del segundo paso se envían las cabeceras `Mcp-Session-Id` y `MCP-Protocol-Version`.

Solo hacen falta `curl` y `php` (para leer el JSON); no hay que instalar `jq` ni nada más.

La contraseña tiene que ser una creada desde *Herramientas → Conectar con Claude* (las creadas a mano en el perfil dan 403 en el MCP) y el usuario necesita algún paquete en *Usuarios → Acceso de Claude*. La credencial se pasa a `curl` con `-K <(auth)`, por la entrada estándar, para que no aparezca en la lista de procesos (`ps`) ni en los registros de auditoría del equipo.

El paso 0 comprueba que la contraseña **no** sirve fuera del MCP: debe dar 403 con `wp_connect_mcp_password_scope`. Si da 200, el plugin no está limitando la contraseña.

```bash
SITE="https://example.com"
WP_USER="usuario-del-agente"
read -s -p "Application Password: " WP_APP_PASS; echo   # no queda en el historial
MCP="$SITE/wp-json/wp-connect-mcp/mcp"
PV="2025-11-25"
auth() { printf 'user = "%s:%s"\n' "$WP_USER" "$WP_APP_PASS"; }

# 0. La contraseña no debe servir fuera del MCP: debe dar 403 y wp_connect_mcp_password_scope
curl -sS -o /dev/null -w '%{http_code}\n' "$SITE/wp-json/wp/v2/users/me" -K <(auth)
curl -sS "$SITE/wp-json/wp/v2/users/me" -K <(auth); echo

# 1. Abrir sesión y guardar el ID
curl -sS -D headers.txt "$MCP" -K <(auth) \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"'"$PV"'","capabilities":{},"clientInfo":{"name":"curl","version":"1.0"}}}'
SID=$(grep -i '^mcp-session-id:' headers.txt | awk '{print $2}' | tr -d '\r')

# 2. Confirmar la sesión
curl -sS "$MCP" -K <(auth) \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","method":"notifications/initialized"}'

# 3. Listar herramientas: solo las de los paquetes del usuario (las dieciséis, con los seis paquetes)
curl -sS "$MCP" -K <(auth) \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' \
  | php -r '$r = json_decode(stream_get_contents(STDIN), true); foreach ($r["result"]["tools"] ?? [] as $t) echo $t["name"], PHP_EOL; if (isset($r["error"])) print_r($r["error"]);'
```

Para llamar a una herramienta, con la misma sesión:

```bash
curl -sS "$MCP" -K <(auth) \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"wp-connect-mcp-list-posts","arguments":{"per_page":5}}}' \
  | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;'
```

Para no repetir cabeceras en cada llamada, se puede definir esta función en la terminal (con la sesión ya abierta). Muestra `OK`, `RECHAZADO` o `ERROR JSON-RPC`:

```bash
mcp_call() {
  curl -sS "$MCP" -K <(auth) \
    -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
    -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
    -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"'"$1"'","arguments":'"$2"'}}' \
  | php -r '$r = json_decode(stream_get_contents(STDIN), true); $res = $r["result"] ?? null;
    if (!$res) { echo "ERROR JSON-RPC: ", json_encode($r["error"] ?? $r, JSON_UNESCAPED_UNICODE), PHP_EOL; exit; }
    if (!empty($res["isError"])) { echo "RECHAZADO: ", $res["content"][0]["text"] ?? "", PHP_EOL; exit; }
    $p = $res["structuredContent"]; echo "OK: ", $p["id"] ?? "", " ", $p["status"] ?? "", " ", $p["date"] ?? "", PHP_EOL;'
}

mcp_call wp-connect-mcp-create-post '{"title":"Prueba MCP"}'
mcp_call wp-connect-mcp-update-post '{"id":123,"status":"publish"}'          # Autor: OK ... publish. Colaborador: RECHAZADO
WHEN=$(date -d '+10 minutes' +%Y-%m-%dT%H:%M:%S%:z)
mcp_call wp-connect-mcp-update-post '{"id":123,"scheduled_date":"'"$WHEN"'"}' # debe dar OK ... future
mcp_call wp-connect-mcp-trash-post '{"id":123}'                                # debe dar OK 123 trash
```

`schedule-posts` responde con una lista, así que se lee aparte (con dos entradas propias, por ejemplo 123 y 124):

```bash
WHEN=$(date -d '+1 day' +%Y-%m-%dT09:00:00%:z)
curl -sS "$MCP" -K <(auth) \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"wp-connect-mcp-schedule-posts","arguments":{"posts":[{"id":123,"scheduled_date":"'"$WHEN"'"},{"id":124,"scheduled_date":"'"$WHEN"'"}]}}}' \
  | php -r '$r = json_decode(stream_get_contents(STDIN), true); $res = $r["result"] ?? null;
    if (!$res) { echo "ERROR JSON-RPC: ", json_encode($r["error"] ?? $r, JSON_UNESCAPED_UNICODE), PHP_EOL; exit; }
    if (!empty($res["isError"])) { echo "RECHAZADO: ", $res["content"][0]["text"] ?? "", PHP_EOL; exit; }
    foreach ($res["structuredContent"]["posts"] as $p) { echo "OK: ", $p["id"], " ", $p["status"], " ", $p["date"], PHP_EOL; }
    foreach ($res["structuredContent"]["failed"] as $f) { echo "FALLO: ", $f["id"], " ", $f["error"], PHP_EOL; }'
```

#### Enviar el contenido desde un archivo

Cuando el contenido ya está en un archivo (por ejemplo, el HTML de un post redactado por Claude), un script local puede enviarlo al MCP sin que Claude tenga que leerlo y volver a escribirlo en la llamada: en un lote de 10 posts, eso son unos 220 KB menos de contexto. El servidor no necesita nada especial: es una llamada `tools/call` normal, con la misma sesión. Aquí, con la sesión abierta (pasos 1 y 2), se crea un borrador:

```bash
read -r -p "Archivo con el contenido (HTML): " FILE
read -r -p "Título: " TITLE
php -r 'echo json_encode(["jsonrpc" => "2.0", "id" => 4, "method" => "tools/call", "params" => ["name" => "wp-connect-mcp-create-post",
  "arguments" => ["title" => $argv[2], "content" => file_get_contents($argv[1]), "status" => "draft"]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);' \
  "$FILE" "$TITLE" > peticion.json
curl -sS "$MCP" -K <(auth) \
  -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $PV" \
  --data-binary @peticion.json \
  | php -r '$r = json_decode(stream_get_contents(STDIN), true); $p = $r["result"]["structuredContent"] ?? null;
    if (!$p) { echo "ERROR: ", json_encode($r["result"]["content"][0]["text"] ?? $r["error"] ?? $r, JSON_UNESCAPED_UNICODE), PHP_EOL; exit(1); }
    echo "OK: ", $p["id"], " ", $p["status"], " · contenido ", $p["content_hash"] === md5_file($argv[1]) ? "idéntico" : "DISTINTO del archivo (revisar con get-post)", PHP_EOL;' "$FILE"
rm -f peticion.json
```

- **Comprobar que ha llegado entero:** la respuesta trae `content_hash` (MD5 del contenido guardado), que se compara con el del archivo entero: el script lo envía tal cual, con el salto de línea final, y WordPress lo guarda así. Si no coincide, WordPress lo ha cambiado al guardarlo (por ejemplo, filtra el HTML de los roles sin `unfiltered_html`, como Autor o Colaborador) y conviene revisarlo con `get-post`.
- **Credencial:** un script para el agente puede tomar la cabecera `Authorization` de la conexión guardada en `~/.claude.json` (`mcpServers` → nombre de la conexión → `headers`), sin mostrarla ni pasarla como argumento de `curl`. Los límites del servidor (paquetes, solo entradas propias, límite por hora) se aplican igual; lo que se salta es el permiso que Claude Code pide antes de cada herramienta, así que el script debe ejecutarse solo con el visto bueno de la persona.

Los errores de las herramientas llegan dentro de `result`, con `isError: true` y el mensaje en `content[0].text`. Los rechazos por esquema (estado no permitido, campo desconocido) llegan con el texto de WordPress «tiene una entrada no válida».

### Lista de comprobación

Los resultados de cada ronda de pruebas se anotan en [ESTADO.md](ESTADO.md).

- `tools/list` devuelve solo las herramientas de los paquetes del usuario: las dieciséis con los seis, cinco solo con *Leer*. Sin paquetes, `initialize` da 403.
- La contraseña de la conexión da 403 (`wp_connect_mcp_password_scope`) en `wp/v2/users/me` y en `wp/v2/posts`; una contraseña creada a mano en el perfil da 403 en el MCP.
- Con algún paquete, una contraseña creada a mano en el perfil del mismo usuario da error (`wp_connect_mcp_api_blocked`) en `wp/v2/users/me`; quitándole todos los paquetes, vuelve a funcionar. El usuario sigue pudiendo entrar en el escritorio y ver la vista previa de sus borradores.
- `get-current-user` devuelve `version` (la del plugin) y `password_scope: "mcp"`.
- `create-category`: crea una categoría y, repetida con el mismo nombre o slug, devuelve la misma con `created: false`; sin el paquete, `Permission denied`. `create-post` con una etiqueta que no existe: con el paquete se crea; sin él, error con la lista de las que faltan y no se crea la entrada.
- *Leer el sitio*: `list-published-posts` incluye entradas publicadas de otros autores; `list-pages`, las páginas publicadas; `get-published` devuelve el esquema sin `content` y, con `include_content: true`, el contenido. Un borrador, una privada o una página con contraseña dan el mismo error que un ID inexistente.
- Con *Leer* + *Escribir*, `trash-post` da `Permission denied`. Quitar todos los paquetes a un usuario revoca sus conexiones (`claude mcp get` deja de conectar) y queda en el registro.
- El registro de uso de *Usuarios → Acceso de Claude* muestra las herramientas usadas, los accesos denegados y los cambios de permisos.
- `/wp-json/mcp/mcp-adapter-default-server` da 404 (servidor por defecto desactivado), salvo con `wp_connect_mcp_keep_default_server`.
- `list-categories` devuelve IDs utilizables; `get-current-user` confirma el usuario y rol antes de las pruebas; `get-template-status` no devuelve contenido de Elementor y detecta una plantilla de Archivo con `include/archive` o con condiciones de categoría (`exists: true`), y lista en `templates` las que no cubren el blog.
- `create-post` y `update-post` responden sin `content` y con `content_hash` igual al MD5 del contenido enviado (si el agente lo copió de un fichero, sin su salto de línea final); con `return_content: true`, con el contenido.
- Crear un borrador con `slug` y, si Yoast está activo, sus tres campos SEO; `get-post` devuelve los valores guardados.
- `upload-media` acepta una imagen válida menor de 2 MB, permite usar su ID como `featured_image` y rechaza un archivo falso con extensión `.jpg`, un archivo mayor del límite y una imagen de otro usuario.
- Crear un borrador, leerlo, editar su título y enviarlo a la papelera funciona; desde el escritorio se puede restaurar.
- Programar con `scheduled_date` a 10 minutos deja la entrada en `future`, y se publica sola a esa hora. El cron de WordPress solo se ejecuta cuando alguien visita el sitio, así que puede tardar algo más.
- `update-post` con `replace`: una sustitución que aparece una vez cambia solo ese fragmento y `content_hash` coincide con el de la copia local con el mismo cambio; con una búsqueda que no aparece o aparece dos veces, error con su número y la entrada no cambia; junto a `content`, error.
- *Plantillas de Elementor*: `list-templates` devuelve las plantillas guardadas publicadas con su shortcode; un borrador de prueba con `<!-- wp:shortcode -->[elementor-template id="N"]<!-- /wp:shortcode -->` la muestra en la vista previa con la plantilla «Entrada individual»; con el ID de una plantilla en borrador, de cabecera o inexistente, `wp_connect_mcp_invalid_template`; sin el paquete, `wp_connect_mcp_templates_not_allowed`.
- `update-media` cambia el `alt` y el título de una imagen subida por el usuario; con una de otro usuario o un ID que no es una imagen, el mismo error que con un ID inexistente.
- `reviewed_by` en `create-post` se guarda en el campo `wp_connect_mcp_reviewed_by`, `get-post` lo devuelve y, vacío en `update-post`, se borra.
- `schedule-posts` con dos entradas propias las deja en `future` con su fecha en una sola llamada, y el registro anota los dos IDs. Con una entrada de otro usuario, una fecha pasada o un ID repetido en la lista, no programa ninguna y el error dice cuál falla. Con un Colaborador, `Permission denied`.
- Con un Autor, `status: publish` publica una entrada propia (también una programada, con la fecha actual) y `status: draft` la despublica.
- Fallan como deben: `status: publish` con un Colaborador, `status: trash`, el ID de una página, una fecha pasada, un campo desconocido (`post_type`), un usuario Suscriptor, `update-post` y `trash-post` con una entrada de otro usuario (también con un Editor). Con un Autor, `trash-post` de una entrada propia publicada funciona y la retira del sitio.
- Página de conexión: sin marcar la casilla no crea nada; con un Editor o Administrador muestra el aviso; los comandos no contienen la contraseña. El bloque de Mac y Linux, sin `</dev/tty` y con las respuestas por tubería, falla con un mensaje claro y sin guardar nada con la contraseña vacía o incorrecta, un sitio sin plugin o sin conexión y sin `claude`, y pregunta antes de sustituir una conexión existente.
- Funciona junto a otros plugins que usan el adapter MCP (Novamira).

### Problemas habituales

| Síntoma | Causa probable |
|---|---|
| `404` / `rest_no_route` | Plugin inactivo o WordPress anterior a 6.9 (sale un aviso en el escritorio). |
| `403` al abrir sesión (`initialize`) | El usuario no tiene ningún paquete en *Usuarios → Acceso de Claude*, o la contraseña se creó a mano en el perfil en lugar de desde *Herramientas → Conectar con Claude*. |
| `403` `wp_connect_mcp_password_scope` | Es lo esperado: la contraseña de la conexión solo vale para el MCP, no para `wp/v2`. |
| «Se ha alcanzado el máximo de N usos por hora» | Límite por hora de esa herramienta. Esperar o cambiarlo con `wp_connect_mcp_rate_limits`. |
| `401` `incorrect_password` | El usuario existe, pero la Application Password no vale: es de otro sitio u otro usuario, o se pegó incompleta (`echo ${#WP_APP_PASS}` debe dar 24 o 29). Generar una nueva en el perfil de ese usuario. |
| `401` `invalid_username` | El usuario no existe en ese sitio. |
| `401` sin código de WordPress | El servidor no pasa la cabecera `Authorization` a PHP (Apache con FastCGI). |
| `curl: (23) Failure writing output` | Falla el comando que va detrás de la tubería (por ejemplo, `jq` no instalado). Usar las líneas con `php -r`. |
| `Missing Mcp-Session-Id header` | `$SID` vacío: revisar `headers.txt`. |
| `-32005 Session not found` | La sesión no es de ese usuario o ha caducado. Al cambiar `WP_USER` hay que repetir los pasos 1 y 2: cada sesión va ligada al usuario que la abrió. |
| Error de `MCP-Protocol-Version` | Falta esa cabecera en los pasos 2 y siguientes. |
| `Permission denied` con un Autor o Colaborador | La entrada es de otro usuario: estos roles solo editan las suyas. |
| «Solo se pueden modificar o borrar las entradas creadas por este usuario» | La entrada es de otro usuario. Pasa también con Editor y Administrador: el plugin solo modifica y borra las propias. |
| La respuesta es una página HTML («CrowdSec Ban», «Access denied», Cloudflare) | El cortafuegos del sitio ha bloqueado la IP, normalmente por muchas llamadas seguidas. No reintentar: esperar a que se levante o pedírselo a sistemas, y usar `schedule-posts` o llamadas en serie. |
| `wp_connect_mcp_api_blocked` con una contraseña creada a mano | Es lo esperado: el usuario tiene acceso de Claude y solo puede usar la API por el MCP. Para otra integración, usar otro usuario. |
| `wp_connect_mcp_tags_not_found` | La etiqueta no existe y el usuario no tiene el paquete *Categorías y etiquetas*. Usar una existente o pedir el paquete. |
| `wp_connect_mcp_replace_not_unique` | Una búsqueda de `replace` no aparece exactamente una vez en el contenido guardado (el error dice cuál y cuántas veces). Leer la entrada con `get-post` y alargar la búsqueda hasta que sea única. No se ha cambiado nada. |
| `wp_connect_mcp_templates_not_allowed` / `wp_connect_mcp_invalid_template` | El contenido añade `[elementor-template]` sin el paquete *Plantillas de Elementor*, o con una plantilla que no está publicada, no es de sección, contenedor o página, o lleva más atributos que `id`. Usar un ID de `list-templates`. |
| Menos de dieciséis herramientas | Es lo normal si el usuario no tiene los seis paquetes. Si los tiene, revisar el log de PHP; los errores del plugin llevan el prefijo `[wp-connect-mcp]`. |

## Desarrollo

```bash
composer install          # o composer dump-autoload tras añadir clases
```

El estado del proyecto, los pendientes, las decisiones y el historial de pruebas están en [ESTADO.md](ESTADO.md).

Código propio en `includes/` (PSR-4 `WpConnectMcp\`):

- `Plugin.php`: registra la categoría, las abilities, el servidor MCP, los filtros de seguridad y, en el escritorio, las páginas de conexión y de acceso.
- `Admin/ConnectPage.php`: página *Herramientas → Conectar con Claude* (crea la contraseña de aplicación, la muestra aparte y da los bloques para la terminal de cada sistema).
- `Admin/GrantsPage.php`: página *Usuarios → Acceso de Claude* (paquetes por usuario y registro de uso).
- `Security/Grants.php`: paquetes (`PACKAGES`), paquetes de cada usuario, revocación y migración a 0.3.0. **Cada ability nueva debe entrar en un paquete.**
- `Security/Guard.php`: envuelve cada ability (`Guard::protect()`): paquete y límite por hora antes del permiso propio, registro tras ejecutarla; filtra `tools/list` y da acceso al transporte.
- `Security/ApplicationPasswordScope.php`: limita las contraseñas del plugin a la ruta del MCP.
- `Security/AuditLog.php`: registro de uso (opción `wp_connect_mcp_log`).
- `Abilities/`: una clase por ability (`NAME`, `register()`, `execute()`).
- `Support/PostInput.php`: saneado y reglas de estado y programación.
- `Support/PostExtras.php`: validación y escritura de Yoast, imagen destacada y «revisado por» (`reviewed_by`).
- `Support/ContentReplace.php`: sustituciones parciales del contenido (`replace` de `update-post`).
- `Support/SavedTemplates.php`: plantillas guardadas de Elementor que se pueden listar e insertar, y comprobación de `[elementor-template]` en el contenido.
- `Support/PostAccess.php`: carga de la entrada y comprobación de permisos.
- `Support/PostFormatter.php`: formato y esquemas de salida.
