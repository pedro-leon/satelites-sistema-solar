<?php
/**
 * Acciones ejecutadas al activar el plugin.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Activator {

	/**
	 * Programa la tarea semanal y lanza una primera descarga de datos.
	 */
	public static function activate() {
		require_once SSS_PLUGIN_DIR . 'includes/class-sss-cron.php';
		require_once SSS_PLUGIN_DIR . 'includes/class-sss-api-client.php';
		require_once SSS_PLUGIN_DIR . 'includes/class-sss-data-store.php';

		SSS_Cron::schedule();

		// Primera carga de datos para que el shortcode tenga contenido desde el minuto uno.
		if ( false === get_option( SSS_OPTION_LAST_UPDATED, false ) ) {
			SSS_Data_Store::refresh_data();
		}
	}
}
