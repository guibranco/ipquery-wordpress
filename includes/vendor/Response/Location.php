<?php

namespace GuiBranco\IpQuery\Response;

defined( 'ABSPATH' ) || exit;

class Location {
    public ?string $country      = null;
    public ?string $countryCode  = null;
    public ?string $city         = null;
    public ?string $state        = null;
    public ?string $zipcode      = null;
    public ?float  $latitude     = null;
    public ?float  $longitude    = null;
    public ?string $timezone     = null;
    public ?string $localtime    = null;

    public static function fromArray( array $data ): self {
        $obj              = new self();
        $obj->country     = $data['country']      ?? null;
        $obj->countryCode = $data['country_code'] ?? null;
        $obj->city        = $data['city']         ?? null;
        $obj->state       = $data['state']        ?? null;
        $obj->zipcode     = $data['zipcode']      ?? null;
        $obj->timezone    = $data['timezone']     ?? null;
        $obj->localtime   = $data['localtime']    ?? null;

        if ( isset( $data['latitude'] ) && is_numeric( $data['latitude'] ) ) {
            $obj->latitude = (float) $data['latitude'];
        }
        if ( isset( $data['longitude'] ) && is_numeric( $data['longitude'] ) ) {
            $obj->longitude = (float) $data['longitude'];
        }
        return $obj;
    }
}
