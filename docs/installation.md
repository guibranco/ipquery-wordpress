---
title: Installation
nav_order: 2
---

# Installation

## Requirements

Before installing, verify your environment meets these requirements:

| Requirement | Minimum |
|---|---|
| WordPress | 6.0 |
| PHP | 8.2 |
| PHP extension | cURL |
| Database | MySQL 5.7+ / MariaDB 10.3+ |

{: .note }
No Composer or external package manager is needed. The [ipquery-php](https://github.com/guibranco/ipquery-php) library is bundled inside the plugin.

---

## Install from GitHub

1. Go to the [Releases page](https://github.com/guibranco/ipquery-wordpress/releases) and download the latest `.zip`.
2. In your WordPress admin, navigate to **Plugins → Add New Plugin → Upload Plugin**.
3. Choose the downloaded `.zip` and click **Install Now**.
4. Click **Activate Plugin**.

## Install manually

1. Clone or download the repository:
   ```bash
   git clone https://github.com/guibranco/ipquery-wordpress.git
   ```
2. Copy the `ipquery-wordpress` folder into your `wp-content/plugins/` directory.
3. In your WordPress admin, go to **Plugins** and activate **IpQuery for WordPress**.

---

## What happens on activation

When you activate the plugin, it:

1. Creates the `wp_ipquery_visitors` database table (prefixed with your `$table_prefix`).
2. Writes default settings to the `ipquery_wp_settings` option.
3. Schedules a daily WP-Cron event (`ipquery_wp_daily_cleanup`) for automatic data retention.

No changes are made to existing WordPress tables.

---

## What happens on deactivation / uninstall

| Action | Effect |
|---|---|
| **Deactivate** | Tracking stops; data and settings are preserved |
| **Uninstall** (delete from Plugins screen) | Drops the `wp_ipquery_visitors` table and removes all plugin options |

{: .warning }
Uninstalling the plugin permanently deletes all collected visitor data.

---

## After activation

Once active, the plugin immediately starts tracking visitors (based on your [settings]({% link configuration.md %})). Navigate to **IpQuery → Dashboard** in the WordPress admin sidebar to see the interface.

[Configure the plugin →]({% link configuration.md %}){: .btn .btn-primary }
