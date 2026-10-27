# Estado del proyecto

Registro vivo de cómo se construye y prueba el plugin. Se actualiza en cada paso: qué se hizo, qué se probó, qué se decidió y qué queda pendiente. Lo más reciente va arriba en cada sección.

## Situación actual

- **Versión:** 0.1.0, en `main` (commit `a981dd7`). Sin commit todavía: corrección de `PostAccess.php`, este fichero y los cambios de documentación posteriores.
- **Estado:** funcionando en `test.wwwarcelona.net`. Probados conexión, `tools/list`, crear, leer, editar, programar (con publicación automática confirmada), los rechazos principales y el usuario Suscriptor.
- **Siguiente paso:** commit de los cambios pendientes, revisar el log de PHP y probar la conexión desde Claude Code.

## Pendientes

- [x] Confirmar que la entrada 2453 pasa de `future` a `publish` sola.
- [ ] Enviar la entrada de prueba 2453 a la papelera desde el escritorio (el agente no puede borrar).
- [x] Probar con un usuario Suscriptor: todas las herramientas deben rechazarse.
- [x] Subir `includes/Support/PostAccess.php` corregido a `test.wwwarcelona.net` y repetir `get-post` como Suscriptor: debe dar `Permission denied`.
- [ ] Revisar el log de PHP en `test.wwwarcelona.net`: solo debería aparecer el aviso de "deprecated" del adapter.
- [x] `wp-json.json`: borrado confirmado en el commit `a981dd7`.
- [x] Primer commit (`a981dd7`, en `main`).
- [ ] Commit de la corrección de `PostAccess.php`, `ESTADO.md` y la documentación.
- [ ] Probar la conexión desde Claude Code (`claude mcp add`, en el README) con una tarea real. Hasta ahora solo se ha probado con `curl`.
- [ ] Decidir si el agente puede editar entradas ya publicadas. Ahora puede, y el cambio se ve en directo. Recomendación: prohibirlo.
- [ ] Decidir si el agente puede crear etiquetas nuevas o solo usar las existentes.
- [ ] Acordar el rol estándar del usuario del agente: Autor, que puede programar, o Colaborador, que solo deja entradas pendientes.

## Pruebas en test.wwwarcelona.net

Usuarios de prueba con Application Password: `test-mcp` (ID 5, puede crear y programar) y `test-mcp-sub` (ID 6, Suscriptor). Comandos en la sección «Probar con curl» del README.

| Fecha | Prueba | Resultado |
|---|---|---|
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
