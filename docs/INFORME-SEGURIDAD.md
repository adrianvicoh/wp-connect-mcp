# Seguridad de WP Connect MCP

Informe para sistemas · Versión 0.3.0 · 31/10/2026 · Actualizado con la 0.4.5 el 04/11/2026

Qué se ha cerrado en la 0.3.0, qué riesgo queda y qué necesitamos que decidáis antes de usar el plugin en producción. Versión local del informe publicado como artifact; el detalle técnico está en el [README](../README.md), [ALTERNATIVAS.md](ALTERNATIVAS.md) y el [CHANGELOG](../CHANGELOG.md).

## Resumen: la contraseña de Claude ya solo sirve para el MCP

Hasta la 0.2.2, la contraseña de aplicación que usa Claude valía para **toda la API REST de WordPress**. Con un Autor se podían borrar entradas para siempre y, con un Editor, tocar las de otros usuarios, saltándose los límites del plugin.

La 0.3.0:

- limita esa contraseña a la ruta del MCP;
- añade permisos por usuario, registro de uso y límites por hora.

Está probada fuera de WordPress; **falta probarla en el sitio de pruebas y vuestro visto bueno** antes de llevarla a producción.

| Cifra | Qué es |
|---|---|
| **403** | Respuesta de la contraseña de Claude fuera del MCP: `wp/v2`, `batch` y XML-RPC. |
| **3** | Paquetes por usuario: Leer, Escribir y Papelera. Sin paquetes, sin acceso. |
| **34** | Comprobaciones locales, todas correctas, en una simulación con funciones de WordPress sustituidas. |
| **10** | Hallazgos de la revisión: 6 arreglados en la 0.3.0, 3 arreglados en parte y 1 pendiente de decisión. |

## Qué cambia en la 0.4.5 (04/11/2026, sin probar en el sitio)

Tras la reunión de marketing del 02/11/2026:

| Punto | Antes | 0.4.5 |
|---|---|---|
| Otras credenciales del usuario de Claude | Sus contraseñas de aplicación creadas a mano valían para la API REST y XML-RPC según el rol; XML-RPC aceptaba su contraseña normal. | Si el usuario tiene algún paquete, se rechazan siempre (`wp_connect_mcp_api_blocked`), sin excepción por usuario. Queda en el registro. El escritorio (`wp-admin`) no cambia. |
| Etiquetas nuevas | Un Autor o Colaborador las creaba al asignarlas. | Solo con el paquete *Categorías y etiquetas* o `manage_categories`; si no, error con las que faltan. |
| Crear categorías | No se podía. | `create-category`, con el paquete *Categorías y etiquetas*; 10 por hora. No edita ni borra las existentes. |
| Leer el sitio | Solo las entradas que el usuario puede editar. | Con el paquete *Leer el sitio*: páginas y entradas **publicadas** de cualquier autor, lo mismo que ve un visitante. Borradores, privadas y con contraseña siguen fuera. |
| Comprobación del cliente | — | `get-current-user` devuelve la versión del plugin y `password_scope: "mcp"`. |

Lo que sigue sin cubrir: plugins de autenticación de terceros (JWT, OAuth, Basic Auth con la contraseña normal), si el sitio los tiene.

## Qué cambia en la 0.3.0

