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

        // Manual reminder triggers
        add_action('admin_post_wrpm_send_reminder_manual', [$this, 'send_reminder_manual']);
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
        add_submenu_page('wrpm-dashboard', 'Harga Produk', 'Harga Produk', $cap, 'wrpm-product-prices', [$this, 'view_product_prices']);
        add_submenu_page('wrpm-dashboard', 'Produk Reseller', 'Produk Reseller', $cap, 'wrpm-reseller-products', [$this, 'view_reseller_products']);
        add_submenu_page('wrpm-dashboard', 'Customer', 'Customer', $cap, 'wrpm-customers', [$this, 'view_customers']);
        add_submenu_page('wrpm-dashboard', 'Seller', 'Seller', $cap, 'wrpm-sellers', [$this, 'view_sellers']);
        add_submenu_page('wrpm-dashboard', 'Produk Aktif', 'Produk Aktif', $cap, 'wrpm-active-products', [$this, 'view_active_products']);
        add_submenu_page('wrpm-dashboard', 'Reminder', 'Reminder', $cap, 'wrpm-reminders', [$this, 'view_reminders']);
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
        wp_enqueue_style('wrpm-admin-css', plugins_url('assets/css/admin.css', dirname(__FILE__)), [], WRPM_App::VERSION);
        wp_enqueue_script('wrpm-admin-js', plugins_url('assets/js/admin.js', dirname(__FILE__)), ['jquery', 'select2', 'chartjs'], WRPM_App::VERSION, true);
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
            $this->render_template('product-prices', ['action' => $action, 'row' => $row, 'sellers' => $sellers]);
        } else {
            $rows = $wpdb->get_results("SELECT p.*, s.name as seller_name FROM " . WRPM_DB::get_table('product_prices') . " p LEFT JOIN " . WRPM_DB::get_table('sellers') . " s ON p.seller_id = s.id ORDER BY p.name ASC", ARRAY_A);
            $this->render_template('product-prices', ['action' => 'list', 'rows' => $rows]);
        }
    }

    public function view_reseller_products() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('reseller_products') . " WHERE id = %s", $id), ARRAY_A) : null;
            $prices = $wpdb->get_results("SELECT id, name, duration_days, sale_price FROM " . WRPM_DB::get_table('product_prices') . " ORDER BY name ASC", ARRAY_A);
            $sellers = $wpdb->get_results("SELECT id, name FROM " . WRPM_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);
            $this->render_template('reseller-products', ['action' => $action, 'row' => $row, 'prices' => $prices, 'sellers' => $sellers]);
        } else {
            $rows = $wpdb->get_results("SELECT r.*, s.name as seller_name FROM " . WRPM_DB::get_table('reseller_products') . " r LEFT JOIN " . WRPM_DB::get_table('sellers') . " s ON r.seller_id = s.id ORDER BY r.product_name ASC", ARRAY_A);
            $this->render_template('reseller-products', ['action' => 'list', 'rows' => $rows]);
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
            $rows = $wpdb->get_results("SELECT * FROM " . WRPM_DB::get_table('customers') . " ORDER BY name ASC", ARRAY_A);
            $this->render_template('customers', ['action' => 'list', 'rows' => $rows]);
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
            $rows = $wpdb->get_results("SELECT * FROM " . WRPM_DB::get_table('sellers') . " ORDER BY name ASC", ARRAY_A);
            $this->render_template('sellers', ['action' => 'list', 'rows' => $rows]);
        }
    }

    public function view_active_products() {
        global $wpdb;
        $action = !empty($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'add' || $action === 'edit') {
            $id = !empty($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WRPM_DB::get_table('active_products') . " WHERE id = %s", $id), ARRAY_A) : null;
            $customers = $wpdb->get_results("SELECT id, name FROM " . WRPM_DB::get_table('customers') . " ORDER BY name ASC", ARRAY_A);
            $resellers = $wpdb->get_results("SELECT id, product_name, duration_days, price FROM " . WRPM_DB::get_table('reseller_products') . " ORDER BY product_name ASC", ARRAY_A);
            $this->render_template('active-products', ['action' => $action, 'row' => $row, 'customers' => $customers, 'resellers' => $resellers]);
        } else {
            $rows = $wpdb->get_results("SELECT * FROM " . WRPM_DB::get_table('active_products') . " ORDER BY expires_at ASC", ARRAY_A);
            $this->render_template('active-products', ['action' => 'list', 'rows' => $rows]);
        }
    }

    public function view_reminders() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT r.*, a.product_label, c.name as customer_name FROM " . WRPM_DB::get_table('active_reminders') . " r INNER JOIN " . WRPM_DB::get_table('active_products') . " a ON r.active_product_id = a.id INNER JOIN " . WRPM_DB::get_table('customers') . " c ON r.customer_id = c.id ORDER BY r.reminder_date ASC", ARRAY_A);
        $this->render_template('reminders', ['rows' => $rows]);
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
            'tags' => sanitize_text_field($_POST['tags']),
            'seller_id' => !empty($_POST['seller_id']) ? sanitize_text_field($_POST['seller_id']) : null,
            'reseller_price' => (float)$_POST['reseller_price'],
            'sale_price' => (float)$_POST['sale_price'],
            'duration_days' => (int)$_POST['duration_days'],
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

    public function save_reseller_product() {
        check_admin_referer('wrpm_save_reseller_product');
        if (!current_user_can('wrpm_manage')) wp_die('Forbidden');

        global $wpdb;
        $id = !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : wp_generate_uuid4();
        $is_edit = !empty($_POST['id']);

        $price_id = sanitize_text_field($_POST['price_id']);
        $price_row = $wpdb->get_row($wpdb->prepare("SELECT name, duration_days FROM " . WRPM_DB::get_table('product_prices') . " WHERE id = %s", $price_id), ARRAY_A);

        $product_name = $price_row ? $price_row['name'] : '';
        $duration_days = $price_row ? (int)$price_row['duration_days'] : 0;

        $data = [
            'id' => $id,
            'price_id' => $price_id,
            'product_name' => $product_name,
            'category' => sanitize_text_field($_POST['category']),
            'tags' => sanitize_text_field($_POST['tags']),
            'seller_id' => !empty($_POST['seller_id']) ? sanitize_text_field($_POST['seller_id']) : null,
            'reseller_name' => sanitize_text_field($_POST['reseller_name']),
            'reseller_contact' => sanitize_text_field($_POST['reseller_contact']),
            'purchase_date' => sanitize_text_field($_POST['purchase_date']),
            'duration_days' => $duration_days,
            'description' => wp_kses_post($_POST['description']),
            'price' => (float)$_POST['price'],
            'expires_at' => sanitize_text_field($_POST['expires_at']),
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
            "SELECT r.*, a.product_label, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.telegram as customer_telegram, c.whatsapp as customer_whatsapp, a.expires_at, a.price
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
            'product_label' => $r['product_label'],
            'expires_at' => $r['expires_at'],
            'price' => 'Rp ' . number_format_i18n((float)$r['price'], 0),
            'remaining_days' => $r['offset_days'],
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
}
