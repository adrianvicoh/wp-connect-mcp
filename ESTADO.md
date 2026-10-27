# Estado del proyecto

Registro vivo de cómo se construye y prueba el plugin. Se actualiza en cada paso: qué se hizo, qué se probó, qué se decidió y qué queda pendiente. Lo más reciente va arriba en cada sección.

## Situación actual

- **Versión:** 0.1.0, en `main` (commits `a981dd7`, `587e6f9` y `37ee397`).
- **Estado:** funcionando en `test.wwwarcelona.net`. Probados conexión, `tools/list`, crear, leer, editar, programar (con publicación automática confirmada), los rechazos principales, el usuario Suscriptor y una tarea real desde Claude Code con la conexión de la página.
- **Documentación:** README, AGENTS.md y CHANGELOG al día a 27/10/2026. Nuevo [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md): alternativas para usar IA en WordPress, seguridad y control de funciones por paquetes.
- **Hallazgo de seguridad:** la contraseña de aplicación de la conexión vale para toda la API REST (`wp/v2`), no solo para el MCP; un Autor podría publicar y borrar saltándose el plugin. No usar en producción hasta limitarla a la ruta (ver docs/ALTERNATIVAS.md, alternativa A).
- **Siguiente paso:** revisar [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) con backend y sistemas, marketing y dirección, y decidir la alternativa antes de seguir con el código (v0.2). Mientras tanto, subir la nueva `ConnectPage.php` a `test.wwwarcelona.net` y revocar la conexión antigua del usuario ID 3. Claude Code ya está conectado con `test-mcp-sub` (ID 6), que ahora es Autor.

## Pendientes

- [ ] Revisar [docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md) con backend y sistemas, marketing y dirección, y responder a las preguntas del apartado 8.
- [ ] Si se aprueba la alternativa A (v0.2): limitar las contraseñas del plugin a `/wwwarcelona/mcp`, paquetes de funciones por usuario, registro de uso y límite de volumen.
- [ ] Subir `includes/Admin/ConnectPage.php` con el aviso del usuario y comprobar que lo muestra antes y después de crear la conexión.
- [ ] Revocar la conexión creada con el usuario ID 3 (perfil de ese usuario → *Contraseñas de aplicación*). La nueva ya está en Claude Code, con `test-mcp-sub` (ID 6, Autor).
- [ ] Decidir si se crea otro usuario Suscriptor de pruebas: `test-mcp-sub` ha pasado a Autor y ya no sirve para repetir las pruebas de Suscriptor.
- [ ] Entrada de prueba 2470 («Hola»): programada, se publicará sola el 28/10/2026 a las 9:00. Si no debe salir, pasarla a borrador (lo puede hacer el agente con la conexión actual).
- [ ] Decidir qué hacer con la entrada de prueba 2467 («Prueba desde Claude Code»): está programada y se publicará sola el 28/10/2026 a las 9:00. Para evitarlo, pasarla a borrador o a la papelera desde el escritorio.

- [x] Confirmar que la entrada 2453 pasa de `future` a `publish` sola.
- [ ] Enviar la entrada de prueba 2453 a la papelera desde el escritorio (el agente no puede borrar).
- [x] Probar con un usuario Suscriptor: todas las herramientas deben rechazarse.
- [x] Subir `includes/Support/PostAccess.php` corregido a `test.wwwarcelona.net` y repetir `get-post` como Suscriptor: debe dar `Permission denied`.
- [x] Revisar el log de PHP en `test.wwwarcelona.net`: solo debería aparecer el aviso de "deprecated" del adapter.
- [ ] **Fuera del plugin, seguridad del sitio:** `https://test.wwwarcelona.net/wp-content/debug.log` se puede descargar desde internet (HTTP 206, 27/10/2026). Mover el log fuera de la carpeta pública (`define('WP_DEBUG_LOG', '/ruta/fuera/de/htdocs/debug.log');`) o bloquearlo en la configuración del servidor. Revisar lo mismo en otros sitios. El log pesa 152 MB y está lleno de avisos de otros plugins.
- [ ] Valorar instalar el plugin oficial MCP Adapter en lugar de llevarlo dentro de `vendor/`, para quitar el aviso de "deprecated" (v0.2).
- [x] `wp-json.json`: borrado confirmado en el commit `a981dd7`.
- [x] Primer commit (`a981dd7`, en `main`).
- [x] Commit de la corrección de `PostAccess.php`, `ESTADO.md` y la documentación (`587e6f9`).
- [x] Subir `includes/Admin/ConnectPage.php` e `includes/Plugin.php` a `test.wwwarcelona.net`, crear la conexión desde *Herramientas → Conectar con Claude*, pegar el comando y comprobar que Claude Code conecta.
- [x] Pedir a Claude, en una sesión nueva, una tarea real con el sitio conectado.
- [ ] Revisar la página con un usuario administrador (debe recomendar un usuario propio para Claude) y comprobar que la conexión aparece en la lista y se puede revocar desde el perfil.
- [ ] Probar el comando de la página en Windows (PowerShell) y en un Mac.
- [ ] Probar los bloques de la alternativa por terminal en Windows (PowerShell) y en un Mac (zsh). En Linux funciona el de Linux y macOS.
- [ ] Decidir si el agente puede editar entradas ya publicadas. Ahora puede, y el cambio se ve en directo. Recomendación: prohibirlo.
- [ ] Decidir si el agente puede crear etiquetas nuevas o solo usar las existentes.
- [ ] Acordar el rol estándar del usuario del agente: Autor, que puede programar, o Colaborador, que solo deja entradas pendientes.

