# Changelog

Cambios por versión publicada. El día a día (pruebas, decisiones, pendientes) está en [ESTADO.md](ESTADO.md).

## 0.1.0 (sin publicar)

- Servidor MCP propio en `/wp-json/wwwarcelona/mcp` con cuatro tools: listar, leer, crear y editar entradas.
- El agente solo puede dejar entradas en borrador, pendientes o programadas; no publica, no borra y no toca páginas.
- Página *Herramientas → Conectar con Claude*: crea la conexión con un clic y muestra un comando de una línea para pegar en la terminal (Mac, Linux y Windows), con instrucciones en pantalla, el usuario para el que se crea y la lista de conexiones del usuario.
- Quien no puede editar entradas recibe siempre `Permission denied`, sin revelar si un ID existe.
- Probado en `test.wwwarcelona.net`: conexión, herramientas, creación, edición, programación, rechazos, usuarios Suscriptor y Autor, y tareas reales desde Claude Code conectado con la página.
- Documentación: campos de cada herramienta, permisos por rol, uso con Claude Code, cómo quitar una conexión, seguridad y limitaciones conocidas, y pruebas con `curl` y `php -r` sin dependencias extra.
