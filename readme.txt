=== IpQuery ===
Contributors: guilhermestracini
Tags: ip, geolocation, analytics, security, heatmap
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.1.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Track and analyse visitor IP data using the IpQuery API. Displays location maps, traffic heatmaps, and VPN/proxy/Tor statistics.

== Description ==

IpQuery enriches every visitor's IP address with real-time geolocation, ISP data, and risk intelligence — powered by the [IpQuery API](https://ipquery.io) via the bundled [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) library.

**Features:**

* **World heatmap** — Interactive Leaflet.js map with a heat layer weighted by visit count
* **Country & city charts** — Horizontal bar chart of top countries; dedicated top-cities table with country flags
* **Risk intelligence** — Per-IP flags for VPN, proxy, Tor, datacenter, and mobile connections
* **Risk scoring** — Numeric risk score (0–100) per IP with colour-coded indicators
* **Visitors table** — Searchable, sortable, filterable table with full enrichment data for every IP
* **Manual lookup** — Instantly enrich any IP address on demand from the admin panel
* **Non-blocking** — API calls run at `shutdown` — visitor page load is never delayed
* **Smart caching** — WordPress transients cache each IP for 1 hour; only one API call per IP per hour
* **Auto-retention** — Configurable data retention with daily WP-Cron cleanup
* **Privacy controls** — Exclude IPs, skip logged-in users or admins, disable tracking at any time

== Installation ==

1. Upload the `ipquery` folder to the `/wp-content/plugins/` directory, or install via the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **IpQuery → Dashboard** to see visitor analytics.
4. Configure tracking options under **IpQuery → Settings**.

== Frequently Asked Questions ==

= Does this plugin require an API key? =

No. The [IpQuery API](https://ipquery.io) is free and requires no API key.

= Does tracking slow down my site? =

No. All API calls are deferred to the WordPress `shutdown` action, which runs after the page response has been sent to the visitor's browser.

= What data is collected? =

For each unique visitor IP the plugin stores: country, city, state, latitude/longitude, timezone, ISP/ASN, VPN/proxy/Tor/datacenter/mobile flags, risk score, first/last seen timestamps, and visit count. See the [Privacy & GDPR](https://guilherme.stracini.com.br/ipquery-wordpress/privacy) documentation for full details.

= Is this GDPR compliant? =

The plugin provides controls to help you comply with GDPR (disable tracking, exclude IPs, set data retention, delete individual records). You are responsible for assessing compliance in your jurisdiction and updating your privacy policy accordingly.

= Where is data stored? =

All data is stored in your own WordPress database in the `wp_ipquery_visitors` table. No data is sent to any service other than the IpQuery API lookup.

== Screenshots ==

1. Dashboard — stat cards, world heatmap, and top countries chart
2. Visitors — searchable and sortable visitor log with enrichment data
3. Settings — tracking options, data retention, and excluded IPs

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Third-party services ==

This plugin makes server-side requests to the **IpQuery API** (`https://api.ipquery.io`) to enrich visitor IP addresses with geolocation, ISP, and risk data. Requests are made from your web server — not from the visitor's browser.

* [IpQuery API](https://ipquery.io)
* [IpQuery Terms of Service](https://ipquery.io/terms)
* [IpQuery Privacy Policy](https://ipquery.io/privacy)

This plugin bundles the **guibranco/ipquery-php** library (MIT License), which handles all communication with the IpQuery API.

* [guibranco/ipquery-php on GitHub](https://github.com/guibranco/ipquery-php)
