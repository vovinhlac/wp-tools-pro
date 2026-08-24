<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class DatabaseDiagnostics {
    public static function indexReview(): array {
        global $wpdb;
        $expected = [
            'redirects' => ['PRIMARY', 'source_match', 'enabled_order'],
            '404' => ['PRIMARY', 'path', 'last_seen', 'hits_last_seen'],
            'spam' => ['PRIMARY', 'source', 'comment_id', 'score', 'created_at'],
            'mail' => ['PRIMARY', 'status', 'created_at', 'provider_message_id', 'transport_event', 'status_created'],
            'queue' => ['PRIMARY', 'status_available', 'status_locked', 'job_type'],
            'redirect_hits' => ['PRIMARY', 'redirect_day', 'hit_date'],
            'redirect_referrers' => ['PRIMARY', 'redirect_ref_day', 'hit_date'],
            'firewall_rules' => ['PRIMARY', 'enabled_priority', 'rule_type'],
            'firewall_bans' => ['PRIMARY', 'ip_hash', 'expires_at'],
            'firewall_audit' => ['PRIMARY', 'action', 'created_at'],
            'media_quarantine' => ['PRIMARY', 'file_hash', 'status'],
            'consent' => ['PRIMARY', 'consent_key', 'user_id', 'created_at'],
        ];
        $result = [];
        foreach ($expected as $key => $indexes) {
            $table = Database::table($key);
            $rows = $wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_A);
            $present = [];
            foreach ((array) $rows as $row) { $present[(string) ($row['Key_name'] ?? '')] = true; }
            $missing = array_values(array_filter($indexes, static fn($name) => empty($present[$name])));
            $result[$key] = ['table' => $table, 'ok' => !$missing, 'missing' => $missing, 'count' => count($present)];
        }
        return $result;
    }

    public static function queryProfile(): array {
        global $wpdb;
        if (!defined('SAVEQUERIES') || !SAVEQUERIES || empty($wpdb->queries) || !is_array($wpdb->queries)) {
            return ['enabled' => false, 'count' => 0, 'time' => 0.0, 'slow' => []];
        }
        $needle = 'lacvo_wtp_';
        $count = 0;
        $time = 0.0;
        $slow = [];
        foreach ($wpdb->queries as $query) {
            $sql = (string) ($query[0] ?? '');
            if (stripos($sql, $needle) === false) { continue; }
            $duration = (float) ($query[1] ?? 0);
            $count++;
            $time += $duration;
            if ($duration >= 0.05) {
                $slow[] = ['time' => $duration, 'query' => substr(preg_replace('/\s+/', ' ', $sql), 0, 600)];
            }
        }
        usort($slow, static fn($a, $b) => $b['time'] <=> $a['time']);
        return ['enabled' => true, 'count' => $count, 'time' => $time, 'slow' => array_slice($slow, 0, 20)];
    }

    public static function tableRows(): array {
        global $wpdb;
        $keys = ['redirects','404','spam','mail','consent','redirect_hits','firewall_rules','firewall_bans','queue','redirect_referrers','firewall_audit','media_quarantine'];
        $out = [];
        foreach ($keys as $key) {
            $table = Database::table($key);
            $out[$key] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        }
        return $out;
    }
}
