<?php
/**
 * Limpieza al desinstalar el plugin.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sss_moons_data' );
delete_option( 'sss_planets_data' );
delete_option( 'sss_last_updated' );
delete_option( 'sss_last_error' );

wp_clear_scheduled_hook( 'sss_weekly_moons_fetch' );
