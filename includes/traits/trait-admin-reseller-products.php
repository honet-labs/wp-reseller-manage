<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Reseller_Products {
    public function page_reseller_products_list() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $pay = isset($_GET['pay']) ? sanitize_text_field(wp_unslash($_GET['pay'])) : '';

        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where .= ' AND (product_name LIKE %s OR reseller_name LIKE %s OR reseller_contact LIKE %s)';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($pay !== '') {
            $where .= ' AND payment_status = %s';
            $params[] = $pay;
        }

        $sql = "SELECT * FROM {$this->tbl_reseller()} WHERE {$where} ORDER BY updated_at DESC LIMIT 500";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        $this->render_template('admin/reseller-products-list.php', [
            'rows' => $rows,
            'q' => $q,
            'pay' => $pay,
        ]);
    }

    public function page_reseller_product_add_edit() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_reseller()} WHERE id = %s", $id), ARRAY_A);
        }

        // Include seller name for nicer labels (Harga Produk Jual can be linked to a Seller)
        $prices = $wpdb->get_results(
            "SELECT p.id, p.name, p.category, p.tags, p.reseller_price, p.duration_days, p.seller_id, s.name AS seller_name\n" .
            "FROM {$this->tbl_prices()} p\n" .
            "LEFT JOIN {$this->tbl_sellers()} s ON p.seller_id = s.id\n" .
            "ORDER BY p.name ASC",
            ARRAY_A
        );
        $sellers = $wpdb->get_results("SELECT id, name, email, phone, telegram, whatsapp FROM {$this->tbl_sellers()} ORDER BY name ASC", ARRAY_A);

        $this->render_template('admin/reseller-products-form.php', [
            'row' => $row,
            'prices' => $prices,
            'sellers' => $sellers,
        ]);
    }

    public function handle_admin_post_save_reseller_product() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_save_reseller_product');

        global $wpdb;
        $table = $this->tbl_reseller();

        $id = !empty($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $is_edit = (bool)$id;
        if (!$id) $id = $this->wrpm_uuid();

        $price_id = sanitize_text_field(wp_unslash($_POST['price_id'] ?? ''));
        $price_row = $price_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_prices()} WHERE id = %s", $price_id), ARRAY_A) : null;
        if (!$price_row) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-reseller-product-add', ['wrpm_msg' => 'Produk jual belum dipilih / tidak ditemukan', 'wrpm_type' => 'error']));
            exit;
        }

        // Existing attachments (edit)
        $existing = [];
        if ($is_edit && !empty($_POST['existing_attachments'])) {
            $existing = array_map('intval', (array)$_POST['existing_attachments']);
        }
        $uploaded = $this->wrpm_handle_uploaded_images('payment_attachments');
        $attachments = array_values(array_unique(array_filter(array_merge($existing, $uploaded))));

        $duration_days = (int)($price_row['duration_days'] ?? 0);

        $purchase_date = sanitize_text_field(wp_unslash($_POST['purchase_date'] ?? ''));
        if (!$purchase_date) $purchase_date = $this->wrpm_today_date();
        $expires_at = $this->wrpm_date_add_days($purchase_date, $duration_days);

        $seller_id = sanitize_text_field(wp_unslash($_POST['seller_id'] ?? ''));
        $seller = $seller_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_sellers()} WHERE id=%s", $seller_id), ARRAY_A) : null;

        $reseller_name = sanitize_text_field(wp_unslash($_POST['reseller_name'] ?? ''));
        $reseller_contact = sanitize_text_field(wp_unslash($_POST['reseller_contact'] ?? ''));
        if ($seller) {
            if ($reseller_name === '') $reseller_name = sanitize_text_field((string)($seller['name'] ?? ''));
            if ($reseller_contact === '') {
                $parts = [];
                if (!empty($seller['phone'])) $parts[] = 'Telp: ' . $seller['phone'];
                if (!empty($seller['whatsapp'])) $parts[] = 'WA: ' . $seller['whatsapp'];
                if (!empty($seller['telegram'])) $parts[] = 'TG: ' . $seller['telegram'];
                if (!empty($seller['email'])) $parts[] = 'Email: ' . $seller['email'];
                $reseller_contact = sanitize_text_field(implode(' | ', $parts));
            }
        }

        $data = [
            'id' => $id,
            'price_id' => $price_id,
            'product_name' => sanitize_text_field((string)($price_row['name'] ?? '')),
            'category' => sanitize_text_field((string)($price_row['category'] ?? '')),
            'tags' => $this->wrpm_sanitize_tags((string)($price_row['tags'] ?? '')),
            'seller_id' => $seller_id ?: null,
            'reseller_name' => $reseller_name,
            'reseller_contact' => $reseller_contact,
            'purchase_date' => $purchase_date,
            'duration_days' => $duration_days,
            'description' => wp_kses_post((string)($price_row['description'] ?? '')),
            'price' => (int)preg_replace('/[^0-9]/', '', (string)($_POST['price'] ?? ($price_row['reseller_price'] ?? 0))),
            'expires_at' => $expires_at,
            'payment_status' => in_array((string)($_POST['payment_status'] ?? 'pending'), ['paid','pending'], true) ? (string)($_POST['payment_status'] ?? 'pending') : 'pending',
            'payment_attachments' => $attachments ? $this->wrpm_json_encode($attachments) : null,
            'notes' => wp_kses_post(wp_unslash($_POST['notes'] ?? '')),
            'updated_at' => current_time('mysql'),
            'updated_by' => $this->wrpm_current_user_id(),
        ];

        if (!$is_edit) $data['created_at'] = current_time('mysql');

        if ($is_edit) {
            $ok = $wpdb->update($table, $data, ['id' => $id]);
            $this->wrpm_log('update', 'reseller_product', $id, 'Update produk reseller', ['data' => $data]);
        } else {
            $ok = $wpdb->insert($table, $data);
            $this->wrpm_log('create', 'reseller_product', $id, 'Tambah produk reseller', ['data' => $data]);
        }

        wp_safe_redirect($this->wrpm_admin_url('wrpm-reseller-products', [
            'wrpm_msg' => $ok !== false ? 'Tersimpan' : 'Gagal menyimpan',
            'wrpm_type' => $ok !== false ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_delete_reseller_product() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_delete_reseller_product_' . $id);

        global $wpdb;
        $ok = $wpdb->delete($this->tbl_reseller(), ['id' => $id]);
        $this->wrpm_log('delete', 'reseller_product', $id, 'Hapus produk reseller');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-reseller-products', [
            'wrpm_msg' => $ok ? 'Terhapus' : 'Gagal menghapus',
            'wrpm_type' => $ok ? 'success' : 'error',
        ]));
        exit;
    }


    public function handle_admin_post_export_reseller_products_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_export_reseller_products_csv');

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->tbl_reseller()} ORDER BY updated_at DESC", ARRAY_A);

        $this->wrpm_log('export', 'reseller_product', '', 'Export CSV produk reseller', ['count' => count($rows)]);

        $out = [];
        $out[] = ['id','price_id','product_name','category','tags','seller_id','reseller_name','reseller_contact','purchase_date','duration_days','description','price','expires_at','payment_status','notes','created_at','updated_at','updated_by'];
        foreach ($rows as $r) {
            $out[] = [
                $r['id'],$r['price_id'],$r['product_name'],$r['category'],$r['tags'],
                ($r['seller_id'] ?? ''),$r['reseller_name'],$r['reseller_contact'],($r['purchase_date'] ?? ''),
                $r['duration_days'],$r['description'],$r['price'],$r['expires_at'],$r['payment_status'],$r['notes'],
                $r['created_at'],$r['updated_at'],$r['updated_by'],
            ];
        }
        $this->wrpm_csv_send('wrpm-reseller-products.csv', $out);
    }

    public function handle_admin_post_import_reseller_products_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_import_reseller_products_csv');

        $parsed = $this->wrpm_parse_csv_upload('csv_file');
        if (empty($parsed['ok'])) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-reseller-products', [
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

        foreach ($rows as $row) {
            $assoc = [];
            foreach ($header as $i => $h) $assoc[$h] = isset($row[$i]) ? $row[$i] : '';

            $id = sanitize_text_field((string)($assoc['id'] ?? ''));
            if (!$id) $id = $this->wrpm_uuid();

            // Resolve price row
            $price_id = sanitize_text_field((string)($assoc['price_id'] ?? ''));
            $price_row = null;

            if ($price_id) {
                $price_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_prices()} WHERE id=%s", $price_id), ARRAY_A);
            }
            if (!$price_row) {
                $pname = sanitize_text_field((string)($assoc['product_name'] ?? ($assoc['name'] ?? '')));
                if ($pname) {
                    $price_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_prices()} WHERE name=%s ORDER BY updated_at DESC LIMIT 1", $pname), ARRAY_A);
                    if ($price_row) $price_id = (string)$price_row['id'];
                }
            }
            if (!$price_row) { $skipped++; continue; }

            $duration_days = (int)($price_row['duration_days'] ?? 0);
            $purchase_date = sanitize_text_field((string)($assoc['purchase_date'] ?? ''));
            if (!$purchase_date) $purchase_date = $this->wrpm_today_date();
            $expires_at = $this->wrpm_date_add_days($purchase_date, $duration_days);

            $seller_id = sanitize_text_field((string)($assoc['seller_id'] ?? ''));

            $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->tbl_reseller()} WHERE id=%s", $id)) > 0;

            $data = [
                'id' => $id,
                'price_id' => $price_id,
                'product_name' => sanitize_text_field((string)($price_row['name'] ?? '')),
                'category' => sanitize_text_field((string)($price_row['category'] ?? '')),
                'tags' => $this->wrpm_sanitize_tags((string)($price_row['tags'] ?? '')),
                'seller_id' => $seller_id ?: null,
                'reseller_name' => sanitize_text_field((string)($assoc['reseller_name'] ?? '')),
                'reseller_contact' => sanitize_text_field((string)($assoc['reseller_contact'] ?? '')),
                'purchase_date' => $purchase_date,
                'duration_days' => $duration_days,
                'description' => wp_kses_post((string)($price_row['description'] ?? '')),
                'price' => (int)preg_replace('/[^0-9]/', '', (string)($assoc['price'] ?? ($price_row['reseller_price'] ?? 0))),
                'expires_at' => $expires_at,
                'payment_status' => in_array((string)($assoc['payment_status'] ?? 'pending'), ['paid','pending'], true) ? (string)($assoc['payment_status'] ?? 'pending') : 'pending',
                'payment_attachments' => null,
                'notes' => wp_kses_post((string)($assoc['notes'] ?? '')),
                'updated_at' => $now,
                'updated_by' => $this->wrpm_current_user_id(),
            ];

            if ($exists) {
                $ok = $wpdb->update($this->tbl_reseller(), $data, ['id' => $id]);
                if ($ok !== false) $updated++; else $skipped++;
            } else {
                $data['created_at'] = $now;
                $ok = $wpdb->insert($this->tbl_reseller(), $data);
                if ($ok !== false) $created++; else $skipped++;
            }
        }

        $this->wrpm_log('import', 'reseller_product', '', 'Import CSV produk reseller', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-reseller-products', [
            'wrpm_msg' => "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped} (baris bisa skip jika price_id / product_name tidak cocok)",
            'wrpm_type' => ($skipped > 0) ? 'error' : 'success',
        ]));
        exit;
    }

}
