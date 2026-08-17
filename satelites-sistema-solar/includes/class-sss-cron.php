<?php
/**
 * Gestiona la tarea programada diaria que actualiza los datos.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Cron {

	/**
	 * Engancha el manejador del evento cron.
	 */
	public static function init() {
		add_action( SSS_CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	/**
	 * Ejecuta la actualización de datos programada.
	 */
	public static function run() {
		SSS_Data_Store::refresh_data();
	}

	/**
	 * Programa el evento diario si aún no lo está.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( SSS_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', SSS_CRON_HOOK );
		}
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
