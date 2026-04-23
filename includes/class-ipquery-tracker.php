<?php
/**
 * Visitor tracker — captures IP addresses and defers API enrichment to shutdown.
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit;

use GuiBranco\IpQuery\IpQueryClient;
use GuiBranco\IpQuery\IpQueryException;

/**
 * Hooks into WordPress request handling to capture and enrich visitor IPs.
 */
class IpQuery_Tracker {

	/**
	 * Plugin settings loaded from the database.
	 *
	 * @var array<string,mixed>
	 */
	private static array $settings = array();

	/**
	 * Stores settings and registers the tracking hook.
	 *
	 * @param array<string,mixed> $settings Plugin settings array.
	 * @return void
	 */
	public static function init( array $settings ): void {
		self::$settings = $settings;
		add_action( 'wp', array( self::class, 'maybe_track' ), 20 );
	}

	/**
	 * Decides whether to track the current request, then schedules the API lookup.
	 *
	 * @return void
	 */
	public static function maybe_track(): void {
		if ( wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) {
			return;
		}

		$settings = self::$settings;

		if ( is_user_logged_in() ) {
			if ( current_user_can( 'manage_options' ) && empty( $settings['track_admins'] ) ) {
				return;
			}
			if ( ! empty( $settings['track_logged_in'] ) === false ) {
				return;
			}
		}

		$ip = self::get_client_ip();
		if ( ! $ip ) {
			return;
		}

		if ( empty( $settings['lookup_private_ips'] ) && ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return;
		}

		if ( ! empty( $settings['excluded_ips'] ) ) {
			$excluded = array_map( 'trim', explode( "\n", $settings['excluded_ips'] ) );
			if ( in_array( $ip, $excluded, true ) ) {
				return;
			}
		}

		$transient_key = 'ipq_' . md5( $ip );
		if ( get_transient( $transient_key ) ) {
			self::bump_visit( $ip );
			return;
		}

		// Defer the API call to shutdown so it never adds latency to page loads.
		add_action(
			'shutdown',
			static function () use ( $ip, $transient_key ) {
				self::lookup_and_store( $ip, $transient_key );
			}
		);
	}

	/**
	 * Calls the IpQuery API and stores the enriched row; sets the transient on success.
	 *
	 * @param string $ip            Visitor IP address.
	 * @param string $transient_key WordPress transient key for this IP.
	 * @return void
	 */
	private static function lookup_and_store( string $ip, string $transient_key ): void {
		try {
			$client   = new IpQueryClient();
			$response = $client->getIpData( $ip );

			$row = array(
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

			set_transient( $transient_key, 1, HOUR_IN_SECONDS );

		} catch ( IpQueryException $e ) {
			error_log( '[IpQuery WP] ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Increments visit_count and updates last_seen for a cached IP without an API call.
	 *
	 * @param string $ip Visitor IP address.
	 * @return void
	 */
	private static function bump_visit( string $ip ): void {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
		$sql   = $wpdb->prepare( "UPDATE {$table} SET visit_count = visit_count + 1, last_seen = %s WHERE ip = %s", current_time( 'mysql' ), $ip ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	}

	// -------------------------------------------------------------------------
	// Reliably resolve the real visitor IP, respecting common proxy headers.
	// -------------------------------------------------------------------------

	/**
	 * Returns the best-guess real visitor IP, checking proxy headers in priority order.
	 *
	 * @return string Validated IP address, or empty string if none found.
	 */
	public static function get_client_ip(): string {
		$candidates = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $candidates as $key ) {
			$val = isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			if ( ! $val ) {
				continue;
			}
			// X-Forwarded-For can be a comma-separated list; take the first value.
			$ip = trim( explode( ',', $val )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '';
	}
}
