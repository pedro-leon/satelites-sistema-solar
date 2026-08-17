<?php
/**
 * Acciones ejecutadas al desactivar el plugin.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Deactivator {

	/**
	 * Cancela la tarea programada. Los datos guardados se conservan.
	 */
	public static function deactivate() {
		require_once SSS_PLUGIN_DIR . 'includes/class-sss-cron.php';

		SSS_Cron::unschedule();
	}
}
