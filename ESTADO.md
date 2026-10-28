# Estado del proyecto

Registro vivo de cómo se construye y prueba el plugin. Se actualiza en cada paso: qué se hizo, qué se probó, qué se decidió y qué queda pendiente. Lo más reciente va arriba en cada sección.

## Situación actual

- **Versión:** 0.2.0 en `main`, sin tag hasta probar publicar en el sitio. La 0.1.0 tiene el tag `v0.1.0` (commit `169509e`).
- **Estado:** funcionando en `test.wwwarcelona.net`. Probados conexión, `tools/list`, crear, leer, editar, programar (con publicación automática confirmada), los rechazos principales, el usuario Suscriptor y una tarea real desde Claude Code con la conexión de la página.
- **Documentación:** README, AGENTS.md y CHANGELOG al día a 27/10/2026. Nuevo [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md): alternativas para usar IA en WordPress, seguridad y control de funciones por paquetes.
- **Nuevo nombre (28/10/2026, sin subir):** el plugin pasa a llamarse **WP Connect MCP** (`wp-connect-mcp`), sin referencias a la marca: carpeta y fichero principal `wp-connect-mcp/wp-connect-mcp.php`, espacio de nombres `WpConnectMcp\`, endpoint `/wp-json/wp-connect-mcp/mcp`, herramientas `wp-connect-mcp-*` y conexión `wp-connect-mcp-<dominio>`. Rompe lo instalado: hay que instalarlo como plugin nuevo y volver a crear la conexión. Antes se llamaba «wwwarcelona WP Connect» (`wwwarcelona-wp-connect`): las filas de pruebas, decisiones e historial anteriores a este cambio conservan los nombres de entonces (ruta `/wwwarcelona/mcp`, herramientas `wwwarcelona-*`, conexión `wwwarcelona-<dominio>`).
- **Publicar entradas propias (28/10/2026, sin subir):** `create-post` y `update-post` admiten `status: publish`, solo para entradas propias y si el usuario puede publicar (Autor o superior). Publicar una programada la publica ahora. Las propias publicadas se pueden despublicar (a borrador o pendiente). Cambia la decisión del 26/10 de que el agente no publica.
- **Cambio provisional (28/10/2026, subido y probado en parte):** el agente puede corregir sus errores. `update-post` solo modifica entradas creadas por el usuario de la conexión, sea cual sea su rol, y la nueva `trash-post` envía a la papelera (recuperable) las propias en cualquier estado, también publicadas (cambio pedido el mismo día). Probado con un Autor el ciclo completo (crear, programar, editar, papelera y restaurar); faltan los rechazos y los demás roles.
- **Hallazgo de seguridad:** la contraseña de aplicación de la conexión vale para toda la API REST (`wp/v2`), no solo para el MCP; un Autor podría publicar y borrar saltándose el plugin. No usar en producción hasta limitarla a la ruta (ver docs/ALTERNATIVAS.md, alternativa A).
- **Siguiente paso:** revisar [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) con backend y sistemas, marketing y dirección, y decidir la alternativa antes de seguir con el código (v0.2). Mientras tanto, subir la nueva `ConnectPage.php` a `test.wwwarcelona.net` y revocar la conexión antigua del usuario ID 3. Claude Code ya está conectado con `test-mcp-sub` (ID 6), que ahora es Autor.

## Pendientes

- [ ] Instalar el plugin renombrado en `test.wwwarcelona.net`. El 28/10/2026 se subieron por error los ficheros anteriores al cambio de nombre (confirmado por Adrián; por eso sigue la ruta antigua). Pasos: desactivar y borrar «wwwarcelona WP Connect» (carpeta `wwwarcelona-wp-connect/`), subir la carpeta completa como `wp-content/plugins/wp-connect-mcp/` (con `vendor/`) y activarla. Crear la conexión de nuevo desde la página (`wp-connect-mcp-test-wwwarcelona-net`) y comprobar `tools/list`: cinco herramientas `wp-connect-mcp-*`. Las conexiones antiguas de Claude Code dejan de funcionar.
- [ ] Decidir si se renombra la carpeta local del repositorio (`wwwarcelona-wp-connect`); el remoto ya es `wp-connect-mcp`.
- [x] `git push origin main` de la 0.2.0 (`84e6666`) y del ESTADO (`c6f667d`), hecho por Adrián desde fuera de Claude Code (aquí no hay credenciales de GitHub). Hecho el 28/10/2026.
- [ ] Tags: no enviarlos por ahora (decisión de Adrián, 28/10/2026). `v0.1.0` existe solo en local. Crear `v0.2.0` y la release cuando publicar esté probado y se decida enviarlos.
- [ ] Subir a `test.wwwarcelona.net` `includes/Support/PostInput.php`, `includes/Abilities/CreatePost.php`, `includes/Abilities/UpdatePost.php`, `includes/Plugin.php`, `includes/Admin/ConnectPage.php` y `wp-connect-mcp.php` (versión 0.2.0), y probar con el Autor: publicar la 2501 (programada) con `update-post` y `status: publish` (debe salir ya, con la fecha actual), crear una entrada directamente publicada, despublicar una propia (`status: draft`) y `status: publish` con `scheduled_date` (rechazado). Con un Colaborador, `status: publish` debe rechazarse.
- [ ] Entrada de prueba 2501 («Nuevo post»), programada para el 28/10/2026 a las 12:57: se publicará sola. Decidir si se deja o se envía a la papelera.
- [ ] Valorar si `PostAccess::find()` debe dar `Permission denied` en lugar de «El ID no corresponde a una entrada (post)» cuando el ID es de otro tipo: hoy deja saber a quien tiene `edit_posts` que ese ID existe y no es una entrada (también para páginas en borrador o privadas). Riesgo bajo.
- [x] Entrada de prueba 2499 («Qué tal»): enviada a la papelera con `trash-post` antes de publicarse, y restaurada desde el escritorio. Hecho el 28/10/2026.
- [x] Entrada 2499 («Qué tal») programada de nuevo para el 28/10/2026 a las 12:34: se publicó sola y se envió a la papelera ya publicada. Hecho el 28/10/2026.
- [x] Entrada 2499: vaciada la papelera desde el escritorio. Hecho el 28/10/2026.
- [ ] Quitar el conector de claude.ai «WP NovaMira wwwarcelona test site» (claude.ai → Ajustes → Conectores). No se puede quitar desde Claude Code. El 28/10/2026 seguía configurado, aunque fallaba con HTTP 502.
- [x] Subir a `test.wwwarcelona.net` los ficheros de `trash-post` y entradas propias, y probar con un Autor `tools/list` (cinco herramientas) y `trash-post` de una programada propia, restaurable desde el escritorio. Hecho el 28/10/2026.
- [ ] Pruebas que faltan de `trash-post` y entradas propias: `update-post` de una entrada de otro usuario con el Editor (rechazado); Suscriptor (`Permission denied`); Colaborador con una entrada suya ya publicada: `get-post`, `update-post` y `trash-post` deben rechazarse.
- [ ] Decidir, junto con la alternativa, si se mantiene el borrado de entradas propias y la restricción de `update-post` a entradas propias.
- [ ] Revisar [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) con backend y sistemas, marketing y dirección, y responder a las preguntas del apartado 8.
- [ ] Si se aprueba la alternativa A (v0.3): limitar las contraseñas del plugin a `/wp-connect-mcp/mcp`, paquetes de funciones por usuario, registro de uso y límite de volumen.
- [x] Subir `includes/Admin/ConnectPage.php` con el aviso del usuario y comprobar que lo muestra antes y después de crear la conexión. Hecho el 28/10/2026.
- [x] Revocar la conexión creada con el usuario ID 3 (perfil de ese usuario → *Contraseñas de aplicación*). La nueva ya está en Claude Code, con `test-mcp-sub` (ID 6, Autor). Hecho el 28/10/2026.
- [ ] Para seguir probando (publicar, Editor, Colaborador, Suscriptor), crear la conexión de nuevo desde la página: la de `test-mcp-1` se quitó de Claude Code el 28/10/2026. Revocar su contraseña en el perfil de `test-mcp-1` si no se va a usar.
- [ ] Crear usuarios de pruebas cuando haga falta repetir pruebas: `test-mcp-sub` (ID 6) se eliminó el 28/10/2026 y la conexión `wwwarcelona-test-wwwarcelona-net` se quitó de Claude Code el mismo día. Crear otra desde la página con el usuario nuevo.
- [x] Entrada de prueba 2470 («Hola»): programada, se publicará sola el 28/10/2026 a las 9:00. Si no debe salir, pasarla a borrador (lo puede hacer el agente con la conexión actual). Hecho el 28/10/2026.
- [x] Decidir qué hacer con la entrada de prueba 2467 («Prueba desde Claude Code»): está programada y se publicará sola el 28/10/2026 a las 9:00. Para evitarlo, pasarla a borrador o a la papelera desde el escritorio. Hecho el 28/10/2026.

- [x] Confirmar que la entrada 2453 pasa de `future` a `publish` sola.
- [x] Enviar la entrada de prueba 2453 a la papelera desde el escritorio (el agente no puede borrar). Hecho el 28/10/2026.
- [x] Probar con un usuario Suscriptor: todas las herramientas deben rechazarse.
- [x] Subir `includes/Support/PostAccess.php` corregido a `test.wwwarcelona.net` y repetir `get-post` como Suscriptor: debe dar `Permission denied`.
- [x] Revisar el log de PHP en `test.wwwarcelona.net`: solo debería aparecer el aviso de "deprecated" del adapter.
- [ ] **Fuera del plugin, seguridad del sitio:** `https://test.wwwarcelona.net/wp-content/debug.log` se puede descargar desde internet (HTTP 206, 27/10/2026). Mover el log fuera de la carpeta pública (`define('WP_DEBUG_LOG', '/ruta/fuera/de/htdocs/debug.log');`) o bloquearlo en la configuración del servidor. Revisar lo mismo en otros sitios. El log pesa 152 MB y está lleno de avisos de otros plugins.
- [ ] Valorar instalar el plugin oficial MCP Adapter en lugar de llevarlo dentro de `vendor/`, para quitar el aviso de "deprecated" (v0.3).
- [x] `wp-json.json`: borrado confirmado en el commit `a981dd7`.
- [x] Primer commit (`a981dd7`, en `main`).
- [x] Commit de la corrección de `PostAccess.php`, `ESTADO.md` y la documentación (`587e6f9`).
- [x] Subir `includes/Admin/ConnectPage.php` e `includes/Plugin.php` a `test.wwwarcelona.net`, crear la conexión desde *Herramientas → Conectar con Claude*, pegar el comando y comprobar que Claude Code conecta.
- [x] Pedir a Claude, en una sesión nueva, una tarea real con el sitio conectado.
- [ ] Revisar la página con un usuario administrador (debe recomendar un usuario propio para Claude) y comprobar que la conexión aparece en la lista y se puede revocar desde el perfil.
- [ ] Probar el comando de la página en Windows (PowerShell) y en un Mac.
- [ ] Probar los bloques de la alternativa por terminal en Windows (PowerShell) y en un Mac (zsh). En Linux funciona el de Linux y macOS.
- [ ] Decidir si el agente puede publicar y editar entradas publicadas sin revisión. Desde el 28/10/2026 puede, con entradas propias y rol Autor o superior; para exigir revisión, conectar con un Colaborador.
- [ ] Decidir si el agente puede crear etiquetas nuevas o solo usar las existentes.
- [ ] Acordar el rol estándar del usuario del agente: Autor, que puede programar, o Colaborador, que solo deja entradas pendientes.

