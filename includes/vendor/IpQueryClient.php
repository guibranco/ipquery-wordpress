<?php

namespace GuiBranco\IpQuery;

defined( 'ABSPATH' ) || exit;

use GuiBranco\IpQuery\Response\IpQueryResponse;

class IpQueryClient implements IIpQueryClient {

    private string $baseUrl = 'https://api.ipquery.io';

    public function getMyIpData(): IpQueryResponse {
        $json = $this->makeRequest( '' );
        return IpQueryResponse::fromJson( $json );
    }

    public function getIpData( string $ip ): IpQueryResponse {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            throw new IpQueryException( "Invalid IP address: {$ip}" );
        }
        $json = $this->makeRequest( '/' . rawurlencode( $ip ) );
        return IpQueryResponse::fromJson( $json );
    }

    public function getMultipleIpData( array $ips ): array {
        foreach ( $ips as $ip ) {
            if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                throw new IpQueryException( "Invalid IP address: {$ip}" );
            }
        }
        $endpoint = '/' . implode( ',', array_map( 'rawurlencode', $ips ) );
        $json     = $this->makeRequest( $endpoint );
        $rows     = json_decode( $json, true );
        if ( ! is_array( $rows ) ) {
            return [];
        }
        return array_map( [ IpQueryResponse::class, 'fromArray' ], $rows );
    }

    protected function makeRequest( string $endpoint ): string {
        $url = $this->baseUrl . $endpoint . '?format=json';
        $ch  = curl_init( $url );

        curl_setopt_array( $ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'IpQuery PHP Client/1.0',
        ] );

        $response = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error    = curl_error( $ch );
        curl_close( $ch );

        if ( $response === false || $error ) {
            throw new IpQueryException( "cURL error: {$error}" );
        }
        if ( $httpCode !== 200 ) {
            throw new IpQueryException( "HTTP {$httpCode} from IpQuery API" );
        }
        return $response;
    }
}
