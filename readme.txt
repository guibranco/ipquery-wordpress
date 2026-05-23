=== Stracini Visitor Analytics with IpQuery ===
Contributors: guilhermestracini
Tags: ip, geolocation, analytics, security, heatmap
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.2.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Track and analyze visitor IP data using the IpQuery API. Displays location maps, traffic heatmaps, and VPN/proxy/Tor statistics. This plugin is not officially affiliated with IpQuery.

== Description ==

Stracini Visitor Analytics with IpQuery enriches every visitor's IP address with real-time geolocation, ISP data, and risk intelligence — powered by the [IpQuery API](https://ipquery.io) via the bundled [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) library.

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
* **GDPR erasure tools** — Delete individual visitor records or bulk-erase all data for a specific country
* **CSV export** — Download all visitor data (or the current filtered view) as a UTF-8 CSV file

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

For each unique visitor IP the plugin stores: country, city, state, latitude/longitude, timezone, ISP/ASN, VPN/proxy/Tor/datacenter/mobile flags, risk score, first/last seen timestamps, and visit count. See the [Privacy & GDPR](https://guilherme.stracini.com.br/ipquery-wordpress/privacy/) documentation for full details.

= Is this GDPR compliant? =

The plugin provides controls to help you comply with GDPR (disable tracking, exclude IPs, set data retention, delete individual records, and bulk-delete all records for a given country). You are responsible for assessing compliance in your jurisdiction and updating your privacy policy accordingly.

= How do I erase all data for visitors from a specific country? =

Navigate to **IpQuery → Settings → GDPR Erasure**, select the country from the dropdown, and confirm the deletion. All visitor records matching that country code will be removed from the `wp_ipquery_visitors` table, and the corresponding cached transients will be flushed.

= Where is data stored? =

All data is stored in your own WordPress database in the `wp_stracini_visitor_analytics_visitors` table. No data is sent to any service other than the IpQuery API lookup.

== Screenshots ==

1. Dashboard — stat cards, world heatmap, and top countries chart
2. Visitors — searchable and sortable visitor log with enrichment data
3. Settings — tracking options, data retention, and excluded IPs
4. GDPR erasure — country-based bulk deletion tool

== Changelog ==

= 1.2.0 =
* Added CSV export — download all visitor records (or the current filtered view) directly from the Visitors screen
* Export honours active search and risk-type filters so you can export exactly the subset you need
* Exported CSV is UTF-8 with BOM for seamless Excel compatibility; filename includes today's date

= 1.1.1 =
* Maintenance and bug fixes

= 1.1.0 =
* Added country-based data deletion for GDPR erasure — bulk-delete all visitor records for a selected country directly from the admin panel
* New **GDPR Erasure** section under **IpQuery → Settings** with a country picker and confirmation step
* Cached transients are flushed for affected IPs after a country-based erasure
* New `ipquery_before_country_erasure` and `ipquery_after_country_erasure` action hooks for extensibility
* Updated Privacy & GDPR documentation to reflect the new erasure workflow

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.2.0 =
Adds CSV export of visitor data from the Visitors screen. No database changes — safe to upgrade.

= 1.1.1 =
Maintenance release. Safe to upgrade.

= 1.1.0 =
Adds country-based bulk data deletion to support GDPR right-to-erasure requests. Recommended for any site processing EU visitor data.

= 1.0.0 =
Initial release.

== Third-party services ==

This plugin is not officially affiliated with IpQuery.

= IpQuery API =

This plugin makes server-side requests to the **IpQuery API** (`https://api.ipquery.io`) to enrich visitor IP addresses with geolocation, ISP, and risk data. Requests are made from your web server — not from the visitor's browser. The visitor's IP address is sent to the IpQuery API on each page load when tracking is enabled and the IP has not been cached.

* [IpQuery website](https://ipquery.io)
* For terms of service and privacy inquiries: contact@ipquery.io

= Flag CDN (flagcdn.com) =

The admin dashboard and visitors screen load country flag images from **Flag CDN**, a free service provided by Flagpedia.net and hosted on Cloudflare. Flag images are loaded in the WordPress admin area only (never on the public-facing site). The visitor's country code (a 2-letter ISO code, e.g. "US") is sent as part of the image URL to retrieve the corresponding flag.

* [Flag CDN website](https://flagcdn.com)
* [Flagpedia Terms of Use](https://flagpedia.net/terms)
* [Flagpedia Privacy Policy](https://flagpedia.net/privacy-policy)

= Bundled library =

This plugin bundles the **guibranco/ipquery-php** library (MIT License), which handles all communication with the IpQuery API. No data is sent by this library to any service other than the IpQuery API.

* [guibranco/ipquery-php on GitHub](https://github.com/guibranco/ipquery-php)
