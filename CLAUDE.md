# mi_repo — notas para retomar el trabajo

Repositorio personal de Pedro León con plugins de WordPress. Este fichero
existe para que una sesión nueva (sin memoria de conversaciones previas)
pueda retomar el trabajo en curso sin perder contexto.

## Plugins

- `satelites-sistema-solar/` — plugin ya terminado y estable (v1.1.0).
  Muestra satélites de los planetas vía la API "Le Système Solaire".
  No hay trabajo pendiente aquí salvo que el usuario lo pida.

- `sondas-espaciales-timeline/` — **en desarrollo activo, poco a poco**.
  Rama de trabajo: `claude/space-probes-timeline-plugin-9cj5wu`.
  Shortcode `[sondas_espaciales_timeline]`: línea de tiempo horizontal
  (años 1959-hoy) x listado vertical de sondas espaciales, con icono de
  destino junto al nombre y una barra de color por sonda (lanzamiento →
  fin de misión), navegable con scroll (columna de nombre y cabecera de
  años fijas), buscador, filtro por destino y zoom. También tiene una
  vista alternativa de "Listado" (tabla ordenable por nombre, agencia,
  destino, año de lanzamiento/fin o estado), con un botón para alternar
  entre ambas vistas; buscador, filtro por destino y "solo activas" se
  aplican a las dos. Desde la v0.5.0 tiene además un panel de
  administración completo (menú "Sondas Espaciales") para gestionar
  sondas y destinos. Desde la v0.6.0, además del año de lanzamiento/fin,
  cada sonda puede tener la fecha completa (día/mes/año), visible en la
  tabla del listado y en el panel de admin (el tooltip del gráfico al
  pasar el ratón por las barras se deja tal cual, a petición expresa del
  usuario — no tocarlo). Desde la v0.7.0, una sonda puede además tener
  "destinos adicionales" (multi-destino): además de su destino
  principal, una lista de destinos intermedios con su propia fecha
  (p. ej. Voyager 1/2 y Pioneer 11 sobrevolando Júpiter antes de llegar
  a Saturno; Voyager 2 sobrevolando también Urano y Neptuno; New
  Horizons sobrevolando Júpiter de camino a Plutón). Se ven como
  distintivos junto al nombre y como marcadores sobre la barra en el
  gráfico (con su propio `title`, sin tocar el tooltip de la barra), y
  como una columna "Ruta" en el listado. Ver su propio `readme.txt`
  para el detalle de versión/changelog.

