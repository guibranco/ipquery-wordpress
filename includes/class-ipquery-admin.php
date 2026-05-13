<?php
/**
 * Admin controller — menus, asset enqueueing, form handlers, and AJAX endpoints.
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit;

use GuiBranco\IpQuery\IpQueryClient;
use GuiBranco\IpQuery\IpQueryException;

/**
 * Registers and handles all WordPress admin functionality for IpQuery.
 */
class IpQuery_Admin {

	/**
	 * Registers all admin hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'admin_init', array( self::class, 'handle_settings_save' ) );
		add_action( 'admin_post_ipquery_delete_ip', array( self::class, 'handle_delete_ip' ) );
		add_action( 'admin_post_ipquery_delete_by_country', array( self::class, 'handle_delete_by_country' ) );
		add_action( 'admin_post_ipquery_purge', array( self::class, 'handle_purge' ) );
		add_action( 'admin_post_ipquery_lookup', array( self::class, 'handle_manual_lookup' ) );
		add_action( 'admin_post_ipquery_export_csv', array( self::class, 'handle_export_csv' ) );
		add_action( 'wp_ajax_ipquery_chart_data', array( self::class, 'ajax_chart_data' ) );
		add_action( 'wp_ajax_ipquery_heatmap_data', array( self::class, 'ajax_heatmap_data' ) );

		// Schedule daily cleanup cron if not already queued.
		if ( ! wp_next_scheduled( 'ipquery_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'ipquery_daily_cleanup' );
		}
		add_action( 'ipquery_daily_cleanup', array( self::class, 'run_cleanup' ) );
	}

	/**
	 * Registers the admin menu and sub-pages.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		add_menu_page(
			__( 'IpQuery', 'ipquery' ),
			__( 'IpQuery', 'ipquery' ),
			'manage_options',
			'ipquery-dashboard',
			array( self::class, 'page_dashboard' ),
			'dashicons-location-alt',
			81
		);
		add_submenu_page(
			'ipquery-dashboard',
			__( 'Dashboard', 'ipquery' ),
			__( 'Dashboard', 'ipquery' ),
			'manage_options',
			'ipquery-dashboard',
			array( self::class, 'page_dashboard' )
		);
		add_submenu_page(
			'ipquery-dashboard',
			__( 'Visitors', 'ipquery' ),
			__( 'Visitors', 'ipquery' ),
			'manage_options',
			'ipquery-visitors',
			array( self::class, 'page_visitors' )
		);
		add_submenu_page(
			'ipquery-dashboard',
			__( 'Settings', 'ipquery' ),
			__( 'Settings', 'ipquery' ),
			'manage_options',
			'ipquery-settings',
			array( self::class, 'page_settings' )
		);
	}

	/**
	 * Enqueues styles and scripts on IpQuery admin pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'ipquery' ) ) {
			return;
		}

		// Leaflet map library (bundled).
		wp_enqueue_style( 'leaflet', IPQUERY_URL . 'assets/css/leaflet.min.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', IPQUERY_URL . 'assets/js/leaflet.min.js', array(), '1.9.4', true );
		wp_enqueue_script( 'leaflet-heat', IPQUERY_URL . 'assets/js/leaflet-heat.js', array( 'leaflet' ), '0.2.0', true );

		// Chart.js for bar and doughnut charts (bundled).
		wp_enqueue_script( 'chartjs', IPQUERY_URL . 'assets/js/chart.umd.min.js', array(), '4.4.3', true );

		// Plugin assets.
		wp_enqueue_style( 'ipquery-admin', IPQUERY_URL . 'assets/css/admin.css', array(), IPQUERY_VERSION );
		wp_enqueue_script( 'ipquery-maps', IPQUERY_URL . 'assets/js/ipquery-maps.js', array( 'leaflet', 'leaflet-heat', 'jquery' ), IPQUERY_VERSION, true );
		wp_enqueue_script( 'ipquery-charts', IPQUERY_URL . 'assets/js/ipquery-charts.js', array( 'chartjs', 'jquery' ), IPQUERY_VERSION, true );

		wp_localize_script(
			'ipquery-maps',
			'IpQueryData',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'ipquery_ajax' ),
				'heatmapAction' => 'ipquery_heatmap_data',
				'chartAction'   => 'ipquery_chart_data',
			)
		);
	}

	// -------------------------------------------------------------------------
	// Page callbacks – delegate rendering to view files.
	// -------------------------------------------------------------------------

	/**
	 * Renders the dashboard admin page.
	 *
	 * @return void
	 */
	public static function page_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}
		include IPQUERY_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Renders the visitors admin page.
	 *
	 * @return void
	 */
	public static function page_visitors(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}
		include IPQUERY_DIR . 'admin/views/visitors.php';
	}

	/**
	 * Renders the settings admin page.
	 *
	 * @return void
	 */
	public static function page_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}
		include IPQUERY_DIR . 'admin/views/settings.php';
	}

	// -------------------------------------------------------------------------
	// Settings save handler.
	// -------------------------------------------------------------------------

	/**
	 * Validates the settings form nonce and saves options to the database.
	 *
	 * @return void
	 */
	public static function handle_settings_save(): void {
		if (
			! isset( $_POST['ipquery_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipquery_settings_nonce'] ) ), 'ipquery_save_settings' )
		) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		update_option(
			'ipquery_settings',
			array(
				'tracking_enabled'   => ! empty( $_POST['tracking_enabled'] ),
				'track_logged_in'    => ! empty( $_POST['track_logged_in'] ),
				'track_admins'       => ! empty( $_POST['track_admins'] ),
				'excluded_ips'       => sanitize_textarea_field( wp_unslash( $_POST['excluded_ips'] ?? '' ) ),
				'retention_days'     => max( 1, (int) sanitize_text_field( wp_unslash( $_POST['retention_days'] ?? '90' ) ) ),
				'lookup_private_ips' => ! empty( $_POST['lookup_private_ips'] ),
			)
		);

		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'ipquery' ) . '</p></div>';
			}
		);
	}

	// -------------------------------------------------------------------------
	// Action handlers.
	// -------------------------------------------------------------------------

	/**
	 * Handles the delete-single-IP form submission.
	 *
	 * @return void
	 */
	public static function handle_delete_ip(): void {
		check_admin_referer( 'ipquery_delete_ip' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}
		$ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			IpQuery_DB::delete_ip( $ip );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&deleted=1' ) );
		exit;
	}

	/**
	 * Handles the delete-by-country form submission.
	 *
	 * @return void
	 */
	public static function handle_delete_by_country(): void {
		check_admin_referer( 'ipquery_delete_by_country' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}

		$country_code = strtoupper(
			sanitize_text_field( wp_unslash( $_POST['country_code'] ?? '' ) )
		);

		if ( empty( $country_code ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors' ) );
			exit;
		}

		$deleted = IpQuery_DB::delete_by_country( $country_code );

		if ( false === $deleted ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=ipquery-visitors&country_delete_error=1' )
			);
			exit;
		}

		IpQuery_DB::log_action(
			sprintf(
				'Admin "%s" deleted %d visitor record(s) for country: %s',
				wp_get_current_user()->user_login,
				(int) $deleted,
				$country_code
			)
		);

		$deleted_param = (string) (int) $deleted;

		wp_safe_redirect(
			admin_url(
				'admin.php?page=ipquery-visitors'
				. '&country_deleted=' . $deleted_param
				. '&country_code=' . rawurlencode( $country_code )
			)
		);
		exit;
	}

	/**
	 * Handles the bulk-purge form submission.
	 *
	 * @return void
	 */
	public static function handle_purge(): void {
		check_admin_referer( 'ipquery_purge' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}
		$days    = max( 1, (int) sanitize_text_field( wp_unslash( $_POST['days'] ?? '90' ) ) );
		$deleted = IpQuery_DB::delete_old_records( $days );
		wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&purged=' . $deleted ) );
		exit;
	}

	/**
	 * Handles the manual IP-lookup form submission.
	 *
	 * @return void
	 */
	public static function handle_manual_lookup(): void {
		check_admin_referer( 'ipquery_manual_lookup' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}
		$ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&lookup_error=invalid' ) );
			exit;
		}

		try {
			$client   = new IpQueryClient();
			$response = $client->getIpData( $ip );
			$row      = array(
				'ip'            => $ip,
				'country'       => $response->location?->country ?? null,
				'country_code'  => $response->location?->countryCode ?? null,
				'city'          => $response->location?->city ?? null,
				'state'         => $response->location?->state ?? null,
				'zipcode'       => $response->location?->zipcode ?? null,
				'latitude'      => $response->location?->latitude ?? null,
				'longitude'     => $response->location?->longitude ?? null,
				'timezone'      => $response->location?->timezone ?? null,
				'asn'           => $response->isp?->asn ?? null,
				'org'           => $response->isp?->org ?? null,
				'isp'           => $response->isp?->isp ?? null,
				'is_mobile'     => (int) ( $response->risk?->isMobile ?? 0 ),
				'is_vpn'        => (int) ( $response->risk?->isVpn ?? 0 ),
				'is_tor'        => (int) ( $response->risk?->isTor ?? 0 ),
				'is_proxy'      => (int) ( $response->risk?->isProxy ?? 0 ),
				'is_datacenter' => (int) ( $response->risk?->isDatacenter ?? 0 ),
				'risk_score'    => $response->risk?->riskScore ?? 0,
			);
			IpQuery_DB::upsert( $row );
			wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&lookup_ok=1' ) );
		} catch ( IpQueryException $e ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&lookup_error=' . rawurlencode( $e->getMessage() ) ) );
		}
		exit;
	}

	/**
	 * Streams all matching visitor records as a CSV download.
	 *
	 * @return void
	 */
	public static function handle_export_csv(): void {
		check_admin_referer( 'ipquery_export_csv' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ipquery' ) );
		}

		$search      = sanitize_text_field( wp_unslash( $_POST['s'] ?? '' ) );
		$risk_filter = sanitize_text_field( wp_unslash( $_POST['risk_filter'] ?? '' ) );

		$rows     = IpQuery_DB::get_all_for_export(
			array(
				'search'      => $search,
				'risk_filter' => $risk_filter,
			)
		);
		$filename = 'ipquery-visitors-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// UTF-8 BOM for Excel compatibility.
		fwrite( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		fputcsv( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
			$output,
			array(
				'IP', 'Country', 'Country Code', 'City', 'State', 'Zipcode',
				'Latitude', 'Longitude', 'Timezone', 'ASN', 'Org', 'ISP',
				'Is Mobile', 'Is VPN', 'Is Tor', 'Is Proxy', 'Is Datacenter',
				'Risk Score', 'First Seen', 'Last Seen', 'Visit Count',
			)
		);

		foreach ( $rows as $row ) {
			fputcsv( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
				$output,
				array(
					$row['ip'],
					$row['country'] ?? '',
					$row['country_code'] ?? '',
					$row['city'] ?? '',
					$row['state'] ?? '',
					$row['zipcode'] ?? '',
					$row['latitude'] ?? '',
					$row['longitude'] ?? '',
					$row['timezone'] ?? '',
					$row['asn'] ?? '',
					$row['org'] ?? '',
					$row['isp'] ?? '',
					$row['is_mobile'] ? 'Yes' : 'No',
					$row['is_vpn'] ? 'Yes' : 'No',
					$row['is_tor'] ? 'Yes' : 'No',
					$row['is_proxy'] ? 'Yes' : 'No',
					$row['is_datacenter'] ? 'Yes' : 'No',
					$row['risk_score'] ?? 0,
					$row['first_seen'] ?? '',
					$row['last_seen'] ?? '',
					$row['visit_count'] ?? 0,
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	// -------------------------------------------------------------------------
	// AJAX endpoints.
	// -------------------------------------------------------------------------

	/**
	 * Returns chart data (countries, risk counts, totals) as JSON.
	 *
	 * @return void
	 */
	public static function ajax_chart_data(): void {
		check_ajax_referer( 'ipquery_ajax', 'nonce' );
		$data = array(
			'countries' => IpQuery_DB::get_top_countries( 15 ),
			'risk'      => IpQuery_DB::get_risk_counts(),
			'totals'    => array(
				'visits'     => IpQuery_DB::get_total_visits(),
				'unique_ips' => IpQuery_DB::get_unique_ips(),
			),
		);
		wp_send_json_success( $data );
	}

	/**
	 * Returns heatmap coordinate data as JSON.
	 *
	 * @return void
	 */
	public static function ajax_heatmap_data(): void {
		check_ajax_referer( 'ipquery_ajax', 'nonce' );
		$points = IpQuery_DB::get_coordinates_for_heatmap( 500 );
		wp_send_json_success( $points );
	}

	/**
	 * Runs the data-retention cleanup; called by the daily WP-Cron event.
	 *
	 * @return void
	 */
	public static function run_cleanup(): void {
		$settings = get_option( 'ipquery_settings', array() );
		$days     = (int) ( $settings['retention_days'] ?? 90 );
		IpQuery_DB::delete_old_records( $days );
	}
}
