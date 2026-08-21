<?php
/**
 * Cliente para la API pública Launch Library 2 (thespacedevs.com).
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Api_Client {

	/**
	 * Obtiene los próximos lanzamientos, ordenados por fecha.
	 *
	 * @param int $limit Número máximo de lanzamientos a solicitar.
	 * @return array|WP_Error
	 */
	public static function get_upcoming_launches( $limit ) {
		$url = add_query_arg(
			array(
				'limit'    => max( 1, (int) $limit ),
				'ordering' => 'net',
				'format'   => 'json',
			),
			PLE_API_BASE
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$snippet = self::body_snippet( wp_remote_retrieve_body( $response ) );

			return new WP_Error(
				'ple_api_http_error',
				sprintf(
					/* translators: 1: código de respuesta HTTP, 2: fragmento de la respuesta de la API (puede estar vacío). */
					__( 'La API de lanzamientos respondió con el código %1$d.%2$s', 'proximos-lanzamientos' ),
					$code,
					'' !== $snippet ? ' ' . sprintf( /* translators: %s: fragmento de la respuesta. */ __( 'Respuesta: %s', 'proximos-lanzamientos' ), $snippet ) : ''
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['results'] ) || ! is_array( $body['results'] ) ) {
			return new WP_Error(
				'ple_api_invalid_response',
				__( 'La API de lanzamientos devolvió una respuesta inesperada.', 'proximos-lanzamientos' )
			);
		}

		return $body['results'];
	}

	/**
	 * Extrae un fragmento corto y legible del cuerpo de una respuesta HTTP,
	 * para poder diagnosticar errores desde el panel de administración.
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
