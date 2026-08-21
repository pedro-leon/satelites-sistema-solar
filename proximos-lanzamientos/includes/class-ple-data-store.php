<?php
/**
 * Normaliza, cachea y expone los datos de lanzamientos.
 *
 * Los lanzamientos se descargan una única vez por intervalo (vía WP-Cron) y
 * se guardan ya normalizados: así el front-end de cada visitante lee de una
 * caché local en vez de golpear la API pública directamente, evitando el
 * límite de peticiones por hora de Launch Library 2, y la URL de imagen y
 * los enlaces se validan aquí (además de en el JS) antes de guardarse.
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Data_Store {

	const DEFAULT_LIMIT           = 12;
	const MAX_LIMIT                = 50;
	const DEFAULT_REFRESH_MINUTES = 15;
	const ALLOWED_REFRESH_MINUTES = array( 15, 30, 60 );

	/**
	 * Descarga los lanzamientos de la API, los normaliza y los guarda.
	 *
	 * @return true|WP_Error
	 */
	public static function refresh_data() {
		$launches = PLE_Api_Client::get_upcoming_launches( self::get_limit() );

		if ( is_wp_error( $launches ) ) {
			update_option( PLE_OPTION_LAST_ERROR, $launches->get_error_message() );
			return $launches;
		}

		$normalized = array();
		foreach ( $launches as $launch ) {
			$normalized[] = self::normalize_launch( $launch );
		}

		update_option( PLE_OPTION_LAUNCHES, $normalized, false );
		update_option( PLE_OPTION_LAST_UPDATED, time(), false );
		delete_option( PLE_OPTION_LAST_ERROR );

		return true;
	}

	/**
	 * Convierte un lanzamiento crudo de la API en la estructura reducida y
	 * ya validada que expone el plugin (endpoint REST y render inicial).
	 *
	 * @param array $launch Lanzamiento tal cual lo devuelve la API.
	 * @return array
	 */
	private static function normalize_launch( $launch ) {
		$image = '';
		if ( ! empty( $launch['image']['image_url'] ) ) {
			$image = $launch['image']['image_url'];
		} elseif ( is_string( $launch['image'] ?? null ) ) {
			$image = $launch['image'];
		} elseif ( ! empty( $launch['rocket']['configuration']['image_url'] ) ) {
			$image = $launch['rocket']['configuration']['image_url'];
		}

		return array(
			'name'          => (string) ( $launch['name'] ?? '' ),
			'window_start'  => (string) ( $launch['window_start'] ?? '' ),
			'status_abbrev' => (string) ( $launch['status']['abbrev'] ?? '' ),
			'status_name'   => (string) ( $launch['status']['name'] ?? '' ),
			'agency'        => (string) ( $launch['launch_service_provider']['name'] ?? '' ),
			'rocket'        => (string) ( $launch['rocket']['configuration']['full_name'] ?? ( $launch['rocket']['configuration']['name'] ?? '' ) ),
			'pad'           => (string) ( $launch['pad']['name'] ?? '' ),
			'location'      => (string) ( $launch['pad']['location']['name'] ?? '' ),
			'description'   => (string) ( $launch['mission']['description'] ?? ( $launch['launch_service_provider']['description'] ?? '' ) ),
			'image'         => self::safe_url( $image ),
			'links'         => self::normalize_links( $launch ),
		);
	}

	/**
	 * Extrae y valida los enlaces de vídeo/web de un lanzamiento, elimina
	 * duplicados y los limita a 4 (los mismos que renderiza el front-end).
	 *
	 * @param array $launch Lanzamiento crudo de la API.
	 * @return array
	 */
	private static function normalize_links( $launch ) {
		$video_source = $launch['vidURLs'] ?? ( $launch['vid_urls'] ?? array() );
		$web_source   = $launch['infoURLs'] ?? ( $launch['info_urls'] ?? array() );

		$video = is_array( $video_source ) ? $video_source : array();
		$web   = is_array( $web_source ) ? $web_source : array();

		$links = array();
		$seen  = array();

		foreach ( $video as $item ) {
			$url = self::safe_url( $item['url'] ?? '' );
			if ( '' === $url || isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;
			$links[]      = array(
				'url'  => $url,
				'type' => 'video',
			);
		}

		foreach ( $web as $item ) {
			$url = self::safe_url( $item['url'] ?? '' );
			if ( '' === $url || isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;
			$is_official   = false !== stripos( (string) ( $item['type']['name'] ?? '' ), 'official' );
			$links[]       = array(
				'url'  => $url,
				'type' => $is_official ? 'official' : 'web',
			);
		}

		return array_slice( $links, 0, 4 );
	}

	/**
	 * Valida que una URL use un esquema http(s) antes de guardarla.
	 *
	 * @param string $url URL candidata.
	 * @return string La URL saneada, o cadena vacía si no es segura.
	 */
	private static function safe_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	/**
	 * Devuelve los lanzamientos cacheados.
	 *
	 * @return array
	 */
	public static function get_launches() {
		$launches = get_option( PLE_OPTION_LAUNCHES, array() );
		return is_array( $launches ) ? $launches : array();
	}

	/**
	 * Marca de tiempo de la última actualización correcta.
	 *
	 * @return int|null
	 */
	public static function get_last_updated() {
		$timestamp = get_option( PLE_OPTION_LAST_UPDATED );
		return $timestamp ? (int) $timestamp : null;
	}

	/**
	 * Último mensaje de error, si lo hubiera.
	 *
	 * @return string
	 */
	public static function get_last_error() {
		return (string) get_option( PLE_OPTION_LAST_ERROR, '' );
	}

	/**
	 * Número de lanzamientos a solicitar/mostrar (configurable, 1-50).
	 *
	 * @return int
	 */
	public static function get_limit() {
		$value = (int) get_option( PLE_OPTION_LIMIT, self::DEFAULT_LIMIT );
		return max( 1, min( self::MAX_LIMIT, $value ) );
	}

	/**
	 * Intervalo de refresco automático en minutos (uno de ALLOWED_REFRESH_MINUTES).
	 *
	 * @return int
	 */
	public static function get_refresh_minutes() {
		$value = (int) get_option( PLE_OPTION_REFRESH_MINUTES, self::DEFAULT_REFRESH_MINUTES );
		return in_array( $value, self::ALLOWED_REFRESH_MINUTES, true ) ? $value : self::DEFAULT_REFRESH_MINUTES;
	}
}
