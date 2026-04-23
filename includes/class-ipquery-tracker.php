<?php

defined( 'ABSPATH' ) || exit;

use GuiBranco\IpQuery\IpQueryClient;
use GuiBranco\IpQuery\IpQueryException;

class IpQuery_Tracker {

	private static array $settings = array();

	public static function init( array $settings ): void {
		self::$settings = $settings;
		add_action( 'wp', array( self::class, 'maybe_track' ), 20 );
	}

	public static function maybe_track(): void {
		// Only track real page requests, not AJAX/REST/cron.
		if ( wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) {
			return;
		}

		$settings = self::$settings;

		// Respect admin/logged-in exclusion settings.
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

		// Skip private/reserved addresses unless the setting is on.
		if ( empty( $settings['lookup_private_ips'] ) && ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return;
		}

		// Check excluded IPs list.
		if ( ! empty( $settings['excluded_ips'] ) ) {
			$excluded = array_map( 'trim', explode( "\n", $settings['excluded_ips'] ) );
			if ( in_array( $ip, $excluded, true ) ) {
				return;
			}
		}

		// Use a transient so we only hit the API once per IP per hour.
		$transient_key = 'ipq_' . md5( $ip );
		if ( get_transient( $transient_key ) ) {
			// Already looked up recently; just bump the visit count.
			self::bump_visit( $ip );
			return;
		}

		// Perform the API lookup in a deferred shutdown action so it never
		// blocks page rendering.
		add_action(
			'shutdown',
			static function () use ( $ip, $transient_key ) {
				self::lookup_and_store( $ip, $transient_key );
			}
		);
	}

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

			// Cache so we don't re-query the API for 1 hour.
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );

		} catch ( IpQueryException $e ) {
			// Silently log; never crash the site.
			error_log( '[IpQuery WP] ' . $e->getMessage() );
		}
	}

	private static function bump_visit( string $ip ): void {
		global $wpdb;
		$table = $wpdb->prefix . IPQUERY_TABLE;
        $wpdb->query( // phpcs:ignore
			$wpdb->prepare(
                "UPDATE {$table} SET visit_count = visit_count + 1, last_seen = %s WHERE ip = %s", // phpcs:ignore
				current_time( 'mysql' ),
				$ip
			)
		);
	}

	// -------------------------------------------------------------------------
	// Reliably resolve the real visitor IP, respecting common proxy headers.
	// -------------------------------------------------------------------------
	public static function get_client_ip(): string {
		$candidates = array(
			'HTTP_CF_CONNECTING_IP',   // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $candidates as $key ) {
			$val = $_SERVER[ $key ] ?? '';
			if ( ! $val ) {
				continue;
			}
			// X-Forwarded-For can be a comma-separated list; take the first.
			$ip = trim( explode( ',', $val )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '';
	}
}
