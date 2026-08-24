<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class Plugin {
    private static ?self $instance = null;
    private ModuleRegistry $registry;

    public static function instance(): self { return self::$instance ??= new self(); }

    public static function activate(bool $networkWide = false): void {
        unset($networkWide);
        if (!get_option('lacvo_wtp_modules')) {
            add_option('lacvo_wtp_modules', [
                'security' => 1,
                'performance' => 1,
                'seo' => 1,
                'redirects' => 1,
            ], '', false);
        }
        if (!get_option('lacvo_wtp_settings')) { add_option('lacvo_wtp_settings', [], '', false); }
        if (!get_option('lacvo_wtp_advanced')) { add_option('lacvo_wtp_advanced', [], '', false); }
        Database::install();
        update_option('lacvo_wtp_version', LACVO_WTP_VERSION, false);
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('lacvo_wtp_queue_cron');
        wp_clear_scheduled_hook('lacvo_wtp_queue_recovery');
    }

    public function boot(): void {
        load_plugin_textdomain('wp-tools-pro', false, dirname(LACVO_WTP_BASENAME).'/languages');
        QueueManager::boot();
        $this->registry = new ModuleRegistry();
        $this->registry->registerDefaults();
        $this->registry->bootEnabled();
    }
}
