---
title: How Tracking Works
nav_order: 6
---

# How Tracking Works

This page explains the technical flow of how visitor IPs are captured, enriched, and stored.

---

## Hook sequence

```
Browser request
    │
    ▼  WordPress bootstrap
    │
    ▼  init action
    │   IpQuery_Tracker::init() — registers the 'wp' hook
    │
    ▼  wp action (template resolved)
    │   IpQuery_Tracker::maybe_track()
    │    ├─ skip if AJAX / REST / Cron
    │    ├─ skip if logged-in and settings exclude them
    │    ├─ resolve client IP (REMOTE_ADDR / proxy headers)
    │    ├─ skip private IPs (unless setting enabled)
    │    ├─ skip excluded IPs
    │    ├─ if transient exists → bump visit count only
    │    └─ else → register shutdown callback
    │
    ▼  Page renders and is sent to browser
    │
    ▼  shutdown action
        IpQuery_Tracker::lookup_and_store()
         ├─ IpQueryClient::getIpData($ip)  ← ipquery-php library
         │   └─ GET https://api.ipquery.io/{ip}?format=json
         ├─ IpQuery_DB::upsert($row)
         └─ set_transient("ipqwp_{md5($ip)}", 1, HOUR_IN_SECONDS)
```

The critical design choice is the **`shutdown` deferral**: the API call to IpQuery happens *after* the response has been sent to the visitor's browser. This means tracking never adds latency to page loads.

---

## IP resolution

The tracker resolves the real client IP from the following `$_SERVER` keys, in order:

| Priority | Key | Use case |
|---|---|---|
| 1 | `HTTP_CF_CONNECTING_IP` | Cloudflare CDN |
| 2 | `HTTP_X_REAL_IP` | nginx reverse proxy |
| 3 | `HTTP_X_FORWARDED_FOR` | standard proxy header (first value) |
| 4 | `REMOTE_ADDR` | direct connection (fallback) |

Each candidate is validated with `filter_var($ip, FILTER_VALIDATE_IP)` before being used. If no valid IP is found, tracking is silently skipped for that request.

{: .warning }
`HTTP_X_FORWARDED_FOR` can be spoofed by clients. If your server is not behind a trusted reverse proxy, consider whether Cloudflare or another CDN is in front of your WordPress install. The plugin uses the first IP in the header, which is set by the original client.

---

## Transient caching

Each looked-up IP gets a WordPress transient with the key `ipqwp_{md5($ip)}` and a TTL of **1 hour**. Subsequent visits from the same IP within that hour only trigger a lightweight `UPDATE ... SET visit_count = visit_count + 1` query — no API call is made.

After the hour expires the next visit will trigger a fresh API call, which updates all enrichment fields (location, ISP, risk) in the database row.

---

## Database upsert

`IpQuery_DB::upsert()` checks for an existing row with the same IP:

- **New IP** → `INSERT` with all enrichment fields, `first_seen = NOW()`, `visit_count = 1`
- **Existing IP (transient expired)** → `UPDATE` all enrichment fields, `last_seen = NOW()`, `visit_count += 1`
- **Existing IP (transient hit)** → `UPDATE last_seen, visit_count += 1` only (no API call)

---

## What the ipquery-php library provides

The [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) library handles all communication with the IpQuery API. It is bundled inside `includes/vendor/` and does not require Composer on your server.

The library exposes three methods via `IpQueryClient`:

| Method | Description |
|---|---|
| `getMyIpData()` | Returns enrichment data for the server's own outbound IP |
| `getIpData(string $ip)` | Returns enrichment data for a single IP |
| `getMultipleIpData(array $ips)` | Batch lookup; returns an array of response objects |

The plugin uses `getIpData()` for all automatic tracking and manual lookups.

Each call returns an `IpQueryResponse` object with three nested objects:

```php
$response->ip                    // "203.0.113.42"

$response->location->country     // "United States"
$response->location->countryCode // "US"
$response->location->city        // "New York"
$response->location->state       // "New York"
$response->location->latitude    // 40.7128
$response->location->longitude   // -74.0060
$response->location->timezone    // "America/New_York"

$response->isp->asn              // "AS15169"
$response->isp->org              // "Google LLC"
$response->isp->isp              // "Google"

$response->risk->isVpn           // false
$response->risk->isProxy         // false
$response->risk->isTor           // false
$response->risk->isDatacenter    // true
$response->risk->isMobile        // false
$response->risk->riskScore       // 35
```

---

## Error handling

All API calls are wrapped in a `try / catch` for `IpQueryException`. If the API is unreachable, returns a non-200 status, or cURL fails, the exception is written to the PHP error log as:

```
[IpQuery WP] <error message>
```

The failure is silent to visitors and does not affect page rendering. The transient is not set on failure, so the next visit will try the API again.
