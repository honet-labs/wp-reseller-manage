<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_API {
    public function register_rest_routes() {
                register_rest_route('wrpm/v1', '/prices', [
            'methods' => 'GET',
            'permission_callback' => function() { return current_user_can(self::CAP_MANAGE); },
            'callback' => function($req) {
                global $wpdb;
                $limit = max(1, min(500, (int)$req->get_param('limit')));
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->tbl_prices()} ORDER BY updated_at DESC LIMIT %d",
                    $limit
                ), ARRAY_A);
                return rest_ensure_response(['items' => $rows]);
            }
        ]);

        register_rest_route('wrpm/v1', '/customers', [
            'methods' => 'GET',
            'permission_callback' => function() { return current_user_can(self::CAP_MANAGE); },
            'callback' => function($req) {
                global $wpdb;
                $limit = max(1, min(500, (int)$req->get_param('limit')));
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->tbl_customers()} ORDER BY updated_at DESC LIMIT %d",
                    $limit
                ), ARRAY_A);
                return rest_ensure_response(['items' => $rows]);
            }
        ]);

register_rest_route('wrpm/v1', '/active-products', [
            'methods' => 'GET',
            'permission_callback' => function() { return current_user_can(self::CAP_MANAGE); },
            'callback' => function($req) {
                global $wpdb;
                $limit = max(1, min(200, (int)$req->get_param('limit')));
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->tbl_active()} ORDER BY created_at DESC LIMIT %d",
                    $limit
                ), ARRAY_A);
                return rest_ensure_response(['items' => $rows]);
            }
        ]);

        register_rest_route('wrpm/v1', '/active-products/(?P<id>[a-f0-9\\-]{10,40})/extend', [
            'methods' => 'POST',
            'permission_callback' => function() { return current_user_can(self::CAP_MANAGE); },
            'callback' => function($req) {
                $id = sanitize_text_field($req['id']);
                $days = (int)$req->get_param('days');
                if ($days <= 0) return new WP_Error('invalid_days', 'days must be > 0', ['status' => 400]);
                $ok = $this->wrpm_extend_active_product($id, $days);
                if (!$ok) return new WP_Error('extend_failed', 'Failed to extend', ['status' => 500]);
                return rest_ensure_response(['ok' => true]);
            }
        ]);
    }
}
