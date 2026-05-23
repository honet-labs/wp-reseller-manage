<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_Updater {
    public function init_github_updater() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'github_updater_check']);
        add_filter('plugins_api', [$this, 'github_updater_details'], 20, 3);
    }

    public function github_updater_check($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $s = $this->wrpm_get_settings();
        $repo = trim((string)($s['github_repo'] ?? ''));
        if (!$repo) return $transient;

        $token = trim((string)($s['github_token'] ?? ''));

        // Query GitHub API
        $url = 'https://api.github.com/repos/' . $repo . '/releases/latest';
        $args = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WP-Reseller-Product-Manager-Updater',
            ]
        ];
        if ($token) {
            $args['headers']['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) return $transient;

        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body, true);

        if (empty($release) || empty($release['tag_name'])) return $transient;

        $new_version = ltrim($release['tag_name'], 'v');
        $current_version = self::VERSION;

        if (version_compare($new_version, $current_version, '>')) {
            $plugin_slug = plugin_basename(WRPM_PLUGIN_DIR . 'wp-reseller-manage.php');
            
            // Find release zip asset URL
            $zip_url = '';
            if (!empty($release['zipball_url'])) {
                $zip_url = $release['zipball_url'];
            }
            if (!empty($release['assets']) && is_array($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (strpos($asset['name'], '.zip') !== false) {
                        $zip_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }

            if ($zip_url) {
                if ($token) {
                    $zip_url = add_query_arg('access_token', $token, $zip_url);
                }

                $obj = new stdClass();
                $obj->slug = 'wp-reseller-product-manager';
                $obj->plugin = $plugin_slug;
                $obj->new_version = $new_version;
                $obj->url = 'https://github.com/' . $repo;
                $obj->package = $zip_url;

                $transient->response[$plugin_slug] = $obj;
            }
        }

        return $transient;
    }

    public function github_updater_details($res, $action, $args) {
        if ($action !== 'plugin_information') return $res;
        if (empty($args->slug) || $args->slug !== 'wp-reseller-product-manager') return $res;

        $s = $this->wrpm_get_settings();
        $repo = trim((string)($s['github_repo'] ?? ''));
        if (!$repo) return $res;

        $token = trim((string)($s['github_token'] ?? ''));

        $url = 'https://api.github.com/repos/' . $repo . '/releases/latest';
        $api_args = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WP-Reseller-Product-Manager-Updater',
            ]
        ];
        if ($token) {
            $api_args['headers']['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get($url, $api_args);
        if (is_wp_error($response)) return $res;

        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($release)) return $res;

        $new_version = ltrim($release['tag_name'], 'v');

        $res = new stdClass();
        $res->name = self::PLUGIN_SHORT_NAME;
        $res->slug = 'wp-reseller-product-manager';
        $res->version = $new_version;
        $res->author = 'Antigravity AI';
        $res->homepage = 'https://github.com/' . $repo;
        $res->sections = [
            'description' => '<h3>WP Reseller Manage</h3><p>Plugin untuk manajemen reseller dan product manager.</p>',
            'changelog' => '<h4>' . esc_html($release['tag_name']) . '</h4>' . wp_kses_post(wpautop($release['body'] ?? '')),
        ];

        return $res;
    }
}
