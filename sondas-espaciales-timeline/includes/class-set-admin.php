<?php
/**
 * Arranque del panel de administración: menú y assets propios.
 *
 * La lógica de cada pantalla vive en SET_Admin_Probes y
 * SET_Admin_Destinations; esta clase solo registra el menú y encola el
 * CSS/JS del admin (solo en las páginas del propio plugin).
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Admin {

	const PAGE_PROBES       = 'sondas-espaciales-timeline';
	const PAGE_DESTINATIONS = 'set-destinos';

	/**
	 * Engancha los hooks de administración.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		SET_Admin_Probes::init();
		SET_Admin_Destinations::init();
	}

	/**
	 * Añade el menú del plugin y sus dos subpáginas.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Sondas Espaciales', 'sondas-espaciales-timeline' ),
			__( 'Sondas Espaciales', 'sondas-espaciales-timeline' ),
			'manage_options',
			self::PAGE_PROBES,
			array( 'SET_Admin_Probes', 'render_page' ),
			'dashicons-chart-line',
			26
		);

		add_submenu_page(
			self::PAGE_PROBES,
			__( 'Sondas', 'sondas-espaciales-timeline' ),
			__( 'Sondas', 'sondas-espaciales-timeline' ),
			'manage_options',
			self::PAGE_PROBES,
			array( 'SET_Admin_Probes', 'render_page' )
		);

		add_submenu_page(
			self::PAGE_PROBES,
			__( 'Destinos', 'sondas-espaciales-timeline' ),
			__( 'Destinos', 'sondas-espaciales-timeline' ),
			'manage_options',
			self::PAGE_DESTINATIONS,
			array( 'SET_Admin_Destinations', 'render_page' )
		);
	}

	/**
	 * Carga el CSS/JS del admin, solo en las páginas del propio plugin.
	 *
	 * @param string $hook Identificador de la pantalla actual de wp-admin.
	 */
	public static function enqueue_assets( $hook ) {
		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $current_page, array( self::PAGE_PROBES, self::PAGE_DESTINATIONS ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'set-admin',
			SET_PLUGIN_URL . 'assets/css/sondas-espaciales-timeline-admin.css',
			array(),
			SET_VERSION
		);

		$deps = array( 'jquery' );

		if ( self::PAGE_DESTINATIONS === $current_page ) {
			wp_enqueue_style( 'wp-color-picker' );
			$deps[] = 'wp-color-picker';
		}

		wp_enqueue_script(
			'set-admin',
			SET_PLUGIN_URL . 'assets/js/sondas-espaciales-timeline-admin.js',
			$deps,
			SET_VERSION,
			true
		);
	}

	/**
	 * Pinta un aviso de administración (éxito o error).
	 *
	 * @param string $message Texto del aviso (ya traducido).
	 * @param string $type    'success' o 'error'.
	 */
	public static function render_notice( $message, $type = 'success' ) {
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			'error' === $type ? 'error' : 'success',
			esc_html( $message )
		);
	}
}
