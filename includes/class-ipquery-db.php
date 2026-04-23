<?php
/**
 * Database helper — table creation, upsert, and query methods.
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles all direct database interactions for the IpQuery plugin.
 */
class IpQuery_DB {

	const DB_VERSION = '1.0.0';

	/**
	 * Creates the visitors table and writes default settings on first activation.
	 *
	 * @return void
	 */
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
        ) {$charset};"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'ipquery_db_version', self::DB_VERSION );

		if ( false === get_option( 'ipquery_settings' ) ) {
			update_option(
				'ipquery_settings',
				array(
					'tracking_enabled'   => true,
					'track_logged_in'    => false,
					'track_admins'       => false,
					'excluded_ips'       => '',
					'retention_days'     => 90,
					'lookup_private_ips' => false,
				)
			);
		}
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {}

	/**
	 * Drops the visitors table and removes all options on uninstall.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	// -------------------------------------------------------------------------
	// Upsert – insert new or increment existing row for an IP address.
	// -------------------------------------------------------------------------

	/**
	 * Inserts a new visitor row or updates visit_count and last_seen for an existing IP.
	 *
	 * @param array<string,mixed> $data Visitor data keyed by column name.
	 * @return void
	 */
	public static function upsert( array $data ): void {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "SELECT id, visit_count FROM {$table} WHERE ip = %s", $data['ip'] ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $existing ) {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$table,
				array(
					'last_seen'   => $now,
					'visit_count' => $existing->visit_count + 1,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
		} else {
			$data['first_seen'] = $now;
			$data['last_seen']  = $now;
			$wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	// -------------------------------------------------------------------------
	// Stats helpers used by the admin views.
	// -------------------------------------------------------------------------

	/**
	 * Returns the total visit count across all tracked IPs.
	 *
	 * @return int
	 */
	public static function get_total_visits(): int {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		return (int) $wpdb->get_var( "SELECT SUM(visit_count) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Returns the number of unique IPs in the database.
	 *
	 * @return int
	 */
	public static function get_unique_ips(): int {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Returns counts for each risk flag (VPN, proxy, Tor, datacenter, mobile).
	 *
	 * @return array<string,int>
	 */
	public static function get_risk_counts(): array {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		return array(
			'vpn'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_vpn = 1" ),        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'proxy'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_proxy = 1" ),      // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'tor'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_tor = 1" ),        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'datacenter' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_datacenter = 1" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'mobile'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_mobile = 1" ),     // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		);
	}

	/**
	 * Returns the top countries ranked by total visits.
	 *
	 * @param int $limit Maximum rows to return.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_top_countries( int $limit = 10 ): array {
		global $wpdb;
		$table  = $wpdb->prefix . IPQUERY_TABLE;
		$sql    = $wpdb->prepare( "SELECT country, country_code, SUM(visit_count) AS visits FROM {$table} WHERE country_code IS NOT NULL GROUP BY country_code ORDER BY visits DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $result ) ? $result : array();
	}

	/**
	 * Returns the top cities ranked by total visits.
	 *
	 * @param int $limit Maximum rows to return.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_top_cities( int $limit = 10 ): array {
		global $wpdb;
		$table  = $wpdb->prefix . IPQUERY_TABLE;
		$sql    = $wpdb->prepare( "SELECT city, country, country_code, SUM(visit_count) AS visits FROM {$table} WHERE city IS NOT NULL AND city != '' GROUP BY city, country_code ORDER BY visits DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $result ) ? $result : array();
	}

	/**
	 * Returns lat/lng/intensity rows for the heatmap.
	 *
	 * @param int $limit Maximum rows to return.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_coordinates_for_heatmap( int $limit = 500 ): array {
		global $wpdb;
		$table  = $wpdb->prefix . IPQUERY_TABLE;
		$sql    = $wpdb->prepare( "SELECT latitude, longitude, visit_count AS intensity FROM {$table} WHERE latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY visit_count DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $result ) ? $result : array();
	}

	/**
	 * Returns a paginated, filtered, and sorted list of visitors plus the total count.
	 *
	 * @param array<string,mixed> $args Query arguments (per_page, page, orderby, order, search, country_code, risk_filter).
	 * @return array{rows:array<int,array<string,mixed>>,total:int}
	 */
	public static function get_visitors( array $args = array() ): array {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;

		$defaults = array(
			'per_page'     => 25,
			'page'         => 1,
			'orderby'      => 'last_seen',
			'order'        => 'DESC',
			'search'       => '',
			'country_code' => '',
			'risk_filter'  => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$where  = array();
		$values = array();

		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(ip LIKE %s OR city LIKE %s OR country LIKE %s OR isp LIKE %s)';
			$values  = array_merge( $values, array( $like, $like, $like, $like ) );
		}
		if ( ! empty( $args['country_code'] ) ) {
			$where[]  = 'country_code = %s';
			$values[] = $args['country_code'];
		}
		if ( ! empty( $args['risk_filter'] ) ) {
			$allowed_flags = array( 'is_vpn', 'is_proxy', 'is_tor', 'is_datacenter', 'is_mobile' );
			if ( in_array( $args['risk_filter'], $allowed_flags, true ) ) {
				$where[] = $args['risk_filter'] . ' = 1';
			}
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$allowed_orderby = array( 'last_seen', 'first_seen', 'visit_count', 'country', 'risk_score', 'ip' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_seen';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$offset       = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$query_values = array_merge( $values, array( (int) $args['per_page'], $offset ) );

		// Table name and validated orderby/order are safe to interpolate; values use placeholders.
		$rows_sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows     = $wpdb->get_results( $wpdb->prepare( $rows_sql, ...$query_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( $values ) {
			$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$values ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return array(
			'rows'  => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}

	/**
	 * Deletes visitor records older than the given number of days.
	 *
	 * @param int $days Age threshold in days.
	 * @return int Number of rows deleted.
	 */
	public static function delete_old_records( int $days ): int {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		$sql   = $wpdb->prepare( "DELETE FROM {$table} WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->rows_affected;
	}

	/**
	 * Deletes all records for a single IP address.
	 *
	 * @param string $ip The IP address to delete.
	 * @return void
	 */
	public static function delete_ip( string $ip ): void {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		$wpdb->delete( $table, array( 'ip' => $ip ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
