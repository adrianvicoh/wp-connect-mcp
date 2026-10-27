# Changelog

Cambios por versión publicada. El día a día (pruebas, decisiones, pendientes) está en [ESTADO.md](ESTADO.md).

## 0.1.0 (sin publicar)

- Servidor MCP propio en `/wp-json/wwwarcelona/mcp` con cuatro tools: listar, leer, crear y editar entradas.
- El agente solo puede dejar entradas en borrador, pendientes o programadas; no publica, no borra y no toca páginas.
- Quien no puede editar entradas recibe siempre `Permission denied`, sin revelar si un ID existe.
- Probado en `test.wwwarcelona.net` (conexión, herramientas, creación, edición, programación, rechazos y usuario Suscriptor).
- Documentación de pruebas con `curl` y `php -r`, sin dependencias extra.
