<?php
/**
 * Acciones ejecutadas al activar el plugin.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Activator {

	/**
	 * De momento no requiere ninguna acción especial: los datos son
	 * estáticos (includes/data/probes.php) y no se usan tablas ni cron.
	 */
	public static function activate() {}
}
