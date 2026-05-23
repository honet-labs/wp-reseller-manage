<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Reports {
    public function page_reports() {
        if (!current_user_can(self::CAP_VIEW_REPORTS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        global $wpdb;

        // Revenue by month (last 12 months) - ONLY paid active products.
        $rows = $wpdb->get_results(
            "SELECT DATE_FORMAT(start_date,'%Y-%m') ym, COALESCE(SUM(price),0) total
             FROM {$this->tbl_active()}
             WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               AND payment_status = 'paid'
             GROUP BY ym
             ORDER BY ym ASC",
            ARRAY_A
        );

        // Build last 12 months series (fill missing months with 0).
        $map = [];
        foreach ((array)$rows as $r) {
            $map[(string)$r['ym']] = (float)($r['total'] ?? 0);
        }
        $months = [];
        $totals = [];
        $month_options = [];
        $cur = wp_date('Y-m-01');
        for ($i = 11; $i >= 0; $i--) {
            $ym = wp_date('Y-m', strtotime($cur . ' -' . $i . ' months'));
            $months[] = $ym;
            $totals[] = isset($map[$ym]) ? (float)$map[$ym] : 0.0;
        }
        // Options (latest first)
        for ($i = 0; $i < 12; $i++) {
            $ym = wp_date('Y-m', strtotime($cur . ' -' . $i . ' months'));
            $month_options[] = $ym;
        }

        $counts = $wpdb->get_results(
            "SELECT status, COUNT(*) c FROM {$this->tbl_active()} GROUP BY status",
            ARRAY_A
        );
        $count_map = ['active' => 0, 'expired' => 0];
        foreach ((array)$counts as $c) {
            $st = (string)($c['status'] ?? '');
            if (isset($count_map[$st])) $count_map[$st] = (int)($c['c'] ?? 0);
        }

        $this->render_template('admin/reports.php', [
            'months' => $months,
            'totals' => $totals,
            'count_map' => $count_map,
            'month_options' => $month_options,
        ]);
    }

    public function handle_admin_post_report_pdf() {
        if (!current_user_can(self::CAP_VIEW_REPORTS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        check_admin_referer('wrpm_report_pdf');

        $ym = isset($_POST['ym']) ? sanitize_text_field(wp_unslash($_POST['ym'])) : '';
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            wp_safe_redirect($this->wrpm_admin_url('wrpm-reports', [
                'wrpm_msg' => 'Bulan tidak valid.',
                'wrpm_type' => 'error',
            ]));
            exit;
        }

        $this->wrpm_output_monthly_report_pdf($ym);
    }
}
