# AGENTS.md

- Plugin de WordPress (PHP 7.4+, WP 6.9+). Código propio en `includes/`, PSR-4 `Wwwarcelona\WpConnect\`; estilo PSR-12 con `defined('ABSPATH') || exit;` en cada fichero.
- `vendor/` se versiona. Tras añadir o mover clases: `composer dump-autoload`. No editar nada dentro de `vendor/`.
- No usar clases de `vendor/` antes de `plugins_loaded` (autoloader de Jetpack).
- Alcance deliberadamente limitado: solo entradas (`post`), sin publicar ni borrar. Cualquier ability nueva debe añadirse a `Plugin::ABILITIES`, comprobar capacidades en `permission_callback` y declarar `input_schema` con `additionalProperties: false` y `output_schema`.
- Las reglas de estado y programación viven en `Support/PostInput.php`; no duplicarlas en las abilities.
- Verificación: `php -l` de los ficheros tocados y, en un WordPress de pruebas, `tools/list` y `tools/call` contra `/wp-json/wwwarcelona/mcp`.
