---
title: Visitors
nav_order: 5
---

# Visitors

The Visitors screen is at **IpQuery → Visitors**. It shows every IP address in the database with its full enrichment data, and provides tools for searching, filtering, manual lookups, and data purging.

---

## Table columns

| Column | Description |
|---|---|
| **IP Address** | The raw IP (IPv4 or IPv6) |
| **Location** | City, state, and country with a country flag |
| **ISP** | Internet service provider name from the IpQuery API |
| **Risk Flags** | Colour-coded badges: `VPN`, `Proxy`, `Tor`, `DC` (datacenter), `Mobile`, or `Clean` |
| **Score** | Numeric risk score 0–100; green ≤ 39, orange 40–79, red ≥ 80 |
| **Visits** | Total number of page views attributed to this IP |
| **First Seen** | Date of the first recorded visit |
| **Last Seen** | Date and time of the most recent visit |
| **Actions** | Per-row delete button |

Every column header (except ISP and Risk Flags) is sortable — click once to sort ascending, again to sort descending.

---

## Filtering and search

The toolbar above the table provides:

- **Search box** — matches against IP address, city, country, and ISP name simultaneously
- **Type filter** — restricts results to one risk category: VPN, Proxy, Tor, Datacenter, or Mobile
- **Reset** button — clears all active filters

Filters are applied server-side and are reflected in the record count shown above the table.

---

## Manual IP lookup

Enter any valid IP address in the **Lookup IP…** field and click **Lookup**. The plugin immediately calls `IpQueryClient::getIpData()` from the [ipquery-php](https://github.com/guibranco/ipquery-php) library and stores the result. The page redirects back with a success or error notice.

This is useful for:
- Testing the API connection
- Pre-populating data for known IPs
- Re-enriching an IP after updating your settings

{: .note }
Manual lookups bypass the 1-hour transient cache used during normal page tracking. Each manual lookup always makes a fresh API call.

---

## Deleting a single record

Click **Delete** at the end of any row. You will be asked to confirm, after which the record is permanently removed from the database. The transient cache for that IP is **not** cleared, so if that visitor returns within the cache window they will be re-inserted as a new record at the next API call.

---

## Purging old records

At the bottom of the Visitors screen there is a **Purge Old Records** form. Enter a number of days and click **Purge** to immediately delete all records whose `last_seen` date is older than that threshold.

This is a manual complement to the automatic [data retention]({% link configuration.md %}#data-retention) setting, which runs daily via WP-Cron.

{: .warning }
Purging is irreversible. The deleted records cannot be recovered.

---

## Pagination

Results are paginated at 25 records per page. Pagination links appear below the table when the result set exceeds one page. Sorting and filter parameters are preserved across page navigation.
