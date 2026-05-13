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

## Export CSV

Click **Export CSV** in the toolbar to download all currently visible visitor records as a UTF-8 encoded CSV file. The export honours any active search term or risk-type filter — only the rows that match the current filter are included.

**Columns exported:**

| Column | Description |
|---|---|
| IP | Raw IP address |
| Country | Full country name |
| Country Code | ISO 3166-1 alpha-2 code |
| City | City name |
| State | State or region |
| Zipcode | Postal / ZIP code |
| Latitude | Decimal latitude |
| Longitude | Decimal longitude |
| Timezone | IANA timezone identifier |
| ASN | Autonomous System Number |
| Org | Organisation name |
| ISP | Internet Service Provider |
| Is Mobile | Yes / No |
| Is VPN | Yes / No |
| Is Tor | Yes / No |
| Is Proxy | Yes / No |
| Is Datacenter | Yes / No |
| Risk Score | 0 – 100 |
| First Seen | UTC datetime of first visit |
| Last Seen | UTC datetime of most recent visit |
| Visit Count | Total visits attributed to this IP |

The file is named `ipquery-visitors-YYYY-MM-DD.csv` (today's date). A UTF-8 BOM is prepended so that Excel opens the file correctly without a manual import step.

{: .note }
The export is not paginated — it always includes every matching record, regardless of how many pages the table spans.

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

## Deleting records by country

The **Delete by Country** tool supports GDPR right-to-erasure workflows by removing all stored visitor records that originate from one or more specific countries.

### How to use it

1. In the **Delete by Country** section at the bottom of the Visitors screen, select one or more countries from the dropdown. Countries are listed by name alongside their ISO 3166-1 alpha-2 code and the current number of records stored for each.
2. Click **Delete Selected Countries**.
3. A confirmation dialog appears showing the number of records that will be deleted and the countries affected. Confirm to proceed or cancel to abort.
4. On confirmation, all matching records are permanently deleted and a success notice is displayed with the total count removed.

### Bulk deletion

You can select multiple countries in a single operation. All records matching any of the selected countries are deleted in one database transaction.

### Audit log

Every country-filter deletion is written to the plugin's action log with:

| Field | Value |
|---|---|
| **Action** | `delete_by_country` |
| **Countries** | Comma-separated list of ISO country codes deleted |
| **Records deleted** | Total row count removed |
| **Performed by** | WordPress user ID and display name |
| **Timestamp** | UTC date and time of the operation |

The log is accessible under **IpQuery → Logs** and is retained for 90 days.

{: .warning }
Country-filter deletion is irreversible. All records for the selected countries are permanently removed and cannot be recovered.

{: .note }
The country dropdown only lists countries for which at least one record is currently stored. If a country does not appear, no records exist for it.

---

## Pagination

Results are paginated at 25 records per page. Pagination links appear below the table when the result set exceeds one page. Sorting and filter parameters are preserved across page navigation.
