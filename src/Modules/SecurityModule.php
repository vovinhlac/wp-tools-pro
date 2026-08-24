<?php
namespace LacVo\WPToolsPro\Modules;

use LacVo\WPToolsPro\Core\{ModuleInterface,Settings};

if (!defined('ABSPATH')) { exit; }

final class SecurityModule implements ModuleInterface {
    public function id(): string { return 'security'; }

    public function boot(): void {
        if (Settings::get('hide_wp_version', 1)) {
            remove_action('wp_head','wp_generator');
            add_filter('the_generator','__return_empty_string');
        }
        if (Settings::get('disable_xmlrpc', 0)) { add_filter('xmlrpc_enabled','__return_false'); }
        if (Settings::get('security_headers', 1)) { add_filter('wp_headers', [$this, 'headers']); }
        if (Settings::get('block_author_enum', 0)) { add_action('template_redirect', [$this, 'blockAuthorEnumeration'], 1); }
    }

    public function headers(array $headers): array {
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        $headers['Permissions-Policy'] = 'camera=(), microphone=(), geolocation=()';
        return $headers;
    }

    public function blockAuthorEnumeration(): void {
        if (is_admin() || is_user_logged_in()) { return; }
        if (isset($_GET['author']) && ctype_digit((string) $_GET['author'])) {
            wp_safe_redirect(home_url('/'), 302);
            exit;
        }
    }
}
