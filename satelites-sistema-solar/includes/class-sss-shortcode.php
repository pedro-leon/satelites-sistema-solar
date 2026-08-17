<?php
/**
 * Shortcode público [satelites_sistema_solar].
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Shortcode {

	const TAG = 'satelites_sistema_solar';

	/**
	 * Registra el shortcode y los assets asociados.
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Encola y localiza el CSS/JS del frontend.
	 *
	 * Se cargan en todas las páginas del front-end (y no solo condicionado a
	 * has_shortcode()) porque el shortcode puede insertarse desde widgets,
	 * constructores de páginas o plantillas donde no es fiable detectarlo
	 * de antemano, y ambos ficheros son ligeros.
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'sss-frontend',
			SSS_PLUGIN_URL . 'assets/css/satelites-sistema-solar.css',
			array(),
			SSS_VERSION
		);

		wp_enqueue_script(
			'sss-frontend',
			SSS_PLUGIN_URL . 'assets/js/satelites-sistema-solar.js',
			array(),
			SSS_VERSION,
			true
		);

		$planets     = SSS_Data_Store::get_planets();
		$moons       = SSS_Data_Store::get_moons_grouped_by_planet();
		$counts      = SSS_Data_Store::get_counts();
		$last_update = SSS_Data_Store::get_last_updated();

		wp_localize_script(
			'sss-frontend',
			'sssData',
			array(
				'planets'      => $planets,
				'moonsByPlanet' => $moons,
				'counts'       => $counts,
				'i18n'         => array(
					'selectPlaceholder' => __( 'Selecciona un planeta…', 'satelites-sistema-solar' ),
					'noMoons'           => __( 'Este planeta no tiene satélites conocidos.', 'satelites-sistema-solar' ),
					'unknown'           => __( '—', 'satelites-sistema-solar' ),
					'satellites'        => __( 'satélites', 'satelites-sistema-solar' ),
					'columns'           => array(
						'name'             => __( 'Nombre', 'satelites-sistema-solar' ),
						'provisional_name' => __( 'Nombre provisional', 'satelites-sistema-solar' ),
						'distance_km'      => __( 'Distancia al planeta (km)', 'satelites-sistema-solar' ),
						'diameter_km'      => __( 'Diámetro (km)', 'satelites-sistema-solar' ),
						'density'          => __( 'Densidad (g/cm³)', 'satelites-sistema-solar' ),
						'discovery_year'   => __( 'Año de descubrimiento', 'satelites-sistema-solar' ),
						'discoverer'       => __( 'Descubridor', 'satelites-sistema-solar' ),
					),
				),
			)
		);
	}

	/**
	 * Genera el HTML del shortcode.
	 *
	 * @return string
	 */
	public static function render() {
		$planets     = SSS_Data_Store::get_planets();
		$counts      = SSS_Data_Store::get_counts();
		$last_update = SSS_Data_Store::get_last_updated();
		$select_id   = wp_unique_id( 'sss-planet-select-' );

		ob_start();
		?>
		<div class="sss-wrapper">

			<?php if ( empty( $planets ) ) : ?>
				<p class="sss-notice">
					<?php esc_html_e( 'Todavía no hay datos de satélites disponibles. Vuelve a intentarlo en unos minutos.', 'satelites-sistema-solar' ); ?>
				</p>
			<?php else : ?>

				<div class="sss-summary">
					<h3 class="sss-summary-title"><?php esc_html_e( 'Satélites por planeta', 'satelites-sistema-solar' ); ?></h3>
					<ul class="sss-summary-grid">
						<?php foreach ( $planets as $planet ) : ?>
							<li class="sss-summary-item">
								<span class="sss-planet-name"><?php echo esc_html( $planet['name'] ); ?></span>
								<span class="sss-planet-count"><?php echo esc_html( $counts['by_planet'][ $planet['id'] ] ?? 0 ); ?></span>
							</li>
						<?php endforeach; ?>
						<li class="sss-summary-item sss-summary-total">
							<span class="sss-planet-name"><?php esc_html_e( 'Total', 'satelites-sistema-solar' ); ?></span>
							<span class="sss-planet-count"><?php echo esc_html( $counts['total'] ); ?></span>
						</li>
					</ul>
				</div>

				<div class="sss-explorer">
					<label class="sss-select-label" for="<?php echo esc_attr( $select_id ); ?>">
						<?php esc_html_e( 'Selecciona un planeta', 'satelites-sistema-solar' ); ?>
					</label>
					<select id="<?php echo esc_attr( $select_id ); ?>" class="sss-planet-select">
						<?php foreach ( $planets as $planet ) : ?>
							<option value="<?php echo esc_attr( $planet['id'] ); ?>">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: nombre del planeta, 2: número de satélites. */
										__( '%1$s (%2$d)', 'satelites-sistema-solar' ),
										$planet['name'],
										$counts['by_planet'][ $planet['id'] ] ?? 0
									)
								);
								?>
							</option>
						<?php endforeach; ?>
					</select>

					<div class="sss-table-wrapper">
						<table class="sss-table">
							<thead>
								<tr>
									<th data-key="name" data-type="string"><?php esc_html_e( 'Nombre', 'satelites-sistema-solar' ); ?></th>
									<th data-key="provisional_name" data-type="string"><?php esc_html_e( 'Nombre provisional', 'satelites-sistema-solar' ); ?></th>
									<th data-key="distance_km" data-type="number"><?php esc_html_e( 'Distancia al planeta (km)', 'satelites-sistema-solar' ); ?></th>
									<th data-key="diameter_km" data-type="number"><?php esc_html_e( 'Diámetro (km)', 'satelites-sistema-solar' ); ?></th>
									<th data-key="density" data-type="number"><?php esc_html_e( 'Densidad (g/cm³)', 'satelites-sistema-solar' ); ?></th>
									<th data-key="discovery_year" data-type="number"><?php esc_html_e( 'Año de descubrimiento', 'satelites-sistema-solar' ); ?></th>
									<th data-key="discoverer" data-type="string"><?php esc_html_e( 'Descubridor', 'satelites-sistema-solar' ); ?></th>
								</tr>
							</thead>
							<tbody class="sss-moons-tbody"></tbody>
						</table>
					</div>
					<p class="sss-empty-message" hidden></p>
				</div>

				<?php if ( $last_update ) : ?>
					<p class="sss-last-updated">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: fecha y hora de la última actualización. */
								__( 'Datos actualizados el %s.', 'satelites-sistema-solar' ),
								wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_update )
							)
						);
						?>
					</p>
				<?php endif; ?>

			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}
}
