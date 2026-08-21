<?php
/**
 * Gestiona la tarea programada que refresca la caché de lanzamientos.
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Cron {

	/**
	 * Engancha el manejador del evento cron y registra las periodicidades.
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'register_schedules' ) );
		add_action( PLE_CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	/**
	 * Añade periodicidades de 15/30/60 minutos a WP-Cron (no vienen de serie).
	 *
	 * @param array $schedules Periodicidades registradas.
	 * @return array
	 */
	public static function register_schedules( $schedules ) {
		foreach ( PLE_Data_Store::ALLOWED_REFRESH_MINUTES as $minutes ) {
			$key = self::schedule_key( $minutes );
			if ( ! isset( $schedules[ $key ] ) ) {
				$schedules[ $key ] = array(
					'interval' => $minutes * MINUTE_IN_SECONDS,
					/* translators: %d: minutos. */
					'display'  => sprintf( __( 'Cada %d minutos', 'proximos-lanzamientos' ), $minutes ),
				);
			}
		}

		return $schedules;
	}

	/**
	 * Ejecuta la actualización de datos programada.
	 */
	public static function run() {
		PLE_Data_Store::refresh_data();
	}

	/**
	 * Nombre de la periodicidad de WP-Cron para un número de minutos dado.
	 *
	 * @param int $minutes Minutos entre ejecuciones.
	 * @return string
	 */
	private static function schedule_key( $minutes ) {
		return 'ple_every_' . $minutes . '_minutes';
	}

	/**
	 * Programa el evento si aún no lo está, o lo reprograma si el intervalo
	 * guardado ha cambiado (p. ej. tras editar el ajuste en el panel).
	 */
	public static function schedule() {
		// La activación (y el guardado de ajustes) puede ejecutarse antes de
		// que 'plugins_loaded' llame a init(), así que hay que registrar las
		// periodicidades también aquí.
		add_filter( 'cron_schedules', array( __CLASS__, 'register_schedules' ) );

		$target = self::schedule_key( PLE_Data_Store::get_refresh_minutes() );
		$next   = wp_next_scheduled( PLE_CRON_HOOK );

		if ( $next && $target === wp_get_schedule( PLE_CRON_HOOK ) ) {
			return;
		}

		if ( $next ) {
			self::unschedule();
		}

		wp_schedule_event( time(), $target, PLE_CRON_HOOK );
	}

	/**
	 * Cancela el evento programado.
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( PLE_CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, PLE_CRON_HOOK );
		}
		wp_clear_scheduled_hook( PLE_CRON_HOOK );
	}
}
