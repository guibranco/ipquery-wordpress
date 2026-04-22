---
title: Configuration
nav_order: 3
---

# Configuration

All settings are found at **IpQuery → Settings** in the WordPress admin.

---

## Tracking settings

### Enable Tracking

Toggles the entire tracking system on or off without deactivating the plugin. Useful for maintenance windows or GDPR-related pauses.

**Default:** enabled

---

### Track Logged-in Users

When enabled, visitors who are logged in to WordPress will also have their IPs tracked.

**Default:** disabled — only anonymous visitors are tracked

{: .tip }
Leaving this disabled is the safest default for GDPR compliance, as registered users have a reasonable expectation that their activity is not profiled beyond what they have consented to.

---

### Track Administrators

When enabled, users with the `manage_options` capability (typically site admins) are also tracked. This is a sub-option of **Track Logged-in Users**.

**Default:** disabled

---

### Look Up Private IPs

When enabled, private and reserved IP ranges (e.g. `192.168.x.x`, `10.x.x.x`, `127.0.0.1`) are sent to the IpQuery API. The API will return empty or limited data for these.

**Default:** disabled

{: .note }
Enable this only when testing on a local development environment (e.g. DDEV, Lando, Docker) where the `REMOTE_ADDR` is always a private IP.

---

### Excluded IPs

A newline-separated list of IP addresses that will never be tracked, regardless of other settings. Useful for excluding your own office IP, staging server crawlers, or known monitoring services.

```
203.0.113.42
198.51.100.0
```

---

### Data Retention

The number of days visitor records are kept. The daily cleanup cron deletes any record whose `last_seen` timestamp is older than this threshold.

**Default:** 90 days  
**Range:** 1 – 3 650 days

{: .tip }
For GDPR alignment, consider setting this to 30 days if you have no specific business need for longer retention.

---

## System status

The bottom of the Settings page shows a live status table:

| Check | What it verifies |
|---|---|
| PHP ≥ 8.2 | Your server's PHP version |
| cURL extension | Whether the cURL extension is loaded (required for API calls) |
| WP Cron (cleanup) | Whether the daily cleanup is scheduled, and when it will next run |
| DB version | The installed database schema version |

---

## Manual IP lookup

On the **Visitors** screen there is a **Lookup IP…** field. Enter any valid IP address and click **Lookup** to immediately query the IpQuery API and store the result — without waiting for that IP to visit your site.

[View the Visitors screen →]({% link visitors.md %}){: .btn }
