=== Satélites del Sistema Solar ===
Contributors: pedroleon
Tags: satelites, lunas, planetas, api, astronomia
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Muestra el número de satélites de cada planeta del sistema solar y un listado ordenable con sus características, obtenidos semanalmente desde una API pública.

== Description ==

Este plugin descarga automáticamente, una vez a la semana, los datos de los satélites naturales de los ocho planetas del sistema solar desde la API pública y gratuita **"Le Système Solaire"** (api.le-systeme-solaire.net).

Añade el shortcode `[satelites_sistema_solar]`, que muestra:

1. Un resumen con el número total de satélites de cada planeta y el número total de todos ellos.
2. Un selector para elegir un planeta y un listado completo de sus satélites con: nombre, nombre provisional, distancia al planeta, diámetro, densidad, año de descubrimiento y descubridor.
3. El listado se puede ordenar por cualquiera de esas características haciendo clic en la cabecera de la columna correspondiente.

Incluye una página de administración ("Satélites SS") donde se puede consultar la fecha de la última actualización, la próxima actualización programada y forzar una actualización manual.

== Installation ==

1. Sube la carpeta `satelites-sistema-solar` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú "Plugins" de WordPress.
3. Al activarse se programa una tarea semanal (WP-Cron) y se realiza una primera descarga de datos.
4. Añade el shortcode `[satelites_sistema_solar]` a cualquier página o entrada.

== Frequently Asked Questions ==

= ¿De dónde vienen los datos? =

De la API pública y gratuita "Le Système Solaire" (https://api.le-systeme-solaire.net), que no requiere clave de acceso.

= ¿Con qué frecuencia se actualizan los datos? =

Una vez a la semana, mediante WP-Cron. También se pueden actualizar manualmente desde el menú "Satélites SS" del panel de administración.

= ¿Qué planetas incluye? =

Los ocho planetas oficiales del sistema solar (Mercurio a Neptuno). No incluye planetas enanos como Plutón.

== Changelog ==

= 1.0.0 =
* Versión inicial.
