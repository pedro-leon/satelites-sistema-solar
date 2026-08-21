=== Sondas Espaciales - Línea de Tiempo ===
Contributors: pedroleon
Tags: sondas, espacio, astronomia, linea de tiempo, exploracion espacial
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Línea de tiempo navegable con todas las sondas espaciales lanzadas desde 1959 hasta hoy.

== Description ==

Este plugin añade el shortcode `[sondas_espaciales_timeline]`, que muestra una línea de tiempo con dos ejes:

* En horizontal, los años desde 1959 (primer lanzamiento, la Luna 1) hasta la actualidad.
* En vertical, un listado de sondas espaciales, cada una con una barra de color que empieza en su año de lanzamiento y termina cuando finalizó la misión o se perdió el contacto.

Cada sonda muestra, junto a su nombre, un icono circular de color que indica su destino (Luna, Sol, Marte, Júpiter, un cometa, un asteroide...). El color de la barra corresponde a ese mismo destino, y su estilo indica el estado de la misión:

* Barra sólida: misión finalizada con normalidad.
* Barra con un punto verde al final: misión activa (llega hasta el día de hoy).
* Barra con rayas diagonales: se perdió el contacto antes de lo previsto.
* Barra con rayas cruzadas: la misión falló (no llegó a cumplir su objetivo).

El bloque es navegable mediante las barras de desplazamiento del propio navegador (la cabecera de años y la columna de nombres quedan fijas al desplazarse), e incluye:

* Un buscador por nombre/agencia.
* Un filtro por destino (con una leyenda de iconos que también funciona como filtro rápido).
* Controles de zoom para ajustar la escala temporal.

Los datos de las sondas son estáticos y se mantienen en `includes/data/probes.php`: es un fichero pensado para ampliarse y corregirse con el tiempo, añadiendo nuevas misiones o completando las existentes. Los destinos disponibles (con su icono y color) están en `includes/data/destinations.php`.

== Installation ==

1. Sube la carpeta `sondas-espaciales-timeline` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú "Plugins" de WordPress.
3. Añade el shortcode `[sondas_espaciales_timeline]` a cualquier página o entrada.

== Frequently Asked Questions ==

= ¿De dónde vienen los datos de las sondas? =

Es un listado curado manualmente (no proviene de ninguna API), pensado como punto de partida. Los años de lanzamiento y fin de misión pueden contener imprecisiones y se irán revisando y ampliando con el tiempo.

= ¿Cómo añado o corrijo una sonda? =

Editando el array que devuelve `includes/data/probes.php`. Cada sonda es un elemento con: id, name, agency, destination (clave de `destinations.php`), launch_year, end_year (null si sigue activa), status (`activa`, `finalizada`, `perdida` o `fallida`) y una nota opcional.

= ¿Cómo añado un destino nuevo (por ejemplo, un cometa concreto)? =

Añadiendo una entrada nueva en `includes/data/destinations.php` con su etiqueta, un símbolo (código) y un color; se usará automáticamente en el icono de las sondas y en la leyenda.

== Changelog ==

= 0.1.0 =
* Primera versión: línea de tiempo con buscador, filtro por destino y zoom, y un listado inicial de varias decenas de sondas históricas.
