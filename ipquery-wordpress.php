<?php
/**
 * Plugin Name:       IpQuery for WordPress
 * Plugin URI:        https://guilherme.stracini.com.br/ipquery-wordpress/
 * Description:       Track and analyse visitor IP data using the IpQuery API (via guibranco/ipquery-php). Displays location maps, traffic heatmaps, and VPN/proxy statistics.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Guilherme Branco Stracini
 * Author URI:        https://github.com/guibranco
 * License:           MIT
 * Text Domain:       ipquery-wp
 */

defined( 'ABSPATH' ) || exit;

define( 'IPQUERY_WP_VERSION', '1.0.0' );
define( 'IPQUERY_WP_FILE',    __FILE__ );
define( 'IPQUERY_WP_DIR',     plugin_dir_path( __FILE__ ) );
define( 'IPQUERY_WP_URL',     plugin_dir_url( __FILE__ ) );
define( 'IPQUERY_WP_TABLE',   'ipquery_visitors' );

require_once IPQUERY_WP_DIR . 'includes/vendor/IpQueryException.php';
require_once IPQUERY_WP_DIR . 'includes/vendor/Response/Isp.php';
require_once IPQUERY_WP_DIR . 'includes/vendor/Response/Location.php';
require_once IPQUERY_WP_DIR . 'includes/vendor/Response/Risk.php';
require_once IPQUERY_WP_DIR . 'includes/vendor/Response/IpQueryResponse.php';
require_once IPQUERY_WP_DIR . 'includes/vendor/IIpQueryClient.php';
require_once IPQUERY_WP_DIR . 'includes/vendor/IpQueryClient.php';
require_once IPQUERY_WP_DIR . 'includes/class-ipquery-db.php';
require_once IPQUERY_WP_DIR . 'includes/class-ipquery-tracker.php';
require_once IPQUERY_WP_DIR . 'includes/class-ipquery-admin.php';

register_activation_hook( IPQUERY_WP_FILE,   [ 'IpQuery_DB', 'install' ] );
register_deactivation_hook( IPQUERY_WP_FILE, [ 'IpQuery_DB', 'deactivate' ] );
register_uninstall_hook( IPQUERY_WP_FILE,    'ipquery_wp_uninstall' );

function ipquery_wp_uninstall(): void {
    IpQuery_DB::uninstall();
    delete_option( 'ipquery_wp_settings' );
    delete_option( 'ipquery_wp_db_version' );
}

add_action( 'init', 'ipquery_wp_init' );
function ipquery_wp_init(): void {
    $settings = get_option( 'ipquery_wp_settings', [] );
    if ( ! empty( $settings['tracking_enabled'] ) ) {
        IpQuery_Tracker::init( $settings );
    }
}

if ( is_admin() ) {
    add_action( 'plugins_loaded', [ 'IpQuery_Admin', 'init' ] );
}
