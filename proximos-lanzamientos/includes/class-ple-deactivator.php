<?php
/**
 * Acciones ejecutadas al desactivar el plugin.
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Deactivator {

	/**
	 * Cancela la tarea programada. Los datos guardados se conservan.
	 */
	public static function deactivate() {
		require_once PLE_PLUGIN_DIR . 'includes/class-ple-cron.php';

		PLE_Cron::unschedule();
	}
}
