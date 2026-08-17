<?php
/**
 * Cliente para la API pública "Le Système Solaire" (api.le-systeme-solaire.net).
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Api_Client {

	/**
	 * Obtiene los 8 planetas del sistema solar, ordenados por distancia al Sol.
	 *
	 * @return array|WP_Error
	 */
	public static function get_planets() {
		$response = self::request(
			array(
				'filter[]' => 'bodyType,eq,Planet',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		usort(
			$response,
			static function ( $a, $b ) {
				return ( $a['semiMajorAxis'] ?? 0 ) <=> ( $b['semiMajorAxis'] ?? 0 );
			}
		);

		$planets = array();
		foreach ( $response as $planet ) {
			if ( empty( $planet['id'] ) ) {
				continue;
			}
			$planets[] = array(
				'id'   => $planet['id'],
				'name' => ! empty( $planet['englishName'] ) ? $planet['englishName'] : $planet['name'],
			);
		}

		return $planets;
	}

	/**
	 * Obtiene todos los satélites (lunas) del sistema solar.
	 *
	 * @return array|WP_Error
	 */
	public static function get_moons() {
		return self::request(
			array(
				'filter[]' => 'bodyType,eq,Moon',
			)
		);
	}

	/**
	 * Realiza una petición GET a la API y devuelve el array de "bodies".
	 *
	 * @param array $query Parámetros de consulta.
	 * @return array|WP_Error
	 */
	private static function request( $query ) {
		$url = add_query_arg( $query, SSS_API_BASE );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'sss_api_http_error',
				sprintf(
					/* translators: %d: código de respuesta HTTP. */
					__( 'La API de satélites respondió con el código %d.', 'satelites-sistema-solar' ),
					$code
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['bodies'] ) || ! is_array( $body['bodies'] ) ) {
			return new WP_Error(
				'sss_api_invalid_response',
				__( 'La API de satélites devolvió una respuesta inesperada.', 'satelites-sistema-solar' )
			);
		}

		return $body['bodies'];
	}
}
