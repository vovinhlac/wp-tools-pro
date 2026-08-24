<?php
namespace LacVo\WPToolsPro\Modules;

use LacVo\WPToolsPro\Core\{ModuleInterface,Settings};

if (!defined('ABSPATH')) { exit; }

final class PerformanceModule implements ModuleInterface {
    public function id(): string { return 'performance'; }

    public function boot(): void {
        if (Settings::get('disable_emojis', 1)) {
            remove_action('wp_head','print_emoji_detection_script',7);
            remove_action('wp_print_styles','print_emoji_styles');
            remove_action('admin_print_scripts','print_emoji_detection_script');
            remove_action('admin_print_styles','print_emoji_styles');
            remove_filter('the_content_feed','wp_staticize_emoji');
            remove_filter('comment_text_rss','wp_staticize_emoji');
            remove_filter('wp_mail','wp_staticize_emoji_for_email');
            add_filter('emoji_svg_url','__return_false');
        }
        if (Settings::get('disable_embeds', 0)) {
            remove_action('wp_head','wp_oembed_add_discovery_links');
            remove_action('wp_head','wp_oembed_add_host_js');
        }
        if (Settings::get('guest_dashicons', 0)) { add_action('wp_enqueue_scripts', [$this, 'guestAssets'], 100); }
    }

    public function guestAssets(): void {
        if (!is_user_logged_in()) { wp_dequeue_style('dashicons'); }
    }
}
