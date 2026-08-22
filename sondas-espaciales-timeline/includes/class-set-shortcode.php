<?php
/**
 * Shortcode público [sondas_espaciales_timeline].
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Shortcode {

	const TAG = 'sondas_espaciales_timeline';

	/**
	 * Registra el shortcode y los assets asociados.
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Encola el CSS/JS del frontend.
	 *
	 * Al igual que en el resto de shortcodes del sitio, se cargan en todas
	 * las páginas del front-end porque el shortcode puede insertarse desde
	 * widgets o constructores de páginas donde has_shortcode() no es fiable.
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'set-frontend',
			SET_PLUGIN_URL . 'assets/css/sondas-espaciales-timeline.css',
			array(),
			SET_VERSION
		);

		wp_enqueue_script(
			'set-frontend',
			SET_PLUGIN_URL . 'assets/js/sondas-espaciales-timeline.js',
			array(),
			SET_VERSION,
			true
		);
	}

	/**
	 * Calcula el año actual "fraccionario" (p.ej. 2026.6) para dibujar la
	 * línea de "hoy" y para hacer que las barras de las misiones activas
	 * lleguen hasta el punto exacto del año en curso.
	 *
	 * @return float
	 */
	private static function get_today_fraction() {
		$now         = current_datetime();
		$day_of_year = (int) $now->format( 'z' );
		$days_total  = ( (int) $now->format( 'L' ) === 1 ) ? 366 : 365;

		return (int) $now->format( 'Y' ) + ( $day_of_year / $days_total );
	}

	/**
	 * Genera los años en los que se muestra una etiqueta en la regla superior.
	 *
	 * @param int $start_year Año de inicio de la línea de tiempo.
	 * @param int $end_year   Año actual (fin de la línea de tiempo).
	 * @return int[]
	 */
	private static function get_year_ticks( $start_year, $end_year ) {
		$ticks = array( $start_year );

		for ( $year = (int) ( ceil( $start_year / 5 ) * 5 ); $year <= $end_year; $year += 5 ) {
			if ( $year > $start_year ) {
				$ticks[] = $year;
			}
		}

		if ( end( $ticks ) !== $end_year ) {
			$ticks[] = $end_year;
		}

		return $ticks;
	}

	/**
	 * Año "fraccionario" de una fecha completa (o de un año a secas si no
	 * hay fecha completa), para poder posicionar marcadores en el eje X.
	 *
	 * @param string|null $date Fecha en formato Y-m-d, o null.
	 * @param int|null    $year Año de respaldo si no hay fecha completa.
	 * @return float|null
	 */
	private static function year_fraction( $date, $year ) {
		if ( ! empty( $date ) ) {
			$time = strtotime( $date );
			if ( $time ) {
				$day_of_year = (int) date_i18n( 'z', $time );
				$days_total  = ( '1' === date_i18n( 'L', $time ) ) ? 366 : 365;
				return (int) date_i18n( 'Y', $time ) + ( $day_of_year / $days_total );
			}
		}

		return $year ? (float) $year : null;
	}

	/**
	 * HTML de los distintivos (badges) de los destinos adicionales de una
	 * sonda (además del destino principal), para pintar junto a su nombre.
	 *
	 * @param array $probe        Datos de la sonda (incluye 'waypoints').
	 * @param array $destinations Catálogo de destinos.
	 * @return string
	 */
	private static function build_waypoint_badges( $probe, $destinations ) {
		if ( empty( $probe['waypoints'] ) ) {
			return '';
		}

		$html = '';
		foreach ( $probe['waypoints'] as $waypoint ) {
			$destination = $destinations[ $waypoint['destination'] ] ?? array(
				'label' => $waypoint['destination'],
				'code'  => '?',
				'color' => '#888888',
			);
			$title = $destination['label'];
			if ( ! empty( $waypoint['date'] ) || ! empty( $waypoint['year'] ) ) {
				$title .= ': ' . SET_Data_Store::format_date( $waypoint['date'], $waypoint['year'] );
			}
			$html .= '<span class="set-badge set-badge-mini" style="--set-badge-color: ' . esc_attr( $destination['color'] ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $destination['code'] ) . '</span>';
		}

		return $html;
	}

	/**
	 * Construye el texto del tooltip de una sonda.
	 *
	 * @param array $probe        Datos de la sonda.
	 * @param array $destinations Catálogo de destinos.
	 * @return string
	 */
	private static function build_tooltip( $probe, $destinations ) {
		$destination_label = $destinations[ $probe['destination'] ]['label'] ?? $probe['destination'];
		$status_labels     = SET_Data_Store::get_status_labels();

		$lines   = array();
		$lines[] = trim( $probe['name'] . ( ! empty( $probe['agency'] ) ? ' — ' . $probe['agency'] : '' ) );
		$lines[] = sprintf(
			/* translators: %s: destino de la misión. */
			__( 'Destino: %s', 'sondas-espaciales-timeline' ),
			$destination_label
		);

		if ( $probe['end_year'] ) {
			$lines[] = sprintf(
				/* translators: 1: año de lanzamiento, 2: año de fin. */
				__( 'Lanzamiento: %1$d — Fin: %2$d', 'sondas-espaciales-timeline' ),
				$probe['launch_year'],
				$probe['end_year']
			);
		} else {
			$lines[] = sprintf(
				/* translators: %d: año de lanzamiento. */
				__( 'Lanzamiento: %d — en curso', 'sondas-espaciales-timeline' ),
				$probe['launch_year']
			);
		}

		$lines[] = $status_labels[ $probe['status'] ] ?? $probe['status'];

		if ( ! empty( $probe['note'] ) ) {
			$lines[] = $probe['note'];
		}

		return implode( "\n", $lines );
	}

	/**
	 * Genera el HTML del shortcode.
	 *
	 * @return string
	 */
	public static function render() {
		$probes         = SET_Data_Store::get_probes();
		$destinations   = SET_Data_Store::get_destinations();
		$status_labels  = SET_Data_Store::get_status_labels();
		$start_year     = SET_TIMELINE_START_YEAR;
		$today_frac     = self::get_today_fraction();
		$end_year       = (int) ceil( $today_frac );
		$year_ticks     = self::get_year_ticks( $start_year, $end_year );
		$search_id      = wp_unique_id( 'set-search-' );
		$destination_id = wp_unique_id( 'set-destination-' );
		$active_only_id = wp_unique_id( 'set-active-only-' );

		ob_start();
		?>
		<div class="set-wrapper" data-view="grafico" style="--set-start-year: <?php echo (int) $start_year; ?>; --set-end-year: <?php echo (int) $end_year; ?>; --set-today: <?php echo esc_attr( $today_frac ); ?>;">

			<?php if ( empty( $probes ) ) : ?>
				<p class="set-notice">
					<?php esc_html_e( 'Todavía no hay sondas registradas.', 'sondas-espaciales-timeline' ); ?>
				</p>
			<?php else : ?>

				<div class="set-view-toggle" role="group" aria-label="<?php esc_attr_e( 'Tipo de vista', 'sondas-espaciales-timeline' ); ?>">
					<button type="button" class="set-view-btn is-active" data-view="grafico" aria-pressed="true"><?php esc_html_e( 'Gráfico', 'sondas-espaciales-timeline' ); ?></button>
					<button type="button" class="set-view-btn" data-view="listado" aria-pressed="false"><?php esc_html_e( 'Listado', 'sondas-espaciales-timeline' ); ?></button>
				</div>

				<div class="set-controls">
					<div class="set-field">
						<label for="<?php echo esc_attr( $search_id ); ?>"><?php esc_html_e( 'Buscar', 'sondas-espaciales-timeline' ); ?></label>
						<input type="search" id="<?php echo esc_attr( $search_id ); ?>" class="set-search-input" placeholder="<?php esc_attr_e( 'Nombre, agencia…', 'sondas-espaciales-timeline' ); ?>" autocomplete="off" />
					</div>
					<div class="set-field">
						<label for="<?php echo esc_attr( $destination_id ); ?>"><?php esc_html_e( 'Destino', 'sondas-espaciales-timeline' ); ?></label>
						<select id="<?php echo esc_attr( $destination_id ); ?>" class="set-destination-select">
							<option value=""><?php esc_html_e( 'Todos', 'sondas-espaciales-timeline' ); ?></option>
							<?php foreach ( $destinations as $key => $destination ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $destination['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="set-field set-field-checkbox">
						<label for="<?php echo esc_attr( $active_only_id ); ?>">
							<input type="checkbox" id="<?php echo esc_attr( $active_only_id ); ?>" class="set-active-only" />
							<?php esc_html_e( 'Solo activas', 'sondas-espaciales-timeline' ); ?>
						</label>
					</div>
					<div class="set-field set-zoom">
						<span><?php esc_html_e( 'Zoom', 'sondas-espaciales-timeline' ); ?></span>
						<button type="button" class="set-zoom-out" aria-label="<?php esc_attr_e( 'Alejar', 'sondas-espaciales-timeline' ); ?>">&minus;</button>
						<button type="button" class="set-zoom-in" aria-label="<?php esc_attr_e( 'Acercar', 'sondas-espaciales-timeline' ); ?>">&plus;</button>
					</div>
				</div>

				<div class="set-legend">
					<?php foreach ( $destinations as $key => $destination ) : ?>
						<button type="button" class="set-legend-item" data-destination="<?php echo esc_attr( $key ); ?>">
							<span class="set-badge" style="--set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>"><?php echo esc_html( $destination['code'] ); ?></span>
							<?php echo esc_html( $destination['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<p class="set-count-info">
					<span class="set-count-visible"><?php echo (int) count( $probes ); ?></span>
					/
					<span class="set-count-total"><?php echo (int) count( $probes ); ?></span>
					<?php esc_html_e( 'sondas', 'sondas-espaciales-timeline' ); ?>
				</p>

				<div class="set-scroll">
					<div class="set-content">

						<div class="set-header">
							<div class="set-header-name"><?php esc_html_e( 'Sonda', 'sondas-espaciales-timeline' ); ?></div>
							<div class="set-header-years">
								<?php foreach ( $year_ticks as $year ) : ?>
									<span class="set-year-label" style="left: calc((<?php echo (int) $year; ?> - var(--set-start-year)) * var(--set-year-width));"><?php echo (int) $year; ?></span>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="set-rows">
							<?php foreach ( $probes as $probe ) :
								$destination = $destinations[ $probe['destination'] ] ?? array(
									'label' => $probe['destination'],
									'code'  => '?',
									'color' => '#888888',
								);
								$end_for_bar = $probe['end_year'] ? $probe['end_year'] : $today_frac;
								$search_haystack = mb_strtolower(
									implode(
										' ',
										array(
											$probe['name'],
											$probe['agency'],
											$destination['label'],
											(string) $probe['launch_year'],
										)
									)
								);
								?>
								<div class="set-row" data-destination="<?php echo esc_attr( $probe['destination'] ); ?>" data-status="<?php echo esc_attr( $probe['status'] ); ?>" data-search="<?php echo esc_attr( $search_haystack ); ?>">
									<div class="set-row-name">
										<span class="set-badge" style="--set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>" title="<?php echo esc_attr( $destination['label'] ); ?>"><?php echo esc_html( $destination['code'] ); ?></span>
										<?php echo self::build_waypoint_badges( $probe, $destinations ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span class="set-probe-name"><?php echo esc_html( $probe['name'] ); ?></span>
									</div>
									<div class="set-row-track">
										<span
											class="set-bar set-status-<?php echo esc_attr( $probe['status'] ); ?>"
											style="--set-launch: <?php echo (int) $probe['launch_year']; ?>; --set-end: <?php echo esc_attr( $end_for_bar ); ?>; --set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>;"
											title="<?php echo esc_attr( self::build_tooltip( $probe, $destinations ) ); ?>"
										></span>
										<?php foreach ( $probe['waypoints'] as $waypoint ) :
											$wp_year_frac = self::year_fraction( $waypoint['date'], $waypoint['year'] );
											if ( null === $wp_year_frac ) {
												continue;
											}
											$wp_destination = $destinations[ $waypoint['destination'] ] ?? array(
												'label' => $waypoint['destination'],
												'code'  => '?',
												'color' => '#888888',
											);
											?>
											<span
												class="set-waypoint-marker"
												style="left: calc((<?php echo esc_attr( $wp_year_frac ); ?> - var(--set-start-year)) * var(--set-year-width)); --set-badge-color: <?php echo esc_attr( $wp_destination['color'] ); ?>;"
												title="<?php echo esc_attr( $wp_destination['label'] . ': ' . SET_Data_Store::format_date( $waypoint['date'], $waypoint['year'] ) ); ?>"
											></span>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="set-today-line" style="left: calc(var(--set-name-width) + (var(--set-today) - var(--set-start-year)) * var(--set-year-width));" title="<?php esc_attr_e( 'Hoy', 'sondas-espaciales-timeline' ); ?>"></div>

					</div>
				</div>

				<div class="set-table-wrapper">
					<table class="set-table">
						<thead>
							<tr>
								<th></th>
								<th data-key="name" data-type="string"><?php esc_html_e( 'Nombre', 'sondas-espaciales-timeline' ); ?></th>
								<th data-key="agency" data-type="string"><?php esc_html_e( 'Agencia', 'sondas-espaciales-timeline' ); ?></th>
								<th data-key="destinationLabel" data-type="string"><?php esc_html_e( 'Destino', 'sondas-espaciales-timeline' ); ?></th>
								<th><?php esc_html_e( 'Ruta', 'sondas-espaciales-timeline' ); ?></th>
								<th data-key="launchYear" data-type="number"><?php esc_html_e( 'Lanzamiento', 'sondas-espaciales-timeline' ); ?></th>
								<th data-key="endYear" data-type="number"><?php esc_html_e( 'Fin', 'sondas-espaciales-timeline' ); ?></th>
								<th data-key="statusLabel" data-type="string"><?php esc_html_e( 'Estado', 'sondas-espaciales-timeline' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $probes as $probe ) :
								$destination = $destinations[ $probe['destination'] ] ?? array(
									'label' => $probe['destination'],
									'code'  => '?',
									'color' => '#888888',
								);
								$search_haystack = mb_strtolower(
									implode(
										' ',
										array(
											$probe['name'],
											$probe['agency'],
											$destination['label'],
											(string) $probe['launch_year'],
										)
									)
								);
								$status_label = $status_labels[ $probe['status'] ] ?? $probe['status'];
								?>
								<tr
									class="set-table-row"
									data-destination="<?php echo esc_attr( $probe['destination'] ); ?>"
									data-status="<?php echo esc_attr( $probe['status'] ); ?>"
									data-search="<?php echo esc_attr( $search_haystack ); ?>"
									data-name="<?php echo esc_attr( mb_strtolower( $probe['name'] ) ); ?>"
									data-agency="<?php echo esc_attr( mb_strtolower( $probe['agency'] ) ); ?>"
									data-destination-label="<?php echo esc_attr( mb_strtolower( $destination['label'] ) ); ?>"
									data-launch-year="<?php echo (int) $probe['launch_year']; ?>"
									data-end-year="<?php echo $probe['end_year'] ? (int) $probe['end_year'] : ''; ?>"
									data-status-label="<?php echo esc_attr( mb_strtolower( $status_label ) ); ?>"
								>
									<td><span class="set-badge" style="--set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>" title="<?php echo esc_attr( $destination['label'] ); ?>"><?php echo esc_html( $destination['code'] ); ?></span></td>
									<td><?php echo esc_html( $probe['name'] ); ?></td>
									<td><?php echo esc_html( $probe['agency'] ); ?></td>
									<td><?php echo esc_html( $destination['label'] ); ?></td>
									<td>
										<?php if ( empty( $probe['waypoints'] ) ) : ?>
											&#8212;
										<?php else : ?>
											<?php echo self::build_waypoint_badges( $probe, $destinations ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( SET_Data_Store::format_date( $probe['launch_date'] ) ); ?></td>
									<td><?php echo esc_html( SET_Data_Store::format_date( $probe['end_date'] ) ); ?></td>
									<td><span class="set-status-pill set-status-<?php echo esc_attr( $probe['status'] ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}
}
