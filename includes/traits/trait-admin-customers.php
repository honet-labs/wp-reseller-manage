<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Customers {
    public function page_customers_list() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;
        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where .= ' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s OR telegram LIKE %s OR whatsapp LIKE %s)';
            $params = array_fill(0, 5, $like);
        }

        $sql = "SELECT * FROM {$this->tbl_customers()} WHERE {$where} ORDER BY updated_at DESC LIMIT 500";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        $this->render_template('admin/customers-list.php', [
            'rows' => $rows,
            'q' => $q,
        ]);
    }

    public function page_customer_add_edit() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_customers()} WHERE id = %s", $id), ARRAY_A);
        }

        $this->render_template('admin/customers-form.php', ['row' => $row]);
    }

    public function handle_admin_post_save_customer() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_save_customer');

        global $wpdb;
        $table = $this->tbl_customers();

        $id = !empty($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $is_edit = (bool)$id;
        if (!$id) $id = $this->wrpm_uuid();

        $data = [
            'id' => $id,
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'phone' => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            'telegram' => sanitize_text_field(wp_unslash($_POST['telegram'] ?? '')),
            'whatsapp' => sanitize_text_field(wp_unslash($_POST['whatsapp'] ?? '')),
            'updated_at' => current_time('mysql'),
            'updated_by' => $this->wrpm_current_user_id(),
        ];
        if (!$is_edit) $data['created_at'] = current_time('mysql');

        if ($is_edit) {
            $ok = $wpdb->update($table, $data, ['id' => $id]);
            $this->wrpm_log('update', 'customer', $id, 'Update customer', ['data' => $data]);
        } else {
            $ok = $wpdb->insert($table, $data);
            $this->wrpm_log('create', 'customer', $id, 'Tambah customer', ['data' => $data]);
        }

        wp_safe_redirect($this->wrpm_admin_url('wrpm-customers', [
            'wrpm_msg' => $ok !== false ? 'Tersimpan' : 'Gagal menyimpan',
            'wrpm_type' => $ok !== false ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_delete_customer() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_delete_customer_' . $id);

        global $wpdb;
        $ok = $wpdb->delete($this->tbl_customers(), ['id' => $id]);
        $this->wrpm_log('delete', 'customer', $id, 'Hapus customer');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-customers', [
            'wrpm_msg' => $ok ? 'Terhapus' : 'Gagal menghapus',
            'wrpm_type' => $ok ? 'success' : 'error',
        ]));
        exit;
    }


    public function handle_admin_post_export_customers_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_export_customers_csv');

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->tbl_customers()} ORDER BY updated_at DESC", ARRAY_A);

        $this->wrpm_log('export', 'customer', '', 'Export CSV customer', ['count' => count($rows)]);

        $out = [];
        $out[] = ['id','name','email','phone','telegram','whatsapp','created_at','updated_at','updated_by'];
        foreach ($rows as $r) {
            $out[] = [
                $r['id'],$r['name'],$r['email'],$r['phone'],$r['telegram'],$r['whatsapp'],
                $r['created_at'],$r['updated_at'],$r['updated_by'],
            ];
        }
        $this->wrpm_csv_send('wrpm-customers.csv', $out);
    }

    public function handle_admin_post_import_customers_csv() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_import_customers_csv');

        $parsed = $this->wrpm_parse_csv_upload('csv_file');
        if (empty($parsed['ok'])) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-customers', [
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

            $name = sanitize_text_field((string)($assoc['name'] ?? ''));
            if ($name === '') { $skipped++; continue; }

            $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->tbl_customers()} WHERE id=%s", $id)) > 0;

            $data = [
                'id' => $id,
                'name' => $name,
                'email' => sanitize_email((string)($assoc['email'] ?? '')),
                'phone' => sanitize_text_field((string)($assoc['phone'] ?? '')),
                'telegram' => sanitize_text_field((string)($assoc['telegram'] ?? '')),
                'whatsapp' => sanitize_text_field((string)($assoc['whatsapp'] ?? '')),
                'updated_at' => $now,
                'updated_by' => $this->wrpm_current_user_id(),
            ];

            if ($exists) {
                $ok = $wpdb->update($this->tbl_customers(), $data, ['id' => $id]);
                if ($ok !== false) $updated++; else $skipped++;
            } else {
                $data['created_at'] = $now;
                $ok = $wpdb->insert($this->tbl_customers(), $data);
                if ($ok !== false) $created++; else $skipped++;
            }
        }

        $this->wrpm_log('import', 'customer', '', 'Import CSV customer', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-customers', [
            'wrpm_msg' => "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}",
            'wrpm_type' => ($skipped > 0) ? 'error' : 'success',
        ]));
        exit;
    }

}
