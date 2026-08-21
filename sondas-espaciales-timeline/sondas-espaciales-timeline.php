<?php
/**
 * Plugin Name:        Sondas Espaciales - Línea de Tiempo
 * Plugin URI:         https://github.com/pedro-leon/mi_repo
 * Description:        Línea de tiempo navegable (desde 1959 hasta hoy) con todas las sondas espaciales lanzadas, su duración de misión y su destino.
 * Version:            0.6.0
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

define( 'SET_VERSION', '0.6.0' );
define( 'SET_PLUGIN_FILE', __FILE__ );
define( 'SET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SET_TEXT_DOMAIN', 'sondas-espaciales-timeline' );

// Año en el que arranca la línea de tiempo (primer lanzamiento: Luna 1, 1959).
define( 'SET_TIMELINE_START_YEAR', 1959 );

// Versión del esquema de base de datos (tablas propias); subirla obliga a
// SET_Activator::maybe_upgrade() a volver a ejecutar create_tables().
define( 'SET_DB_VERSION', '1.1' );
define( 'SET_OPTION_DB_VERSION', 'set_db_version' );

require_once SET_PLUGIN_DIR . 'includes/class-set-data-store.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-shortcode.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-activator.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-deactivator.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-admin.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-admin-probes.php';
require_once SET_PLUGIN_DIR . 'includes/class-set-admin-destinations.php';

register_activation_hook( __FILE__, array( 'SET_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SET_Deactivator', 'deactivate' ) );

/**
 * Arranca los distintos componentes del plugin.
 */
function set_run_plugin() {
	load_plugin_textdomain( SET_TEXT_DOMAIN, false, dirname( plugin_basename( SET_PLUGIN_FILE ) ) . '/languages' );

	// Red de seguridad: si el plugin se actualizó copiando ficheros (sin
	// pasar por desactivar/activar) o el esquema cambió de versión, crea o
	// actualiza las tablas antes de que se usen.
	if ( get_option( SET_OPTION_DB_VERSION ) !== SET_DB_VERSION ) {
		SET_Activator::activate();
	}

	SET_Shortcode::init();

	if ( is_admin() ) {
		SET_Admin::init();
	}
}
add_action( 'plugins_loaded', 'set_run_plugin' );
