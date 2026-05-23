<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Reminders {
    public function page_reminders_list() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        // Backward compatible params
        $st = isset($_GET['st']) ? sanitize_text_field(wp_unslash($_GET['st'])) : '';

        // New filters (default: hanya yang jatuh tempo & belum terkirim)
        $off = isset($_GET['off']) ? (int)($_GET['off']) : 0;
        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $due = isset($_GET['due']) ? (int)($_GET['due']) : 1;      // 1 = reminder_date <= hari ini
        $sent = isset($_GET['sent']) ? (int)($_GET['sent']) : 0;   // 1 = tampilkan yang sudah terkirim

        $where = '1=1';
        $params = [];

        // Status filter: if user explicitly provides st, respect it. Otherwise default hide sent.
        if (in_array($st, ['pending','sent'], true)) {
            $where .= ' AND r.status = %s';
            $params[] = $st;
        } else {
            if (!$sent) {
                $where .= " AND r.status = 'pending'";
            }
        }

        if (in_array($off, [1,3,7], true)) {
            $where .= ' AND r.offset_days = %d';
            $params[] = $off;
        }

        if ($due) {
            $where .= ' AND r.reminder_date <= %s';
            $params[] = $this->wrpm_today_date();
        }

        if ($q !== '') {
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where .= ' AND (a.product_label LIKE %s OR a.customer_name LIKE %s)';
            $params[] = $like; $params[] = $like;
        }

        $order = $due ? 'r.reminder_date ASC' : 'r.reminder_date DESC';

        $sql = "SELECT r.*, 
                    a.product_label, a.expires_at, a.start_date, a.duration_days, a.price, a.customer_name, a.customer_contact,
                    c.email AS customer_email, c.telegram AS customer_telegram, c.whatsapp AS customer_whatsapp
                FROM {$this->tbl_reminders()} r
                LEFT JOIN {$this->tbl_active()} a ON a.id = r.active_product_id
                LEFT JOIN {$this->tbl_customers()} c ON c.id = r.customer_id
                WHERE {$where}
                ORDER BY {$order}, r.offset_days ASC
                LIMIT 500";

        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        $this->render_template('admin/reminders-list.php', [
            'rows' => $rows,
            'st' => $st,
            'off' => $off,
            'q' => $q,
            'due' => $due,
            'sent' => $sent,
        ]);
    }

    private function wrpm_build_reminder_vars($active_row, $customer_row, $reminder_row = null) {
        $vars = [
            'customer_name' => (string)($active_row['customer_name'] ?? ($customer_row['name'] ?? '')),
            'product_label' => (string)($active_row['product_label'] ?? ''),
            'start_date' => (string)($active_row['start_date'] ?? ''),
            'duration_days' => (string)($active_row['duration_days'] ?? ''),
            'expires_at' => (string)($active_row['expires_at'] ?? ''),
            'price' => $this->wrpm_money_idr((float)($active_row['price'] ?? 0)),
            'remaining_days' => (string)($reminder_row['remaining_days'] ?? $this->wrpm_date_diff_days($this->wrpm_today_date(), (string)($active_row['expires_at'] ?? ''))),
        ];
        return $vars;
    }

    private function wrpm_send_reminder_for_row($reminder_id, $force = false) {
        global $wpdb;
        $rem = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_reminders()} WHERE id=%s", $reminder_id), ARRAY_A);
        if (!$rem) return ['ok' => false, 'error' => 'Reminder not found'];
        if (!$force && $rem['status'] === 'sent') return ['ok' => true, 'skipped' => true];

        $active = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_active()} WHERE id=%s", (string)$rem['active_product_id']), ARRAY_A);
        if (!$active) return ['ok' => false, 'error' => 'Active product not found'];

        $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tbl_customers()} WHERE id=%s", (string)$rem['customer_id']), ARRAY_A);
        if (!$customer) return ['ok' => false, 'error' => 'Customer not found'];

        $s = $this->wrpm_get_settings();
        $vars = $this->wrpm_build_reminder_vars($active, $customer, $rem);

        $sent_via = [];
        $errors = [];

        // Email
        $to = (string)($customer['email'] ?? '');
        if ($to) {
            $r = $this->wrpm_send_email($to, (string)($s['email_subject'] ?? ''), (string)($s['email_template'] ?? ''), $vars);
            if (!empty($r['ok'])) $sent_via[] = 'email';
            else $errors[] = 'email: ' . (string)($r['error'] ?? 'failed');
        }

        // Telegram
        $tg_chat = (string)($customer['telegram'] ?? '');
        if (!empty($s['telegram_enabled'])) {
            $msg = $this->wrpm_render_template((string)($s['telegram_template'] ?? ''), $vars);
            $r = $this->wrpm_send_telegram($tg_chat, $msg);
            if (!empty($r['ok'])) $sent_via[] = 'telegram';
            else $errors[] = 'telegram: ' . (string)($r['error'] ?? 'failed');
        }

        // WhatsApp (WAHA) with Milestone Templates
        $wa_num = (string)($customer['whatsapp'] ?? '');
        if (!empty($s['waha_enabled']) && $wa_num) {
            $offset = (int)($rem['offset_days'] ?? 0);
            $wa_tpl = (string)($s['whatsapp_template'] ?? '');
            if ($offset === 7 && !empty($s['whatsapp_template_h7'])) {
                $wa_tpl = $s['whatsapp_template_h7'];
            } elseif ($offset === 3 && !empty($s['whatsapp_template_h3'])) {
                $wa_tpl = $s['whatsapp_template_h3'];
            } elseif ($offset === 1 && !empty($s['whatsapp_template_h1'])) {
                $wa_tpl = $s['whatsapp_template_h1'];
            }

            $msg = $this->wrpm_render_template($wa_tpl, $vars);
            $r = $this->wrpm_send_waha($wa_num, $msg);
            if (!empty($r['ok'])) $sent_via[] = 'whatsapp';
            else $errors[] = 'whatsapp: ' . (string)($r['error'] ?? 'failed');
        }

        $ok_any = !empty($sent_via);
        $now = current_time('mysql');
        $wpdb->update($this->tbl_reminders(), [
            'status' => $ok_any ? 'sent' : 'pending',
            'sent_via' => $ok_any ? implode(',', $sent_via) : '',
            'sent_at' => $ok_any ? $now : null,
            'last_error' => $errors ? implode(' | ', $errors) : null,
            'updated_at' => $now,
        ], ['id' => (string)$reminder_id]);

        $this->wrpm_log($ok_any ? 'reminder_sent' : 'reminder_failed', 'reminder', (string)$reminder_id, $ok_any ? 'Reminder sent' : 'Reminder failed', [
            'sent_via' => $sent_via,
            'errors' => $errors,
        ]);

        return ['ok' => $ok_any, 'sent_via' => $sent_via, 'errors' => $errors];
    }

    public function handle_admin_post_send_reminder_manual() {
        if (!current_user_can(self::CAP_MANAGE)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        check_admin_referer('wrpm_send_reminder_' . $id);

        $r = $this->wrpm_send_reminder_for_row($id, true);

        wp_safe_redirect($this->wrpm_admin_url('wrpm-reminders', [
            'wrpm_msg' => !empty($r['ok']) ? 'Reminder terkirim' : ('Gagal kirim reminder: ' . (string)($r['errors'][0] ?? 'unknown')),
            'wrpm_type' => !empty($r['ok']) ? 'success' : 'error',
        ]));
        exit;
    }

    /** Daily cron: update statuses + send reminders */
    public function cron_daily() {
        global $wpdb;
        $today = $this->wrpm_today_date();
        $now = current_time('mysql');

        // 1) Update active status
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tbl_active()} SET status='expired', updated_at=%s WHERE status='active' AND expires_at < %s",
            $now, $today
        ));

        // 2) Update remaining days
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tbl_reminders()} r
             LEFT JOIN {$this->tbl_active()} a ON a.id = r.active_product_id
             SET r.remaining_days = DATEDIFF(a.expires_at, %s), r.updated_at=%s",
            $today, $now
        ));

        // 3) Send due reminders
        $due = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->tbl_reminders()} WHERE status='pending' AND reminder_date <= %s ORDER BY reminder_date ASC LIMIT 200",
            $today
        ));

        foreach ((array)$due as $rid) {
            $rid = sanitize_text_field((string)$rid);
            $this->wrpm_send_reminder_for_row($rid, false);
        }
    }
}
