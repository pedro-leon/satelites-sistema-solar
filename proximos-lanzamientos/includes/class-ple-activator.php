<?php
/**
 * Acciones ejecutadas al activar el plugin.
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Activator {

	/**
	 * Programa la tarea de refresco y lanza una primera descarga de datos.
	 */
	public static function activate() {
		require_once PLE_PLUGIN_DIR . 'includes/class-ple-cron.php';
		require_once PLE_PLUGIN_DIR . 'includes/class-ple-api-client.php';
		require_once PLE_PLUGIN_DIR . 'includes/class-ple-data-store.php';

		PLE_Cron::schedule();

		// Primera carga de datos para que el shortcode tenga contenido desde el minuto uno.
		if ( false === get_option( PLE_OPTION_LAST_UPDATED, false ) ) {
			PLE_Data_Store::refresh_data();
		}
	}
}
