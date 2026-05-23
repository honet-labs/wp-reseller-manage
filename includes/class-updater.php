<?php
if (!defined('ABSPATH')) { exit; }

class WRPM_Updater {
    private $file;
    private $slug;
    private $repo;
    private $token;

    public function __construct($file) {
        $this->file = $file;
        $this->slug = plugin_basename($file);

        $settings = get_option('wrpm_settings_v1', []);
        $this->repo = !empty($settings['github_repo']) ? trim((string)$settings['github_repo']) : '';
        $this->token = !empty($settings['github_token']) ? trim((string)$settings['github_token']) : '';

        if ($this->repo) {
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
            add_filter('upgrader_source_selection', [$this, 'upgrader_source_selection'], 10, 4);
        }
    }

    public function upgrader_source_selection($source, $remote_source, $upgrader, $hook_extra = null) {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $source;
        }

        global $wp_filesystem;
        $plugin_dir = dirname($this->slug);
        $corrected_source = trailingslashit(dirname($source)) . $plugin_dir;

        // Move and rename the GitHub extracted folder to match active plugin folder name
        if ($wp_filesystem && $wp_filesystem->move($source, $corrected_source, true)) {
            return trailingslashit($corrected_source);
        }

        return $source;
    }

    public function check_update($transient) {
        if (empty($transient->checked)) return $transient;

        $remote = $this->get_github_release();
        if (!$remote) return $transient;

        $current = WRPM_App::VERSION;
        $new_ver = ltrim((string)($remote['tag_name'] ?? '0.0.0'), 'v');

        if (version_compare($current, $new_ver, '<')) {
            $obj = new stdClass();
            $obj->slug = 'wp-reseller-product-manager';
            $obj->plugin = $this->slug;
            $obj->new_version = $new_ver;
            $obj->url = 'https://github.com/' . $this->repo;
            $obj->package = $this->get_zip_url($remote);
            $obj->tested = get_bloginfo('version');

            $transient->response[$this->slug] = $obj;
        }

        return $transient;
    }

    public function plugin_info($res, $action, $args) {
        if ($action !== 'plugin_information') return $res;
        if (empty($args->slug) || $args->slug !== 'wp-reseller-product-manager') return $res;

        $remote = $this->get_github_release();
        if (!$remote) return $res;

        $new_ver = ltrim((string)($remote['tag_name'] ?? '0.0.0'), 'v');

        $res = new stdClass();
        $res->name = 'WP Reseller Manage';
        $res->slug = 'wp-reseller-product-manager';
        $res->version = $new_ver;
        $res->author = 'HONET';
        $res->homepage = 'https://github.com/' . $this->repo;
        $res->download_link = $this->get_zip_url($remote);
        $res->sections = [
            'description' => 'Manajemen reseller & notifikasi produk aktif.',
            'changelog'   => $remote['body'] ?? 'Rilis baru tersedia.',
        ];

        return $res;
    }

    private function get_github_release() {
        $url = 'https://api.github.com/repos/' . $this->repo . '/releases/latest';
        $args = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ],
        ];

        if ($this->token) {
            $args['headers']['Authorization'] = 'token ' . $this->token;
        }

        $resp = wp_remote_get($url, $args);
        if (is_wp_error($resp)) return null;

        $body = wp_remote_retrieve_body($resp);
        $data = json_decode($body, true);

        return is_array($data) && !empty($data['tag_name']) ? $data : null;
    }

    private function get_zip_url($release) {
        if ($this->token && !empty($release['assets'][0]['url'])) {
            return $release['assets'][0]['url'];
        }
        return $release['zipball_url'] ?? '';
    }
}
