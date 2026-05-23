<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Admin_Core {
    private function render_template($rel_path, $vars = []) {
        $base = trailingslashit(dirname(__FILE__, 3)) . 'templates/';
        $path = $base . ltrim($rel_path, '/');
        if (!file_exists($path)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Template missing.', self::TEXT_DOMAIN) . ' ' . esc_html($rel_path) . '</p></div>';
            return;
        }
        if (!empty($vars)) extract($vars, EXTR_SKIP);
        include $path;
    }

    private function page_header_html($title, $shortcode = '', $alt = '', $info_html = '') {
        $btns = '';
        if (!empty($info_html)) {
            $btns .= '<div class="fl-head-actions wrpm-info-actions">';
            $btns .= '<button type="button" class="fl-kebab wrpm-info-btn" aria-label="Info">';
            $btns .= '<span class="dashicons dashicons-info-outline"></span>';
            $btns .= '</button>';
            $btns .= '<div class="wrpm-page-info" hidden>' . wp_kses_post($info_html) . '</div>';
            $btns .= '</div>';
        }

        if ($shortcode) {
            $btns .= '<div class="fl-head-actions fl-sc-actions">';
            $btns .= '<button type="button" class="fl-kebab" aria-label="Shortcode actions"><span class="fl-kebab-dots">⋮</span></button>';
            $btns .= '<div class="fl-menu" hidden>';
            $btns .= '<button type="button" class="fl-menu-item fl-copy-shortcode" data-shortcode="' . esc_attr($shortcode) . '">Copy shortcode</button>';
            if ($alt) {
                $btns .= '<button type="button" class="fl-menu-item fl-copy-shortcode" data-shortcode="' . esc_attr($alt) . '">Copy alternative</button>';
            }
            $btns .= '</div></div>';
        }

        $actions = $btns ? '<div class="fl-page-actions">' . $btns . '</div>' : '';
        return '<div class="fl-page-head"><h1>' . esc_html($title) . '</h1>' . $actions . '</div>';
    }

    private function admin_notice_from_query() {
        if (empty($_GET['wrpm_msg'])) return;
        $msg = sanitize_text_field(wp_unslash($_GET['wrpm_msg']));
        $type = !empty($_GET['wrpm_type']) ? sanitize_text_field(wp_unslash($_GET['wrpm_type'])) : 'success';
        $cls = ($type === 'error') ? 'notice notice-error' : 'notice notice-success';
        echo '<div class="' . esc_attr($cls) . '"><p>' . esc_html($msg) . '</p></div>';
    }
}
