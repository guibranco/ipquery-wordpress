<?php

defined( 'ABSPATH' ) || exit;

use GuiBranco\IpQuery\IpQueryClient;
use GuiBranco\IpQuery\IpQueryException;

class IpQuery_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ self::class, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
        add_action( 'admin_init',            [ self::class, 'handle_settings_save' ] );
        add_action( 'admin_post_ipquery_delete_ip',   [ self::class, 'handle_delete_ip' ] );
        add_action( 'admin_post_ipquery_purge',       [ self::class, 'handle_purge' ] );
        add_action( 'admin_post_ipquery_lookup',      [ self::class, 'handle_manual_lookup' ] );
        add_action( 'wp_ajax_ipquery_chart_data',     [ self::class, 'ajax_chart_data' ] );
        add_action( 'wp_ajax_ipquery_heatmap_data',   [ self::class, 'ajax_heatmap_data' ] );

        // Daily cleanup cron.
        if ( ! wp_next_scheduled( 'ipquery_wp_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'ipquery_wp_daily_cleanup' );
        }
        add_action( 'ipquery_wp_daily_cleanup', [ self::class, 'run_cleanup' ] );
    }

    public static function register_menu(): void {
        add_menu_page(
            __( 'IpQuery', 'ipquery-wp' ),
            __( 'IpQuery', 'ipquery-wp' ),
            'manage_options',
            'ipquery-dashboard',
            [ self::class, 'page_dashboard' ],
            'dashicons-location-alt',
            81
        );
        add_submenu_page(
            'ipquery-dashboard',
            __( 'Dashboard', 'ipquery-wp' ),
            __( 'Dashboard', 'ipquery-wp' ),
            'manage_options',
            'ipquery-dashboard',
            [ self::class, 'page_dashboard' ]
        );
        add_submenu_page(
            'ipquery-dashboard',
            __( 'Visitors', 'ipquery-wp' ),
            __( 'Visitors', 'ipquery-wp' ),
            'manage_options',
            'ipquery-visitors',
            [ self::class, 'page_visitors' ]
        );
        add_submenu_page(
            'ipquery-dashboard',
            __( 'Settings', 'ipquery-wp' ),
            __( 'Settings', 'ipquery-wp' ),
            'manage_options',
            'ipquery-settings',
            [ self::class, 'page_settings' ]
        );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( ! str_contains( $hook, 'ipquery' ) ) {
            return;
        }

        // Leaflet
        wp_enqueue_style( 'leaflet',      'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',          [], '1.9.4' );
        wp_enqueue_script( 'leaflet',     'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',           [], '1.9.4', true );
        wp_enqueue_script( 'leaflet-heat','https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js', [ 'leaflet' ], '0.2.0', true );

        // Chart.js
        wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', [], '4.4.3', true );

        // Plugin assets
        wp_enqueue_style(  'ipquery-admin', IPQUERY_WP_URL . 'assets/css/admin.css', [], IPQUERY_WP_VERSION );
        wp_enqueue_script( 'ipquery-maps',  IPQUERY_WP_URL . 'assets/js/ipquery-maps.js',   [ 'leaflet', 'leaflet-heat', 'jquery' ], IPQUERY_WP_VERSION, true );
        wp_enqueue_script( 'ipquery-charts',IPQUERY_WP_URL . 'assets/js/ipquery-charts.js', [ 'chartjs', 'jquery' ],                 IPQUERY_WP_VERSION, true );

        wp_localize_script( 'ipquery-maps', 'IpQueryData', [
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'ipquery_ajax' ),
            'heatmapAction'=> 'ipquery_heatmap_data',
            'chartAction'  => 'ipquery_chart_data',
        ] );
    }

    // -------------------------------------------------------------------------
    // Page callbacks – delegate rendering to view files.
    // -------------------------------------------------------------------------

    public static function page_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'ipquery-wp' ) );
        }
        include IPQUERY_WP_DIR . 'admin/views/dashboard.php';
    }

    public static function page_visitors(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'ipquery-wp' ) );
        }
        include IPQUERY_WP_DIR . 'admin/views/visitors.php';
    }

    public static function page_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'ipquery-wp' ) );
        }
        include IPQUERY_WP_DIR . 'admin/views/settings.php';
    }

    // -------------------------------------------------------------------------
    // Settings save handler.
    // -------------------------------------------------------------------------

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

        update_option( 'ipquery_wp_settings', [
            'tracking_enabled'   => ! empty( $_POST['tracking_enabled'] ),
            'track_logged_in'    => ! empty( $_POST['track_logged_in'] ),
            'track_admins'       => ! empty( $_POST['track_admins'] ),
            'excluded_ips'       => sanitize_textarea_field( wp_unslash( $_POST['excluded_ips'] ?? '' ) ),
            'retention_days'     => max( 1, (int) ( $_POST['retention_days'] ?? 90 ) ),
            'lookup_private_ips' => ! empty( $_POST['lookup_private_ips'] ),
        ] );

        add_action( 'admin_notices', static function () {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'ipquery-wp' ) . '</p></div>';
        } );
    }

    // -------------------------------------------------------------------------
    // Action handlers.
    // -------------------------------------------------------------------------

    public static function handle_delete_ip(): void {
        check_admin_referer( 'ipquery_delete_ip' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'ipquery-wp' ) );
        }
        $ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) );
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            IpQuery_DB::delete_ip( $ip );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&deleted=1' ) );
        exit;
    }

    public static function handle_purge(): void {
        check_admin_referer( 'ipquery_purge' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'ipquery-wp' ) );
        }
        $days = max( 1, (int) ( $_POST['days'] ?? 90 ) );
        $deleted = IpQuery_DB::delete_old_records( $days );
        wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&purged=' . $deleted ) );
        exit;
    }

    public static function handle_manual_lookup(): void {
        check_admin_referer( 'ipquery_manual_lookup' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'ipquery-wp' ) );
        }
        $ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) );
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&lookup_error=invalid' ) );
            exit;
        }

        try {
            $client   = new IpQueryClient();
            $response = $client->getIpData( $ip );
            $row = [
                'ip'           => $ip,
                'country'      => $response->location?->country     ?? null,
                'country_code' => $response->location?->countryCode ?? null,
                'city'         => $response->location?->city        ?? null,
                'state'        => $response->location?->state       ?? null,
                'zipcode'      => $response->location?->zipcode     ?? null,
                'latitude'     => $response->location?->latitude    ?? null,
                'longitude'    => $response->location?->longitude   ?? null,
                'timezone'     => $response->location?->timezone    ?? null,
                'asn'          => $response->isp?->asn              ?? null,
                'org'          => $response->isp?->org              ?? null,
                'isp'          => $response->isp?->isp              ?? null,
                'is_mobile'     => (int) ( $response->risk?->isMobile     ?? 0 ),
                'is_vpn'        => (int) ( $response->risk?->isVpn        ?? 0 ),
                'is_tor'        => (int) ( $response->risk?->isTor        ?? 0 ),
                'is_proxy'      => (int) ( $response->risk?->isProxy      ?? 0 ),
                'is_datacenter' => (int) ( $response->risk?->isDatacenter ?? 0 ),
                'risk_score'    => $response->risk?->riskScore ?? 0,
            ];
            IpQuery_DB::upsert( $row );
            wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&lookup_ok=1' ) );
        } catch ( IpQueryException $e ) {
            wp_safe_redirect( admin_url( 'admin.php?page=ipquery-visitors&lookup_error=' . rawurlencode( $e->getMessage() ) ) );
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // AJAX endpoints.
    // -------------------------------------------------------------------------

    public static function ajax_chart_data(): void {
        check_ajax_referer( 'ipquery_ajax', 'nonce' );
        $data = [
            'countries' => IpQuery_DB::get_top_countries( 15 ),
            'risk'      => IpQuery_DB::get_risk_counts(),
            'totals'    => [
                'visits'     => IpQuery_DB::get_total_visits(),
                'unique_ips' => IpQuery_DB::get_unique_ips(),
            ],
        ];
        wp_send_json_success( $data );
    }

    public static function ajax_heatmap_data(): void {
        check_ajax_referer( 'ipquery_ajax', 'nonce' );
        $points = IpQuery_DB::get_coordinates_for_heatmap( 500 );
        wp_send_json_success( $points );
    }

    public static function run_cleanup(): void {
        $settings     = get_option( 'ipquery_wp_settings', [] );
        $days         = (int) ( $settings['retention_days'] ?? 90 );
        IpQuery_DB::delete_old_records( $days );
    }
}
