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
	 * Construye el texto del tooltip de una sonda.
	 *
	 * @param array $probe        Datos de la sonda.
	 * @param array $destinations Catálogo de destinos.
	 * @return string
	 */
	private static function build_tooltip( $probe, $destinations ) {
		$destination_label = $destinations[ $probe['destination'] ]['label'] ?? $probe['destination'];

		$status_labels = array(
			'activa'      => __( 'Misión activa', 'sondas-espaciales-timeline' ),
			'finalizada'  => __( 'Misión finalizada', 'sondas-espaciales-timeline' ),
			'perdida'     => __( 'Contacto perdido', 'sondas-espaciales-timeline' ),
			'fallida'     => __( 'Misión fallida', 'sondas-espaciales-timeline' ),
		);

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
		$probes        = SET_Data_Store::get_probes();
		$destinations  = SET_Data_Store::get_destinations();
		$start_year    = SET_TIMELINE_START_YEAR;
		$today_frac    = self::get_today_fraction();
		$end_year      = (int) ceil( $today_frac );
		$year_ticks    = self::get_year_ticks( $start_year, $end_year );
		$search_id     = wp_unique_id( 'set-search-' );
		$destination_id = wp_unique_id( 'set-destination-' );

		ob_start();
		?>
		<div class="set-wrapper" style="--set-start-year: <?php echo (int) $start_year; ?>; --set-end-year: <?php echo (int) $end_year; ?>; --set-today: <?php echo esc_attr( $today_frac ); ?>;">

			<?php if ( empty( $probes ) ) : ?>
				<p class="set-notice">
					<?php esc_html_e( 'Todavía no hay sondas registradas.', 'sondas-espaciales-timeline' ); ?>
				</p>
			<?php else : ?>

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
								<div class="set-row" data-destination="<?php echo esc_attr( $probe['destination'] ); ?>" data-search="<?php echo esc_attr( $search_haystack ); ?>">
									<div class="set-row-name">
										<span class="set-badge" style="--set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>" title="<?php echo esc_attr( $destination['label'] ); ?>"><?php echo esc_html( $destination['code'] ); ?></span>
										<span class="set-probe-name"><?php echo esc_html( $probe['name'] ); ?></span>
									</div>
									<div class="set-row-track">
										<span
											class="set-bar set-status-<?php echo esc_attr( $probe['status'] ); ?>"
											style="--set-launch: <?php echo (int) $probe['launch_year']; ?>; --set-end: <?php echo esc_attr( $end_for_bar ); ?>; --set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>;"
											title="<?php echo esc_attr( self::build_tooltip( $probe, $destinations ) ); ?>"
										></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="set-today-line" style="left: calc(var(--set-name-width) + (var(--set-today) - var(--set-start-year)) * var(--set-year-width));" title="<?php esc_attr_e( 'Hoy', 'sondas-espaciales-timeline' ); ?>"></div>

					</div>
				</div>

			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}
}
