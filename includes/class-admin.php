<?php
if (!defined('ABSPATH')) { exit; }

class WRPM_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Form post hooks
        add_action('admin_post_wrpm_save_price', [$this, 'save_product_price']);
        add_action('admin_post_wrpm_delete_price', [$this, 'delete_product_price']);
        add_action('admin_post_wrpm_save_seller', [$this, 'save_seller']);
        add_action('admin_post_wrpm_delete_seller', [$this, 'delete_seller']);
        add_action('admin_post_wrpm_save_customer', [$this, 'save_customer']);
        add_action('admin_post_wrpm_delete_customer', [$this, 'delete_customer']);
        add_action('admin_post_wrpm_save_reseller_product', [$this, 'save_reseller_product']);
        add_action('admin_post_wrpm_delete_reseller_product', [$this, 'delete_reseller_product']);
        add_action('admin_post_wrpm_save_active_product', [$this, 'save_active_product']);
        add_action('admin_post_wrpm_delete_active_product', [$this, 'delete_active_product']);
        add_action('admin_post_wrpm_save_settings', [$this, 'save_settings']);
        add_action('admin_post_wrpm_backup_data', [$this, 'backup_data']);
        add_action('admin_post_wrpm_restore_data', [$this, 'restore_data']);
        add_action('admin_post_wrpm_invoice_pdf', [$this, 'download_invoice_pdf']);
        add_action('admin_post_wrpm_save_shortlink', [$this, 'save_shortlink']);
        add_action('admin_post_wrpm_delete_shortlink', [$this, 'delete_shortlink']);

        // Manual reminder triggers
        add_action('admin_post_wrpm_send_reminder_manual', [$this, 'send_reminder_manual']);

        // AJAX hooks for quick add and connection testing
        add_action('wp_ajax_wrpm_quick_add_seller', [$this, 'quick_add_seller']);
        add_action('wp_ajax_wrpm_quick_add_customer', [$this, 'quick_add_customer']);
        add_action('wp_ajax_wrpm_test_waha', [$this, 'ajax_test_waha']);
        add_action('wp_ajax_wrpm_test_telegram', [$this, 'ajax_test_telegram']);
        add_action('wp_ajax_wrpm_test_smtp', [$this, 'ajax_test_smtp']);
    }

    public function register_menus() {
        $cap = 'wrpm_manage';

        add_menu_page(
            'WP Reseller Manage',
            'WP Reseller Manage',
            $cap,
            'wrpm-dashboard',
            [$this, 'view_dashboard'],
            'dashicons-blank',
            58
        );

        add_submenu_page('wrpm-dashboard', 'Dashboard', 'Dashboard', $cap, 'wrpm-dashboard', [$this, 'view_dashboard']);
        add_submenu_page('wrpm-dashboard', 'Daftar Harga Produk', 'Daftar Harga Produk', $cap, 'wrpm-product-prices', [$this, 'view_product_prices']);
        add_submenu_page('wrpm-dashboard', 'Pembelian Produk', 'Pembelian Produk', $cap, 'wrpm-reseller-products', [$this, 'view_reseller_products']);
        add_submenu_page('wrpm-dashboard', 'Customer', 'Customer', $cap, 'wrpm-customers', [$this, 'view_customers']);
        add_submenu_page('wrpm-dashboard', 'Seller', 'Seller', $cap, 'wrpm-sellers', [$this, 'view_sellers']);
        add_submenu_page('wrpm-dashboard', 'Produk Aktif', 'Produk Aktif', $cap, 'wrpm-active-products', [$this, 'view_active_products']);
        add_submenu_page('wrpm-dashboard', 'Reminder', 'Reminder', $cap, 'wrpm-reminders', [$this, 'view_reminders']);
        add_submenu_page('wrpm-dashboard', 'Shortlink Affiliate', 'Shortlink Affiliate', $cap, 'wrpm-shortlinks', [$this, 'view_shortlinks']);
        add_submenu_page('wrpm-dashboard', 'Laporan', 'Laporan', 'wrpm_view_reports', 'wrpm-reports', [$this, 'view_reports']);
        add_submenu_page('wrpm-dashboard', 'Logs', 'Logs', 'wrpm_view_logs', 'wrpm-logs', [$this, 'view_logs']);
        add_submenu_page('wrpm-dashboard', 'Settings', 'Settings', 'wrpm_manage_settings', 'wrpm-settings', [$this, 'view_settings']);
    }

    public function enqueue_assets($hook) {
        if (empty($_GET['page']) || strpos($_GET['page'], 'wrpm-') !== 0) return;

        // Modern Visuals & Chart libraries
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        // Chart.js for interactive analytics
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true);

        // Load visual styling assets
        $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/admin.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/admin.css') : time();
        $js_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/js/admin.js') ? filemtime(dirname(dirname(__FILE__)) . '/assets/js/admin.js') : time();
        wp_enqueue_style('wrpm-admin-css', plugins_url('assets/css/admin.css', dirname(__FILE__)), [], $css_ver);
        wp_enqueue_script('wrpm-admin-js', plugins_url('assets/js/admin.js', dirname(__FILE__)), ['jquery', 'select2', 'chartjs'], $js_ver, true);
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
        $total_reseller = (int)$wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('reseller_products'));
        $total_active = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . WRPM_DB::get_table('active_products') . " WHERE status = %s", 'active'));
        $total_expired = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . WRPM_DB::get_table('active_products') . " WHERE status = %s", 'expired'));
        $total_income = (float)$wpdb->get_var("SELECT COALESCE(SUM(price),0) FROM " . WRPM_DB::get_table('active_products'));

        $today = wp_date('Y-m-d');
        $in7 = wp_date('Y-m-d', strtotime('+7 days'));

        $soon = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . WRPM_DB::get_table('active_products') . " WHERE status = 'active' AND expires_at >= %s AND expires_at <= %s ORDER BY expires_at ASC LIMIT 10",
            $today, $in7
        ), ARRAY_A);

        // revenue analytical data
        $revenue_monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m_val = date('Y-m', strtotime("-$i months"));
            $m_label = date('F Y', strtotime("-$i months"));
            $revenue = (float)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(price),0) FROM " . WRPM_DB::get_table('active_products') . " WHERE start_date LIKE %s",
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
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('product_prices') . " WHERE id = %s", $id), ARRAY_A) : null;
            $sellers = $wpdb->get_results("SELECT id, name FROM " . WRPM_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);

            // Fetch all unique tags from stored products
            $all_tags_raw = $wpdb->get_col("SELECT DISTINCT tags FROM " . WRPM_DB::get_table('product_prices') . " WHERE tags IS NOT NULL AND tags != ''");
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
            $existing_categories = $wpdb->get_col("SELECT DISTINCT category FROM " . WRPM_DB::get_table('product_prices') . " WHERE category IS NOT NULL AND category != ''");
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

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('product_prices'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT p.*, s.name as seller_name, s.email as seller_email, s.phone as seller_phone, s.telegram as seller_telegram, s.whatsapp as seller_whatsapp FROM " . WRPM_DB::get_table('product_prices') . " p LEFT JOIN " . WRPM_DB::get_table('sellers') . " s ON p.seller_id = s.id ORDER BY p.name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
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
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('reseller_products') . " WHERE id = %s", $id), ARRAY_A) : null;
            $prices = $wpdb->get_results("SELECT p.id, p.name, p.duration_days, p.sale_price, p.seller_id, s.name as seller_name FROM " . WRPM_DB::get_table('product_prices') . " p LEFT JOIN " . WRPM_DB::get_table('sellers') . " s ON p.seller_id = s.id ORDER BY p.name ASC", ARRAY_A);
            $sellers = $wpdb->get_results("SELECT id, name FROM " . WRPM_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);
            $this->render_template('reseller-products', ['action' => $action, 'row' => $row, 'prices' => $prices, 'sellers' => $sellers]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('reseller_products'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT r.*, s.name as seller_name FROM " . WRPM_DB::get_table('reseller_products') . " r LEFT JOIN " . WRPM_DB::get_table('sellers') . " s ON r.seller_id = s.id ORDER BY r.product_name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
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
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('customers') . " WHERE id = %s", $id), ARRAY_A) : null;
            $this->render_template('customers', ['action' => $action, 'row' => $row]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('customers'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('customers') . " ORDER BY name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
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
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('sellers') . " WHERE id = %s", $id), ARRAY_A) : null;
            $this->render_template('sellers', ['action' => $action, 'row' => $row]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('sellers'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('sellers') . " ORDER BY name ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
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
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A) : null;
            $customers = $wpdb->get_results("SELECT id, name FROM " . WRPM_DB::get_table('customers') . " ORDER BY name ASC", ARRAY_A);
            $resellers = $wpdb->get_results("SELECT r.id, r.product_name, r.duration_days, r.price, r.purchase_date, s.name as seller_name, (SELECT COUNT(*) FROM " . WRPM_DB::get_table('active_products') . " WHERE reseller_product_id = r.id) as is_used FROM " . WRPM_DB::get_table('reseller_products') . " r LEFT JOIN " . WRPM_DB::get_table('sellers') . " s ON r.seller_id = s.id ORDER BY r.product_name ASC", ARRAY_A);
            $this->render_template('active-products', ['action' => $action, 'row' => $row, 'customers' => $customers, 'resellers' => $resellers]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('active_products'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT a.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.telegram as customer_telegram, c.whatsapp as customer_whatsapp FROM " . WRPM_DB::get_table('active_products') . " a LEFT JOIN " . WRPM_DB::get_table('customers') . " c ON a.customer_id = c.id ORDER BY a.expires_at ASC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
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
        $rows = $wpdb->get_results("SELECT r.*, a.product_label, c.name as customer_name FROM " . WRPM_DB::get_table('active_reminders') . " r INNER JOIN " . WRPM_DB::get_table('active_products') . " a ON r.active_product_id = a.id INNER JOIN " . WRPM_DB::get_table('customers') . " c ON r.customer_id = c.id ORDER BY r.reminder_date ASC", ARRAY_A);
        $this->render_template('reminders', ['rows' => $rows]);
    }

    public function view_shortlinks() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

        $row = null;
        if (($action === 'edit' || $action === 'add') && !empty($id)) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('shortlinks') . " WHERE id = %s", $id), ARRAY_A);
        }

        if ($action === 'add' || $action === 'edit') {
            $this->render_template('shortlinks', ['action' => $action, 'row' => $row]);
        } else {
            $per_page = 15;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($paged - 1) * $per_page;

            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM " . WRPM_DB::get_table('shortlinks'));
            $total_pages = ceil($total_rows / $per_page);

            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('shortlinks') . " ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
            
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
        $sales = $wpdb->get_results("SELECT start_date, price FROM " . WRPM_DB::get_table('active_products') . " WHERE payment_status = 'paid'", ARRAY_A);
        $this->render_template('reports', ['sales' => $sales]);
    }

    public function view_logs() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . WRPM_DB::get_table('logs') . " ORDER BY happened_at DESC LIMIT 200", ARRAY_A);
        $this->render_template('logs', ['rows' => $rows]);
    }

    public function view_settings() {
        $settings = get_option('wrpm_settings_v1', []);
        $this->render_template('settings', ['settings' => $settings]);
    }

    // POST action processors
    public function save_product_price() {
        check_admin_referer('wrpm_save_price');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

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
            'description' => wp_kses_post($_POST['description']),
            'notes' => wp_kses_post($_POST['notes']),
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        ];

        if ($is_edit) {
            $wpdb->update(WRPM_DB::get_table('product_prices'), $data, ['id' => $_POST['id']]);
            WRPM_Reseller_Manager::log('update', 'product_price', $id, "Updated product price: " . $data['name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(WRPM_DB::get_table('product_prices'), $data);
            WRPM_Reseller_Manager::log('create', 'product_price', $id, "Created product price: " . $data['name']);
        }

        if (!empty($_POST['affiliate_url']) && !empty($_POST['auto_create_shortlink'])) {
            $short_key = sanitize_title($data['name']);
            if (empty($short_key)) {
                $short_key = substr(md5($id), 0, 8);
            }
            
            $t_shortlinks = WRPM_DB::get_table('shortlinks');
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

        wp_safe_redirect(admin_url('admin.php?page=wrpm-product-prices'));
        exit;
    }

    public function delete_product_price() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_delete_price_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(WRPM_DB::get_table('product_prices'), ['id' => $id]);
        WRPM_Reseller_Manager::log('delete', 'product_price', $id, "Deleted product price ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=wrpm-product-prices'));
        exit;
    }

    public function save_seller() {
        check_admin_referer('wrpm_save_seller');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

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
            $wpdb->update(WRPM_DB::get_table('sellers'), $data, ['id' => $_POST['id']]);
            WRPM_Reseller_Manager::log('update', 'seller', $id, "Updated seller: " . $data['name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(WRPM_DB::get_table('sellers'), $data);
            WRPM_Reseller_Manager::log('create', 'seller', $id, "Created seller: " . $data['name']);
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-sellers'));
        exit;
    }

    public function delete_seller() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_delete_seller_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(WRPM_DB::get_table('sellers'), ['id' => $id]);
        WRPM_Reseller_Manager::log('delete', 'seller', $id, "Deleted seller ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=wrpm-sellers'));
        exit;
    }

    public function save_customer() {
        check_admin_referer('wrpm_save_customer');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

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
            $wpdb->update(WRPM_DB::get_table('customers'), $data, ['id' => $_POST['id']]);
            WRPM_Reseller_Manager::log('update', 'customer', $id, "Updated customer: " . $data['name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(WRPM_DB::get_table('customers'), $data);
            WRPM_Reseller_Manager::log('create', 'customer', $id, "Created customer: " . $data['name']);
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-customers'));
        exit;
    }

    public function delete_customer() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_delete_customer_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(WRPM_DB::get_table('customers'), ['id' => $id]);
        WRPM_Reseller_Manager::log('delete', 'customer', $id, "Deleted customer ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=wrpm-customers'));
        exit;
    }

    public function save_shortlink() {
        check_admin_referer('wrpm_save_shortlink');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

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

        $table = WRPM_DB::get_table('shortlinks');

        $conflict = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE short_key = %s AND id != %s", $short_key, $id));
        if ($conflict) {
            wp_die('Key Shortlink ini sudah digunakan oleh shortlink lain! Silakan gunakan key lain.');
        }

        if ($is_edit) {
            $wpdb->update($table, $data, ['id' => $_POST['id']]);
            WRPM_Reseller_Manager::log('update', 'shortlink', $id, "Updated shortlink: " . $data['title']);
        } else {
            $data['clicks'] = 0;
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            WRPM_Reseller_Manager::log('create', 'shortlink', $id, "Created shortlink: " . $data['title']);
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-shortlinks'));
        exit;
    }

    public function delete_shortlink() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_delete_shortlink_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(WRPM_DB::get_table('shortlinks'), ['id' => $id]);
        WRPM_Reseller_Manager::log('delete', 'shortlink', $id, "Deleted shortlink ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=wrpm-shortlinks'));
        exit;
    }

    public function save_reseller_product() {
        check_admin_referer('wrpm_save_reseller_product');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $price_id = sanitize_text_field($_POST['price_id']);
        $price_row = $wpdb->get_row($wpdb->prepare("SELECT name, duration_days FROM " . WRPM_DB::get_table('product_prices') . " WHERE id = %s", $price_id), ARRAY_A);

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
            $old_row = $wpdb->get_row($wpdb->prepare("SELECT payment_attachments FROM " . WRPM_DB::get_table('reseller_products') . " WHERE id = %s", $id), ARRAY_A);
            if ($old_row) {
                $data['payment_attachments'] = $old_row['payment_attachments'];
            }
        }

        if ($is_edit) {
            $wpdb->update(WRPM_DB::get_table('reseller_products'), $data, ['id' => $_POST['id']]);
            WRPM_Reseller_Manager::log('update', 'reseller_product', $id, "Updated reseller product: " . $data['product_name']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(WRPM_DB::get_table('reseller_products'), $data);
            WRPM_Reseller_Manager::log('create', 'reseller_product', $id, "Created reseller product: " . $data['product_name']);
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-reseller-products'));
        exit;
    }

    public function delete_reseller_product() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_delete_reseller_product_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(WRPM_DB::get_table('reseller_products'), ['id' => $id]);
        WRPM_Reseller_Manager::log('delete', 'reseller_product', $id, "Deleted reseller product ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=wrpm-reseller-products'));
        exit;
    }

    public function save_active_product() {
        check_admin_referer('wrpm_save_active_product');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $reseller_product_id = sanitize_text_field($_POST['reseller_product_id']);
        $customer_id = sanitize_text_field($_POST['customer_id']);

        $rp = $wpdb->get_row($wpdb->prepare("SELECT product_name, duration_days FROM " . WRPM_DB::get_table('reseller_products') . " WHERE id = %s", $reseller_product_id), ARRAY_A);
        $cust = $wpdb->get_row($wpdb->prepare("SELECT name, phone, telegram, whatsapp, email FROM " . WRPM_DB::get_table('customers') . " WHERE id = %s", $customer_id), ARRAY_A);

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
            $old_row = $wpdb->get_row($wpdb->prepare("SELECT payment_attachments FROM " . WRPM_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A);
            if ($old_row) {
                $data['payment_attachments'] = $old_row['payment_attachments'];
            }
        }

        if ($is_edit) {
            $wpdb->update(WRPM_DB::get_table('active_products'), $data, ['id' => $_POST['id']]);
            WRPM_Reseller_Manager::log('update', 'active_product', $id, "Updated active product: " . $data['product_label']);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(WRPM_DB::get_table('active_products'), $data);
            WRPM_Reseller_Manager::log('create', 'active_product', $id, "Created active product: " . $data['product_label']);
        }

        // Sync reminders
        $saved_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A);
        if ($saved_row) {
            WRPM_Reseller_Manager::sync_reminders($saved_row);
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-active-products'));
        exit;
    }

    public function delete_active_product() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_delete_active_product_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $wpdb->delete(WRPM_DB::get_table('active_products'), ['id' => $id]);
        $wpdb->delete(WRPM_DB::get_table('active_reminders'), ['active_product_id' => $id]);
        WRPM_Reseller_Manager::log('delete', 'active_product', $id, "Deleted active product & reminders ID: " . $id);

        wp_safe_redirect(admin_url('admin.php?page=wrpm-active-products'));
        exit;
    }

    public function save_settings() {
        check_admin_referer('wrpm_save_settings');
        if (!current_user_can('wrpm_manage_settings')) wp_die('Forbidden');

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
        ];

        update_option('wrpm_settings_v1', $data);
        WRPM_Reseller_Manager::log('save_settings', 'settings', '', 'Updated plugin settings configuration');

        wp_safe_redirect(admin_url('admin.php?page=wrpm-settings'));
        exit;
    }

    public function backup_data() {
        check_admin_referer('wrpm_backup_data');
        WRPM_Backup::export_json();
    }

    public function restore_data() {
        check_admin_referer('wrpm_restore_data');
        if (!current_user_can('wrpm_manage_settings')) wp_die('Forbidden');

        if (!empty($_FILES['restore_file']['tmp_name'])) {
            $res = WRPM_Backup::import_json($_FILES['restore_file']['tmp_name']);
            $msg = $res['ok'] ? 'Database & settings successfully restored' : 'Restore failed: ' . $res['error'];
        } else {
            $msg = 'Please upload a backup JSON file first';
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-settings&msg=' . urlencode($msg)));
        exit;
    }

    public function download_invoice_pdf() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_invoice_pdf_' . $id);

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A);
        if (!$row) wp_die('Active product not found');

        $settings = get_option('wrpm_settings_v1', []);
        $pdf_gen = new WRPM_PDF_Invoice();
        $pdf_data = $pdf_gen->generate_invoice($row, $settings);

        WRPM_Reseller_Manager::log('download_invoice', 'active_product', $id, "Downloaded invoice PDF: " . $row['product_label']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice-' . $id . '.pdf"');
        header('Content-Length: ' . strlen($pdf_data));
        echo $pdf_data;
        exit;
    }

    public function send_reminder_manual() {
        $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        check_admin_referer('wrpm_send_reminder_' . $id);
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $r = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, a.product_label, a.start_date, a.duration_days, a.notes, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.telegram as customer_telegram, c.whatsapp as customer_whatsapp, a.expires_at, a.price
             FROM " . WRPM_DB::get_table('active_reminders') . " r
             INNER JOIN " . WRPM_DB::get_table('active_products') . " a ON r.active_product_id = a.id
             INNER JOIN " . WRPM_DB::get_table('customers') . " c ON r.customer_id = c.id
             WHERE r.id = %s",
            $id
        ), ARRAY_A);

        if (!$r) wp_die('Reminder record not found');

        $notifier = new WRPM_Notifier();
        $settings = get_option('wrpm_settings_v1', []);

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
            'invoice_url' => admin_url('admin-post.php?action=wrpm_invoice_pdf&id=' . $r['active_product_id']),
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
            $wpdb->update(WRPM_DB::get_table('active_reminders'), [
                'status' => 'sent',
                'sent_via' => implode(',', $sent_channels),
                'sent_at' => $now,
                'last_error' => !empty($error_log) ? implode('; ', $error_log) : null,
                'updated_at' => $now,
            ], ['id' => $r['id']]);

            WRPM_Reseller_Manager::log('send_reminder', 'reminder', $r['id'], "Manual reminder triggered successfully via " . implode(',', $sent_channels));
        } else {
            $wpdb->update(WRPM_DB::get_table('active_reminders'), [
                'last_error' => implode('; ', $error_log),
                'updated_at' => $now,
            ], ['id' => $r['id']]);

            WRPM_Reseller_Manager::log('send_reminder_fail', 'reminder', $r['id'], "Manual reminder failed: " . implode('; ', $error_log));
        }

        wp_safe_redirect(admin_url('admin.php?page=wrpm-reminders'));
        exit;
    }

    public function quick_add_seller() {
        if (!current_user_can('wrpm_manage')) {
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

        $inserted = $wpdb->insert(WRPM_DB::get_table('sellers'), $data);
        if ($inserted) {
            WRPM_Reseller_Manager::log('create', 'seller', $id, "Quick created seller: " . $name);
            wp_send_json_success([
                'id' => $id,
                'name' => $name
            ]);
        } else {
            wp_send_json_error(['message' => 'Gagal menyimpan data Seller ke database.']);
        }
    }

    public function quick_add_customer() {
        if (!current_user_can('wrpm_manage')) {
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

        $inserted = $wpdb->insert(WRPM_DB::get_table('customers'), $data);
        if ($inserted) {
            WRPM_Reseller_Manager::log('create', 'customer', $id, "Quick created customer: " . $name);
            wp_send_json_success([
                'id' => $id,
                'name' => $name
            ]);
        } else {
            wp_send_json_error(['message' => 'Gagal menyimpan data Customer ke database.']);
        }
     }

    public function ajax_test_waha() {
        if (!current_user_can('wrpm_manage')) {
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
        }

        $message = "Halo! Ini adalah pesan uji coba dari WP Reseller Manage Anda. Koneksi berhasil! 🚀";

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
        if (!current_user_can('wrpm_manage')) {
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
        $message = "Halo! Ini adalah pesan uji coba dari WP Reseller Manage Anda. Koneksi berhasil! 🚀";

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
        if (!current_user_can('wrpm_manage')) {
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
        
        $subject = 'WP Reseller Manage - Test SMTP Connection';
        $body = "Halo!\n\nIni adalah email uji coba untuk memverifikasi pengaturan SMTP Anda pada plugin WP Reseller Manage.\n\nKoneksi SMTP Anda berhasil terintegrasi dengan sempurna! 🚀";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $ok = wp_mail($from_email, $subject, $body, $headers);
        
        remove_action('phpmailer_init', $temp_hook, 999);

        if ($ok) {
            wp_send_json_success(['message' => 'Email uji coba berhasil terkirim ke ' . $from_email . '! Koneksi SMTP sukses.']);
        } else {
            wp_send_json_error(['message' => 'Gagal mengirim email uji coba. Silakan periksa kembali konfigurasi detail SMTP Anda atau log server.']);
        }
    }
}