| Punto | Hasta la 0.2.2 | 0.3.0 |
|---|---|---|
| Alcance de la contraseña | Toda la API REST y XML-RPC, con los permisos del rol. | Solo `/wp-json/wp-connect-mcp/mcp`. El resto responde 403 (`wp_connect_mcp_password_scope`). |
| Qué contraseñas acepta el MCP | Cualquier contraseña de aplicación del usuario. | Solo las creadas desde *Herramientas → Conectar con Claude*. Las creadas a mano en el perfil dan 403. |
| Quién puede usar Claude | Cualquier usuario con `edit_posts`. | Solo quien tenga paquetes otorgados por un administrador en *Usuarios → Acceso de Claude*. Nadie por defecto. |
| Qué herramientas ve | Las nueve. | Solo las de sus paquetes; las demás no salen en `tools/list` y dan `Permission denied`. |
| Revocación | A mano, contraseña por contraseña, desde el perfil. | Quitar todos los paquetes revoca todas sus conexiones. Al crear una conexión nueva se propone revocar las anteriores. |
| Registro | Ninguno propio. | Herramienta usada, acceso denegado y cambio de permisos, con fecha, usuario, ID y resultado. En la base de datos, no en `debug.log`. |
| Volumen | Sin límite. | Por usuario y hora: crear 30, editar 120, subir imágenes 60 y papelera 30. Ajustable por filtro. |
| Servidor MCP genérico del adapter | Activo: `/wp-json/mcp/mcp-adapter-default-server`, con `execute-ability` para cualquier usuario con `read`. | Desactivado. Se mantiene a propósito con un filtro en los sitios que lo necesiten (Novamira). |
| Subida de imágenes | Límite de 2 MB comprobado después de decodificar. | Envíos grandes rechazados antes de decodificar; máximo 40 megapíxeles. |

## Qué comprueba el servidor en cada petición

Todo se impone en WordPress. Las skills de Claude pueden avisar de lo que falta, pero no son una barrera: se pueden editar o saltar, y cualquier otro cliente con la misma credencial no las lee.

| Paso | Qué comprueba | Si falla |
|---|---|---|
| 1. Contraseña | Fuera de la ruta del MCP, la contraseña de Claude no vale. Dentro, solo se aceptan las creadas desde la página del plugin. | 403: no se abre sesión. |
| 2. Paquete | El usuario tiene el paquete que contiene la herramienta: Leer, Escribir o Papelera. | La herramienta no aparece y da `Permission denied`. |
| 3. Límite por hora | Las herramientas que escriben cuentan sus usos por usuario y hora. | Error claro hasta la hora siguiente. |
| 4. Rol de WordPress | Las capacidades de siempre: `edit_posts`, `publish_posts`, `upload_files`… | Por ejemplo, un Colaborador no programa ni publica. |
| 5. Entrada propia | Modificar, publicar y enviar a la papelera solo vale para entradas creadas por ese usuario, con cualquier rol. | Las de otros se rechazan, también con un Editor. |
| 6. Registro | Cada uso, rechazo y cambio de permisos queda anotado en *Usuarios → Acceso de Claude*. | Se guardan los últimos 500 movimientos. |

## Revisión de seguridad del 31/10/2026: hallazgos y estado

Revisión de toda la documentación y del código, hecha aparte de la implementación. Lo ya conocido (la contraseña en `wp/v2`, la credencial en el equipo) no se repite.

| Hallazgo | Gravedad | Estado | Detalle |
|---|---|---|---|
| Servidor MCP genérico del adapter activo | Media | Arreglado | Lo abría el propio plugin, con `execute-ability` para cualquier usuario con `read`. Desactivado por defecto. |
| Contraseñas acumuladas que no caducan | Media | Arreglado en parte | Se revocan las anteriores al crear una nueva y al quitar paquetes. Falta decidir si caducan solas tras N días sin uso. |
| Credencial en los argumentos de procesos | Media | Arreglado en parte | `curl` la recibe ahora por la entrada estándar. `claude mcp add` la sigue recibiendo como argumento durante un instante: puede quedar en registros de EDR, Sysmon o auditd. |
| Datos reales del entorno en el repositorio | Media | Arreglado en parte | El repositorio es público. Se ha quitado el dominio del sitio de pruebas, pero sigue en el historial de git y quedan nombres de usuarios de prueba. Pendiente decidir si se reescribe el historial. |
| Direcciones `http://` en los comandos | Baja | Arreglado | Se rechazan, salvo `localhost`. |
| Un Autor puede crear etiquetas | Baja | Arreglado (0.4.5) | Solo con el paquete *Categorías y etiquetas* o `manage_categories`. |
| Valores sin validar en los comandos | Baja | Arreglado | Se valida también la dirección del sitio, y se quitan `&` y `%` (Windows ejecuta `claude.cmd`). |
| Subida: decodifica antes de medir | Baja | Arreglado | El tamaño se comprueba antes de decodificar, y hay límite de megapíxeles. |
| Contraseña creada aunque no estén disponibles | Baja | Arreglado | Se comprueba la disponibilidad antes de crear nada. |
| Portapapeles y pegado fuera de sitio | Baja | Documentado | La página avisa de pegar la contraseña solo cuando la terminal la pide; el README recomienda borrar el portapapeles. |