### Destinos adicionales / multi-destino (`SET_Data_Store::*_waypoints*`, desde v0.7.0)

  Tabla `wp_set_probe_waypoints` (`probe_id`, `destination`, `event_date`
  DATE NULL, `event_year` SMALLINT NULL, `sort_order`). El campo
  `destination` de la sonda sigue siendo el destino "principal" (icono
  junto al nombre, filtro por destino, color de la barra); los waypoints
  son un desglose adicional opcional, en el orden en que se guardan (no
  se reordenan automáticamente por fecha).
  - `SET_Data_Store::get_probe_waypoints( $probe_id )` /
    `get_waypoints_for_probes( $ids )` (bulk, evita N+1 en `get_probes()`)
    / `save_probe_waypoints( $probe_id, $waypoints )` (borra todos e
    inserta de nuevo — reemplazo completo, no hay update parcial) /
    `delete_probe_waypoints( $probe_id )`.
  - `delete_probe()` borra también los waypoints de esa sonda.
    `save_destination()` (al renombrar el `id`) reasigna también los
    waypoints que usaban la clave vieja. `delete_destination()` está
    bloqueado si el destino está en uso como principal **o** como
    waypoint de cualquier sonda (`count_probes_by_destination()` +
    `count_waypoints_by_destination()`); la pantalla de administración
    de destinos muestra ambos recuentos por separado.
  - Panel de admin (`SET_Admin_Probes`): formulario con una sección
    "Destinos adicionales", filas repetibles (destino + fecha) con
    botones "+ Añadir destino" / "Quitar" (JS en
    `sondas-espaciales-timeline-admin.js`, usando un `<template>` y
    `content.cloneNode(true)`). `handle_save()` reconstruye la lista
    desde `$_POST['waypoint_destination'][]` / `waypoint_date[]`,
    descarta filas sin destino elegido y valida que cada destino exista.
  - Seed (`includes/data/probes.php`): clave opcional `'waypoints'`,
    array de `array( 'destination' => ..., 'date' => 'Y-m-d'|null,
    'year' => int|null )`. Ya rellenada para `voyager-1`, `voyager-2`,
    `pioneer-11` y `new-horizons` (fechas de sobrevuelo reales, sacadas
    de las tablas "Jupiter probes"/"Saturn probes"/"Uranus probes"/
    "Neptune probes" de "List of Solar System probes"). `pioneer-10`
    solo visitó Júpiter, así que no lleva waypoints. Se añadió también
    un destino nuevo al catálogo, `pluton` (♇), y `new-horizons` pasó a
    tener `destination = 'pluton'` (antes `'multiple'`), con Júpiter
    como waypoint (asistencia gravitatoria de 2007).
  - El botón "Exportar a PHP" del admin también exporta los waypoints
    de cada sonda (bloque `'waypoints' => array( array( 'destination'
    => ..., 'date' => ..., 'year' => ... ), ... )`), solo si la sonda
    tiene alguno.

## Modelo de datos (sondas-espaciales-timeline, desde v0.5.0; ampliado en v0.6.0 y v0.7.0)

Los datos **ya no son ficheros PHP estáticos**: viven en dos tablas
propias de la base de datos de WordPress, `{prefix}set_probes` y
`{prefix}set_destinations`, creadas con `dbDelta()` en
`SET_Activator::activate()` (hook de activación del plugin, y también
comprobado en cada carga vía `plugins_loaded` por si la versión del
esquema — constante `SET_DB_VERSION` — cambia). Toda la lectura/escritura
pasa por `SET_Data_Store` (`includes/class-set-data-store.php`); ni el
shortcode ni el admin tocan `$wpdb` directamente.

- `includes/data/probes.php` y `includes/data/destinations.php` **siguen
  existiendo**, pero ahora son solo la "semilla de fábrica": se usan para
  poblar las tablas la primera vez (`SET_Data_Store::maybe_seed_defaults()`)
  y para el botón "Restaurar valores de fábrica" del panel
  (`SET_Data_Store::reset_to_defaults()`, que trunca ambas tablas y
  vuelve a sembrar). **No se editan a mano en el día a día** — para eso
  está el panel de administración.
- El panel de administración (menú "Sondas Espaciales" en wp-admin) tiene
  dos pantallas: "Sondas" (`SET_Admin_Probes`) y "Destinos"
  (`SET_Admin_Destinations`), cada una con listado
  buscable/filtrable/ordenable/paginado y formularios de alta/edición
  (con validación server-side; los errores se guardan en un transient y
  se muestran al volver al formulario, conservando lo ya escrito).
  Borrar un destino que todavía usa alguna sonda está bloqueado.
  Renombrar el `id` de un destino reasigna automáticamente las sondas que
  lo usaban (el `id` de una sonda, en cambio, no se puede cambiar una vez
  creada, para simplificar).
- Cada pantalla tiene un botón "Exportar a PHP" que descarga un fichero
  con el mismo formato que `includes/data/probes.php` /
  `destinations.php` — es la forma de volver a versionar en git una
  instantánea de los datos si el usuario lo pide (por ejemplo, para
  hacer commit de una remesa grande de altas hechas desde el panel).
- `uninstall.php` borra las dos tablas; una simple desactivación no
  toca nada.