## Pruebas en test.wwwarcelona.net

Usuarios de prueba con Application Password: `test-mcp-1` (ID 7, Autor, conexión actual de Claude Code desde el 28/10/2026), `test-mcp` (ID 5, puede crear y programar) y `test-mcp-sub` (ID 6, eliminado el 28/10/2026; era Suscriptor en las pruebas del 26 y 27/10, ahora es Autor y su nombre visible es «test-mcp»). Comandos en la sección «Probar con curl» del README.

| Fecha | Prueba | Resultado |
|---|---|---|
| 28/10/2026 | Tras subir el plugin renombrado (según Adrián), comprobación sin credenciales: `POST` a `wp-connect-mcp/mcp` y a `wwwarcelona/mcp` (también con `?rest_route=` para saltar cachés), índice de `wp-json/`, `users/5`, `6`, `7`, `posts/2501` y `claude mcp list` | ❌ `wp-connect-mcp/mcp` da 404 (`rest_no_route`) y `wwwarcelona/mcp` da 401: sigue registrada la ruta antigua y no la nueva. La 2501 da 401: existe, aunque no es pública (borrador o papelera); no está borrada definitivamente. Los usuarios dan 404, que no confirma nada (la API pública oculta a quien no tiene entradas publicadas). En Claude Code no queda ninguna conexión del plugin ✅ |
| 28/10/2026 | Programar la 2501 (último post según `list-posts`) con `update-post` y `scheduled_date` a 10 minutos, con `test-mcp-1` (Autor) | ✅ `future`, `2026-10-28T12:57:32+01:00` |
| 28/10/2026 | `create-post` «Nuevo post» («Prueba para test») sin estado, con `test-mcp-1` (Autor, confirmado con `users/me?context=edit`) | ✅ Entrada 2501 en `draft`, autor ID 7 |
| 28/10/2026 | Páginas con `test-mcp-1` (Autor): crear una página (`create-post` con `post_type: page`), editar la página Elementor 2232 «Avinent – Polarix v2» (`update-post`), enviarla a la papelera (`trash-post`) y leerla (`get-post`). IDs buscados en la API pública, sin credenciales | ✅ Crear: rechazado por el esquema («post_type no es una propiedad válida»). Editar, papelera y leer: «El ID no corresponde a una entrada (post).» No hay ninguna herramienta para Elementor. La 2232 sigue igual (`publish`, modificada el 27/10/2026) |
| 28/10/2026 | `trash-post` de la 2499 en borrador (propia; restaurada desde el escritorio tras la prueba anterior, confirmado con `get-post`), con `test-mcp-1` (Autor) | ✅ Pasa de `draft` a `trash` |
| 28/10/2026 | `trash-post` de la 2499 ya publicada (propia; publicada sola a las 12:34, confirmado por Adrián y por `get-post`), con `test-mcp-1` (Autor) | ✅ Pasa de `publish` a `trash`. La API pública ya no la sirve (HTTP 401) |
| 28/10/2026 | Entrada 1439 «test 2 – guillem» (de otro usuario, ID 4, publicada) con `test-mcp-1` (Autor): `list-posts` con `search: guillem`, `get-post`, `update-post` y `trash-post`. El ID se buscó en la API pública, sin credenciales | ✅ No aparece al listar; `get-post` da `Permission denied`; `update-post` y `trash-post` dan «Solo se pueden modificar o borrar las entradas creadas por este usuario». Comprobado por la API pública que sigue sin cambios (`publish`, modificada el 17/04/2026) |
| 28/10/2026 | Volver a programar la 2499 (restaurada, en borrador) con `update-post` y `scheduled_date` a 10 minutos, con `test-mcp-1` (Autor) | ✅ `future`, `2026-10-28T12:34:12+01:00` |
| 28/10/2026 | Restaurar la 2499 desde *Entradas → Papelera* (comprobado por Adrián en el escritorio) | ✅ Vuelve como borrador y sin programar: no se publica sola; para que salga hay que volver a programarla o publicarla |
| 28/10/2026 | `trash-post` de la 2499 (propia, programada), con `test-mcp-1` (Autor) | ✅ Pasa a `trash` antes de su hora de publicación y no se publica |
| 28/10/2026 | `update-post` de la 2499 (propia, programada): título «Qué tal» y contenido «Hola», con `test-mcp-1` (Autor) | ✅ Cambian título y contenido; sigue en `future` a las 12:28 y el slug se queda en `hola` |
| 28/10/2026 | Conexión `wwwarcelona-test-wwwarcelona-net` nueva: usuario y rol con `users/me?context=edit`, y `tools/list` | ✅ `test-mcp-1` (ID 7), rol `author`. Salen las 5 herramientas, `trash-post` incluida: la versión nueva está subida |
| 28/10/2026 | `create-post` «Hola» («Qué tal») programada a 10 minutos, con `test-mcp-1` (Autor) | ✅ Entrada 2499 en `future`, `2026-10-28T12:28:28+01:00`, autor ID 7 |
| 27/10/2026 | Contraseña de la conexión de Claude Code (`test-mcp-sub`, ID 6, Autor, confirmado con `users/me?context=edit`) contra la API general: `GET wp/v2/users/me` y `GET wp/v2/posts?status=draft`. Solo lectura | ⚠️ Los dos dan `200 OK`; el usuario tiene `publish_posts` y `delete_posts`. La credencial sirve fuera del MCP: podría publicar y borrar por `wp/v2`. Documentado en docs/ALTERNATIVAS.md |
| 27/10/2026 | `create-post` «Hola» («Qué tal») programada para el 28/10/2026 a las 9:00, con `test-mcp-sub` (Autor) | ✅ Entrada 2470 en `future`, `2026-10-28T09:00:00+01:00`, autor ID 6 |
| 27/10/2026 | Conexión `wwwarcelona-test-wwwarcelona-net` de Claude Code: usuario y rol con `users/me` | ✅ `test-mcp-sub` (ID 6), rol `author`, con `edit_posts` y `publish_posts`. Con esta conexión la 2467 (del usuario ID 3) da 403: un Autor no puede editar entradas de otros |
| 27/10/2026 | Tarea real: borrar la 2467. No hay herramienta de borrado; se intenta con `update-post` y `status: trash` | ✅ Rechazado por el esquema: «input[status] no es uno de draft, pending y future» |
| 27/10/2026 | Tarea real desde Claude Code (conexión creada en la página, usuario ID 3): intentar publicar la 2467 con `update-post` y `status: publish` | ✅ Rechazado por el esquema: «input[status] no es uno de draft, pending y future» |
| 27/10/2026 | Tarea real: `create-post` «Prueba desde Claude Code» programada para el 28/10/2026 a las 9:00 sin zona horaria | ✅ Entrada 2467 en `future`, `2026-10-28T09:00:00+01:00` (zona del sitio). `modified` sale igual a la fecha programada: es lo que hace WordPress al crear una entrada con fecha |
| 27/10/2026 | Tarea real: `list-posts` con `per_page: 5` | ✅ 3 entradas (las que el usuario puede editar: 1439, 1437, 1238). La 2453, en la papelera, no aparece |
| 27/10/2026 | Página *Herramientas → Conectar con Claude* en Linux: crear conexión, copiar y pegar el comando | ✅ Claude Code guarda `wwwarcelona-test-wwwarcelona-net` en la configuración de usuario. `claude mcp get` da «✔ Connected» |
| 27/10/2026 | Log de PHP desde la activación (26/10, 12:58 UTC) | ✅ Sin errores fatales, sin warnings y sin errores `[wwwarcelona-wp-connect]`. Solo el aviso esperado del adapter (28 veces, una por petición que lo inicializa). El resto de avisos son de otros plugins (Enlazatom, traducciones del tema y ACF) |
| 27/10/2026 | Suscriptor con la corrección subida: `get-post` con 2453 (existe, en papelera) y 99999 (no existe) | ✅ Los dos dan `Permission denied`: ya no se revela qué IDs existen |
| 26/10/2026 | Suscriptor `test-mcp-sub` (ID 6): `list-posts`, `create-post` | ✅ `Permission denied` |
| 26/10/2026 | Suscriptor: `get-post` con ID 2453 | ⚠️ Respondió «No existe ninguna entrada» en lugar de `Permission denied`: buscaba la entrada antes de comprobar permisos y dejaba averiguar qué IDs existen. Corregido en `PostAccess::canEdit()` y comprobado el 27/10/2026 |
| 26/10/2026 | Usar la sesión MCP de `test-mcp` con las credenciales de `test-mcp-sub` | ✅ Rechazado: «Session not found». Las sesiones van ligadas al usuario que las abre (el adapter las guarda por usuario) |
| 26/10/2026 | Publicación automática de la programada | ✅ La 2453 se publicó sola a la hora prevista (cron de WordPress) |
| 26/10/2026 | `update-post` con `scheduled_date` +10 min | ✅ `future`, publicación prevista a las 17:30:41 |
| 26/10/2026 | `get-post` con ID 1978 (página) | ✅ Rechazado: «El ID no corresponde a una entrada (post).» |
| 26/10/2026 | `update-post` con campo `post_type` | ✅ Rechazado por el esquema: propiedad no válida |
| 26/10/2026 | `update-post` con fecha pasada | ✅ Rechazado: «scheduled_date debe ser una fecha futura.» |
| 26/10/2026 | `update-post` con `status: publish` | ✅ Rechazado por el esquema: solo draft, pending y future |
| 26/10/2026 | `update-post`: cambiar título | ✅ Solo cambia el título; sigue en `draft` |
| 26/10/2026 | `get-post` de la 2453 | ✅ Devuelve todos los campos; fechas con zona `+01:00` |
| 26/10/2026 | `create-post` | ✅ Entrada 2453 en `draft`, autora `test-mcp`, categoría por defecto |
| 26/10/2026 | `tools/list` | ✅ Exactamente las 4 herramientas |
| 26/10/2026 | `initialize` | ✅ Servidor «wwwarcelona WP Connect» v0.1.0, protocolo 2025-11-25 |
| 26/10/2026 | Convivencia con Novamira (activo en el mismo sitio) | ✅ Sin conflictos aparentes. Falta revisar el log |
| 26/10/2026 | Primer intento de conexión | ❌ `incorrect_password`: Application Password no válida. Se generó una nueva y funcionó |

