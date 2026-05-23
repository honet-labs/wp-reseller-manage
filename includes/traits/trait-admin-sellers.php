<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Sellers {
    public function page_sellers_list() {
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

        $sql = "SELECT * FROM {$this->tbl_sellers()} WHERE {$where} ORDER BY updated_at DESC LIMIT 500";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        $this->render_template('admin/sellers-list.php', [
            'rows' => $rows,
            'q' => $q,
        ]);
    }

    public function page_seller_add_edit() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_sellers()} WHERE id = %s", $id), ARRAY_A);
        }

        $this->render_template('admin/sellers-form.php', ['row' => $row]);
    }

    public function handle_admin_post_save_seller() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_save_seller');

        global $wpdb;
        $table = $this->tbl_sellers();

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
            $this->wrpm_log('update', 'seller', $id, 'Update seller');
        } else {
            $ok = $wpdb->insert($table, $data);
            $this->wrpm_log('create', 'seller', $id, 'Tambah seller');
        }

        wp_safe_redirect($this->wrpm_admin_url('wrpm-sellers', [
            'wrpm_msg' => $ok !== false ? 'Tersimpan' : 'Gagal menyimpan',
            'wrpm_type' => $ok !== false ? 'success' : 'error',
        ]));
        exit;
    }

    public function handle_admin_post_delete_seller() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_delete_seller_' . $id);

        global $wpdb;
        $ok = $wpdb->delete($this->tbl_sellers(), ['id' => $id]);
        $this->wrpm_log('delete', 'seller', $id, 'Hapus seller');

        wp_safe_redirect($this->wrpm_admin_url('wrpm-sellers', [
            'wrpm_msg' => $ok ? 'Terhapus' : 'Gagal menghapus',
            'wrpm_type' => $ok ? 'success' : 'error',
        ]));
        exit;
    }
}
