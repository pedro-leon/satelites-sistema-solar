=== Satelites Espana Shortcode ===
Contributors: Pedro Leon with Codex
Tags: satelites, espana, shortcode, gcat
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.1.5
License: GPL-2.0-or-later

Muestra una tabla con los satelites espaciales cuyo SatState es "E" en el launch log de GCAT. (J. McDowell, planet4589.org/space/gcat)

== Uso ==

Activa el plugin y anade este shortcode en una pagina o entrada:

[satelites_espana]

Tambien esta disponible el alias:

[satelites_espaciales_espana]

== Datos mostrados ==

La tabla muestra, en este orden:

* Fecha de lanzamiento
* Pieza
* Nombre
* Propietario del satelite
* Vehiculo de lanzamiento

Las filas se numeran automaticamente y se ordenan cronologicamente por fecha de lanzamiento. La tabla anade una fila separadora en negrita al comenzar cada ano con lanzamientos.

== Administracion ==

El plugin anade una pantalla en Ajustes > Satelites Espana.

Desde esa pantalla se pueden anadir satelites manuales con fecha, hora opcional, pieza, nombre, propietario y vehiculo de lanzamiento. Esos satelites se incorporan al listado publico junto con los datos de GCAT y se ordenan por fecha de lanzamiento.

== Actualizacion ==

El plugin consulta esta fuente:

https://planet4589.org/space/gcat/tsv/derived/launchlog.tsv

Los datos se guardan en cache en WordPress y se actualizan automaticamente una vez a la semana mediante WP-Cron. Si la tabla se visita y la ultima sincronizacion tiene mas de una semana, el shortcode fuerza una nueva sincronizacion antes de mostrarse. La fecha de actualizacion declarada por la fuente se extrae de la linea "# Updated ..." del TSV original y se muestra bajo la tabla.
