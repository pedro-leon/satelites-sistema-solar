<?php
/**
 * Plugin Name:       Proximos Lanzamientos Espaciales
 * Plugin URI:        https://github.com/pedro-leon/mi_repo
 * Description:       Muestra los próximos lanzamientos de cohetes con el shortcode [proximos_lanzamientos], usando datos de Launch Library 2 cacheados en el servidor.
 * Version:            2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:             Pedro León con Codex
 * License:            GPL-2.0-or-later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        proximos-lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PLE_VERSION', '2.0.0' );
define( 'PLE_PLUGIN_FILE', __FILE__ );
define( 'PLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PLE_TEXT_DOMAIN', 'proximos-lanzamientos' );

define( 'PLE_CRON_HOOK', 'ple_refresh_launches' );
define( 'PLE_OPTION_LAUNCHES', 'ple_launches_data' );
define( 'PLE_OPTION_LAST_UPDATED', 'ple_last_updated' );
define( 'PLE_OPTION_LAST_ERROR', 'ple_last_error' );
define( 'PLE_OPTION_LIMIT', 'ple_launches_limit' );
define( 'PLE_OPTION_REFRESH_MINUTES', 'ple_refresh_minutes' );
define( 'PLE_API_BASE', 'https://ll.thespacedevs.com/2.3.0/launches/upcoming/' );
define( 'PLE_REST_NAMESPACE', 'ple/v1' );

require_once PLE_PLUGIN_DIR . 'includes/class-ple-api-client.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-data-store.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-cron.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-rest.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-shortcode.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-admin.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-activator.php';
require_once PLE_PLUGIN_DIR . 'includes/class-ple-deactivator.php';

register_activation_hook( __FILE__, array( 'PLE_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PLE_Deactivator', 'deactivate' ) );

/**
 * Arranca los distintos componentes del plugin.
 */
function ple_run_plugin() {
	load_plugin_textdomain( PLE_TEXT_DOMAIN, false, dirname( plugin_basename( PLE_PLUGIN_FILE ) ) . '/languages' );

	PLE_Cron::init();
	PLE_Rest::init();
	PLE_Shortcode::init();
	PLE_Admin::init();
}
add_action( 'plugins_loaded', 'ple_run_plugin' );
