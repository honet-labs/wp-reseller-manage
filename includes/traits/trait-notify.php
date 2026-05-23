<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Notify {
    public function maybe_apply_smtp($phpmailer) {
        $s = $this->wrpm_get_settings();
        if (empty($s['smtp_enabled'])) return;

        $host = (string)($s['smtp_host'] ?? '');
        $user = (string)($s['smtp_user'] ?? '');
        $pass = (string)($s['smtp_pass'] ?? '');
        $port = (int)($s['smtp_port'] ?? 587);
        $secure = (string)($s['smtp_secure'] ?? 'tls');

        if (!$host) return;

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = $port > 0 ? $port : 587;
        $phpmailer->Username = $user;
        $phpmailer->Password = $pass;
        if ($secure === 'ssl' || $secure === 'tls') {
            $phpmailer->SMTPSecure = $secure;
        }

        $from_email = (string)($s['smtp_from_email'] ?? '');
        $from_name = (string)($s['smtp_from_name'] ?? '');
        if ($from_email) {
            $phpmailer->setFrom($from_email, $from_name ?: get_bloginfo('name'));
        }
    }

    private function wrpm_render_template($tpl, $vars) {
        $out = (string)$tpl;
        foreach ((array)$vars as $k => $v) {
            $out = str_replace('{' . $k . '}', (string)$v, $out);
        }
        return $out;
    }

    private function wrpm_send_email($to, $subject_tpl, $body_tpl, $vars) {
        $to = sanitize_email((string)$to);
        if (!$to || !is_email($to)) return ['ok' => false, 'error' => 'Invalid email'];

        $subject = $this->wrpm_render_template($subject_tpl, $vars);
        $body = $this->wrpm_render_template($body_tpl, $vars);

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
        $ok = wp_mail($to, $subject, $body, $headers);
        return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'wp_mail failed'];
    }

    private function wrpm_send_telegram($chat_id, $message) {
        $s = $this->wrpm_get_settings();
        if (empty($s['telegram_enabled'])) return ['ok' => false, 'error' => 'Telegram disabled'];
        $token = (string)($s['telegram_bot_token'] ?? '');
        if (!$token) return ['ok' => false, 'error' => 'Missing bot token'];

        $chat_id = (string)$chat_id;
        if (!$chat_id) {
            $chat_id = (string)($s['telegram_default_chat_id'] ?? '');
        }
        if (!$chat_id) return ['ok' => false, 'error' => 'Missing chat id'];

        $url = 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage';
        $resp = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $this->wrpm_json_encode([
                'chat_id' => $chat_id,
                'text' => (string)$message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]),
        ]);

        if (is_wp_error($resp)) return ['ok' => false, 'error' => $resp->get_error_message()];
        $code = (int)wp_remote_retrieve_response_code($resp);
        $body = (string)wp_remote_retrieve_body($resp);

        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'HTTP ' . $code . ' ' . $body];
        }
        return ['ok' => true];
    }

    private function wrpm_send_waha($to, $message) {
        $s = $this->wrpm_get_settings();
        if (empty($s['waha_enabled'])) return ['ok' => false, 'error' => 'WAHA disabled'];
        $api_url = (string)($s['waha_api_url'] ?? '');
        if (!$api_url) return ['ok' => false, 'error' => 'Missing WAHA API URL'];
        $token = (string)($s['waha_api_token'] ?? '');
        $session = (string)($s['waha_session_name'] ?? 'default');

        // Normalize destination number (e.g. remove spaces, dashes, +, etc)
        $to = preg_replace('/[^0-9]/', '', $to);
        if (!$to) return ['ok' => false, 'error' => 'Invalid destination number'];

        // Append @c.us if not present
        if (strpos($to, '@') === false) {
            $to .= '@c.us';
        }

        $url = rtrim($api_url, '/') . '/api/sendText';
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $resp = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => $headers,
            'body' => $this->wrpm_json_encode([
                'session' => $session,
                'chatId' => $to,
                'text' => (string)$message,
            ]),
        ]);

        if (is_wp_error($resp)) return ['ok' => false, 'error' => $resp->get_error_message()];
        $code = (int)wp_remote_retrieve_response_code($resp);
        $body = (string)wp_remote_retrieve_body($resp);

        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'HTTP ' . $code . ' ' . $body];
        }
        return ['ok' => true];
    }
}
