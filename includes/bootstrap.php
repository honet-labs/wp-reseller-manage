<?php
/**
 * Bootstrap loader for WP Reseller Manage.
 */
if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/traits/trait-utils.php';
require_once __DIR__ . '/traits/trait-db.php';
require_once __DIR__ . '/traits/trait-logs.php';
require_once __DIR__ . '/traits/trait-notify.php';
require_once __DIR__ . '/traits/trait-pdf.php';
require_once __DIR__ . '/traits/trait-csv.php';
require_once __DIR__ . '/traits/trait-api.php';
require_once __DIR__ . '/traits/trait-updater.php';

// Admin modules
require_once __DIR__ . '/traits/trait-admin-core.php';
require_once __DIR__ . '/traits/trait-admin-dashboard.php';
require_once __DIR__ . '/traits/trait-admin-product-prices.php';
require_once __DIR__ . '/traits/trait-admin-reseller-products.php';
require_once __DIR__ . '/traits/trait-admin-customers.php';
require_once __DIR__ . '/traits/trait-admin-sellers.php';
require_once __DIR__ . '/traits/trait-admin-active-products.php';
require_once __DIR__ . '/traits/trait-admin-reminders.php';
require_once __DIR__ . '/traits/trait-admin-reports.php';
require_once __DIR__ . '/traits/trait-admin-settings.php';
require_once __DIR__ . '/traits/trait-admin-logs.php';
require_once __DIR__ . '/traits/trait-admin-pages.php';
