<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_DB {
    private function tbl_prices()   { global $wpdb; return $wpdb->prefix . 'wrpm_product_prices'; }
    private function tbl_reseller() { global $wpdb; return $wpdb->prefix . 'wrpm_reseller_products'; }
    private function tbl_customers(){ global $wpdb; return $wpdb->prefix . 'wrpm_customers'; }
    private function tbl_sellers()  { global $wpdb; return $wpdb->prefix . 'wrpm_sellers'; }
    private function tbl_active()   { global $wpdb; return $wpdb->prefix . 'wrpm_active_products'; }
    private function tbl_reminders(){ global $wpdb; return $wpdb->prefix . 'wrpm_active_reminders'; }
    private function tbl_logs()     { global $wpdb; return $wpdb->prefix . 'wrpm_logs'; }

    public function install_schema() {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $prices = $this->tbl_prices();
        $reseller = $this->tbl_reseller();
        $customers = $this->tbl_customers();
        $sellers = $this->tbl_sellers();
        $active = $this->tbl_active();
        $reminders = $this->tbl_reminders();
        $logs = $this->tbl_logs();

        $sql_prices = "CREATE TABLE {$prices} (
            id CHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT '',
            tags TEXT NULL,
            seller_id CHAR(36) NULL,
            reseller_price BIGINT(20) NOT NULL DEFAULT 0,
            sale_price BIGINT(20) NOT NULL DEFAULT 0,
            duration_days INT(11) NOT NULL DEFAULT 0,
            description LONGTEXT NULL,
            notes LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by BIGINT(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY name (name(80)),
            KEY category (category(40)),
            KEY seller_id (seller_id)
        ) {$charset};";

        $sql_reseller = "CREATE TABLE {$reseller} (
            id CHAR(36) NOT NULL,
            price_id CHAR(36) NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT '',
            tags TEXT NULL,
            seller_id CHAR(36) NULL,
            reseller_name VARCHAR(200) NOT NULL DEFAULT '',
            reseller_contact VARCHAR(200) NOT NULL DEFAULT '',
            purchase_date DATE NULL,
            duration_days INT(11) NOT NULL DEFAULT 0,
            description LONGTEXT NULL,
            price BIGINT(20) NOT NULL DEFAULT 0,
            expires_at DATE NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_attachments LONGTEXT NULL,
            notes LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by BIGINT(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY price_id (price_id),
            KEY seller_id (seller_id),
            KEY expires_at (expires_at),
            KEY payment_status (payment_status)
        ) {$charset};";

        $sql_customers = "CREATE TABLE {$customers} (
            id CHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(50) NOT NULL DEFAULT '',
            telegram VARCHAR(100) NOT NULL DEFAULT '',
            whatsapp VARCHAR(50) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by BIGINT(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY email (email(80)),
            KEY name (name(80))
        ) {$charset};";

        $sql_sellers = "CREATE TABLE {$sellers} (
            id CHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(50) NOT NULL DEFAULT '',
            telegram VARCHAR(100) NOT NULL DEFAULT '',
            whatsapp VARCHAR(50) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by BIGINT(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY email (email(80)),
            KEY name (name(80))
        ) {$charset};";

        $sql_active = "CREATE TABLE {$active} (
            id CHAR(36) NOT NULL,
            reseller_product_id CHAR(36) NOT NULL,
            product_label VARCHAR(255) NOT NULL,
            customer_id CHAR(36) NOT NULL,
            customer_name VARCHAR(200) NOT NULL,
            customer_contact VARCHAR(200) NOT NULL DEFAULT '',
            start_date DATE NOT NULL,
            duration_days INT(11) NOT NULL DEFAULT 0,
            expires_at DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            price BIGINT(20) NOT NULL DEFAULT 0,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_attachments LONGTEXT NULL,
            notes LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by BIGINT(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY expires_at (expires_at),
            KEY status (status),
            KEY customer_id (customer_id),
            KEY reseller_product_id (reseller_product_id)
        ) {$charset};";

        $sql_reminders = "CREATE TABLE {$reminders} (
            id CHAR(36) NOT NULL,
            active_product_id CHAR(36) NOT NULL,
            customer_id CHAR(36) NOT NULL,
            offset_days INT(11) NOT NULL DEFAULT 0,
            reminder_date DATE NOT NULL,
            remaining_days INT(11) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            sent_via VARCHAR(50) NOT NULL DEFAULT '',
            sent_at DATETIME NULL,
            last_error TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY active_product_id (active_product_id),
            KEY reminder_date (reminder_date),
            KEY status (status)
        ) {$charset};";

        $sql_logs = "CREATE TABLE {$logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            happened_at DATETIME NOT NULL,
            user_id BIGINT(20) NOT NULL DEFAULT 0,
            user_login VARCHAR(60) NOT NULL DEFAULT '',
            action VARCHAR(120) NOT NULL DEFAULT '',
            entity VARCHAR(60) NOT NULL DEFAULT '',
            entity_id CHAR(36) NOT NULL DEFAULT '',
            message TEXT NULL,
            meta LONGTEXT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY happened_at (happened_at),
            KEY action (action(60)),
            KEY entity (entity(40)),
            KEY entity_id (entity_id)
        ) {$charset};";

        dbDelta($sql_prices);
        dbDelta($sql_reseller);
        dbDelta($sql_customers);
        dbDelta($sql_sellers);
        dbDelta($sql_active);
        dbDelta($sql_reminders);
        dbDelta($sql_logs);

        // Defaults
        $settings = get_option(self::OPT_SETTINGS, null);
        if (!is_array($settings)) {
            update_option(self::OPT_SETTINGS, $this->wrpm_default_settings(), false);
        }

        // Cron schedule
        $this->ensure_cron();

        // Caps
        $this->ensure_role_caps();
    }

    public function uninstall_schema() {
        global $wpdb;
        $tables = [
            $this->tbl_prices(),
            $this->tbl_reseller(),
            $this->tbl_customers(),
            $this->tbl_sellers(),
            $this->tbl_active(),
            $this->tbl_reminders(),
            $this->tbl_logs(),
        ];
        foreach ($tables as $t) {
            $wpdb->query("DROP TABLE IF EXISTS {$t}");
        }
        delete_option(self::OPT_SETTINGS);
    }

    public function ensure_role_caps() {
        $admin = get_role('administrator');
        if (!$admin) return;

        // Create plugin roles (optional). Admins can still manage via capabilities.
        if (!get_role('wrpm_manager')) {
            add_role('wrpm_manager', 'WRPM Manager', [
                self::CAP_MANAGE => true,
                self::CAP_VIEW_REPORTS => true,
                self::CAP_VIEW_LOGS => true,
            ]);
        }
        if (!get_role('wrpm_viewer')) {
            add_role('wrpm_viewer', 'WRPM Viewer', [
                self::CAP_VIEW_REPORTS => true,
                self::CAP_VIEW_LOGS => true,
            ]);
        }

        foreach ([
            self::CAP_MANAGE => true,
            self::CAP_VIEW_REPORTS => true,
            self::CAP_MANAGE_SETTINGS => true,
            self::CAP_VIEW_LOGS => true,
        ] as $cap => $grant) {
            if ($grant) $admin->add_cap($cap);
        }
    }

    public function ensure_cron() {
        if (!wp_next_scheduled('wrpm_daily_cron')) {
            $s = $this->wrpm_get_settings();
            $cron_time = (string)($s['cron_time'] ?? '08:00'); // HH:MM
            if (!preg_match('/^\d{2}:\d{2}$/', $cron_time)) $cron_time = '08:00';

            $ts = strtotime('today ' . $cron_time, current_time('timestamp'));
            if ($ts <= current_time('timestamp')) $ts = strtotime('tomorrow ' . $cron_time, current_time('timestamp'));
            wp_schedule_event($ts, 'daily', 'wrpm_daily_cron');
        }
    }

    public function clear_cron() {
        $ts = wp_next_scheduled('wrpm_daily_cron');
        if ($ts) wp_unschedule_event($ts, 'wrpm_daily_cron');
    }
}
