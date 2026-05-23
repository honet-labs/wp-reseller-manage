<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Dashboard {
    public function page_dashboard() {
        if (!current_user_can(self::CAP_MANAGE)) {
            wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        }

        global $wpdb;
        $total_reseller = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->tbl_reseller()}");
        $total_active = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_active()} WHERE status = %s",
            'active'
        ));
        $total_expired = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_active()} WHERE status = %s",
            'expired'
        ));
        $total_income = (float)$wpdb->get_var("SELECT COALESCE(SUM(price),0) FROM {$this->tbl_active()}");

        $pending_reseller = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_reseller()} WHERE payment_status = %s",
            'pending'
        ));
        $due_reminders = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_reminders()} WHERE status = 'pending' AND reminder_date <= %s",
            $this->wrpm_today_date()
        ));

        $today = $this->wrpm_today_date();
        $in7 = $this->wrpm_date_add_days($today, 7);
        $in3 = $this->wrpm_date_add_days($today, 3);
        $in1 = $this->wrpm_date_add_days($today, 1);

        $exp7 = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_active()} WHERE status='active' AND expires_at >= %s AND expires_at <= %s",
            $today, $in7
        ));
        $exp3 = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_active()} WHERE status='active' AND expires_at >= %s AND expires_at <= %s",
            $today, $in3
        ));
        $exp1 = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tbl_active()} WHERE status='active' AND expires_at >= %s AND expires_at <= %s",
            $today, $in1
        ));

        $soon = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tbl_active()} WHERE status = %s AND expires_at >= %s AND expires_at <= %s ORDER BY expires_at ASC LIMIT 50",
            'active', $today, $in7
        ), ARRAY_A);

        // Calculate revenue for the last 6 months
        $revenue_monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m_val = date('Y-m', strtotime("-$i months"));
            $m_label = date('F Y', strtotime("-$i months"));
            $revenue = (float)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(price),0) FROM {$this->tbl_active()} WHERE start_date LIKE %s",
                $m_val . '%'
            ));
            $revenue_monthly[] = [
                'label' => $m_label,
                'revenue' => $revenue
            ];
        }

        $this->render_template('admin/dashboard.php', [
            'total_reseller' => $total_reseller,
            'total_active' => $total_active,
            'total_expired' => $total_expired,
            'total_income' => $total_income,
            'soon' => $soon,
            'today' => $today,
            'pending_reseller' => $pending_reseller,
            'due_reminders' => $due_reminders,
            'exp7' => $exp7,
            'exp3' => $exp3,
            'exp1' => $exp1,
            'revenue_monthly' => $revenue_monthly,
        ]);
    }
}
