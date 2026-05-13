<div align="center">

# 🌍 IpQuery

**Track and analyse every visitor. Visualise traffic. Detect threats.**

A WordPress plugin that enriches visitor IP addresses with real-time geolocation, ISP data, and risk intelligence — powered by the [IpQuery API](https://ipquery.io) via [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php).

[![Build](https://img.shields.io/github/actions/workflow/status/guibranco/ipquery-wordpress/pages.yml?label=docs&style=flat-square)](https://github.com/guibranco/ipquery-wordpress/actions/workflows/pages.yml)
[![Version](https://img.shields.io/badge/version-1.2.0-blue?style=flat-square)](https://github.com/guibranco/ipquery-wordpress/releases)
[![WordPress](https://img.shields.io/badge/WordPress-%E2%89%A56.0-21759B?style=flat-square&logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A58.2-777BB4?style=flat-square&logo=php)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

[📖 Documentation](https://guilherme.stracini.com.br/ipquery-wordpress/) · [🐛 Report a Bug](https://github.com/guibranco/ipquery-wordpress/issues) · [✨ Request a Feature](https://github.com/guibranco/ipquery-wordpress/issues)

</div>

---

## ✨ Features

| | Feature | Description |
|---|---|---|
| 🗺️ | **World heatmap** | Interactive Leaflet.js map with a heat layer weighted by visit count |
| 📊 | **Country & city charts** | Horizontal bar chart of top countries; dedicated top-cities table with country flags |
| 🛡️ | **Risk intelligence** | Per-IP flags for VPN, proxy, Tor, datacenter, and mobile connections |
| 🔢 | **Risk scoring** | Numeric risk score (0–100) per IP with colour-coded indicators |
| 🔍 | **Visitors table** | Searchable, sortable, filterable table with full enrichment data for every IP |
| 🔎 | **Manual lookup** | Instantly enrich any IP address on demand from the admin panel |
| ⚡ | **Non-blocking** | API calls run at `shutdown` — visitor page load is never delayed |
| 🗄️ | **Smart caching** | WordPress transients cache each IP for 1 hour; only one API call per IP per hour |
| 🧹 | **Auto-retention** | Configurable data retention with daily WP-Cron cleanup |
| 🔒 | **Privacy controls** | Exclude IPs, skip logged-in users or admins, disable tracking at any time |
| 📥 | **CSV export** | Download all visitor data (or the current filtered view) as a UTF-8 CSV file |

---

## 🖥️ Admin screens

### Dashboard

The plugin adds an **IpQuery** entry to the WordPress admin sidebar with three sub-pages.

**Dashboard** — a live overview built from four widgets:

- **Stat cards** — total visits, unique IPs, VPN/proxy/Tor count, and overall risk rate
- **Visitor heatmap** — world map with gradient heat layer (blue → yellow → red)
- **Top countries** — bar chart (Chart.js) and a flag-annotated data table
- **Top cities** — table with city name, country flag, visit count, and traffic share
- **Risk breakdown** — doughnut chart and individual cards for each risk type

**Visitors** — full paginated table of every tracked IP with search, type filters, per-row delete, manual lookup form, and bulk purge.

**Settings** — all tracking options, data retention, excluded IPs list, and a system status panel.

---

## 🏗️ Architecture

```
Visitor browser request
        │
        ▼  WordPress bootstrap
        │
  [ init ]  IpQuery_Tracker registered
        │
  [ wp ]    maybe_track()
        │    ├─ skip: AJAX / REST / Cron
        │    ├─ skip: logged-in / admin (configurable)
        │    ├─ resolve IP (Cloudflare → X-Real-IP → X-Forwarded-For → REMOTE_ADDR)
        │    ├─ skip: private ranges, excluded IPs
        │    ├─ transient hit  →  bump visit_count only
        │    └─ transient miss →  register shutdown callback
        │
  Page rendered & sent to browser ◄──── no latency added
        │
  [ shutdown ]  lookup_and_store()
        │
        ▼
  IpQueryClient::getIpData($ip)   ← guibranco/ipquery-php
        │
        ▼
  GET https://api.ipquery.io/{ip}?format=json
        │
        ▼
  IpQueryResponse { location, isp, risk }
        │
        ▼
  IpQuery_DB::upsert()  →  wp_ipquery_visitors
        │
        ▼
  set_transient( "ipq_{md5}", 1, HOUR_IN_SECONDS )
```

---

## 📋 Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.0 |
| PHP | 8.2 |
| PHP extension | cURL |
| Database | MySQL 5.7+ / MariaDB 10.3+ |

> No Composer or external package manager needed — [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) is bundled inside `includes/vendor/`.

---

## 🚀 Installation

### From GitHub releases

1. Download the latest `.zip` from the [Releases page](https://github.com/guibranco/ipquery-wordpress/releases).
2. In your WordPress admin go to **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the `.zip` and click **Install Now**, then **Activate**.

### Manually

```bash
git clone https://github.com/guibranco/ipquery-wordpress.git
```

Copy the `ipquery-wordpress/` folder into `wp-content/plugins/`, then activate it from **Plugins** in the WordPress admin.

### On activation

The plugin automatically:

- Creates the `wp_ipquery_visitors` database table
- Writes default settings to `wp_options`
- Schedules a daily WP-Cron event for data retention cleanup

---

## ⚙️ Configuration

Navigate to **IpQuery → Settings** to configure:

| Setting | Default | Description |
|---|---|---|
| Enable Tracking | ✅ On | Master switch — disable to pause all tracking |
| Track Logged-in Users | ❌ Off | Whether authenticated visitors are tracked |
| Track Administrators | ❌ Off | Whether `manage_options` users are tracked |
| Look Up Private IPs | ❌ Off | Send private/LAN IPs to the API (useful for local dev) |
| Excluded IPs | _(empty)_ | Newline-separated list of IPs to never track |
| Data Retention | 90 days | Records older than this are auto-deleted by cron |

Full documentation: [guilherme.stracini.com.br/ipquery-wordpress/configuration](https://guilherme.stracini.com.br/ipquery-wordpress/configuration)

---

## 🗃️ Database schema

A single table `wp_ipquery_visitors` is created on activation:

```sql
id            BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT
ip            VARCHAR(45)      UNIQUE NOT NULL
country       VARCHAR(100)
country_code  VARCHAR(10)
city          VARCHAR(100)
state         VARCHAR(100)
zipcode       VARCHAR(20)
latitude      DECIMAL(10,7)
longitude     DECIMAL(10,7)
timezone      VARCHAR(100)
asn           VARCHAR(50)
org           VARCHAR(255)
isp           VARCHAR(255)
is_mobile     TINYINT(1)
is_vpn        TINYINT(1)
is_tor        TINYINT(1)
is_proxy      TINYINT(1)
is_datacenter TINYINT(1)
risk_score    SMALLINT(3)
first_seen    DATETIME
last_seen     DATETIME
visit_count   BIGINT UNSIGNED
```

---

## 📦 Project structure

```
ipquery-wordpress/
├── ipquery-wordpress.php          # Plugin bootstrap & constants
├── includes/
│   ├── vendor/                    # Bundled guibranco/ipquery-php
│   │   ├── IpQueryClient.php
│   │   ├── IpQueryException.php
│   │   ├── IIpQueryClient.php
│   │   └── Response/
│   │       ├── IpQueryResponse.php
│   │       ├── Isp.php
│   │       ├── Location.php
│   │       └── Risk.php
│   ├── class-ipquery-db.php       # Table install, upsert, and query helpers
│   ├── class-ipquery-tracker.php  # Visitor capture, IP resolution, caching
│   └── class-ipquery-admin.php    # Admin menus, AJAX, action handlers
├── admin/views/
│   ├── dashboard.php              # Heatmap, charts, stat cards, tables
│   ├── visitors.php               # Searchable/sortable visitor log
│   └── settings.php               # Settings form & system status
├── assets/
│   ├── css/admin.css
│   ├── js/ipquery-maps.js         # Leaflet heatmap
│   └── js/ipquery-charts.js       # Chart.js bar & doughnut charts
├── docs/                          # Jekyll / Just the Docs site
│   ├── _config.yml
│   ├── index.md
│   ├── installation.md
│   ├── configuration.md
│   ├── dashboard.md
│   ├── visitors.md
│   ├── tracking.md
│   ├── api.md
│   └── privacy.md
└── .github/workflows/
    └── docs.yml                   # GitHub Pages deployment
```

---

## 🔌 Powered by

| Dependency | Role |
|---|---|
| [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php) | PHP client library for the IpQuery API — bundled inside the plugin |
| [IpQuery API](https://ipquery.io) | Free IP intelligence service providing geolocation, ISP, and risk data |
| [Leaflet.js](https://leafletjs.com) | Interactive world map rendering |
| [Leaflet.heat](https://github.com/Leaflet/Leaflet.heat) | Heatmap layer for Leaflet |
| [Chart.js](https://www.chartjs.org) | Bar and doughnut charts |
| [OpenStreetMap](https://www.openstreetmap.org) | Map tile provider |
| [Flag CDN](https://flagcdn.com) | Country flag images |

---

## 📖 Documentation

Full documentation is available at **[guilherme.stracini.com.br/ipquery-wordpress](https://guilherme.stracini.com.br/ipquery-wordpress/)**.

| Page | Description |
|---|---|
| [Installation](https://guilherme.stracini.com.br/ipquery-wordpress/installation) | System requirements and install steps |
| [Configuration](https://guilherme.stracini.com.br/ipquery-wordpress/configuration) | All settings explained |
| [Dashboard](https://guilherme.stracini.com.br/ipquery-wordpress/dashboard) | Widget reference |
| [Visitors](https://guilherme.stracini.com.br/ipquery-wordpress/visitors) | Visitor table, filters, and lookup |
| [How Tracking Works](https://guilherme.stracini.com.br/ipquery-wordpress/tracking) | Technical flow and caching |
| [IpQuery API](https://guilherme.stracini.com.br/ipquery-wordpress/api) | API fields and PHP library reference |
| [Privacy & GDPR](https://guilherme.stracini.com.br/ipquery-wordpress/privacy) | Data collected, controls, and policy guidance |

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome.

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/my-feature`
3. Commit your changes: `git commit -m "feat: add my feature"`
4. Push to the branch: `git push origin feat/my-feature`
5. Open a Pull Request

Please follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) for PHP files.

---

## 📄 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for full text.

---

<div align="center">

Made with ❤️ by [Guilherme Branco Stracini](https://github.com/guibranco) · Built on [guibranco/ipquery-php](https://github.com/guibranco/ipquery-php)

</div>
