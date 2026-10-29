# Alternativas para usar IA en WordPress

Documento para decidir, con backend y sistemas, marketing y dirección, cómo se conecta la IA a los sitios WordPress antes de seguir desarrollando este plugin. Responde a los puntos «Temas de seguridad» y «Otras propuestas» de la planificación del 27/10/2026.

## 1. Qué se decide

**Caso de uso.** El equipo de marketing parte de un Excel con títulos, palabras clave y fechas. Un plugin de Claude, con skills, genera el contenido y deja las entradas programadas en el sitio del cliente. Lo usarán personas con rol limitado (Autor, Editor) en varios sitios. Más adelante habrá otros plugins con otras funciones, así que la conexión tiene que poder crecer sin perder el control.

**Criterios para comparar:**

| Criterio | Pregunta |
|---|---|
| Seguridad | Si la credencial se filtra o el agente se desvía, ¿qué puede hacer? |
| Control | ¿Se puede decidir qué hace cada usuario, función por función? |
| Auditoría | ¿Queda registro de qué hizo la IA, cuándo y en nombre de quién? |
| Mantenimiento | ¿Cuánto trabajo exige cada proyecto nuevo y cada cambio de WordPress? |
| Fiabilidad | ¿Funciona igual cada vez? |
| Facilidad para marketing | ¿Lo puede usar una persona no técnica sin ayuda? |
| Escalado | ¿Cuesta poco añadir funciones nuevas? |

## 2. Punto de partida y hallazgo

**Hoy.** Este plugin crea un servidor MCP propio en `/wp-json/wp-connect-mcp/mcp` con nueve herramientas: listar y leer entradas, listar categorías, identificar el usuario conectado, comprobar plantillas del blog, crear y editar entradas, subir imágenes y enviar entradas a la papelera. También permite guardar slug, campos de Yoast e imagen destacada. Desde el 28/10/2026, como medida provisional hasta decidir la alternativa, el agente puede modificar, publicar y enviar a la papelera (recuperable) las entradas creadas por su usuario, también las publicadas; publicar, solo si el rol lo permite (Autor o superior). Cada persona se conecta con una contraseña de aplicación de su usuario de WordPress, creada desde *Herramientas → Conectar con Claude*.

**Hallazgo (comprobado el 27/10/2026 en el sitio de pruebas).** La contraseña de aplicación no vale solo para el MCP: vale para **toda la API REST de WordPress**. Con la credencial que Claude Code tiene guardada (usuario `test-mcp-sub`, rol Autor), `wp/v2/users/me` y `wp/v2/posts?status=draft` responden `200 OK`, y el usuario tiene `publish_posts` y `delete_posts`. Por tanto:

- quien tenga esa credencial (o el propio agente, que en Claude Code puede ejecutar `curl`) puede hacer por `wp/v2` todo lo que permita el rol del usuario, saltándose los límites del MCP;
- los límites del MCP (solo entradas propias, sin borrar definitivamente, sin páginas) son hoy una norma del MCP, no una barrera de seguridad.

**Qué permite cada vía con la misma credencial.** La API general no añade límites propios: manda el rol.

| Acción | Plugin (MCP) | API de WordPress (`wp/v2`) |
|---|---|---|
| Crear, editar y programar entradas | Solo propias | Autor: solo las suyas. Editor y Administrador: las de cualquiera |
| Publicar | Solo propias, con Autor o superior | Con Autor o superior; Editor y Administrador, también las de otros |
| Enviar a la papelera | Solo propias | Según el rol, también las de otros (Editor y Administrador) |
| Borrar definitivamente | No | Sí (`force=true`), según el rol |
| Entradas de otros usuarios | No, con ningún rol | Sí, con Editor o Administrador |
| Páginas | No | Sí (`wp/v2/pages`), con Editor o Administrador |
| Elementor | No | Muy limitado: el diseño se guarda en el metadato `_elementor_data`, que Elementor no expone en la API. Se puede cambiar el HTML de la página, no el diseño de Elementor; para eso hace falta su editor, un plugin propio o Novamira |
| Medios, comentarios, usuarios, ajustes | Solo subir imágenes propias; no comentarios, usuarios ni ajustes | Según el rol; un Administrador, casi todo |

Con un Autor el riesgo extra es sobre todo borrar para siempre sus entradas; con un Editor o un Administrador, casi todo el contenido del sitio.

Esto afecta a cualquier alternativa que use contraseñas de aplicación (A, C y F) y es lo primero que hay que corregir si se sigue con ellas. La corrección es sencilla: que las contraseñas creadas por el plugin solo se acepten en la ruta del MCP (ver alternativa A).

## 3. Alternativas

### A. MCP propio reforzado (propuesta principal)

**Cómo funciona.** Es el plugin actual con cuatro refuerzos:

