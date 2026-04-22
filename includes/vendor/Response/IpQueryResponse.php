<?php

namespace GuiBranco\IpQuery\Response;

defined( 'ABSPATH' ) || exit;

class IpQueryResponse {
    public ?string   $ip       = null;
    public ?Isp      $isp      = null;
    public ?Location $location = null;
    public ?Risk     $risk     = null;

    public static function fromJson( string $json ): self {
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) {
            return new self();
        }
        return self::fromArray( $data );
    }

    public static function fromArray( array $data ): self {
        $obj       = new self();
        $obj->ip   = $data['ip'] ?? null;
        $obj->isp  = isset( $data['isp'] )      && is_array( $data['isp'] )      ? Isp::fromArray( $data['isp'] )           : null;
        $obj->location = isset( $data['location'] ) && is_array( $data['location'] ) ? Location::fromArray( $data['location'] ) : null;
        $obj->risk     = isset( $data['risk'] )     && is_array( $data['risk'] )     ? Risk::fromArray( $data['risk'] )         : null;
        return $obj;
    }
}
