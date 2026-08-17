<?php
/**
 * Plugin Name:       Satélites del Sistema Solar
 * Plugin URI:         https://github.com/pedro-leon/mi_repo
 * Description:        Muestra el número de satélites de cada planeta del sistema solar y un listado ordenable con sus características, obtenidos a diario desde una API pública.
 * Version:             1.0.0
 * Requires at least:  5.8
 * Requires PHP:        7.4
 * Author:              Pedro León
 * License:             GPL-2.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         satelites-sistema-solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Salir si se accede directamente.
}

define( 'SSS_VERSION', '1.0.0' );
define( 'SSS_PLUGIN_FILE', __FILE__ );
define( 'SSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSS_TEXT_DOMAIN', 'satelites-sistema-solar' );

define( 'SSS_CRON_HOOK', 'sss_daily_moons_fetch' );
define( 'SSS_OPTION_MOONS', 'sss_moons_data' );
define( 'SSS_OPTION_PLANETS', 'sss_planets_data' );
define( 'SSS_OPTION_LAST_UPDATED', 'sss_last_updated' );
define( 'SSS_OPTION_LAST_ERROR', 'sss_last_error' );
define( 'SSS_API_BASE', 'https://api.le-systeme-solaire.net/rest/bodies/' );

require_once SSS_PLUGIN_DIR . 'includes/class-sss-api-client.php';
require_once SSS_PLUGIN_DIR . 'includes/class-sss-data-store.php';
require_once SSS_PLUGIN_DIR . 'includes/class-sss-cron.php';
require_once SSS_PLUGIN_DIR . 'includes/class-sss-shortcode.php';
require_once SSS_PLUGIN_DIR . 'includes/class-sss-admin.php';
require_once SSS_PLUGIN_DIR . 'includes/class-sss-activator.php';
require_once SSS_PLUGIN_DIR . 'includes/class-sss-deactivator.php';

register_activation_hook( __FILE__, array( 'SSS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SSS_Deactivator', 'deactivate' ) );

/**
 * Arranca los distintos componentes del plugin.
 */
function sss_run_plugin() {
	load_plugin_textdomain( SSS_TEXT_DOMAIN, false, dirname( plugin_basename( SSS_PLUGIN_FILE ) ) . '/languages' );

	SSS_Cron::init();
	SSS_Shortcode::init();
	SSS_Admin::init();
}
add_action( 'plugins_loaded', 'sss_run_plugin' );