1. **Contraseña limitada a la ruta.** Las contraseñas creadas por el plugin se rechazan en cualquier ruta que no sea `/wp-connect-mcp/mcp`, y también en XML-RPC.
2. **Paquetes de funciones por usuario, impuestos en el servidor.** Nadie tiene acceso por defecto, y el administrador otorga paquetes (ver apartado 5).
3. **Registro de uso.** Queda anotado quién, qué función, sobre qué entrada y cuándo.
4. **Límites de volumen**, por ejemplo un máximo de entradas creadas por usuario y hora, para cortar bucles del agente.

| | |
|---|---|
| Seguridad | Alta. Una credencial filtrada solo permite las funciones otorgadas a ese usuario, y se revoca en un clic |
| Control | Por usuario y por paquete |
| Auditoría | Sí, con registro propio |
| Mantenimiento | Un plugin igual en todos los sitios. Sin configuración por proyecto |
| Fiabilidad | Alta: las herramientas tienen esquema y validación |
| Facilidad | Alta: botón «Crear conexión», copiar y pegar |
| Escalado | Alto: una función nueva es una ability nueva dentro de un paquete |

**Veredicto:** recomendada, siempre con los refuerzos aplicados. Sin ellos, no debe usarse en producción.

### B. MCP solo en staging, y paso a producción aparte

**Cómo funciona.** El plugin solo se instala en el entorno de staging. La IA crea y programa allí, y las entradas pasan a producción por un proceso separado: exportación e importación, sincronización o revisión humana.

| | |
|---|---|
| Seguridad | Muy alta para producción: allí no hay ninguna credencial de IA |
| Control | El de A, solo en staging |
| Auditoría | En staging, y en el paso a producción |
| Mantenimiento | Medio-alto: hay que mantener staging al día y el proceso de paso |
| Fiabilidad | Media: la programación hay que reproducirla en producción |
| Facilidad | Media: falta un paso manual o una herramienta de sincronización |
| Escalado | Alto en staging |

**Veredicto:** buena opción para sitios con producción sensible y respuesta directa a la objeción de sistemas. Tiene un coste: la programación automática deja de ser directa.

### C. API REST de WordPress con usuario limitado, sin MCP

**Cómo funciona.** Las skills llaman directamente a `wp/v2/posts` con una contraseña de aplicación. No hace falta ningún plugin propio.

| | |
|---|---|
| Seguridad | Baja-media: el límite es el rol de WordPress, y un Autor puede publicar y borrar sus entradas |
| Control | Solo por rol. No hay forma de quitar «publicar» a un Autor sin otro plugin |
| Auditoría | No, sin plugins adicionales |
| Mantenimiento | Bajo en WordPress. Las reglas (no publicar, fechas) viven en las skills, donde se pueden saltar |
| Fiabilidad | Alta |
| Facilidad | Media: la conexión se configura a mano |
| Escalado | Medio: cada función nueva depende de lo que ofrezca la API de WordPress |

Tampoco sirve para Elementor: su diseño no está en la API (ver la tabla del apartado 2), así que las páginas maquetadas con Elementor no se pueden editar por esta vía.

**Veredicto:** no recomendada. Para limitarla bien habría que construir lo mismo que A.

### D. Claude in Chrome (agente que maneja el navegador)

**Cómo funciona.** El agente abre el escritorio de WordPress en el navegador del usuario, con su sesión, y hace clics y rellena formularios como lo haría una persona.

| | |
|---|---|
| Seguridad | Baja: el agente tiene **todos** los permisos del usuario en ese momento, y lo que lee en páginas web puede desviarlo (prompt injection) |
| Control | Ninguno por función: solo el rol |
| Auditoría | Pobre: en WordPress parece que lo ha hecho la persona |
| Mantenimiento | Nada que instalar, pero se rompe cuando cambian la interfaz de WordPress, Elementor o los plugins |
| Fiabilidad | Baja-media: lento y con resultados variables |
| Facilidad | Alta para tareas sueltas |
| Escalado | Bajo |

**Veredicto:** no apta como canal de escritura para marketing. Útil para revisar visualmente o para depurar.

### E. Playwright (automatización de navegador con scripts)

**Cómo funciona.** Scripts que controlan un navegador sin interfaz e inician sesión en WordPress para hacer acciones concretas. A diferencia de D, siempre hacen lo mismo.

| | |
|---|---|
| Seguridad | Media-baja: hace falta la sesión completa del usuario (usuario y contraseña de acceso al escritorio, no revocables por separado) |
| Control | Solo por rol |
| Auditoría | Los registros del propio script |
| Mantenimiento | Alto: los selectores cambian con cada versión de WordPress, Elementor o plugins, y por sitio |
| Fiabilidad | Alta mientras no cambie la interfaz |
| Facilidad | Baja: lo mantiene un técnico |
| Escalado | Bajo: cada función nueva es un script nuevo |

