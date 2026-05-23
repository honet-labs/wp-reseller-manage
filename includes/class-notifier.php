<?php
if (!defined('ABSPATH')) { exit; }

class WRPM_Notifier {
    private $settings;

    public function __construct() {
        $this->settings = $this->get_settings();
        add_action('phpmailer_init', [$this, 'apply_smtp']);
    }

    private function get_settings() {
        $defaults = [
            'reminder_offsets' => [7,3,1],
            'cron_time' => '08:00',
            'email_subject' => '[Reminder] {product_label} akan expired',
            'email_template' => "Halo {customer_name},\n\nLayanan: {product_label}\nExpired: {expires_at}\nHarga: {price}\nSisa Waktu: {remaining_days} hari lagi.\n\nSilakan lakukan perpanjangan layanan segera.",
            'telegram_template' => "Halo {customer_name}, layanan {product_label} akan expired pada {expires_at} (sisa {remaining_days} hari lagi).",
            'whatsapp_template' => "Halo {customer_name}, layanan {product_label} akan expired pada {expires_at} (sisa {remaining_days} hari lagi). Silakan perpanjang ya.",
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
            'waha_enabled' => 0,
            'waha_api_url' => '',
            'waha_api_token' => '',
            'waha_session_name' => 'default',
        ];
        $opt = get_option('wrpm_settings_v1', []);
        return array_merge($defaults, is_array($opt) ? $opt : []);
    }

    public function apply_smtp($phpmailer) {
        if (empty($this->settings['smtp_enabled'])) return;
        $host = trim((string)($this->settings['smtp_host'] ?? ''));
        if (!$host) return;

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = (int)($this->settings['smtp_port'] ?? 587);
        $phpmailer->Username = trim((string)($this->settings['smtp_user'] ?? ''));
        $phpmailer->Password = trim((string)($this->settings['smtp_pass'] ?? ''));
        $secure = trim((string)($this->settings['smtp_secure'] ?? 'tls'));
        if ($secure === 'ssl' || $secure === 'tls') {
            $phpmailer->SMTPSecure = $secure;
        }

        $from_email = trim((string)($this->settings['smtp_from_email'] ?? ''));
        $from_name = trim((string)($this->settings['smtp_from_name'] ?? ''));
        if ($from_email) {
            $phpmailer->setFrom($from_email, $from_name ?: get_bloginfo('name'));
        }
    }

    public function render_template($template, $vars) {
        $out = (string)$template;
        foreach ((array)$vars as $k => $v) {
            $out = str_replace('{' . $k . '}', (string)$v, $out);
        }
        return $out;
    }

    public function send_email($to, $subject_tpl, $body_tpl, $vars) {
        $to = sanitize_email($to);
        if (!$to || !is_email($to)) return ['ok' => false, 'error' => 'Invalid destination email'];

        $subject = $this->render_template($subject_tpl, $vars);
        $body = $this->render_template($body_tpl, $vars);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $ok = wp_mail($to, $subject, $body, $headers);
        return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'wp_mail function returned false'];
    }

    public function send_telegram($chat_id, $message) {
        if (empty($this->settings['telegram_enabled'])) return ['ok' => false, 'error' => 'Telegram notifications are disabled'];
        $token = trim((string)($this->settings['telegram_bot_token'] ?? ''));
        if (!$token) return ['ok' => false, 'error' => 'Bot token is not configured'];

        $chat_id = trim((string)$chat_id);
        if (!$chat_id) {
            $chat_id = trim((string)($this->settings['telegram_default_chat_id'] ?? ''));
        }
        if (!$chat_id) return ['ok' => false, 'error' => 'No target Chat ID provided'];

        $url = 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage';
        $resp = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]),
        ]);

        if (is_wp_error($resp)) return ['ok' => false, 'error' => $resp->get_error_message()];
        $code = (int)wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'HTTP Status Code ' . $code];
        }
        return ['ok' => true];
    }

    public function send_waha($to, $message) {
        if (empty($this->settings['waha_enabled'])) return ['ok' => false, 'error' => 'WAHA WhatsApp Gateway is disabled'];
        $api_url = trim((string)($this->settings['waha_api_url'] ?? ''));
        if (!$api_url) return ['ok' => false, 'error' => 'WAHA API URL is not configured'];

        $to = preg_replace('/[^0-9]/', '', $to);
        if (strpos($to, '0') === 0) {
            $to = '62' . substr($to, 1);
        }
        if (!$to) return ['ok' => false, 'error' => 'Invalid target phone number'];

        if (strpos($to, '@') === false) {
            $to .= '@c.us';
        }

        $url = rtrim($api_url, '/') . '/api/sendText';
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $token = trim((string)($this->settings['waha_api_token'] ?? ''));
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
            $headers['X-Api-Key'] = $token;
        }

        $resp = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => $headers,
            'body' => wp_json_encode([
                'session' => trim((string)($this->settings['waha_session_name'] ?? 'default')),
                'chatId' => $to,
                'text' => $message,
            ]),
        ]);

        if (is_wp_error($resp)) return ['ok' => false, 'error' => $resp->get_error_message()];
        $code = (int)wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'HTTP Status Code ' . $code];
        }
        return ['ok' => true];
    }
}
