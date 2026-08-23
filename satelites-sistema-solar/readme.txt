=== Satélites del Sistema Solar ===
Contributors: pedroleon
Tags: satelites, lunas, planetas, api, astronomia
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Muestra el número de satélites de cada planeta del sistema solar y un listado ordenable con sus características, obtenidos semanalmente desde una API pública.

== Description ==

Este plugin descarga automáticamente, una vez a la semana, los datos de los satélites naturales de los ocho planetas del sistema solar desde la API pública **"Le Système Solaire"** (api.le-systeme-solaire.net). La API es gratuita, pero requiere generar una clave y configurarla en el plugin (ver "Instalación").

Añade el shortcode `[satelites_sistema_solar]`, que muestra:

1. Un resumen con el número total de satélites de cada planeta y el número total de todos ellos.
2. Un selector para elegir un planeta y un listado completo de sus satélites con: nombre, nombre provisional, distancia al planeta, diámetro, densidad, año de descubrimiento y descubridor.
3. El listado se puede ordenar por cualquiera de esas características haciendo clic en la cabecera de la columna correspondiente.

Incluye una página de administración ("Satélites SS") donde se puede consultar la fecha de la última actualización, la próxima actualización programada y forzar una actualización manual.

== Installation ==

1. Sube la carpeta `satelites-sistema-solar` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú "Plugins" de WordPress.
3. Genera una clave de API gratuita en https://api.le-systeme-solaire.net/generatekey.html y guárdala en el menú "Satélites SS" del panel de administración. Sin ella, la descarga de datos fallará con un error 401.
4. Fuerza una actualización manual desde esa misma página (o espera a la tarea semanal automática).
5. Añade el shortcode `[satelites_sistema_solar]` a cualquier página o entrada.

== Frequently Asked Questions ==

= ¿De dónde vienen los datos? =

De la API pública "Le Système Solaire" (https://api.le-systeme-solaire.net).

= ¿Necesito una clave de API? =

Sí. Es gratuita, pero obligatoria: la API la exige como token de tipo `Bearer` en la cabecera `Authorization`. Genera la tuya en https://api.le-systeme-solaire.net/generatekey.html y pégala en el menú "Satélites SS" del panel de administración. Sin clave, el plugin no podrá descargar datos y lo indicará tanto en el panel como donde se use el shortcode.

= ¿Con qué frecuencia se actualizan los datos? =

Una vez a la semana, mediante WP-Cron. También se pueden actualizar manualmente desde el menú "Satélites SS" del panel de administración.

= ¿Qué planetas incluye? =

Los ocho planetas oficiales del sistema solar (Mercurio a Neptuno). No incluye planetas enanos como Plutón.

== Changelog ==

= 1.3.0 =
* El CSS se blinda frente a temas y constructores de página (p. ej. Elementor) que aplican sus propios estilos globales a `<button>`, `<h3>`, `<p>`, etc., y que hacían que los recuadros de planeta se vieran con los colores del tema y el nombre/número en la misma línea.
* Antes de elegir un planeta se muestra un aviso ("Pulsa en un planeta para ver el listado de sus satélites.") en lugar de no mostrar nada.
* El HTML del shortcode se genera más compacto para evitar que `wpautop`/algunos constructores de página añadan espacios de más.
* Al actualizar el plugin, si hay datos guardados de una versión anterior se refrescan solos en segundo plano (vía WP-Cron) en vez de esperar a la siguiente ejecución semanal o a que alguien pulse "Actualizar datos ahora".

= 1.2.0 =
* Rediseño del resumen y el listado: menos espaciado, recuadros de planeta más pequeños, título centrado ("Número de satélites por planeta").
* Se elimina el selector de planeta: cada recuadro es ahora un botón que muestra/oculta debajo la tabla de sus satélites.
* Columnas de diámetro, densidad y año más estrechas; en la de descubridor se muestra solo el primer nombre, con el resto visible al pasar el ratón por encima.
* Corregido el campo de distancia al planeta (la API lo llama "semimajorAxis", no "semiMajorAxis"), que hasta ahora siempre salía vacío. También afectaba, sin que se notara, al orden de los planetas por distancia al Sol.

= 1.1.0 =
* La API pública ahora exige una clave gratuita (token Bearer). Se añade un campo en el panel de administración para configurarla, y se avisa claramente si falta.
* Los errores de la API ahora muestran también un fragmento de la respuesta, para facilitar el diagnóstico.

= 1.0.0 =
* Versión inicial.
