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
  años fijas), buscador, filtro por destino y zoom. Ver su propio
  `readme.txt` para el detalle de versión/changelog.

## Estado de los datos (sondas-espaciales-timeline)

A fecha de la última sesión: **235 sondas** en
`includes/data/probes.php` (v0.3.0). Desglose aproximado por destino:
Luna 89, Marte 51, Venus 40, heliofísica 15, asteroide 12, cometa 9,
Júpiter 5, Sol 4, Mercurio 3, múltiple 3, Saturno 2, interestelar 2.

Fuentes de Wikipedia (en inglés) ya procesadas por completo — **no hace
falta volver a pedirlas**, ya están incorporadas:
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

## Convenciones de `includes/data/probes.php`

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

## Flujo de trabajo para añadir/corregir sondas

1. Leer el PDF con `Read` + `pages` (máx. 20 páginas por llamada).
2. Antes de añadir, comprobar duplicados: `grep` por nombre en
   `includes/data/probes.php`.
3. Validar tras cada edición grande:
   `php -l includes/data/probes.php`, y un script PHP rápido que cargue
   el array con `ABSPATH` definido (el fichero hace `exit` si no lo
   está) para comprobar IDs duplicados y destinos inválidos.
4. Comprobación visual: no hay build ni entorno WordPress en este repo,
   así que para previsualizar el shortcode se ha usado un script PHP
   suelto en el scratchpad de la sesión que define stubs mínimos de
   funciones de WordPress (`esc_html`, `__`, `wp_unique_id`,
   `current_datetime`...), incluye `class-set-data-store.php` y
   `class-set-shortcode.php`, y vuelca el HTML+CSS+JS a un fichero para
   abrirlo con Playwright/Chromium (`/opt/pw-browsers/chromium`, vía
   `NODE_PATH=/opt/node22/lib/node_modules`). Ese script no está en el
   repo (era solo del scratchpad); si hace falta, se puede recrear en
   dos minutos siguiendo ese patrón.
5. Subir siempre a la rama `claude/space-probes-timeline-plugin-9cj5wu`
   (nunca a `master` directamente), actualizando versión en
   `sondas-espaciales-timeline.php` y el changelog de `readme.txt`.
