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
			$name = ! empty( $planet['englishName'] ) ? $planet['englishName'] : $planet['name'];

			$planets[] = array(
				'id'   => $planet['id'],
				'name' => SSS_I18n_Names::translate_planet( $name ),
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
		$api_key = trim( (string) get_option( SSS_OPTION_API_KEY, '' ) );

		if ( '' === $api_key ) {
			return new WP_Error(
				'sss_api_missing_key',
				sprintf(
					/* translators: %s: URL para generar una clave de API. */
					__( 'Falta configurar la clave de API. Genera una gratis en %s y guárdala en la página "Satélites SS" del panel de administración.', 'satelites-sistema-solar' ),
					SSS_API_KEY_URL
				)
			);
		}

		$url = add_query_arg( $query, SSS_API_BASE );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$snippet = self::body_snippet( wp_remote_retrieve_body( $response ) );

			return new WP_Error(
				'sss_api_http_error',
				sprintf(
					/* translators: 1: código de respuesta HTTP, 2: fragmento de la respuesta de la API (puede estar vacío). */
					__( 'La API de satélites respondió con el código %1$d.%2$s', 'satelites-sistema-solar' ),
					$code,
					'' !== $snippet ? ' ' . sprintf( /* translators: %s: fragmento de la respuesta. */ __( 'Respuesta: %s', 'satelites-sistema-solar' ), $snippet ) : ''
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

	/**
	 * Extrae un fragmento corto y legible del cuerpo de una respuesta HTTP,
	 * para poder diagnosticar errores (p. ej. cuando la API devuelve un
	 * código distinto de 401/403/500 con un mensaje explicativo, o cuando
	 * un proxy/CDN intermedio devuelve una página de error en vez de la API).
	 *
	 * @param string $body Cuerpo crudo de la respuesta.
	 * @return string
	 */
	private static function body_snippet( $body ) {
		$text = trim( wp_strip_all_tags( (string) $body ) );
		$text = preg_replace( '/\s+/', ' ', $text );

		if ( '' === $text ) {
			return '';
		}

		return mb_strimwidth( $text, 0, 200, '…' );
	}
}
