<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Utils {
    /** Generate UUID (CHAR(36)) */
    private function wrpm_uuid() {
        return function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function wrpm_default_settings() {
        return [
            'reminder_offsets' => [7,3,1],
            'cron_time' => '08:00', // HH:MM site time
            'email_subject' => '[Reminder] {product_label} akan expired',
            'email_template' => "Halo {customer_name},\n\nProduk: {product_label}\nMulai: {start_date}\nDurasi: {duration_days} hari\nExpired: {expires_at}\nHarga: {price}\nSisa: {remaining_days} hari\n\nSilakan lakukan perpanjangan sebelum expired.\n",
            'telegram_template' => "Halo {customer_name}, produk {product_label} akan expired pada {expires_at} (sisa {remaining_days} hari).",
            'whatsapp_template' => "Halo {customer_name}, produk {product_label} akan expired pada {expires_at} (sisa {remaining_days} hari). Silakan perpanjang ya.",

            // Milestone Specific WhatsApp Templates
            'whatsapp_template_h7' => "Halo {customer_name}, layanan {product_label} akan berakhir dalam 7 hari ({expires_at}). Silakan melakukan perpanjangan ya.",
            'whatsapp_template_h3' => "Halo {customer_name}, layanan {product_label} akan berakhir dalam 3 hari ({expires_at}). Segera lakukan pembayaran agar layanan tidak terputus.",
            'whatsapp_template_h1' => "PENTING: Halo {customer_name}, layanan {product_label} akan berakhir BESOK ({expires_at}). Segera lakukan perpanjangan hari ini.",

            'smtp_enabled' => 0,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_secure' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => '',

            'telegram_enabled' => 0,
            'telegram_bot_token' => '',
            'telegram_default_chat_id' => '',

            // WAHA WhatsApp
            'waha_enabled' => 0,
            'waha_api_url' => '',
            'waha_api_token' => '',
            'waha_session_name' => 'default',

            // PDF Branding
            'pdf_invoice_title' => 'Invoice',
            'pdf_logo_url' => '',
            'pdf_company_name' => '',
            'pdf_company_address' => '',
            'pdf_company_phone' => '',
            'pdf_payment_details' => '',
            'pdf_primary_color' => '#1e293b', // Slate hex

            // GitHub Auto Updater
            'github_repo' => '',
            'github_token' => '',

            // WooCommerce
            'wc_sync_enabled' => 0,
        ];
    }

    private function wrpm_money_idr($amount) {
        $amount = (float)$amount;
        return 'Rp ' . number_format_i18n($amount, 0);
    }

    private function wrpm_today_date() {
        return wp_date('Y-m-d', current_time('timestamp'));
    }

    private function wrpm_date_add_days($date_ymd, $days) {
        $ts = strtotime((string)$date_ymd);
        if (!$ts) return '';
        $days = (int)$days;
        return wp_date('Y-m-d', $ts + ($days * DAY_IN_SECONDS));
    }

    private function wrpm_date_diff_days($from_ymd, $to_ymd) {
        $a = strtotime((string)$from_ymd);
        $b = strtotime((string)$to_ymd);
        if (!$a || !$b) return 0;
        return (int)floor(($b - $a) / DAY_IN_SECONDS);
    }

    private function wrpm_get_settings() {
        $opt = get_option(self::OPT_SETTINGS, []);
        $opt = is_array($opt) ? $opt : [];
        // Merge defaults so new settings keys exist on older installs.
        return array_merge($this->wrpm_default_settings(), $opt);
    }

    private function wrpm_admin_url($page, $args = []) {
        $args = array_merge(['page' => $page], (array)$args);
        return add_query_arg($args, admin_url('admin.php'));
    }

    private function wrpm_sanitize_tags($tags) {
        $parts = preg_split('/[\s,;]+/', (string)$tags);
        $clean = [];
        foreach ((array)$parts as $p) {
            $p = strtolower(trim((string)$p));
            if ($p === '') continue;
            $p = preg_replace('/[^a-z0-9_-]/', '', $p);
            if ($p !== '') $clean[] = $p;
        }
        $clean = array_values(array_unique($clean));
        return implode(',', $clean);
    }

    private function wrpm_json_encode($val) {
        return wp_json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function wrpm_json_decode_assoc($json) {
        if (!$json) return [];
        $out = json_decode((string)$json, true);
        return is_array($out) ? $out : [];
    }

    private function wrpm_current_user_id() {
        $u = wp_get_current_user();
        return $u && $u->ID ? (int)$u->ID : 0;
    }

    private function wrpm_current_user_login() {
        $u = wp_get_current_user();
        return $u && $u->user_login ? (string)$u->user_login : 'system';
    }

    private function wrpm_client_ip() {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];
        foreach ($candidates as $k) {
            if (empty($_SERVER[$k])) continue;
            $v = (string)$_SERVER[$k];
            if ($k === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $v);
                $v = trim((string)($parts[0] ?? ''));
            }
            $v = sanitize_text_field($v);
            if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
        }
        return '';
    }

    /**
     * Upload multiple images via WordPress media handling.
     * Returns array of attachment IDs.
     *
     * Expected input: <input type="file" name="..." multiple accept="image/*">
     */
    private function wrpm_handle_uploaded_images($input_name) {
        if (empty($_FILES[$input_name]) || empty($_FILES[$input_name]["name"])) return [];
        if (!function_exists("media_handle_upload")) {
            require_once ABSPATH . "wp-admin/includes/file.php";
            require_once ABSPATH . "wp-admin/includes/media.php";
            require_once ABSPATH . "wp-admin/includes/image.php";
        }

        $ids = [];

        $names = $_FILES[$input_name]["name"];
        $is_multi = is_array($names);
        $files = $_FILES[$input_name];
        $count = $is_multi ? count($files["name"]) : 1;

        for ($i = 0; $i < $count; $i++) {
            if ($is_multi) {
                $_FILES["wrpm_single_upload"] = [
                    "name" => $files["name"][$i],
                    "type" => $files["type"][$i],
                    "tmp_name" => $files["tmp_name"][$i],
                    "error" => $files["error"][$i],
                    "size" => $files["size"][$i],
                ];
                $attach_id = media_handle_upload("wrpm_single_upload", 0);
            } else {
                $attach_id = media_handle_upload($input_name, 0);
            }

            if (!is_wp_error($attach_id) && $attach_id) {
                $ids[] = (int)$attach_id;
            }
        }

        if (isset($_FILES["wrpm_single_upload"])) unset($_FILES["wrpm_single_upload"]);
        return array_values(array_unique(array_filter($ids)));
    }

    /** Build a human-readable contact line from a seller/customer row */
    private function wrpm_build_contact_line($row) {
        $row = is_array($row) ? $row : [];
        $parts = [];
        if (!empty($row['phone'])) $parts[] = 'Telp: ' . (string)$row['phone'];
        if (!empty($row['whatsapp'])) $parts[] = 'WA: ' . (string)$row['whatsapp'];
        if (!empty($row['telegram'])) $parts[] = 'TG: ' . (string)$row['telegram'];
        if (!empty($row['email'])) $parts[] = 'Email: ' . (string)$row['email'];
        return implode(' | ', $parts);
    }
}