**Veredicto:** muy buena herramienta de **QA y verificación** (comprobar que las entradas programadas se ven bien, capturas, pruebas de regresión). No recomendada para escribir contenido.

### F. n8n (el flujo actual de marketing)

**Cómo funciona.** Un flujo de n8n lee la hoja, genera el texto con un modelo de IA y crea las entradas por la API REST. Hoy se usa en algunos proyectos, y hay que ajustar el JSON del flujo en cada uno.

| | |
|---|---|
| Seguridad | Media-baja: la credencial de WordPress suele tener permisos amplios y vive en n8n |
| Control | Solo el que dé la credencial usada |
| Auditoría | El historial de ejecuciones de n8n |
| Mantenimiento | Alto: un flujo afinado por proyecto |
| Fiabilidad | Alta: es determinista |
| Facilidad | Baja para marketing cuando hay que tocar el flujo |
| Escalado | Medio |

**Variante F2: n8n como planificador que llama al MCP reforzado.** n8n conserva lo que hace bien (calendario, lotes, avisos), y la escritura pasa por A con un usuario y un paquete propios. Así se quita la credencial amplia y el ajuste de la escritura por proyecto.

**¿Mejora la nueva propuesta lo que se hace con n8n?** Sí, en tres puntos:

1. un mismo plugin y las mismas skills en todos los sitios, sin JSON por proyecto;
2. el contenido se genera y se revisa dentro de Claude, con las instrucciones de la skill;
3. permisos por persona, revocables.

n8n sigue siendo mejor para lotes grandes sin supervisión. Por eso F2 es el camino para los proyectos en los que ya funciona.

### G. Novamira u otro MCP genérico

**Cómo funciona.** Expone muchas funciones del sitio (páginas, Elementor, ajustes, plugins) a un agente.

| | |
|---|---|
| Seguridad | Baja para este caso: mucha superficie de ataque |
| Control | Limitado a lo que ofrezca el producto |
| Auditoría | Según el producto |
| Mantenimiento | Bajo: es de terceros |
| Fiabilidad | Alta |
| Facilidad | Alta |
| Escalado | Alto, pero sin control propio |

**Veredicto:** adecuado para técnicos que maquetan en staging; **no apto para usuarios de marketing**. Además, si una misma persona tiene Novamira y este plugin conectados al mismo sitio, los límites de este plugin dejan de servir.

### H. Sin escritura remota: generar un fichero e importarlo

**Cómo funciona.** La IA produce un fichero (WXR de WordPress o CSV) con las entradas y sus fechas, y una persona lo importa desde el escritorio con un importador.

| | |
|---|---|
| Seguridad | Máxima: la IA no tiene ninguna credencial del sitio |
| Control | Total: decide quien importa |
| Auditoría | La importación queda como acción de esa persona |
| Mantenimiento | Bajo |
| Fiabilidad | Media: depende del importador (categorías, imágenes, fechas) |
| Facilidad | Media: un paso manual en cada lote |
| Escalado | Bajo: solo sirve para contenido, no para otras funciones |

**Veredicto:** plan B si sistemas no acepta ninguna credencial de IA en los sitios.

## 4. Comparativa

| | Seguridad | Control | Auditoría | Mantenimiento (menos es mejor) | Fiabilidad | Facilidad marketing | Escalado |
|---|---|---|---|---|---|---|---|
| **A. MCP reforzado** | Alta | Alto | Sí | Bajo | Alta | Alta | Alto |
| **B. MCP en staging** | Muy alta | Alto | Sí | Medio-alto | Media | Media | Alto |
| C. API REST directa | Baja-media | Bajo | No | Bajo | Alta | Media | Medio |
| D. Claude in Chrome | Baja | Nulo | Pobre | Medio | Baja-media | Alta | Bajo |
| E. Playwright | Media-baja | Bajo | Parcial | Alto | Alta | Baja | Bajo |
| F. n8n actual | Media-baja | Bajo | Parcial | Alto | Alta | Baja | Medio |
| F2. n8n + MCP reforzado | Alta | Alto | Sí | Medio | Alta | Media | Alto |
| G. Novamira | Baja | Bajo | Según producto | Bajo | Alta | Alta | Alto |
| H. Fichero e importación | Máxima | Total | Sí | Bajo | Media | Media | Bajo |

## 5. ¿El control de funciones en las skills o en el servidor?

La planificación pregunta si el control de abilities podría definirse en las skills de los futuros plugins, en lugar de en paneles de WordPress.

**Las skills no pueden ser la barrera de seguridad.** Una skill es un conjunto de instrucciones que se ejecuta en el equipo de quien la usa:

- se puede editar, desactivar o ignorar;
- el agente puede desviarse de ella (por ejemplo, por un texto malicioso en el Excel o en una web);
- cualquier otro cliente con la misma credencial (curl, otra IA, n8n) no la lee.

