# AGENTS.md

- **Antes de empezar, leer [ESTADO.md](ESTADO.md)**: situación actual, pendientes, decisiones y pruebas. **Al terminar cada paso, actualizarlo**:
  - situación actual;
  - casillas de pendientes;
  - fila en la tabla de pruebas o de decisiones, con la fecha;
  - entrada en el historial.

  Lo más reciente va arriba en cada sección.

- Plugin de WordPress (PHP 7.4+, WP 6.9+). Código propio en `includes/`, PSR-4 `Wwwarcelona\WpConnect\`; estilo PSR-12 con `defined('ABSPATH') || exit;` en cada fichero.
- `vendor/` se versiona. Tras añadir o mover clases: `composer dump-autoload`. No editar nada dentro de `vendor/`.
- No usar clases de `vendor/` antes de `plugins_loaded` (autoloader de Jetpack).
- Alcance deliberadamente limitado: solo entradas (`post`), sin publicar ni borrar. Cualquier ability nueva debe añadirse a `Plugin::ABILITIES`, comprobar capacidades en `permission_callback` (las generales, como `edit_posts`, antes de cargar nada, para no revelar qué existe) y declarar `input_schema` con `additionalProperties: false` y `output_schema`.
- Las reglas de estado y programación viven en `Support/PostInput.php`; no duplicarlas en las abilities.
- Verificación: `php -l` de los ficheros tocados y, en un WordPress de pruebas, `tools/list` y `tools/call` contra `/wp-json/wwwarcelona/mcp`. Los pasos con `curl` (sesión, cabeceras) y la lista de comprobación están en la sección «Probar con curl» del README.
- En comandos de prueba y documentación no depender de herramientas que haya que instalar (`jq` y similares): usar solo `curl` y `php -r` para leer el JSON.
- Nunca escribir Application Passwords en ficheros del repositorio ni en comandos que queden en el historial.
