<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Product_Prices {
    public function page_product_prices_list() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $cat = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';

        $prices = $this->tbl_prices();
        $sellers = $this->tbl_sellers();
        // Backward compatibility: older installs may not yet have seller_id column.
        $has_seller_id = (bool)$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$prices} LIKE %s", 'seller_id'));

        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $like = '%' . $wpdb->esc_like($q) . '%';
            // Search supports: nama produk, tags, nama seller, harga, description/notes.
            $conds = [
                'p.name LIKE %s',
                'p.tags LIKE %s',
                'p.description LIKE %s',
                'p.notes LIKE %s',
                'CAST(p.reseller_price AS CHAR) LIKE %s',
                'CAST(p.sale_price AS CHAR) LIKE %s',
            ];
            if ($has_seller_id) {
                $conds[] = 's.name LIKE %s';
                $conds[] = 's.phone LIKE %s';
                $conds[] = 's.whatsapp LIKE %s';
                $conds[] = 's.email LIKE %s';
            }
            $where .= ' AND (' . implode(' OR ', $conds) . ')';
            foreach ($conds as $_) { $params[] = $like; }
        }
        if ($cat !== '') {
            $where .= ' AND p.category = %s';
            $params[] = $cat;
        }
        if ($has_seller_id) {
            $sql = "SELECT p.*, s.name AS seller_name, s.email AS seller_email, s.phone AS seller_phone, s.telegram AS seller_telegram, s.whatsapp AS seller_whatsapp
                    FROM {$prices} p
                    LEFT JOIN {$sellers} s ON p.seller_id = s.id
                    WHERE {$where}
                    ORDER BY p.updated_at DESC LIMIT 500";
            $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);
        } else {
            $sql = "SELECT p.* FROM {$prices} p WHERE {$where} ORDER BY p.updated_at DESC LIMIT 500";
            $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);
            // Ensure expected keys exist for the template.
            foreach ($rows as &$r) {
                if (!isset($r['seller_id'])) $r['seller_id'] = '';
                $r['seller_name'] = $r['seller_name'] ?? '';
                $r['seller_email'] = $r['seller_email'] ?? '';
                $r['seller_phone'] = $r['seller_phone'] ?? '';
                $r['seller_telegram'] = $r['seller_telegram'] ?? '';
                $r['seller_whatsapp'] = $r['seller_whatsapp'] ?? '';
            }
            unset($r);
        }

        // categories for filter
        $cats = $wpdb->get_col("SELECT DISTINCT category FROM {$this->tbl_prices()} WHERE category <> '' ORDER BY category ASC");

        $this->render_template('admin/product-prices-list.php', [
            'rows' => $rows,
            'cats' => $cats,
            'q' => $q,
            'cat' => $cat,
        ]);
    }

    public function page_product_price_add_edit() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_prices()} WHERE id = %s", $id), ARRAY_A);
        }

        $sellers = $wpdb->get_results("SELECT id, name, email, phone, telegram, whatsapp FROM {$this->tbl_sellers()} ORDER BY name ASC", ARRAY_A);

        $this->render_template('admin/product-prices-form.php', [
            'row' => $row,
            'sellers' => $sellers,
        ]);
    }

    public function handle_admin_post_save_price() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_save_price');

        global $wpdb;
        $table = $this->tbl_prices();

        $id = !empty($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $is_edit = (bool)$id;
        if (!$id) $id = $this->wrpm_uuid();

        $data = [
            'id' => $id,
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'category' => sanitize_text_field(wp_unslash($_POST['category'] ?? '')),
            'tags' => $this->wrpm_sanitize_tags(wp_unslash($_POST['tags'] ?? '')),
            'seller_id' => null,
            'reseller_price' => (int)preg_replace('/[^0-9]/', '', (string)($_POST['reseller_price'] ?? 0)),
            'sale_price' => (int)preg_replace('/[^0-9]/', '', (string)($_POST['sale_price'] ?? 0)),
            'duration_days' => (int)($_POST['duration_days'] ?? 0),
            'description' => wp_kses_post(wp_unslash($_POST['description'] ?? '')),
            'notes' => wp_kses_post(wp_unslash($_POST['notes'] ?? '')),
            'updated_at' => current_time('mysql'),
            'updated_by' => $this->wrpm_current_user_id(),
        ];

        $seller_id = sanitize_text_field(wp_unslash($_POST['seller_id'] ?? ''));
        if ($seller_id !== '') {
            $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->tbl_sellers()} WHERE id=%s", $seller_id)) > 0;
            if ($exists) $data['seller_id'] = $seller_id;
        }

        if (!$is_edit) {
            $data['created_at'] = current_time('mysql');
        }

        if ($is_edit) {
            $ok = $wpdb->update($table, $data, ['id' => $id]);
            $this->wrpm_log('update', 'product_price', $id, 'Update harga produk jual', ['data' => $data]);
        } else {
            $ok = $wpdb->insert($table, $data);
            $this->wrpm_log('create', 'product_price', $id, 'Tambah harga produk jual', ['data' => $data]);
        }

        $url = $this->wrpm_admin_url('wrpm-product-prices', [
            'wrpm_msg' => $ok !== false ? 'Tersimpan' : 'Gagal menyimpan',
            'wrpm_type' => $ok !== false ? 'success' : 'error',
        ]);
        wp_safe_redirect($url);
        exit;
    }

    public function handle_admin_post_delete_price() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_delete_price_' . $id);

        global $wpdb;
        $ok = $wpdb->delete($this->tbl_prices(), ['id' => $id]);
        $this->wrpm_log('delete', 'product_price', $id, 'Hapus harga produk jual');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-product-prices', [
            'wrpm_msg' => $ok ? 'Terhapus' : 'Gagal menghapus',
            'wrpm_type' => $ok ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_export_prices_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_export_prices_csv');

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->tbl_prices()} ORDER BY updated_at DESC", ARRAY_A);

        $this->wrpm_log('export', 'product_price', '', 'Export CSV harga produk jual', ['count' => count($rows)]);

        $out = [];
        $out[] = ['id','name','category','tags','seller_id','reseller_price','sale_price','duration_days','description','notes','created_at','updated_at','updated_by'];
        foreach ($rows as $r) {
            $out[] = [
                $r['id'],$r['name'],$r['category'],$r['tags'],
                ($r['seller_id'] ?? ''),$r['reseller_price'],$r['sale_price'],$r['duration_days'],
                $r['description'],$r['notes'],$r['created_at'],$r['updated_at'],$r['updated_by'],
            ];
        }

        $this->wrpm_csv_send('wrpm-product-prices.csv', $out);
    }


    public function handle_admin_post_import_prices_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_import_prices_csv');

        $parsed = $this->wrpm_parse_csv_upload('csv_file');
        if (empty($parsed['ok'])) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-product-prices', [
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
            foreach ($header as $i => $h) {
                $assoc[$h] = isset($row[$i]) ? $row[$i] : '';
            }

            $id = sanitize_text_field((string)($assoc['id'] ?? ''));
            if (!$id) $id = $this->wrpm_uuid();

            $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->tbl_prices()} WHERE id=%s", $id)) > 0;

            $data = [
                'id' => $id,
                'name' => sanitize_text_field((string)($assoc['name'] ?? '')),
                'category' => sanitize_text_field((string)($assoc['category'] ?? '')),
                'tags' => $this->wrpm_sanitize_tags((string)($assoc['tags'] ?? '')),
                'seller_id' => null,
                'reseller_price' => (int)preg_replace('/[^0-9]/', '', (string)($assoc['reseller_price'] ?? 0)),
                'sale_price' => (int)preg_replace('/[^0-9]/', '', (string)($assoc['sale_price'] ?? 0)),
                'duration_days' => (int)($assoc['duration_days'] ?? 0),
                'description' => wp_kses_post((string)($assoc['description'] ?? '')),
                'notes' => wp_kses_post((string)($assoc['notes'] ?? '')),
                'updated_at' => $now,
                'updated_by' => $this->wrpm_current_user_id(),
            ];

            $seller_id = sanitize_text_field((string)($assoc['seller_id'] ?? ''));
            if ($seller_id !== '') {
                $exists_seller = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->tbl_sellers()} WHERE id=%s", $seller_id)) > 0;
                if ($exists_seller) $data['seller_id'] = $seller_id;
            }

            if (empty($data['name'])) { $skipped++; continue; }

            if ($exists) {
                $ok = $wpdb->update($this->tbl_prices(), $data, ['id' => $id]);
                if ($ok !== false) $updated++; else $skipped++;
            } else {
                $data['created_at'] = $now;
                $ok = $wpdb->insert($this->tbl_prices(), $data);
                if ($ok !== false) $created++; else $skipped++;
            }
        }

        $this->wrpm_log('import', 'product_price', '', 'Import CSV harga produk jual', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-product-prices', [
            'wrpm_msg' => "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}",
            'wrpm_type' => ($skipped > 0) ? 'error' : 'success',
        ]));
        exit;
    }

}