## Decisiones

| Fecha | Decisión | Motivo |
|---|---|---|
| 28/10/2026 | Versiones: tag `v0.1.0` en `169509e` (lo probado el 26 y 27/10) y versión 0.2.0 para papelera, entradas propias y publicar. El tag `v0.2.0` se crea cuando publicar esté probado en el sitio. El refuerzo de seguridad de la alternativa A pasa a v0.3. Las releases, como pre-release y con el aviso de no usar en producción | Hay un conjunto probado al que poder volver y otro con funciones nuevas. Etiquetar sin probar publicar daría por buena una versión sin comprobar |
| 28/10/2026 | El agente puede publicar (`status: publish`) entradas propias, si el usuario puede publicar. Publicar una programada la publica ahora, con la fecha actual. Las propias publicadas se pueden despublicar. Las privadas siguen sin poder cambiar de estado. Sustituye a «El agente no publica» (26/10/2026) | Pedido por Adrián: igual que el resto de acciones, solo sobre entradas propias. Quien necesite revisión previa conecta con un Colaborador, que no puede publicar. Provisional hasta decidir la alternativa |
| 28/10/2026 | `trash-post` también borra entradas propias ya publicadas (las manda a la papelera) | Pedido por Adrián: si el error ya está publicado, que la persona también pueda retirarlo. Sigue siendo recuperable y solo sobre entradas propias. El estado de una publicada sigue sin poder cambiarse con `update-post` (no se puede despublicar a borrador), pero sí su contenido |
| 28/10/2026 | Provisional, mientras se decide la alternativa: `update-post` y la nueva `trash-post` solo actúan sobre entradas creadas por el usuario de la conexión, aunque su rol permita tocar las de otros. Borrar es enviar a la papelera, y solo borradores, pendientes y programadas | Si la persona se equivoca, que pueda corregirlo sin entrar en el escritorio, sin poder tocar el trabajo de otros ni retirar lo publicado. La papelera permite deshacerlo; si está desactivada (`EMPTY_TRASH_DAYS` a 0) no se borra, porque WordPress borraría para siempre |
| 27/10/2026 | Antes de seguir con el código, informe de alternativas ([docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md)) para decidir con backend y sistemas, marketing y dirección. Propuesta: MCP reforzado con paquetes de funciones otorgados por usuario en el servidor | Backend y sistemas ven el MCP inseguro en producción, el MCP va a crecer con nuevas funciones y hay que comparar con Chrome, Playwright y n8n. El control no puede vivir solo en las skills: se pueden saltar |
| 27/10/2026 | La página dice con qué usuario se crea la conexión (nombre y usuario), junto al botón y en el aviso de conexión creada | En la prueba real la conexión salió del usuario ID 3 en lugar de `test-mcp`, porque era la sesión abierta en el escritorio. Claude actúa en nombre de ese usuario, así que tiene que quedar claro antes de pulsar |
| 27/10/2026 | Forma principal de conexión: página *Herramientas → Conectar con Claude*. Crea la contraseña de aplicación del usuario conectado y muestra un comando de una línea, válido en Mac, Linux y Windows. Los bloques de terminal quedan como alternativa | Simplificar al máximo: entrar en WordPress, pulsar un botón, copiar y pegar. Sin preguntas, sin crear contraseñas a mano y sin un bloque distinto por sistema |
| 27/10/2026 | Se acepta que la credencial quede en el historial de la terminal y en `~/.claude.json` | Es una contraseña de aplicación revocable, de un solo usuario y sin acceso al escritorio. La página avisa de no compartir el comando y enlaza a revocar |
| 27/10/2026 | Descartado por ahora OAuth (entrar con el usuario en el navegador, como Novamira) | Es lo más sencillo para el usuario, pero exige construir un servidor OAuth. Se valorará en una versión posterior |
| 27/10/2026 | Conectar Claude Code con bloques de comandos para copiar y pegar (Linux/macOS y Windows), sin ficheros ejecutables. Sustituye al script `bin/conectar-claude.sh`, que se ha retirado | Los usuarios no técnicos no tienen el repositorio: con un fichero habría que repartirlo y actualizarlo, no funcionaría en Windows y obligaría a ejecutar ficheros recibidos. Los bloques piden y confirman el sitio, validan antes de guardar y explican los errores |
| 27/10/2026 | Los bloques confirman el sitio (nombre y dirección) antes de pedir credenciales | Que la persona sepa con qué sitio se está conectando antes de escribir su contraseña |
| 27/10/2026 | Conexión en Claude Code con `--scope user` | `local` solo funciona en la carpeta donde se ejecuta; `project` dejaría la credencial en el repositorio |
| 28/10/2026 | Renombrar el plugin a WP Connect MCP (`wp-connect-mcp`) y quitar todas las referencias a wwwarcelona del código y la documentación del plugin, dentro de la 0.2.0 (sin publicar). Se quitan las cabeceras Author y Author URI | Pedido por Adrián: plugin genérico, con el mismo nombre que el repositorio. Cambian ruta, herramientas, abilities, códigos de error y espacio de nombres, por eso hay que reinstalar y reconectar |
| 26/10/2026 | Comprobar `edit_posts` antes de buscar la entrada en `get-post` y `update-post` | Quien no puede editar recibe siempre `Permission denied` y no averigua qué IDs existen |
| 26/10/2026 | Pruebas solo con `curl` y `php -r`, sin `jq` | No instalar nada en los equipos |
| 26/10/2026 | Arranque en `plugins_loaded` | El autoloader de Jetpack elige la versión más nueva del adapter cuando ya conoce todos los plugins |
| 26/10/2026 | `jetpack-autoloader` como dependencia directa | Sin eso no generaba `vendor/autoload_packages.php` |
| 26/10/2026 | Servidor MCP propio en `/wp-json/wwwarcelona/mcp` con 4 tools | Que el agente no vea ninguna otra ability del sitio |
| 26/10/2026 | El agente no publica: solo `draft`, `pending` y `future` | Publicar lo decide una persona; programar sí se permite |
| 26/10/2026 | Solo entradas (`post`) | Alcance inicial limitado |
| 26/10/2026 | Las entradas publicadas se pueden editar, pero no cambiar su estado ni su fecha | Decisión provisional, pendiente de revisar |

