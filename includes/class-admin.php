<?php
if (!defined('ABSPATH')) { exit; }

class OKJ_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Form post hooks
        add_action('admin_post_okj_save_price', [$this, 'save_product_price']);
        add_action('admin_post_okj_delete_price', [$this, 'delete_product_price']);
        add_action('admin_post_okj_save_seller', [$this, 'save_seller']);
        add_action('admin_post_okj_delete_seller', [$this, 'delete_seller']);
        add_action('admin_post_okj_save_customer', [$this, 'save_customer']);
        add_action('admin_post_okj_delete_customer', [$this, 'delete_customer']);
        add_action('admin_post_okj_save_reseller_product', [$this, 'save_reseller_product']);
        add_action('admin_post_okj_delete_reseller_product', [$this, 'delete_reseller_product']);
        add_action('admin_post_okj_save_active_product', [$this, 'save_active_product']);
        add_action('admin_post_okj_delete_active_product', [$this, 'delete_active_product']);
        add_action('admin_post_okj_renew_active_product', [$this, 'renew_active_product']);
        add_action('admin_post_okj_save_settings', [$this, 'save_settings']);
        add_action('admin_post_okj_backup_data', [$this, 'backup_data']);
        add_action('admin_post_okj_restore_data', [$this, 'restore_data']);
        add_action('admin_post_okj_invoice_pdf', [$this, 'download_invoice_pdf']);
        add_action('admin_post_okj_save_shortlink', [$this, 'save_shortlink']);
        add_action('admin_post_okj_delete_shortlink', [$this, 'delete_shortlink']);

        // Manual reminder triggers
        add_action('admin_post_okj_send_reminder_manual', [$this, 'send_reminder_manual']);

        // AJAX hooks for quick add and connection testing
        add_action('wp_ajax_okj_quick_add_seller', [$this, 'quick_add_seller']);
        add_action('wp_ajax_okj_quick_add_customer', [$this, 'quick_add_customer']);
        add_action('wp_ajax_okj_get_renewal_history', [$this, 'ajax_get_renewal_history']);
        add_action('wp_ajax_okj_test_waha', [$this, 'ajax_test_waha']);
        add_action('wp_ajax_okj_test_telegram', [$this, 'ajax_test_telegram']);
        add_action('wp_ajax_okj_test_smtp', [$this, 'ajax_test_smtp']);
        add_action('wp_ajax_okj_pos_get_products', [$this, 'ajax_pos_get_products']);
        add_action('wp_ajax_okj_pos_checkout', [$this, 'ajax_pos_checkout']);
        add_action('wp_ajax_okj_pos_send_wa_struk', [$this, 'ajax_pos_send_wa_struk']);
        add_action('wp_ajax_okj_pos_update_status', [$this, 'ajax_pos_update_status']);
        
        // Public self-service order AJAX hooks (guests)
        add_action('wp_ajax_okj_public_get_products', [$this, 'ajax_public_get_products']);
        add_action('wp_ajax_nopriv_okj_public_get_products', [$this, 'ajax_public_get_products']);
        add_action('wp_ajax_okj_public_place_order', [$this, 'ajax_public_place_order']);
        add_action('wp_ajax_nopriv_okj_public_place_order', [$this, 'ajax_public_place_order']);
        add_action('wp_ajax_okj_public_check_order_status', [$this, 'ajax_public_check_order_status']);
        add_action('wp_ajax_nopriv_okj_public_check_order_status', [$this, 'ajax_public_check_order_status']);
    }

    public function register_menus() {
        $cap = 'okj_manage';

        // Main OKJualin Manager Menu
        add_menu_page(
            'OKJualin',
            'OKJualin',
            $cap,
            'okj-dashboard',
            [$this, 'view_dashboard'],
            'dashicons-store',
            58
        );

        add_submenu_page('okj-dashboard', 'Dashboard', 'Dashboard', $cap, 'okj-dashboard', [$this, 'view_dashboard']);
        add_submenu_page('okj-dashboard', 'Daftar Harga Produk', 'Daftar Harga Produk', $cap, 'okj-product-prices', [$this, 'view_product_prices']);
        add_submenu_page('okj-dashboard', 'Pembelian Produk', 'Pembelian Produk', $cap, 'okj-reseller-products', [$this, 'view_reseller_products']);
        add_submenu_page('okj-dashboard', 'Customer', 'Customer', $cap, 'okj-customers', [$this, 'view_customers']);
        add_submenu_page('okj-dashboard', 'Seller', 'Seller', $cap, 'okj-sellers', [$this, 'view_sellers']);
        add_submenu_page('okj-dashboard', 'Produk Aktif', 'Produk Aktif', $cap, 'okj-active-products', [$this, 'view_active_products']);
        add_submenu_page('okj-dashboard', 'Reminder', 'Reminder', $cap, 'okj-reminders', [$this, 'view_reminders']);
        add_submenu_page('okj-dashboard', 'Shortlink Affiliate', 'Shortlink Affiliate', $cap, 'okj-shortlinks', [$this, 'view_shortlinks']);
        add_submenu_page('okj-dashboard', 'Laporan', 'Laporan', 'okj_view_reports', 'okj-reports', [$this, 'view_reports']);
        add_submenu_page('okj-dashboard', 'Logs', 'Logs', 'okj_view_logs', 'okj-logs', [$this, 'view_logs']);
        add_submenu_page('okj-dashboard', 'Settings', 'Settings', 'okj_manage_settings', 'okj-settings', [$this, 'view_settings']);

        // Dedicated Top-Level POS Menu (Premium UX ala WooCommerce)
        add_menu_page(
            'OKJualin POS',
            'OKJualin - POS',
            $cap,
            'okj-pos',
            [$this, 'view_pos'],
            'dashicons-calculator',
            59
        );
    }

    public function enqueue_assets($hook) {
        if (empty($_GET['page']) || strpos($_GET['page'], 'okj-') !== 0) return;

        // Modern Visuals & Chart libraries
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        // Chart.js for interactive analytics
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true);

        // Load visual styling assets
        $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/admin.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/admin.css') : time();
        $js_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/js/admin.js') ? filemtime(dirname(dirname(__FILE__)) . '/assets/js/admin.js') : time();
        wp_enqueue_style('okj-admin-css', plugins_url('assets/css/admin.css', dirname(__FILE__)), [], $css_ver);
        wp_enqueue_script('okj-admin-js', plugins_url('assets/js/admin.js', dirname(__FILE__)), ['jquery', 'select2', 'chartjs'], $js_ver, true);
    }

    // View router helper
    private function render_template($name, $args = []) {
        extract($args);
        $path = dirname(dirname(__FILE__)) . '/templates/' . $name . '.php';
        if (file_exists($path)) {
            include $path;
        }
    }

    // views implementation
    public function view_dashboard() {
        global $wpdb;
        $total_reseller = (int)$wpdb->get_var("SELECT COUNT(*) FROM " . OKJ_DB::get_table('reseller_products'));
        $total_active = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . OKJ_DB::get_table('active_products') . " WHERE status = %s", 'active'));
        $total_expired = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . OKJ_DB::get_table('active_products') . " WHERE status = %s", 'expired'));
        $total_income = (float)$wpdb->get_var("SELECT COALESCE(SUM(price),0) FROM " . OKJ_DB::get_table('active_products'));

        $today = wp_date('Y-m-d');
        $in7 = wp_date('Y-m-d', strtotime('+7 days'));

        $soon = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE status = 'active' AND expires_at >= %s AND expires_at <= %s ORDER BY expires_at ASC LIMIT 10",
            $today, $in7
        ), ARRAY_A);

        // revenue analytical data
        $revenue_monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m_val = date('Y-m', strtotime("-$i months"));
            $m_label = date('F Y', strtotime("-$i months"));
            $revenue = (float)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(price),0) FROM " . OKJ_DB::get_table('active_products') . " WHERE start_date LIKE %s",
                $m_val . '%'
            ));
            $revenue_monthly[] = ['label' => $m_label, 'revenue' => $revenue];
        }

        $this->render_template('dashboard', [
            'total_reseller' => $total_reseller,
            'total_active' => $total_active,
            'total_expired' => $total_expired,
            'total_income' => $total_income,
            'soon' => $soon,
            'today' => $today,
            'revenue_monthly' => $revenue_monthly,
        ]);
    }

    public function view_product_prices() {
        global $wpdb;

        // Self-healing database check for show_in_pos column
        $col_check = $wpdb->get_results("SHOW COLUMNS FROM " . OKJ_DB::get_table('product_prices') . " LIKE 'show_in_pos'");
        if (empty($col_check)) {
            OKJ_DB::install();
        }

        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('product_prices') . " WHERE id = %s", $id), ARRAY_A) : null;
            $sellers = $wpdb->get_results("SELECT id, name FROM " . OKJ_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);

            // Fetch all unique tags from stored products
            $all_tags_raw = $wpdb->get_col("SELECT DISTINCT tags FROM " . OKJ_DB::get_table('product_prices') . " WHERE tags IS NOT NULL AND tags != ''");
            $existing_tags = [];
            if (is_array($all_tags_raw)) {
                foreach ($all_tags_raw as $tags_str) {
                    $splitted = array_map('trim', explode(',', $tags_str));
                    foreach ($splitted as $tag) {
                        if ($tag !== '' && !in_array($tag, $existing_tags)) {
                            $existing_tags[] = $tag;
                        }
                    }
                }
            }
            sort($existing_tags);

            // Fetch all unique categories from stored products
            $existing_categories = $wpdb->get_col("SELECT DISTINCT category FROM " . OKJ_DB::get_table('product_prices') . " WHERE category IS NOT NULL AND category != ''");
            if (!is_array($existing_categories)) {
                $existing_categories = [];
            }
            sort($existing_categories);

            $this->render_template('product-prices', [
                'action' => $action, 
                'row' => $row, 
                'sellers' => $sellers,
                'existing_tags' => $existing_tags,
                'existing_categories' => $existing_categories
            ]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . OKJ_DB::get_table('product_prices'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT p.*, s.name as seller_name, s.email as seller_email, s.phone as seller_phone, s.telegram as seller_telegram, s.whatsapp as seller_whatsapp FROM " . OKJ_DB::get_table('product_prices') . " p LEFT JOIN " . OKJ_DB::get_table('sellers') . " s ON p.seller_id = s.id ORDER BY p.name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
            $this->render_template('product-prices', [
                'action' => 'list', 
                'rows' => $rows,
                'paged' => $paged,
                'total_pages' => $total_pages,
                'total_rows' => $total_rows,
                'per_page' => $per_page
            ]);
        }
    }

    public function view_reseller_products() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('reseller_products') . " WHERE id = %s", $id), ARRAY_A) : null;
            $prices = $wpdb->get_results("SELECT p.id, p.name, p.duration_days, p.sale_price, p.seller_id, s.name as seller_name FROM " . OKJ_DB::get_table('product_prices') . " p LEFT JOIN " . OKJ_DB::get_table('sellers') . " s ON p.seller_id = s.id ORDER BY p.name ASC", ARRAY_A);
            $sellers = $wpdb->get_results("SELECT id, name FROM " . OKJ_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);
            $this->render_template('reseller-products', ['action' => $action, 'row' => $row, 'prices' => $prices, 'sellers' => $sellers]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . OKJ_DB::get_table('reseller_products'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT r.*, s.name as seller_name FROM " . OKJ_DB::get_table('reseller_products') . " r LEFT JOIN " . OKJ_DB::get_table('sellers') . " s ON r.seller_id = s.id ORDER BY r.product_name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
            $this->render_template('reseller-products', [
                'action' => 'list', 
                'rows' => $rows,
                'paged' => $paged,
                'total_pages' => $total_pages,
                'total_rows' => $total_rows,
                'per_page' => $per_page
            ]);
        }
    }

    public function view_customers() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('customers') . " WHERE id = %s", $id), ARRAY_A) : null;
            $this->render_template('customers', ['action' => $action, 'row' => $row]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . OKJ_DB::get_table('customers'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('customers') . " ORDER BY name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
            $this->render_template('customers', [
                'action' => 'list', 
                'rows' => $rows,
                'paged' => $paged,
                'total_pages' => $total_pages,
                'total_rows' => $total_rows,
                'per_page' => $per_page
            ]);
        }
    }

    public function view_sellers() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('sellers') . " WHERE id = %s", $id), ARRAY_A) : null;
            $this->render_template('sellers', ['action' => $action, 'row' => $row]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . OKJ_DB::get_table('sellers'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('sellers') . " ORDER BY name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
            $this->render_template('sellers', [
                'action' => 'list', 
                'rows' => $rows,
                'paged' => $paged,
                'total_pages' => $total_pages,
                'total_rows' => $total_rows,
                'per_page' => $per_page
            ]);
        }
    }

    public function view_active_products() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A) : null;
            $customers = $wpdb->get_results("SELECT id, name FROM " . OKJ_DB::get_table('customers') . " ORDER BY name ASC", ARRAY_A);
            $resellers = $wpdb->get_results("SELECT r.id, r.product_name, r.duration_days, r.price, r.purchase_date, s.name as seller_name, (SELECT COUNT(*) FROM " . OKJ_DB::get_table('active_products') . " WHERE reseller_product_id = r.id) as is_used FROM " . OKJ_DB::get_table('reseller_products') . " r LEFT JOIN " . OKJ_DB::get_table('sellers') . " s ON r.seller_id = s.id ORDER BY r.product_name ASC", ARRAY_A);
            $this->render_template('active-products', ['action' => $action, 'row' => $row, 'customers' => $customers, 'resellers' => $resellers]);
        } else {
            // First: Self-healing check: Auto-update statuses where expires_at < today
            $today = wp_date('Y-m-d');
            $wpdb->query($wpdb->prepare(
                "UPDATE " . OKJ_DB::get_table('active_products') . " 
                 SET status = 'expired' 
                 WHERE expires_at < %s AND status = 'active'", 
                $today
            ));

            $status_filter = !empty($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'active';
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $table_ap = OKJ_DB::get_table('active_products');
            $table_cust = OKJ_DB::get_table('customers');

            // Build conditional WHERE clause
            $where = "1=1";
            if ($status_filter === 'active') {
                $where .= " AND a.status = 'active'";
            } elseif ($status_filter === 'expired') {
                $where .= " AND a.status = 'expired'";
            }

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$table_ap} a WHERE {$where}");
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.telegram as customer_telegram, c.whatsapp as customer_whatsapp 
                 FROM {$table_ap} a 
                 LEFT JOIN {$table_cust} c ON a.customer_id = c.id 
                 WHERE {$where} 
                 ORDER BY a.expires_at ASC 
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ), ARRAY_A);

            $this->render_template('active-products', [
                'action' => 'list', 
                'rows' => $rows,
                'paged' => $paged,
                'total_pages' => $total_pages,
                'total_rows' => $total_rows,
                'per_page' => $per_page
            ]);
        }
    }

    public function view_reminders() {
        global $wpdb;
        $today = wp_date('Y-m-d');

        $all_rows = $wpdb->get_results("SELECT r.*, a.product_label, c.name as customer_name FROM " . OKJ_DB::get_table('active_reminders') . " r INNER JOIN " . OKJ_DB::get_table('active_products') . " a ON r.active_product_id = a.id INNER JOIN " . OKJ_DB::get_table('customers') . " c ON r.customer_id = c.id ORDER BY r.reminder_date ASC", ARRAY_A);

        // For each active_product_id, find the smallest offset_days among
        // pending reminders whose reminder_date has already passed (or is today).
        $min_past_pending = [];
        foreach ($all_rows as $r) {
            if ($r['status'] === 'pending' && $r['reminder_date'] <= $today) {
                $apid = $r['active_product_id'];
                $offset = (int) $r['offset_days'];
                if (!isset($min_past_pending[$apid]) || $offset < $min_past_pending[$apid]) {
                    $min_past_pending[$apid] = $offset;
                }
            }
        }

        // Filter: hide past-pending rows that are NOT the closest milestone
        $rows = [];
        foreach ($all_rows as $r) {
            $apid = $r['active_product_id'];
            $offset = (int) $r['offset_days'];

            // Already sent → always show
            if ($r['status'] !== 'pending') {
                $rows[] = $r;
                continue;
            }

            // Future pending (not yet due) → always show
            if ($r['reminder_date'] > $today) {
                $rows[] = $r;
                continue;
            }

            // Past pending → only show the closest one (smallest offset)
            if (isset($min_past_pending[$apid]) && $offset === $min_past_pending[$apid]) {
                $rows[] = $r;
            }
            // else: superseded past milestone → hide
        }

        $this->render_template('reminders', ['rows' => $rows]);
    }

    public function view_shortlinks() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

        $row = null;
        if (($action === 'edit' || $action === 'add') && !empty($id)) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('shortlinks') . " WHERE id = %s", $id), ARRAY_A);
        }

        if ($action === 'add' || $action === 'edit') {
            $this->render_template('shortlinks', ['action' => $action, 'row' => $row]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . OKJ_DB::get_table('shortlinks'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('shortlinks') . " ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
            
            $this->render_template('shortlinks', [
                'action' => 'list',
                'rows' => $rows,
                'paged' => $paged,
                'total_pages' => $total_pages,
                'total_rows' => $total_rows,
                'per_page' => $per_page
            ]);
        }
    }

    public function view_reports() {
        global $wpdb;
        $sales = $wpdb->get_results("SELECT start_date, price FROM " . OKJ_DB::get_table('active_products') . " WHERE payment_status = 'paid'", ARRAY_A);
        $this->render_template('reports', ['sales' => $sales]);
    }

    public function view_logs() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . OKJ_DB::get_table('logs') . " ORDER BY happened_at DESC LIMIT 200", ARRAY_A);
        $this->render_template('logs', ['rows' => $rows]);
    }

    public function view_settings() {
        if (isset($_GET['check_release'])) {
            $settings = get_option('okj_settings_v1', []);
            $repo = !empty($settings['github_repo']) ? trim((string)$settings['github_repo']) : '';
            if ($repo) {
                delete_transient('okj_github_latest_release_' . md5($repo));
            }
            wp_safe_redirect(admin_url('admin.php?page=okj-settings'));
            exit;
        }

        $settings = get_option('okj_settings_v1', []);
        $this->render_template('settings', ['settings' => $settings]);
    }

    // POST action processors
    public function save_product_price() {
        check_admin_referer('okj_save_price');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $data = [
            'id' => $id,
            'name' => sanitize_text_field($_POST['name']),
            'category' => sanitize_text_field($_POST['category']),
            'tags' => !empty($_POST['tags']) && is_array($_POST['tags']) ? implode(',', array_map('sanitize_text_field', array_map('trim', $_POST['tags']))) : (!empty($_POST['tags']) ? sanitize_text_field($_POST['tags']) : ''),
            'seller_id' => !empty($_POST['seller_id']) ? sanitize_text_field($_POST['seller_id']) : null,
            'reseller_price' => (float)$_POST['reseller_price'],
            'sale_price' => (float)$_POST['sale_price'],
            'duration_days' => (int)$_POST['duration_days'],
            'affiliate_url' => !empty($_POST['affiliate_url']) ? esc_url_raw($_POST['affiliate_url']) : '',
            'show_in_pos' => isset($_POST['show_in_pos']) ? 1 : 0,
            'description' => wp_kses_post($_POST['description']),
            'notes' => wp_kses_post($_POST['notes']),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ];

        if ($is_edit) {
            $wpdb->update(OKJ_DB::get_table('product_prices'), $data, ['id' => $_POST['id']]);
            OKJ_Reseller_Manager::log('update', 'product_price', $id, "Updated product price: " . $data['name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(OKJ_DB::get_table('product_prices'), $data);
            OKJ_Reseller_Manager::log('create', 'product_price', $id, "Created product price: " . $data['name']);
        }

        if (!empty($_POST['affiliate_url']) && !empty($_POST['auto_create_shortlink'])) {
            $short_key = sanitize_title($data['name']);
            if (empty($short_key)) {
                $short_key = substr(md5($id), 0, 8);
            }
            
            $t_shortlinks = OKJ_DB::get_table('shortlinks');
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t_shortlinks} WHERE short_key = %s", $short_key), ARRAY_A);
            
            if ($existing) {
                $wpdb->update($t_shortlinks, [
                    'title' => 'Affiliate - ' . $data['name'],
                    'destination_url' => $data['affiliate_url'],
                    'updated_at' => current_time('mysql'),
                ], ['id' => $existing['id']]);
            } else {
                $wpdb->insert($t_shortlinks, [
                    'id' => wp_generate_uuid4(),
                    'title' => 'Affiliate - ' . $data['name'],
                    'short_key' => $short_key,
                    'destination_url' => $data['affiliate_url'],
                    'clicks' => 0,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]);
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-product-prices'));
        exit;
    }

    public function delete_product_price() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_delete_price_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(OKJ_DB::get_table('product_prices'), ['id' => $id]);
        OKJ_Reseller_Manager::log('delete', 'product_price', $id, "Deleted product price ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=okj-product-prices'));
        exit;
    }

    public function save_seller() {
        check_admin_referer('okj_save_seller');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $data = [
            'id' => $id,
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'telegram' => sanitize_text_field($_POST['telegram']),
            'whatsapp' => sanitize_text_field($_POST['whatsapp']),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ];

        if ($is_edit) {
            $wpdb->update(OKJ_DB::get_table('sellers'), $data, ['id' => $_POST['id']]);
            OKJ_Reseller_Manager::log('update', 'seller', $id, "Updated seller: " . $data['name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(OKJ_DB::get_table('sellers'), $data);
            OKJ_Reseller_Manager::log('create', 'seller', $id, "Created seller: " . $data['name']);
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-sellers'));
        exit;
    }

    public function delete_seller() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_delete_seller_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(OKJ_DB::get_table('sellers'), ['id' => $id]);
        OKJ_Reseller_Manager::log('delete', 'seller', $id, "Deleted seller ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=okj-sellers'));
        exit;
    }

    public function save_customer() {
        check_admin_referer('okj_save_customer');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $data = [
            'id' => $id,
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'telegram' => sanitize_text_field($_POST['telegram']),
            'whatsapp' => sanitize_text_field($_POST['whatsapp']),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ];

        if ($is_edit) {
            $wpdb->update(OKJ_DB::get_table('customers'), $data, ['id' => $_POST['id']]);
            OKJ_Reseller_Manager::log('update', 'customer', $id, "Updated customer: " . $data['name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(OKJ_DB::get_table('customers'), $data);
            OKJ_Reseller_Manager::log('create', 'customer', $id, "Created customer: " . $data['name']);
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-customers'));
        exit;
    }

    public function delete_customer() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_delete_customer_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(OKJ_DB::get_table('customers'), ['id' => $id]);
        OKJ_Reseller_Manager::log('delete', 'customer', $id, "Deleted customer ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=okj-customers'));
        exit;
    }

    public function save_shortlink() {
        check_admin_referer('okj_save_shortlink');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $short_key = sanitize_title($_POST['short_key']);
        if (empty($short_key)) {
            $short_key = substr(md5(uniqid()), 0, 8);
        }

        $data = [
            'id' => $id,
            'title' => sanitize_text_field($_POST['title']),
            'short_key' => $short_key,
            'destination_url' => esc_url_raw($_POST['destination_url']),
            'updated_at' => current_time('mysql'),
        ];

        $table = OKJ_DB::get_table('shortlinks');

        $conflict = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE short_key = %s AND id != %s", $short_key, $id));
        if ($conflict) {
            wp_die('Key Shortlink ini sudah digunakan oleh shortlink lain! Silakan gunakan key lain.');
        }

        if ($is_edit) {
            $wpdb->update($table, $data, ['id' => $_POST['id']]);
            OKJ_Reseller_Manager::log('update', 'shortlink', $id, "Updated shortlink: " . $data['title']);
        } else {
            $data['clicks'] = 0;
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            OKJ_Reseller_Manager::log('create', 'shortlink', $id, "Created shortlink: " . $data['title']);
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-shortlinks'));
        exit;
    }

    public function delete_shortlink() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_delete_shortlink_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(OKJ_DB::get_table('shortlinks'), ['id' => $id]);
        OKJ_Reseller_Manager::log('delete', 'shortlink', $id, "Deleted shortlink ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=okj-shortlinks'));
        exit;
    }

    public function save_reseller_product() {
        check_admin_referer('okj_save_reseller_product');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $price_id = sanitize_text_field($_POST['price_id']);
        $price_row = $wpdb->get_row($wpdb->prepare("SELECT name, duration_days FROM " . OKJ_DB::get_table('product_prices') . " WHERE id = %s", $price_id), ARRAY_A);

        $product_name = $price_row ? $price_row['name'] : '';
        $duration_days = isset($_POST['duration_days']) ? (int)$_POST['duration_days'] : ($price_row ? (int)$price_row['duration_days'] : 0);
        $purchase_date = sanitize_text_field($_POST['purchase_date']);
        $expires_at = wp_date('Y-m-d', strtotime($purchase_date . " +{$duration_days} days"));

        $data = [
            'id' => $id,
            'price_id' => $price_id,
            'product_name' => $product_name,
            'category' => sanitize_text_field($_POST['category']),
            'tags' => sanitize_text_field($_POST['tags']),
            'seller_id' => !empty($_POST['seller_id']) ? sanitize_text_field($_POST['seller_id']) : null,
            'reseller_name' => sanitize_text_field($_POST['reseller_name']),
            'reseller_contact' => sanitize_text_field($_POST['reseller_contact']),
            'purchase_date' => $purchase_date,
            'duration_days' => $duration_days,
            'description' => wp_kses_post($_POST['description']),
            'price' => (float)$_POST['price'],
            'expires_at' => $expires_at,
            'payment_status' => sanitize_text_field($_POST['payment_status']),
            'notes' => wp_kses_post($_POST['notes']),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ];

        // Attachment handles
        if (!empty($_FILES['payment_attachments']['name'])) {
            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }
            $attach_id = media_handle_upload('payment_attachments', 0);
            if (!is_wp_error($attach_id)) {
                $data['payment_attachments'] = wp_get_attachment_url($attach_id);
            }
        } elseif ($is_edit) {
            // Keep existing attachment
            $old_row = $wpdb->get_row($wpdb->prepare("SELECT payment_attachments FROM " . OKJ_DB::get_table('reseller_products') . " WHERE id = %s", $id), ARRAY_A);
            if ($old_row) {
                $data['payment_attachments'] = $old_row['payment_attachments'];
            }
        }

        if ($is_edit) {
            $wpdb->update(OKJ_DB::get_table('reseller_products'), $data, ['id' => $_POST['id']]);
            OKJ_Reseller_Manager::log('update', 'reseller_product', $id, "Updated reseller product: " . $data['product_name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(OKJ_DB::get_table('reseller_products'), $data);
            OKJ_Reseller_Manager::log('create', 'reseller_product', $id, "Created reseller product: " . $data['product_name']);
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-reseller-products'));
        exit;
    }

    public function delete_reseller_product() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_delete_reseller_product_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(OKJ_DB::get_table('reseller_products'), ['id' => $id]);
        OKJ_Reseller_Manager::log('delete', 'reseller_product', $id, "Deleted reseller product ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=okj-reseller-products'));
        exit;
    }

    public function save_active_product() {
        check_admin_referer('okj_save_active_product');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $reseller_product_id = sanitize_text_field($_POST['reseller_product_id']);
        $customer_id = sanitize_text_field($_POST['customer_id']);

        $rp = $wpdb->get_row($wpdb->prepare("SELECT product_name, duration_days FROM " . OKJ_DB::get_table('reseller_products') . " WHERE id = %s", $reseller_product_id), ARRAY_A);
        $cust = $wpdb->get_row($wpdb->prepare("SELECT name, phone, telegram, whatsapp, email FROM " . OKJ_DB::get_table('customers') . " WHERE id = %s", $customer_id), ARRAY_A);

        $start_date = sanitize_text_field($_POST['start_date']);
        $duration = $rp ? (int)$rp['duration_days'] : 30;
        $expires_at = wp_date('Y-m-d', strtotime($start_date . " +{$duration} days"));

        $today = wp_date('Y-m-d');
        $status = (strtotime($expires_at) < strtotime($today)) ? 'expired' : 'active';

        $cust_contact = '';
        if ($cust) {
            $parts = [];
            if ($cust['phone']) $parts[] = 'Telp: ' . $cust['phone'];
            if ($cust['whatsapp']) $parts[] = 'WA: ' . $cust['whatsapp'];
            if ($cust['telegram']) $parts[] = 'TG: ' . $cust['telegram'];
            if ($cust['email']) $parts[] = 'Email: ' . $cust['email'];
            $cust_contact = implode(' | ', $parts);
        }

        $data = [
            'id' => $id,
            'reseller_product_id' => $reseller_product_id,
            'product_label' => $rp ? $rp['product_name'] : 'Produk',
            'customer_id' => $customer_id,
            'customer_name' => $cust ? $cust['name'] : 'Customer',
            'customer_contact' => $cust_contact,
            'start_date' => $start_date,
            'duration_days' => $duration,
            'expires_at' => $expires_at,
            'status' => $status,
            'price' => (float)$_POST['price'],
            'payment_status' => sanitize_text_field($_POST['payment_status']),
            'notes' => wp_kses_post($_POST['notes']),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ];

        // Attachment handles
        if (!empty($_FILES['payment_attachments']['name'])) {
            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }
            $attach_id = media_handle_upload('payment_attachments', 0);
            if (!is_wp_error($attach_id)) {
                $data['payment_attachments'] = wp_get_attachment_url($attach_id);
            }
        } elseif ($is_edit) {
            // Keep existing attachment
            $old_row = $wpdb->get_row($wpdb->prepare("SELECT payment_attachments FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A);
            if ($old_row) {
                $data['payment_attachments'] = $old_row['payment_attachments'];
            }
        }

        if ($is_edit) {
            $wpdb->update(OKJ_DB::get_table('active_products'), $data, ['id' => $_POST['id']]);
            OKJ_Reseller_Manager::log('update', 'active_product', $id, "Updated active product: " . $data['product_label']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(OKJ_DB::get_table('active_products'), $data);
            OKJ_Reseller_Manager::log('create', 'active_product', $id, "Created active product: " . $data['product_label']);
        }

        // Sync reminders
        $saved_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A);
        if ($saved_row) {
            OKJ_Reseller_Manager::sync_reminders($saved_row);
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-active-products'));
        exit;
    }

    public function delete_active_product() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_delete_active_product_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(OKJ_DB::get_table('active_products'), ['id' => $id]);
        $wpdb->delete(OKJ_DB::get_table('active_reminders'), ['active_product_id' => $id]);
        OKJ_Reseller_Manager::log('delete', 'active_product', $id, "Deleted active product & reminders ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=okj-active-products'));
        exit;
    }

    public function renew_active_product() {
        check_admin_referer('okj_renew_active_product');
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $active_product_id = sanitize_text_field($_POST['active_product_id']);
        
        $ap = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $active_product_id), ARRAY_A);
        if (!$ap) {
            wp_die('Layanan Produk Aktif tidak ditemukan.');
        }

        $duration_days = (int)$_POST['duration_days'];
        $price = (float)$_POST['price'];
        $payment_status = sanitize_text_field($_POST['payment_status']);
        $notes = wp_kses_post($_POST['notes']);
        $start_from = sanitize_text_field($_POST['start_from']); // 'today' or 'old_expiry'

        $old_expires_at = $ap['expires_at'];
        
        // Calculate start date for renewal
        $today = wp_date('Y-m-d');
        if ($start_from === 'today') {
            $base_date = $today;
        } else {
            $base_date = $old_expires_at;
        }
        
        $new_expires_at = wp_date('Y-m-d', strtotime($base_date . " +{$duration_days} days"));
        $new_status = (strtotime($new_expires_at) < strtotime($today)) ? 'expired' : 'active';

        $attachment_url = '';
        if (!empty($_FILES['payment_attachments']['name'])) {
            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }
            $attach_id = media_handle_upload('payment_attachments', 0);
            if (!is_wp_error($attach_id)) {
                $attachment_url = wp_get_attachment_url($attach_id);
            }
        }

        // 1. Insert into active_product_renewals
        $renewal_id = wp_generate_uuid4();
        $wpdb->insert(OKJ_DB::get_table('active_product_renewals'), [
            'id' => $renewal_id,
            'active_product_id' => $active_product_id,
            'old_expires_at' => $old_expires_at,
            'new_expires_at' => $new_expires_at,
            'duration_days' => $duration_days,
            'price' => $price,
            'payment_status' => $payment_status,
            'payment_attachments' => $attachment_url ?: null,
            'notes' => $notes,
            'renewed_at' => current_time('mysql'),
            'updated_by' => get_current_user_id()
        ]);

        // 2. Update active_products table
        $update_data = [
            'expires_at' => $new_expires_at,
            'duration_days' => $ap['duration_days'] + $duration_days,
            'status' => $new_status,
            'payment_status' => $payment_status,
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id()
        ];
        if ($attachment_url) {
            $update_data['payment_attachments'] = $attachment_url;
        }
        $wpdb->update(OKJ_DB::get_table('active_products'), $update_data, ['id' => $active_product_id]);

        // 3. Reset reminder statuses back to pending so they can trigger again for the new expires_at date
        $wpdb->query($wpdb->prepare(
            "UPDATE " . OKJ_DB::get_table('active_reminders') . " 
             SET status = 'pending', sent_via = '', sent_at = NULL, last_error = NULL 
             WHERE active_product_id = %s",
            $active_product_id
        ));

        // 4. Log the action
        OKJ_Reseller_Manager::log(
            'renew_product', 
            'active_product', 
            $active_product_id, 
            "Renewed active product {$ap['product_label']} for customer {$ap['customer_name']}. New Expiry: {$new_expires_at} (+{$duration_days} days).",
            [
                'renewal_id' => $renewal_id,
                'price' => $price,
                'payment_status' => $payment_status,
                'old_expiry' => $old_expires_at,
                'new_expiry' => $new_expires_at
            ]
        );

        // 5. Sync active reminders
        $updated_ap = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $active_product_id), ARRAY_A);
        if ($updated_ap) {
            OKJ_Reseller_Manager::sync_reminders($updated_ap);
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-active-products&renewal_success=1'));
        exit;
    }

    public function ajax_get_renewal_history() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        global $wpdb;
        $active_product_id = sanitize_text_field($_GET['active_product_id']);
        
        $ap = $wpdb->get_row($wpdb->prepare("SELECT product_label, customer_name FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $active_product_id), ARRAY_A);
        if (!$ap) {
            wp_send_json_error(['message' => 'Produk tidak ditemukan.']);
        }

        $renewals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . OKJ_DB::get_table('active_product_renewals') . " 
             WHERE active_product_id = %s 
             ORDER BY renewed_at DESC",
            $active_product_id
        ), ARRAY_A);

        ob_start();
        ?>
        <div style="margin-bottom: 16px;">
            <p style="margin: 0; font-size: 14px; color: #475569;">
                Layanan: <strong style="color: #0f172a;"><?php echo esc_html($ap['product_label']); ?></strong><br>
                Customer: <strong style="color: #0f172a;"><?php echo esc_html($ap['customer_name']); ?></strong>
            </p>
        </div>
        <?php if (empty($renewals)): ?>
            <div style="text-align: center; padding: 24px; color: #64748b; background: #f8fafc; border-radius: 8px; border: 1px dashed #e2e8f0;">
                <span class="dashicons dashicons-info" style="font-size: 32px; width: 32px; height: 32px; color: #94a3b8; margin-bottom: 8px;"></span>
                <p style="margin: 0; font-size: 14px;">Belum ada riwayat perpanjangan (renewal) untuk layanan ini.</p>
            </div>
        <?php else: ?>
            <table style="font-size: 13px; width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 8px 10px; font-weight: 600; color: #475569;">Tgl Perpanjangan</th>
                        <th style="padding: 8px 10px; font-weight: 600; color: #475569;">Durasi</th>
                        <th style="padding: 8px 10px; font-weight: 600; color: #475569;">Masa Aktif Baru</th>
                        <th style="padding: 8px 10px; font-weight: 600; color: #475569;">Biaya</th>
                        <th style="padding: 8px 10px; font-weight: 600; color: #475569;">Status Bayar</th>
                        <th style="padding: 8px 10px; font-weight: 600; color: #475569;">Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($renewals as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px 10px;"><?php echo esc_html(wp_date(get_option('date_format') . ' H:i', strtotime($r['renewed_at']))); ?></td>
                            <td style="padding: 8px 10px;"><strong><?php echo esc_html($r['duration_days']); ?> Hari</strong></td>
                            <td style="padding: 8px 10px;">
                                <div style="font-size: 11px; color: #64748b; text-decoration: line-through;"><?php echo esc_html($r['old_expires_at']); ?></div>
                                <div style="font-weight: 600; color: #16a34a; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px; width: 14px; height: 14px; margin-top: 1px;"></span>
                                    <?php echo esc_html($r['new_expires_at']); ?>
                                </div>
                            </td>
                            <td style="padding: 8px 10px; font-weight: 600; color: #0f172a;">Rp <?php echo number_format_i18n((float)$r['price'], 0); ?></td>
                            <td style="padding: 8px 10px;">
                                <?php if ($r['payment_status'] === 'paid'): ?>
                                    <span class="okj-badge okj-badge-success" style="padding: 2px 6px; font-size: 11px;">Lunas</span>
                                <?php else: ?>
                                    <span class="okj-badge okj-badge-warning" style="padding: 2px 6px; font-size: 11px;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 10px;">
                                <?php if (!empty($r['payment_attachments'])): ?>
                                    <a href="<?php echo esc_url($r['payment_attachments']); ?>" target="_blank" style="text-decoration: none; color: #4f46e5; display: inline-flex; align-items: center; font-weight: 600;" title="Lihat Bukti Bayar">
                                        <span class="dashicons dashicons-image-filter" style="font-size: 16px; width: 16px; height: 16px; margin-right: 2px;"></span> Lihat
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($r['notes'])): ?>
                            <tr style="background: #fcfdfe; border-bottom: 1px solid #f1f5f9;">
                                <td colspan="6" style="padding: 6px 10px; font-size: 11px; color: #64748b; font-style: italic;">
                                    <strong>Catatan:</strong> <?php echo esc_html($r['notes']); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php
        endif;
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function save_settings() {
        check_admin_referer('okj_save_settings');
        if (!current_user_can('okj_manage_settings')) wp_die('Forbidden');

        $offsets_raw = sanitize_text_field($_POST['reminder_offsets']);
        $offsets_arr = array_filter(array_map('intval', explode(',', $offsets_raw)));

        $data = [
            'reminder_offsets' => $offsets_arr ?: [7,3,1],
            'cron_time' => sanitize_text_field($_POST['cron_time']),
            'email_subject' => sanitize_text_field($_POST['email_subject']),
            'email_template' => wp_kses_post($_POST['email_template']),
            'telegram_template' => wp_kses_post($_POST['telegram_template']),
            'whatsapp_template' => wp_kses_post($_POST['whatsapp_template']),
            'whatsapp_template_h7' => wp_kses_post($_POST['whatsapp_template_h7']),
            'whatsapp_template_h3' => wp_kses_post($_POST['whatsapp_template_h3']),
            'whatsapp_template_h1' => wp_kses_post($_POST['whatsapp_template_h1']),
            'smtp_enabled' => !empty($_POST['smtp_enabled']) ? 1 : 0,
            'smtp_host' => sanitize_text_field($_POST['smtp_host']),
            'smtp_port' => (int)$_POST['smtp_port'],
            'smtp_user' => sanitize_text_field($_POST['smtp_user']),
            'smtp_pass' => sanitize_text_field($_POST['smtp_pass']),
            'smtp_secure' => sanitize_text_field($_POST['smtp_secure']),
            'smtp_from_email' => sanitize_email($_POST['smtp_from_email']),
            'smtp_from_name' => sanitize_text_field($_POST['smtp_from_name']),
            'telegram_enabled' => !empty($_POST['telegram_enabled']) ? 1 : 0,
            'telegram_bot_token' => sanitize_text_field($_POST['telegram_bot_token']),
            'telegram_default_chat_id' => sanitize_text_field($_POST['telegram_default_chat_id']),
            'waha_enabled' => !empty($_POST['waha_enabled']) ? 1 : 0,
            'waha_api_url' => sanitize_url($_POST['waha_api_url']),
            'waha_api_token' => sanitize_text_field($_POST['waha_api_token']),
            'waha_session_name' => sanitize_text_field($_POST['waha_session_name']),
            'pdf_invoice_title' => sanitize_text_field($_POST['pdf_invoice_title']),
            'pdf_company_name' => sanitize_text_field($_POST['pdf_company_name']),
            'pdf_company_address' => sanitize_text_field($_POST['pdf_company_address']),
            'pdf_company_phone' => sanitize_text_field($_POST['pdf_company_phone']),
            'pdf_payment_details' => wp_kses_post($_POST['pdf_payment_details']),
            'pdf_primary_color' => sanitize_text_field($_POST['pdf_primary_color']),
            'github_repo' => sanitize_text_field($_POST['github_repo']),
            'github_token' => sanitize_text_field($_POST['github_token']),
            'pos_enable_cash' => !empty($_POST['pos_enable_cash']) ? 1 : 0,
            'pos_enable_transfer' => !empty($_POST['pos_enable_transfer']) ? 1 : 0,
            'pos_enable_qris' => !empty($_POST['pos_enable_qris']) ? 1 : 0,
        ];

        update_option('okj_settings_v1', $data);
        OKJ_Reseller_Manager::log('save_settings', 'settings', '', 'Updated plugin settings configuration');

        wp_safe_redirect(admin_url('admin.php?page=okj-settings'));
        exit;
    }

    public function backup_data() {
        check_admin_referer('okj_backup_data');
        OKJ_Backup::export_json();
    }

    public function restore_data() {
        check_admin_referer('okj_restore_data');
        if (!current_user_can('okj_manage_settings')) wp_die('Forbidden');

        if (!empty($_FILES['restore_file']['tmp_name'])) {
            $res = OKJ_Backup::import_json($_FILES['restore_file']['tmp_name']);
            $msg = $res['ok'] ? 'Database & settings successfully restored' : 'Restore failed: ' . $res['error'];
        } else {
            $msg = 'Please upload a backup JSON file first';
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-settings&msg=' . urlencode($msg)));
        exit;
    }

    public function download_invoice_pdf() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_invoice_pdf_' . $id);

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A);
        if (!$row) wp_die('Active product not found');

        $settings = get_option('okj_settings_v1', []);
        $pdf_gen = new OKJ_PDF_Invoice();
        $pdf_data = $pdf_gen->generate_invoice($row, $settings);

        OKJ_Reseller_Manager::log('download_invoice', 'active_product', $id, "Downloaded invoice PDF: " . $row['product_label']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice-' . $id . '.pdf"');
        header('Content-Length: ' . strlen($pdf_data));
        echo $pdf_data;
        exit;
    }

    public function send_reminder_manual() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('okj_send_reminder_' . $id);
        if (!current_user_can('okj_manage')) wp_die('Forbidden');

        global $wpdb;
        $r = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, a.product_label, a.start_date, a.duration_days, a.notes, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.telegram as customer_telegram, c.whatsapp as customer_whatsapp, a.expires_at, a.price
             FROM " . OKJ_DB::get_table('active_reminders') . " r
             INNER JOIN " . OKJ_DB::get_table('active_products') . " a ON r.active_product_id = a.id
             INNER JOIN " . OKJ_DB::get_table('customers') . " c ON r.customer_id = c.id
             WHERE r.id = %s",
            $id
        ), ARRAY_A);

        if (!$r) wp_die('Reminder record not found');

        $notifier = new OKJ_Notifier();
        $settings = get_option('okj_settings_v1', []);

        $vars = [
            'customer_name' => $r['customer_name'],
            'customer_email' => $r['customer_email'],
            'customer_phone' => $r['customer_phone'],
            'customer_telegram' => $r['customer_telegram'],
            'customer_whatsapp' => $r['customer_whatsapp'],
            'product_label' => $r['product_label'],
            'expires_at' => date_i18n(get_option('date_format'), strtotime($r['expires_at'])),
            'price' => 'Rp ' . number_format_i18n((float)$r['price'], 0),
            'remaining_days' => $r['offset_days'],
            'start_date' => date_i18n(get_option('date_format'), strtotime($r['start_date'])),
            'duration_days' => $r['duration_days'],
            'notes' => $r['notes'] ?: '-',
            'invoice_url' => admin_url('admin-post.php?action=okj_invoice_pdf&id=' . $r['active_product_id']),
            'company_name' => !empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name'),
            'company_address' => !empty($settings['pdf_company_address']) ? $settings['pdf_company_address'] : '',
            'company_phone' => !empty($settings['pdf_company_phone']) ? $settings['pdf_company_phone'] : '',
            'payment_details' => !empty($settings['pdf_payment_details']) ? $settings['pdf_payment_details'] : '',
        ];

        $sent_channels = [];
        $error_log = [];

        // Email
        if (!empty($settings['smtp_enabled']) && !empty($r['customer_email'])) {
            $sub_tpl = !empty($settings['email_subject']) ? $settings['email_subject'] : '[Reminder] {product_label} akan expired';
            $body_tpl = !empty($settings['email_template']) ? $settings['email_template'] : '';
            $res = $notifier->send_email($r['customer_email'], $sub_tpl, $body_tpl, $vars);
            if ($res['ok']) $sent_channels[] = 'email'; else $error_log[] = 'Email: ' . $res['error'];
        }

        // Telegram
        if (!empty($settings['telegram_enabled']) && !empty($r['customer_telegram'])) {
            $tele_tpl = !empty($settings['telegram_template']) ? $settings['telegram_template'] : '';
            $message = $notifier->render_template($tele_tpl, $vars);
            $res = $notifier->send_telegram($r['customer_telegram'], $message);
            if ($res['ok']) $sent_channels[] = 'telegram'; else $error_log[] = 'Telegram: ' . $res['error'];
        }

        // WhatsApp WAHA
        if (!empty($settings['waha_enabled']) && !empty($r['customer_whatsapp'])) {
            $wa_tpl = '';
            if ($r['offset_days'] == 7) {
                $wa_tpl = !empty($settings['whatsapp_template_h7']) ? $settings['whatsapp_template_h7'] : '';
            } elseif ($r['offset_days'] == 3) {
                $wa_tpl = !empty($settings['whatsapp_template_h3']) ? $settings['whatsapp_template_h3'] : '';
            } elseif ($r['offset_days'] == 1) {
                $wa_tpl = !empty($settings['whatsapp_template_h1']) ? $settings['whatsapp_template_h1'] : '';
            }
            if (!$wa_tpl) {
                $wa_tpl = !empty($settings['whatsapp_template']) ? $settings['whatsapp_template'] : '';
            }

            $message = $notifier->render_template($wa_tpl, $vars);
            $res = $notifier->send_waha($r['customer_whatsapp'], $message);
            if ($res['ok']) $sent_channels[] = 'whatsapp'; else $error_log[] = 'WhatsApp: ' . $res['error'];
        }

        $now = current_time('mysql');
        if (!empty($sent_channels)) {
            $wpdb->update(OKJ_DB::get_table('active_reminders'), [
                'status' => 'sent',
                'sent_via' => implode(',', $sent_channels),
                'sent_at' => $now,
                'last_error' => !empty($error_log) ? implode('; ', $error_log) : null,
                'updated_at' => $now,
            ], ['id' => $r['id']]);

            OKJ_Reseller_Manager::log('send_reminder', 'reminder', $r['id'], "Manual reminder triggered successfully via " . implode(',', $sent_channels));
        } else {
            $wpdb->update(OKJ_DB::get_table('active_reminders'), [
                'last_error' => implode('; ', $error_log),
                'updated_at' => $now,
            ], ['id' => $r['id']]);

            OKJ_Reseller_Manager::log('send_reminder_fail', 'reminder', $r['id'], "Manual reminder failed: " . implode('; ', $error_log));
        }

        wp_safe_redirect(admin_url('admin.php?page=okj-reminders'));
        exit;
    }

    public function quick_add_seller() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        $name = !empty($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            wp_send_json_error(['message' => 'Nama Seller tidak boleh kosong.']);
        }

        global $wpdb;
        $id = wp_generate_uuid4();
        $data = [
            'id' => $id,
            'name' => $name,
            'whatsapp' => !empty($_POST['whatsapp']) ? sanitize_text_field($_POST['whatsapp']) : '',
            'telegram' => !empty($_POST['telegram']) ? sanitize_text_field($_POST['telegram']) : '',
            'phone' => !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'email' => !empty($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $inserted = $wpdb->insert(OKJ_DB::get_table('sellers'), $data);
        if ($inserted) {
            OKJ_Reseller_Manager::log('create', 'seller', $id, "Quick created seller: " . $name);
            wp_send_json_success([
                'id' => $id,
                'name' => $name
            ]);
        } else {
            wp_send_json_error(['message' => 'Gagal menyimpan data Seller ke database.']);
        }
    }

    public function quick_add_customer() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        $name = !empty($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            wp_send_json_error(['message' => 'Nama Customer tidak boleh kosong.']);
        }

        global $wpdb;
        $id = wp_generate_uuid4();
        $data = [
            'id' => $id,
            'name' => $name,
            'whatsapp' => !empty($_POST['whatsapp']) ? sanitize_text_field($_POST['whatsapp']) : '',
            'telegram' => !empty($_POST['telegram']) ? sanitize_text_field($_POST['telegram']) : '',
            'phone' => !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'email' => !empty($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $inserted = $wpdb->insert(OKJ_DB::get_table('customers'), $data);
        if ($inserted) {
            OKJ_Reseller_Manager::log('create', 'customer', $id, "Quick created customer: " . $name);
            wp_send_json_success([
                'id' => $id,
                'name' => $name
            ]);
        } else {
            wp_send_json_error(['message' => 'Gagal menyimpan data Customer ke database.']);
        }
     }

    public function ajax_test_waha() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        $api_url = !empty($_POST['waha_api_url']) ? esc_url_raw($_POST['waha_api_url']) : '';
        $token = !empty($_POST['waha_api_token']) ? sanitize_text_field($_POST['waha_api_token']) : '';
        $session = !empty($_POST['waha_session_name']) ? sanitize_text_field($_POST['waha_session_name']) : 'default';
        $target = !empty($_POST['target_phone']) ? sanitize_text_field($_POST['target_phone']) : '';

        if (!$api_url) {
            wp_send_json_error(['message' => 'API URL WAHA tidak boleh kosong.']);
        }
        if (!$target) {
            wp_send_json_error(['message' => 'Nomor HP tujuan pengetesan wajib diisi.']);
        }

        // Clean & internationalize phone
        $target = preg_replace('/[^0-9]/', '', $target);
        if (strpos($target, '0') === 0) {
            $target = '62' . substr($target, 1);
        }
        if (strpos($target, '@') === false) {
            $target .= '@c.us';
        }

        $url = rtrim($api_url, '/') . '/api/sendText';
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
            $headers['X-Api-Key'] = $token;
        }

        $message = "Halo! Ini adalah pesan uji coba dari OKJualin Anda. Koneksi berhasil! 🚀";

        $resp = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => $headers,
            'body' => wp_json_encode([
                'session' => $session,
                'chatId' => $target,
                'text' => $message,
            ]),
        ]);

        if (is_wp_error($resp)) {
            wp_send_json_error(['message' => 'Error: ' . $resp->get_error_message()]);
        }

        $code = (int)wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            wp_send_json_error(['message' => "Gagal dengan HTTP Status Code " . $code . ". Silakan periksa kembali URL, Session, atau Token API Anda."]);
        }

        wp_send_json_success(['message' => 'Pesan uji coba berhasil terkirim via WAHA!']);
    }

    public function ajax_test_telegram() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        $token = !empty($_POST['telegram_bot_token']) ? sanitize_text_field($_POST['telegram_bot_token']) : '';
        $chat_id = !empty($_POST['telegram_default_chat_id']) ? sanitize_text_field($_POST['telegram_default_chat_id']) : '';

        if (!$token) {
            wp_send_json_error(['message' => 'Bot Token Telegram tidak boleh kosong.']);
        }
        if (!$chat_id) {
            wp_send_json_error(['message' => 'Chat ID Telegram tidak boleh kosong.']);
        }

        $url = 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage';
        $message = "Halo! Ini adalah pesan uji coba dari OKJualin Anda. Koneksi berhasil! 🚀";

        $resp = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]),
        ]);

        if (is_wp_error($resp)) {
            wp_send_json_error(['message' => 'Error: ' . $resp->get_error_message()]);
        }

        $code = (int)wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            wp_send_json_error(['message' => "Gagal dengan HTTP Status Code " . $code . ". Silakan periksa kembali Bot Token atau Chat ID Anda."]);
        }

        wp_send_json_success(['message' => 'Pesan uji coba berhasil terkirim via Telegram!']);
    }

    public function ajax_test_smtp() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        $host = !empty($_POST['smtp_host']) ? sanitize_text_field($_POST['smtp_host']) : '';
        $port = !empty($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587;
        $user = !empty($_POST['smtp_user']) ? sanitize_text_field($_POST['smtp_user']) : '';
        $pass = !empty($_POST['smtp_pass']) ? sanitize_text_field($_POST['smtp_pass']) : '';
        $secure = !empty($_POST['smtp_secure']) ? sanitize_text_field($_POST['smtp_secure']) : 'tls';
        $from_email = !empty($_POST['smtp_from_email']) ? sanitize_email($_POST['smtp_from_email']) : '';
        $from_name = !empty($_POST['smtp_from_name']) ? sanitize_text_field($_POST['smtp_from_name']) : '';

        if (!$host || !$user || !$pass || !$from_email) {
            wp_send_json_error(['message' => 'Host, Username, Password, dan Sender Email wajib diisi untuk pengetesan.']);
        }

        // We temporarily intercept PHPMailer or run a direct custom send logic
        // Using WP's mailer directly with custom phpmailer hook
        $temp_hook = function($phpmailer) use ($host, $port, $user, $pass, $secure, $from_email, $from_name) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $host;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $port;
            $phpmailer->Username = $user;
            $phpmailer->Password = $pass;
            if ($secure === 'ssl' || $secure === 'tls') {
                $phpmailer->SMTPSecure = $secure;
            }
            $phpmailer->setFrom($from_email, $from_name ?: get_bloginfo('name'));
        };

        add_action('phpmailer_init', $temp_hook, 999);
        
        $subject = 'OKJualin - Test SMTP Connection';
        $body = "Halo!\n\nIni adalah email uji coba untuk memverifikasi pengaturan SMTP Anda pada plugin OKJualin.\n\nKoneksi SMTP Anda berhasil terintegrasi dengan sempurna! 🚀";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $ok = wp_mail($from_email, $subject, $body, $headers);
        
        remove_action('phpmailer_init', $temp_hook, 999);

        if ($ok) {
            wp_send_json_success(['message' => 'Email uji coba berhasil terkirim ke ' . $from_email . '! Koneksi SMTP sukses.']);
        } else {
            wp_send_json_error(['message' => 'Gagal mengirim email uji coba. Silakan periksa kembali konfigurasi detail SMTP Anda atau log server.']);
        }
    }

    public function view_pos() {
        global $wpdb;
        $customers = $wpdb->get_results("SELECT id, name, phone, whatsapp FROM " . OKJ_DB::get_table('customers') . " ORDER BY name ASC", ARRAY_A);
        $sellers = $wpdb->get_results("SELECT id, name FROM " . OKJ_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);
        $categories = $wpdb->get_col("SELECT DISTINCT category FROM " . OKJ_DB::get_table('product_prices') . " WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");

        $this->render_template('pos', [
            'customers' => $customers,
            'sellers' => $sellers,
            'categories' => $categories
        ]);
    }

    public function ajax_pos_get_products() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        global $wpdb;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

        $table = OKJ_DB::get_table('product_prices');

        // Self-healing database check: automatically install tables if missing
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            OKJ_DB::install();
        }

        $query = "SELECT * FROM {$table} WHERE show_in_pos = 1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }

        $query .= " ORDER BY name ASC";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }

        wp_send_json_success($results);
    }

    public function ajax_pos_checkout() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        global $wpdb;

        // Support retrieve_only mode for logs thermal receipt printing
        if (isset($_GET['retrieve_only']) && !empty($_GET['transaction_id'])) {
            $tx_id = sanitize_text_field($_GET['transaction_id']);
            $t_transactions = OKJ_DB::get_table('pos_transactions');
            $t_items = OKJ_DB::get_table('pos_transaction_items');

            $tx = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t_transactions} WHERE id = %s", $tx_id), ARRAY_A);
            if (!$tx) {
                wp_send_json_error(['message' => 'Transaksi tidak ditemukan.']);
            }

            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t_items} WHERE transaction_id = %s", $tx_id), ARRAY_A);

            wp_send_json_success([
                'transaction_id' => $tx['id'],
                'transaction_no' => $tx['transaction_no'],
                'customer_name' => $tx['customer_name'],
                'subtotal' => (float)$tx['subtotal'],
                'discount' => (float)$tx['discount'],
                'total' => (float)$tx['total'],
                'payment_method' => $tx['payment_method'],
                'created_at' => $tx['created_at'],
                'items' => $items
            ]);
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || empty($payload['items'])) {
            wp_send_json_error(['message' => 'Keranjang belanja kosong atau data tidak valid.']);
        }

        $customer_id = sanitize_text_field($payload['customer_id']);
        $seller_id = !empty($payload['seller_id']) ? sanitize_text_field($payload['seller_id']) : null;
        $discount = (float)$payload['discount'];
        $notes = sanitize_textarea_field($payload['notes']);
        $payment_method = sanitize_text_field($payload['payment_method']);
        $payment_status = !empty($payload['payment_status']) ? sanitize_text_field($payload['payment_status']) : 'paid';

        // Fetch customer details
        $customer_name = 'Guest';
        $customer_whatsapp = '';
        $cust = null;
        if ($customer_id) {
            $cust = $wpdb->get_row($wpdb->prepare("SELECT name, phone, telegram, whatsapp, email FROM " . OKJ_DB::get_table('customers') . " WHERE id = %s", $customer_id), ARRAY_A);
            if ($cust) {
                $customer_name = $cust['name'];
                $customer_whatsapp = $cust['whatsapp'] ?: $cust['phone'];
            }
        }

        // Generate Transaction No.
        $date_prefix = wp_date('ymd');
        $t_transactions = OKJ_DB::get_table('pos_transactions');
        $latest_no = $wpdb->get_var($wpdb->prepare("SELECT transaction_no FROM {$t_transactions} WHERE transaction_no LIKE %s ORDER BY created_at DESC LIMIT 1", 'TR-' . $date_prefix . '-%'));
        
        $seq = 1;
        if ($latest_no) {
            $parts = explode('-', $latest_no);
            if (count($parts) === 3) {
                $seq = (int)$parts[2] + 1;
            }
        }
        $transaction_no = 'TR-' . $date_prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $transaction_id = wp_generate_uuid4();

        // Calculate Totals & Insert Items
        $subtotal = 0;
        $t_pos_items = OKJ_DB::get_table('pos_transaction_items');
        
        $item_entries = [];
        foreach ($payload['items'] as $item) {
            $p_id = sanitize_text_field($item['id']);
            $product = $wpdb->get_row($wpdb->prepare("SELECT name, sale_price, duration_days FROM " . OKJ_DB::get_table('product_prices') . " WHERE id = %s", $p_id), ARRAY_A);
            if (!$product) {
                wp_send_json_error(['message' => 'Produk tidak ditemukan di master harga.']);
            }

            $price = (float)$product['sale_price'];
            $qty = max(1, (int)$item['qty']);
            $item_subtotal = $price * $qty;
            $subtotal += $item_subtotal;

            $item_entries[] = [
                'id' => wp_generate_uuid4(),
                'transaction_id' => $transaction_id,
                'product_id' => $p_id,
                'product_name' => $product['name'],
                'price' => $price,
                'qty' => $qty,
                'duration_days' => (int)$product['duration_days'],
                'subtotal' => $item_subtotal,
                'created_at' => current_time('mysql'),
            ];
        }

        // Deduct Discount
        $total = max(0, $subtotal - $discount);

        // Insert Transaction Header
        $wpdb->insert($t_transactions, [
            'id' => $transaction_id,
            'transaction_no' => $transaction_no,
            'customer_id' => $customer_id ?: null,
            'customer_name' => $customer_name,
            'seller_id' => $seller_id ?: null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => 0,
            'total' => $total,
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ]);

        // Insert Transaction Items
        foreach ($item_entries as $entry) {
            $wpdb->insert($t_pos_items, $entry);

            // Automate: Create entry in okj_active_products if product has duration
            if ($entry['duration_days'] > 0 && $customer_id) {
                $cust_contact = '';
                if ($cust) {
                    $parts = [];
                    if ($cust['phone']) $parts[] = 'Telp: ' . $cust['phone'];
                    if ($cust['whatsapp']) $parts[] = 'WA: ' . $cust['whatsapp'];
                    if ($cust['telegram']) $parts[] = 'TG: ' . $cust['telegram'];
                    if ($cust['email']) $parts[] = 'Email: ' . $cust['email'];
                    $cust_contact = implode(' | ', $parts);
                }

                $active_id = wp_generate_uuid4();
                $start_date = wp_date('Y-m-d');
                $expires_at = wp_date('Y-m-d', strtotime($start_date . " +{$entry['duration_days']} days"));

                $wpdb->insert(OKJ_DB::get_table('active_products'), [
                    'id' => $active_id,
                    'reseller_product_id' => '', // Direct POS sale, no reseller product ID needed
                    'product_label' => $entry['product_name'],
                    'customer_id' => $customer_id,
                    'customer_name' => $customer_name,
                    'customer_contact' => $cust_contact,
                    'start_date' => $start_date,
                    'duration_days' => $entry['duration_days'],
                    'expires_at' => $expires_at,
                    'status' => 'active',
                    'price' => $entry['price'] * $entry['qty'],
                    'payment_status' => $payment_status,
                    'notes' => 'Pembelian via POS (' . $transaction_no . ')',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'updated_by' => get_current_user_id(),
                ]);

                // Sync reminders for this active product
                $saved_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . OKJ_DB::get_table('active_products') . " WHERE id = %s", $active_id), ARRAY_A);
                if ($saved_row) {
                    OKJ_Reseller_Manager::sync_reminders($saved_row);
                }
            }
        }

        OKJ_Reseller_Manager::log('pos_checkout', 'pos_transaction', $transaction_id, "Completed POS transaction: " . $transaction_no . " for customer: " . $customer_name);

        wp_send_json_success([
            'transaction_id' => $transaction_id,
            'transaction_no' => $transaction_no,
            'customer_name' => $customer_name,
            'customer_whatsapp' => $customer_whatsapp,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => $payment_method,
            'created_at' => wp_date('Y-m-d H:i:s'),
            'items' => $item_entries
        ]);
    }

    public function ajax_pos_send_wa_struk() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        global $wpdb;
        $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
        $whatsapp_no = isset($_POST['whatsapp_no']) ? sanitize_text_field($_POST['whatsapp_no']) : '';

        if (empty($transaction_id) || empty($whatsapp_no)) {
            wp_send_json_error(['message' => 'Parameter tidak lengkap.']);
        }

        $t_transactions = OKJ_DB::get_table('pos_transactions');
        $t_pos_items = OKJ_DB::get_table('pos_transaction_items');

        $tx = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t_transactions} WHERE id = %s", $transaction_id), ARRAY_A);
        if (!$tx) {
            wp_send_json_error(['message' => 'Transaksi tidak ditemukan.']);
        }

        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t_pos_items} WHERE transaction_id = %s", $transaction_id), ARRAY_A);

        // Build elegant WhatsApp invoice message
        $settings = get_option('okj_settings_v1', []);
        $company_name = !empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name');
        
        $msg = "*NOTA PEMBELIAN - {$company_name}*\n";
        $msg .= "------------------------------------------\n";
        $msg .= "No. Transaksi: `{$tx['transaction_no']}`\n";
        $msg .= "Tanggal: " . wp_date('d-m-Y H:i', strtotime($tx['created_at'])) . "\n";
        $msg .= "Pelanggan: {$tx['customer_name']}\n";
        $msg .= "------------------------------------------\n";
        
        foreach ($items as $item) {
            $formatted_price = 'Rp ' . number_format($item['price'], 0, ',', '.');
            $formatted_sub = 'Rp ' . number_format($item['subtotal'], 0, ',', '.');
            $msg .= "• {$item['product_name']}\n";
            $msg .= "   {$item['qty']} x {$formatted_price} = *{$formatted_sub}*\n";
        }
        
        $msg .= "------------------------------------------\n";
        $msg .= "Subtotal: Rp " . number_format($tx['subtotal'], 0, ',', '.') . "\n";
        if ($tx['discount'] > 0) {
            $msg .= "Diskon: -Rp " . number_format($tx['discount'], 0, ',', '.') . "\n";
        }
        $msg .= "*TOTAL BAYAR: Rp " . number_format($tx['total'], 0, ',', '.') . "*\n";
        $msg .= "Metode Bayar: " . strtoupper($tx['payment_method']) . "\n";
        $msg .= "Status: *LUNAS*\n";
        $msg .= "------------------------------------------\n";
        $msg .= "Terima kasih atas kunjungan/pembelian Anda! 🙏\n";

        $notifier = new OKJ_Notifier();
        $res = $notifier->send_waha($whatsapp_no, $msg);

        if ($res['ok']) {
            wp_send_json_success(['message' => 'Struk berhasil dikirim ke WhatsApp ' . $whatsapp_no]);
        } else {
            wp_send_json_error(['message' => 'Gagal mengirim struk via WhatsApp: ' . $res['error']]);
        }
    }

    public function ajax_pos_update_status() {
        if (!current_user_can('okj_manage')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        global $wpdb;
        $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (empty($transaction_id) || empty($new_status)) {
            wp_send_json_error(['message' => 'Parameter tidak lengkap.']);
        }

        $t_transactions = OKJ_DB::get_table('pos_transactions');
        $tx = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t_transactions} WHERE id = %s", $transaction_id), ARRAY_A);
        if (!$tx) {
            wp_send_json_error(['message' => 'Transaksi tidak ditemukan.']);
        }

        $wpdb->update($t_transactions, [
            'payment_status' => $new_status,
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id()
        ], ['id' => $transaction_id]);

        OKJ_Reseller_Manager::log('pos_update_status', 'pos_transaction', $transaction_id, "Updated transaction status for: " . $tx['transaction_no'] . " to: " . $new_status);

        // Notify client if they entered WhatsApp details
        $status_label = 'Pending / Menunggu';
        if ($new_status === 'processing') $status_label = 'Sedang Diproses 🧑‍🍳';
        if ($new_status === 'paid' || $new_status === 'completed') $status_label = 'Selesai & Lunas 🟢';

        if (!empty($tx['customer_id'])) {
            $cust = $wpdb->get_row($wpdb->prepare("SELECT whatsapp, phone FROM " . OKJ_DB::get_table('customers') . " WHERE id = %s", $tx['customer_id']), ARRAY_A);
            $wa_no = $cust ? ($cust['whatsapp'] ?: $cust['phone']) : '';
            if ($wa_no) {
                $notifier = new OKJ_Notifier();
                $settings = get_option('okj_settings_v1', []);
                $company_name = !empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name');
                
                $msg = "*UPDATE PESANAN - {$company_name}*\n";
                $msg .= "------------------------------------------\n";
                $msg .= "No. Transaksi: `{$tx['transaction_no']}`\n";
                $msg .= "Status Terbaru: *{$status_label}*\n";
                $msg .= "------------------------------------------\n";
                $msg .= "Terima kasih atas kesabaran Anda! Pesanan Anda sedang kami proses dengan sepenuh hati. 🙏";

                $notifier->send_waha($wa_no, $msg);
            }
        }

        wp_send_json_success(['message' => 'Status transaksi berhasil diubah ke ' . $new_status]);
    }

    public function ajax_public_get_products() {
        global $wpdb;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

        $table = OKJ_DB::get_table('product_prices');
        $query = "SELECT * FROM {$table} WHERE show_in_pos = 1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }

        $query .= " ORDER BY name ASC";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }

        wp_send_json_success($results);
    }

    public function ajax_public_place_order() {
        global $wpdb;
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || empty($payload['items']) || empty($payload['customer_name'])) {
            wp_send_json_error(['message' => 'Keranjang belanja kosong atau nama belum diisi.']);
        }

        $name = sanitize_text_field($payload['customer_name']);
        $whatsapp = sanitize_text_field($payload['customer_whatsapp']);
        $notes = sanitize_textarea_field($payload['notes']);
        $payment_method = sanitize_text_field($payload['payment_method']);

        // Search or Create Customer
        $customer_id = null;
        if (!empty($whatsapp)) {
            $cust = $wpdb->get_row($wpdb->prepare("SELECT id FROM " . OKJ_DB::get_table('customers') . " WHERE whatsapp = %s OR phone = %s", $whatsapp, $whatsapp), ARRAY_A);
            if ($cust) {
                $customer_id = $cust['id'];
            } else {
                $customer_id = wp_generate_uuid4();
                $wpdb->insert(OKJ_DB::get_table('customers'), [
                    'id' => $customer_id,
                    'name' => $name,
                    'phone' => $whatsapp,
                    'whatsapp' => $whatsapp,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]);
            }
        }

        // Generate Transaction No.
        $date_prefix = wp_date('ymd');
        $t_transactions = OKJ_DB::get_table('pos_transactions');
        $latest_no = $wpdb->get_var($wpdb->prepare("SELECT transaction_no FROM {$t_transactions} WHERE transaction_no LIKE %s ORDER BY created_at DESC LIMIT 1", 'TR-' . $date_prefix . '-%'));
        
        $seq = 1;
        if ($latest_no) {
            $parts = explode('-', $latest_no);
            if (count($parts) === 3) {
                $seq = (int)$parts[2] + 1;
            }
        }
        $transaction_no = 'TR-' . $date_prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $transaction_id = wp_generate_uuid4();

        // Calculate Totals & Gather Items
        $subtotal = 0;
        $t_pos_items = OKJ_DB::get_table('pos_transaction_items');
        
        $item_entries = [];
        foreach ($payload['items'] as $item) {
            $p_id = sanitize_text_field($item['id']);
            $product = $wpdb->get_row($wpdb->prepare("SELECT name, sale_price, duration_days FROM " . OKJ_DB::get_table('product_prices') . " WHERE id = %s", $p_id), ARRAY_A);
            if (!$product) {
                wp_send_json_error(['message' => 'Produk tidak ditemukan.']);
            }

            $price = (float)$product['sale_price'];
            $qty = max(1, (int)$item['qty']);
            $item_subtotal = $price * $qty;
            $subtotal += $item_subtotal;

            $item_entries[] = [
                'id' => wp_generate_uuid4(),
                'transaction_id' => $transaction_id,
                'product_id' => $p_id,
                'product_name' => $product['name'],
                'price' => $price,
                'qty' => $qty,
                'duration_days' => (int)$product['duration_days'],
                'subtotal' => $item_subtotal,
                'created_at' => current_time('mysql'),
            ];
        }

        // Insert Transaction Header
        $wpdb->insert($t_transactions, [
            'id' => $transaction_id,
            'transaction_no' => $transaction_no,
            'customer_id' => $customer_id,
            'customer_name' => $name,
            'seller_id' => null,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
            'payment_method' => $payment_method,
            'payment_status' => 'pending',
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'updated_by' => 0,
        ]);

        // Insert Transaction Items & sync active product tracking
        foreach ($item_entries as $entry) {
            $wpdb->insert($t_pos_items, $entry);

            // Automated active product registration (if paid instantly or operator updates later)
            // But since this is a new public order, we register it as 'pending_payment' active product
            if ($entry['duration_days'] > 0 && $customer_id) {
                $cust_contact = 'WA: ' . $whatsapp;
                $active_id = wp_generate_uuid4();
                $start_date = wp_date('Y-m-d');
                $expires_at = wp_date('Y-m-d', strtotime($start_date . " +{$entry['duration_days']} days"));

                $wpdb->insert(OKJ_DB::get_table('active_products'), [
                    'id' => $active_id,
                    'reseller_product_id' => '',
                    'product_label' => $entry['product_name'],
                    'customer_id' => $customer_id,
                    'customer_name' => $name,
                    'customer_contact' => $cust_contact,
                    'start_date' => $start_date,
                    'duration_days' => $entry['duration_days'],
                    'expires_at' => $expires_at,
                    'status' => 'expired', // Set expired/pending until marked paid
                    'price' => $entry['price'] * $entry['qty'],
                    'payment_status' => 'unpaid',
                    'notes' => 'Pemesanan Mandiri QR POS (' . $transaction_no . ')',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'updated_by' => 0,
                ]);
            }
        }

        OKJ_Reseller_Manager::log('pos_public_order', 'pos_transaction', $transaction_id, "New self-service order received: " . $transaction_no . " from customer: " . $name);

        // Notify client via WhatsApp instantly if provided
        if (!empty($whatsapp)) {
            $notifier = new OKJ_Notifier();
            $settings = get_option('okj_settings_v1', []);
            $company_name = !empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name');
            $track_url = home_url('/?okj_order=1&track_order=' . $transaction_id);

            $msg = "*PESANAN DITERIMA - {$company_name}*\n";
            $msg .= "------------------------------------------\n";
            $msg .= "No. Transaksi: `{$transaction_no}`\n";
            $msg .= "Nama Pelanggan: {$name}\n";
            $msg .= "Total Bayar: Rp " . number_format($subtotal, 0, ',', '.') . "\n";
            $msg .= "Metode Bayar: " . strtoupper($payment_method) . "\n";
            $msg .= "Status: *MENUNGGU KONFIRMASI*\n";
            $msg .= "------------------------------------------\n";
            $msg .= "Pantau status pesanan secara real-time di sini:\n{$track_url}\n";
            $msg .= "------------------------------------------\n";
            $msg .= "Terima kasih atas pemesanan Anda! 🙏";

            $notifier->send_waha($whatsapp, $msg);
        }

        wp_send_json_success([
            'transaction_id' => $transaction_id,
            'transaction_no' => $transaction_no,
            'customer_name' => $name,
            'total' => $subtotal,
            'created_at' => wp_date('Y-m-d H:i:s'),
        ]);
    }

    public function ajax_public_check_order_status() {
        global $wpdb;
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';

        if (empty($transaction_id)) {
            wp_send_json_error(['message' => 'Parameter tidak lengkap.']);
        }

        $t_transactions = OKJ_DB::get_table('pos_transactions');
        $tx = $wpdb->get_row($wpdb->prepare("SELECT payment_status FROM {$t_transactions} WHERE id = %s", $transaction_id), ARRAY_A);

        if (!$tx) {
            wp_send_json_error(['message' => 'Transaksi tidak ditemukan.']);
        }

        wp_send_json_success([
            'status' => $tx['payment_status']
        ]);
    }
}

