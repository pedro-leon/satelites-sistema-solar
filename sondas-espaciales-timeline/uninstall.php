<?php
/**
 * Se ejecuta al desinstalar el plugin desde el panel de WordPress.
 *
 * Borra las tablas propias (`set_probes` y `set_destinations`) y la
 * opción de versión del esquema. La desactivación normal (sin
 * desinstalar) no toca nada de esto: los datos sobreviven a un simple
 * "desactivar".
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'set_probes' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'set_destinations' );

delete_option( 'set_db_version' );
