<?php
/**
 * Shortcode público [proximos_lanzamientos].
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Shortcode {

	const TAG = 'proximos_lanzamientos';

	/**
	 * Registra el shortcode y los assets asociados.
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Registra (sin encolar) el CSS/JS del frontend y localiza su configuración.
	 */
	public static function register_assets() {
		wp_register_style(
			'ple-launches-style',
			PLE_PLUGIN_URL . 'assets/css/proximos-lanzamientos.css',
			array(),
			PLE_VERSION
		);

		wp_register_script(
			'ple-launches-script',
			PLE_PLUGIN_URL . 'assets/js/proximos-lanzamientos.js',
			array(),
			PLE_VERSION,
			true
		);

		wp_localize_script(
			'ple-launches-script',
			'pleLaunchesConfig',
			array(
				'apiUrl'           => rest_url( PLE_REST_NAMESPACE . '/launches' ),
				'refreshInterval'  => PLE_Data_Store::get_refresh_minutes() * MINUTE_IN_SECONDS * 1000,
				'statusLoading'    => __( 'Cargando próximos lanzamientos...', 'proximos-lanzamientos' ),
				'statusUpdated'    => __( 'Actualizado:', 'proximos-lanzamientos' ),
				'localTimeNotice'  => __( 'Todas las horas se muestran en tu hora local.', 'proximos-lanzamientos' ),
				'statusError'      => __( 'No se pudieron cargar los lanzamientos. Revisa la conexión o inténtalo más tarde.', 'proximos-lanzamientos' ),
				'emptyMessage'     => __( 'No hay lanzamientos próximos disponibles ahora mismo.', 'proximos-lanzamientos' ),
				'fallbackText'     => __( 'Pendiente de confirmar', 'proximos-lanzamientos' ),
				'videoLabel'       => __( 'Video', 'proximos-lanzamientos' ),
				'webLabel'         => __( 'Web', 'proximos-lanzamientos' ),
				'officialWebLabel' => __( 'Web oficial', 'proximos-lanzamientos' ),
				'dateLabel'        => __( 'Fecha y hora', 'proximos-lanzamientos' ),
				'agencyLabel'      => __( 'Agencia', 'proximos-lanzamientos' ),
				'rocketLabel'      => __( 'Cohete', 'proximos-lanzamientos' ),
				'padLabel'         => __( 'Plataforma', 'proximos-lanzamientos' ),
				'pendingDate'      => __( 'Fecha pendiente', 'proximos-lanzamientos' ),
				'pendingLocation'  => __( 'Ubicación pendiente', 'proximos-lanzamientos' ),
				'missionFallback'  => __( 'La misión todavía no tiene descripción pública.', 'proximos-lanzamientos' ),
			)
		);
	}

	/**
	 * Genera el HTML del shortcode, con las tarjetas ya renderizadas desde la
	 * caché del servidor (para que el contenido exista sin depender de JS,
	 * p. ej. para buscadores) y el JS asumiendo el refresco periódico después.
	 *
	 * @return string
	 */
	public static function render() {
		wp_enqueue_style( 'ple-launches-style' );
		wp_enqueue_script( 'ple-launches-script' );

		$launches = PLE_Data_Store::get_launches();

		ob_start();
		?>
		<div class="ple-launches-widget" data-ple-launches>
			<section class="ple-topbar" aria-labelledby="ple-page-title">
				<div>
					<p class="ple-eyebrow"><?php esc_html_e( 'Agenda espacial en directo', 'proximos-lanzamientos' ); ?></p>
					<h2 id="ple-page-title" class="ple-title"><?php esc_html_e( 'Próximos lanzamientos de cohetes', 'proximos-lanzamientos' ); ?></h2>
				</div>
				<div class="ple-summary" aria-label="<?php esc_attr_e( 'Resumen', 'proximos-lanzamientos' ); ?>">
					<div class="ple-stat">
						<strong data-ple-launch-count><?php echo esc_html( count( $launches ) ); ?></strong>
						<span><?php esc_html_e( 'lanzamientos cargados', 'proximos-lanzamientos' ); ?></span>
					</div>
					<div class="ple-stat">
						<strong data-ple-next-countdown>--</strong>
						<span><?php esc_html_e( 'hasta el próximo lanzamiento', 'proximos-lanzamientos' ); ?></span>
					</div>
				</div>
			</section>

			<section class="ple-controls" aria-label="<?php esc_attr_e( 'Controles', 'proximos-lanzamientos' ); ?>">
				<p class="ple-time-notice"><?php esc_html_e( 'Todas las horas se muestran en tu hora local.', 'proximos-lanzamientos' ); ?></p>
				<p class="ple-status" data-ple-status role="status"><?php esc_html_e( 'Cargando próximos lanzamientos...', 'proximos-lanzamientos' ); ?></p>
			</section>

			<section class="ple-launch-grid" data-ple-launches-list aria-live="polite">
				<?php if ( empty( $launches ) ) : ?>
					<div class="ple-empty"><?php esc_html_e( 'No hay lanzamientos próximos disponibles ahora mismo.', 'proximos-lanzamientos' ); ?></div>
				<?php else : ?>
					<?php foreach ( $launches as $launch ) : ?>
						<?php self::render_card( $launch ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>

			<footer class="ple-page-footer">
				<p class="ple-source">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %s: enlace a Launch Library 2. */
							__( 'Datos de %s.', 'proximos-lanzamientos' ),
							'<a href="https://thespacedevs.com/llapi" target="_blank" rel="noreferrer noopener">Launch Library 2</a>'
						),
						array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) )
					);
					?>
				</p>
			</footer>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Renderiza una tarjeta de lanzamiento. Misma estructura y clases CSS que
	 * genera el JS al refrescar, para que no haya salto visual entre el
	 * render inicial del servidor y las actualizaciones del cliente.
	 *
	 * @param array $launch Lanzamiento normalizado (ver PLE_Data_Store).
	 */
	private static function render_card( $launch ) {
		$launch_date = ! empty( $launch['window_start'] ) ? strtotime( $launch['window_start'] ) : false;
		$status      = ! empty( $launch['status_abbrev'] ) ? $launch['status_abbrev'] : $launch['status_name'];
		?>
		<article class="ple-launch-card">
			<?php if ( ! empty( $launch['image'] ) ) : ?>
				<img class="ple-launch-image" src="<?php echo esc_url( $launch['image'] ); ?>" alt="" loading="lazy" decoding="async">
			<?php endif; ?>
			<div class="ple-mission">
				<h3 class="ple-mission-title"><?php echo esc_html( self::clean_text( $launch['name'] ) ); ?></h3>
				<span class="ple-badge <?php echo esc_attr( self::status_class( $status ) ); ?>">
					<?php echo esc_html( self::clean_text( $launch['status_abbrev'], 'TBD' ) ); ?>
				</span>
			</div>
			<dl class="ple-meta">
				<div class="ple-meta-row">
					<dt><?php esc_html_e( 'Fecha y hora', 'proximos-lanzamientos' ); ?></dt>
					<dd class="ple-date-list">
						<?php if ( $launch_date ) : ?>
							<time class="ple-date-line" datetime="<?php echo esc_attr( gmdate( 'c', $launch_date ) ); ?>"><?php echo esc_html( wp_date( 'l, j \d\e F \d\e Y, H:i', $launch_date ) ); ?></time>
							<time class="ple-date-line" datetime="<?php echo esc_attr( gmdate( 'c', $launch_date ) ); ?>"><?php echo esc_html( gmdate( 'l, j \d\e F \d\e Y, H:i', $launch_date ) ); ?> UTC</time>
						<?php else : ?>
							<?php esc_html_e( 'Fecha pendiente', 'proximos-lanzamientos' ); ?>
						<?php endif; ?>
					</dd>
				</div>
				<div class="ple-meta-row">
					<dt><?php esc_html_e( 'Agencia', 'proximos-lanzamientos' ); ?></dt>
					<dd><?php echo esc_html( self::clean_text( $launch['agency'] ) ); ?></dd>
				</div>
				<div class="ple-meta-row">
					<dt><?php esc_html_e( 'Cohete', 'proximos-lanzamientos' ); ?></dt>
					<dd><?php echo esc_html( self::clean_text( $launch['rocket'] ) ); ?></dd>
				</div>
				<div class="ple-meta-row">
					<dt><?php esc_html_e( 'Plataforma', 'proximos-lanzamientos' ); ?></dt>
					<dd><?php echo esc_html( self::clean_text( $launch['pad'] ) ); ?> · <?php echo esc_html( self::clean_text( $launch['location'], __( 'Ubicación pendiente', 'proximos-lanzamientos' ) ) ); ?></dd>
				</div>
			</dl>
			<p class="ple-description"><?php echo esc_html( self::clean_text( $launch['description'], __( 'La misión todavía no tiene descripción pública.', 'proximos-lanzamientos' ) ) ); ?></p>
			<?php if ( ! empty( $launch['links'] ) ) : ?>
				<div class="ple-launch-links" aria-label="<?php esc_attr_e( 'Enlaces del lanzamiento', 'proximos-lanzamientos' ); ?>">
					<?php foreach ( $launch['links'] as $link ) : ?>
						<?php
						$label = __( 'Web', 'proximos-lanzamientos' );
						if ( 'video' === $link['type'] ) {
							$label = __( 'Video', 'proximos-lanzamientos' );
						} elseif ( 'official' === $link['type'] ) {
							$label = __( 'Web oficial', 'proximos-lanzamientos' );
						}
						$class = 'video' === $link['type'] ? 'video' : 'web';
						?>
						<a class="ple-launch-link <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noreferrer noopener"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * Devuelve un valor si tiene contenido, o un texto de respaldo si no.
	 *
	 * @param string $value    Valor a comprobar.
	 * @param string $fallback Texto de respaldo.
	 * @return string
	 */
	private static function clean_text( $value, $fallback = '' ) {
		$value = trim( (string) $value );
		if ( '' !== $value ) {
			return $value;
		}
		return '' !== $fallback ? $fallback : __( 'Pendiente de confirmar', 'proximos-lanzamientos' );
	}

	/**
	 * Convierte un estado ("Go", "TBD"...) en una clase CSS en minúsculas.
	 *
	 * @param string $status Estado del lanzamiento.
	 * @return string
	 */
	private static function status_class( $status ) {
		$status = strtolower( self::clean_text( $status, 'tbd' ) );
		return preg_replace( '/[^a-z0-9]+/', '-', $status );
	}
}
