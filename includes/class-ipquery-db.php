<?php

defined( 'ABSPATH' ) || exit;

class IpQuery_DB {

    const DB_VERSION = '1.0.0';

    public static function install(): void {
        global $wpdb;

        $table   = $wpdb->prefix . IPQUERY_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip            VARCHAR(45)         NOT NULL,
            country       VARCHAR(100)        DEFAULT NULL,
            country_code  VARCHAR(10)         DEFAULT NULL,
            city          VARCHAR(100)        DEFAULT NULL,
            state         VARCHAR(100)        DEFAULT NULL,
            zipcode       VARCHAR(20)         DEFAULT NULL,
            latitude      DECIMAL(10,7)       DEFAULT NULL,
            longitude     DECIMAL(10,7)       DEFAULT NULL,
            timezone      VARCHAR(100)        DEFAULT NULL,
            asn           VARCHAR(50)         DEFAULT NULL,
            org           VARCHAR(255)        DEFAULT NULL,
            isp           VARCHAR(255)        DEFAULT NULL,
            is_mobile     TINYINT(1)          DEFAULT 0,
            is_vpn        TINYINT(1)          DEFAULT 0,
            is_tor        TINYINT(1)          DEFAULT 0,
            is_proxy      TINYINT(1)          DEFAULT 0,
            is_datacenter TINYINT(1)          DEFAULT 0,
            risk_score    SMALLINT(3)         DEFAULT 0,
            first_seen    DATETIME            NOT NULL,
            last_seen     DATETIME            NOT NULL,
            visit_count   BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY   ip (ip),
            KEY          country_code (country_code),
            KEY          last_seen (last_seen),
            KEY          risk_score (risk_score)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        update_option( 'ipquery_db_version', self::DB_VERSION );

        // Default settings on first install.
        if ( false === get_option( 'ipquery_settings' ) ) {
            update_option( 'ipquery_settings', [
                'tracking_enabled'       => true,
                'track_logged_in'        => false,
                'track_admins'           => false,
                'excluded_ips'           => '',
                'retention_days'         => 90,
                'lookup_private_ips'     => false,
            ] );
        }
    }

    public static function deactivate(): void {}

    public static function uninstall(): void {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    // -------------------------------------------------------------------------
    // Upsert – insert new or increment existing row for an IP address.
    // -------------------------------------------------------------------------
    public static function upsert( array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        $now   = current_time( 'mysql' );

        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, visit_count FROM {$table} WHERE ip = %s", $data['ip'] ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        if ( $existing ) {
            $wpdb->update(
                $table,
                [ 'last_seen' => $now, 'visit_count' => $existing->visit_count + 1 ],
                [ 'id' => $existing->id ],
                [ '%s', '%d' ],
                [ '%d' ]
            );
        } else {
            $data['first_seen'] = $now;
            $data['last_seen']  = $now;
            $wpdb->insert( $table, $data );
        }
    }

    // -------------------------------------------------------------------------
    // Stats helpers used by the admin views.
    // -------------------------------------------------------------------------

    public static function get_total_visits(): int {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        return (int) $wpdb->get_var( "SELECT SUM(visit_count) FROM {$table}" ); // phpcs:ignore
    }

    public static function get_unique_ips(): int {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
    }

    public static function get_risk_counts(): array {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        return [
            'vpn'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_vpn = 1" ),        // phpcs:ignore
            'proxy'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_proxy = 1" ),      // phpcs:ignore
            'tor'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_tor = 1" ),        // phpcs:ignore
            'datacenter' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_datacenter = 1" ), // phpcs:ignore
            'mobile'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_mobile = 1" ),     // phpcs:ignore
        ];
    }

    public static function get_top_countries( int $limit = 10 ): array {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        return $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT country, country_code, SUM(visit_count) AS visits
                 FROM {$table}
                 WHERE country_code IS NOT NULL
                 GROUP BY country_code
                 ORDER BY visits DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function get_top_cities( int $limit = 10 ): array {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        return $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT city, country, country_code, SUM(visit_count) AS visits
                 FROM {$table}
                 WHERE city IS NOT NULL AND city != ''
                 GROUP BY city, country_code
                 ORDER BY visits DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function get_coordinates_for_heatmap( int $limit = 500 ): array {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        return $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT latitude, longitude, visit_count AS intensity
                 FROM {$table}
                 WHERE latitude IS NOT NULL AND longitude IS NOT NULL
                 ORDER BY visit_count DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function get_visitors( array $args = [] ): array {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;

        $defaults = [
            'per_page'   => 25,
            'page'       => 1,
            'orderby'    => 'last_seen',
            'order'      => 'DESC',
            'search'     => '',
            'country_code' => '',
            'risk_filter'  => '',
        ];
        $args = wp_parse_args( $args, $defaults );

        $where  = [];
        $values = [];

        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[]  = '(ip LIKE %s OR city LIKE %s OR country LIKE %s OR isp LIKE %s)';
            $values   = array_merge( $values, [ $like, $like, $like, $like ] );
        }
        if ( ! empty( $args['country_code'] ) ) {
            $where[]  = 'country_code = %s';
            $values[] = $args['country_code'];
        }
        if ( ! empty( $args['risk_filter'] ) ) {
            $allowed_flags = [ 'is_vpn', 'is_proxy', 'is_tor', 'is_datacenter', 'is_mobile' ];
            if ( in_array( $args['risk_filter'], $allowed_flags, true ) ) {
                $where[] = $args['risk_filter'] . ' = 1';
            }
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $allowed_orderby = [ 'last_seen', 'first_seen', 'visit_count', 'country', 'risk_score', 'ip' ];
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_seen';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];

        $query_values = array_merge( $values, [ (int) $args['per_page'], $offset ] );

        $rows = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore
                $query_values
            ),
            ARRAY_A
        );

        $total = (int) $wpdb->get_var( // phpcs:ignore
            $values
                ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where_sql}", $values ) // phpcs:ignore
                : "SELECT COUNT(*) FROM {$table}" // phpcs:ignore
        );

        return [ 'rows' => $rows ?: [], 'total' => $total ];
    }

    public static function delete_old_records( int $days ): int {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        $wpdb->query( // phpcs:ignore
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        return (int) $wpdb->rows_affected;
    }

    public static function delete_ip( string $ip ): void {
        global $wpdb;
        $table = $wpdb->prefix . IPQUERY_TABLE;
        $wpdb->delete( $table, [ 'ip' => $ip ], [ '%s' ] );
    }
}
