<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

// Load plugin main to access uninstall_schema.
require_once __DIR__ . '/wp-reseller-manage.php';

if (function_exists('wrpm_app')) {
    $app = wrpm_app();
    if ($app && method_exists($app, 'uninstall_schema')) {
        $app->uninstall_schema();
    }
}