A fecha de la última sesión: **235 sondas** en la semilla de fábrica
(`includes/data/probes.php`, v0.7.0; el número de sondas no cambió en la
v0.6.0 ni en la v0.7.0). Desglose aproximado por destino (destino
principal, no cuenta los waypoints): Luna 89, Marte 51, Venus 40,
heliofísica 15, asteroide 12, cometa 9, Júpiter 5, Sol 4, Mercurio 3,
Saturno 2, interestelar 2, múltiple 2, Plutón 1.

### Fechas completas (`launch_date`/`end_date`, desde v0.6.0)

Columnas `DATE NULL` en `wp_set_probes`, opcionales (muchas sondas solo
tienen año). `SET_Data_Store::format_date( $date, $fallback_year )`
centraliza el formateo (`d/m/Y` si hay fecha completa, si no el año, si
no un guion) y se usa tanto en el admin como en la tabla de listado del
shortcode. **El tooltip del gráfico no usa `format_date` y no se debe
tocar** — el usuario confirmó explícitamente que esa parte ya le vale
como está.

Al extraer fechas de los PDF hay que distinguir con cuidado:
- **Fallo de lanzamiento** (la sonda no llegó a escapar de la órbita
  terrestre, o falló nada más despegar): la única fecha de la fila de
  Wikipedia coincide con la fecha real de lanzamiento → usable como
  `launch_date`.
- **Cualquier otro evento** (sobrevuelo, inserción orbital, aterrizaje,
  pérdida de contacto en ruta): la fecha de la fila es la fecha de ESE
  evento, no la de lanzamiento. Si esa fecha coincide con el `end_year`
  ya registrado, es razonable usarla como `end_date`; nunca usarla como
  `launch_date`.
- "List of missions to Mars" es la excepción: tiene una columna "Launch
  date" dedicada y fiable, no hace falta aplicar esta distinción ahí.

Fuentes de Wikipedia (en inglés) ya procesadas por completo — **no hace
falta volver a pedirlas**, ya están incorporadas a la semilla de fábrica:
- "List of Solar System probes" (versión completa, ~44 páginas en PDF):
  Sol, Mercurio, Venus, Marte, Júpiter, Saturno, Titán, Urano, Neptuno,
  Plutón, cometas, cinturón de Kuiper — todo cubierto.
- "List of lunar probes" (~30 páginas): programas Luna, Ranger, Zond,
  Surveyor, Lunar Orbiter, Lunokhod, y toda la era comercial/privada
  reciente (Beresheet, Peregrine, IM-1/IM-2, Blue Ghost, Hakuto-R...).
- "List of missions to Mars" (~27 páginas): usada sobre todo para
  contrastar fechas exactas de lanzamiento (tiene una columna "Launch
  date" dedicada, más fiable que las fechas de evento de las otras
  listas) y para completar sondas soviéticas tempranas.

Fuentes que **todavía no se han pedido/procesado** y podrían aportar más
sondas si el usuario las adjunta como PDF: listas dedicadas de Venus,
Júpiter, Saturno, planetas exteriores, asteroides o cometas (aunque la
lista completa de "Solar System probes" ya cubre razonablemente esas
categorías, así que el margen de mejora ahí es menor que en Marte/Luna).

## Si el usuario pide ampliar/corregir sondas a partir de una fuente nueva

Como los datos ya no están en el PHP, el flujo cambia respecto a antes:
la forma más directa es hacerlo **a través del panel de administración**
si hay un WordPress real disponible; si se está trabajando solo en este
repo (sin WordPress), lo más práctico sigue siendo editar
`includes/data/probes.php`/`destinations.php` (mismo formato de
siempre — ver convenciones más abajo) y avisar al usuario de que, para
que los cambios lleguen a su sitio ya instalado, tocará o bien volver a
activar el plugin sobre una base de datos limpia, o bien usar el botón
"Restaurar valores de fábrica" del panel (que sobreescribe cualquier
edición manual que el usuario haya hecho desde el propio panel, así que
hay que avisarle antes de sugerirlo).

