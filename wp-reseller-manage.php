<?php
/**
 * Plugin Name: WP Reseller Manage
 * Description: Manajemen produk reseller: master harga, produk reseller, customer, produk aktif, reminder (email/telegram), invoice PDF, logs & laporan.
 * Version: 0.1.15
 * Author: HONET
 * License: GPLv2 or later
 * Text Domain: wp-reseller-product-manager
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; }

if (!defined('WRPM_PLUGIN_DIR')) {
  define('WRPM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WRPM_PLUGIN_URL')) {
  define('WRPM_PLUGIN_URL', plugin_dir_url(__FILE__));
}

require_once __DIR__ . '/includes/bootstrap.php';

final class WRPM_App {
    const VERSION = '0.1.15';

    const OPT_DB_VERSION = 'wrpm_db_version';

    const TEXT_DOMAIN = 'wp-reseller-product-manager';
    const PLUGIN_SHORT_NAME = 'WP Reseller Manage';

    const OPT_SETTINGS = 'wrpm_settings_v1';

    // Capabilities
    const CAP_MANAGE = 'wrpm_manage';
    const CAP_VIEW_REPORTS = 'wrpm_view_reports';
    const CAP_MANAGE_SETTINGS = 'wrpm_manage_settings';
    const CAP_VIEW_LOGS = 'wrpm_view_logs';

    use WRPM_Trait_Utils;
    use WRPM_Trait_DB;
    use WRPM_Trait_Logs;
    use WRPM_Trait_Notify;
    use WRPM_Trait_PDF;
    use WRPM_Trait_CSV;
    use WRPM_Trait_API;
    use WRPM_Trait_Updater;

    use WRPM_Trait_Admin_Pages;

    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        $this->init_github_updater();

        // Lightweight auto-upgrade (dbDelta) when plugin version changes.
        add_action('admin_init', [$this, 'maybe_upgrade_schema']);

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);

        // SMTP
        add_action('phpmailer_init', [$this, 'maybe_apply_smtp']);

        // REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron
        add_action('wrpm_daily_cron', [$this, 'cron_daily']);

        // Admin post actions
        add_action('admin_post_wrpm_save_price', [$this, 'handle_admin_post_save_price']);
        add_action('admin_post_wrpm_delete_price', [$this, 'handle_admin_post_delete_price']);
        add_action('admin_post_wrpm_export_prices_csv', [$this, 'handle_admin_post_export_prices_csv']);
        add_action('admin_post_wrpm_import_prices_csv', [$this, 'handle_admin_post_import_prices_csv']);

        add_action('admin_post_wrpm_export_customers_csv', [$this, 'handle_admin_post_export_customers_csv']);
        add_action('admin_post_wrpm_import_customers_csv', [$this, 'handle_admin_post_import_customers_csv']);

        add_action('admin_post_wrpm_export_reseller_products_csv', [$this, 'handle_admin_post_export_reseller_products_csv']);
        add_action('admin_post_wrpm_import_reseller_products_csv', [$this, 'handle_admin_post_import_reseller_products_csv']);

        add_action('admin_post_wrpm_export_active_products_csv', [$this, 'handle_admin_post_export_active_products_csv']);
        add_action('admin_post_wrpm_import_active_products_csv', [$this, 'handle_admin_post_import_active_products_csv']);


        add_action('admin_post_wrpm_save_reseller_product', [$this, 'handle_admin_post_save_reseller_product']);
        add_action('admin_post_wrpm_delete_reseller_product', [$this, 'handle_admin_post_delete_reseller_product']);

        add_action('admin_post_wrpm_save_customer', [$this, 'handle_admin_post_save_customer']);
        add_action('admin_post_wrpm_delete_customer', [$this, 'handle_admin_post_delete_customer']);

        add_action('admin_post_wrpm_save_seller', [$this, 'handle_admin_post_save_seller']);
        add_action('admin_post_wrpm_delete_seller', [$this, 'handle_admin_post_delete_seller']);

        add_action('admin_post_wrpm_save_active_product', [$this, 'handle_admin_post_save_active_product']);
        add_action('admin_post_wrpm_delete_active_product', [$this, 'handle_admin_post_delete_active_product']);
        add_action('admin_post_wrpm_extend_active_product', [$this, 'handle_admin_post_extend_active_product']);
        add_action('admin_post_wrpm_invoice_pdf', [$this, 'handle_admin_post_invoice_pdf']);

        add_action('admin_post_wrpm_send_reminder_manual', [$this, 'handle_admin_post_send_reminder_manual']);

        add_action('admin_post_wrpm_save_settings', [$this, 'handle_admin_post_save_settings']);
        add_action('admin_post_wrpm_test_email', [$this, 'handle_admin_post_test_email']);
        add_action('admin_post_wrpm_test_telegram', [$this, 'handle_admin_post_test_telegram']);
        add_action('admin_post_wrpm_test_waha', [$this, 'handle_admin_post_test_waha']);

        add_action('admin_post_wrpm_run_cron_now', [$this, 'handle_admin_post_run_cron_now']);
        add_action('admin_post_wrpm_wc_sync', [$this, 'handle_admin_post_wc_sync']);
        add_action('admin_post_wrpm_backup_data', [$this, 'handle_admin_post_backup_data']);
        add_action('admin_post_wrpm_restore_data', [$this, 'handle_admin_post_restore_data']);

        // Reports
        add_action('admin_post_wrpm_report_pdf', [$this, 'handle_admin_post_report_pdf']);


        // Shortcodes
        add_shortcode('wrpm_price_table', [$this, 'shortcode_price_table']);
    }

    public function maybe_upgrade_schema() {
        if (!is_admin()) return;
        $cur = (string)get_option(self::OPT_DB_VERSION, '');
        if ($cur === self::VERSION) return;
        $this->install_schema();
        update_option(self::OPT_DB_VERSION, self::VERSION, false);
    }

    public function load_textdomain() {
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        $cap = self::CAP_MANAGE;

        add_menu_page(
            self::PLUGIN_SHORT_NAME,
            'WP Reseller',
            $cap,
            'wrpm-dashboard',
            [$this, 'page_dashboard'],
            'dashicons-cart',
            58
        );

        add_submenu_page('wrpm-dashboard', 'Dashboard', 'Dashboard', $cap, 'wrpm-dashboard', [$this, 'page_dashboard']);

        add_submenu_page('wrpm-dashboard', 'Harga Produk', 'Harga Produk', $cap, 'wrpm-product-prices', [$this, 'page_product_prices_list']);
        add_submenu_page('wrpm-dashboard', 'Tambah Harga Produk', 'Tambah Harga Produk', $cap, 'wrpm-product-price-add', [$this, 'page_product_price_add_edit']);

        add_submenu_page('wrpm-dashboard', 'Produk Reseller', 'Produk Reseller', $cap, 'wrpm-reseller-products', [$this, 'page_reseller_products_list']);
        add_submenu_page('wrpm-dashboard', 'Tambah Produk Reseller', 'Tambah Produk Reseller', $cap, 'wrpm-reseller-product-add', [$this, 'page_reseller_product_add_edit']);

        add_submenu_page('wrpm-dashboard', 'Customer', 'Customer', $cap, 'wrpm-customers', [$this, 'page_customers_list']);
        add_submenu_page('wrpm-dashboard', 'Tambah Customer', 'Tambah Customer', $cap, 'wrpm-customer-add', [$this, 'page_customer_add_edit']);

        add_submenu_page('wrpm-dashboard', 'Seller', 'Seller', $cap, 'wrpm-sellers', [$this, 'page_sellers_list']);
        add_submenu_page('wrpm-dashboard', 'Tambah Seller', 'Tambah Seller', $cap, 'wrpm-seller-add', [$this, 'page_seller_add_edit']);

        add_submenu_page('wrpm-dashboard', 'Produk Aktif', 'Produk Aktif', $cap, 'wrpm-active-products', [$this, 'page_active_products_list']);
        add_submenu_page('wrpm-dashboard', 'Tambah Produk Aktif', 'Tambah Produk Aktif', $cap, 'wrpm-active-product-add', [$this, 'page_active_product_add_edit']);

        add_submenu_page('wrpm-dashboard', 'Reminder', 'Reminder', $cap, 'wrpm-reminders', [$this, 'page_reminders_list']);

        add_submenu_page('wrpm-dashboard', 'Laporan', 'Laporan', self::CAP_VIEW_REPORTS, 'wrpm-reports', [$this, 'page_reports']);
        add_submenu_page('wrpm-dashboard', 'Logs', 'Logs', self::CAP_VIEW_LOGS, 'wrpm-logs', [$this, 'page_logs']);

        add_submenu_page('wrpm-dashboard', 'Settings', 'Settings', self::CAP_MANAGE_SETTINGS, 'wrpm-settings', [$this, 'page_settings']);
    }

    public function admin_assets($hook) {
        // Load only on our pages.
        if (empty($_GET['page'])) return;
        $page = sanitize_text_field(wp_unslash($_GET['page']));
        if (strpos($page, 'wrpm-') !== 0) return;

        // Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        // Chart.js
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true);

        wp_enqueue_style('wrpm-admin', WRPM_PLUGIN_URL . 'assets/css/admin.css', [], self::VERSION);
        wp_enqueue_script('wrpm-admin-core', WRPM_PLUGIN_URL . 'assets/js/admin-core.js', ['jquery', 'select2'], self::VERSION, true);
        wp_enqueue_script('wrpm-admin-upload', WRPM_PLUGIN_URL . 'assets/js/admin-upload.js', ['jquery'], self::VERSION, true);
        wp_localize_script('wrpm-admin-upload', 'SIMAK_UPLOAD', [
            'max_bytes' => 1400 * 1024,
            'max_dim' => 1600,
            'quality' => 0.78,
        ]);
    }

    public function shortcode_price_table($atts = []) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT name, category, tags, sale_price, duration_days, description FROM {$this->tbl_prices()} ORDER BY name ASC", ARRAY_A);
        if (empty($rows)) return '<div class="wrpm-empty">Belum ada data harga.</div>';

        ob_start();
        echo '<div class="wrpm-price-table">';
        echo '<table class="wrpm-table">';
        echo '<thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Durasi</th><th>Deskripsi</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html($r['name']) . '</td>';
            echo '<td>' . esc_html($r['category']) . '</td>';
            echo '<td>' . esc_html($this->wrpm_money_idr((float)$r['sale_price'])) . '</td>';
            echo '<td>' . esc_html((int)$r['duration_days']) . ' hari</td>';
            echo '<td>' . wp_kses_post($r['description']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        return ob_get_clean();
    }
}

function wrpm_app() {
    return WRPM_App::instance();
}
wrpm_app();

register_activation_hook(__FILE__, function(){
    $app = wrpm_app();
    $app->install_schema();
});

register_deactivation_hook(__FILE__, function(){
    $app = wrpm_app();
    $app->clear_cron();
});