Lo que de verdad limita es lo que el servidor acepta. Opciones:

| Opción | Cómo | Valoración |
|---|---|---|
| a) Solo en las skills | La skill dice qué herramientas usar | Insegura: no limita nada |
| b) Panel con casillas por ability | El administrador marca función a función por usuario | Segura, pero no escala: con muchas funciones se vuelve inmanejable |
| **c) Paquetes (recomendada)** | Cada plugin de skills declara un *paquete* (por ejemplo, `contenido-programado` = listar, leer, crear y editar entradas). El mismo paquete está definido en código en el servidor. El administrador otorga paquetes a usuarios, desde una pantalla mínima o con WP-CLI. Al arrancar, la skill consulta qué herramientas tiene y, si falta alguna, explica qué pedir | Segura y escalable: el detalle se define una vez, en código revisado, y el administrador solo decide «este usuario puede usar el plugin de contenido» |
| d) Un servidor MCP por paquete | Una ruta distinta por plugin (`/wp-connect-mcp/mcp/contenido-programado`) | Aislamiento máximo, a costa de una conexión por plugin y usuario. Se puede pasar a esto más adelante si hace falta |

La opción c) recoge lo que se busca con la pregunta: la definición vive junto a las skills (el mismo nombre de paquete y la misma lista), sin gestionar casillas función a función, y la seguridad sigue en el servidor.

**Para escalar:** cada función nueva tiene que entrar en un paquete, con revisión de código. Un paquete nuevo o ampliado pasa por backend antes de publicarse.

## 6. Respuesta a backend y sistemas

**Lo que es cierto.** Con el plugin tal como está (v0.1.0, v0.2.0 y v0.2.1), una credencial filtrada permite hacer por la API general de WordPress todo lo que permita el rol del usuario (apartado 2). En ese estado no debe ir a producción.

**Lo que se corrige con A.** La credencial queda limitada a la ruta del MCP y a los paquetes del usuario. Se revoca sin tocar su contraseña de acceso, deja registro de uso y tiene un límite de volumen.

**Lo que queda.**

- Quien tenga una credencial válida puede hacer, dentro de su paquete, lo mismo que haría la IA. Por ejemplo, crear entradas programadas que se publicarán solas.
- La dependencia del adapter MCP de WordPress, aún en versión 0.x.
- Lo que el agente escriba en el contenido (texto erróneo o inyectado). Se controla con revisión humana antes de la fecha programada.

**Condiciones mínimas para producción:**

1. HTTPS.
2. Un usuario de WordPress propio por persona, nunca administrador.
3. Contraseñas del plugin limitadas a la ruta del MCP.
4. Paquetes otorgados por un administrador, y nadie con acceso por defecto.
5. Registro de uso activado y revisable.
6. Rotación de contraseñas (por ejemplo, cada 90 días) y revocación al cambiar de equipo o de proyecto.
7. Opcional: WAF o lista de IP permitidas sobre `/wp-json/wp-connect-mcp/mcp`.
8. `debug.log` fuera de la carpeta pública. En el sitio de pruebas se podía descargar desde internet.
9. Nadie con otra conexión de IA al mismo sitio (Novamira, conectores) que la de este plugin.

Si no se aceptan estas condiciones: alternativa **B** (solo staging) o **H** (fichero e importación).

## 7. Recomendación

1. **A + paquetes (5c)** como canal de escritura para marketing. Antes de usarlo en producción, implementar la contraseña limitada a la ruta, los paquetes, el registro y los límites.
2. **B** para los sitios de clientes con producción sensible, o mientras sistemas no dé el visto bueno a A.
3. **Playwright** para verificar y hacer QA (capturas de las entradas programadas, pruebas tras actualizar), no para escribir.
4. **n8n** solo donde ya funcione, y migrar a **F2** (n8n planifica, el MCP escribe) cuando se toque cada proyecto.
5. Descartar **D** y **G** como canal para marketing. Siguen siendo válidas como herramientas para técnicos en staging.
6. **H** como plan B si no se acepta ninguna credencial de IA en los sitios.

## 8. Preguntas para la reunión

- ¿Se acepta A en producción con las condiciones del apartado 6, o solo B?
- ¿Qué rol estándar se da a los usuarios de marketing: Autor (puede programar) o Colaborador (solo deja pendientes)?
- ¿El agente puede editar entradas ya publicadas? Recomendación: no.
- ¿Puede crear etiquetas nuevas o solo usar las existentes?
- ¿Quién otorga y revisa los paquetes en cada sitio: sistemas, el responsable del proyecto o marketing?
- ¿Cada cuánto se rotan las contraseñas y quién lo hace?
- ¿Qué proyectos de n8n se mantienen y cuáles pasan a F2?
