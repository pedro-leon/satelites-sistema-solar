Plugin de WordPress: Proximos Lanzamientos Espaciales

Instalacion
1. Copia la carpeta `proximos-lanzamientos` dentro de `wp-content/plugins/`.
2. Activa el plugin desde el panel de WordPress.
3. Inserta el shortcode `[proximos_lanzamientos]` en cualquier pagina o entrada.

Como funciona
- El plugin consulta Launch Library 2 desde el servidor (WP-Cron), no desde
  el navegador de cada visitante, y guarda los datos en cache local. El
  intervalo de actualizacion y el numero de lanzamientos a mostrar se
  configuran en el panel de administracion ("Lanzamientos" en el menu).
- El shortcode renderiza las tarjetas ya en el HTML inicial (a partir de la
  cache), y el JavaScript del front-end las refresca despues leyendo un
  endpoint REST propio del sitio (`/wp-json/ple/v1/launches`), sin llamar
  nunca directamente a la API externa desde el navegador.
- La hora principal se muestra en la zona local del navegador y la UTC
  aparece debajo.
- Si un lanzamiento trae enlaces publicos, el plugin muestra botones como
  `Video`, `Web` o `Web oficial`.

Notas
- Requiere WordPress 5.8+ y PHP 7.4+.
- Al desinstalar el plugin se eliminan sus opciones y la tarea programada.
