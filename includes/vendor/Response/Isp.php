<?php

namespace GuiBranco\IpQuery\Response;

defined( 'ABSPATH' ) || exit;

class Isp {
    public ?string $asn  = null;
    public ?string $org  = null;
    public ?string $isp  = null;

    public static function fromArray( array $data ): self {
        $obj      = new self();
        $obj->asn = isset( $data['asn'] ) && is_string( $data['asn'] ) ? $data['asn'] : null;
        $obj->org = isset( $data['org'] ) && is_string( $data['org'] ) ? $data['org'] : null;
        $obj->isp = isset( $data['isp'] ) && is_string( $data['isp'] ) ? $data['isp'] : null;
        return $obj;
    }
}
