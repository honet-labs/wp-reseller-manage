<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Active_Products {
    public function page_active_products_list() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $st = isset($_GET['st']) ? sanitize_text_field(wp_unslash($_GET['st'])) : '';

        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where .= ' AND (product_label LIKE %s OR customer_name LIKE %s OR customer_contact LIKE %s)';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (in_array($st, ['active','expired'], true)) {
            $where .= ' AND status = %s';
            $params[] = $st;
        }

        $sql = "SELECT * FROM {$this->tbl_active()} WHERE {$where} ORDER BY updated_at DESC LIMIT 500";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        $this->render_template('admin/active-products-list.php', [
            'rows' => $rows,
            'q' => $q,
            'st' => $st,
        ]);
    }

    public function page_active_product_add_edit() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_active()} WHERE id = %s", $id), ARRAY_A);
        }

        $customers = $wpdb->get_results("SELECT id, name, email, phone, telegram, whatsapp FROM {$this->tbl_customers()} ORDER BY name ASC", ARRAY_A);
        $resellers = $wpdb->get_results("SELECT id, product_name, duration_days, price, reseller_name, expires_at FROM {$this->tbl_reseller()} ORDER BY updated_at DESC", ARRAY_A);

        $this->render_template('admin/active-products-form.php', [
            'row' => $row,
            'customers' => $customers,
            'resellers' => $resellers,
        ]);
    }

    private function wrpm_customer_contact_summary($c) {
        $parts = [];
        if (!empty($c['phone'])) $parts[] = 'Telp: ' . $c['phone'];
        if (!empty($c['whatsapp'])) $parts[] = 'WA: ' . $c['whatsapp'];
        if (!empty($c['telegram'])) $parts[] = 'TG: ' . $c['telegram'];
        if (!empty($c['email'])) $parts[] = 'Email: ' . $c['email'];
        return implode(' | ', $parts);
    }

    private function wrpm_sync_reminders_for_active($active_row) {
        global $wpdb;
        $s = $this->wrpm_get_settings();
        $offsets = (array)($s['reminder_offsets'] ?? [7,3,1]);
        $offsets = array_values(array_unique(array_filter(array_map('intval', $offsets), function($d){ return $d > 0 && $d < 3660; })));
        if (empty($offsets)) $offsets = [7,3,1];

        $active_id = (string)$active_row['id'];
        $customer_id = (string)$active_row['customer_id'];
        $expires_at = (string)$active_row['expires_at'];

        foreach ($offsets as $d) {
            $reminder_date = $this->wrpm_date_add_days($expires_at, -$d);
            if (!$reminder_date) continue;
            $remaining = $this->wrpm_date_diff_days($this->wrpm_today_date(), $expires_at);

            // Find existing reminder for this active+offset.
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id,status FROM {$this->tbl_reminders()} WHERE active_product_id=%s AND offset_days=%d LIMIT 1",
                $active_id, $d
            ), ARRAY_A);

            $now = current_time('mysql');
            if ($existing) {
                // Keep status if already sent.
                $status = ($existing['status'] === 'sent') ? 'sent' : 'pending';
                $wpdb->update($this->tbl_reminders(), [
                    'customer_id' => $customer_id,
                    'reminder_date' => $reminder_date,
                    'remaining_days' => $remaining,
                    'status' => $status,
                    'updated_at' => $now,
                ], ['id' => $existing['id']]);
            } else {
                $wpdb->insert($this->tbl_reminders(), [
                    'id' => $this->wrpm_uuid(),
                    'active_product_id' => $active_id,
                    'customer_id' => $customer_id,
                    'offset_days' => $d,
                    'reminder_date' => $reminder_date,
                    'remaining_days' => $remaining,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function handle_admin_post_save_active_product() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_save_active_product');

        global $wpdb;
        $table = $this->tbl_active();

        $id = !empty($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $is_edit = (bool)$id;
        if (!$id) $id = $this->wrpm_uuid();

        $reseller_product_id = sanitize_text_field(wp_unslash($_POST['reseller_product_id'] ?? ''));
        $rp = $reseller_product_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_reseller()} WHERE id=%s", $reseller_product_id), ARRAY_A) : null;
        $back_args = $is_edit ? ['id' => $id] : [];

        if (!$rp) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-active-product-add', array_merge($back_args, ['wrpm_msg' => 'Produk reseller belum dipilih / tidak ditemukan', 'wrpm_type' => 'error'])));
            exit;
        }

        // Customer: choose existing OR create inline on this form.
        $add_new_customer = !empty($_POST['add_new_customer']) && (string)wp_unslash($_POST['add_new_customer']) === '1';

        $customer_id = '';
        $cust = null;
        if ($add_new_customer) {
            $new_name = sanitize_text_field(wp_unslash($_POST['new_customer_name'] ?? ''));
            if ($new_name === '') {
                wp_safe_redirect($this->wrpm_admin_url('wrpm-active-product-add', array_merge($back_args, ['wrpm_msg' => 'Nama customer baru wajib diisi', 'wrpm_type' => 'error'])));
                exit;
            }

            $customer_id = $this->wrpm_uuid();
            $now = current_time('mysql');
            $cdata = [
                'id' => $customer_id,
                'name' => $new_name,
                'email' => sanitize_email(wp_unslash($_POST['new_customer_email'] ?? '')),
                'phone' => sanitize_text_field(wp_unslash($_POST['new_customer_phone'] ?? '')),
                'telegram' => sanitize_text_field(wp_unslash($_POST['new_customer_telegram'] ?? '')),
                'whatsapp' => sanitize_text_field(wp_unslash($_POST['new_customer_whatsapp'] ?? '')),
                'created_at' => $now,
                'updated_at' => $now,
                'updated_by' => $this->wrpm_current_user_id(),
            ];

            $okc = $wpdb->insert($this->tbl_customers(), $cdata);
            if ($okc === false) {
                wp_safe_redirect($this->wrpm_admin_url('wrpm-active-product-add', array_merge($back_args, ['wrpm_msg' => 'Gagal membuat customer baru', 'wrpm_type' => 'error'])));
                exit;
            }

            $cust = $cdata;
            $this->wrpm_log('create', 'customer', $customer_id, 'Tambah customer (inline dari Produk Aktif)', ['data' => $cdata]);
        } else {
            $customer_id = sanitize_text_field(wp_unslash($_POST['customer_id'] ?? ''));
            $cust = $customer_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_customers()} WHERE id=%s", $customer_id), ARRAY_A) : null;
            if (!$cust) {
                wp_safe_redirect($this->wrpm_admin_url('wrpm-active-product-add', array_merge($back_args, ['wrpm_msg' => 'Customer belum dipilih / tidak ditemukan', 'wrpm_type' => 'error'])));
                exit;
            }
        }

        $start_date = sanitize_text_field(wp_unslash($_POST['start_date'] ?? ''));
        if (!$start_date) $start_date = $this->wrpm_today_date();

        $duration_days = (int)($_POST['duration_days'] ?? 0);
        if ($duration_days <= 0) $duration_days = (int)($rp['duration_days'] ?? 0);

        $expires_at = $this->wrpm_date_add_days($start_date, $duration_days);
        if (!$expires_at) $expires_at = $this->wrpm_today_date();

        $today = $this->wrpm_today_date();
        $status = (strtotime($expires_at) < strtotime($today)) ? 'expired' : 'active';

        $product_label = (string)($rp['product_name'] ?? '');
        $product_label = trim($product_label);
        $product_label = $product_label ? ($product_label . ' - ' . $duration_days) : ('Produk - ' . $duration_days);

        // Existing attachments (edit)
        $existing = [];
        if ($is_edit && !empty($_POST['existing_attachments'])) {
            $existing = array_map('intval', (array)$_POST['existing_attachments']);
        }
        $uploaded = $this->wrpm_handle_uploaded_images('payment_attachments');
        $attachments = array_values(array_unique(array_filter(array_merge($existing, $uploaded))));

        $data = [
            'id' => $id,
            'reseller_product_id' => $reseller_product_id,
            'product_label' => sanitize_text_field($product_label),
            'customer_id' => $customer_id,
            'customer_name' => sanitize_text_field((string)($cust['name'] ?? '')),
            'customer_contact' => sanitize_text_field($this->wrpm_customer_contact_summary($cust)),
            'start_date' => $start_date,
            'duration_days' => $duration_days,
            'expires_at' => $expires_at,
            'status' => $status,
            'price' => (int)preg_replace('/[^0-9]/', '', (string)($_POST['price'] ?? 0)),
            'payment_status' => in_array((string)($_POST['payment_status'] ?? 'pending'), ['paid','pending'], true) ? (string)($_POST['payment_status'] ?? 'pending') : 'pending',
            'payment_attachments' => $attachments ? $this->wrpm_json_encode($attachments) : null,
            'notes' => wp_kses_post(wp_unslash($_POST['notes'] ?? '')),
            'updated_at' => current_time('mysql'),
            'updated_by' => $this->wrpm_current_user_id(),
        ];

        // If price not provided, default to reseller product price.
        if ((int)$data['price'] <= 0) {
            $data['price'] = (int)($rp['price'] ?? 0);
        }
        if (!$is_edit) $data['created_at'] = current_time('mysql');

        if ($is_edit) {
            $ok = $wpdb->update($table, $data, ['id' => $id]);
            $this->wrpm_log('update', 'active_product', $id, 'Update produk aktif', ['data' => $data]);
        } else {
            $ok = $wpdb->insert($table, $data);
            $this->wrpm_log('create', 'active_product', $id, 'Tambah produk aktif', ['data' => $data]);
        }

        // Sync reminders for this active product.
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_active()} WHERE id=%s", $id), ARRAY_A);
        if ($row) $this->wrpm_sync_reminders_for_active($row);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-active-products', [
            'wrpm_msg' => $ok !== false ? 'Tersimpan' : 'Gagal menyimpan',
            'wrpm_type' => $ok !== false ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_delete_active_product() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_delete_active_product_' . $id);

        global $wpdb;
        $ok = $wpdb->delete($this->tbl_active(), ['id' => $id]);
        $wpdb->delete($this->tbl_reminders(), ['active_product_id' => $id]);
        $this->wrpm_log('delete', 'active_product', $id, 'Hapus produk aktif');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-active-products', [
            'wrpm_msg' => $ok ? 'Terhapus' : 'Gagal menghapus',
            'wrpm_type' => $ok ? 'success' : 'error',
        ]));
        exit;
    }

    public function wrpm_extend_active_product($id, $extra_days) {
        global $wpdb;
        $id = sanitize_text_field((string)$id);
        $extra_days = (int)$extra_days;
        if ($extra_days <= 0) return false;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_active()} WHERE id=%s", $id), ARRAY_A);
        if (!$row) return false;

        $new_duration = (int)$row['duration_days'] + $extra_days;
        $expires_at = $this->wrpm_date_add_days((string)$row['start_date'], $new_duration);
        $today = $this->wrpm_today_date();
        $status = (strtotime($expires_at) < strtotime($today)) ? 'expired' : 'active';

        $product_label = (string)$row['product_label'];
        // Keep base name and replace trailing duration if possible.
        if (preg_match('/^(.*)\s-\s\d+$/', $product_label, $m)) {
            $product_label = trim($m[1]) . ' - ' . $new_duration;
        } else {
            $product_label = trim($product_label) . ' - ' . $new_duration;
        }

        $ok = $wpdb->update($this->tbl_active(), [
            'duration_days' => $new_duration,
            'expires_at' => $expires_at,
            'status' => $status,
            'product_label' => sanitize_text_field($product_label),
            'updated_at' => current_time('mysql'),
            'updated_by' => $this->wrpm_current_user_id(),
        ], ['id' => $id]);

        if ($ok === false) return false;
        $this->wrpm_log('extend', 'active_product', $id, 'Perpanjang produk aktif', ['extra_days' => $extra_days]);

        $row2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_active()} WHERE id=%s", $id), ARRAY_A);
        if ($row2) $this->wrpm_sync_reminders_for_active($row2);

        return true;
    }

    public function handle_admin_post_extend_active_product() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        check_admin_referer('wrpm_extend_active_product_' . $id);
        $days = (int)($_POST['days'] ?? 0);

        $ok = $this->wrpm_extend_active_product($id, $days);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-active-products', [
            'wrpm_msg' => $ok ? 'Durasi ditambah' : 'Gagal menambah durasi',
            'wrpm_type' => $ok ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_invoice_pdf() {
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_invoice_pdf_' . $id);
        $this->wrpm_output_invoice_pdf($id);
    }


    public function handle_admin_post_export_active_products_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_export_active_products_csv');

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->tbl_active()} ORDER BY updated_at DESC", ARRAY_A);

        $this->wrpm_log('export', 'active_product', '', 'Export CSV produk aktif', ['count' => count($rows)]);

        $out = [];
        $out[] = ['id','reseller_product_id','product_label','customer_id','customer_name','customer_contact','start_date','duration_days','expires_at','status','price','payment_status','notes','created_at','updated_at','updated_by'];
        foreach ($rows as $r) {
            $out[] = [
                $r['id'],$r['reseller_product_id'],$r['product_label'],$r['customer_id'],$r['customer_name'],$r['customer_contact'],
                $r['start_date'],$r['duration_days'],$r['expires_at'],$r['status'],$r['price'],($r['payment_status'] ?? 'pending'),$r['notes'],
                $r['created_at'],$r['updated_at'],$r['updated_by'],
            ];
        }
        $this->wrpm_csv_send('wrpm-active-products.csv', $out);
    }

    public function handle_admin_post_import_active_products_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_import_active_products_csv');

        $parsed = $this->wrpm_parse_csv_upload('csv_file');
        if (empty($parsed['ok'])) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-active-products', [
                'wrpm_msg' => 'Gagal import CSV: ' . (string)($parsed['error'] ?? 'unknown'),
                'wrpm_type' => 'error',
            ]));
            exit;
        }

        global $wpdb;
        $header = array_map('strtolower', array_map('trim', (array)$parsed['header']));
        $rows = (array)$parsed['rows'];

        $created = 0; $updated = 0; $skipped = 0;
        $now = current_time('mysql');
        $today = $this->wrpm_today_date();

        foreach ($rows as $row) {
            $assoc = [];
            foreach ($header as $i => $h) $assoc[$h] = isset($row[$i]) ? $row[$i] : '';

            $id = sanitize_text_field((string)($assoc['id'] ?? ''));
            if (!$id) $id = $this->wrpm_uuid();

            // Resolve reseller_product
            $reseller_product_id = sanitize_text_field((string)($assoc['reseller_product_id'] ?? ''));
            $rp = null;
            if ($reseller_product_id) {
                $rp = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_reseller()} WHERE id=%s", $reseller_product_id), ARRAY_A);
            }
            if (!$rp) { $skipped++; continue; }

            // Resolve customer
            $customer_id = sanitize_text_field((string)($assoc['customer_id'] ?? ''));
            $cust = null;
            if ($customer_id) {
                $cust = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_customers()} WHERE id=%s", $customer_id), ARRAY_A);
            }
            if (!$cust) {
                $cname = sanitize_text_field((string)($assoc['customer_name'] ?? ''));
                if ($cname) {
                    $cust = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_customers()} WHERE name=%s ORDER BY updated_at DESC LIMIT 1", $cname), ARRAY_A);
                    if ($cust) $customer_id = (string)$cust['id'];
                }
            }
            if (!$cust) { $skipped++; continue; }

            $start_date = sanitize_text_field((string)($assoc['start_date'] ?? ''));
            if (!$start_date) $start_date = $today;

            $duration_days = (int)($assoc['duration_days'] ?? 0);
            if ($duration_days <= 0) $duration_days = (int)($rp['duration_days'] ?? 0);
            if ($duration_days <= 0) { $skipped++; continue; }

            $expires_at = $this->wrpm_date_add_days($start_date, $duration_days);
            if (!$expires_at) $expires_at = $today;

            $status = (strtotime($expires_at) < strtotime($today)) ? 'expired' : 'active';

            $product_label = sanitize_text_field((string)($assoc['product_label'] ?? ''));
            if ($product_label === '') {
                $base = trim((string)($rp['product_name'] ?? 'Produk'));
                $product_label = $base . ' - ' . $duration_days;
            }

            $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->tbl_active()} WHERE id=%s", $id)) > 0;

            $data = [
                'id' => $id,
                'reseller_product_id' => $reseller_product_id,
                'product_label' => $product_label,
                'customer_id' => $customer_id,
                'customer_name' => sanitize_text_field((string)($cust['name'] ?? '')),
                'customer_contact' => sanitize_text_field($this->wrpm_customer_contact_summary($cust)),
                'start_date' => $start_date,
                'duration_days' => $duration_days,
                'expires_at' => $expires_at,
                'status' => $status,
                'price' => (int)preg_replace('/[^0-9]/', '', (string)($assoc['price'] ?? 0)),
                'payment_status' => in_array((string)($assoc['payment_status'] ?? 'pending'), ['paid','pending'], true) ? (string)($assoc['payment_status'] ?? 'pending') : 'pending',
                'payment_attachments' => null,
                'notes' => wp_kses_post((string)($assoc['notes'] ?? '')),
                'updated_at' => $now,
                'updated_by' => $this->wrpm_current_user_id(),
            ];

            if ((int)$data['price'] <= 0) $data['price'] = (int)($rp['price'] ?? 0);

            if ($exists) {
                $ok = $wpdb->update($this->tbl_active(), $data, ['id' => $id]);
                if ($ok !== false) $updated++; else { $skipped++; continue; }
            } else {
                $data['created_at'] = $now;
                $ok = $wpdb->insert($this->tbl_active(), $data);
                if ($ok !== false) $created++; else { $skipped++; continue; }
            }

            $row2 = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_active()} WHERE id=%s", $id), ARRAY_A);
            if ($row2) $this->wrpm_sync_reminders_for_active($row2);
        }

        $this->wrpm_log('import', 'active_product', '', 'Import CSV produk aktif', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-active-products', [
            'wrpm_msg' => "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped} (baris bisa skip jika reseller_product_id / customer tidak cocok)",
            'wrpm_type' => ($skipped > 0) ? 'error' : 'success',
        ]));
        exit;
    }

}
