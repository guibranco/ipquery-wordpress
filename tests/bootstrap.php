<?php

declare(strict_types=1);

// Composer autoloader — Brain\Monkey, Mockery, etc.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// WordPress constants required by plugin files.
if (! defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/wordpress/');
}
define('SVA_VERSION', '1.0.0');
define('SVA_FILE',    dirname(__DIR__) . '/stracini-visitor-analytics.php');
define('SVA_DIR',     dirname(__DIR__) . '/');
define('SVA_URL',     'https://example.com/wp-content/plugins/stracini-visitor-analytics/');
define('SVA_TABLE', 'sva_visitors');
define('HOUR_IN_SECONDS', 3600);
define('ARRAY_A',         'ARRAY_A');

// Bundled vendor library (namespace GuiBranco\IpQuery).
require_once SVA_DIR . 'includes/vendor/IpQueryException.php';
require_once SVA_DIR . 'includes/vendor/Response/Isp.php';
require_once SVA_DIR . 'includes/vendor/Response/Location.php';
require_once SVA_DIR . 'includes/vendor/Response/Risk.php';
require_once SVA_DIR . 'includes/vendor/Response/IpQueryResponse.php';
require_once SVA_DIR . 'includes/vendor/IIpQueryClient.php';
require_once SVA_DIR . 'includes/vendor/IpQueryClient.php';

// Plugin classes.
require_once SVA_DIR . 'includes/class-sva-db.php';
require_once SVA_DIR . 'includes/class-sva-tracker.php';
require_once SVA_DIR . 'includes/class-sva-admin.php';
