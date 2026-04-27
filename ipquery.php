<?php
/**
 * Plugin Name:       IpQuery
 * Plugin URI:        https://guilherme.stracini.com.br/ipquery-wordpress/
 * Description:       Track and analyse visitor IP data using the IpQuery API (via guibranco/ipquery-php). Displays location maps, traffic heatmaps, and VPN/proxy statistics.
 * Version:           1.0.4
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Guilherme Branco Stracini
 * Author URI:        https://github.com/guibranco
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       ipquery
 *
 * Bundled library:   guibranco/ipquery-php (MIT License)
 * Library URI:       https://github.com/guibranco/ipquery-php
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit;

define( 'IPQUERY_VERSION', '1.0.4' );
define( 'IPQUERY_FILE', __FILE__ );
define( 'IPQUERY_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPQUERY_URL', plugin_dir_url( __FILE__ ) );
define( 'IPQUERY_TABLE', 'ipquery_visitors' );

require_once IPQUERY_DIR . 'includes/vendor/IpQueryException.php';
require_once IPQUERY_DIR . 'includes/vendor/Response/Isp.php';
require_once IPQUERY_DIR . 'includes/vendor/Response/Location.php';
require_once IPQUERY_DIR . 'includes/vendor/Response/Risk.php';
require_once IPQUERY_DIR . 'includes/vendor/Response/IpQueryResponse.php';
require_once IPQUERY_DIR . 'includes/vendor/IIpQueryClient.php';
require_once IPQUERY_DIR . 'includes/vendor/IpQueryClient.php';
require_once IPQUERY_DIR . 'includes/class-ipquery-db.php';
require_once IPQUERY_DIR . 'includes/class-ipquery-tracker.php';
require_once IPQUERY_DIR . 'includes/class-ipquery-admin.php';

register_activation_hook( IPQUERY_FILE, array( 'IpQuery_DB', 'install' ) );
register_deactivation_hook( IPQUERY_FILE, array( 'IpQuery_DB', 'deactivate' ) );
register_uninstall_hook( IPQUERY_FILE, 'ipquery_uninstall' );

/**
 * Removes all plugin data on uninstall.
 *
 * @return void
 */
function ipquery_uninstall(): void {
	IpQuery_DB::uninstall();
	delete_option( 'ipquery_settings' );
	delete_option( 'ipquery_db_version' );
}

add_action( 'init', 'ipquery_init' );

/**
 * Boots the tracker when tracking is enabled.
 *
 * @return void
 */
function ipquery_init(): void {
	$settings = get_option( 'ipquery_settings', array() );
	if ( ! empty( $settings['tracking_enabled'] ) ) {
		IpQuery_Tracker::init( $settings );
	}
}

if ( is_admin() ) {
	add_action( 'plugins_loaded', array( 'IpQuery_Admin', 'init' ) );
}
