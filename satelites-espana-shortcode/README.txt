=== Satelites Espana Shortcode ===
Contributors: Pedro Leon with Codex
Tags: satelites, espana, shortcode, gcat
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.3.0
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

Los datos se guardan en cache en WordPress y se actualizan automaticamente una vez a la semana mediante WP-Cron. Si la tabla se visita y la ultima sincronizacion tiene mas de una semana, el shortcode sigue mostrando la cache actual y programa la descarga en segundo plano (no bloquea al visitante). Si todavia no hay ninguna cache, la primera visita si espera a la descarga inicial. La fecha de actualizacion declarada por la fuente se extrae de la linea "# Updated ..." del TSV original y se muestra bajo la tabla.

Las peticiones a GCAT son condicionales (If-None-Match / If-Modified-Since): si la fuente no ha cambiado desde la ultima descarga, responde 304 y no se reenvia el TSV completo.

Si una sincronizacion falla, se conserva la ultima cache valida y se muestra un aviso con el motivo del fallo en Ajustes > Satelites Espana.

Si un satelite anadido manualmente coincide en "Pieza" con uno que GCAT termina publicando oficialmente, se muestra solo la version de GCAT.

== Changelog ==

= 1.3.0 =
* Rediseño completo del aspecto visual: tema oscuro tipo "centro de control", tipografía monoespaciada, acentos en cian y separadores de año a modo de panel de estado.
* Titular fijo sobre la tabla: "Listado de satélites de España lanzados al espacio hasta [año actual]".
* La columna ID se muestra como una pequeña etiqueta/badge.
* La tabla se envuelve en un contenedor con scroll horizontal para pantallas estrechas.

= 1.2.0 =
* La actualizacion semanal ya no bloquea la carga de la pagina para el visitante: se sirve la cache actual y la descarga se programa en segundo plano.
* Se muestra un aviso en el admin cuando falla una sincronizacion con GCAT.
* Peticiones condicionales a GCAT (ETag / Last-Modified) para no volver a descargar el TSV si no ha cambiado.
* El CSS del shortcode se imprime una sola vez por pagina, aunque se use el shortcode varias veces.
* Enlace a GCAT con rel="noopener noreferrer".
* Los satelites manuales se ocultan automaticamente si GCAT publica la misma pieza.
* Se anade uninstall.php para borrar las opciones del plugin al desinstalarlo.

= 1.1.5 =
* Version inicial publicada en este repositorio.
