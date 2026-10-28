# Changelog

Cambios por versión publicada. El día a día (pruebas, decisiones, pendientes) está en [ESTADO.md](ESTADO.md).

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
