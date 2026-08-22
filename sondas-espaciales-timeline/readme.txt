=== Sondas Espaciales - Línea de Tiempo ===
Contributors: pedroleon
Tags: sondas, espacio, astronomia, linea de tiempo, exploracion espacial
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.8.0
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

= 0.8.0 =
* Las tablas de listado (frontend y panel de administración) ya no muestran el año como respaldo cuando falta la fecha completa: si no hay día/mes/año, la celda queda en blanco (un guion), en vez de mostrar solo el año.
* Rellenadas ~100 fechas completas más (lanzamiento y/o fin) a partir de una segunda pasada por los tres PDF de Wikipedia, con especial atención al PDF de "List of lunar probes" (tiene una clave explícita sobre qué representa cada fecha) y a las notas de "List of missions to Mars", que en varios casos dan lanzamiento/inserción orbital/fin explícitos con las palabras "(launch)" o similares.
* Nueva migración automática (`SET_Data_Store::backfill_missing_data_from_seed()`, en cada activación/actualización del plugin): si una instalación ya existente tiene una sonda sin fecha completa o sin destinos adicionales que la semilla de fábrica sí incorpora en una versión más reciente, se rellenan esos huecos sin tocar ningún otro dato que el usuario ya tenga editado. Antes, una instalación existente solo recibía los datos nuevos si se restauraban los valores de fábrica.
* Vega 1 y Vega 2 pasan a llevar Venus y el cometa Halley como destinos adicionales con su fecha (antes solo tenían el destino genérico "Varios destinos", sin desglose).

= 0.7.0 =
* Soporte de multi-destino: una sonda puede tener, además de su destino principal, una lista de "destinos adicionales" con su propia fecha (p. ej. las Voyager y Pioneer 11 sobrevolando Júpiter antes de Saturno, o Voyager 2 sobrevolando también Urano y Neptuno; New Horizons sobrevolando Júpiter de camino a Plutón). Se gestionan desde el formulario de la sonda en el panel de administración, con filas repetibles para añadir o quitar destinos.
* La línea de tiempo (vista Gráfico) muestra un marcador en la barra por cada destino adicional, en la fecha correspondiente, además de distintivos junto al nombre de la sonda. La vista Listado añade una columna "Ruta" con los mismos distintivos. El tooltip de la barra al pasar el ratón no cambia.
* Nueva tabla `wp_set_probe_waypoints` (destino + fecha por sonda). Borrar una sonda borra también sus destinos adicionales; renombrar un destino reasigna también los destinos adicionales que lo usaban; un destino usado solo como destino adicional (sin ninguna sonda que lo tenga como principal) también bloquea su borrado.
* Añadido el destino "Plutón" al catálogo; New Horizons pasa a tener a Plutón como destino principal (antes "Varios destinos"), con Júpiter como destino adicional (asistencia gravitatoria de 2007).
* Voyager 1, Voyager 2 y Pioneer 11 llevan ahora sus sobrevuelos intermedios (con fecha) como destinos adicionales.

= 0.6.0 =
* Añadida la fecha completa (día/mes/año) de lanzamiento y de fin de misión, además del año: se muestra en la tabla del listado y en el panel de administración (nuevos campos de fecha en el formulario de sondas). El tooltip del gráfico al pasar el ratón por las barras no cambia.
* Nuevas columnas `launch_date` y `end_date` (fecha, opcionales) en la tabla `wp_set_probes`; se conservan las sondas que solo tienen año.
* Rellenadas fechas completas de más de 70 sondas a partir de "List of missions to Mars" (columna dedicada de fecha de lanzamiento) y de las tablas de fallos de lanzamiento/fin de misión de "List of Solar System probes".

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
