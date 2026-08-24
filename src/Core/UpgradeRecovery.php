<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class UpgradeRecovery {
    private const OPT = 'lacvo_wtp_upgrade_failure';

    public static function record(string $from, string $to, string $message): void {
        update_option(self::OPT, [
            'from' => sanitize_text_field($from),
            'to' => sanitize_text_field($to),
            'message' => sanitize_text_field($message),
            'failed_at' => gmdate('c'),
        ], false);
    }

    public static function clear(): void { delete_option(self::OPT); }

    public static function status(): array {
        $status = get_option(self::OPT, []);
        return is_array($status) ? $status : [];
    }

    public static function blocksCurrentUpgrade(): bool {
        $status = self::status();
        return !empty($status['to']) && (string) $status['to'] === LACVO_WTP_VERSION;
    }
}
