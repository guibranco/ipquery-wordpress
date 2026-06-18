<?php
/**
 * Plugin Name:       Stracini Visitor Analytics with IpQuery
 * Plugin URI:        https://guilherme.stracini.com.br/ipquery-wordpress/
 * Description:       Track and analyze visitor IP data using the IpQuery API (via guibranco/ipquery-php). Displays location maps, traffic heatmaps, and VPN/proxy statistics.
 * Version:           1.3.5
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Guilherme Branco Stracini
 * Author URI:        https://guilherme.stracini.com.br
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       stracini-visitor-analytics
 *
 * Bundled library:   guibranco/ipquery-php (MIT License)
 * Library URI:       https://github.com/guibranco/ipquery-php
 *
 * @package SVA
 */

defined( 'ABSPATH' ) || exit;

define( 'SVA_VERSION', '1.3.5' );
define( 'SVA_FILE', __FILE__ );
define( 'SVA_DIR', plugin_dir_path( __FILE__ ) );
define( 'SVA_URL', plugin_dir_url( __FILE__ ) );
define( 'SVA_TABLE', 'sva_visitors' );

require_once SVA_DIR . 'includes/vendor/IpQueryException.php';
require_once SVA_DIR . 'includes/vendor/Response/Isp.php';
require_once SVA_DIR . 'includes/vendor/Response/Location.php';
require_once SVA_DIR . 'includes/vendor/Response/Risk.php';
require_once SVA_DIR . 'includes/vendor/Response/IpQueryResponse.php';
require_once SVA_DIR . 'includes/vendor/IIpQueryClient.php';
require_once SVA_DIR . 'includes/vendor/IpQueryClient.php';
require_once SVA_DIR . 'includes/class-sva-db.php';
require_once SVA_DIR . 'includes/class-sva-tracker.php';
require_once SVA_DIR . 'includes/class-sva-admin.php';

register_activation_hook( SVA_FILE, array( 'SVA_DB', 'install' ) );
register_deactivation_hook( SVA_FILE, array( 'SVA_DB', 'deactivate' ) );
register_uninstall_hook( SVA_FILE, 'sva_uninstall' );

/**
 * Removes all plugin data on uninstall.
 *
 * @return void
 */
function sva_uninstall(): void {
	SVA_DB::uninstall();
	delete_option( 'sva_settings' );
	delete_option( 'sva_db_version' );
}

add_action( 'init', 'sva_init' );

/**
 * Boots the tracker when tracking is enabled.
 *
 * @return void
 */
function sva_init(): void {
	$settings = get_option( 'sva_settings', array() );
	if ( ! empty( $settings['tracking_enabled'] ) ) {
		SVA_Tracker::init( $settings );
	}
}

if ( is_admin() ) {
	add_action( 'plugins_loaded', array( 'SVA_Admin', 'init' ) );
}
