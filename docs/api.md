---
title: IpQuery API
nav_order: 7
---

# IpQuery API

The plugin communicates with the [IpQuery API](https://ipquery.io) via the [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) library. This page documents the API endpoints and response fields used by the plugin.

---

## Endpoint

All requests go to:

```
https://api.ipquery.io/{ip}?format=json
```

- **No API key required** for standard usage.
- Responses are JSON objects.
- The library sets a `User-Agent` of `IpQuery PHP Client/1.0`.
- Timeouts: 5 s connection, 10 s total.

---

## Response structure

A successful response looks like this:

```json
{
  "ip": "203.0.113.42",
  "isp": {
    "asn": "AS15169",
    "org": "Google LLC",
    "isp": "Google"
  },
  "location": {
    "country": "United States",
    "country_code": "US",
    "city": "Mountain View",
    "state": "California",
    "zipcode": "94043",
    "latitude": 37.4056,
    "longitude": -122.0775,
    "timezone": "America/Los_Angeles",
    "localtime": "2026-04-22T10:30:00"
  },
  "risk": {
    "is_mobile": false,
    "is_vpn": false,
    "is_tor": false,
    "is_proxy": false,
    "is_datacenter": true,
    "risk_score": 35
  }
}
```

---

## Field reference

### `isp` object

| Field | Type | Description |
|---|---|---|
| `asn` | string | Autonomous System Number (e.g. `AS15169`) |
| `org` | string | Organisation name registered to the ASN |
| `isp` | string | Internet Service Provider name |

### `location` object

| Field | Type | Description |
|---|---|---|
| `country` | string | Full country name |
| `country_code` | string | ISO 3166-1 alpha-2 code (e.g. `US`, `BR`) |
| `city` | string | City name |
| `state` | string | State or region name |
| `zipcode` | string | Postal/ZIP code |
| `latitude` | float | Decimal latitude |
| `longitude` | float | Decimal longitude |
| `timezone` | string | IANA timezone identifier (e.g. `America/New_York`) |
| `localtime` | string | Local time at the IP's location (ISO 8601) |

### `risk` object

| Field | Type | Description |
|---|---|---|
| `is_mobile` | bool | IP belongs to a mobile carrier |
| `is_vpn` | bool | IP is a known VPN exit node |
| `is_tor` | bool | IP is a Tor exit node |
| `is_proxy` | bool | IP is a known open proxy |
| `is_datacenter` | bool | IP is assigned to a datacenter or cloud provider |
| `risk_score` | int | Composite risk score from 0 (clean) to 100 (high risk) |

---

## PHP library classes

The library is bundled in `includes/vendor/` and uses the namespace `GuiBranco\IpQuery`.

### `IpQueryClient`

The main client class. No constructor arguments required.

```php
use GuiBranco\IpQuery\IpQueryClient;

$client = new IpQueryClient();

// Single IP
$response = $client->getIpData('203.0.113.42');

// Multiple IPs (one API call)
$responses = $client->getMultipleIpData(['203.0.113.42', '198.51.100.1']);

// Server's own IP
$self = $client->getMyIpData();
```

### `IpQueryResponse`

Returned by all three methods. Properties:

```php
$response->ip        // string
$response->isp       // GuiBranco\IpQuery\Response\Isp
$response->location  // GuiBranco\IpQuery\Response\Location
$response->risk      // GuiBranco\IpQuery\Response\Risk
```

### `IpQueryException`

Thrown by `IpQueryClient` on network errors or non-200 HTTP responses. Extends `\RuntimeException`.

```php
use GuiBranco\IpQuery\IpQueryException;

try {
    $response = $client->getIpData($ip);
} catch (IpQueryException $e) {
    error_log('[IpQuery WP] ' . $e->getMessage());
}
```

---

## Rate limiting

The IpQuery API is free for standard usage. The plugin avoids excessive calls by:

1. Caching each lookup in a WordPress transient for **1 hour**.
2. Only calling the API from the `shutdown` action (after response is sent).
3. Batch requests are available via `getMultipleIpData()` if you need to bulk-enrich IPs programmatically.

For high-traffic sites, consider raising the transient TTL by filtering the value via a custom plugin or `mu-plugin`.

---

## Further reading

- [ipquery.io](https://ipquery.io) — API documentation and usage information
- [guibranco/ipquery-php on GitHub](https://github.com/guibranco/ipquery-php) — source code, tests, and issue tracker for the PHP client library
