<?php
/**
 * Gestiona la tarea programada semanal que actualiza los datos.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Cron {

	const SCHEDULE = 'sss_weekly';

	/**
	 * Engancha el manejador del evento cron y registra la periodicidad semanal.
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'register_schedule' ) );
		add_action( SSS_CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	/**
	 * Añade una periodicidad "semanal" a WP-Cron (WordPress no trae una por defecto).
	 *
	 * @param array $schedules Periodicidades registradas.
	 * @return array
	 */
	public static function register_schedule( $schedules ) {
		if ( ! isset( $schedules[ self::SCHEDULE ] ) ) {
			$schedules[ self::SCHEDULE ] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Una vez a la semana', 'satelites-sistema-solar' ),
			);
		}

		return $schedules;
	}

	/**
	 * Ejecuta la actualización de datos programada.
	 */
	public static function run() {
		SSS_Data_Store::refresh_data();
	}

	/**
	 * Programa el evento semanal si aún no lo está (o si la periodicidad guardada
	 * ya no coincide, p. ej. tras cambiar de "daily" a "sss_weekly").
	 */
	public static function schedule() {
		// La activación se ejecuta antes de que 'plugins_loaded' llame a init(),
		// así que hay que registrar la periodicidad también aquí.
		add_filter( 'cron_schedules', array( __CLASS__, 'register_schedule' ) );

		$next = wp_next_scheduled( SSS_CRON_HOOK );
		if ( $next && self::SCHEDULE === wp_get_schedule( SSS_CRON_HOOK ) ) {
			return;
		}

		if ( $next ) {
			self::unschedule();
		}

		wp_schedule_event( time(), self::SCHEDULE, SSS_CRON_HOOK );
	}

	/**
	 * Cancela el evento programado.
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( SSS_CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, SSS_CRON_HOOK );
		}
		wp_clear_scheduled_hook( SSS_CRON_HOOK );
	}
}
