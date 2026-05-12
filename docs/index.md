---
title: Home
nav_order: 1
permalink: /
---

# IpQuery for WordPress

Track and analyse visitor IP data directly from your WordPress admin panel — see where your visitors come from on an interactive heatmap, identify VPN and proxy traffic, and drill into per-IP details.

[Get started — Installation]({% link installation.md %}){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 }
[View on GitHub](https://github.com/guibranco/ipquery-wordpress){: .btn .fs-5 .mb-4 .mb-md-0 target="_blank" rel="noopener" }

---

## Features

| Feature | Description |
|---|---|
| **World heatmap** | Leaflet.js map with a heat layer showing visitor density by coordinates |
| **Country breakdown** | Horizontal bar chart of top countries by visit count |
| **Risk analysis** | Per-IP flags for VPN, proxy, Tor, datacenter, and mobile; risk score 0–100 |
| **Visitors table** | Sortable, searchable, filterable list of all tracked IPs with full IP details |
| **Manual IP lookup** | Query any IP address on demand from the Visitors screen |
| **Automatic tracking** | Hooks into `wp` action; deferred to `shutdown` so page load is never blocked |
| **Smart caching** | WordPress transients cache each IP lookup for 1 hour — one API call per IP per hour |
| **Data retention** | Configurable retention period with automatic daily cleanup via WP-Cron |
| **Privacy controls** | Exclude IPs, skip logged-in users or admins, and disable tracking at any time |
| **GDPR erasure by country** | Bulk-delete all visitor records for one or more countries; confirmation dialog and audit log included |

## Powered by ipquery-php

This plugin uses [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) as its API client library. The library is bundled inside the plugin (no Composer required on your server) and communicates with the [IpQuery API](https://ipquery.io) — a free IP intelligence service providing location, ISP, and risk data.

```
Visitor request
      │
      ▼
WordPress (wp action)
      │  deferred to shutdown
      ▼
IpQueryClient::getIpData($ip)   ← guibranco/ipquery-php
      │
      ▼
https://api.ipquery.io/{ip}
      │
      ▼
IpQueryResponse { location, isp, risk }
      │
      ▼
wp_ipquery_visitors (DB table)
```

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher
- cURL PHP extension
- MySQL / MariaDB (standard with any WordPress install)
