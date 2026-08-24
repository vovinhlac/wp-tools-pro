<?php
namespace LacVo\WPToolsPro\Modules;

use LacVo\WPToolsPro\Core\ModuleInterface;

if (!defined('ABSPATH')) { exit; }

final class SeoModule implements ModuleInterface {
    public function id(): string { return 'seo'; }

    public function boot(): void {
        add_action('template_redirect', [$this, 'attachmentRedirect']);
    }

    public function attachmentRedirect(): void {
        if (!is_attachment()) { return; }
        global $post;
        $url = $post && $post->post_parent ? get_permalink($post->post_parent) : home_url('/');
        wp_safe_redirect($url, 301);
        exit;
    }
}
