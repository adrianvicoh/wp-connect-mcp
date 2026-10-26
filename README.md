# wwwarcelona WP Connect

Plugin de WordPress que expone un servidor MCP propio para que Claude u otros agentes trabajen con **entradas (posts)** con permisos muy limitados.

## Qué puede hacer el agente

| Tool MCP | Qué hace |
|---|---|
| `wwwarcelona-list-posts` | Lista entradas editables por el usuario (búsqueda, estado, paginación). |
| `wwwarcelona-get-post` | Lee una entrada: contenido sin procesar, extracto, estado, fechas, categorías y etiquetas. |
| `wwwarcelona-create-post` | Crea una entrada como borrador, pendiente o programada. |
| `wwwarcelona-update-post` | Modifica solo los campos indicados de una entrada. |

Límites:

- Solo el tipo `post`; páginas y tipos personalizados se rechazan.
- **No publica.** Estados permitidos: `draft`, `pending` y `future` (programada, con `scheduled_date` futura). Programar exige que el usuario pueda publicar.
- En entradas ya publicadas (o privadas) se puede editar el contenido, que cambia en directo, pero no su estado ni su fecha.
- No borra, no sube medios y no toca otras partes del sitio. El servidor solo expone estas cuatro tools, no las meta-tools de descubrimiento del servidor MCP por defecto.
- Cada acción respeta las capacidades del usuario de WordPress (`edit_posts`, `edit_post`, `publish_post`, asignar categorías y etiquetas).
- El contenido se filtra con `wp_kses_post`, también para administradores.

## Requisitos

- WordPress 6.9 o superior (Abilities API en el núcleo).
- PHP 7.4 o superior.
- HTTPS y Application Passwords activos.

El paquete `wordpress/mcp-adapter` va incluido en `vendor/` y se carga con el autoloader de Jetpack. Si otro plugin trae una versión más nueva del adapter, se usa esa. Al cargarse como librería, el adapter deja un aviso de deprecación en el log de WordPress.

## Conexión

1. Crear un usuario con el rol mínimo necesario (Autor para programar; Colaborador si solo debe dejar pendientes) y generarle una **Application Password** en *Usuarios → Perfil*.
2. Endpoint: `https://<sitio>/wp-json/wwwarcelona/mcp`
3. En Claude Code:

   ```bash
   claude mcp add --transport http wwwarcelona-<sitio> https://<sitio>/wp-json/wwwarcelona/mcp \
     --header "Authorization: Basic $(printf '%s' 'usuario:xxxx xxxx xxxx xxxx xxxx xxxx' | base64)"
   ```

## Desarrollo

```bash
composer install          # o composer dump-autoload tras añadir clases
```

Código propio en `includes/` (PSR-4 `Wwwarcelona\WpConnect\`):

- `Plugin.php`: registra la categoría, las abilities y el servidor MCP.
- `Abilities/`: una clase por ability (`NAME`, `register()`, `execute()`).
- `Support/PostInput.php`: saneado y reglas de estado y programación.
- `Support/PostAccess.php`: carga de la entrada y comprobación de permisos.
- `Support/PostFormatter.php`: formato y esquemas de salida.
