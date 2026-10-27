# Continuación del proyecto

## Consideraciones previas

En este punto del proyecto, paso todas las especificaciones de la planificación que teníamos en mente:
- La idea es que queremos realizar un plugin de Claude que contenga unas skills que, a partir de un excel con titulos, keywqord y fechas, genere todo el contenido de unos postst programables.
- OJO, ahora no vamos a programar este plugin, eso a parte, nos seguimos centrando en el MCP de este proyecto.
- La cosa es que ese plugin queremos que se conecte a este mcp en cada proyecto donde se quiera usar.
- Lo van a llevar acabo unos usuairos del euipo de márketing con acceso limitado de autor, editor, etc.
- En el futuro se harán nuevos plugins y funionalidades, por lo que este mcp escalará para pemritir nuevas abilities. LO que queremos es controlarlo debidamente.

## Especificaciones a implementar
- Para limitar todod lo que puedan hacer, se tienen que permitir solamente unas abilities muy determinadas.
- Para esto hay que proponer una estrategia para que como adminstradores pudiéramos otorgar o revocar abilities a los usuarios

## Temas de seguridad
QUeremos comporbar la seguridad del plugin
- Hacer un análisis y obtener una propuesta de cómo podemo imlmeentar la seguridad del plugin y como proceder al respecto.
- INdicar si seria conveniente usar esta estrategia del mcp para usar la IA en wordpress o proponer una alternativa más segura

## Otras propuestas
- MIrar si sería más conveniente simplemetne usasndo la habildiad de Claude para depurar Chrome u otrosop navegadores
- El uso de la libreria https://playwright.dev
- El equipo de backend y sistemas indica que el mcp es insegura de cara a entorno de producción y que convniene valorar su uso o no
- Desde marketing ya tenían una tuomatización para ciertos proyectos donde se hacian los postst con n8n, peropara proyectos limitados y que requiere afinar cad auno de ellosy modificar el json de n8n. Mirar si la nueva propuesta mejora lo que hacen.
- Mirar si el control de habilities se puede definidr defniitivamente en las skills de los futuros plugins en vez de unos paneles