## Historial

### 28/10/2026

- Adrián: subidos los ficheros, quitada la conexión local, eliminado el usuario de pruebas con sus entradas y hechas las verificaciones. Desde aquí, sin credenciales: confirmado que Claude Code no tiene ninguna conexión del plugin, pero el sitio sigue sirviendo `/wwwarcelona/mcp` y no `/wp-connect-mcp/mcp`, y la 2501 sigue existiendo. Adrián confirma que subió los ficheros anteriores, no los nuevos: va a borrar el plugin y subirlo de nuevo.
- Renombrado a WP Connect MCP: `wp-connect-mcp.php`, `WpConnectMcp\` (con `composer dump-autoload`), constantes `WP_CONNECT_MCP_*`, categoría, servidor, text domain y página `wp-connect-mcp`, ruta `wp-connect-mcp/mcp`, abilities `wp-connect-mcp/*`, códigos de error `wp_connect_mcp_*` y conexión `wp-connect-mcp-<dominio>` (página y bloques de terminal). README, AGENTS.md, CHANGELOG y docs/ALTERNATIVAS.md al día; los ejemplos usan `example.com`. `php -l` correcto. Sin subir ni probar en el sitio.
- Quitada de Claude Code la conexión `wwwarcelona-test-wwwarcelona-net` (`test-mcp-1`). No queda ninguna conexión local a `test.wwwarcelona.net`. La contraseña de aplicación sigue activa en WordPress hasta revocarla. Intentado `git push` sin tags: falló por credenciales.
- Versión 0.2.0: cabecera y constante del plugin, CHANGELOG separado en 0.2.0 y 0.1.0. Tag `v0.1.0` en local sobre `169509e`. El plan de la alternativa A pasa a v0.3.
- docs/ALTERNATIVAS.md: tabla «Qué permite cada vía con la misma credencial» (plugin frente a `wp/v2`, por acción y rol, con Elementor) en el apartado 2 y nota de Elementor en la alternativa C. README: punto de seguridad de la API REST actualizado (el riesgo ya no es publicar, sino borrar para siempre, tocar lo de otros y salir de las entradas) y nota de Elementor.
- Publicar entradas propias: `publish` añadido a `PostInput::STATUSES`, con `PostInput::publish()` (exige `publish_posts`/`publish_post`, no admite `scheduled_date` y pone la fecha actual para que una programada no vuelva a `future`). Textos de las herramientas, del servidor y de la página de conexión, README, CHANGELOG, AGENTS.md y docs/ALTERNATIVAS.md al día. `php -l` correcto y reglas probadas fuera de WordPress simulando sus funciones. Falta subir y probar en el sitio.
- Pedido publicar ya la 2501: el plugin no lo permitía y no se hizo por otra vía.
- Creada la entrada 2501 («Nuevo post») en borrador y programada para las 12:57.
- Adrián vació la papelera (la 2499 queda borrada definitivamente). Intentado con el Autor crear una página, editar con Elementor la 2232 (Polarix) y enviarla a la papelera: todo rechazado, la página no cambia.
- La 2499, restaurada otra vez (en borrador), se envió a la papelera: probado `trash-post` con un borrador propio.
- La 2499 se publicó sola a las 12:34 y se envió a la papelera ya publicada: deja de verse en el sitio.
- Vuelta a programar la 2499 para las 12:34. Intentado leer, editar y enviar a la papelera la 1439 («test 2 – guillem», de otro usuario) con el Autor: las tres se rechazan y la entrada no cambia.
- Adrián confirma en el escritorio el ciclo completo con la 2499: creada, programada, editada, enviada a la papelera por el agente y restaurada desde el panel, donde queda en borrador y sin programar.
- Enviada a la papelera la 2499 («Qué tal») con `trash-post`, antes de su hora de publicación.
- Editada la 2499: título «Qué tal» y contenido «Hola».
- Conectado con la nueva conexión (`test-mcp-1`, ID 7, Autor), creada desde la página. Llamadas hechas por HTTP leyendo la credencial de `~/.claude.json` sin mostrarla, porque las herramientas no estaban cargadas en la sesión. Programada la entrada 2499 («Hola») para las 12:28.
- Revisadas las conexiones MCP de Claude Code: ninguna local de Novamira apunta a `test.wwwarcelona.net` (solo iss, consiliors, ai y, en otros proyectos, fundaciopm y trailrun). Queda el conector de claude.ai «WP NovaMira wwwarcelona test site», que se quita desde claude.ai.
- README: nueva tabla «Quién puede usar cada herramienta» (comprobación de cada ability y qué puede hacer cada rol) y tabla de roles de «Conexión» ajustada. Las restricciones del Colaborador sobre entradas publicadas vienen de WordPress; falta comprobarlas en el sitio.
- `trash-post` admite también entradas publicadas: quitada la comprobación de estado. Documentación al día. Falta probar en el sitio.
- Corregir errores propios: `PostAccess::canEditOwn()` y `canDeleteOwn()` (comprueban la capacidad general antes de buscar la entrada y después que el autor sea el usuario actual); `update-post` usa `canEditOwn()`; nueva ability `trash-post` (`includes/Abilities/TrashPost.php`). Textos de la página de conexión y del servidor, README, CHANGELOG, AGENTS.md y docs/ALTERNATIVAS.md al día. `php -l` correcto y caminos probados fuera de WordPress simulando sus funciones (propia, de otro, publicada, programada, inexistente, repetida, sin capacidades). Falta probar en el sitio.
- Limpieza del sitio de pruebas (hecha por Adrián): subida la `ConnectPage.php` con el aviso del usuario y comprobada, revocada la conexión del usuario ID 3 y eliminadas las entradas de prueba 2470, 2467 y 2453 y el usuario `test-mcp-sub`. Comprobado desde aquí que la conexión de Claude Code ya da `invalid_username`. Quitada de Claude Code la conexión `wwwarcelona-test-wwwarcelona-net`: en local solo quedan las de Novamira (iss, consiliors, ai).

### 27/10/2026

- Nuevo [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md), a partir de `CONTINUACION_20261027.md`. Compara: MCP reforzado, MCP solo en staging, API REST directa, Claude in Chrome, Playwright, n8n (y n8n llamando al MCP), Novamira y generar un fichero para importar. Incluye control por paquetes frente a control en las skills, respuesta a backend y sistemas, recomendación y preguntas para la reunión.
- Comprobado (solo lectura) que la contraseña de la conexión funciona en `wp/v2`, fuera del MCP.
- Documentación completada. README: campos de cada herramienta, tabla de permisos por rol, instalación y actualización, «Uso con Claude Code», «Quitar una conexión», «Seguridad y limitaciones conocidas», lista de comprobación y problemas habituales ampliados. AGENTS.md: qué va en cada documento, despliegue, aviso del usuario en la página, cómo comprobar conexiones sin mostrar la contraseña, confirmar usuario y rol antes de anotar pruebas, no saltarse los límites con otras conexiones. CHANGELOG al día.
- Vuelto a conectar Claude Code desde la página, con `test-mcp-sub` (ID 6, Autor).
- Programada desde Claude Code la entrada 2470 («Hola») para el 28/10/2026 a las 9:00, con la conexión de `test-mcp-sub` (Autor).
- Revisada la conexión de Claude Code: usa `test-mcp-sub` (ID 6), que ahora es Autor. ESTADO.md lo seguía dando como Suscriptor; corregido.
- Eliminada también de Claude Code la conexión `wwwarcelona-test-wwwarcelona-net` (ya fallaba al conectar). Claude Code no tiene ninguna conexión a `test.wwwarcelona.net`; solo queda el conector de claude.ai de Novamira.
- Revisada la sesión de la tarea real: el autor de la 2467 es el usuario ID 3, no `test-mcp` (ID 5); la conexión se creó con la sesión abierta en el escritorio. La página ahora muestra con qué usuario se crea la conexión.
- Eliminada de Claude Code la conexión `novamira-test-wwwarcelona` (Novamira en `test.wwwarcelona.net`, usuario `adrian`), para que el agente solo tenga acceso al sitio a través de este plugin. Queda el conector de claude.ai «WP NovaMira wwwarcelona test site», que se quita desde los ajustes de claude.ai.
- Tarea real desde Claude Code con la conexión creada en la página: listar entradas, crear y programar la 2467 e intentar publicarla y borrarla. Todo como se esperaba: la publicación y el borrado se rechazan.
- Página *Herramientas → Conectar con Claude* (`includes/Admin/ConnectPage.php`):
  - instrucciones paso a paso en pantalla;
  - botón «Crear conexión», que crea la contraseña de aplicación con el identificador del plugin;
  - comando de una línea con botón «Copiar», que se muestra una sola vez;
  - lista de conexiones del usuario, con su último uso y enlace para revocarlas;
  - avisos si las contraseñas de aplicación no están disponibles y si el usuario es administrador.

  Probada fuera de WordPress, simulando sus funciones, y después en `test.wwwarcelona.net`: la conexión creada desde la página funciona en Claude Code («✔ Connected»).
- README reorganizado: la conexión desde el escritorio pasa a ser la recomendada y los bloques de terminal, la alternativa.
- Conexión con Claude Code: primero un script (`bin/conectar-claude.sh`), sustituido el mismo día por bloques para copiar y pegar en el README (Linux/macOS y Windows). Los bloques confirman el sitio antes de pedir credenciales. Probados en Linux los casos de contraseña incorrecta, rechazo de la confirmación y dirección inexistente; no se guarda nada en ninguno. El bloque de Windows está sin probar.
- Corregido el comando de técnicos: `<sitio>` como hueco rompía el comando en bash; además `--scope local` pasa a `--scope user`.
- Revisado el log de PHP del sitio de pruebas: el plugin no genera errores. Detectado que `debug.log` es accesible públicamente (problema del sitio, no del plugin).
- Subida a `test.wwwarcelona.net` la corrección de `PostAccess.php` y comprobada con el Suscriptor: `Permission denied` exista o no el ID.
- Documentación revisada y al día: sección Instalación en el README, límites de permisos y sesiones, regla nueva en AGENTS.md, CHANGELOG.

### 26/10/2026

- Prueba con Suscriptor: sin acceso a ninguna herramienta. Corregido `PostAccess::canEdit()` para comprobar `edit_posts` antes de buscar la entrada (no revelar qué IDs existen).
- Comprobado que una sesión MCP no se puede usar con las credenciales de otro usuario.
- Commit `a981dd7` en `main` con la v0.1.0 (código, `vendor/` con el autoloader de Jetpack y documentación) y el borrado de `wp-json.json`.
- Pruebas en `test.wwwarcelona.net`: conexión, herramientas, CRUD, rechazos y programación (ver tabla).
- Documentación: sección «Probar con curl» en el README, función `mcp_call` para las pruebas, tabla de problemas habituales, reglas nuevas en AGENTS.md y este fichero.
- `.gitignore` con `headers.txt` y `tools.json`, que se generan en las pruebas.
- Implementación de la v0.1.0: bootstrap, `Plugin`, cuatro abilities (`list-posts`, `get-post`, `create-post`, `update-post`) y clases de apoyo (`PostInput`, `PostAccess`, `PostFormatter`).
- Plan aprobado: MCP limitado a entradas, sin publicar, con servidor propio.
- Punto de partida: repositorio con la cabecera del plugin y `wordpress/mcp-adapter` 0.7.1 instalado con Composer.
