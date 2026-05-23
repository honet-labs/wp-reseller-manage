<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Logs {
    private function wrpm_log($action, $entity = '', $entity_id = '', $message = '', $meta = []) {
        global $wpdb;
        $table = $this->tbl_logs();
        $wpdb->insert($table, [
            'happened_at' => current_time('mysql'),
            'user_id' => $this->wrpm_current_user_id(),
            'user_login' => $this->wrpm_current_user_login(),
            'action' => sanitize_text_field($action),
            'entity' => sanitize_text_field($entity),
            'entity_id' => sanitize_text_field($entity_id),
            'message' => sanitize_text_field($message),
            'meta' => $meta ? $this->wrpm_json_encode($meta) : null,
            'ip' => $this->wrpm_client_ip(),
        ], [
            '%s','%d','%s','%s','%s','%s','%s','%s','%s'
        ]);
    }

    private function wrpm_get_logs($limit = 200, $offset = 0, $filters = []) {
        global $wpdb;
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);

        $where = '1=1';
        $params = [];

        if (!empty($filters['entity'])) {
            $where .= ' AND entity = %s';
            $params[] = (string)$filters['entity'];
        }
        if (!empty($filters['action'])) {
            $where .= ' AND action = %s';
            $params[] = (string)$filters['action'];
        }
        if (!empty($filters['q'])) {
            $q = '%' . $wpdb->esc_like((string)$filters['q']) . '%';
            $where .= ' AND (message LIKE %s OR meta LIKE %s OR user_login LIKE %s)';
            $params[] = $q; $params[] = $q; $params[] = $q;
        }

        $sql = "SELECT * FROM {$this->tbl_logs()} WHERE {$where} ORDER BY happened_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }
}
