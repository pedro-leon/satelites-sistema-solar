<?php
/**
 * Se ejecuta al desinstalar el plugin desde el panel de WordPress.
 *
 * El plugin no guarda opciones ni tablas propias (los datos son estáticos,
 * en includes/data/), así que no hay nada que limpiar por ahora.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
