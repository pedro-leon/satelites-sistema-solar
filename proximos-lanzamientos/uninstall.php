<?php
/**
 * Limpieza al desinstalar el plugin.
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ple_launches_data' );
delete_option( 'ple_last_updated' );
delete_option( 'ple_last_error' );
delete_option( 'ple_launches_limit' );
delete_option( 'ple_refresh_minutes' );

wp_clear_scheduled_hook( 'ple_refresh_launches' );