## Lo que la 0.3.0 no resuelve

- **Dentro de sus paquetes, la credencial hace lo mismo que Claude.** Por ejemplo, programar entradas que se publicarán solas. Se mitiga con revisión humana antes de la fecha y con el registro.
- ~~**Las contraseñas creadas a mano siguen valiendo para la API REST** según el rol.~~ Resuelto en la 0.4.5 para los usuarios con paquetes: solo pueden usar la API por el MCP.
- **La credencial queda en el equipo** (`~/.claude.json`), en texto plano, y pasa por los argumentos de `claude mcp add` al guardarla.
- **Contenido erróneo o inyectado** desde el Excel o desde una web: el HTML se filtra con `wp_kses_post`, pero el texto en sí lo tiene que revisar una persona.
- **Dependencia del adapter MCP de WordPress**, todavía en versión 0.x e incluido en `vendor/`.

## Condiciones mínimas para producción

| Dónde | Condición |
|---|---|
| En el plugin (hecho) | Contraseña limitada a la ruta del MCP. |
| En el plugin (hecho) | Paquetes otorgados por un administrador; nadie con acceso por defecto. |
| En el plugin (hecho) | Registro de uso y límite por hora. |
| En el sitio | HTTPS y `debug.log` fuera de la carpeta pública. |
| En el sitio | Un usuario de WordPress por persona, nunca administrador. |
| En el sitio | Ninguna otra conexión de IA al mismo sitio para esas personas (Novamira, conectores). |
| Proceso | Rotación de contraseñas (por ejemplo, cada 90 días) y revocación al cambiar de equipo. |
| Opcional | WAF o lista de IP sobre `/wp-json/wp-connect-mcp/mcp`. |

## Lo que necesitamos de sistemas: cuatro decisiones

1. **¿Aceptáis la 0.3.0 en producción con las condiciones mínimas?** Si no, la alternativa es usarlo solo en staging y pasar las entradas a producción por otra vía.
2. **¿Es aceptable que la credencial pase por los argumentos de `claude mcp add`?** Ocurre una vez, al guardar la conexión. Si vuestros EDR registran líneas de comando, conviene saberlo.
3. **¿Qué hacemos en los sitios con Novamira?** La 0.3.0 desactiva el servidor que usa Novamira. O se mantiene con un filtro en un mu-plugin, o se separan esos sitios de los de marketing.
4. **¿Caducidad automática de las contraseñas sin uso?** Por ejemplo, revocar las que lleven 60 días sin usarse.

## Prueba rápida en un sitio

Con una contraseña creada desde *Conectar con Claude*. La credencial va a `curl` por la entrada estándar, no como argumento. El primer comando debe dar **403** y el segundo, la respuesta del servidor MCP.

```bash
SITE="https://example.com"
WP_USER="usuario-de-claude"
read -s -p "Contraseña de aplicación: " WP_APP_PASS; echo
auth() { printf 'user = "%s:%s"\n' "$WP_USER" "$WP_APP_PASS"; }

# Fuera del MCP: debe dar 403
curl -sS -o /dev/null -w '%{http_code}\n' \
  "$SITE/wp-json/wp/v2/users/me" -K <(auth)

# En el MCP: debe responder "WP Connect MCP"
curl -sS "$SITE/wp-json/wp-connect-mcp/mcp" -K <(auth) \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"curl","version":"1.0"}}}'
```

---

wwwarcelona · WP Connect MCP · Informe de seguridad
