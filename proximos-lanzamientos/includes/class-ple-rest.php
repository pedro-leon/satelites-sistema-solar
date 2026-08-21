<?php
/**
 * Endpoint REST de solo lectura que sirve la caché de lanzamientos.
 *
 * El front-end lo consulta en vez de llamar directamente a la API externa:
 * así todas las visitas comparten una única caché same-origin (sin CORS,
 * sin exponer la API de terceros al navegador de cada visitante, y sin
 * arriesgarse a agotar el límite de peticiones por hora de Launch Library 2).
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Rest {

	/**
	 * Registra la ruta REST.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Define GET /wp-json/ple/v1/launches.
	 */
	public static function register_routes() {
		register_rest_route(
			PLE_REST_NAMESPACE,
			'/launches',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_launches' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Devuelve los lanzamientos cacheados.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_launches() {
		$response = new WP_REST_Response(
			array(
				'launches'    => PLE_Data_Store::get_launches(),
				'lastUpdated' => PLE_Data_Store::get_last_updated(),
			),
			200
		);

		// Los datos solo cambian cuando corre el cron: evita revalidaciones innecesarias entre refrescos.
		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}
}
