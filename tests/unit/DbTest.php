<?php

declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * @covers IpQuery_DB
 */
class DbTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // DB_VERSION constant
    // -------------------------------------------------------------------------

    public function test_db_version_constant_is_defined(): void {
        $this->assertSame( '1.0.0', IpQuery_DB::DB_VERSION );
    }

    // -------------------------------------------------------------------------
    // get_visitors() — args normalisation and return structure
    // -------------------------------------------------------------------------

    private function makeWpdb(): object {
        $wpdb         = new stdClass();
        $wpdb->prefix = 'wp_';

        $wpdb->prepare = static function ( string $query, ...$args ): string {
            return $query; // Return query as-is for testing.
        };
        $wpdb->get_results = static function ( string $query, string $output = ARRAY_A ): array {
            return [];
        };
        $wpdb->get_var = static function ( string $query ): string {
            return '0';
        };
        $wpdb->esc_like = static function ( string $text ): string {
            return addcslashes( $text, '_%\\' );
        };

        return $wpdb;
    }

    public function test_get_visitors_returns_rows_and_total_keys(): void {
        $GLOBALS['wpdb'] = $this->makeWpdb();

        Functions\when( 'wp_parse_args' )->alias(
            static function ( array $args, array $defaults ): array {
                return array_merge( $defaults, $args );
            }
        );

        $result = IpQuery_DB::get_visitors( [] );

        $this->assertArrayHasKey( 'rows', $result );
        $this->assertArrayHasKey( 'total', $result );
        $this->assertIsArray( $result['rows'] );
        $this->assertIsInt( $result['total'] );
    }

    public function test_get_visitors_accepts_valid_orderby_columns(): void {
        $GLOBALS['wpdb'] = $this->makeWpdb();

        Functions\when( 'wp_parse_args' )->alias(
            static function ( array $args, array $defaults ): array {
                return array_merge( $defaults, $args );
            }
        );

        $validColumns = ['last_seen', 'first_seen', 'visit_count', 'country', 'risk_score', 'ip'];

        foreach ( $validColumns as $column ) {
            $result = IpQuery_DB::get_visitors( ['orderby' => $column] );
            $this->assertArrayHasKey( 'rows', $result, "Expected 'rows' key for orderby=$column" );
        }
    }

    public function test_get_visitors_falls_back_to_last_seen_for_invalid_orderby(): void {
        $capturedQuery = '';

        $wpdb             = new stdClass();
        $wpdb->prefix     = 'wp_';
        $wpdb->prepare    = static function ( string $query, ...$args ) use ( &$capturedQuery ): string {
            $capturedQuery = $query;
            return $query;
        };
        $wpdb->get_results = static function (): array { return []; };
        $wpdb->get_var     = static function (): string { return '0'; };
        $wpdb->esc_like    = static function ( string $t ): string { return $t; };
        $GLOBALS['wpdb']  = $wpdb;

        Functions\when( 'wp_parse_args' )->alias(
            static function ( array $args, array $defaults ): array {
                return array_merge( $defaults, $args );
            }
        );

        IpQuery_DB::get_visitors( ['orderby' => 'malicious_column; DROP TABLE--'] );

        $this->assertStringContainsString( 'last_seen', $capturedQuery );
        $this->assertStringNotContainsString( 'malicious_column', $capturedQuery );
    }

    public function test_get_visitors_sanitises_order_direction(): void {
        $capturedQuery = '';

        $wpdb             = new stdClass();
        $wpdb->prefix     = 'wp_';
        $wpdb->prepare    = static function ( string $query, ...$args ) use ( &$capturedQuery ): string {
            $capturedQuery = $query;
            return $query;
        };
        $wpdb->get_results = static function (): array { return []; };
        $wpdb->get_var     = static function (): string { return '0'; };
        $wpdb->esc_like    = static function ( string $t ): string { return $t; };
        $GLOBALS['wpdb']  = $wpdb;

        Functions\when( 'wp_parse_args' )->alias(
            static function ( array $args, array $defaults ): array {
                return array_merge( $defaults, $args );
            }
        );

        IpQuery_DB::get_visitors( ['order' => 'INVALID; DROP TABLE--'] );

        $this->assertStringContainsString( ' DESC', $capturedQuery );
        $this->assertStringNotContainsString( 'INVALID', $capturedQuery );
    }
}
