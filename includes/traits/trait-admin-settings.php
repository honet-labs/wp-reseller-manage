<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Settings {
    public function page_settings() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $s = $this->wrpm_get_settings();
        $this->render_template('admin/settings.php', ['s' => $s]);
    }

    public function handle_admin_post_save_settings() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_save_settings');

        $offsets_raw = sanitize_text_field(wp_unslash($_POST['reminder_offsets'] ?? '7,3,1'));
        $offsets = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;]+/', $offsets_raw)), function($d){ return $d > 0 && $d < 3660; })));
        if (empty($offsets)) $offsets = [7,3,1];

        $s = [
            'reminder_offsets' => $offsets,
            'cron_time' => sanitize_text_field(wp_unslash($_POST['cron_time'] ?? '08:00')),
            'email_subject' => sanitize_text_field(wp_unslash($_POST['email_subject'] ?? '')),
            'email_template' => (string)wp_unslash($_POST['email_template'] ?? ''),
            'telegram_template' => (string)wp_unslash($_POST['telegram_template'] ?? ''),
            'whatsapp_template' => (string)wp_unslash($_POST['whatsapp_template'] ?? ''),
            'whatsapp_template_h7' => (string)wp_unslash($_POST['whatsapp_template_h7'] ?? ''),
            'whatsapp_template_h3' => (string)wp_unslash($_POST['whatsapp_template_h3'] ?? ''),
            'whatsapp_template_h1' => (string)wp_unslash($_POST['whatsapp_template_h1'] ?? ''),

            'smtp_enabled' => !empty($_POST['smtp_enabled']) ? 1 : 0,
            'smtp_host' => sanitize_text_field(wp_unslash($_POST['smtp_host'] ?? '')),
            'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
            'smtp_user' => sanitize_text_field(wp_unslash($_POST['smtp_user'] ?? '')),
            'smtp_pass' => sanitize_text_field(wp_unslash($_POST['smtp_pass'] ?? '')),
            'smtp_secure' => in_array((string)($_POST['smtp_secure'] ?? 'tls'), ['tls','ssl',''], true) ? (string)($_POST['smtp_secure'] ?? 'tls') : 'tls',
            'smtp_from_email' => sanitize_email(wp_unslash($_POST['smtp_from_email'] ?? '')),
            'smtp_from_name' => sanitize_text_field(wp_unslash($_POST['smtp_from_name'] ?? '')),

            'telegram_enabled' => !empty($_POST['telegram_enabled']) ? 1 : 0,
            'telegram_bot_token' => sanitize_text_field(wp_unslash($_POST['telegram_bot_token'] ?? '')),
            'telegram_default_chat_id' => sanitize_text_field(wp_unslash($_POST['telegram_default_chat_id'] ?? '')),

            'waha_enabled' => !empty($_POST['waha_enabled']) ? 1 : 0,
            'waha_api_url' => esc_url_raw(wp_unslash($_POST['waha_api_url'] ?? '')),
            'waha_api_token' => sanitize_text_field(wp_unslash($_POST['waha_api_token'] ?? '')),
            'waha_session_name' => sanitize_text_field(wp_unslash($_POST['waha_session_name'] ?? 'default')),

            'pdf_invoice_title' => sanitize_text_field(wp_unslash($_POST['pdf_invoice_title'] ?? 'Invoice')),
            'pdf_primary_color' => sanitize_hex_color(wp_unslash($_POST['pdf_primary_color'] ?? '#1e293b')),
            'pdf_company_name' => sanitize_text_field(wp_unslash($_POST['pdf_company_name'] ?? '')),
            'pdf_company_address' => sanitize_text_field(wp_unslash($_POST['pdf_company_address'] ?? '')),
            'pdf_company_phone' => sanitize_text_field(wp_unslash($_POST['pdf_company_phone'] ?? '')),
            'pdf_payment_details' => sanitize_textarea_field(wp_unslash($_POST['pdf_payment_details'] ?? '')),

            'github_repo' => sanitize_text_field(wp_unslash($_POST['github_repo'] ?? '')),
            'github_token' => sanitize_text_field(wp_unslash($_POST['github_token'] ?? '')),

            'wc_sync_enabled' => !empty($_POST['wc_sync_enabled']) ? 1 : 0,
        ];

        update_option(self::OPT_SETTINGS, $s, false);
        // Re-schedule daily cron if cron_time changed.
        $this->clear_cron();
        $this->ensure_cron();
        $this->wrpm_log('update', 'settings', '', 'Update settings');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', ['wrpm_msg' => 'Settings tersimpan', 'wrpm_type' => 'success']));
        exit;
    }

    public function handle_admin_post_test_email() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_test_email');

        $to = sanitize_email(wp_unslash($_POST['test_email_to'] ?? ''));
        $s = $this->wrpm_get_settings();
        $r = $this->wrpm_send_email($to, '[TEST] WP Reseller Manage', "Ini email test dari WP Reseller Manage.", []);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
            'wrpm_msg' => !empty($r['ok']) ? 'Test email terkirim' : ('Gagal kirim test email: ' . (string)($r['error'] ?? 'unknown')),
            'wrpm_type' => !empty($r['ok']) ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_test_telegram() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_test_telegram');

        $chat_id = sanitize_text_field(wp_unslash($_POST['test_telegram_chat_id'] ?? ''));
        $r = $this->wrpm_send_telegram($chat_id, 'Test message dari WP Reseller Manage.');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
            'wrpm_msg' => !empty($r['ok']) ? 'Test Telegram terkirim' : ('Gagal kirim Telegram: ' . (string)($r['error'] ?? 'unknown')),
            'wrpm_type' => !empty($r['ok']) ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_test_waha() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_test_waha');

        $to = sanitize_text_field(wp_unslash($_POST['test_waha_to'] ?? ''));
        $r = $this->wrpm_send_waha($to, 'Test WhatsApp dari WP Reseller Manage.');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
            'wrpm_msg' => !empty($r['ok']) ? 'Test WhatsApp terkirim' : ('Gagal kirim WhatsApp: ' . (string)($r['error'] ?? 'unknown')),
            'wrpm_type' => !empty($r['ok']) ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_run_cron_now() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_run_cron_now');

        $this->cron_daily();
        $this->wrpm_log('cron_run', 'cron', '', 'Run daily cron manually');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
            'wrpm_msg' => 'Cron dijalankan (cek halaman Logs untuk detail).',
            'wrpm_type' => 'success',
        ]));
        exit;
    }

    public function handle_admin_post_wc_sync() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_wc_sync');

        if (!class_exists('WooCommerce') || !function_exists('wc_get_product_id_by_sku')) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
                'wrpm_msg' => 'WooCommerce tidak terdeteksi. Install & aktifkan WooCommerce terlebih dahulu.',
                'wrpm_type' => 'error',
            ]));
            exit;
        }

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->tbl_prices()} ORDER BY updated_at DESC", ARRAY_A);
        $created = 0; $updated = 0; $errors = 0;

        foreach ((array)$rows as $r) {
            $sku = (string)$r['id'];
            $product_id = (int)wc_get_product_id_by_sku($sku);

            $post_data = [
                'post_title'   => (string)$r['name'],
                'post_content' => (string)$r['description'],
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ];

            if ($product_id > 0) {
                $post_data['ID'] = $product_id;
                $res = wp_update_post($post_data, true);
                if (is_wp_error($res)) { $errors++; continue; }
                $updated++;
            } else {
                $res = wp_insert_post($post_data, true);
                if (is_wp_error($res) || !$res) { $errors++; continue; }
                $product_id = (int)$res;
                $created++;
            }

            // Set SKU & price
            update_post_meta($product_id, '_sku', $sku);
            update_post_meta($product_id, '_regular_price', (string)((int)$r['sale_price']));
            update_post_meta($product_id, '_price', (string)((int)$r['sale_price']));
            update_post_meta($product_id, '_wrpm_price_id', $sku);

            // Categories (product_cat)
            $cat = trim((string)($r['category'] ?? ''));
            if ($cat !== '') {
                wp_set_object_terms($product_id, [$cat], 'product_cat', false);
            }

            // Tags (product_tag) from CSV list
            $tags = $this->wrpm_sanitize_tags((string)($r['tags'] ?? ''));
            if ($tags !== '') {
                $tag_terms = array_filter(array_map('trim', explode(',', $tags)));
                if ($tag_terms) wp_set_object_terms($product_id, $tag_terms, 'product_tag', false);
            }
        }

        $this->wrpm_log('wc_sync', 'woocommerce', '', 'Sync harga produk jual -> WooCommerce', [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ]);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
            'wrpm_msg' => "WooCommerce sync selesai. Created: {$created}, Updated: {$updated}, Errors: {$errors}",
            'wrpm_type' => $errors ? 'error' : 'success',
        ]));
        exit;
    }

    public function handle_admin_post_backup_data() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_backup_data');

        global $wpdb;
        $data = [
            'version' => self::VERSION,
            'exported_at' => current_time('mysql'),
            'tables' => [
                'prices' => $wpdb->get_results("SELECT * FROM {$this->tbl_prices()}", ARRAY_A),
                'reseller' => $wpdb->get_results("SELECT * FROM {$this->tbl_reseller()}", ARRAY_A),
                'customers' => $wpdb->get_results("SELECT * FROM {$this->tbl_customers()}", ARRAY_A),
                'sellers' => $wpdb->get_results("SELECT * FROM {$this->tbl_sellers()}", ARRAY_A),
                'active' => $wpdb->get_results("SELECT * FROM {$this->tbl_active()}", ARRAY_A),
                'reminders' => $wpdb->get_results("SELECT * FROM {$this->tbl_reminders()}", ARRAY_A),
                'logs' => $wpdb->get_results("SELECT * FROM {$this->tbl_logs()}", ARRAY_A),
            ],
            'settings' => $this->wrpm_get_settings(),
        ];

        $json = $this->wrpm_json_encode($data);
        $filename = 'wrpm-backup-' . date('Y-m-d-His') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $json;
        exit;
    }

    public function handle_admin_post_restore_data() {
        if (!current_user_can(self::CAP_MANAGE_SETTINGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_restore_data');

        if (empty($_FILES['restore_file']['tmp_name'])) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
                'wrpm_msg' => 'Pilih file backup JSON terlebih dahulu.',
                'wrpm_type' => 'error',
            ]));
            exit;
        }

        $file_path = $_FILES['restore_file']['tmp_name'];
        $content = file_get_contents($file_path);
        $data = $this->wrpm_json_decode_assoc($content);

        if (!$data || !is_array($data) || empty($data['tables'])) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
                'wrpm_msg' => 'Format file backup tidak valid.',
                'wrpm_type' => 'error',
            ]));
            exit;
        }

        global $wpdb;
        $tables_map = [
            'prices' => $this->tbl_prices(),
            'reseller' => $this->tbl_reseller(),
            'customers' => $this->tbl_customers(),
            'sellers' => $this->tbl_sellers(),
            'active' => $this->tbl_active(),
            'reminders' => $this->tbl_reminders(),
            'logs' => $this->tbl_logs(),
        ];

        // Clear existing data and import
        foreach ($tables_map as $key => $table_name) {
            if (isset($data['tables'][$key]) && is_array($data['tables'][$key])) {
                $wpdb->query("TRUNCATE TABLE {$table_name}");
                foreach ($data['tables'][$key] as $row) {
                    $wpdb->insert($table_name, $row);
                }
            }
        }

        // Restore settings if present
        if (!empty($data['settings']) && is_array($data['settings'])) {
            update_option('wrpm_settings_v1', $data['settings']);
        }

        $this->wrpm_log('restore', 'settings', '', 'Restore database & settings dari file JSON');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-settings', [
            'wrpm_msg' => 'Database dan Settings berhasil di-restore!',
            'wrpm_type' => 'success',
        ]));
        exit;
    }
}
