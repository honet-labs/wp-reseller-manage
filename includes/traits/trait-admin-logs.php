<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Logs {
    public function page_logs() {
        if (!current_user_can(self::CAP_VIEW_LOGS)) wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));

        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $entity = isset($_GET['entity']) ? sanitize_text_field(wp_unslash($_GET['entity'])) : '';
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

        $rows = $this->wrpm_get_logs(300, 0, [
            'q' => $q,
            'entity' => $entity,
            'action' => $action,
        ]);

        $this->render_template('admin/logs.php', [
            'rows' => $rows,
            'q' => $q,
            'entity' => $entity,
            'action' => $action,
        ]);
    }
}