### Convenciones de `includes/data/probes.php` (semilla de fábrica)

- Un array por sonda: `id` (slug único), `name`, `agency`,
  `destination` (clave de `includes/data/destinations.php`),
  `launch_year` (int, año real de lanzamiento — no el de inserción
  orbital/aterrizaje/sobrevuelo), `end_year` (int|null; `null` = activa),
  `status` (`activa` | `finalizada` | `perdida` | `fallida`), `note`.
- **Truco para el año de lanzamiento real**: en las tablas de Wikipedia,
  la columna "Ref" suele enlazar al identificador COSPAR
  (`AAAA-NNNL`, ej. `1965-092A`); los 4 primeros dígitos son el año de
  lanzamiento real. La columna "Date" de esas tablas NO es fiable como
  fecha de lanzamiento salvo que la sonda fallara en el lanzamiento
  mismo (para orbitadores/aterrizadores/sobrevuelos es la fecha de
  inserción orbital, aterrizaje o encuentro más cercano).
- Una fila por nave principal: los CubeSats de acompañamiento y
  subcargas menores (p. ej. los ~10 CubeSats de Artemis I, DCAM1/DCAM2
  de Hayabusa2, Minerva de Hayabusa2, Okina/Ouna de Kaguya) se omiten
  deliberadamente para no saturar la línea de tiempo, salvo que sean
  muy notables por sí mismos (ej. Ingenuity se menciona en la nota de
  Perseverance en vez de añadirse aparte).
- El fichero mantiene un orden cronológico aproximado por bloques, con
  comentarios `// A partir de aquí: ...` que documentan de qué fuente
  vino cada bloque y cuándo se añadió — mantener ese patrón al ampliar.
- Los años del eje X se recalculan solos en el shortcode
  (`SET_TIMELINE_START_YEAR` = 1959; el año final es el actual).

### Comprobación de un lote grande de cambios

1. Leer el PDF con `Read` + `pages` (máx. 20 páginas por llamada).
2. Antes de añadir, comprobar duplicados: `grep` por nombre en
   `includes/data/probes.php`.
3. `php -l` sobre todos los ficheros tocados.
4. Verificación funcional real (no solo sintaxis): en el scratchpad de la
   sesión se ha montado un `$wpdb` falso respaldado por SQLite de verdad
   (vía `PDO`), con stubs de las funciones de WordPress necesarias
   (`sanitize_*`, `current_user_can`, `wp_nonce_*`, `add_query_arg`,
   `get_transient`/`set_transient`, `wp_safe_redirect` capturado como
   excepción para poder probar los `admin_post_*` sin matar el proceso,
   etc.). Con eso se puede llamar directamente a
   `SET_Data_Store::maybe_seed_defaults()`, `query_probes()`,
   `save_probe()`, `save_destination()`, y a los `handle_*` del admin,
   y comprobar de verdad el SQL generado, no solo que el PHP no falle.
   Ese arnés no está en el repo (era solo del scratchpad); si hace
   falta, se puede recrear en unos minutos siguiendo ese patrón
   (`Fake_WPDB` sobre `new PDO('sqlite::memory:')`).
5. Comprobación visual: renderizar el HTML (shortcode o pantallas del
   admin) a un fichero con el mismo arnés de stubs y abrirlo con
   Playwright/Chromium (`/opt/pw-browsers/chromium`, vía
   `NODE_PATH=/opt/node22/lib/node_modules`) para hacer capturas.
6. Subir siempre a la rama `claude/space-probes-timeline-plugin-9cj5wu`
   (nunca a `master` directamente), actualizando versión en
   `sondas-espaciales-timeline.php` (constantes `SET_VERSION` y
   `SET_DB_VERSION` si cambia el esquema) y el changelog de `readme.txt`.