## Pruebas en test.wwwarcelona.net

Usuarios de prueba con Application Password: `test-mcp` (ID 5, puede crear y programar) y `test-mcp-sub` (ID 6; era Suscriptor en las pruebas del 26 y 27/10, ahora es Autor y su nombre visible es «test-mcp»). Comandos en la sección «Probar con curl» del README.

| Fecha | Prueba | Resultado |
|---|---|---|
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
| 27/10/2026 | Antes de seguir con el código, informe de alternativas ([docs/ALTERNATIVAS.md](docs/ALTERNATIVAS.md)) para decidir con backend y sistemas, marketing y dirección. Propuesta: MCP reforzado con paquetes de funciones otorgados por usuario en el servidor | Backend y sistemas ven el MCP inseguro en producción, el MCP va a crecer con nuevas funciones y hay que comparar con Chrome, Playwright y n8n. El control no puede vivir solo en las skills: se pueden saltar |
| 27/10/2026 | La página dice con qué usuario se crea la conexión (nombre y usuario), junto al botón y en el aviso de conexión creada | En la prueba real la conexión salió del usuario ID 3 en lugar de `test-mcp`, porque era la sesión abierta en el escritorio. Claude actúa en nombre de ese usuario, así que tiene que quedar claro antes de pulsar |
| 27/10/2026 | Forma principal de conexión: página *Herramientas → Conectar con Claude*. Crea la contraseña de aplicación del usuario conectado y muestra un comando de una línea, válido en Mac, Linux y Windows. Los bloques de terminal quedan como alternativa | Simplificar al máximo: entrar en WordPress, pulsar un botón, copiar y pegar. Sin preguntas, sin crear contraseñas a mano y sin un bloque distinto por sistema |
| 27/10/2026 | Se acepta que la credencial quede en el historial de la terminal y en `~/.claude.json` | Es una contraseña de aplicación revocable, de un solo usuario y sin acceso al escritorio. La página avisa de no compartir el comando y enlaza a revocar |
| 27/10/2026 | Descartado por ahora OAuth (entrar con el usuario en el navegador, como Novamira) | Es lo más sencillo para el usuario, pero exige construir un servidor OAuth. Se valorará en una versión posterior |
| 27/10/2026 | Conectar Claude Code con bloques de comandos para copiar y pegar (Linux/macOS y Windows), sin ficheros ejecutables. Sustituye al script `bin/conectar-claude.sh`, que se ha retirado | Los usuarios no técnicos no tienen el repositorio: con un fichero habría que repartirlo y actualizarlo, no funcionaría en Windows y obligaría a ejecutar ficheros recibidos. Los bloques piden y confirman el sitio, validan antes de guardar y explican los errores |
| 27/10/2026 | Los bloques confirman el sitio (nombre y dirección) antes de pedir credenciales | Que la persona sepa con qué sitio se está conectando antes de escribir su contraseña |
| 27/10/2026 | Conexión en Claude Code con `--scope user` | `local` solo funciona en la carpeta donde se ejecuta; `project` dejaría la credencial en el repositorio |
| 26/10/2026 | Comprobar `edit_posts` antes de buscar la entrada en `get-post` y `update-post` | Quien no puede editar recibe siempre `Permission denied` y no averigua qué IDs existen |
| 26/10/2026 | Pruebas solo con `curl` y `php -r`, sin `jq` | No instalar nada en los equipos |
| 26/10/2026 | Arranque en `plugins_loaded` | El autoloader de Jetpack elige la versión más nueva del adapter cuando ya conoce todos los plugins |
| 26/10/2026 | `jetpack-autoloader` como dependencia directa | Sin eso no generaba `vendor/autoload_packages.php` |
| 26/10/2026 | Servidor MCP propio en `/wp-json/wwwarcelona/mcp` con 4 tools | Que el agente no vea ninguna otra ability del sitio |
| 26/10/2026 | El agente no publica: solo `draft`, `pending` y `future` | Publicar lo decide una persona; programar sí se permite |
| 26/10/2026 | Solo entradas (`post`) | Alcance inicial limitado |
| 26/10/2026 | Las entradas publicadas se pueden editar, pero no cambiar su estado ni su fecha | Decisión provisional, pendiente de revisar |

## Historial

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
