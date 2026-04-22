<?php

namespace GuiBranco\IpQuery\Response;

defined( 'ABSPATH' ) || exit;

class Risk {
    public ?bool $isMobile     = null;
    public ?bool $isVpn        = null;
    public ?bool $isTor        = null;
    public ?bool $isProxy      = null;
    public ?bool $isDatacenter = null;
    public ?int  $riskScore    = null;

    public static function fromArray( array $data ): self {
        $obj               = new self();
        $obj->isMobile     = isset( $data['is_mobile'] )     ? (bool) $data['is_mobile']     : null;
        $obj->isVpn        = isset( $data['is_vpn'] )        ? (bool) $data['is_vpn']        : null;
        $obj->isTor        = isset( $data['is_tor'] )        ? (bool) $data['is_tor']        : null;
        $obj->isProxy      = isset( $data['is_proxy'] )      ? (bool) $data['is_proxy']      : null;
        $obj->isDatacenter = isset( $data['is_datacenter'] ) ? (bool) $data['is_datacenter'] : null;

        if ( isset( $data['risk_score'] ) && is_int( $data['risk_score'] ) ) {
            $obj->riskScore = $data['risk_score'];
        }
        return $obj;
    }
}
