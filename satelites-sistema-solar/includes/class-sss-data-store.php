<?php
/**
 * Normaliza, almacena y expone los datos de planetas y satélites.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Data_Store {

	/**
	 * Designaciones provisionales de objetos vistos una sola vez cerca del
	 * anillo F de Saturno que nunca se han podido confirmar como satélites
	 * reales (probablemente cúmulos transitorios de polvo, no cuerpos
	 * sólidos). La API los sigue incluyendo, pero fuentes que depuran su
	 * lista activamente (como la del JPL) ya no los cuentan, así que aquí
	 * se excluyen también aunque la API no los haya retirado.
	 *
	 * @var string[]
	 */
	private static $excluded_designations = array(
		'S/2004 S 3',
		'S/2004 S 4',
		'S/2004 S 6',
	);

	/**
	 * Descarga los datos de la API, los normaliza y los guarda en la base de datos.
	 *
	 * @return true|WP_Error
	 */
	public static function refresh_data() {
		$planets = SSS_Api_Client::get_planets();
		if ( is_wp_error( $planets ) ) {
			update_option( SSS_OPTION_LAST_ERROR, $planets->get_error_message() );
			return $planets;
		}

		$moons = SSS_Api_Client::get_moons();
		if ( is_wp_error( $moons ) ) {
			update_option( SSS_OPTION_LAST_ERROR, $moons->get_error_message() );
			return $moons;
		}

		$planet_ids = wp_list_pluck( $planets, 'name', 'id' );

		$normalized_moons = array();
		foreach ( $moons as $moon ) {
			$planet_id = $moon['aroundPlanet']['planet'] ?? '';
			if ( '' === $planet_id || ! isset( $planet_ids[ $planet_id ] ) ) {
				continue; // Solo nos interesan satélites de los 8 planetas.
			}

			if ( in_array( trim( $moon['alternativeName'] ?? '' ), self::$excluded_designations, true ) ) {
				continue;
			}

			$normalized_moons[] = self::normalize_moon( $moon, $planet_id );
		}

		update_option( SSS_OPTION_PLANETS, $planets, false );
		update_option( SSS_OPTION_MOONS, $normalized_moons, false );
		update_option( SSS_OPTION_LAST_UPDATED, time(), false );
		delete_option( SSS_OPTION_LAST_ERROR );

		return true;
	}

	/**
	 * Convierte el objeto "body" crudo de la API en la estructura que usa el plugin.
	 *
	 * @param array  $moon      Cuerpo devuelto por la API.
	 * @param string $planet_id Id del planeta alrededor del cual orbita.
	 * @return array
	 */
	private static function normalize_moon( $moon, $planet_id ) {
		$name             = ! empty( $moon['englishName'] ) ? $moon['englishName'] : ( $moon['name'] ?? '' );
		$alternative_name = trim( $moon['alternativeName'] ?? '' );

		if ( '' !== $name ) {
			// Traduce al español los satélites más conocidos. Los que no
			// están en el diccionario se quedan con su nombre internacional.
			$name = SSS_I18n_Names::translate_moon( $name );
		} else {
			// Algunos satélites irregulares aún no tienen nombre oficial: solo
			// su designación provisional. En ese caso se usa como nombre y no
			// se repite también en la columna de nombre provisional.
			$name = '' !== $alternative_name ? $alternative_name : ( $moon['id'] ?? __( 'Desconocido', 'satelites-sistema-solar' ) );
		}

		$provisional_name = ( '' !== $alternative_name && $alternative_name !== $name ) ? $alternative_name : '';

		$distance = SSS_Api_Client::semimajor_axis( $moon );
		$diameter = isset( $moon['meanRadius'] ) ? ( (float) $moon['meanRadius'] ) * 2 : 0;
		$density  = isset( $moon['density'] ) ? (float) $moon['density'] : 0;

		return array(
			'id'                => $moon['id'] ?? sanitize_title( $name ),
			'planet_id'         => $planet_id,
			'name'              => $name,
			'provisional_name'  => $provisional_name,
			'distance_km'       => $distance > 0 ? $distance : null,
			'diameter_km'       => $diameter > 0 ? $diameter : null,
			'density'           => $density > 0 ? $density : null,
			'discovery_year'    => self::extract_year( $moon['discoveryDate'] ?? '' ),
			'discoverer'        => trim( $moon['discoveredBy'] ?? '' ),
		);
	}

	/**
	 * Extrae el año (4 dígitos) de una cadena de fecha de descubrimiento.
	 *
	 * @param string $date_string Fecha tal cual la devuelve la API (p. ej. "12/03/1610" o "1610").
	 * @return int|null
	 */
	private static function extract_year( $date_string ) {
		if ( preg_match( '/(\d{4})/', $date_string, $matches ) ) {
			return (int) $matches[1];
		}

		return null;
	}

	/**
	 * Devuelve los planetas guardados (ordenados por distancia al Sol).
	 *
	 * @return array
	 */
	public static function get_planets() {
		return get_option( SSS_OPTION_PLANETS, array() );
	}

	/**
	 * Devuelve todos los satélites guardados.
	 *
	 * @return array
	 */
	public static function get_moons() {
		return get_option( SSS_OPTION_MOONS, array() );
	}

	/**
	 * Agrupa los satélites por id de planeta.
	 *
	 * @return array
	 */
	public static function get_moons_grouped_by_planet() {
		$grouped = array();
		foreach ( self::get_moons() as $moon ) {
			$grouped[ $moon['planet_id'] ][] = $moon;
		}

		return $grouped;
	}

	/**
	 * Devuelve el número de satélites por planeta y el total.
	 *
	 * @return array {
	 *     @type array $by_planet Array [planet_id => número de satélites].
	 *     @type int   $total     Número total de satélites.
	 * }
	 */
	public static function get_counts() {
		$grouped   = self::get_moons_grouped_by_planet();
		$by_planet = array();
		$total     = 0;

		foreach ( self::get_planets() as $planet ) {
			$count                        = isset( $grouped[ $planet['id'] ] ) ? count( $grouped[ $planet['id'] ] ) : 0;
			$by_planet[ $planet['id'] ]   = $count;
			$total                       += $count;
		}

		return array(
			'by_planet' => $by_planet,
			'total'     => $total,
		);
	}

	/**
	 * Marca de tiempo de la última actualización correcta.
	 *
	 * @return int|null
	 */
	public static function get_last_updated() {
		$timestamp = get_option( SSS_OPTION_LAST_UPDATED );

		return $timestamp ? (int) $timestamp : null;
	}

	/**
	 * Último mensaje de error, si lo hubiera.
	 *
	 * @return string
	 */
	public static function get_last_error() {
		return (string) get_option( SSS_OPTION_LAST_ERROR, '' );
	}
}
