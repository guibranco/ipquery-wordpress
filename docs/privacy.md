---
title: Privacy & GDPR
nav_order: 8
---

# Privacy & GDPR

This page outlines what data the plugin collects, how long it is kept, and what controls are available to you as the site operator.

{: .important }
This documentation does not constitute legal advice. You are responsible for assessing whether your use of this plugin complies with applicable privacy laws (GDPR, LGPD, CCPA, etc.) in your jurisdiction.

---

## What is collected

For each unique visitor IP the plugin stores:

| Field | Category | Example |
|---|---|---|
| IP address | **Personal data** | `203.0.113.42` |
| Country, city, state | Derived location | `United States / New York / New York` |
| Latitude / longitude | Derived location | `40.7128 / -74.0060` |
| Timezone | Derived location | `America/New_York` |
| ISP, ASN, organisation | Network metadata | `Spectrum / AS11351` |
| VPN / Proxy / Tor / DC / Mobile flags | Risk metadata | `false / false / false / false / false` |
| Risk score | Risk metadata | `12` |
| First seen, last seen | Timestamps | `2026-03-01 / 2026-04-22` |
| Visit count | Aggregate counter | `7` |

IP addresses are considered personal data under the GDPR when they can be linked to an individual. All derived fields (location, ISP, risk) are fetched from the third-party [IpQuery API](https://ipquery.io) and stored locally in your WordPress database.

---

## Third-party data transfer

When a visitor's IP is looked up, it is sent to:

```
https://api.ipquery.io/{ip}?format=json
```

This is an outbound server-to-server request made from your web server — **not from the visitor's browser**. No cookies, tracking pixels, or browser-side scripts are sent to `ipquery.io`.

You should reference `ipquery.io` in your privacy policy as a sub-processor if you operate under GDPR.

---

## Available controls

| Control | Location | Effect |
|---|---|---|
| **Disable tracking** | Settings → Enable Tracking | No IPs are captured or sent to the API |
| **Exclude logged-in users** | Settings → Track Logged-in Users | Registered users are never tracked |
| **Exclude admins** | Settings → Track Administrators | `manage_options` users are never tracked |
| **Exclude specific IPs** | Settings → Excluded IPs | Listed IPs are never tracked or stored |
| **Data retention** | Settings → Data Retention | Records older than N days are auto-deleted |
| **Manual delete** | Visitors screen → Delete | Removes a single IP record immediately |
| **Bulk purge** | Visitors screen → Purge Old Records | Removes all records older than N days immediately |

---

## Data storage

All collected data is stored in the `wp_ipquery_visitors` table in your own WordPress database. No data is sent to any service other than the IpQuery API lookup described above, and the results of that lookup are stored only on your server.

---

## Recommended privacy policy language

If you use this plugin, consider adding a clause similar to the following to your privacy policy:

> We use the IpQuery for WordPress plugin to analyse visitor traffic. Your IP address is sent to the IpQuery API (ipquery.io) to retrieve geolocation and network risk information. This information is stored in our database for up to [X] days and is used solely for security and analytics purposes. It is not shared with third parties. You may request deletion of your data by contacting us at [your contact address].

---

## WordPress Privacy Tools integration

The plugin does not currently register itself with the WordPress core privacy tools (`wp_privacy_send_personal_data_export_requests` / `wp_privacy_personal_data_erasure_fulfilled`). If you need to support data export and erasure requests, you can query and delete records programmatically:

```php
// Find all records for an IP
global $wpdb;
$table = $wpdb->prefix . 'ipquery_visitors';
$rows  = $wpdb->get_results(
    $wpdb->prepare( "SELECT * FROM {$table} WHERE ip = %s", $user_ip )
);

// Delete records for an IP
IpQuery_DB::delete_ip( $user_ip );
```

Support for native WordPress privacy tools is planned for a future release.
