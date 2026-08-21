<?php
/**
 * Limpieza al desinstalar el plugin.
 *
 * @package Satelites_Espana_Shortcode
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ses_gcat_spain_satellites' );
delete_option( 'ses_manual_spain_satellites' );
delete_option( 'ses_gcat_source_updated' );
delete_option( 'ses_gcat_last_sync' );
delete_option( 'ses_gcat_last_error' );
delete_option( 'ses_gcat_http_etag' );
delete_option( 'ses_gcat_http_last_modified' );

wp_clear_scheduled_hook( 'ses_update_spain_satellites' );
