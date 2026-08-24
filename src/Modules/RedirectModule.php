<?php
namespace LacVo\WPToolsPro\Modules;

use LacVo\WPToolsPro\Core\{Database,ModuleInterface,Settings};

if (!defined('ABSPATH')) { exit; }

final class RedirectModule implements ModuleInterface {
    public function id(): string { return 'redirects'; }

    public function boot(): void {
        add_action('template_redirect', [$this, 'redirect'], 1);
        add_action('template_redirect', [$this, 'log404'], 999);
    }

    public function redirect(): void {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) { return; }
        global $wpdb;
        $path = $this->requestPath();
        if ($path === '') { return; }

        $table = Database::table('redirects');
        $row = $wpdb->get_row($wpdb->prepare("SELECT id,source,target,status,match_type FROM {$table} WHERE source=%s AND match_type='exact' AND enabled=1 LIMIT 1", $path));
        if (!$row && Settings::get('redirect_regex_enabled', 0)) {
            $rules = $wpdb->get_results("SELECT id,source,target,status,match_type FROM {$table} WHERE match_type='regex' AND enabled=1 ORDER BY rule_order ASC,id ASC LIMIT 250");
            foreach ($rules as $rule) {
                $pattern = (string) $rule->source;
                if (!$this->validRegex($pattern)) { continue; }
                if (@preg_match($pattern, $path) === 1) { $row = $rule; break; }
            }
        }
        if (!$row) { return; }

        $target = (string) $row->target;
        if (($row->match_type ?? 'exact') === 'regex') {
            $target = (string) @preg_replace((string) $row->source, $target, $path);
        }
        if (str_starts_with($target, '/')) { $target = home_url($target); }
        if (!$this->safeTarget($target) || $this->isLoop($path, $target)) { return; }

        $wpdb->query($wpdb->prepare("UPDATE {$table} SET hits=hits+1,updated_at=%s WHERE id=%d", current_time('mysql'), (int) $row->id));
        wp_redirect($target, in_array((int) $row->status, [301,302,307,308], true) ? (int) $row->status : 301, 'WP Tools Pro');
        exit;
    }

    public function log404(): void {
        if (!Settings::get('redirect_log_404', 1) || !is_404() || (is_user_logged_in() && current_user_can('manage_options'))) { return; }
        global $wpdb;
        $path = $this->requestPath();
        if ($path === '' || strlen($path) > 1000) { return; }
        $table = Database::table('404');
        $now = current_time('mysql');
        $ref = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (path,referrer,user_agent,ip_hash,hits,first_seen,last_seen) VALUES (%s,%s,%s,%s,1,%s,%s) ON DUPLICATE KEY UPDATE hits=hits+1,last_seen=VALUES(last_seen),referrer=VALUES(referrer)",
            $path,
            substr($ref,0,1000),
            substr($ua,0,500),
            Database::hash($ip),
            $now,
            $now
        );
        $wpdb->query($sql);
    }

    private function requestPath(): string {
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) { return ''; }
        $path = '/' . ltrim(rawurldecode($path), '/');
        return $path === '//' ? '/' : (untrailingslashit($path) ?: '/');
    }

    private function validRegex(string $pattern): bool { return strlen($pattern) < 500 && @preg_match($pattern, '') !== false; }
    private function safeTarget(string $target): bool { return str_starts_with($target, home_url()) || (bool) wp_http_validate_url($target); }

    private function isLoop(string $path, string $target): bool {
        $targetPath = wp_parse_url($target, PHP_URL_PATH);
        if (!is_string($targetPath)) { return false; }
        return untrailingslashit('/'.ltrim(rawurldecode($targetPath),'/')) === untrailingslashit($path);
    }
}
