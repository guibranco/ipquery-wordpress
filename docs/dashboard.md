---
title: Dashboard
nav_order: 4
---

# Dashboard

The dashboard is at **IpQuery → Dashboard**. It gives a live overview of all tracked visitor data.

---

## Stat cards

Four summary cards appear at the top of the page:

| Card | Description |
|---|---|
| **Total Visits** | Sum of `visit_count` across all stored IP records |
| **Unique IPs** | Number of distinct IP addresses in the database |
| **VPN / Proxy / Tor** | Count of IPs flagged as any of these three risk types |
| **Risk Rate** | (VPN + Proxy + Tor) ÷ Unique IPs, as a percentage |

---

## Visitor heatmap

An interactive world map rendered with [Leaflet.js](https://leafletjs.com) and the [Leaflet.heat](https://github.com/Leaflet/Leaflet.heat) plugin. Each point represents one stored IP address, weighted by its `visit_count`.

- **Blue** — low intensity
- **Yellow** — medium intensity
- **Red** — high intensity (most visits from this location)

The heatmap data is loaded asynchronously via AJAX after the page renders, so it never blocks the initial page load. Up to **500 coordinates** are included, ordered by visit count descending.

{: .note }
IPs for which the IpQuery API could not determine coordinates (e.g. private IPs, some datacenter ranges) are excluded from the heatmap but still appear in the Visitors table.

---

## Top countries chart

A horizontal bar chart (rendered with [Chart.js](https://www.chartjs.org)) showing the **15 countries with the most visits**. The chart is loaded via the same AJAX call as the heatmap, so it renders without blocking the page.

The same data is also presented as a table on the right side of this row, with flag icons and percentage of total traffic per country.

---

## Risk breakdown

A doughnut chart showing the distribution of risk flags across all unique IPs:

| Segment | Flag |
|---|---|
| VPN | `is_vpn = 1` |
| Proxy | `is_proxy = 1` |
| Tor | `is_tor = 1` |
| Datacenter | `is_datacenter = 1` |
| Mobile | `is_mobile = 1` |

{: .note }
An IP can have multiple flags. The chart shows counts, not exclusive categories — the same IP may appear in more than one segment.

---

## Risk detail cards

Below the chart row, five individual cards show the absolute count and percentage for each risk type: **VPN**, **Proxy**, **Tor**, **Datacenter**, and **Mobile**.

---

## Data freshness

All stat cards and the country table are server-rendered on page load and reflect the current state of the database at request time. The heatmap and charts load asynchronously and also reflect the current database state.

To see data in the dashboard, at least one visitor IP must have been tracked and successfully looked up via the IpQuery API. You can also use the [manual lookup]({% link visitors.md %}#manual-ip-lookup) on the Visitors screen to seed data immediately.
