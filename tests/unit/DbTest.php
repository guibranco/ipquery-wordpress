<?php

declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IpQuery_DB::class)]
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
        return new class {
            public string $prefix = 'wp_';

            public function prepare( string $query, ...$args ): string {
                return $query;
            }

            public function get_results( string $query, string $output = ARRAY_A ): array {
                return [];
            }

            public function get_var( string $query ): string {
                return '0';
            }

            public function esc_like( string $text ): string {
                return addcslashes( $text, '_%\\' );
            }
        };
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
        $wpdb = new class {
            public string $prefix      = 'wp_';
            public string $lastQuery   = '';

            public function prepare( string $query, ...$args ): string {
                $this->lastQuery = $query;
                return $query;
            }

            public function get_results( string $query, string $output = ARRAY_A ): array {
                return [];
            }

            public function get_var( string $query ): string {
                return '0';
            }

            public function esc_like( string $text ): string {
                return $text;
            }
        };
        $GLOBALS['wpdb'] = $wpdb;

        Functions\when( 'wp_parse_args' )->alias(
            static function ( array $args, array $defaults ): array {
                return array_merge( $defaults, $args );
            }
        );

        IpQuery_DB::get_visitors( ['orderby' => 'malicious_column; DROP TABLE--'] );

        $this->assertStringContainsString( 'last_seen', $wpdb->lastQuery );
        $this->assertStringNotContainsString( 'malicious_column', $wpdb->lastQuery );
    }

    public function test_get_visitors_sanitises_order_direction(): void {
        $wpdb = new class {
            public string $prefix    = 'wp_';
            public string $lastQuery = '';

            public function prepare( string $query, ...$args ): string {
                $this->lastQuery = $query;
                return $query;
            }

            public function get_results( string $query, string $output = ARRAY_A ): array {
                return [];
            }

            public function get_var( string $query ): string {
                return '0';
            }

            public function esc_like( string $text ): string {
                return $text;
            }
        };
        $GLOBALS['wpdb'] = $wpdb;

        Functions\when( 'wp_parse_args' )->alias(
            static function ( array $args, array $defaults ): array {
                return array_merge( $defaults, $args );
            }
        );

        IpQuery_DB::get_visitors( ['order' => 'INVALID; DROP TABLE--'] );

        $this->assertStringContainsString( ' DESC', $wpdb->lastQuery );
        $this->assertStringNotContainsString( 'INVALID', $wpdb->lastQuery );
    }
}
