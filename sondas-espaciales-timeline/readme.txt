=== Sondas Espaciales - Línea de Tiempo ===
Contributors: pedroleon
Tags: sondas, espacio, astronomia, linea de tiempo, exploracion espacial
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Línea de tiempo navegable con todas las sondas espaciales lanzadas desde 1959 hasta hoy.

== Description ==

Este plugin añade el shortcode `[sondas_espaciales_timeline]`, que muestra una línea de tiempo con dos ejes:

* En horizontal, los años desde 1959 (primer lanzamiento, la Luna 1) hasta la actualidad.
* En vertical, un listado de sondas espaciales, cada una con una barra de color que empieza en su año de lanzamiento y termina cuando finalizó la misión o se perdió el contacto.

También se puede cambiar a una vista de **listado**: una tabla con todas las misiones, ordenable haciendo clic en cualquier columna (nombre, agencia, destino, año de lanzamiento, año de fin o estado).

Cada sonda muestra, junto a su nombre, un icono circular de color que indica su destino (Luna, Sol, Marte, Júpiter, un cometa, un asteroide...). El color de la barra corresponde a ese mismo destino, y su estilo indica el estado de la misión:

* Barra sólida: misión finalizada con normalidad.
* Barra con un punto verde al final: misión activa (llega hasta el día de hoy).
* Barra con rayas diagonales: se perdió el contacto antes de lo previsto.
* Barra con rayas cruzadas: la misión falló (no llegó a cumplir su objetivo).

El bloque es navegable mediante las barras de desplazamiento del propio navegador (la cabecera de años y la columna de nombres quedan fijas al desplazarse), e incluye:

* Un buscador por nombre/agencia.
* Un filtro por destino (con una leyenda de iconos que también funciona como filtro rápido).
* Una casilla para mostrar solo las misiones activas.
* Controles de zoom para ajustar la escala temporal (vista de gráfico).

El buscador, el filtro por destino y la casilla de "solo activas" se aplican por igual a las dos vistas.

Los datos de las sondas y de los destinos viven en dos tablas propias de la base de datos (`wp_set_probes` y `wp_set_destinations`), gestionables por completo desde un panel de administración: **Sondas Espaciales** en el menú de WordPress, con dos pantallas — "Sondas" y "Destinos" — para añadir, editar y borrar registros, buscar, filtrar y ordenar. También se puede restaurar todo a los valores de fábrica del plugin, o exportar los datos actuales a un fichero PHP (útil para guardar una copia en el repositorio).

Las tablas se crean automáticamente al activar el plugin, y se siembran con los datos de fábrica de `includes/data/probes.php` y `includes/data/destinations.php` la primera vez (esos ficheros siguen en el repositorio como semilla inicial y como formato de exportación, pero ya no son la fuente de datos en caliente).

== Installation ==

1. Sube la carpeta `sondas-espaciales-timeline` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú "Plugins" de WordPress (esto crea las tablas propias y las siembra con los datos de fábrica).
3. Añade el shortcode `[sondas_espaciales_timeline]` a cualquier página o entrada.
4. Gestiona las sondas y los destinos desde el menú "Sondas Espaciales" del panel de administración.

== Frequently Asked Questions ==

= ¿De dónde vienen los datos de las sondas? =

Es un listado curado manualmente (no proviene de ninguna API), pensado como punto de partida. Los años de lanzamiento y fin de misión pueden contener imprecisiones y se pueden corregir en cualquier momento desde el panel de administración.

= ¿Cómo añado, corrijo o borro una sonda? =

Desde el menú "Sondas Espaciales" → "Sondas" del panel de administración: hay botones para añadir una sonda nueva, y enlaces de "Editar"/"Borrar" en cada fila del listado.

= ¿Cómo añado un destino nuevo (por ejemplo, un cometa concreto)? =

Desde "Sondas Espaciales" → "Destinos", con su etiqueta, un símbolo corto y un color (hay un selector de color). No se puede borrar un destino mientras alguna sonda lo esté usando.

= Si desinstalo el plugin, ¿pierdo mis datos? =

Al desactivarlo no se pierde nada. Al desinstalarlo desde el panel de administración sí se borran las tablas propias (`wp_set_probes` y `wp_set_destinations`); si quieres conservar una copia antes, usa el botón "Exportar a PHP" de cada pantalla.

== Changelog ==

= 0.5.0 =
* Añadido un panel de administración completo ("Sondas Espaciales" en el menú de WordPress) para añadir, editar y borrar sondas y destinos, con búsqueda, filtros, orden y paginación.
* Los datos pasan de ficheros PHP estáticos a dos tablas propias de la base de datos (`wp_set_probes`, `wp_set_destinations`), creadas y sembradas automáticamente al activar el plugin.
* Añadidos botones de "Restaurar valores de fábrica" y "Exportar a PHP" (mismo formato que `includes/data/probes.php`/`destinations.php`), para poder seguir guardando copias en el repositorio si se quiere.

= 0.4.0 =
* Añadida una vista de "Listado": una tabla con todas las misiones, ordenable por nombre, agencia, destino, año de lanzamiento, año de fin o estado. Se alterna con la vista de gráfico mediante dos botones en la parte superior.
* Añadida una casilla "Solo activas" que filtra ambas vistas.

= 0.3.0 =
* Ampliado el listado de sondas de 185 a 235, a partir de los artículos completos de Wikipedia "List of Solar System probes", "List of lunar probes" y "List of missions to Mars": se completan los programas Luna, Ranger, Zond y Lunokhod (retorno de muestras y róvers soviéticos), toda la era reciente de alunizadores privados/comerciales (Beresheet, Peregrine, IM-1 Odysseus, Blue Ghost, Hakuto-R, IM-2 Athena, Luna 25...), varias sondas soviéticas tempranas a Marte, y Beagle 2, Fobos-Grunt, ESCAPADE y Suisei, entre otras.
* Corregidos el estado y las notas de Mars 3, Mars 5, Mars 6, Sakigake y MAVEN (fin de misión en 2025) con datos más precisos de las fuentes.

= 0.2.0 =
* Ampliado el listado de sondas de ~90 a 185, contrastando datos con la tabla "List of Solar System probes" de Wikipedia (en inglés) y con Wikidata: se añaden los programas Ranger y Lunar Orbiter (Luna), las primeras misiones soviéticas a Marte (Mars/Zond/Fobos), sondas heliofísicas (WIND, SOHO, ACE, STEREO, DSCOVR...) y varias misiones de asteroides (Psyche, Hera, LICIACube, Tianwen-2), entre otras.
* Corregidos varios años de lanzamiento/fin de misión (Mariner 10, Venus Express, Akatsuki) y se añade Curiosity, que faltaba.

= 0.1.0 =
* Primera versión: línea de tiempo con buscador, filtro por destino y zoom, y un listado inicial de varias decenas de sondas históricas.
