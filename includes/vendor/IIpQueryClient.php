<?php

namespace GuiBranco\IpQuery;

defined( 'ABSPATH' ) || exit;

use GuiBranco\IpQuery\Response\IpQueryResponse;

interface IIpQueryClient {
    public function getMyIpData(): IpQueryResponse;
    public function getIpData( string $ip ): IpQueryResponse;
    public function getMultipleIpData( array $ips ): array;
}
