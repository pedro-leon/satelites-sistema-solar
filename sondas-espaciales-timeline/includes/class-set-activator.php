<?php
/**
 * Acciones ejecutadas al activar el plugin: crea las tablas propias y las
 * siembra con los datos de fábrica si están vacías.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Activator {

	/**
	 * Crea/actualiza las tablas y siembra los datos de fábrica.
	 */
	public static function activate() {
		self::create_tables();
		SET_Data_Store::maybe_seed_defaults();

		update_option( SET_OPTION_DB_VERSION, SET_DB_VERSION );
	}

	/**
	 * Crea (o actualiza, si ya existían) las tablas `set_probes` y
	 * `set_destinations` mediante dbDelta, que es idempotente: se puede
	 * llamar de nuevo sin duplicar datos ni fallar si ya existen.
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$table_destinations = SET_Data_Store::destinations_table();
		$sql_destinations   = "CREATE TABLE $table_destinations (
			id VARCHAR(32) NOT NULL,
			label VARCHAR(191) NOT NULL,
			code VARCHAR(16) NOT NULL,
			color VARCHAR(7) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_destinations );

		$table_probes = SET_Data_Store::probes_table();
		$sql_probes   = "CREATE TABLE $table_probes (
			id VARCHAR(64) NOT NULL,
			name VARCHAR(191) NOT NULL,
			agency VARCHAR(191) NOT NULL DEFAULT '',
			destination VARCHAR(32) NOT NULL,
			launch_year SMALLINT NOT NULL,
			end_year SMALLINT NULL,
			status VARCHAR(16) NOT NULL,
			note TEXT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY destination (destination),
			KEY launch_year (launch_year)
		) $charset_collate;";
		dbDelta( $sql_probes );
	}
}
