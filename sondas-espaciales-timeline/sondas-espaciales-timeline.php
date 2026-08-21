<?php
/**
 * Plugin Name:        Sondas Espaciales - Línea de Tiempo
 * Plugin URI:         https://github.com/pedro-leon/mi_repo
 * Description:        Línea de tiempo navegable (desde 1959 hasta hoy) con todas las sondas espaciales lanzadas, su duración de misión y su destino.
 * Version:            0.2.0
 * Requires at least:  5.8
 * Requires PHP:       7.4
 * Author:             Pedro León
 * License:            GPL-2.0-or-later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        sondas-espaciales-timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Salir si se accede directamente.
}

define( 'SET_VERSION', '0.2.0' );
define( 'SET_PLUGIN_FILE', __FILE__ );
define( 'SET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SET_TEXT_DOMAIN', 'sondas-espaciales-timeline' );

// Año en el que arranca la línea de tiempo (primer lanzamiento: Luna 1, 1959).
define( 'SET_TIMELINE_START_YEAR', 1959 );

require_once SET_PLUGIN_DIR . 'includes/class-set-data-store.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-shortcode.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-activator.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-deactivator.php';

register_activation_hook( __FILE__, array( 'SET_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SET_Deactivator', 'deactivate' ) );

/**
 * Arranca los distintos componentes del plugin.
 */
function set_run_plugin() {
	load_plugin_textdomain( SET_TEXT_DOMAIN, false, dirname( plugin_basename( SET_PLUGIN_FILE ) ) . '/languages' );

	SET_Shortcode::init();
}
add_action( 'plugins_loaded', 'set_run_plugin' );
