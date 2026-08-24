<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class MigrationManager {
    private const OPT = 'lacvo_wtp_migration_history';
    private const BACKUP = 'lacvo_wtp_migration_backup';

    public static function run(string $from, string $to, callable $callback): array {
        $backup = self::snapshotOptions($from, $to);
        $history = self::history();
        $entry = [
            'id' => wp_generate_uuid4(),
            'from' => $from,
            'to' => $to,
            'started_at' => current_time('mysql'),
            'finished_at' => '',
            'status' => 'running',
            'message' => '',
        ];
        array_unshift($history, $entry);
        update_option(self::OPT, array_slice($history, 0, 20), false);
        update_option(self::BACKUP, $backup, false);

        try {
            $callback();
            self::finish($entry['id'], 'complete', 'Migration completed.');
            UpgradeRecovery::clear();
            return ['ok' => true, 'id' => $entry['id']];
        } catch (\Throwable $e) {
            self::restoreLatestBackup();
            UpgradeRecovery::record($from, $to, $e->getMessage());
            self::finish($entry['id'], 'rolled_back', sanitize_text_field($e->getMessage()));
            return ['ok' => false, 'id' => $entry['id'], 'message' => $e->getMessage()];
        }
    }

    public static function history(): array {
        $value = get_option(self::OPT, []);
        return is_array($value) ? $value : [];
    }

    public static function restoreLatestBackup(): bool {
        $backup = get_option(self::BACKUP, []);
        if (!is_array($backup) || empty($backup['options'])) { return false; }
        foreach ($backup['options'] as $name => $value) {
            update_option($name, $value, false);
        }
        return true;
    }

    private static function snapshotOptions(string $from, string $to): array {
        $names = [
            'lacvo_wtp_modules', 'lacvo_wtp_settings', 'lacvo_wtp_history', 'lacvo_wtp_advanced',
            'lacvo_wtp_role_snapshots', 'lacvo_wtp_version',
        ];
        $options = [];
        foreach ($names as $name) { $options[$name] = get_option($name, null); }
        return [
            'from' => $from,
            'to' => $to,
            'created_at' => current_time('mysql'),
            'options' => $options,
        ];
    }

    private static function finish(string $id, string $status, string $message): void {
        $history = self::history();
        foreach ($history as &$entry) {
            if (($entry['id'] ?? '') !== $id) { continue; }
            $entry['status'] = $status;
            $entry['message'] = $message;
            $entry['finished_at'] = current_time('mysql');
            break;
        }
        unset($entry);
        update_option(self::OPT, $history, false);
    }
}
