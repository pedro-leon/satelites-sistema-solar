<?php
/**
 * Acceso a los datos estáticos de destinos y sondas.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Data_Store {

	/**
	 * Devuelve el catálogo de destinos (id => label/code/color).
	 *
	 * @return array
	 */
	public static function get_destinations() {
		static $destinations = null;

		if ( null === $destinations ) {
			$destinations = require SET_PLUGIN_DIR . 'includes/data/destinations.php';
		}

		return $destinations;
	}

	/**
	 * Devuelve el listado de sondas, ordenado por año de lanzamiento.
	 *
	 * @return array
	 */
	public static function get_probes() {
		static $probes = null;

		if ( null === $probes ) {
			$probes = require SET_PLUGIN_DIR . 'includes/data/probes.php';

			usort(
				$probes,
				static function ( $a, $b ) {
					return $a['launch_year'] <=> $b['launch_year'];
				}
			);
		}

		return $probes;
	}
}
