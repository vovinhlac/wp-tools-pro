<?php
namespace LacVo\WPToolsPro\Core;

use LacVo\WPToolsPro\Admin\Admin;
use LacVo\WPToolsPro\Admin\AdvancedAdmin;
use LacVo\WPToolsPro\Admin\ProductionAdmin;
use LacVo\WPToolsPro\Admin\ReleaseAdmin;

if (!defined('ABSPATH')) { exit; }

final class Plugin {
    private static ?self $instance = null;
    private ModuleRegistry $registry;

    public static function instance(): self { return self::$instance ??= new self(); }

    public static function activate(bool $networkWide = false): void {
        if (is_multisite() && $networkWide) {
            MultisiteManager::activateNetwork();
            return;
        }
        self::activateSite();
    }

    public static function activateSite(): void {
        if (!get_option('lacvo_wtp_modules')) {
            add_option('lacvo_wtp_modules', [
                'toc'=>1,'duplicate'=>1,'media'=>1,'comments'=>1,'branding'=>0,
                'permissions'=>0,'security'=>1,'performance'=>1,'seo'=>1,'snippets'=>0,
                'consent'=>0,'smtp'=>0,'admin'=>0,'editor'=>0,'developer'=>0,'redirects'=>1,
            ], '', false);
        }
        if (!get_option('lacvo_wtp_settings')) { add_option('lacvo_wtp_settings', [], '', false); }
        if (!get_option('lacvo_wtp_history')) { add_option('lacvo_wtp_history', [], '', false); }
        if (!get_option('lacvo_wtp_advanced')) { add_option('lacvo_wtp_advanced', [], '', false); }
        Database::install();
        update_option('lacvo_wtp_version', LACVO_WTP_VERSION, false);
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('lacvo_wtp_media_queue_tick');
        wp_clear_scheduled_hook('lacvo_wtp_queue_cron');
        wp_clear_scheduled_hook('lacvo_wtp_queue_recovery');
        wp_clear_scheduled_hook('lacvo_wtp_consent_cleanup');
    }

    public function boot(): void {
        UpgradeRecovery::boot();
        $installed = (string) get_option('lacvo_wtp_version', '');
        if ($installed !== LACVO_WTP_VERSION && !UpgradeRecovery::blocksCurrentUpgrade()) {
            $result = MigrationManager::run($installed ?: 'unknown', LACVO_WTP_VERSION, static function (): void {
                Database::install();
            });
            if (!empty($result['ok'])) { update_option('lacvo_wtp_version', LACVO_WTP_VERSION, false); }
        }
        if (UpgradeRecovery::blocksCurrentUpgrade()) {
            load_plugin_textdomain('wp-tools-pro', false, dirname(LACVO_WTP_BASENAME).'/languages');
            MultisiteManager::boot();
            return;
        }
        load_plugin_textdomain('wp-tools-pro', false, dirname(LACVO_WTP_BASENAME).'/languages');
        MediaQueue::boot();
        MailTransport::boot();
        MultisiteManager::boot();
        if (Settings::isEnabled('consent')) { ConsentRetention::boot(); }
        $this->registry = new ModuleRegistry();
        $this->registry->registerDefaults();
        $this->registry->bootEnabled();
        if (is_admin()) {
            (new Admin($this->registry))->boot();
            (new AdvancedAdmin())->boot();
            (new ProductionAdmin())->boot();
            (new ReleaseAdmin())->boot();
        }
    }
}
