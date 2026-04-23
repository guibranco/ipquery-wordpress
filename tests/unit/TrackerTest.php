<?php

declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * @covers IpQuery_Tracker
 */
class TrackerTest extends TestCase {

    private array $serverBackup = [];

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        $this->serverBackup = $_SERVER;
        foreach ( ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key ) {
            unset( $_SERVER[ $key ] );
        }
    }

    protected function tearDown(): void {
        $_SERVER = $this->serverBackup;
        Monkey\tearDown();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // get_client_ip() — pure PHP, no WordPress dependencies
    // -------------------------------------------------------------------------

    public function test_cloudflare_header_takes_priority(): void {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
        $_SERVER['HTTP_X_REAL_IP']        = '5.6.7.8';
        $_SERVER['REMOTE_ADDR']           = '9.10.11.12';

        $this->assertSame( '1.2.3.4', IpQuery_Tracker::get_client_ip() );
    }

    public function test_x_real_ip_used_when_no_cloudflare(): void {
        $_SERVER['HTTP_X_REAL_IP'] = '5.6.7.8';
        $_SERVER['REMOTE_ADDR']    = '9.10.11.12';

        $this->assertSame( '5.6.7.8', IpQuery_Tracker::get_client_ip() );
    }

    public function test_x_forwarded_for_returns_first_ip_in_list(): void {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 5.6.7.8, 9.10.11.12';
        $_SERVER['REMOTE_ADDR']          = '127.0.0.1';

        $this->assertSame( '1.2.3.4', IpQuery_Tracker::get_client_ip() );
    }

    public function test_x_forwarded_for_strips_whitespace(): void {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '  1.2.3.4 , 5.6.7.8';

        $this->assertSame( '1.2.3.4', IpQuery_Tracker::get_client_ip() );
    }

    public function test_remote_addr_is_final_fallback(): void {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.42';

        $this->assertSame( '203.0.113.42', IpQuery_Tracker::get_client_ip() );
    }

    public function test_invalid_ip_in_cf_header_falls_through_to_next_candidate(): void {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = 'not-an-ip';
        $_SERVER['REMOTE_ADDR']           = '1.2.3.4';

        $this->assertSame( '1.2.3.4', IpQuery_Tracker::get_client_ip() );
    }

    public function test_ipv6_address_is_accepted(): void {
        $_SERVER['REMOTE_ADDR'] = '2001:db8::1';

        $this->assertSame( '2001:db8::1', IpQuery_Tracker::get_client_ip() );
    }

    public function test_returns_empty_string_when_no_valid_ip_present(): void {
        // All candidates absent.
        $this->assertSame( '', IpQuery_Tracker::get_client_ip() );
    }

    // -------------------------------------------------------------------------
    // maybe_track() — early-return conditions (WordPress functions mocked)
    // -------------------------------------------------------------------------

    public function test_maybe_track_skips_ajax_requests(): void {
        $this->expectNotToPerformAssertions();

        Functions\when( 'wp_doing_ajax' )->justReturn( true );
        Functions\when( 'wp_doing_cron' )->justReturn( false );
        Functions\expect( 'is_user_logged_in' )->never();

        IpQuery_Tracker::init( ['tracking_enabled' => true] );
        IpQuery_Tracker::maybe_track();
    }

    public function test_maybe_track_skips_cron_requests(): void {
        $this->expectNotToPerformAssertions();

        Functions\when( 'wp_doing_ajax' )->justReturn( false );
        Functions\when( 'wp_doing_cron' )->justReturn( true );
        Functions\expect( 'is_user_logged_in' )->never();

        IpQuery_Tracker::init( ['tracking_enabled' => true] );
        IpQuery_Tracker::maybe_track();
    }

    public function test_maybe_track_skips_ip_on_exclusion_list(): void {
        $this->expectNotToPerformAssertions();

        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        Functions\when( 'wp_doing_ajax' )->justReturn( false );
        Functions\when( 'wp_doing_cron' )->justReturn( false );
        Functions\when( 'is_user_logged_in' )->justReturn( false );
        Functions\expect( 'get_transient' )->never();

        IpQuery_Tracker::init( [
            'tracking_enabled'   => true,
            'excluded_ips'       => "1.2.3.4\n5.6.7.8",
            'lookup_private_ips' => true,
        ] );
        IpQuery_Tracker::maybe_track();
    }

    public function test_maybe_track_skips_private_ip_when_setting_off(): void {
        $this->expectNotToPerformAssertions();

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        Functions\when( 'wp_doing_ajax' )->justReturn( false );
        Functions\when( 'wp_doing_cron' )->justReturn( false );
        Functions\when( 'is_user_logged_in' )->justReturn( false );
        Functions\expect( 'get_transient' )->never();

        IpQuery_Tracker::init( [
            'tracking_enabled'   => true,
            'excluded_ips'       => '',
            'lookup_private_ips' => false,
        ] );
        IpQuery_Tracker::maybe_track();
    }

    public function test_maybe_track_registers_shutdown_on_transient_miss(): void {
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        Functions\when( 'wp_doing_ajax' )->justReturn( false );
        Functions\when( 'wp_doing_cron' )->justReturn( false );
        Functions\when( 'is_user_logged_in' )->justReturn( false );
        Functions\when( 'get_transient' )->justReturn( false );

        $shutdownRegistered = false;
        Functions\when( 'add_action' )->alias(
            static function ( string $hook, $callback ) use ( &$shutdownRegistered ): void {
                if ( $hook === 'shutdown' ) {
                    $shutdownRegistered = true;
                }
            }
        );

        IpQuery_Tracker::init( [
            'tracking_enabled'   => true,
            'excluded_ips'       => '',
            'lookup_private_ips' => false,
        ] );
        IpQuery_Tracker::maybe_track();

        $this->assertTrue( $shutdownRegistered, 'Expected a shutdown action to be registered for API lookup.' );
    }
}
